<?php
session_start();

// Check if user logged in
if (!isset($_SESSION['user_id'])) {
        echo"<script> alert('Login First');</script>"; 
        ?><script> location.replace("nutrionist.php"); </script><?php
      
        die();
      }

error_reporting(E_ALL);
ini_set('display_errors', 1);

include 'components/head.php';
include 'components/navbar.php';
include(__DIR__ . '/../config/db_conn.php'); // gives $connection



$user_id = $_SESSION['user_id'];

// Fetch user details
$user_sql = "SELECT name, email FROM reg WHERE id = $user_id";
$user_result = mysqli_query($connection, $user_sql);
$user = mysqli_fetch_assoc($user_result);

// Check nutritionist_id
if (!isset($_GET['nutritionist_id'])) {
    header("Location: nutritionist.php");
    exit();
}
$nutri_id = intval($_GET['nutritionist_id']);

// Fetch nutritionist details
$nutri_sql = "SELECT * FROM nutritionists WHERE id = $nutri_id";
$nutri_result = mysqli_query($connection, $nutri_sql);
$nutritionist = mysqli_fetch_assoc($nutri_result);

if (!$nutritionist) {
    header("Location: nutritionist.php");
    exit();
}
?>

<!-- MAIN CONTENT -->
<main class="max-w-6xl mx-auto pt-32 px-6 space-y-12">

  <!-- Nutritionist Profile -->
  <div class="bg-white rounded-2xl shadow-lg p-8 flex flex-col md:flex-row items-center md:items-start md:space-x-10">
    <!-- Left Image -->
    <img src="../nutrionist//<?php echo htmlspecialchars($nutritionist['image']); ?>" 
         alt="<?php echo htmlspecialchars($nutritionist['name']); ?>" 
         class="w-56 h-56 object-cover rounded-full shadow-md">

    <!-- Right Details -->
    <div class="mt-6 md:mt-0 text-center md:text-left">
      <h2 class="text-3xl font-bold text-green-600"><?php echo htmlspecialchars($nutritionist['name']); ?></h2>
      <p class="text-gray-500 text-lg mt-2">
        <?php echo htmlspecialchars($nutritionist['specialization']); ?> | 
        <?php echo intval($nutritionist['experience']); ?> years
      </p>
      <p class="text-gray-600 mt-4 leading-relaxed max-w-xl"><?php echo htmlspecialchars($nutritionist['description']); ?></p>
    </div>
  </div>

  <!-- Appointment Form -->
  <div class="bg-white rounded-2xl shadow-lg p-8">
    <h3 class="text-2xl font-bold text-gray-800 mb-6 text-center">Book an Appointment</h3>
    
    <form action="book_appointment.php" method="POST" class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <!-- Hidden nutritionist_id -->
      <input type="hidden" name="nutritionist_id" value="<?php echo $nutri_id; ?>">

      <!-- Name -->
      <div class="md:col-span-1">
        <label class="block text-gray-700 font-medium mb-1">Your Name</label>
        <input type="text" name="name" value="<?php echo htmlspecialchars($user['name']); ?>" required 
               class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500">
      </div>

      <!-- Email -->
      <div class="md:col-span-1">
        <label class="block text-gray-700 font-medium mb-1">Email</label>
        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" required 
               class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500">
      </div>

      <!-- Phone -->
      <div class="md:col-span-1">
        <label class="block text-gray-700 font-medium mb-1">Phone</label>
        <input type="tel" name="phone" required 
               class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500">
      </div>

      <!-- Date -->
      <div class="md:col-span-1">
        <label class="block text-gray-700 font-medium mb-1">Preferred Date</label>
        <input type="date" name="date" required 
               class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500">
      </div>

      <!-- Time -->
      <div class="md:col-span-1">
        <label class="block text-gray-700 font-medium mb-1">Preferred Time</label>
        <input type="time" name="time" required 
               class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500">
      </div>

      <!-- Notes -->
      <div class="md:col-span-2">
        <label class="block text-gray-700 font-medium mb-1">Additional Notes</label>
        <textarea name="notes" rows="4" 
                  class="w-full border rounded-lg px-4 py-2 focus:ring-2 focus:ring-green-500"></textarea>
      </div>

      <!-- Submit -->
      <div class="md:col-span-2">
        <button type="submit" 
                class="w-full bg-green-600 text-white font-semibold py-3 rounded-lg hover:bg-green-700 transition">
          Confirm Appointment
        </button>
      </div>
    </form>
  </div>
</main>

<?php include 'components/footer.php'; ?>
