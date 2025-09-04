<?php
session_start();
include(__DIR__ . '/../config/db_conn.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch progress history
$historyQuery = mysqli_query($connection, "SELECT weight, height, updated_at FROM progress_history WHERE user_id='$user_id' ORDER BY updated_at ASC");
$weights = [];
$dates   = [];
while ($row = mysqli_fetch_assoc($historyQuery)) {
    $weights[] = $row['weight'];
    $dates[]   = date("M d", strtotime($row['updated_at']));
}

// Fetch latest user data
$user = mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM reg WHERE id='$user_id'"));

// Fetch meal plan (example: based on user goal/dietary/activity stored in reg table)
$goal     = $user['goal'] ?? 'weight_loss';
$dietary  = $user['dietary'] ?? 'veg';
$activity = $user['activity'] ?? 'medium';

$mealQuery = mysqli_query($connection, "
    SELECT meal_type, plan_text, protein, carbs, fat
    FROM diet_plans
    WHERE goal='$goal' AND dietary='$dietary' AND activity='$activity'
    ORDER BY FIELD(meal_type,'Breakfast','Lunch','Snack','Dinner')
");
$meals = [];
while ($row = mysqli_fetch_assoc($mealQuery)) {
    $meals[] = $row;
}
?>

<?php include 'components/head.php'; ?>
<?php include 'components/navbar.php'; ?>

<!-- Main Content -->
<main class="flex-1 overflow-y-auto p-8">
  <h2 class="text-3xl font-bold text-emerald-700 mb-6">Your Progress</h2>

  <!-- BMI Tracking -->
  <section class="bg-white p-6 rounded-xl shadow-lg mb-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4">BMI Tracking</h3>
    <form id="bmiForm" method="post" class="grid grid-cols-1 md:grid-cols-3 gap-6">
      <div>
        <label class="block text-gray-700 font-medium">Weight (kg)</label>
        <input type="number" name="weight" id="weight" value="<?= htmlspecialchars($user['weight']) ?>" required class="w-full border rounded-lg px-3 py-2 mt-1">
      </div>
      <div>
        <label class="block text-gray-700 font-medium">Height (cm)</label>
        <input type="number" name="height" id="height" value="<?= htmlspecialchars($user['height']) ?>" required class="w-full border rounded-lg px-3 py-2 mt-1">
      </div>
      <div class="flex items-end">
        <button type="submit" class="w-full bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700">
          Calculate & Save BMI
        </button>
      </div>
    </form>
    <div id="bmiResult" class="mt-4 text-lg font-semibold text-emerald-700 hidden"></div>
  </section>

  <!-- Weight Progress Visualization -->
  <section class="bg-white p-6 rounded-xl shadow-lg mb-8">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Weight Progress</h3>
    <div class="relative w-full h-80">
      <canvas id="weightChart"></canvas>
    </div>
  </section>

  <!-- Meal Suggestions -->
  <section class="bg-white p-6 rounded-xl shadow-lg">
    <h3 class="text-xl font-bold text-gray-800 mb-4">Meal Suggestions</h3>
    <div id="mealSuggestions" class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <?php if ($meals): ?>
        <?php foreach ($meals as $meal): ?>
          <div class="p-4 bg-gray-50 rounded-lg shadow-sm">
            <h4 class="font-semibold text-gray-800"><?= htmlspecialchars($meal['meal_type']) ?></h4>
            <p class="text-gray-600"><?= htmlspecialchars($meal['plan_text']) ?></p>
            <small class="text-gray-500">
              Protein: <?= $meal['protein'] ?>g | Carbs: <?= $meal['carbs'] ?>g | Fat: <?= $meal['fat'] ?>g
            </small>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <p class="text-red-500">No matching plan found.</p>
      <?php endif; ?>
    </div>
  </section>
</main>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
  // Render weight chart with PHP data
  const weights = <?= json_encode($weights) ?>;
  const dates   = <?= json_encode($dates) ?>;

  const ctx = document.getElementById("weightChart").getContext("2d");
  new Chart(ctx, {
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
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: true, position: "bottom" } },
      scales: { y: { beginAtZero: false } }
    }
  });

  // BMI Calculation (instant on page, also stored in DB by form)
  document.getElementById("bmiForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const weight = parseFloat(document.getElementById("weight").value);
    const height = parseFloat(document.getElementById("height").value) / 100;
    const bmi = (weight / (height * height)).toFixed(1);

    let category = "Normal";
    if (bmi < 18.5) category = "Underweight";
    else if (bmi < 24.9) category = "Normal";
    else if (bmi < 29.9) category = "Overweight";
    else category = "Obese";

    const result = document.getElementById("bmiResult");
    result.textContent = `Your BMI: ${bmi} (${category})`;
    result.classList.remove("hidden");

    // Submit form normally to save data in DB
    e.target.submit();
  });
</script>

</body>
</html>
