<body class="bg-gradient-to-r from-purple-200 to-blue-100 flex min-h-screen">
  <!-- Sidebar -->
  <aside class="bg-emerald-700 text-white flex flex-col w-64 md:w-64 sm:w-16 transition-all duration-300">
    <!-- Header -->
    <div class="flex items-center justify-center sm:justify-center md:justify-start gap-2 p-4 text-xl font-bold border-b border-emerald-600">
      <i class="fas fa-leaf text-2xl"></i>
      <span class="hidden sm:hidden md:inline"><a href="index.php">ADMIN</a></span>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 p-4 overflow-y-auto">
      <ul class="space-y-2">

        <li>
          <a href="index.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
            <i class="fas fa-home"></i>
            <span class="hidden sm:hidden md:inline">Dashboard</span>
          </a>
        </li>

        <li>
          <a href="nutrionist-approval.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
            <i class="fas fa-user-check"></i>
            <span class="hidden sm:hidden md:inline">Approve Nutritionist</span>
          </a>
        </li>

        <li>
          <a href="nutrionist-manage.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
            <i class="fas fa-user-md"></i>
            <span class="hidden sm:hidden md:inline">Manage Nutritionist</span>
          </a>
        </li>

        <li>
          <a href="dietplans.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
            <i class="fas fa-utensils"></i>
            <span class="hidden sm:hidden md:inline">Manage Diet Plans</span>
          </a>
        </li>

        <li>
          <a href="add_dietplan.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
            <i class="fas fa-plus-circle"></i>
            <span class="hidden sm:hidden md:inline">Add Diet Plan</span>
          </a>
        </li>

        <li>
          <a href="swapplans.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
            <i class="fas fa-exchange-alt"></i>
            <span class="hidden sm:hidden md:inline">Manage Swap Plans</span>
          </a>
        </li>

        <li>
          <a href="add_swapplan.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
            <i class="fas fa-plus"></i>
            <span class="hidden sm:hidden md:inline">Add Swap Plan</span>
          </a>
        </li>



      </ul>
    </nav>

    <!-- Logout -->
    <div class="p-4 border-t border-emerald-600">
      <a href="logout.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-red-600 transition">
        <i class="fas fa-sign-out-alt"></i>
        <span class="hidden sm:hidden md:inline">Logout</span>
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 p-8">
    <!-- Your admin content -->


  <!-- FontAwesome (Correct script link) -->
  <script src="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/js/all.min.js" crossorigin="anonymous"></script>

  <style>
    @media (max-width: 768px) {
      aside {
        width: 4rem; /* Collapsed sidebar width */
      }

      aside span {
        display: none !important; /* Hide text */
      }

      aside i {
        margin: auto;
      }
    }
  </style>

</body>
