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


<!-- Main Content -->
<main class="flex items-center justify-center min-h-screen bg-gray-50 px-4 pt-28 pb-20">
  <!-- Login Card -->
  <div class="bg-white rounded-2xl shadow-lg w-full max-w-xl p-10">
    <!-- Logo -->
    <div class="text-center mb-6">
      <img src="https://cdn-icons-png.flaticon.com/512/3069/3069171.png" 
           alt="logo" class="w-20 h-20 mx-auto animate-bounce">
      <h1 class="text-3xl font-bold text-emerald-700 mt-4">Smart Diet Planner</h1>
      <p class="text-gray-500 text-base">Login to continue</p>
    </div>

    <!-- Form -->
    <form action="dashboard.html" method="POST" class="space-y-6">
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

      <!-- Remember Me + Forgot -->
      <div class="flex items-center justify-between text-sm">
        <label class="flex items-center space-x-2">
          <input type="checkbox" class="text-emerald-600 rounded">
          <span class="text-gray-600">Remember Me</span>
        </label>
        <a href="#" class="text-emerald-600 hover:underline">Forgot Password?</a>
      </div>

      <!-- Submit -->
      <button type="submit" 
              class="w-full bg-emerald-600 text-white font-semibold text-lg py-3.5 rounded-lg hover:bg-emerald-700 transition">
        Login
      </button>
    </form>

    <!-- Divider -->
    <div class="flex items-center my-6">
      <hr class="flex-1 border-gray-300">
      <span class="px-3 text-gray-500 text-sm">OR</span>
      <hr class="flex-1 border-gray-300">
    </div>

    <!-- Social Login -->
    <div class="flex justify-center space-x-5">
      <button class="flex items-center space-x-3 px-5 py-2.5 border rounded-lg hover:bg-gray-50">
        <img src="https://cdn-icons-png.flaticon.com/512/2991/2991148.png" class="w-6 h-6">
        <span class="text-base">Google</span>
      </button>
      <button class="flex items-center space-x-3 px-5 py-2.5 border rounded-lg hover:bg-gray-50">
        <img src="https://cdn-icons-png.flaticon.com/512/5968/5968764.png" class="w-6 h-6">
        <span class="text-base">Facebook</span>
      </button>
    </div>

    <!-- Sign Up -->
    <p class="text-center text-gray-600 text-base mt-8">
      Don’t have an account? 
      <a href="register.php" class="text-emerald-600 font-semibold hover:underline">Sign Up</a>
    </p>
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
