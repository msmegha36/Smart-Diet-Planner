<?php include 'components/head.php'; ?>

<?php include 'components/navbar.php'; ?>



  <!-- Main Content -->
  <main class="flex-1 overflow-y-auto p-8">
    <h2 class="text-3xl font-bold text-emerald-700 mb-6">Your Progress</h2>

    <!-- BMI Tracking -->
    <section class="bg-white p-6 rounded-xl shadow-lg mb-8">
      <h3 class="text-xl font-bold text-gray-800 mb-4">BMI Tracking</h3>
      <form id="bmiForm" class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div>
          <label class="block text-gray-700 font-medium">Weight (kg)</label>
          <input type="number" id="weight" required class="w-full border rounded-lg px-3 py-2 mt-1">
        </div>
        <div>
          <label class="block text-gray-700 font-medium">Height (cm)</label>
          <input type="number" id="height" required class="w-full border rounded-lg px-3 py-2 mt-1">
        </div>
        <div class="flex items-end">
          <button type="submit" class="w-full bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700">
            Calculate BMI
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
        <!-- JS will populate -->
      </div>
    </section>
  </main>
</div>

<script>
  let weightChart;
  const weights = [70, 69, 68.5, 68, 67.8]; // example dummy values
  const dates = ["Day 1", "Day 5", "Day 10", "Day 15", "Day 20"];

  // Render weight chart
  const ctx = document.getElementById("weightChart").getContext("2d");
  weightChart = new Chart(ctx, {
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
      scales: {
        y: { beginAtZero: false }
      }
    }
  });

  // BMI Calculation
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
  });

  // Meal Suggestions (Rule-based)
  const meals = {
    "Breakfast": "Oatmeal with nuts & fruits",
    "Lunch": "Grilled chicken / Paneer with brown rice & salad",
    "Snack": "Smoothie or Greek yogurt with seeds",
    "Dinner": "Fish curry / Veg curry with chapati & veggies"
  };
  const mealDiv = document.getElementById("mealSuggestions");
  for (let [meal, suggestion] of Object.entries(meals)) {
    mealDiv.innerHTML += `
      <div class="p-4 bg-gray-50 rounded-lg shadow-sm">
        <h4 class="font-semibold text-gray-800">${meal}</h4>
        <p class="text-gray-600">${suggestion}</p>
      </div>`;
  }
</script>

</body>
</html>
