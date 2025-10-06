<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . '/../config/db_conn.php');

// Check login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../home/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch appointments for the logged-in user with nutritionist details
$sql = "SELECT a.*, n.name AS nutritionist_name, n.specialization 
        FROM appointments a
        JOIN nutritionists n ON a.nutritionist_id = n.id
        WHERE a.user_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<?php include 'components/head.php'; ?>
<?php include 'components/navbar.php'; ?>

<main class="w-full pt-32 px-6 min-h-screen bg-gray-50">
  <h2 class="text-3xl font-bold text-emerald-700 mb-6">📅 My Appointments</h2>

  <?php if ($result->num_rows > 0): ?>
    <div class="overflow-x-auto bg-white rounded-xl shadow-xl">
      <table class="w-full table-auto border-collapse">
        <thead class="bg-emerald-600 text-white">
          <tr>
            <th class="px-4 py-3 text-left">Nutritionist</th>
            <th class="px-4 py-3 text-left">Specialization</th>
            <th class="px-4 py-3 text-left">Date</th>
            <th class="px-4 py-3 text-left">Time</th>
            <th class="px-4 py-3 text-left">Notes</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">Booked On</th>
            <th class="px-4 py-3 text-center">Details</th> <!-- New Header -->
          </tr>
        </thead>
        <tbody class="text-gray-700">
          <?php while($row = $result->fetch_assoc()): ?>
            <tr class="border-b border-gray-200 hover:bg-emerald-50 transition duration-150">
              <td class="px-4 py-3 font-semibold"><?= htmlspecialchars($row['nutritionist_name']); ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['specialization']); ?></td>
              <td class="px-4 py-3 font-medium"><?= htmlspecialchars($row['appointment_date']); ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars(substr($row['appointment_time'], 0, 5)); ?></td>
              <td class="px-4 py-3 text-sm italic"><?= $row['notes'] ? htmlspecialchars($row['notes']) : '—'; ?></td>
              <td class="px-4 py-3">
                <?php if ($row['status'] == 'pending'): ?>
                  <span class="px-2 py-1 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700">Pending</span>
                <?php elseif ($row['status'] == 'confirmed'): ?>
                  <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700">Confirmed</span>
                <?php else: ?>
                  <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-700">Cancelled</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500"><?= htmlspecialchars($row['created_at']); ?></td>
              
              <!-- New Details Button -->
              <td class="px-4 py-3 text-center">
                <a href="../home/contact.php?nutritionist_id=<?= $row['nutritionist_id']; ?>" 
                   class="inline-block px-3 py-1 text-sm rounded-lg bg-emerald-500 text-white font-medium hover:bg-emerald-600 transition duration-150 shadow-md">
                   View Profile
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="p-6 bg-white rounded-xl shadow-lg mt-4">
      <p class="text-gray-500 font-medium">No appointments booked yet. Time to connect with a nutritionist!</p>
    </div>
  <?php endif; ?>
</main>
<?php include 'components/footer.php'; ?>
<?php
// Close the statement and connection
if (isset($stmt)) {
    $stmt->close();
}
if (isset($connection)) {
    $connection->close();
}
?>
