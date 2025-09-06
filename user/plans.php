<?php include 'components/head.php'; ?>
<?php include 'components/navbar.php'; ?>
<?php include(__DIR__ . '/../config/db_conn.php');  ?> <!-- your DB connection -->

<!-- Main Content -->
<main class="flex-1 overflow-y-auto p-8">
  <!-- Input Form -->
  <section class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <h2 class="text-2xl font-bold text-emerald-700 mb-6">Personalized Nutrition Plan</h2>
    <form id="dietForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
      <div>
        <label class="block text-gray-700 font-medium">Age</label>
        <input type="number" id="age" required class="w-full border rounded-lg px-3 py-2 mt-1">
      </div>
      <div>
        <label class="block text-gray-700 font-medium">Gender</label>
        <select id="gender" class="w-full border rounded-lg px-3 py-2 mt-1">
          <option value="male">Male</option>
          <option value="female">Female</option>
        </select>
      </div>
      <div>
        <label class="block text-gray-700 font-medium">Activity Level</label>
        <select id="activity" class="w-full border rounded-lg px-3 py-2 mt-1">
          <option value="sedentary">Sedentary</option>
          <option value="light">Lightly Active</option>
          <option value="moderate">Moderately Active</option>
          <option value="active">Very Active</option>
        </select>
      </div>
      <div>
        <label class="block text-gray-700 font-medium">Food Preference</label>
        <select id="food" class="w-full border rounded-lg px-3 py-2 mt-1">
          <option value="veg">Vegetarian</option>
          <option value="nonveg">Non-Vegetarian</option>
          <option value="vegan">Vegan</option>
        </select>
      </div>
      <div class="md:col-span-2">
        <label class="block text-gray-700 font-medium">Fitness Goal</label>
        <select id="goal" class="w-full border rounded-lg px-3 py-2 mt-1">
          <option value="weight_loss">Weight Loss</option>
          <option value="weight_gain">Weight Gain</option>
          <option value="muscle_build">Muscle Building</option>
          <option value="balanced">Balanced Diet</option>
        </select>
      </div>
      <div class="md:col-span-2">
        <button type="submit" class="w-full bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700">
          Generate Plan
        </button>
      </div>
    </form>
  </section>

  <!-- Results -->
  <section id="planResult" class="hidden bg-white rounded-xl shadow-lg p-6 mb-8">
    <h3 class="text-xl font-bold text-emerald-700 mb-4">Your Personalized Meal Plan</h3>
    <div id="mealSuggestions" class="space-y-4"></div>

    <!-- Chart -->
    <div class="relative w-full h-80 mt-6">
      <canvas id="macroChart"></canvas>
    </div>
  </section>
</main>
</div>

<script>
let macroChart;

document.getElementById("dietForm").addEventListener("submit", function(e) {
  e.preventDefault();

  const age = document.getElementById("age").value;
  const gender = document.getElementById("gender").value;
  const activity = document.getElementById("activity").value;
  const food = document.getElementById("food").value;
  const goal = document.getElementById("goal").value;

  fetch(`get_diet.php?goal=${goal}&dietary=${food}&activity=${activity}`)
    .then(res => res.json())
    .then(data => {
      if (data.success) {
        // Show results
        document.getElementById("planResult").classList.remove("hidden");

        // Meal Plan
        let mealDiv = document.getElementById("mealSuggestions");
        mealDiv.innerHTML = `
          <div class="p-4 bg-gray-50 rounded-lg shadow-sm">
            <pre class="whitespace-pre-wrap text-gray-700">${data.plan.plan_text}</pre>
          </div>`;

        // Chart
        const ctx = document.getElementById("macroChart").getContext("2d");
        if (macroChart) macroChart.destroy();
        macroChart = new Chart(ctx, {
          type: "doughnut",
          data: {
            labels: ["Protein", "Carbs", "Fat"],
            datasets: [{
              data: [data.plan.protein, data.plan.carbs, data.plan.fat],
              backgroundColor: ["#10B981", "#3B82F6", "#F59E0B"]
            }]
          },
          options: { responsive: true, maintainAspectRatio: false }
        });
      } else {
        alert("No matching plan found.");
      }
    });
});
</script>

</body>
</html>
