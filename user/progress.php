<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);
include(__DIR__ . '/../config/db_conn.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch weight history
$historyQuery = mysqli_query($connection, "SELECT weight, updated_at FROM progress_history WHERE user_id='$user_id' ORDER BY updated_at ASC");
$weights = [];
$dates   = [];
while ($row = mysqli_fetch_assoc($historyQuery)) {
    $weights[] = $row['weight'];
    $dates[]   = date("M d", strtotime($row['updated_at']));
}

// Fetch latest user data for BMI
$user = mysqli_fetch_assoc(mysqli_query($connection, "SELECT weight, height FROM reg WHERE id='$user_id'"));

// Calculate latest BMI
$latestWeight = end($weights) ?: $user['weight'];
$height_m = $user['height'] / 100;
$latestBMI = $height_m > 0 ? round($latestWeight / ($height_m * $height_m), 1) : 0;

// Determine BMI category
$bmiCategory = "Normal";
if ($latestBMI < 18.5) $bmiCategory = "Underweight";
elseif ($latestBMI < 24.9) $bmiCategory = "Normal";
elseif ($latestBMI < 29.9) $bmiCategory = "Overweight";
else $bmiCategory = "Obese";
?>

<?php include 'components/head.php'; ?>
<?php include 'components/navbar.php'; ?>

<main class="flex-1 overflow-y-auto p-8">
  <h2 class="text-3xl font-bold text-emerald-700 mb-6">Your Progress</h2>

  <!-- Weight Chart -->
  <section class="bg-white p-6 rounded-xl shadow-lg mb-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Weight Progress</h3>
    <div class="relative w-full h-80">
      <canvas id="weightChart"></canvas>
    </div>
  </section>

  <!-- BMI Doughnut Chart -->
  <section class="bg-white p-6 rounded-xl shadow-lg">
    <h3 class="text-xl font-bold text-gray-800 mb-4">BMI Progress</h3>
    <div class="relative w-80 h-80 mx-auto">
      <canvas id="bmiChart"></canvas>
      <div id="bmiLabel" class="absolute inset-0 flex flex-col justify-center items-center text-center">
        <span class="text-3xl font-bold text-gray-800"><?= $latestBMI ?></span>
        <span class="text-gray-500"><?= $bmiCategory ?></span>
      </div>
    </div>
  </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
const dates = <?= json_encode($dates) ?>;
const weights = <?= json_encode($weights) ?>;

// Weight Chart
const weightCtx = document.getElementById("weightChart").getContext("2d");
new Chart(weightCtx, {
    type: "line",
    data: {
        labels: dates,
        datasets: [{
            label: "Weight (kg)",
            data: weights,
            borderColor: "#10B981",
            backgroundColor: "rgba(16,185,129,0.2)",
            tension: 0.3,
            fill: true,
            pointRadius: 5,
            pointBackgroundColor: "#10B981"
        }]
    },
    options: { responsive: true, maintainAspectRatio: false }
});

// BMI Doughnut Chart
const bmiCtx = document.getElementById("bmiChart").getContext("2d");
new Chart(bmiCtx, {
    type: 'doughnut',
    data: {
        labels: ['BMI', 'Remaining'],
        datasets: [{
            data: [<?= $latestBMI ?>, <?= max(0, 50 - $latestBMI) ?>],
            backgroundColor: ['#3B82F6', '#E5E7EB'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
            legend: { display: false },
            tooltip: { enabled: false }
        }
    }
});
</script>
</body>
</html>
