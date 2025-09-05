<?php
session_start();
?>
<body class="bg-gray-100 text-gray-200">

<!-- Admin Navbar -->
<header class="bg-gray-900 shadow-lg fixed w-full top-0 z-50">
  <div class="container mx-auto flex items-center justify-between py-5 px-6 md:px-8">
    
    <!-- Logo -->
    <div class="flex items-center space-x-3">
      <img src="https://cdn-icons-png.flaticon.com/512/3135/3135715.png" 
           alt="Admin Logo" class="w-10 h-10">
      <span class="text-2xl md:text-3xl font-extrabold text-emerald-400 tracking-wide">
        Admin Panel
      </span>
    </div>

   

    <!-- Right: Logout -->
    <?php if (isset($_SESSION['admin_id'])): ?>
      <a href="logout.php" 
         class="bg-emerald-500 text-white px-6 py-2 rounded-full font-bold hover:bg-emerald-600 transition transform hover:scale-105">
        Logout
      </a>
    <?php else: ?>
      <a href="login.php" 
         class="bg-blue-500 text-white px-6 py-2 rounded-full font-bold hover:bg-blue-600 transition transform hover:scale-105">
        Login
      </a>
    <?php endif; ?>

    <!-- Mobile Menu Button -->
    <button id="menu-btn" class="md:hidden text-gray-300 focus:outline-none">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none"
           viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>
  </div>

  <!-- Mobile Menu -->
  <div id="mobile-menu" class="hidden md:hidden bg-gray-800 shadow-lg">
    <nav class="flex flex-col space-y-4 px-6 py-6 text-lg font-semibold text-gray-200">
  
      <?php if (isset($_SESSION['admin_id'])): ?>
        <a href="logout.php" class="bg-emerald-500 text-white px-6 py-2 rounded-full font-bold text-center hover:bg-emerald-600 transition">
          Logout
        </a>
      <?php else: ?>
        <a href="login.php" class="bg-blue-500 text-white px-6 py-2 rounded-full font-bold text-center hover:bg-blue-600 transition">
          Login
        </a>
      <?php endif; ?>
    </nav>
  </div>
</header>
