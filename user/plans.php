<?php
/**
 * user_dietPlan.php
 * Handles TDEE calculation, plan retrieval from the diet_plans table, 
 * client-side dynamic scaling based on target calories, and saving the final scaled plan.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
session_start();
// Assuming these components handle HTML structure, header, and navigation
include 'components/head.php'; 
include 'components/navbar.php'; 
// NOTE: Replace this with your actual path to db_conn.php
include(__DIR__ . '/../config/db_conn.php'); 

if (!isset($_SESSION['user_id'])) {
    header("Location: ../home/login.php");
    exit;
}

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$user_id = $_SESSION['user_id'];
$success = $error = "";
$loadedPlan = [];
$showSaveButton = false;
$tdee = 0; // Initialize TDEE for JS
$dailyTarget = 0; // Initialize daily target for JS
$bmi = 0; // Initialize BMI
$baseTotalCalories = 0; // New: To store the total calories of the fetched BASE plan

// Function to display styled alerts
function displayAlert($message, $type = 'error') {
    // Using Emerald colors for success state
    $class = ($type === 'success') 
        ? 'bg-emerald-100 border-emerald-400 text-emerald-700' 
        : 'bg-red-100 border-red-400 text-red-700';
    $icon = ($type === 'success') ? '✅ Success:' : '❌ Error:';
    
    echo "
    <div class='alert-container'>
        <style>
            .alert-container .{$class} { 
                border: 1px solid; 
                padding: 15px; 
                margin-bottom: 20px; 
                border-radius: 8px; 
                font-weight: 600; 
                box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            }
        </style>
        <div class='{$class} px-4 py-3 rounded mb-4 font-semibold'>
            {$icon} {$message}
        </div>
    </div>
    ";
}

// Macro Calorie Conversions (Kcal per gram) - Used only for JS calculation reference
const CALORIES_PER_GRAM = ['P' => 4, 'C' => 4, 'F' => 9];

// --- Fetch user details ---
$userRes = $connection->prepare("SELECT * FROM reg WHERE id = ?");
$userRes->bind_param("i", $user_id);
$userRes->execute();
$user = $userRes->get_result()->fetch_assoc();
$userRes->close();

if (!$user) {
    $error = "User record not found! Please check your account data.";
} else {
    // Ensure all necessary user data is available
    $weight = floatval($user['weight'] ?? 0);
    $height = floatval($user['height'] ?? 0);
    $age = intval($user['age'] ?? 0);
    $gender = strtolower($user['gender'] ?? 'female');
    // Normalize health issues for robust checking
    $health_issues = strtolower(trim($user['health_issues'] ?? ''));

    // Calculate BMI (Weight in kg, Height in cm)
    $height_m = $height / 100;
    $bmi = ($height_m > 0) ? round($weight / ($height_m * $height_m), 1) : 0;

    // Set defaults from user profile for form if not posted
    $finalGoal = $_POST['goal'] ?? $user['goal'] ?? '';
    $finalDietary = $_POST['food'] ?? $user['dietary'] ?? '';
    $finalActivity = $_POST['activity'] ?? $user['activity'] ?? '';
    $finalMealType = $_POST['meal_type'] ?? $user['meal_type'] ?? '';
}


// ================== HANDLE PLAN GENERATION ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_plan'])) {
    $inputGoal      = $_POST['goal'] ?? '';
    $inputDietary   = $_POST['food'] ?? '';
    $inputActivity  = $_POST['activity'] ?? '';
    $inputMealType  = $_POST['meal_type'] ?? '';
    $canProceed     = true; 
    
    // Initialize the REQUIRED health focus for the SQL query
    $finalHealthFocus = "none"; 
    $healthWarning = "";

    // --- Initial Validation ---
    if (empty($inputGoal) || empty($inputDietary) || empty($inputActivity) || empty($inputMealType)) {
        $error = "Please select all options to generate a plan.";
        $canProceed = false;
    } elseif ($weight <= 0 || $height <= 0 || $age <= 0) {
        $error = "Please update your weight, height, and age in your profile to generate a personalized plan.";
        $canProceed = false;
    } 
    
    if ($canProceed) {
        
        // Variables used for the SQL fetch
        $currentGoal      = $inputGoal;
        $currentDietary   = $inputDietary;
        $currentActivity  = $inputActivity;
        $currentMealType  = $inputMealType;
        $currentGoalDisplay = ucwords(str_replace('_', ' ', $inputGoal));
        
        // --- Health/Goal Conflict Check 1: BMI Safety ---
        if ($bmi >= 40) {
             if ($inputGoal === 'weight_gain' || $inputGoal === 'muscle_build') {
                $error = "Health Safety Alert: Your current BMI ({$bmi}) indicates a high-risk category (Obesity Class III). The selected goal ({$currentGoalDisplay}) is medically discouraged. Please select Weight Loss or Balanced Diet.";
                $canProceed = false;
            }
        }
        
        // --- Health/Goal Conflict Check 2 & Health Focus Determination ---
        if ($canProceed) {
            
            // Diabetes / PCOS / PCOD: BLOCK weight gain/muscle build, SET low_carb filter
            if (preg_match('/\b(diabetes|diabetic|pcod|pcos)\b/i', $health_issues)) {
                
                if ($currentGoal === 'weight_gain' || $currentGoal === 'muscle_build') {
                    $error = "Health Safety Alert: Your selected goal ({$currentGoalDisplay}) is highly discouraged for users with Diabetes, PCOS, or PCOD. Please select Weight Loss or Balanced Diet.";
                    $canProceed = false;
                }
                
                if ($canProceed) {
                    $finalHealthFocus = "low_carb"; // Set focus for SQL query
                    $healthWarning = "A specialized **Low-Carb** filter has been applied to your plan for optimal blood sugar control.";
                }
            } 
            
            // Heart Disease: BLOCK weight gain/muscle build, SET high_fiber filter
            elseif (preg_match('/\b(heart|cardiac|hypertension|cholesterol)\b/i', $health_issues)) {
                
                if ($currentGoal === 'weight_gain' || $currentGoal === 'muscle_build') {
                    $error = "Health Safety Alert: Your selected goal ({$currentGoalDisplay}) is unsafe for users with Heart Disease/Hypertension. Please select Weight Loss or Balanced Diet.";
                    $canProceed = false;
                }
                
                if ($canProceed) {
                    $finalHealthFocus = "high_fiber"; // Set focus for SQL query
                    $healthWarning = "A **Heart-Healthy High-Fiber** filter has been applied. Please ensure your food choices are low in sodium and saturated fats.";
                }
            }
            
            // Obesity: BLOCK weight gain, SET high_fiber filter
            elseif (preg_match('/\b(obesity|overweight|high\s?bmi)\b/i', $health_issues) || $bmi >= 30) {
                
                if ($currentGoal === 'weight_gain') {
                    $error = "Health Safety Alert: Your selected goal (Weight Gain) is unsafe for users with Obesity. Please select Weight Loss or Balanced Diet.";
                    $canProceed = false;
                }
                
                if ($canProceed) {
                    $finalHealthFocus = "high_fiber"; // Set focus for SQL query
                    $healthWarning = "A **High-Fiber** filter has been applied to support effective weight management.";
                }
            }
        }
    }

    if ($canProceed) {
        // --- 1: TDEE Calculation ---
        // Harris-Benedict (Revised) or similar BMR formula
        // Weight in kg, Height in cm, Age in years
        $bmr = ($gender === 'male')
            ? (10 * $weight) + (6.25 * $height) - (5 * $age) + 5
            : (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;

        $bmrAdjusted = $bmr;
        switch ($inputActivity) {
            case 'light': $bmrAdjusted *= 1.375; break;
            case 'moderate': $bmrAdjusted *= 1.55; break;
            case 'active': $bmrAdjusted *= 1.725; break;
            default: $bmrAdjusted *= 1.2; // Sedentary
        }
        $tdee = round($bmrAdjusted);
        
        // --- 3: Calculate Final Target Calories ---
        switch ($currentGoal) {
            case 'weight_loss': $calorieAdjustment = -500; break;
            case 'weight_gain': $calorieAdjustment = 500; break;
            case 'muscle_build': $calorieAdjustment = 300; break;
            default: $calorieAdjustment = 0;
        }
        
        $targetCalories = $tdee + $calorieAdjustment;
        
        // Ensure target calories is safe minimum (WHO/medical standards)
        $dailyTarget = max(($gender === 'male' ? 1500 : 1200), round($targetCalories));
        
        // --- 4: Plan Fetching (Now fetches base nutritional data for runtime scaling) ---
        $loadedPlan = [];
        $planAttempt = []; // Will store results from DB
        $isFallback = false;
        
        // Reusable function to fetch all 7 days worth of individual meal rows
        $fetchPlanRows = function($goal, $dietary, $activity, $meal_type, $focus) use ($connection) {
            // CRITICAL: Fetching the base nutritional data AND the base_quantity (grams) for dynamic scaling
            $query = "
                SELECT 
                    day_number, meal_time, meal_text, quantity, 
                    protein, carbs, fat, calories, 
                    protein_per_unit, carbs_per_unit, fat_per_unit, calories_per_unit, 
                    base_quantity, unit
                FROM diet_plans 
                WHERE goal=? 
                AND dietary=? 
                AND activity=? 
                AND meal_type=? 
                AND health_focus=?
                ORDER BY day_number ASC, FIELD(meal_time, 'breakfast', 'snack1', 'lunch', 'snack2', 'dinner', 'snack3')
            ";
            
            $stmt = $connection->prepare($query);
            $stmt->bind_param("sssss", $goal, $dietary, $activity, $meal_type, $focus);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($res->num_rows > 0) {
                return $res->fetch_all(MYSQLI_ASSOC);
            } 
            
            return false; // Return false if no rows found.
        }; 

        // --- Fetch Attempt 1: Highly Filtered Plan ---
        $planAttempt = $fetchPlanRows($currentGoal, $inputDietary, $currentActivity, $currentMealType, $finalHealthFocus);
        
        // --- Fetch Attempt 2: Fallback to 'none' filter if the health-specific plan wasn't generated ---
        if ($planAttempt === false && $finalHealthFocus !== "none") {
            $isFallback = true;
            $finalHealthFocus = "none"; // Set focus for SQL query
            $planAttempt = $fetchPlanRows($currentGoal, $inputDietary, $currentActivity, $currentMealType, $finalHealthFocus); 
            if ($planAttempt !== false) {
                 $healthWarning .= " **NOTE: The highly specialized health filter was unavailable, falling back to a general plan.**";
            }
        }
        
        // --- 5: Plan Assembly and Finalization ---
        if ($planAttempt !== false) {
            
            $tempPlan = [];
            $totalBaseCalorieSum = 0; // Total calories of the base plan (before scaling)
            
            // Group the individual meal rows into the required 7-day structure
            foreach ($planAttempt as $mealRow) {
                $dayNum = $mealRow['day_number'];
                
                // Get the goal-adjusted BASE macro/calorie values directly from the DB row
                $baseProtein = $mealRow['protein'];
                $baseCarbs   = $mealRow['carbs'];
                $baseFat     = $mealRow['fat'];
                $baseCalories= $mealRow['calories'];
                
                // CRITICAL FIX: Use the numeric 'base_quantity' (grams) for scaling, and 'quantity' for display.
                $baseQuantityInGrams = floatval($mealRow['base_quantity']); // Numeric grams for calculation
                $displayQuantityString = $mealRow['quantity']; // e.g., "1 plate", "2 chapatis"


                // Initialize day if it doesn't exist
                if (!isset($tempPlan[$dayNum])) {
                    $tempPlan[$dayNum] = [
                        'day_number' => $dayNum,
                        'meals' => [],
                        'P' => 0, 
                        'C' => 0, 
                        'F' => 0, 
                        'Cal' => 0,
                    ];
                }
                
                $mealData = [
                    'meal_time'     => $mealRow['meal_time'],
                    'meal_text'     => $mealRow['meal_text'],
                    
                    // Base 100g unit values for JS calculation
                    'protein_per_unit' => $mealRow['protein_per_unit'], 
                    'carbs_per_unit'   => $mealRow['carbs_per_unit'], 
                    'fat_per_unit'     => $mealRow['fat_per_unit'], 
                    
                    // Store the numeric Base Quantity (grams) for JS scaling
                    'base_quantity_grams' => $baseQuantityInGrams, // NEW KEY
                    'unit'          => $mealRow['unit'],          
                    
                    // Store the Goal-Adjusted BASE macros/calories directly from the DB
                    'protein'       => $baseProtein, 
                    'carbs'         => $baseCarbs,
                    'fat'           => $baseFat,
                    'calories'      => $baseCalories,
                    'quantity_string' => $displayQuantityString, // NEW KEY for descriptive string
                    'quantity'      => $displayQuantityString, // Retain original key for initial display
                ];

                // Add meal to the day's meals
                $tempPlan[$dayNum]['meals'][] = $mealData;
                
                // Accumulate base daily totals for scaling factor calculation
                $tempPlan[$dayNum]['P'] += $baseProtein;
                $tempPlan[$dayNum]['C'] += $baseCarbs;
                $tempPlan[$dayNum]['F'] += $baseFat;
                $tempPlan[$dayNum]['Cal'] += $baseCalories;
                $totalBaseCalorieSum += $baseCalories;
            }
            
            // Calculate the 7-day average base calories
            $baseTotalCalories = round($totalBaseCalorieSum / 7);

            // Convert indexed array to sequential array for display loop
            $loadedPlan = array_values($tempPlan); 
            
            // Check if 7 full days were retrieved
            if (count($loadedPlan) < 7) {
                 $error = "❌ Error: Found only " . count($loadedPlan) . " days of data. A complete 7-day plan is required. Please ensure your generator script (`test.php`) ran successfully for this combination.";
                 $loadedPlan = []; // Clear partial data
            } else {
                
                // Set final parameters for saving and hidden fields
                $finalGoal = $currentGoal;
                $finalDietary = $inputDietary;
                $finalActivity = $currentActivity;
                $finalMealType = $currentMealType;

                $goalName = ucwords(str_replace('_', ' ', $finalGoal));
                // Note that the plan is *not* yet scaled in the display, the JS will do that.
                $success = "✅ Success: Personalized Plan loaded. It is now scaled dynamically to match your **{$dailyTarget} kcal** target." . ($healthWarning ? " <br>{$healthWarning}" : "");
                $showSaveButton = true;
            }
            
        } else {
             // Plan failed to fetch (no meals in DB)
             $error = "❌ Error: No complete 7-day plan found at this moment for your selected options (Goal: {$currentGoalDisplay}, Diet: {$currentDietary}, Focus: {$finalHealthFocus}). Please ensure you have generated this combination using your `test.php` script.";
        }
    } // End of if ($canProceed)
}


// ================== HANDLE SAVE PLAN (Saves the final SCALED data) ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_plan'])) {
    // These values are passed via hidden fields to save user preferences
    $savedGoal      = $_POST['goal'] ?? '';
    $savedDietary   = $_POST['food'] ?? '';
    $savedActivity  = $_POST['activity'] ?? '';
    $savedMealType  = $_POST['meal_type'] ?? '';

    if (empty($_POST['plan'])) {
        $error = "❌ Error: No plan data received from the client. Please regenerate and try again.";
    } else {
        // Decode the JSON plan data passed from the hidden field (THIS IS THE SCALED DATA FROM JS)
        $plan = json_decode(html_entity_decode($_POST['plan']), true);

        if (!$plan || !is_array($plan)) {
            $error = "❌ Error: Invalid plan structure or data loss during save!";
        } else {
            $connection->begin_transaction();
            try {
                // 🔹 Step 1: Delete existing user plan
                $delStmt = $connection->prepare("DELETE FROM user_diet_plans WHERE user_id = ?");
                if (!$delStmt) { throw new Exception("Prepare failed (DELETE): " . $connection->error); }
                $delStmt->bind_param("i", $user_id);
                $delStmt->execute();
                $delStmt->close();

                // 🔹 Step 2: Insert new plan
                // NOTE: This assumes the user's `user_diet_plans` table is structured for one meal per row.
                $insertStmt = $connection->prepare("
                    INSERT INTO user_diet_plans 
                    (user_id, day_number, meal_time, meal_text, quantity, protein, carbs, fat, calories)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$insertStmt) { throw new Exception("Prepare failed (INSERT): " . $connection->error); }

                foreach ($plan as $day) {
                    $day_number = intval($day['day_number']);
                    foreach ($day['meals'] as $meal) {
                        $meal_time  = trim($meal['meal_time'] ?? '');
                        $meal_text  = trim($meal['meal_text'] ?? '');
                        
                        // Only save non-SKIP meals.
                        if (trim(strtoupper($meal_text)) === 'SKIP') continue; 
                        
                        // IMPORTANT: Use the SCALED data passed from JS here
                        $quantity   = trim($meal['quantity'] ?? ''); // Final scaled portion (e.g., "170g")
                        $protein    = intval($meal['protein'] ?? 0);
                        $carbs      = intval($meal['carbs'] ?? 0);
                        $fat        = intval($meal['fat'] ?? 0);
                        $calories   = intval($meal['calories'] ?? 0);

                        if (empty($meal_time) || empty($meal_text)) continue;

                        $insertStmt->bind_param(
                            "iisssiiii",
                            $user_id,
                            $day_number,
                            $meal_time,
                            $meal_text,
                            $quantity, 
                            $protein,
                            $carbs,
                            $fat,
                            $calories
                        );
                        $insertStmt->execute();
                    }
                }
                $insertStmt->close();

                // 🔹 Step 3: Update reg table with user preferences
                if (!empty($savedGoal) && !empty($savedDietary) && !empty($savedActivity) && !empty($savedMealType)) {
                    $updateReg = $connection->prepare("
                        UPDATE reg 
                        SET goal = ?, dietary = ?, activity = ?, meal_type = ? 
                        WHERE id = ?
                    ");
                    if (!$updateReg) { throw new Exception("Prepare failed (UPDATE reg): " . $connection->error); }
                    $updateReg->bind_param("ssssi", $savedGoal, $savedDietary, $savedActivity, $savedMealType, $user_id);
                    $updateReg->execute();
                    $updateReg->close();
                }

                // 🔹 Step 4: Commit transaction
                $connection->commit();
                
                // Redirect back to user_plans.php with a success message
                $_SESSION['success'] = "✅ Success: Plan saved successfully and preferences updated! See your new, calorie-correct plan below.";
                header("Location: user_dietPlan.php");
                exit;

            } catch (Exception $e) {
                // 🔸 Rollback if any query fails
                $connection->rollback();
                $error = "❌ Error: MySQL error during save operation. " . $e->getMessage();
                error_log($e->getMessage());
            }
        }
    }
}
?>

<main class="flex-1 overflow-y-auto p-8 bg-gray-50">
    <!-- Updated color: sky-400 -> emerald-400 -->
    <h1 class="text-3xl font-extrabold text-gray-800 mb-8 border-b-2 border-emerald-400 pb-2">Plan Generation</h1>

    <?php if($success): ?>
    <?php displayAlert($success, 'success'); ?>
    <?php endif; ?>

    <?php if($error): ?>
    <?php displayAlert($error, 'error'); ?>
    <?php endif; ?>

    <!-- Input Form -->
    <section class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <!-- Updated color: sky-700 -> emerald-700 -->
        <h2 class="text-2xl font-bold text-emerald-700 mb-6">Personalized Nutrition Plan Generator</h2>
        
        <?php if ($bmi > 0): ?>
            <p class="text-sm text-gray-500 mb-4">Your calculated BMI: <strong class="text-emerald-600"><?= $bmi ?></strong> kg/m². This metric is used for enhanced safety checks.</p>
        <?php endif; ?>
        
        <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Note: Value is set to the last selected/finalized value -->
            <div>
                <label class="block text-gray-700 font-medium">Activity Level</label>
                <!-- Updated color: focus:ring-sky-500 focus:border-sky-500 -> focus:ring-emerald-500 focus:border-emerald-500 -->
                <select name="activity" class="w-full border rounded-lg px-3 py-2 mt-1 focus:ring-emerald-500 focus:border-emerald-500" required>
                    <option value="light" <?= $finalActivity == 'light' ? 'selected' : '' ?>>Lightly Active</option>
                    <option value="moderate" <?= $finalActivity == 'moderate' ? 'selected' : '' ?>>Moderately Active</option>
                    <option value="active" <?= $finalActivity == 'active' ? 'selected' : '' ?>>Very Active</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Food Preference</label>
                <!-- Updated color: focus:ring-sky-500 focus:border-sky-500 -> focus:ring-emerald-500 focus:border-emerald-500 -->
                <select name="food" class="w-full border rounded-lg px-3 py-2 mt-1 focus:ring-emerald-500 focus:border-emerald-500" required>
                    <option value="veg" <?= $finalDietary == 'veg' ? 'selected' : '' ?>>Vegetarian</option>
                    <option value="nonveg" <?= $finalDietary == 'nonveg' ? 'selected' : '' ?>>Non-Vegetarian</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Meal Type</label>
                <!-- Updated color: focus:ring-sky-500 focus:border-sky-500 -> focus:ring-emerald-500 focus:border-emerald-500 -->
                <select name="meal_type" class="w-full border rounded-lg px-3 py-2 mt-1 focus:ring-emerald-500 focus:border-emerald-500" required>
                    <option value="3_meals" <?= $finalMealType == '3_meals' ? 'selected' : '' ?>>3 Meals (Breakfast, Lunch, Dinner)</option>
                    <option value="5_small" <?= $finalMealType == '5_small' ? 'selected' : '' ?>>5-6 Small Meals (Incl. Snacks)</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-gray-700 font-medium">Fitness Goal</label>
                <!-- Updated color: focus:ring-sky-500 focus:border-sky-500 -> focus:ring-emerald-500 focus:border-emerald-500 -->
                <select name="goal" class="w-full border rounded-lg px-3 py-2 mt-1 focus:ring-emerald-500 focus:border-emerald-500" required>
                    <option value="weight_loss" <?= $finalGoal == 'weight_loss' ? 'selected' : '' ?>>Weight Loss</option>
                    <option value="weight_gain" <?= $finalGoal == 'weight_gain' ? 'selected' : '' ?>>Weight Gain</option>
                    <option value="muscle_build" <?= $finalGoal == 'muscle_build' ? 'selected' : '' ?>>Muscle Building</option>
                    <option value="balanced" <?= $finalGoal == 'balanced' ? 'selected' : '' ?>>Balanced Diet</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <!-- Updated color: bg-sky-600 hover:bg-sky-700 -> bg-emerald-600 hover:bg-emerald-700 -->
                <button type="submit" name="generate_plan" class="w-full bg-emerald-600 text-white px-4 py-3 rounded-lg font-semibold hover:bg-emerald-700 transition-colors shadow-md">
                    📝 Generate Personalized Plan
                </button>
            </div>
        </form>
    </section>
    
    <!-- Display loaded plan and Save button -->
    <?php if(!empty($loadedPlan)): ?>
    <section class="bg-white rounded-xl shadow-lg p-6 mb-8 border-t-4 border-emerald-500">
        <!-- Updated color: sky-700 -> emerald-700 -->
        <h3 class="text-xl font-bold text-emerald-700 mb-4">7-Day Personalized Meal Plan (Quantities Dynamically Scaled)</h3>
        
        <!-- Save Form for the loaded plan -->
        <form method="post" class="mb-6" id="save-plan-form">
            <!-- Hidden fields to retain the final, possibly health-modified selections -->
            <input type="hidden" name="goal" value="<?= htmlspecialchars($finalGoal) ?>">
            <input type="hidden" name="food" value="<?= htmlspecialchars($finalDietary) ?>">
            <input type="hidden" name="activity" value="<?= htmlspecialchars($finalActivity) ?>">
            <input type="hidden" name="meal_type" value="<?= htmlspecialchars($finalMealType) ?>">
            
            <!-- Hidden plan data (Will be populated by JS with the SCALED data) -->
            <!-- The $loadedPlan contains the BASE data for JS to scale -->
            <input type="hidden" id="js-base-plan-data" value='<?= htmlspecialchars(json_encode($loadedPlan), ENT_QUOTES, 'UTF-8') ?>'>
            <!-- Placeholder for scaled data to be sent to PHP for saving -->
            <input type="hidden" id="js-scaled-plan-data" name="plan" value=''> 
            <!-- Pass the daily target and base calories to JS -->
            <input type="hidden" id="js-daily-target" value="<?= $dailyTarget ?>">
            <input type="hidden" id="js-base-avg-cal" value="<?= $baseTotalCalories ?>">


            <!-- Save button - Disabled until scaling runs -->
            <button type="submit" name="save_plan" id="save-plan-btn" disabled
                    class="w-full bg-emerald-400 text-white px-4 py-3 rounded-lg font-semibold transition-colors shadow-xl disabled:opacity-50 disabled:cursor-not-allowed">
                💾 Save This Plan to My Profile
            </button>
        </form>

        <!-- Container for the calorie summary calculated by PHP/generator -->
        <div id="plan-summary-container">
            <div class="bg-blue-50 border-blue-200 text-blue-700 px-4 py-3 rounded-lg mb-6 shadow-sm">
                <h4 class="font-bold mb-1">Target Summary</h4>
                <p class="text-sm">
                    **Daily Target:** <span id="daily-target-display" class="font-extrabold text-blue-800"><?= $dailyTarget ?> kcal</span><br>
                    **Base Plan Avg:** <span id="base-plan-avg-display"><?= $baseTotalCalories ?> kcal</span> (The plan's starting average before scaling)
                </p>
                <p class="text-xs mt-2 font-semibold">Scaling Factor Applied: <span id="scaling-factor-display" class="text-red-600">Calculating...</span></p>
                <p class="text-xs font-semibold">Final Scaled Plan Avg: <span id="scaled-plan-avg-display" class="text-blue-800">...</span></p>
            </div>
        </div>
        
        <div id="plan-display">
            <?php foreach($loadedPlan as $day): 
                // Display the BASE calculated daily totals as placeholders
                $dayCal = round($day['Cal'] ?? '--');
                $dayP = round($day['P'] ?? '--');
                $dayC = round($day['C'] ?? '--');
                $dayF = round($day['F'] ?? '--');
            ?>
            <!-- Day Container -->
            <div class="mb-8 border border-gray-200 rounded-lg overflow-hidden shadow-md day-container" data-day-number="<?= $day['day_number'] ?>">
                <!-- Updated color: bg-emerald-500, bg-emerald-600 -->
                <div class="bg-emerald-500 text-white p-4 font-bold text-lg flex justify-between items-center">
                    <span>Day <?= $day['day_number'] ?></span>
                    <span class="text-sm bg-emerald-600 py-1 px-3 rounded-full">Total: <span class="day-calorie-total"><?= $dayCal ?></span> kcal</span> 
                </div>
                <!-- Display generator's macro totals -->
                <div class="bg-emerald-100 p-3 text-sm font-semibold text-gray-700 flex justify-around">
                    <span>Protein: <span class="day-protein-total"><?= $dayP ?></span>g</span>
                    <span>Carbs: <span class="day-carbs-total"><?= $dayC ?></span>g</span>
                    <span>Fat: <span class="day-fat-total"><?= $dayF ?></span>g</span>
                </div>
                <ul class="divide-y divide-gray-100">
                    <?php foreach($day['meals'] as $index => $meal): ?>
                        <li class="p-4 flex justify-between items-start hover:bg-gray-50 transition-colors meal-item" 
                            data-meal-index="<?= $index ?>" 
                            data-meal-time="<?= $meal['meal_time'] ?>"
                            data-base-protein="<?= $meal['protein'] ?>"
                            data-base-carbs="<?= $meal['carbs'] ?>"
                            data-base-fat="<?= $meal['fat'] ?>"
                            data-base-calories="<?= $meal['calories'] ?>"
                            data-base-grams="<?= $meal['base_quantity_grams'] ?>"> <!-- CRITICAL: This is the numeric grams used for calculation -->

                            <div class="flex-1 pr-4">
                                <!-- Updated color: text-emerald-600 -->
                                <p class="text-xs font-semibold uppercase text-emerald-600 mb-1"><?= ucwords(str_replace('_', ' ', $meal['meal_time'])) ?></p>
                                <p class="text-gray-900 font-medium meal-text"><?= $meal['meal_text'] ?></p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="font-bold text-lg text-emerald-700 mb-1">
                                    <!-- Displays the initial descriptive quantity (e.g., "1 plate") -->
                                    <span class="meal-quantity-display"><?= $meal['quantity'] ?></span> 
                                </p>
                                <p class="text-xs text-gray-500">
                                    <span class="meal-calorie-display"><?= $meal['calories'] ?></span> kcal
                                </p>
                                <p class="text-xs text-gray-400">
                                    (<span class="meal-macro-display">P<?= $meal['protein'] ?> C<?= $meal['carbs'] ?> F<?= $meal['fat'] ?></span>)
                                </p>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</main>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const basePlanInput = document.getElementById('js-base-plan-data');
    const scaledPlanInput = document.getElementById('js-scaled-plan-data');
    const dailyTargetInput = document.getElementById('js-daily-target');
    const baseAvgCalInput = document.getElementById('js-base-avg-cal');
    const saveButton = document.getElementById('save-plan-btn');

    if (!basePlanInput || !scaledPlanInput || !dailyTargetInput || !baseAvgCalInput) {
        // Only proceed if all necessary data inputs exist (i.e., a plan was successfully loaded)
        return; 
    }

    // --- 1. Get Base Data and Targets ---
    const dailyTarget = parseFloat(dailyTargetInput.value);
    const baseAvgCal = parseFloat(baseAvgCalInput.value);
    let basePlanData = [];

    try {
        // Decode the plan data which was htmlspecialchars'd in PHP
        const rawJson = basePlanInput.value;
        basePlanData = JSON.parse(rawJson);
    } catch (e) {
        console.error("Error parsing base plan JSON:", e);
        return;
    }

    if (dailyTarget <= 0 || baseAvgCal <= 0 || basePlanData.length === 0) {
        console.warn("Missing necessary values for scaling. Skipping scaling.");
        return;
    }

    // --- 2. Calculate Scaling Factor ---
    // This factor adjusts the entire 7-day plan's average calories to match the target.
    const scalingFactor = dailyTarget / baseAvgCal;
    document.getElementById('scaling-factor-display').textContent = scalingFactor.toFixed(3);

    let totalScaledCalSum = 0; // For calculating the final average

    // --- 3. Iterate and Scale the Plan Data and UI ---
    
    // We create a deep copy of the base data to store the scaled results
    const scaledPlanData = JSON.parse(JSON.stringify(basePlanData)); 
    
    // Iterate through each day container in the UI
    document.querySelectorAll('.day-container').forEach(dayContainer => {
        const dayNum = parseInt(dayContainer.getAttribute('data-day-number'));
        const dayIndex = dayNum - 1; // 0-indexed array
        
        // Accumulators for the scaled daily totals
        let scaledDayCal = 0;
        let scaledDayP = 0;
        let scaledDayC = 0;
        let scaledDayF = 0;

        // Iterate through each meal item in the day
        dayContainer.querySelectorAll('.meal-item').forEach(mealItem => {
            const mealIndex = parseInt(mealItem.getAttribute('data-meal-index'));

            // Get base data from attributes
            const baseGrams = parseFloat(mealItem.getAttribute('data-base-grams'));
            const baseCal = parseFloat(mealItem.getAttribute('data-base-calories'));
            const baseP = parseFloat(mealItem.getAttribute('data-base-protein'));
            const baseC = parseFloat(mealItem.getAttribute('data-base-carbs'));
            const baseF = parseFloat(mealItem.getAttribute('data-base-fat'));
            
            // Skip meals with 0 calories or grams (e.g., placeholder "SKIP" meals)
            if (baseGrams === 0 || baseCal === 0) return; 

            // --- Scaling Calculation ---
            // 1. Scale the numeric quantity (grams)
            const scaledGrams = Math.round(baseGrams * scalingFactor);
            
            // 2. Scale the macros and calories proportionally
            // Note: We use Math.round for displayable integers, but keep precise totals
            const scaledCalories = Math.round(baseCal * scalingFactor);
            const scaledProtein = Math.round(baseP * scalingFactor);
            const scaledCarbs = Math.round(baseC * scalingFactor);
            const scaledFat = Math.round(baseF * scalingFactor);

            // Accumulate daily totals
            scaledDayCal += scaledCalories;
            scaledDayP += scaledProtein;
            scaledDayC += scaledCarbs;
            scaledDayF += scaledFat;
            
            // --- Update UI for the Meal ---
            mealItem.querySelector('.meal-quantity-display').textContent = `${scaledGrams}g`; // Display as scaled grams
            mealItem.querySelector('.meal-calorie-display').textContent = scaledCalories;
            mealItem.querySelector('.meal-macro-display').textContent = `P${scaledProtein} C${scaledCarbs} F${scaledFat}`;
            
            // --- Update scaledPlanData for saving ---
            if (scaledPlanData[dayIndex] && scaledPlanData[dayIndex].meals[mealIndex]) {
                const mealToUpdate = scaledPlanData[dayIndex].meals[mealIndex];
                
                // Store the scaled values
                mealToUpdate.quantity = `${scaledGrams}g`; // Final quantity string for saving
                mealToUpdate.protein = scaledProtein;
                mealToUpdate.carbs = scaledCarbs;
                mealToUpdate.fat = scaledFat;
                mealToUpdate.calories = scaledCalories;
                
                // These base values are no longer needed for saving but we can keep them for debug
                delete mealToUpdate.base_quantity_grams; 
                delete mealToUpdate.unit;
                delete mealToUpdate.protein_per_unit;
                delete mealToUpdate.carbs_per_unit;
                delete mealToUpdate.fat_per_unit;
            }
        });

        // --- Update UI for the Day Totals ---
        dayContainer.querySelector('.day-calorie-total').textContent = Math.round(scaledDayCal);
        dayContainer.querySelector('.day-protein-total').textContent = Math.round(scaledDayP);
        dayContainer.querySelector('.day-carbs-total').textContent = Math.round(scaledDayC);
        dayContainer.querySelector('.day-fat-total').textContent = Math.round(scaledDayF);

        // Update scaledPlanData day totals
        scaledPlanData[dayIndex].P = scaledDayP;
        scaledPlanData[dayIndex].C = scaledDayC;
        scaledPlanData[dayIndex].F = scaledDayF;
        scaledPlanData[dayIndex].Cal = scaledDayCal;
        
        totalScaledCalSum += scaledDayCal;
    });

    // --- 4. Final Summary Update ---
    const finalScaledAvg = Math.round(totalScaledCalSum / 7);
    document.getElementById('scaled-plan-avg-display').textContent = `${finalScaledAvg} kcal`;
    
    // Set the hidden input value for PHP to save the scaled data
    scaledPlanInput.value = JSON.stringify(scaledPlanData);
    
    // Enable the save button now that the plan is calculated and saved data is prepared
    saveButton.disabled = false;
});
</script>

