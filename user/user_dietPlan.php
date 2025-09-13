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
        <?php
          $totalProtein = array_sum(array_column($meals, 'protein'));
          $totalCarbs   = array_sum(array_column($meals, 'carbs'));
          $totalFat     = array_sum(array_column($meals, 'fat'));
          $totalCalories = array_sum(array_column($meals, 'calories'));
        ?>
        <div class="rounded-xl overflow-hidden shadow-md border border-gray-200 bg-white">
          <!-- Day Header -->
          <div class="bg-gradient-to-r from-emerald-600 to-green-500 text-white px-6 py-2 text-lg font-bold flex justify-between items-center">
            <span>Day <?= $dayNum ?></span>
            <div class="flex gap-2 text-sm items-center">
              <span>Calories: <?= $totalCalories ?> kcal</span>
              <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded">P: <?= $totalProtein ?>g</span>
              <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded">C: <?= $totalCarbs ?>g</span>
              <span class="bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded">F: <?= $totalFat ?>g</span>
            </div>
          </div>

          <!-- Meals & Chart side by side -->
          <div class="flex flex-col md:flex-row items-stretch">
            <!-- Meals List -->
            <div class="flex-1 p-4 divide-y divide-gray-200">
              <?php foreach($meals as $meal): ?>
                <?php
                  // Meal time badge color
                  $badgeColor = match($meal['meal_time']) {
                      'breakfast' => 'bg-yellow-200 text-yellow-800',
                      'lunch'     => 'bg-blue-200 text-blue-800',
                      'snack'     => 'bg-purple-200 text-purple-800',
                      'dinner'    => 'bg-red-200 text-red-800',
                      default     => 'bg-gray-200 text-gray-800'
                  };
                ?>
                <div class="py-3 flex justify-between items-center hover:bg-gray-50 transition">
                  <div>
                      <span class="px-2 py-0.5 rounded-full text-xs font-semibold <?= $badgeColor ?> bg-emerald-300 text-emerald-900"><?= ucfirst($meal['meal_time']) ?></span>
    <span class="ml-2 font-medium text-emerald-850"><?= htmlspecialchars($meal['meal_text']) ?></span>
  
                    <div class="mt-1 flex gap-2 text-sm">
        <span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded">P: <?= $meal['protein'] ?>g</span>
        <span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded">C: <?= $meal['carbs'] ?>g</span>
        <span class="bg-emerald-100 text-emerald-700 px-2 py-0.5 rounded">F: <?= $meal['fat'] ?>g</span>
    </div>
                  </div>
                  <!-- Swap Button -->
                  <form method="POST" action="swap_meal.php">
                    <input type="hidden" name="meal_id" value="<?= $meal['id'] ?>">
                    <button type="submit" class="px-3 py-1 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm" title="Swap this meal 🔄">🔄</button>
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
        plugins: {
          legend: { position: 'bottom' },
          tooltip: { enabled: true },
          doughnutlabel: {
            labels: [
              {
                text: '<?= $totalCalories ?> kcal',
                font: { size: 16, weight: 'bold' }
              }
            ]
          }
        }
      }
    });
  <?php endforeach; ?>
});
</script>
