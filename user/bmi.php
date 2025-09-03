<?php include 'components/head.php'; ?>

<?php include 'components/navbar.php'; ?>



  <!-- Main Content -->
  <main class="flex-1 overflow-y-auto p-8">
    <section class="bg-white rounded-xl shadow-lg p-6 mb-8">
      <h2 class="text-2xl font-bold text-emerald-700 mb-6">BMI Calculator</h2>
      
      <!-- Form -->
      <form id="bmiForm" class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-gray-700 font-medium">Weight (kg)</label>
          <input type="number" id="weight" required class="w-full border rounded-lg px-3 py-2 mt-1">
        </div>
        <div>
          <label class="block text-gray-700 font-medium">Height (cm)</label>
          <input type="number" id="height" required class="w-full border rounded-lg px-3 py-2 mt-1">
        </div>
        <div class="md:col-span-2">
          <button type="submit" class="w-full bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700">
            Calculate BMI
          </button>
        </div>
      </form>

      <!-- Result -->
      <div id="bmiResult" class="mt-6 hidden">
        <h3 class="text-lg font-semibold text-gray-800">Your BMI: <span id="bmiValue"></span></h3>
        <p class="text-gray-600">Category: <span id="bmiCategory" class="font-bold"></span></p>
      </div>

      <!-- Chart -->
      <div class="relative w-full h-80 mt-6">
        <canvas id="bmiChart"></canvas>
      </div>
    </section>
  </main>
</div>

<script>
  let bmiChart;

  document.getElementById("bmiForm").addEventListener("submit", function(e) {
    e.preventDefault();
    const weight = parseFloat(document.getElementById("weight").value);
    const height = parseFloat(document.getElementById("height").value) / 100; // cm -> m
    const bmi = (weight / (height * height)).toFixed(1);

    let category = "";
    if (bmi < 18.5) category = "Underweight";
    else if (bmi < 25) category = "Normal";
    else if (bmi < 30) category = "Overweight";
    else category = "Obese";

    // Update result text
    document.getElementById("bmiValue").textContent = bmi;
    document.getElementById("bmiCategory").textContent = category;
    document.getElementById("bmiResult").classList.remove("hidden");

    // Update chart
    const ctx = document.getElementById("bmiChart").getContext("2d");
    if (bmiChart) bmiChart.destroy();

    bmiChart = new Chart(ctx, {
      type: "doughnut",
      data: {
        labels: ["Underweight", "Normal", "Overweight", "Obese"],
        datasets: [{
          data: [18.5, 6.4, 5, 10], // approximate ranges
          backgroundColor: ["#60A5FA", "#10B981", "#F59E0B", "#EF4444"],
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        cutout: "70%",
        plugins: {
          legend: { position: "bottom" },
          tooltip: { enabled: false }
        }
      },
      plugins: [{
        id: "bmiIndicator",
        afterDraw(chart) {
          const { ctx, chartArea: { width, height } } = chart;
          ctx.save();
          ctx.font = "bold 18px sans-serif";
          ctx.fillStyle = "#374151";
          ctx.textAlign = "center";
          ctx.fillText("BMI: " + bmi, width / 2, height / 2);
          ctx.restore();
        }
      }]
    });
  });
</script>

</body>
</html>
