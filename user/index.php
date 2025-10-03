<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . '/../config/db_conn.php');

// Redirect if not logged in
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
$diet_sql = "SELECT SUM(protein) AS protein, SUM(carbs) AS carbs, SUM(fat) AS fat, GROUP_CONCAT(meal_text SEPARATOR '\n') AS plan_text
             FROM diet_plans
             WHERE goal = '{$user['goal']}' 
               AND dietary = '{$user['dietary']}' 
               AND activity = '{$user['activity']}' 
               AND meal_type = '{$user['meal_type']}'";
$diet_res = mysqli_query($connection, $diet_sql);
$diet = mysqli_fetch_assoc($diet_res);

// Fetch progress history
$history_sql = "SELECT * FROM progress_history WHERE user_id='$user_id' ORDER BY updated_at DESC";
$history_res = mysqli_query($connection, $history_sql);


$bmi = 0;
$bmi_msg = "";
if (!empty($user['height']) && !empty($user['weight'])) {
    $height_m = $user['height'] / 100; // convert cm to m
    $bmi = $user['weight'] / ($height_m * $height_m);

    if ($bmi < 18.5) {
        $bmi_msg = "Underweight";
    } elseif ($bmi < 24.9) {
        $bmi_msg = "Normal";
    } elseif ($bmi < 29.9) {
        $bmi_msg = "Overweight";
    } else {
        $bmi_msg = "Obese";
    }
}


$alert_msg = "";
if ($bmi > 0) {
    if ($bmi < 18.5 && $user['goal'] === 'weight_loss') {
        $alert_msg = "⚠️ Your BMI indicates Underweight but your goal is Weight Loss. Please reconsider.";
    } elseif ($bmi > 25 && $user['goal'] === 'weight_gain') {
        $alert_msg = "⚠️ Your BMI indicates Overweight but your goal is Weight Gain. Please reconsider.";
    } elseif ($bmi > 30 && $user['goal'] === 'muscle_build' && $user['activity'] === 'light') {
        $alert_msg = "⚠️ Your BMI is in Obese range. Muscle building with low activity may not be safe.";
    }
}



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

  <!-- Calorie Breakdown Chart -->
  <section class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <h2 class="text-xl font-bold text-emerald-700 mb-4">Calorie Breakdown</h2>
    <div class="relative w-full h-80">
      <canvas id="calorieChart"></canvas>
    </div>
  </section>

 <?php  if(!empty($alert_msg)): ?>
  <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
    <?= $alert_msg ?>
  </div>
<?php endif; ?>


  <!-- Diet Plan Section 
  <section class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <h2 class="text-xl font-bold text-emerald-700 mb-4">Your Diet Plan</h2>
    <?php // if ($diet && ($diet['plan_text'] || $diet['protein'] || $diet['carbs'] || $diet['fat'])): ?>
      <div class="text-gray-700 whitespace-pre-line">
        <? // nl2br(htmlspecialchars($diet['plan_text'])); ?>
      </div>
    <?php //else: ?>
      <p class="text-gray-500">No matching plan found for your preferences.</p>
    <?php // endif; ?>
  </section> -->

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

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function toggleModal(show) {
  const modal = document.getElementById("updateModal");
  modal.classList.toggle("hidden", !show);
  modal.classList.toggle("flex", show);
}

// Ensure DOM is loaded before initializing chart
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('calorieChart');
    if(ctx) {
        const calorieChart = new Chart(ctx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: ['Protein', 'Carbs', 'Fat'],
                datasets: [{
                    data: [
                        <?= !empty($diet['protein']) ? $diet['protein'] : 0 ?>,
                        <?= !empty($diet['carbs']) ? $diet['carbs'] : 0 ?>,
                        <?= !empty($diet['fat']) ? $diet['fat'] : 0 ?>
                    ],
                    backgroundColor: ['#10B981', '#3B82F6', '#F59E0B'],
                    borderWidth: 1
                }]
            },
            options: { 
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    }
});
</script>

</script>
