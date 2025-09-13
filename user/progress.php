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

<main class="flex-1 overflow-y-auto p-8 bg-gray-50">
  <h2 class="text-3xl font-bold text-emerald-700 mb-6">📈 Your Progress</h2>

  <!-- Weight Chart -->
  <section class="bg-white p-6 rounded-xl shadow-lg mb-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Weight Progress</h3>
    <div class="relative w-full h-80">
      <canvas id="weightChart"></canvas>
    </div>
    <p class="mt-3 text-gray-600 text-sm">Latest weight: <strong><?= $latestWeight ?> kg</strong></p>
  </section>

  <!-- BMI Doughnut Chart -->
  <section class="bg-white p-6 rounded-xl shadow-lg">
    <h3 class="text-xl font-bold text-gray-800 mb-4">BMI Progress</h3>
    <div class="relative w-80 h-80 mx-auto">
      <canvas id="bmiChart"></canvas>
      <div class="absolute inset-0 flex flex-col justify-center items-center text-center">
        <span class="text-4xl font-bold text-gray-800"><?= $latestBMI ?></span>
        <span class="text-sm font-semibold <?= 
            $latestBMI < 18.5 ? 'text-blue-600' : 
            ($latestBMI < 24.9 ? 'text-green-600' :
            ($latestBMI < 29.9 ? 'text-yellow-600' : 'text-red-600')) 
        ?>"><?= $bmiCategory ?></span>
      </div>
    </div>
    <p class="mt-3 text-gray-500 text-sm text-center">BMI categories: Underweight <18.5, Normal 18.5–24.9, Overweight 25–29.9, Obese ≥30</p>
  </section>
</main>

<script>
const dates = <?= json_encode($dates) ?>;
const weights = <?= json_encode($weights) ?>;

// Weight Chart with latest point highlighted
const weightCtx = document.getElementById("weightChart").getContext("2d");
new Chart(weightCtx, {
    type: "line",
    data: {
        labels: dates,
        datasets: [{
            label: "Weight (kg)",
            data: weights,
            borderColor: "#10B981",
            backgroundColor: function(context) {
                const gradient = context.chart.ctx.createLinearGradient(0,0,0,400);
                gradient.addColorStop(0, "rgba(16,185,129,0.4)");
                gradient.addColorStop(1, "rgba(16,185,129,0.05)");
                return gradient;
            },
            tension: 0.3,
            fill: true,
            pointRadius: 5,
            pointBackgroundColor: weights.map((w,i) => i === weights.length-1 ? "#F59E0B" : "#10B981"),
            pointHoverRadius: 7
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: true, position: 'top' },
            tooltip: { mode: 'index', intersect: false }
        },
        scales: {
            y: { beginAtZero: false, title: { display: true, text: 'Weight (kg)' } },
            x: { title: { display: true, text: 'Date' } }
        }
    }
});

// BMI Doughnut with color-coded categories
const bmiCtx = document.getElementById("bmiChart").getContext("2d");
const bmiValue = <?= $latestBMI ?>;
let bmiColor = "#10B981"; // default normal
if (bmiValue < 18.5) bmiColor = "#3B82F6"; 
else if (bmiValue < 24.9) bmiColor = "#10B981"; 
else if (bmiValue < 29.9) bmiColor = "#F59E0B"; 
else bmiColor = "#EF4444";

new Chart(bmiCtx, {
    type: 'doughnut',
    data: {
        labels: ['BMI', 'Remaining'],
        datasets: [{
            data: [bmiValue, Math.max(0, 50 - bmiValue)],
            backgroundColor: [bmiColor, '#E5E7EB'],
            borderWidth: 0
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: '70%',
        plugins: {
            legend: { display: false },
            tooltip: { enabled: true }
        }
    }
});
</script>
