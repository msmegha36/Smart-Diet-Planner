<?php
session_start(); // Ensure session is started
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Include database connection file
include(__DIR__ . '/../config/db_conn.php');

// Check if $connection is successfully set by db_conn.php
if (!isset($connection) || (is_object($connection) && $connection->connect_error)) {
    echo "<main class='pt-32 px-6 min-h-screen'><p class='text-center text-red-500 font-bold'>Database connection failed: Please check 'db_conn.php'.</p></main>";
    include 'components/footer.php';
    exit();
}

include 'components/head.php'; 
include 'components/navbar.php'; 

// --- 1. Security and Input Checks ---
if (!isset($_SESSION['user_id'])) {
    // If the user is not logged in, redirect them to the login page
    echo"<script> alert('Login First');</script>"; 
    ?><script> location.replace("nutrionist.php"); </script><?php
    die();
}

if (!isset($_GET['nutritionist_id']) || !is_numeric($_GET['nutritionist_id'])) {
    header("Location: nutritionists.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$nutritionist_id = intval($_GET['nutritionist_id']);

// --- 1.5. Fetch User Details (FIXED: Only fetching 'name' and 'email' from 'reg') ---
// Initialize user array without the 'phone' key
$user = ['name' => '', 'email' => '']; 

// Line 39 in this file should be the prepare statement below.
// *** CRITICAL FIX: The SELECT query MUST NOT include the 'phone' column. ***
$stmt = $connection->prepare("SELECT name, email FROM reg WHERE id = ? LIMIT 1"); 
$stmt->bind_param("i", $user_id);
$stmt->execute();
$userResult = $stmt->get_result();

if ($userResult->num_rows > 0) {
    $user = $userResult->fetch_assoc();
}
$stmt->close();


// --- 2. Fetch Nutritionist Details ---
$stmt = $connection->prepare("SELECT * FROM nutritionists WHERE id = ? AND status = 'approved' LIMIT 1");
$stmt->bind_param("i", $nutritionist_id);
$stmt->execute();
$nutritionistResult = $stmt->get_result();

if ($nutritionistResult->num_rows === 0) {
    echo "<main class='pt-32 px-6 min-h-screen'><p class='text-center text-red-500'>Nutritionist not found or not approved.</p></main>";
    include 'components/footer.php';
    exit();
}
$nutritionist = $nutritionistResult->fetch_assoc();
$stmt->close();


// --- 3. Check for UPCOMING Appointment ---
$upcomingAppointment = null;
$currentDateTime = date('Y-m-d H:i:s'); 

$sql = "SELECT * FROM appointments 
        WHERE user_id = ? 
          AND nutritionist_id = ? 
          AND status IN ('pending', 'confirmed') 
          AND CONCAT(appointment_date, ' ', appointment_time) > ? 
        ORDER BY appointment_date ASC, appointment_time ASC 
        LIMIT 1";

$stmt = $connection->prepare($sql);
$stmt->bind_param("iis", $user_id, $nutritionist_id, $currentDateTime);
$stmt->execute();
$appointmentResult = $stmt->get_result();

if ($appointmentResult->num_rows > 0) {
    $upcomingAppointment = $appointmentResult->fetch_assoc();
}
$stmt->close();

// --- 4. Fetch PAST Appointments ---
$pastAppointments = [];
$sql = "SELECT * FROM appointments 
        WHERE user_id = ? 
          AND nutritionist_id = ? 
          AND CONCAT(appointment_date, ' ', appointment_time) <= ? 
        ORDER BY appointment_date DESC, appointment_time DESC";

$stmt = $connection->prepare($sql);
$stmt->bind_param("iis", $user_id, $nutritionist_id, $currentDateTime);
$stmt->execute();
$pastResult = $stmt->get_result();

if ($pastResult->num_rows > 0) {
    while ($row = $pastResult->fetch_assoc()) {
        $pastAppointments[] = $row;
    }
}
$stmt->close();
?>

<!-- MAIN CONTENT -->
<main class="max-w-6xl mx-auto pt-32 pb-12 px-6 space-y-12">

    <!-- Nutritionist Profile -->
    <div class="bg-white rounded-2xl shadow-xl p-8 flex flex-col md:flex-row items-center md:items-start md:space-x-10">
        <!-- Left Image -->
        <img src="../nutrionist/<?php echo htmlspecialchars($nutritionist['image'] ?? 'images/default-nutritionist.jpg'); ?>" 
             alt="<?php echo htmlspecialchars($nutritionist['name']); ?>" 
             class="w-56 h-56 object-cover object-center rounded-full shadow-md ring-4 ring-green-200">

        <!-- Right Details -->
        <div class="mt-6 md:mt-0 text-center md:text-left">
            <h2 class="text-4xl font-extrabold text-green-700"><?php echo htmlspecialchars($nutritionist['name']); ?></h2>
            <p class="text-gray-600 text-lg mt-2 font-semibold">
                <?php echo htmlspecialchars($nutritionist['specialization']); ?>
            </p>
            <p class="text-gray-500 text-md mt-1">
                 <?php echo intval($nutritionist['experience']); ?> years of experience
            </p>
            <p class="text-gray-700 mt-4 leading-relaxed max-w-xl italic"><?php echo htmlspecialchars($nutritionist['description']); ?></p>
        </div>
    </div>

    <!-- Appointment Section (Conditional) -->
    <?php if ($upcomingAppointment): ?>
        <!-- Scenario 1: User has an UPCOMING appointment (Styled to fit the theme) -->
        <div class="bg-emerald-50 border-l-4 border-emerald-500 p-8 rounded-2xl shadow-xl">
            <h3 class="text-3xl font-extrabold text-emerald-800 mb-6 flex items-center">
                <svg class="w-8 h-8 mr-3 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                Upcoming Session Booked!
            </h3>
            <p class="text-lg text-gray-700 mb-6">You have an active session (Pending/Confirmed) scheduled with this nutritionist.</p>
            
            <div class="grid grid-cols-2 gap-4 bg-white p-4 rounded-lg shadow-inner border border-emerald-100">
                <p class="text-sm text-gray-500 font-semibold">Date</p>
                <p class="text-sm text-gray-800 font-mono"><?php echo htmlspecialchars($upcomingAppointment['appointment_date']); ?></p>
                
                <p class="text-sm text-gray-500 font-semibold">Time</p>
                <p class="text-sm text-gray-800 font-mono"><?php echo htmlspecialchars(substr($upcomingAppointment['appointment_time'], 0, 5)); ?></p>
                
                <p class="text-sm text-gray-500 font-semibold">Status</p>
                <p class="text-sm">
                    <span class="px-3 py-1 text-xs rounded-full font-bold <?php echo $upcomingAppointment['status'] === 'confirmed' ? 'bg-green-200 text-green-800' : 'bg-yellow-200 text-yellow-800'; ?>">
                        <?php echo ucfirst(htmlspecialchars($upcomingAppointment['status'])); ?>
                    </span>
                </p>
                
                <?php if (!empty($upcomingAppointment['notes'])): ?>
                <p class="text-sm text-gray-500 font-semibold col-span-2">Your Notes</p>
                <p class="text-sm text-gray-800 col-span-2 italic">"<?php echo htmlspecialchars($upcomingAppointment['notes']); ?>"</p>
                <?php endif; ?>
            </div>
            <p class="mt-6 text-sm text-emerald-700">Please check your <a href="../user/my_appointments.php" class="underline font-medium hover:text-emerald-900">My Appointments</a> page for details.</p>
        </div>
    <?php else: ?>
        <!-- Scenario 2: No upcoming appointment, show the booking form -->
        <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
            <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">Book an Appointment</h3>
            
            <form action="book_appointment.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Hidden nutritionist_id and user_id -->
                <input type="hidden" name="nutritionist_id" value="<?php echo $nutritionist_id; ?>">
                <input type="hidden" name="user_id" value="<?php echo $user_id; ?>">

                <!-- Name (Pre-filled) -->
                <div class="md:col-span-1">
                    <label class="block text-gray-700 font-medium mb-1">Your Name</label>
                    <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required 
                           class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 bg-gray-50/50" disabled>
                    <!-- Send name value via hidden field if text field is disabled -->
                    <input type="hidden" name="name" value="<?php echo htmlspecialchars($user['name']); ?>">
                </div>

                <!-- Email (Pre-filled) -->
                <div class="md:col-span-1">
                    <label class="block text-gray-700 font-medium mb-1">Email</label>
                    <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required 
                           class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500 bg-gray-50/50" disabled>
                    <input type="hidden" name="email" value="<?php echo htmlspecialchars($user['email']); ?>">
                </div>

                <!-- Phone (Input is required but value starts empty) -->
                <div class="md:col-span-1">
                    <label class="block text-gray-700 font-medium mb-1">Phone</label>
                    <input type="tel" name="phone" value="" required 
                           class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500" placeholder="Enter your best contact number">
                </div>

                <!-- Date -->
                <div class="md:col-span-1">
                    <label class="block text-gray-700 font-medium mb-1">Preferred Date</label>
                    <input type="date" name="date" required 
                           class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500">
                </div>

                <!-- Time -->
                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-medium mb-1">Preferred Time</label>
                    <input type="time" name="time" required 
                           class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500">
                </div>

                <!-- Notes -->
                <div class="md:col-span-2">
                    <label class="block text-gray-700 font-medium mb-1">Additional Notes</label>
                    <textarea name="notes" rows="4" placeholder="Briefly describe your goals or specific concerns..."
                              class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500"></textarea>
                </div>

                <!-- Submit -->
                <div class="md:col-span-2">
                    <button type="submit" name="book_appointment" 
                            class="w-full bg-green-600 text-white font-semibold py-3 rounded-lg hover:bg-green-700 transition shadow-lg hover:shadow-xl">
                        Confirm Appointment Request
                    </button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <!-- Appointment History Table -->
    <div class="bg-white rounded-2xl shadow-xl p-8 border border-gray-100">
        <h3 class="text-2xl font-bold text-gray-800 mb-6">Past Appointment History</h3>
        
        <?php if (!empty($pastAppointments)): ?>
            <div class="overflow-x-auto rounded-lg border border-gray-200">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Time</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th scope="col" class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Notes</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-100">
                        <?php foreach ($pastAppointments as $appointment): ?>
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                                    <?php echo htmlspecialchars($appointment['appointment_date']); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <?php echo htmlspecialchars(substr($appointment['appointment_time'], 0, 5)); ?>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php 
                                            if ($appointment['status'] === 'completed') echo 'bg-blue-100 text-blue-800';
                                            else if ($appointment['status'] === 'cancelled') echo 'bg-red-100 text-red-800';
                                            else if ($appointment['status'] === 'confirmed') echo 'bg-green-100 text-green-800';
                                            else echo 'bg-gray-100 text-gray-800'; 
                                        ?>">
                                        <?php echo ucfirst(htmlspecialchars($appointment['status'])); ?>
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-500 max-w-xs truncate">
                                    <?php echo htmlspecialchars($appointment['notes'] ?? 'N/A'); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php else: ?>
            <p class="text-gray-500 text-center py-4">You have no past appointment history with this nutritionist yet.</p>
        <?php endif; ?>
    </div>
</main>

<?php include 'components/footer.php'; ?>
<?php 
// Close connection only if it was successfully opened
if (isset($connection) && is_object($connection) && method_exists($connection, 'close')) {
    $connection->close();
}
?>
