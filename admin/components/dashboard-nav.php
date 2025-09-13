<body class="bg-gradient-to-r from-purple-200 to-blue-100">

<div class="flex min-h-screen">
  <!-- Sidebar -->
  <aside class="w-64 bg-emerald-700 text-white flex flex-col shadow-xl">
    <!-- Logo / Title -->
    <div class="p-6 text-2xl font-bold border-b border-emerald-600 flex items-center gap-2">
      <i class="fas fa-leaf"></i> 
      <span>ADMIN</span>
    </div>

    <nav class="flex-1 p-4 space-y-1">

      <a href="index.php" 
         class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
        <i class="fas fa-home"></i> Home
      </a>

 <!-- Nutritionist Dropdown -->
<div class="space-y-1">
  <button class="flex items-center justify-between w-full py-2 px-4 rounded-lg hover:bg-emerald-600 transition" onclick="toggleDropdown('nutritionistMenu')">
    <span class="flex items-center gap-3">
      <!-- Heroicon User Check -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 4v6a2 2 0 01-2 2H6a2 2 0 01-2-2v-6m16-2a9 9 0 11-18 0 9 9 0 0118 0z" />
      </svg>
      Nutritionist
    </span>
    <!-- Chevron Down Icon -->
    <svg id="nutritionistArrow" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
    </svg>
  </button>
  <div id="nutritionistMenu" class="ml-4 mt-1 space-y-1 hidden">
    <a href="nutrionist-approval.php" class="block py-2 px-4 rounded-lg hover:bg-emerald-600 transition text-sm">Approve Nutritionist</a>
    <a href="nutrionist-manage.php" class="block py-2 px-4 rounded-lg hover:bg-emerald-600 transition text-sm">Manage Nutritionist</a>
  </div>
</div>

<!-- Diet Plans Dropdown -->
<div class="space-y-1">
  <button class="flex items-center justify-between w-full py-2 px-4 rounded-lg hover:bg-emerald-600 transition" onclick="toggleDropdown('dietMenu')">
    <span class="flex items-center gap-3">
      <!-- Heroicon Utensils -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12l9-5-9-5-9 5 9 5zm0 0v10m0 0l3-3m-3 3l-3-3" />
      </svg>
      Diet Plans
    </span>
    <!-- Chevron Down Icon -->
    <svg id="dietArrow" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
    </svg>
  </button>
  <div id="dietMenu" class="ml-4 mt-1 space-y-1 hidden">
    <a href="dietplans.php" class="block py-2 px-4 rounded-lg hover:bg-emerald-600 transition text-sm">Manage Diet Plans</a>
    <a href="add_dietplan.php" class="block py-2 px-4 rounded-lg hover:bg-emerald-600 transition text-sm">Add Diet Plan</a>
  </div>
</div>

<!-- Swap Plans Dropdown -->
<div class="space-y-1">
  <button class="flex items-center justify-between w-full py-2 px-4 rounded-lg hover:bg-emerald-600 transition" 
          onclick="toggleDropdown('swapMenu', 'swapArrow')">
    <span class="flex items-center gap-3">
      <!-- Heroicon Utensils -->
      <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 12l9-5-9-5-9 5 9 5zm0 0v10m0 0l3-3m-3 3l-3-3" />
      </svg>
      Swap Plans
    </span>
    <!-- Chevron Down Icon -->
    <svg id="swapArrow" xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 transition-transform duration-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
      <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
    </svg>
  </button>
  <div id="swapMenu" class="ml-4 mt-1 space-y-1 hidden">
    <a href="swapplans.php" class="block py-2 px-4 rounded-lg hover:bg-emerald-600 transition text-sm">Manage Swap Plans</a>
    <a href="add_swapplan.php" class="block py-2 px-4 rounded-lg hover:bg-emerald-600 transition text-sm">Add Swap Plan</a>
  </div>
</div>



    </nav>

    <!-- Logout -->
    <div class="p-4 border-t border-emerald-600">
      <a href="logout.php" 
         class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-red-600 transition">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </aside>

<!-- Mobile Menu Button -->
  <div class="md:hidden absolute top-4 left-4 z-50">
    <button onclick="toggleSidebar()" class="text-white bg-emerald-700 p-2 rounded-md">
      <i class="fas fa-bars"></i>
    </button>
  </div>

<!-- FontAwesome -->
<script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

<!-- Dropdown JS -->
<script>
function toggleDropdown(id) {
  const menu = document.getElementById(id);
  const arrow = document.getElementById(id.replace('Menu','') + 'Arrow');
  menu.classList.toggle('hidden');
  arrow.classList.toggle('rotate-180');
}

// Responsive sidebar toggle
function toggleSidebar() {
  const sidebar = document.getElementById('sidebar');
  sidebar.classList.toggle('-translate-x-full');
}
</script>

