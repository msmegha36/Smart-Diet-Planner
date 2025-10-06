<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . '/../config/db_conn.php');

// ✅ Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle Delete Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_id'])) {
    $nutritionist_id = intval($_POST['delete_id']);
    
    $delete = $connection->prepare("DELETE FROM nutritionists WHERE id = ?");
    $delete->bind_param("i", $nutritionist_id);
    $delete->execute();

    header("Location: nutrionist.php"); // Refresh page
    exit();
}

// Fetch only approved nutritionists
$sql = "SELECT * FROM nutritionists WHERE status = 'approved' ORDER BY created_at DESC";
$result = $connection->query($sql);
?>

<?php include 'components/head.php'; ?>
<?php include 'components/dashboard-nav.php'; ?>

<main class="bg-gray-100 w-full pt-28 px-6">
  <h2 class="text-3xl font-bold text-green-600 mb-6">✅ Approved Nutritionists</h2>

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
            <th class="px-4 py-3 text-left">Created At</th>
            <th class="px-4 py-3 text-left">Action</th>
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
              <td class="px-4 py-3 text-sm text-gray-500"><?= htmlspecialchars($row['created_at']); ?></td>
              <td class="px-4 py-3">
                <form method="POST" onsubmit="return confirm('Are you sure you want to delete this nutritionist?');">
                  <input type="hidden" name="delete_id" value="<?= $row['id']; ?>">
                  <button type="submit"
                    class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700 text-sm">
                    ❌ Delete
                  </button>
                </form>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="text-gray-500">No approved nutritionists found.</p>
  <?php endif; ?>
</main>
