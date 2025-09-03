<?php include 'components/head.php'; ?>

<?php include 'components/navbar.php'; ?>


  <!-- Main Content -->
  <main class="flex-1 overflow-y-auto p-8">
    <!-- Profile Section -->
    <section class="bg-white rounded-xl shadow-lg p-6 mb-8">
      <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold text-emerald-700">User Profile</h2>
        <button onclick="toggleModal(true)" class="bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700">
          Update
        </button>
      </div>
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-gray-700">
        <p><strong>Name:</strong> John Doe</p>
        <p><strong>Email:</strong> john@example.com</p>
        <p><strong>Age:</strong> 28</p>
        <p><strong>Height:</strong> 175 cm</p>
        <p><strong>Weight:</strong> 72 kg</p>
        <p><strong>Diet Plan:</strong> Weight Loss</p>
        <p><strong>Food Preference:</strong> Vegetarian</p>
      </div>
    </section>

    <!-- Chart Section -->
    <section class="bg-white rounded-xl shadow-lg p-6 mb-8">
      <h2 class="text-xl font-bold text-emerald-700 mb-4">Calorie Breakdown</h2>
      <div class="relative w-full h-80">
        <canvas id="calorieChart"></canvas>
      </div>
    </section>

    <!-- History Section -->
    <section>
      <h2 class="text-xl font-bold text-emerald-700 mb-4">Progress History</h2>
      <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white p-6 rounded-xl shadow-md">
          <h3 class="font-semibold text-gray-800">Initial Data</h3>
          <p>Weight: 75kg</p>
          <p>Height: 175cm</p>
          <p class="text-sm text-gray-500">Joined: Jan 10, 2025</p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-md">
          <h3 class="font-semibold text-gray-800">Updated</h3>
          <p>Weight: 72kg</p>
          <p>Height: 175cm</p>
          <p class="text-sm text-gray-500">Updated: Feb 20, 2025</p>
        </div>
        <div class="bg-white p-6 rounded-xl shadow-md">
          <h3 class="font-semibold text-gray-800">Updated</h3>
          <p>Weight: 70kg</p>
          <p>Height: 175cm</p>
          <p class="text-sm text-gray-500">Updated: Mar 1, 2025</p>
        </div>
        
      </div>
    </section>
  </main>
</div>

<!-- Modal -->
<div id="updateModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-xl shadow-lg w-full max-w-2xl p-6">
    <div class="flex justify-between items-center border-b pb-3 mb-4">
      <h5 class="text-lg font-bold">Update Profile</h5>
      <button onclick="toggleModal(false)" class="text-gray-500 hover:text-gray-700">&times;</button>
    </div>
    <form class="grid grid-cols-1 md:grid-cols-2 gap-4">
      <div>
        <label class="block text-gray-700 font-medium">Name</label>
        <input type="text" class="w-full border rounded-lg px-3 py-2" value="John Doe">
      </div>
      <div>
        <label class="block text-gray-700 font-medium">Email</label>
        <input type="email" class="w-full border rounded-lg px-3 py-2" value="john@example.com">
      </div>
      <div>
        <label class="block text-gray-700 font-medium">Age</label>
        <input type="number" class="w-full border rounded-lg px-3 py-2" value="28">
      </div>
      <div>
        <label class="block text-gray-700 font-medium">Height (cm)</label>
        <input type="number" class="w-full border rounded-lg px-3 py-2" value="175">
      </div>
      <div>
        <label class="block text-gray-700 font-medium">Weight (kg)</label>
        <input type="number" class="w-full border rounded-lg px-3 py-2" value="72">
      </div>
      <div>
        <label class="block text-gray-700 font-medium">Diet Plan</label>
        <select class="w-full border rounded-lg px-3 py-2">
          <option selected>Weight Loss</option>
          <option>Weight Gain</option>
          <option>Muscle Building</option>
          <option>Balanced Diet</option>
        </select>
      </div>
      <div>
        <label class="block text-gray-700 font-medium">Food Preference</label>
        <select class="w-full border rounded-lg px-3 py-2">
          <option selected>Vegetarian</option>
          <option>Non-Vegetarian</option>
          <option>Vegan</option>
        </select>
      </div>
    </form>
    <div class="flex justify-end mt-6 space-x-3">
      <button onclick="toggleModal(false)" class="px-4 py-2 rounded-lg bg-gray-300 hover:bg-gray-400">Close</button>
      <button class="px-4 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">Save Changes</button>
    </div>
  </div>
</div>

<script>
  // Toggle modal
  function toggleModal(show) {
    document.getElementById("updateModal").classList.toggle("hidden", !show);
    document.getElementById("updateModal").classList.toggle("flex", show);
  }

  // Chart.js
  const ctx = document.getElementById('calorieChart').getContext('2d');
  new Chart(ctx, {
    type: 'doughnut',
    data: {
      labels: ['Protein', 'Carbs', 'Fat'],
      datasets: [{
        data: [30, 50, 20],
        backgroundColor: ['#10B981', '#3B82F6', '#F59E0B']
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: {
        legend: { position: 'bottom' }
      }
    }
  });
</script>

</body>
</html>
