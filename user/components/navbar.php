<body class="bg-gray-100 flex min-h-screen">
  <!-- Sidebar -->
  <aside class="bg-emerald-700 text-white flex flex-col w-64 md:w-64 sm:w-16 transition-all duration-300">
    <div class="flex items-center justify-center sm:justify-center md:justify-start gap-2 p-4 text-xl font-bold border-b border-emerald-600">
      <i class="fas fa-leaf text-2xl"></i>
      <span class="hidden sm:hidden md:inline"><a href="../home/index.php">Diet Planner</a></span>
    </div>

    <nav class="flex-1 p-4 overflow-y-auto">
      <ul class="space-y-2">
        <!-- Profile with submenu -->
        <li class="group">
          <a href="index.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
            <i class="fas fa-user"></i>
            <span class="hidden sm:hidden md:inline">Profile</span>
          </a>
        </li>

        <!-- Regular items -->
        <li>
          <a href="user_dietPlan.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
            <i class="fas fa-utensils"></i>
            <span class="hidden sm:hidden md:inline">My Plan</span>
          </a>
        </li>
        <li>
          <a href="progress.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
            <i class="fas fa-chart-line"></i>
            <span class="hidden sm:hidden md:inline">Progress</span>
          </a>
        </li>
        <li>
          <a href="plans.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
            <i class="fas fa-lightbulb"></i>
            <span class="hidden sm:hidden md:inline">Generate Plans</span>
          </a>
        </li>
        <li>
          <a href="bmi.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
            <i class="fas fa-weight"></i>
            <span class="hidden sm:hidden md:inline">BMI Calculator</span>
          </a>
        </li>
        <li>
          <a href="my_appointments.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
            <i class="fas fa-calendar-alt"></i>
            <span class="hidden sm:hidden md:inline">Appointments</span>
          </a>
        </li>
      </ul>
    </nav>

    <div class="p-4 border-t border-emerald-600">
      <a href="logout.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-red-600 transition">
        <i class="fas fa-sign-out-alt"></i>
        <span class="hidden sm:hidden md:inline">Logout</span>
      </a>
    </div>
  </aside>

  <!-- Main Content -->
  <main class="flex-1 p-8">


  <style>
    @media (max-width: 768px) {
      aside {
        width: 4rem; /* collapsed width */
      }
    }
  </style>
</body>
