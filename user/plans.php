<?php 
session_start();
include 'components/head.php'; 
include 'components/navbar.php'; 
include(__DIR__ . '/../config/db_conn.php'); 

if(!isset($_SESSION['user_id'])){
    header("Location: ../home/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];
?>

<main class="flex-1 overflow-y-auto p-8">

  <!-- Input Form -->
  <section class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <h2 class="text-2xl font-bold text-emerald-700 mb-6">Personalized Nutrition Plan</h2>
    <form id="dietForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
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
      <div>
        <label class="block text-gray-700 font-medium">Meal Type</label>
        <select id="meal_type" class="w-full border rounded-lg px-3 py-2 mt-1">
          <option value="3_meals">3 Meals</option>
          <option value="5_small">5 Small Meals</option>
          <option value="intermittent">Intermittent Fasting</option>
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
    <h3 class="text-xl font-bold text-emerald-700 mb-4">Your 7-Day Meal Plan</h3>
     <!-- Macro Chart -->
    <div class="relative w-full h-80 mt-6">
      <canvas id="macroChart"></canvas>
    </div>
    <div id="mealSuggestions" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6"></div>

   
  </section>
</main>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
let macroChart;

document.getElementById("dietForm").addEventListener("submit", function(e) {
  e.preventDefault();

  const activity = document.getElementById("activity").value;
  const food = document.getElementById("food").value;
  const goal = document.getElementById("goal").value;
  const meal_type = document.getElementById("meal_type").value;

  fetch(`get_diet.php?goal=${goal}&dietary=${food}&activity=${activity}&meal_type=${meal_type}`)
    .then(res => res.json())
    .then(data => {
      if(data.success){
        document.getElementById("planResult").classList.remove("hidden");
        const container = document.getElementById("mealSuggestions");
        container.innerHTML = "";

        let totalProtein = 0, totalCarbs = 0, totalFat = 0;

        data.plan.forEach(day => {
          let dayDiv = document.createElement("div");
          dayDiv.classList.add("bg-gray-50","p-4","rounded-xl","shadow-md");
          dayDiv.innerHTML = `<h4 class="font-bold text-emerald-700 mb-2 text-center">Day ${day.day_number}</h4>`;

          if(day.meals.length === 0){
            dayDiv.innerHTML += "<p class='text-gray-500'>No meals found for this day.</p>";
          } else {
            const seenMeals = new Set(); // track duplicates

            day.meals.forEach(meal => {
              const key = meal.meal_time + meal.meal_text;
              if(seenMeals.has(key)) return; // skip duplicate
              seenMeals.add(key);

              dayDiv.innerHTML += `
                <div class="mb-2 p-2 border rounded-lg bg-white">
                  <strong>${meal.meal_time.toUpperCase()}</strong>: ${meal.meal_text} 
                  <br>
                  <span class="text-sm text-gray-600">Protein: ${meal.protein}g, Carbs: ${meal.carbs}g, Fat: ${meal.fat}g, Calories: ${meal.calories} kcal</span>
                </div>
              `;

              totalProtein += parseInt(meal.protein);
              totalCarbs += parseInt(meal.carbs);
              totalFat += parseInt(meal.fat);
            });
          }

          container.appendChild(dayDiv);
        });

        // Move chart on top
        const chartContainer = document.getElementById("macroChart").parentElement;
        chartContainer.scrollIntoView({behavior:"smooth"});

        // Macro Chart
        const ctx = document.getElementById("macroChart").getContext("2d");
        if(macroChart) macroChart.destroy();
        macroChart = new Chart(ctx, {
          type: "doughnut",
          data: {
            labels: ["Protein", "Carbs", "Fat"],
            datasets: [{
              data: [totalProtein, totalCarbs, totalFat],
              backgroundColor: ["#10B981", "#3B82F6", "#F59E0B"]
            }]
          },
          options: { responsive: true, maintainAspectRatio: false }
        });

      } else {
        alert(data.msg || "No matching plan found.");
      }
    }).catch(err=>{
      console.error(err);
      alert("Error fetching diet plan.");
    });
});

</script>
