<body class="bg-gradient-to-r from-purple-200 to-blue-100 flex min-h-screen">

  <!-- Sidebar -->
  <aside class="bg-emerald-700 text-white flex flex-col w-64 md:w-64 sm:w-16 transition-all duration-300 shadow-xl">

    <!-- Logo / Title -->
    <div class="flex items-center justify-center sm:justify-center md:justify-start gap-2 p-4 text-xl font-bold border-b border-emerald-600">
      <i class="fas fa-leaf text-2xl"></i>
      <span class="hidden sm:hidden md:inline">Nutritionist</span>
    </div>

    <!-- Navigation -->
    <nav class="flex-1 p-4 overflow-y-auto">
      <ul class="space-y-2">

        <li>
          <a href="index.php" 
            class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
            <i class="fas fa-user"></i>
            <span class="hidden sm:hidden md:inline">Profile</span>
          </a>
        </li>

        <li>
          <a href="appointments.php" 
            class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
            <i class="fas fa-calendar-check"></i>
            <span class="hidden sm:hidden md:inline">View Appointments</span>
          </a>
        </li>

        <li>
          <a href="manage_appointments.php" 
            class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
            <i class="fas fa-tools"></i>
            <span class="hidden sm:hidden md:inline">Manage Appointments</span>
          </a>
        </li>
      </ul>
    </nav>

    <!-- Logout -->
    <div class="p-4 border-t border-emerald-600">
      <a href="logout.php" 
         class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-red-600 transition">
        <i class="fas fa-sign-out-alt"></i>
        <span class="hidden sm:hidden md:inline">Logout</span>
      </a>
    </div>

  </aside>


  <!-- FontAwesome -->
  <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>

  <style>
    /* Auto-collapse on small screens */
    @media (max-width: 768px) {
      aside {
        width: 4rem; /* collapsed width */
      }
    }
  </style>

</body>
