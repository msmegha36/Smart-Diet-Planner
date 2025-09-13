<body class="bg-gray-100">

<div class="flex min-h-screen">
    <!-- Sidebar -->
  <aside class="w-64 bg-emerald-700 text-white flex flex-col">
    <div class="p-6 text-2xl font-bold border-b border-emerald-600 flex items-center gap-2">
      <i class="fas fa-leaf"></i>  <a href="../home/index.php">Diet Planner</a>
    </div>
    <nav class="flex-1 p-4 space-y-2">
      <a href="index.php" class=" flex items-center gap-3 py-2 px-4 rounded-lg bg-emerald-600">
        <i class="fas fa-home"></i> Home
      </a>
      <a href="user_dietPlan.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
        <i class="fas fa-utensils"></i> My Plan
      </a>
       <a href="progress.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
        <i class="fas fa-chart-line"></i> Progress
      </a>
      <a href="plans.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
        <i class="fas fa-utensils"></i> Generate Plans
      </a>
     
      
      <a href="bmi.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
        <i class="fas fa-weight"></i> BMI Calculator
      </a>
      <a href="my_appointments.php" class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-emerald-600 transition">
        <i class="fas fa-weight"></i> Appoinments
      </a>
     
    </nav>
     <!-- Logout -->
    <div class="p-4 border-t border-emerald-600">
      <a href="logout.php" 
         class="flex items-center gap-3 py-2 px-4 rounded-lg hover:bg-red-600 transition">
        <i class="fas fa-sign-out-alt"></i> Logout
      </a>
    </div>
  </aside>