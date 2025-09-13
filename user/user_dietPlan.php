<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . '/../config/db_conn.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../home/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$user = mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM reg WHERE id='$user_id'"));

// Fetch user diet plans
$plans_res = mysqli_query($connection, "
    SELECT * FROM user_diet_plans 
    WHERE user_id='$user_id' 
    ORDER BY day_number ASC, FIELD(meal_time, 'breakfast','lunch','snack','dinner')
");

// Group plans by day
$userPlans = [];
while ($row = mysqli_fetch_assoc($plans_res)) {
    $day = $row['day_number'];
    if(!isset($userPlans[$day])) $userPlans[$day] = [];
    $userPlans[$day][] = $row;
}

// Capture session messages
$successMsg = $_SESSION['success'] ?? '';
$errorMsg = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<?php include 'components/head.php'; ?>
<?php include 'components/navbar.php'; ?>

<main class="flex-1 overflow-y-auto p-8">
  <h2 class="text-3xl font-bold text-emerald-700 mb-6">🍽 Your Weekly Diet Plans</h2>

  <!-- Display session messages -->
  <?php if($successMsg): ?>
    <div class="mb-4 p-4 bg-green-100 border border-green-400 text-green-800 rounded-lg">
      <?= htmlspecialchars($successMsg) ?>
    </div>
  <?php endif; ?>
  <?php if($errorMsg): ?>
    <div class="mb-4 p-4 bg-red-100 border border-red-400 text-red-800 rounded-lg">
      <?= htmlspecialchars($errorMsg) ?>
    </div>
  <?php endif; ?>

  <?php if(!empty($userPlans)): ?>
    <div class="space-y-6">
      <?php foreach($userPlans as $dayNum => $meals): ?>
        <div class="rounded-xl overflow-hidden shadow-md border border-gray-200 bg-white">
          <!-- Day Header -->
          <div class="bg-emerald-600 text-white px-6 py-2 text-lg font-bold flex justify-between items-center">
            <span>Day <?= $dayNum ?></span>
            <?php
              $totalProtein = array_sum(array_column($meals, 'protein'));
              $totalCarbs   = array_sum(array_column($meals, 'carbs'));
              $totalFat     = array_sum(array_column($meals, 'fat'));
              $totalCalories = array_sum(array_column($meals, 'calories'));
            ?>
            <span class="text-sm">Calories: <?= $totalCalories ?> kcal</span>
          </div>

          <!-- Meals & Chart side by side -->
          <div class="flex flex-col md:flex-row items-stretch">
            <!-- Meals List -->
            <div class="flex-1 p-4 divide-y divide-gray-200">
              <?php foreach($meals as $meal): ?>
                <div class="py-3 flex justify-between items-center hover:bg-gray-50 transition">
                  <div>
                    <strong class="text-emerald-700"><?= ucfirst($meal['meal_time']) ?>:</strong>
                    <span class="ml-2"><?= htmlspecialchars($meal['meal_text']) ?></span>
                    <div class="mt-1 text-sm text-gray-500">
                      Protein: <?= $meal['protein'] ?>g | Carbs: <?= $meal['carbs'] ?>g | Fat: <?= $meal['fat'] ?>g
                    </div>
                  </div>
                  <!-- Swap Button -->
                  <form method="POST" action="swap_meal.php">
                    <input type="hidden" name="meal_id" value="<?= $meal['id'] ?>">
                    <button type="submit" class="px-3 py-1 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm">🔄</button>
                  </form>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Chart -->
            <div class="w-full md:w-64 p-4 flex justify-center items-center border-l bg-gray-50">
              <canvas id="macroChart<?= $dayNum ?>" class="w-48 h-48"></canvas>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php else: ?>
    <p class="text-gray-500">No diet plans found. Please add your diet plans first.</p>
  <?php endif; ?>
</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
  <?php foreach($userPlans as $dayNum => $meals): ?>
    const ctx<?= $dayNum ?> = document.getElementById('macroChart<?= $dayNum ?>').getContext('2d');
    new Chart(ctx<?= $dayNum ?>, {
      type: 'doughnut',
      data: {
        labels: ['Protein', 'Carbs', 'Fat'],
        datasets: [{
          data: [
            <?= array_sum(array_column($meals, 'protein')) ?>,
            <?= array_sum(array_column($meals, 'carbs')) ?>,
            <?= array_sum(array_column($meals, 'fat')) ?>
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
  <?php endforeach; ?>
});
</script>
