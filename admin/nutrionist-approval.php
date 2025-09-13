<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);


// ✅ Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

include(__DIR__ . '/../config/db_conn.php');


// Handle Approve/Reject Actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['nutritionist_id'])) {
    $nutritionist_id = intval($_POST['nutritionist_id']);
    $action = $_POST['action'];

    if ($action === 'approve') {
        $update = $connection->prepare("UPDATE nutritionists SET status = 'approved' WHERE id = ?");
        $update->bind_param("i", $nutritionist_id);
        $update->execute();
    } elseif ($action === 'reject') {
        $delete = $connection->prepare("DELETE FROM nutritionists WHERE id = ?");
        $delete->bind_param("i", $nutritionist_id);
        $delete->execute();
    }

    header("Location: nutrionist-approval.php"); // Refresh to see changes
    exit();
}

// Fetch Nutritionists
// Fetch only pending Nutritionists
$sql = "SELECT * FROM nutritionists WHERE status = 'pending' ORDER BY created_at DESC";

$result = $connection->query($sql);
?>

<?php include 'components/head.php'; ?>
<?php include 'components/dashboard-nav.php'; ?>

<main class="bg-gray-100 w-full pt-28 px-6">
  <h2 class="text-3xl font-bold text-emerald-400 mb-6">👩‍⚕️ Manage Nutritionists</h2>

  <?php if ($result->num_rows > 0): ?>
    <div class="overflow-x-auto bg-white rounded-xl shadow-lg">
      <table class="w-full table-auto border-collapse">
        <thead class="bg-gray-900 text-white">
          <tr>
            <th class="px-4 py-3 text-left">Profile</th>
            <th class="px-4 py-3 text-left">Name</th>
            <th class="px-4 py-3 text-left">Email</th>
            <th class="px-4 py-3 text-left">Phone</th>
            <th class="px-4 py-3 text-left">Specialization</th>
            <th class="px-4 py-3 text-left">Experience</th>
            <th class="px-4 py-3 text-left">Status</th>
            <th class="px-4 py-3 text-left">Action</th>
            <th class="px-4 py-3 text-left">Created At</th>
          </tr>
        </thead>
        <tbody class="text-gray-700">
          <?php while($row = $result->fetch_assoc()): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="px-4 py-3">
                <?php if (!empty($row['image'])): ?>
                  <img src="../nutrionist/<?= htmlspecialchars($row['image']); ?>" 
                       alt="<?= htmlspecialchars($row['name']); ?>" 
                       class="w-12 h-12 rounded-full object-cover">
                <?php else: ?>
                  <span class="text-gray-400">No Image</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 font-semibold"><?= htmlspecialchars($row['name']); ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['email']); ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['phone']); ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['specialization']); ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['experience']); ?> yrs</td>
              <td class="px-4 py-3">
                <?php if ($row['status'] === 'pending'): ?>
                  <span class="px-2 py-1 text-sm rounded bg-yellow-100 text-yellow-700">Pending</span>
                <?php elseif ($row['status'] === 'approved'): ?>
                  <span class="px-2 py-1 text-sm rounded bg-green-100 text-green-700">Approved</span>
                <?php else: ?>
                  <span class="px-2 py-1 text-sm rounded bg-red-100 text-red-700">Rejected</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3">
                <?php if ($row['status'] === 'pending'): ?>
                  <form method="POST" class="flex gap-2">
                    <input type="hidden" name="nutritionist_id" value="<?= $row['id']; ?>">
                    <button type="submit" name="action" value="approve"
                      class="px-3 py-1 rounded bg-green-600 text-white hover:bg-green-700 text-sm">
                      ✅ Approve
                    </button>
                    <button type="submit" name="action" value="reject"
                      class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700 text-sm"
                      onclick="return confirm('Are you sure you want to reject this nutritionist?');">
                      ❌ Reject
                    </button>
                  </form>
                <?php elseif ($row['status'] === 'approved'): ?>
                  <span class="text-green-600 font-semibold">✔ Already Approved</span>
                <?php endif; ?>
              </td>
              <td class="px-4 py-3 text-sm text-gray-500"><?= htmlspecialchars($row['created_at']); ?></td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="text-gray-500">No nutritionists registered yet.</p>
  <?php endif; ?>
</main>
