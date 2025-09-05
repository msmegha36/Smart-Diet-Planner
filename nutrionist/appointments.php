<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . '/../config/db_conn.php');

// ✅ Check login for nutritionist
if (!isset($_SESSION['nutritionist_id'])) {
    header("Location: ../home/login.php");
    exit();
}

$nutritionist_id = $_SESSION['nutritionist_id'];

// Fetch appointments for this nutritionist with user details
$sql = "SELECT a.*, r.name AS user_name, r.email AS user_email, a.phone AS user_phone
        FROM appointments a
        JOIN reg r ON a.user_id = r.id
        WHERE a.nutritionist_id = ?
        ORDER BY a.appointment_date DESC, a.appointment_time DESC";

$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $nutritionist_id);
$stmt->execute();
$result = $stmt->get_result();
?>
<?php include 'components/head.php'; ?>
<?php include 'components/dashboard-nav.php'; ?>

<main class="bg-white w-full pt-32 px-6">
  <h2 class="text-3xl font-bold text-emerald-700 mb-6">👩‍⚕️ My Clients’ Appointments</h2>

  <?php if ($result->num_rows > 0): ?>
    <div class="overflow-x-auto bg-white rounded-xl shadow-lg">
      <table class="w-full table-auto border-collapse">
        <thead class="bg-emerald-600 text-white">
          <tr>
            <th class="px-4 py-3 text-left">Client</th>
            <th class="px-4 py-3 text-left">Email</th>
            <th class="px-4 py-3 text-left">Phone</th>
            <th class="px-4 py-3 text-left">Date</th>
            <th class="px-4 py-3 text-left">Time</th>
            <th class="px-4 py-3 text-left">Notes</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">Booked On</th>
          </tr>
        </thead>
        <tbody class="text-gray-700">
          <?php while($row = $result->fetch_assoc()): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="px-4 py-3 font-semibold"><?= htmlspecialchars($row['user_name']); ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['user_email']); ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['user_phone']); ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['appointment_date']); ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars(substr($row['appointment_time'], 0, 5)); ?></td>
              <td class="px-4 py-3"><?= $row['notes'] ? htmlspecialchars($row['notes']) : '—'; ?></td>
              <td class="px-4 py-3">
                <?php if ($row['status'] == 'pending'): ?>
                  <span class="px-2 py-1 text-sm rounded bg-yellow-100 text-yellow-700">Pending</span>
                <?php elseif ($row['status'] == 'confirmed'): ?>
                  <span class="px-2 py-1 text-sm rounded bg-green-100 text-green-700">Confirmed</span>
                <?php else: ?>
                  <span class="px-2 py-1 text-sm rounded bg-red-100 text-red-700">Cancelled</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500"><?= htmlspecialchars($row['created_at']); ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="text-gray-500">No appointments booked with you yet.</p>
  <?php endif; ?>
</main>
