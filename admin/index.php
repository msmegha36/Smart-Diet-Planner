<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . '/../config/db_conn.php');

// ✅ Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Fetch metrics
$total_users = $connection->query("SELECT COUNT(*) as cnt FROM reg")->fetch_assoc()['cnt'];
$total_nutritionists = $connection->query("SELECT COUNT(*) as cnt FROM nutritionists")->fetch_assoc()['cnt'];
$pending_nutritionists = $connection->query("SELECT COUNT(*) as cnt FROM nutritionists WHERE status='pending'")->fetch_assoc()['cnt'];
$total_diet_plans = $connection->query("SELECT COUNT(*) as cnt FROM diet_plans")->fetch_assoc()['cnt'];
$total_appointments = $connection->query("SELECT COUNT(*) as cnt FROM appointments")->fetch_assoc()['cnt'];
$pending_appointments = $connection->query("SELECT COUNT(*) as cnt FROM appointments WHERE status='pending'")->fetch_assoc()['cnt'];
?>

<?php include 'components/head.php'; ?>
<?php include 'components/dashboard-nav.php'; ?>

<main class="bg-gray-100 min-h-screen w-full pt-28 px-6">
  <h2 class="text-3xl font-bold text-emerald-600 mb-8">📊 Admin Dashboard</h2>

  <!-- Metrics Grid -->
  <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
    
    <!-- Users -->
    <div class="bg-white shadow-lg rounded-xl p-6 border-t-4 border-blue-500">
      <h3 class="text-xl font-semibold text-gray-700">👤 Total Users</h3>
      <p class="text-4xl font-bold text-blue-600 mt-2"><?= $total_users; ?></p>
    </div>

    <!-- Nutritionists -->
    <div class="bg-white shadow-lg rounded-xl p-6 border-t-4 border-green-500">
      <h3 class="text-xl font-semibold text-gray-700">🥗 Nutritionists</h3>
      <p class="text-4xl font-bold text-green-600 mt-2"><?= $total_nutritionists; ?></p>
      <p class="text-sm text-yellow-600 mt-1">Pending: <?= $pending_nutritionists; ?></p>
    </div>

    <!-- Diet Plans -->
    <div class="bg-white shadow-lg rounded-xl p-6 border-t-4 border-purple-500">
      <h3 class="text-xl font-semibold text-gray-700">📄 Diet Plans</h3>
      <p class="text-4xl font-bold text-purple-600 mt-2"><?= $total_diet_plans; ?></p>
    </div>

    <!-- Appointments -->
    <div class="bg-white shadow-lg rounded-xl p-6 border-t-4 border-pink-500">
      <h3 class="text-xl font-semibold text-gray-700">📅 Appointments</h3>
      <p class="text-4xl font-bold text-pink-600 mt-2"><?= $total_appointments; ?></p>
      <p class="text-sm text-orange-600 mt-1">Pending: <?= $pending_appointments; ?></p>
    </div>
  </div>

  <!-- Quick Actions -->
  <div class="mt-10">
    <h3 class="text-2xl font-semibold text-gray-700 mb-4">⚡ Quick Actions</h3>
    <div class="flex flex-wrap gap-4">
      <a href="nutrionist-approval.php" 
         class="px-6 py-3 bg-green-600 text-white rounded-lg font-semibold hover:bg-green-700 shadow-md">
         ✅ Approve Nutritionists
      </a>
   
    
      <a href="dietplans.php" 
         class="px-6 py-3 bg-purple-600 text-white rounded-lg font-semibold hover:bg-purple-700 shadow-md">
         📄 Manage Diet Plans
      </a>
    </div>
  </div>
</main>
