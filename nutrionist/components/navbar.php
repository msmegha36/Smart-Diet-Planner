<?php
session_start();
?>
<body class="bg-gray-50 text-gray-800">

<!-- Navbar -->
<header class="bg-white shadow-lg fixed w-full top-0 z-50">
  <div class="container mx-auto flex items-center justify-between py-5 px-6 md:px-8">
    
    <!-- Logo -->
    <div class="flex items-center space-x-3">
      <img src="https://cdn-icons-png.flaticon.com/512/3069/3069171.png" 
           alt="logo" class="w-10 h-10 animate-bounce">
      <span class="text-2xl md:text-3xl font-extrabold text-emerald-600 tracking-wide">Smart Diet Planner</span>
    </div>

    <!-- Desktop Menu -->
 

    <!-- Right: Login button -->
    <a href="login.php" 
       class="bg-emerald-500 text-white px-6 py-3 rounded-full font-bold hover:bg-emerald-600 transition transform hover:scale-105">
      Login
    </a>

    <!-- Mobile Menu Button -->
    <button id="menu-btn" class="md:hidden text-gray-700 focus:outline-none">
      <svg xmlns="http://www.w3.org/2000/svg" class="w-8 h-8" fill="none"
           viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
              d="M4 6h16M4 12h16M4 18h16"/>
      </svg>
    </button>
  </div>

  <!-- Mobile Menu -->
  <div id="mobile-menu" class="hidden md:hidden bg-white shadow-lg">
    <nav class="flex flex-col space-y-4 px-6 py-6 text-lg font-semibold text-gray-700">
      
      <a href="login.php" class="bg-emerald-500 text-white px-6 py-3 rounded-full font-bold hover:bg-emerald-600 transition text-center">
        Login
      </a>
    </nav>
  </div>
</header>
