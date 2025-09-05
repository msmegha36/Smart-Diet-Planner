<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . '/../config/db_conn.php');

// If no session, redirect to login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../home/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$sql = "SELECT * FROM reg WHERE id='$user_id'";
$user_res = mysqli_query($connection, $sql);
$user = mysqli_fetch_assoc($user_res);

// Fetch matching diet plan based on user preferences
$diet_sql = "SELECT * FROM diet_plans 
             WHERE goal = '{$user['goal']}' 
               AND dietary = '{$user['dietary']}' 
               AND activity = '{$user['activity']}' 
               AND meal_type = '{$user['meal_type']}' 
             LIMIT 1";

$diet_res = mysqli_query($connection, $diet_sql);
$diet = mysqli_fetch_assoc($diet_res);

// Fetch history
$history_sql = "SELECT * FROM progress_history WHERE user_id='$user_id' ORDER BY updated_at DESC";
$history_res = mysqli_query($connection, $history_sql);
?>
<?php include 'components/head.php'; ?>
<?php include 'components/navbar.php'; ?>

<main class="flex-1 overflow-y-auto p-8">
  <!-- Profile Section -->
  <section class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-xl font-bold text-emerald-700">User Profile</h2>
      <button onclick="toggleModal(true)" class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700">Update</button>
    </div>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-gray-700">
      <p><strong>Name:</strong> <?= htmlspecialchars($user['name']); ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($user['email']); ?></p>
      <p><strong>Age:</strong> <?= htmlspecialchars($user['age']); ?></p>
      <p><strong>Height:</strong> <?= htmlspecialchars($user['height']); ?> cm</p>
      <p><strong>Weight:</strong> <?= htmlspecialchars($user['weight']); ?> kg</p>
      

    </div>
  </section>

  <!-- Chart Section -->
  <section class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <h2 class="text-xl font-bold text-emerald-700 mb-4">Calorie Breakdown</h2>
    <div class="relative w-full h-80">
      <canvas id="calorieChart"></canvas>
    </div>
  </section>

  <!-- Diet Plan Section -->
  <section class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <h2 class="text-xl font-bold text-emerald-700 mb-4">Your Diet Plan</h2>
    <?php if ($diet): ?>
      <div class="text-gray-700 whitespace-pre-line">
        <?= nl2br(htmlspecialchars($diet['plan_text'])); ?>
      </div>
    <?php else: ?>
      <p class="text-gray-500">No matching plan found for your preferences.</p>
    <?php endif; ?>
  </section>

  <!-- History Section -->
  <section>
    <h2 class="text-xl font-bold text-emerald-700 mb-4">Progress History</h2>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <?php while($h = mysqli_fetch_assoc($history_res)) { ?>
        <div class="bg-white p-6 rounded-xl shadow-md">
          <h3 class="font-semibold text-gray-800">Update</h3>
          <p>Weight: <?= $h['weight']; ?> kg</p>
          <p>Height: <?= $h['height']; ?> cm</p>
          <p class="text-sm text-gray-500">Updated: <?= $h['updated_at']; ?></p>
        </div>
      <?php } ?>
    </div>
  </section>
</main>

<!-- Modal -->
<div id="updateModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl p-6">
    <div class="flex justify-between items-center border-b pb-3 mb-4">
      <h5 class="text-lg font-bold">Update Profile</h5>
      <button onclick="toggleModal(false)" class="text-gray-500 hover:text-gray-700">&times;</button>
    </div>
    <form method="POST" action="update_profile.php" class="grid grid-cols-1 md:grid-cols-2 gap-4" id="updateForm">
      <input type="hidden" name="user_id" value="<?= $user_id; ?>">
      <div>
        <label class="block text-gray-700 font-medium">Name</label>
        <input type="text" name="name" class="w-full border rounded-lg px-3 py-2" value="<?= htmlspecialchars($user['name']); ?>">
      </div>
      <div>
        <label class="block text-gray-700 font-medium">Email</label>
        <input type="email" name="email" class="w-full border rounded-lg px-3 py-2" value="<?= htmlspecialchars($user['email']); ?>">
      </div>
      <div>
        <label class="block text-gray-700 font-medium">Age</label>
        <input type="number" name="age" class="w-full border rounded-lg px-3 py-2" value="<?= htmlspecialchars($user['age']); ?>">
      </div>
      <div>
        <label class="block text-gray-700 font-medium">Height (cm)</label>
        <input type="number" name="height" class="w-full border rounded-lg px-3 py-2" value="<?= htmlspecialchars($user['height']); ?>">
      </div>
      <div>
        <label class="block text-gray-700 font-medium">Weight (kg)</label>
        <input type="number" name="weight" class="w-full border rounded-lg px-3 py-2" value="<?= htmlspecialchars($user['weight']); ?>">
      </div>
    </form>
    <div class="flex justify-end mt-6 space-x-3">
      <button onclick="toggleModal(false)" class="px-4 py-2 rounded-lg bg-gray-300 hover:bg-gray-400">Close</button>
      <button type="submit" form="updateForm" class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Save Changes</button>
    </div>
  </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
function toggleModal(show) {
  document.getElementById("updateModal").classList.toggle("hidden", !show);
  document.getElementById("updateModal").classList.toggle("flex", show);
}

const ctx = document.getElementById('calorieChart').getContext('2d');
new Chart(ctx, {
  type: 'doughnut',
  data: {
    labels: ['Protein', 'Carbs', 'Fat'],
    datasets: [{
      data: [<?= $diet['protein'] ?? 0 ?>, <?= $diet['carbs'] ?? 0 ?>, <?= $diet['fat'] ?? 0 ?>],
      backgroundColor: ['#10B981', '#3B82F6', '#F59E0B']
    }]
  },
  options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } } }
});
</script>
