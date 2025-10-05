<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Assume db_conn.php and its contents are correctly located one directory up
include(__DIR__ . '/../config/db_conn.php'); 

// --- Configuration Data ---
// Define the list of available health issues for the dropdown
$health_issues_options = ['None', 'Diabetes', 'Hypertension', 'Thyroid Disorder', 'Obesity', 'Heart Disease', 'PCOS / PCOD'];
// --- End Configuration Data ---

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../home/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info (including the health_issues column)
$sql = "SELECT * FROM reg WHERE id='$user_id'";
$user_res = mysqli_query($connection, $sql);
$user = mysqli_fetch_assoc($user_res);

// Fetch matching diet plan based on user preferences (still needed for calorie chart)
$diet_sql = "SELECT SUM(protein) AS protein, SUM(carbs) AS carbs, SUM(fat) AS fat, GROUP_CONCAT(meal_text SEPARATOR '\n') AS plan_text
             FROM diet_plans
             WHERE goal = '{$user['goal']}' 
               AND dietary = '{$user['dietary']}' 
               AND activity = '{$user['activity']}' 
               AND meal_type = '{$user['meal_type']}'";
$diet_res = mysqli_query($connection, $diet_sql);
$diet = mysqli_fetch_assoc($diet_res);

// Fetch progress history
$history_sql = "SELECT * FROM progress_history WHERE user_id='$user_id' ORDER BY updated_at DESC";
$history_res = mysqli_query($connection, $history_sql);


// Calculate BMI and message
$bmi = 0;
$bmi_msg = "";
if (!empty($user['height']) && !empty($user['weight'])) {
    $height_m = $user['height'] / 100; // convert cm to m
    $bmi = $user['weight'] / ($height_m * $height_m);

    if ($bmi < 18.5) {
        $bmi_msg = "Underweight";
    } elseif ($bmi < 24.9) {
        $bmi_msg = "Normal";
    } elseif ($bmi < 29.9) {
        $bmi_msg = "Overweight";
    } else {
        $bmi_msg = "Obese";
    }
}

// Generate health-based alert messages
$alert_msg = "";

// 1. BMI-Goal conflict check (original logic)
if ($bmi > 0) {
    if ($bmi < 18.5 && $user['goal'] === 'weight_loss') {
        $alert_msg .= "⚠️ Your BMI indicates **Underweight** but your goal is **Weight Loss**. Please reconsider your target.\n";
    } elseif ($bmi > 25 && $user['goal'] === 'weight_gain') {
        $alert_msg .= "⚠️ Your BMI indicates **Overweight** but your goal is **Weight Gain**. Please consult a professional.\n";
    } elseif ($bmi > 30 && $user['goal'] === 'muscle_build' && $user['activity'] === 'light') {
        $alert_msg .= "⚠️ Your BMI is in the **Obese** range. Muscle building with low activity may require medical supervision.\n";
    }
}

// 2. Health Issue based warning
if ($user['health_issues'] && $user['health_issues'] !== 'None') {
    $alert_msg .= "🩺 Please note your declared health issue: **" . htmlspecialchars($user['health_issues']) . "**. Always consult a doctor before starting any new diet or exercise routine.\n";
}

// Clean up the alert message for display
$alert_msg = trim($alert_msg);


?>

<?php include 'components/head.php'; ?>
<!-- Assuming components/head.php closes the head and opens the body/main layout -->
<?php include 'components/navbar.php'; ?>

<main class="flex-1 overflow-y-auto p-4 md:p-8 bg-gray-50 min-h-screen">
  <!-- Profile Section -->
  <section class="bg-white rounded-xl shadow-2xl p-6 mb-8 border-t-4 border-emerald-500">
    <div class="flex justify-between items-center mb-4">
      <h2 class="text-2xl font-extrabold text-emerald-800">Your Fitness Profile</h2>
      <button onclick="toggleModal(true)" class="bg-emerald-600 text-white font-semibold px-4 py-2 rounded-full shadow-md hover:bg-emerald-700 transition duration-300">
        Update Profile
      </button>
    </div>
    
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 text-gray-700 border-t pt-4">
      <!-- Personal Info -->
      <div class="space-y-2">
        <h3 class="text-lg font-semibold text-gray-800">Personal Details</h3>
        <p><strong>Name:</strong> <?= htmlspecialchars($user['name']); ?></p>
        <p><strong>Email:</strong> <?= htmlspecialchars($user['email']); ?></p>
        <p><strong>Age:</strong> <?= htmlspecialchars($user['age']); ?></p>
      </div>

      <!-- Measurements & Health -->
      <div class="space-y-2">
        <h3 class="text-lg font-semibold text-gray-800">Measurements & Health</h3>
        <p><strong>Height:</strong> <?= htmlspecialchars($user['height']); ?> cm</p>
        <p><strong>Weight:</strong> <?= htmlspecialchars($user['weight']); ?> kg</p>
        <!-- HEALTH ISSUE FIELD -->
        <p>
          <strong>Health Issue:</strong> 
          <span class="font-bold text-red-600"><?= htmlspecialchars($user['health_issues'] ?: 'None'); ?></span>
        </p>
      </div>
      
      <!-- Metrics -->
      <div class="space-y-2">
        <h3 class="text-lg font-semibold text-gray-800">Health Metrics</h3>
        <?php if ($bmi > 0): ?>
          <p><strong>BMI:</strong> <span class="text-emerald-600 font-bold"><?= number_format($bmi, 1); ?></span></p>
          <p><strong>BMI Status:</strong> <span class="font-bold text-indigo-600"><?= htmlspecialchars($bmi_msg); ?></span></p>
        <?php else: ?>
          <p class="text-sm text-gray-500">Enter height/weight to calculate BMI.</p>
        <?php endif; ?>
      </div>
    </div>
  </section>

  <?php  if(!empty($alert_msg)): ?>
    <!-- Alert Section -->
    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4 rounded-md mb-8 shadow-md whitespace-pre-line">
      <h3 class="font-bold text-lg mb-1">Important Health Notice</h3>
      <?= nl2br($alert_msg) ?>
    </div>
  <?php endif; ?>

  <!-- Calorie Breakdown Chart -->
  <section class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <h2 class="text-xl font-bold text-emerald-700 mb-4">Macronutrient Breakdown (Grams)</h2>
    <div class="relative w-full h-80">
      <canvas id="calorieChart"></canvas>
    </div>
    <?php if (empty($diet['protein']) && empty($diet['carbs']) && empty($diet['fat'])): ?>
      <p class="text-center text-gray-500 mt-4">No complete macro data available for your current preferences.</p>
    <?php endif; ?>
  </section>

  <!-- Progress History Section -->
  <section>
    <h2 class="text-xl font-bold text-emerald-700 mb-4">Progress History</h2>
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6">
      <?php if (mysqli_num_rows($history_res) > 0): ?>
        <?php while($h = mysqli_fetch_assoc($history_res)) { ?>
          <div class="bg-white p-6 rounded-xl shadow-md border-l-4 border-indigo-400 hover:shadow-lg transition duration-300">
            <h3 class="font-bold text-indigo-700 mb-2">Progress Update</h3>
            <p class="text-gray-700"><strong>Weight:</strong> <?= $h['weight']; ?> kg</p>
            <p class="text-gray-700"><strong>Height:</strong> <?= $h['height']; ?> cm</p>
            <!-- Assuming health_issues is added to the progress_history table -->
            <p class="text-gray-700"><strong>Health:</strong> <?= htmlspecialchars($h['health_issues'] ?? 'N/A'); ?></p> 
            <p class="text-xs text-gray-500 mt-2">Recorded: <?= date('M d, Y', strtotime($h['updated_at'])); ?></p>
          </div>
        <?php } ?>
      <?php else: ?>
        <p class="text-gray-500 col-span-full">No progress history recorded yet. Use the Update button to log your first progress!</p>
      <?php endif; ?>
    </div>
  </section>
</main>

    </div> <!-- CLOSED THE MAIN CONTENT DIV THAT WAS MISSING -->
</div>




<!-- Modal -->
<div id="updateModal" class="fixed inset-0 bg-black bg-opacity-60 hidden items-center justify-center z-50 p-4" onclick="if (event.target.id === 'updateModal') toggleModal(false)">
  <div class="bg-white rounded-xl shadow-2xl w-full max-w-lg p-8 transform transition-all duration-300 scale-100">
    <div class="flex justify-between items-center border-b pb-3 mb-6">
      <h5 class="text-xl font-bold text-emerald-700">Update Your Profile</h5>
      <button onclick="toggleModal(false)" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
    </div>
    <!-- FORM ACTION POINTS TO THE NEW HANDLER FILE -->
    <form method="POST" action="update_profile.php" class="grid grid-cols-1 md:grid-cols-2 gap-6" id="updateForm">
      <input type="hidden" name="user_id" value="<?= $user_id; ?>">
      
      <!-- Name and Email -->
      <div class="md:col-span-2">
        <label class="block text-gray-700 font-medium mb-1">Name</label>
        <input type="text" name="name" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500" value="<?= htmlspecialchars($user['name']); ?>">
      </div>
      <div class="md:col-span-2">
        <label class="block text-gray-700 font-medium mb-1">Email</label>
        <input type="email" name="email" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500" value="<?= htmlspecialchars($user['email']); ?>">
      </div>

      <!-- Age, Height, Weight -->
      <div>
        <label class="block text-gray-700 font-medium mb-1">Age</label>
        <input type="number" name="age" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500" value="<?= htmlspecialchars($user['age']); ?>">
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-1">Height (cm)</label>
        <input type="number" name="height" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500" value="<?= htmlspecialchars($user['height']); ?>">
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-1">Weight (kg)</label>
        <input type="number" name="weight" class="w-full border border-gray-300 rounded-lg px-3 py-2 focus:ring-emerald-500 focus:border-emerald-500" value="<?= htmlspecialchars($user['weight']); ?>">
      </div>

      <!-- HEALTH ISSUE DROPDOWN -->
      <div>
        <label class="block text-gray-700 font-medium mb-1">Health Issue</label>
        <select name="health_issues" class="w-full border border-gray-300 rounded-lg px-3 py-2 bg-white focus:ring-emerald-500 focus:border-emerald-500">
            <?php foreach ($health_issues_options as $option): ?>
                <option value="<?= htmlspecialchars($option) ?>" <?= (isset($user['health_issues']) && $user['health_issues'] == $option) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($option) ?>
                </option>
            <?php endforeach; ?>
        </select>
      </div>

    </form>
    <div class="flex justify-end mt-8 space-x-3">
      <button onclick="toggleModal(false)" class="px-5 py-2 rounded-full bg-gray-200 text-gray-700 font-medium hover:bg-gray-300 transition duration-300">
        Close
      </button>
      <button type="submit" form="updateForm" class="px-5 py-2 rounded-full bg-emerald-600 text-white font-medium hover:bg-emerald-700 shadow-lg transition duration-300">
        Save Changes
      </button>
    </div>
  </div>
</div>



<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function toggleModal(show) {
  const modal = document.getElementById("updateModal");
  if (show) {
    modal.classList.remove("hidden");
    modal.classList.add("flex");
  } else {
    modal.classList.add("hidden");
    modal.classList.remove("flex");
  }
}

// Ensure DOM is loaded before initializing chart
document.addEventListener("DOMContentLoaded", function() {
    const ctx = document.getElementById('calorieChart');
    if(ctx) {
        // Data for the chart
        const protein = <?= !empty($diet['protein']) ? $diet['protein'] : 0 ?>;
        const carbs = <?= !empty($diet['carbs']) ? $diet['carbs'] : 0 ?>;
        const fat = <?= !empty($diet['fat']) ? $diet['fat'] : 0 ?>;
        
        // Only draw if there is data
        if (protein > 0 || carbs > 0 || fat > 0) {
            const calorieChart = new Chart(ctx.getContext('2d'), {
                type: 'doughnut',
                data: {
                    labels: ['Protein (g)', 'Carbs (g)', 'Fat (g)'],
                    datasets: [{
                        data: [protein, carbs, fat],
                        backgroundColor: [
                            '#059669', // Emerald 600
                            '#3B82F6', // Blue 500
                            '#F59E0B'  // Amber 500
                        ],
                        hoverBackgroundColor: [
                            '#10B981',
                            '#60A5FA',
                            '#FBBF24'
                        ],
                        borderWidth: 2,
                        borderColor: '#ffffff',
                    }]
                },
                options: { 
                    responsive: true,
                    maintainAspectRatio: false,
                    cutout: '70%', // Make it a ring chart
                    plugins: { 
                        legend: { 
                            position: 'bottom',
                            labels: {
                                padding: 20,
                                font: { size: 14 }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Macronutrient Distribution (in Grams)',
                            font: { size: 16, weight: 'bold' },
                            color: '#10B981'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    let label = context.label || '';
                                    if (label) { label += ': '; }
                                    if (context.parsed !== null) { label += context.parsed + ' g'; }
                                    return label;
                                }
                            }
                        }
                    }
                }
            });
        } else {
            // Hide canvas and show a message if no data is available
            ctx.style.display = 'none';
        }
    }
});
</script>
