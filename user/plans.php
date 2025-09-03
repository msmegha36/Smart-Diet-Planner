<?php include 'components/head.php'; ?>

<?php include 'components/navbar.php'; ?>



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
            <option>Male</option>
            <option>Female</option>
          </select>
        </div>
        <div>
          <label class="block text-gray-700 font-medium">Activity Level</label>
          <select id="activity" class="w-full border rounded-lg px-3 py-2 mt-1">
            <option>Sedentary</option>
            <option>Lightly Active</option>
            <option>Moderately Active</option>
            <option>Very Active</option>
          </select>
        </div>
        <div>
          <label class="block text-gray-700 font-medium">Food Preference</label>
          <select id="food" class="w-full border rounded-lg px-3 py-2 mt-1">
            <option>Vegetarian</option>
            <option>Non-Vegetarian</option>
            <option>Vegan</option>
          </select>
        </div>
        <div class="md:col-span-2">
          <label class="block text-gray-700 font-medium">Fitness Goal</label>
          <select id="goal" class="w-full border rounded-lg px-3 py-2 mt-1">
            <option>Weight Loss</option>
            <option>Weight Gain</option>
            <option>Muscle Building</option>
            <option>Balanced Diet</option>
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

    const age = parseInt(document.getElementById("age").value);
    const gender = document.getElementById("gender").value;
    const activity = document.getElementById("activity").value;
    const food = document.getElementById("food").value;
    const goal = document.getElementById("goal").value;

    // Rule-based AI logic (simplified)
    let calories = 2000;
    if (goal === "Weight Loss") calories -= 400;
    if (goal === "Weight Gain") calories += 400;
    if (goal === "Muscle Building") calories += 600;

    if (activity === "Sedentary") calories -= 200;
    if (activity === "Very Active") calories += 300;

    // Macro split
    let protein = 0.3, carbs = 0.5, fat = 0.2;
    if (goal === "Weight Loss") { protein = 0.35; carbs = 0.4; fat = 0.25; }
    if (goal === "Muscle Building") { protein = 0.4; carbs = 0.4; fat = 0.2; }

    // Meal Suggestions
    let meals = {
      "Breakfast": food === "Vegetarian" ? "Oats with fruits & nuts" : "Eggs & whole grain toast",
      "Lunch": food === "Vegetarian" ? "Dal, brown rice & salad" : "Grilled chicken, quinoa & veggies",
      "Snack": food === "Vegan" ? "Smoothie with almond milk & seeds" : "Greek yogurt with fruits",
      "Dinner": food === "Vegetarian" ? "Paneer curry with chapati" : "Fish curry with brown rice"
    };

    // Show Results
    document.getElementById("planResult").classList.remove("hidden");
    let mealDiv = document.getElementById("mealSuggestions");
    mealDiv.innerHTML = "";
    for (let [meal, suggestion] of Object.entries(meals)) {
      mealDiv.innerHTML += `
        <div class="p-4 bg-gray-50 rounded-lg shadow-sm">
          <h4 class="font-semibold text-gray-800">${meal}</h4>
          <p class="text-gray-600">${suggestion}</p>
        </div>`;
    }

    // Chart update
    const ctx = document.getElementById("macroChart").getContext("2d");
    if (macroChart) macroChart.destroy();
    macroChart = new Chart(ctx, {
      type: "doughnut",
      data: {
        labels: ["Protein", "Carbs", "Fat"],
        datasets: [{
          data: [protein*100, carbs*100, fat*100],
          backgroundColor: ["#10B981", "#3B82F6", "#F59E0B"]
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: "bottom" }
        }
      }
    });
  });
</script>

</body>
</html>
