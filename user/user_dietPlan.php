<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . '/../config/db_conn.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../home/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user info
$user_res = mysqli_query($connection, "SELECT * FROM reg WHERE id='$user_id'");
$user = mysqli_fetch_assoc($user_res);

// --- TDEE / CALORIE GOAL CALCULATION ---
$age = $user['age'];
$weight = $user['weight']; // kg
$height = $user['height']; // cm
$gender = $user['gender'];
$activity = $user['activity'];
$goal = $user['goal'];

$bmr = 0;
if ($gender === 'male') {
    // Men: 10 * weight + 6.25 * height - 5 * age + 5
    $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) + 5;
} else {
    // Women: 10 * weight + 6.25 * height - 5 * age - 161
    $bmr = (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;
}
$bmr = round($bmr);

$activity_multiplier = match ($activity) {
    'light' => 1.375,
    'moderate' => 1.55,
    'active' => 1.725,
    default => 1.2 // Sedentary/default
};

$tdee = round($bmr * $activity_multiplier);

// Determine Calorie Goal Range and Strictness
$goal_strictness = "Low";
$min_cal_target = $tdee;
$max_cal_target = $tdee;
$goal_text = match ($goal) {
    'weight_loss' => "Weight Loss (Deficit)",
    'weight_gain' => "Weight Gain (Surplus)",
    'muscle_build' => "Muscle Building (Maintenance/Slight Surplus)",
    'balanced' => "Balanced Maintenance",
    default => "Balanced Maintenance"
};

switch ($goal) {
    case 'weight_loss':
        $min_cal_target = $tdee - 500;
        $max_cal_target = $tdee - 300;
        $goal_strictness = "High";
        break;
    case 'weight_gain':
        $min_cal_target = $tdee + 300;
        $max_cal_target = $tdee + 500;
        $goal_strictness = "Medium";
        break;
    case 'muscle_build':
        $min_cal_target = $tdee + 150;
        $max_cal_target = $tdee + 300;
        $goal_strictness = "Medium";
        break;
    case 'balanced':
    default:
        $min_cal_target = $tdee - 100;
        $max_cal_target = $tdee + 100;
        $goal_strictness = "Low";
        break;
}

// Ensure minimum calorie is sensible (Safety net)
$min_cal_target = max($min_cal_target, ($gender === 'male' ? 1500 : 1200));

// --- Health Issue Management Timeline (NEW LOGIC) ---
$health_issues = htmlspecialchars($user['health_issues'] ?? 'None specified.');
$health_advice = match ($health_issues) {
    'None specified.' => 'Continue with your regular plan. Focus on consistent hydration and listening to your body.',
    default => 'Given your specific health focus, adherence to meal ingredients and portion sizes is <strong class="text-red-600">crucial</strong>. Strictly monitor ingredients to prevent complications.'
};

$timeline_estimate = "";
$disclaimer = '<span class="text-sm text-gray-500 italic">Note: This is a model-based estimate assuming strict adherence and is not medical advice. Consult a healthcare professional.</span>';

if ($user['health_issues'] && $user['health_issues'] !== 'None') {
    $issue = $user['health_issues'];
    
    // Default estimate for complex metabolic/hormonal issues
    $estimate_text = "3 to 6 months"; 

    if ($issue === 'Obesity' && $goal === 'weight_loss') {
        // Estimate target loss for significant progress (e.g., 15% of body weight)
        $target_loss_kg = round($weight * 0.15, 1);
        // Healthy rate: ~0.75 kg per week
        $weeks = ceil($target_loss_kg / 0.75);
        $months = max(3, ceil($weeks / 4.3)); // Minimum 3 months
        
        $estimate_text = "Approximately <strong>{$months} months</strong> to achieve significant weight reduction ($target_loss_kg kg) for better management.";
    } elseif (in_array($issue, ['Diabetes', 'Hypertension', 'Heart Disease'])) {
        $estimate_text = "<strong>6 to 12 months</strong> of consistent adherence for stabilization of key health markers (e.g., A1C, blood pressure).";
    } elseif (in_array($issue, ['Thyroid Disorder', 'PCOS / PCOD'])) {
        $estimate_text = "<strong>3 to 6 months</strong> of dedicated dietary focus to see symptomatic improvements.";
    }

    $timeline_estimate = "Based on your plan and health issue (<strong>{$issue}</strong>), significant progress toward management is estimated to take: <strong>{$estimate_text}</strong>.";
}
// --- END Health Issue Management Timeline ---


// Fetch user diet plans, using a comprehensive meal time order
$plans_res = mysqli_query($connection, "
    SELECT * FROM user_diet_plans 
    WHERE user_id='$user_id' 
    ORDER BY day_number ASC, FIELD(meal_time, 'breakfast','mid_morning','lunch','snack','dinner', 'pre_workout', 'post_workout')
");

// Group plans by day
$userPlans = [];
while ($row = mysqli_fetch_assoc($plans_res)) {
    $day = $row['day_number'];
    if(!isset($userPlans[$day])) $userPlans[$day] = [];
    $userPlans[$day][] = $row;
}

// --- Calorie Consistency Check (NEW LOGIC) ---
$all_plans_too_high = !empty($userPlans); // Assume true if plans exist initially
$max_cal_target_check = $max_cal_target; 

foreach ($userPlans as $dayNum => $meals) {
    $totalCalories = array_sum(array_column($meals, 'calories'));
    
    // If ANY day is within or below the target max, the flag is false, and we can stop checking.
    if ($totalCalories <= $max_cal_target_check) {
        $all_plans_too_high = false;
        break;
    }
}
// --- END Calorie Consistency Check ---


// Capture session messages
$successMsg = $_SESSION['success'] ?? '';
$errorMsg = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);
?>
<?php include 'components/head.php'; ?>
<?php include 'components/navbar.php'; ?>

<main class="flex-1 overflow-y-auto p-8 bg-gray-50">
  <h2 class="text-3xl font-extrabold text-gray-800 mb-8 border-b-2 border-emerald-400 pb-2">🍽 Your Weekly Diet Plans</h2>

  <!-- Display session messages -->
  <?php if($successMsg): ?>
    <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-800 rounded-lg font-medium shadow-md">
      <?= htmlspecialchars($successMsg) ?>
    </div>
  <?php endif; ?>
  <?php if($errorMsg): ?>
    <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-800 rounded-lg font-medium shadow-md">
      <?= htmlspecialchars($errorMsg) ?>
    </div>
  <?php endif; ?>
  
  <!-- Calorie Consistency Alert (NEW ALERT SECTION) -->
  <?php if ($all_plans_too_high): ?>
    <div class="mb-6 p-6 bg-red-100 border-l-4 border-red-500 text-red-800 rounded-lg shadow-xl flex flex-col md:flex-row justify-between items-center">
        <div>
            <h4 class="font-extrabold text-xl mb-1">Plan Calorie Warning!</h4>
            <p class="text-lg">All your current plan days have total calories **higher** than your target maximum (<?= $max_cal_target ?> kcal). </p>
            <p class="mt-1 font-semibold">We recommend generating a new plan to better meet your **<?= $goal_text ?>** goal.</p>
        </div>
        <button onclick="window.location='plans.php'" class="mt-4 md:mt-0 px-6 py-3 bg-red-600 text-white font-bold rounded-full shadow-md hover:bg-red-700 transition duration-300">
            Generate New Plan
        </button>
    </div>
  <?php endif; ?>
  
  <?php if(empty($userPlans)): ?>
    <div class="p-8 bg-white rounded-xl shadow-lg border border-gray-200">
        <p class="text-gray-600 text-lg">No diet plans found. Please generate or add your diet plans first to see your weekly schedule and metrics.</p>
    </div>
  <?php endif; ?>

  <!-- --- USER METRICS & GOAL CARD --- -->
  <div class="bg-white rounded-xl shadow-2xl p-6 mb-8 border-t-4 border-emerald-500">
    <h3 class="text-2xl font-bold text-gray-700 mb-4 flex items-center">
      <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6 mr-2 text-emerald-500" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0h6" />
      </svg>
      Health & Goal Overview
    </h3>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 text-center">
      <!-- TDEE -->
      <div class="p-4 bg-emerald-50 rounded-lg">
        <p class="text-sm font-semibold text-gray-500">Maintenance Calories (TDEE)</p>
        <p class="text-2xl font-extrabold text-emerald-700"><?= $tdee ?> kcal</p>
      </div>
      
      <!-- Target Calories (UPDATED LABEL) -->
      <div class="p-4 bg-blue-50 rounded-lg">
        <p class="text-sm font-semibold text-gray-500">Goal-Adjusted Daily Range</p>
        <p class="text-2xl font-extrabold text-blue-700"><?= $min_cal_target ?> - <?= $max_cal_target ?> kcal</p>
      </div>

      <!-- Goal -->
      <div class="p-4 bg-yellow-50 rounded-lg">
        <p class="text-sm font-semibold text-gray-500">Primary Goal</p>
        <p class="text-2xl font-extrabold text-yellow-700 capitalize"><?= str_replace('_', ' ', $user['goal']) ?></p>
      </div>

      <!-- Strictness -->
      <div class="p-4 bg-red-50 rounded-lg">
        <p class="text-sm font-semibold text-gray-500">Adherence Strictness</p>
        <p class="text-2xl font-extrabold text-red-700"><?= $goal_strictness ?></p>
      </div>
    </div>
    
    <!-- Health Issue & Advice -->
    <div class="mt-4 p-4 bg-gray-100 rounded-lg border border-gray-200">
        <p class="font-semibold text-gray-700 mb-1">Health Issues Reported: <strong class="text-red-600"><?= $health_issues ?></strong></p>
        <p class="text-base text-emerald-600 font-medium"><?= $health_advice ?></p>
        
        <?php if($timeline_estimate): ?>
        <div class="mt-3 pt-3 border-t border-gray-300">
            <p class="font-semibold text-gray-700 mb-1">Estimated Improvement Timeline:</p>
            <p class="text-base text-indigo-700 font-medium"><?= $timeline_estimate ?></p>
            <?= $disclaimer ?>
        </div>
        <?php endif; ?>
    </div>
  </div>
  <!-- --- END USER METRICS & GOAL CARD --- -->


  <?php if(!empty($userPlans)): ?>
    <div class="space-y-8">
      <?php foreach($userPlans as $dayNum => $meals): ?>
        <?php
          $totalProtein = array_sum(array_column($meals, 'protein'));
          $totalCarbs   = array_sum(array_column($meals, 'carbs'));
          $totalFat     = array_sum(array_column($meals, 'fat'));
          $totalCalories = array_sum(array_column($meals, 'calories'));

          // Determine Calorie Status Text and Color
          $cal_status_class = '';
          $cal_status_text = '';

          if ($totalCalories >= $min_cal_target && $totalCalories <= $max_cal_target) {
              $cal_status_class = 'text-green-800 bg-green-200';
              $cal_status_text = 'IN TARGET RANGE';
          } elseif ($totalCalories > $max_cal_target) {
              $cal_status_class = 'text-red-800 bg-red-200';
              $cal_status_text = 'TOO HIGH';
          } else { // $totalCalories < $min_cal_target
              $cal_status_class = 'text-orange-800 bg-orange-200';
              $cal_status_text = 'TOO LOW';
          }
        ?>
        <div class="rounded-xl overflow-hidden shadow-xl border border-gray-300 bg-white transform hover:scale-[1.01] transition duration-300">
          <!-- Day Header -->
          <div class="bg-gradient-to-r from-emerald-600 to-emerald-500 text-white px-6 py-3 text-xl font-bold flex flex-col sm:flex-row justify-between items-start sm:items-center">
            <span class="mb-1 sm:mb-0">📅 Day <?= $dayNum ?></span>
            
            <div class="flex flex-wrap gap-4 text-sm items-center font-semibold bg-emerald-700/30 p-1.5 rounded-lg">
              <!-- UPDATED: Calorie Status Badge -->
              <span class="flex flex-col items-center justify-center gap-0 font-extrabold px-3 py-1 rounded-full shadow-sm text-sm <?= $cal_status_class ?>">
                <span class="text-xs font-semibold"><?= $cal_status_text ?></span>
                <span class="text-lg">Total Kcal: <?= $totalCalories ?></span>
              </span>
              <!-- End Calorie Status Badge -->

              <span class="bg-white text-emerald-800 px-3 py-1 rounded-full shadow-sm">P: <strong class="text-lg"><?= $totalProtein ?></strong>g</span>
              <span class="bg-white text-blue-800 px-3 py-1 rounded-full shadow-sm">C: <strong class="text-lg"><?= $totalCarbs ?></strong>g</span>
              <span class="bg-white text-yellow-800 px-3 py-1 rounded-full shadow-sm">F: <strong class="text-lg"><?= $totalFat ?></strong>g</span>
            </div>
          </div>

          <!-- Meals & Chart side by side -->
          <div class="flex flex-col lg:flex-row items-stretch">
            <!-- Meals List -->
            <div class="flex-1 p-6 divide-y divide-gray-100 lg:w-3/5">
              <?php foreach($meals as $meal): ?>
                <?php
                  // Meal time badge color
                  $badgeColor = match($meal['meal_time']) {
                      'breakfast' => 'bg-yellow-100 text-yellow-800 border-yellow-300',
                      'mid_morning' => 'bg-orange-100 text-orange-800 border-orange-300',
                      'lunch'     => 'bg-blue-100 text-blue-800 border-blue-300',
                      'snack'     => 'bg-purple-100 text-purple-800 border-purple-300',
                      'dinner'    => 'bg-red-100 text-red-800 border-red-300',
                      'pre_workout' => 'bg-pink-100 text-pink-800 border-pink-300',
                      'post_workout' => 'bg-teal-100 text-teal-800 border-teal-300',
                      default     => 'bg-gray-100 text-gray-800 border-gray-300'
                  };
                ?>


                <div class="py-4 flex flex-col sm:flex-row justify-between items-start sm:items-center hover:bg-emerald-50 transition duration-150">
                    <div class="flex-1 min-w-0 mb-3 sm:mb-0">
                        <!-- Meal Time & Name -->
                        <div class="flex items-center mb-1">
                            <span class="px-3 py-1 border rounded-full text-xs font-bold <?= $badgeColor ?> shadow-sm mr-3"><?= strtoupper(str_replace('_', ' ', $meal['meal_time'])) ?></span>
                            <span class="text-lg font-extrabold text-gray-800 truncate"><?= htmlspecialchars($meal['meal_text']) ?></span>
                        </div>

                        <!-- Quantity & Macros -->
                        <div class="flex flex-wrap gap-x-4 gap-y-2 text-sm ml-0 sm:ml-12">
                            <!-- Quantity -->
                            <?php if (!empty($meal['quantity'])): ?>
                                <div class="text-gray-600 font-semibold">
                                    Portion: <span class="font-extrabold text-emerald-700"><?= htmlspecialchars($meal['quantity']) ?></span>
                                </div>
                            <?php endif; ?>

                            <!-- Nutrition Info Badges -->
                            <div class="flex gap-2">
                                <span class="bg-emerald-200 text-emerald-800 px-2 py-0.5 rounded-full font-bold">P: <?= $meal['protein'] ?>g</span>
                                <span class="bg-blue-200 text-blue-800 px-2 py-0.5 rounded-full font-bold">C: <?= $meal['carbs'] ?>g</span>
                                <span class="bg-yellow-200 text-yellow-800 px-2 py-0.5 rounded-full font-bold">F: <?= $meal['fat'] ?>g</span>
                                <span class="bg-gray-200 text-gray-800 px-2 py-0.5 rounded-full font-bold">Kcal: <?= $meal['calories'] ?></span>
                            </div>
                        </div>
                    </div>

                    <!-- Swap Button: ACTION UPDATED -->
                    <form method="POST" action="swap_meal_logic.php" class="flex-shrink-0">
                        <input type="hidden" name="meal_id" value="<?= $meal['id'] ?>">
                        <input type="hidden" name="old_protein" value="<?= $meal['protein'] ?>">
                        <input type="hidden" name="old_carbs" value="<?= $meal['carbs'] ?>">
                        <input type="hidden" name="old_fat" value="<?= $meal['fat'] ?>">
                        <input type="hidden" name="old_calories" value="<?= $meal['calories'] ?>">
                        <input type="hidden" name="meal_time" value="<?= $meal['meal_time'] ?>">
                        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded-full hover:bg-emerald-700 transition duration-150 shadow-md flex items-center gap-1 font-semibold text-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor">
                                <path d="M4 7a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM4 12a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1zM4 17a1 1 0 011-1h10a1 1 0 110 2H5a1 1 0 01-1-1z" />
                            </svg>
                            Swap Meal
                        </button>
                    </form>
                </div>
              <?php endforeach; ?>
            </div>

            <!-- Chart -->
            <div class="w-full lg:w-2/5 p-6 flex justify-center items-center lg:border-l border-gray-200 bg-gray-100/50">
              <div class="max-w-xs w-full h-64 flex justify-center items-center">
                <canvas id="macroChart<?= $dayNum ?>" class="w-full h-full"></canvas>
              </div>
            </div>
          </div>
        </div>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>
</main>

<!-- Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
  <?php foreach($userPlans as $dayNum => $meals): ?>
    const ctx<?= $dayNum ?> = document.getElementById('macroChart<?= $dayNum ?>').getContext('2d');
    new Chart(ctx<?= $dayNum ?>, {
      type: 'doughnut',
      data: {
        labels: ['Protein', 'Carbs', 'Fat'],
        datasets: [{
          data: [
            <?= array_sum(array_column($meals, 'protein')) ?>,
            <?= array_sum(array_column($meals, 'carbs')) ?>,
            <?= array_sum(array_column($meals, 'fat')) ?>
          ],
          backgroundColor: ['#10B981', '#3B82F6', '#F59E0B'],
          borderWidth: 1
        }]
      },
      options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
          legend: { position: 'bottom' },
          tooltip: { enabled: true },
        }
      }
    });
  <?php endforeach; ?>
});
</script>

