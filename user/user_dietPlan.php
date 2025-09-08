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
$plans_res = mysqli_query($connection, "SELECT * FROM user_diet_plans WHERE user_id='$user_id' ORDER BY day_number ASC, 
                                        FIELD(meal_time, 'breakfast','lunch','snack','dinner')");

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
  <h2 class="text-3xl font-bold text-emerald-700 mb-6">Your Diet Plans</h2>

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
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php foreach($userPlans as $dayNum => $meals): ?>
        <div class="bg-white p-6 rounded-xl shadow-lg hover:shadow-xl transition-shadow duration-300">
          <h3 class="text-xl font-bold text-emerald-700 mb-4 text-center">Day <?= $dayNum ?></h3>
          
          <!-- Macro Chart for the day -->
          <?php
            $totalProtein = $totalCarbs = $totalFat = 0;
            foreach($meals as $meal){
                $totalProtein += $meal['protein'];
                $totalCarbs += $meal['carbs'];
                $totalFat += $meal['fat'];
            }
          ?>
          <div class="relative w-full h-40 mb-4">
            <canvas id="macroChart<?= $dayNum ?>"></canvas>
          </div>

          <?php foreach($meals as $meal): ?>
            <div class="mb-4 p-3 border rounded-lg bg-gray-50 hover:bg-gray-100 transition-colors duration-200">
              <strong class="text-gray-800"><?= strtoupper($meal['meal_time']) ?></strong>: <?= htmlspecialchars($meal['meal_text']) ?>
              <div class="mt-1 text-sm text-gray-500">
                Protein: <?= $meal['protein'] ?>g | Carbs: <?= $meal['carbs'] ?>g | Fat: <?= $meal['fat'] ?>g | Calories: <?= $meal['calories'] ?> kcal
              </div>
              <!-- Meal swap button -->
              <form method="POST" action="swap_meal.php" class="mt-2">
                <input type="hidden" name="meal_id" value="<?= $meal['id'] ?>">
                <button type="submit" class="px-3 py-1 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-sm">Swap Meal</button>
              </form>
            </div>
          <?php endforeach; ?>
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
