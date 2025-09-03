<?php include 'components/head.php'; ?>



  <style>
    /* Custom Animations */
    @keyframes spin-slow {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    .animate-spin-slow { animation: spin-slow 25s linear infinite; }

    @keyframes fade-in {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in { animation: fade-in 1s ease-in-out; }
    .animate-fade-in-up { animation: fade-in 1.2s ease-in-out; }
  </style>

<?php include 'components/navbar.php'; ?>



<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Diet Planner - Signup</title>
  <script src="https://cdn.tailwindcss.com"></script>
  <script>
    function nextStep(step) {
      document.querySelectorAll(".step").forEach((el) => el.classList.add("hidden"));
      document.getElementById("step" + step).classList.remove("hidden");
    }
  </script>
</head>
<body class="bg-gray-50">

<!-- Main Form Container -->
<main class="flex items-center justify-center min-h-screen px-6 py-16 bg-gray-50">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl p-12">
    <h1 class="text-3xl font-bold text-emerald-700 mb-8 text-center">Create Your Account</h1>

    <form action="register.php" method="POST" class="space-y-10">
      <!-- STEP 1 -->
      <div id="step1" class="step">
        <h2 class="text-xl font-semibold text-gray-700 mb-6">👤 Personal Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div>
            <label class="block text-gray-700 font-medium mb-2">Full Name</label>
            <input type="text" name="name" required 
                   class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-2">Age</label>
            <input type="number" name="age" required 
                   class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-2">Weight (kg)</label>
            <input type="number" name="weight" required 
                   class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-2">Height (cm)</label>
            <input type="number" name="height" required 
                   class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>
        <div class="flex justify-end mt-8">
          <button type="button" onclick="nextStep(2)" 
                  class="bg-emerald-600 text-white px-8 py-3 text-lg rounded-lg hover:bg-emerald-700 transition">
            Next →
          </button>
        </div>
      </div>

      <!-- STEP 2 -->
      <div id="step2" class="step hidden">
        <h2 class="text-xl font-semibold text-gray-700 mb-6">📧 Account Details</h2>
        <div class="space-y-6">
          <div>
            <label class="block text-gray-700 font-medium mb-2">Email</label>
            <input type="email" name="email" required 
                   class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-2">Password</label>
            <input type="password" name="password" required 
                   class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>
        <div class="flex justify-between mt-8">
          <button type="button" onclick="nextStep(1)" 
                  class="bg-gray-300 text-gray-700 px-8 py-3 text-lg rounded-lg hover:bg-gray-400 transition">
            ← Back
          </button>
          <button type="button" onclick="nextStep(3)" 
                  class="bg-emerald-600 text-white px-8 py-3 text-lg rounded-lg hover:bg-emerald-700 transition">
            Next →
          </button>
        </div>
      </div>

      <!-- STEP 3 -->
      <div id="step3" class="step hidden">
        <h2 class="text-xl font-semibold text-gray-700 mb-6">🥗 Diet Preferences</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div>
            <label class="block text-gray-700 font-medium mb-2">Select Plan</label>
            <select name="plan" required 
                    class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
              <option value="">-- Choose a Plan --</option>
              <option value="weight_loss">Weight Loss</option>
              <option value="weight_gain">Weight Gain</option>
              <option value="muscle_build">Muscle Building</option>
              <option value="balanced_diet">Balanced Diet</option>
            </select>
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-2">Food Preference</label>
            <select name="food_type" required 
                    class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
              <option value="">-- Select --</option>
              <option value="veg">Vegetarian</option>
              <option value="nonveg">Non-Vegetarian</option>
              <option value="vegan">Vegan</option>
            </select>
          </div>
        </div>
        <div class="flex justify-between mt-8">
          <button type="button" onclick="nextStep(2)" 
                  class="bg-gray-300 text-gray-700 px-8 py-3 text-lg rounded-lg hover:bg-gray-400 transition">
            ← Back
          </button>
          <button type="submit" 
                  class="bg-emerald-600 text-white px-8 py-3 text-lg rounded-lg hover:bg-emerald-700 transition">
            Submit
          </button>
        </div>
      </div>
    </form>
  </div>
</main>





<?php include 'components/footer.php'; ?>


<script>
  const menuBtn = document.getElementById("menu-btn");
  const mobileMenu = document.getElementById("mobile-menu");

  menuBtn.addEventListener("click", () => {
    mobileMenu.classList.toggle("hidden");
  });
</script>
