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
    // Using Emerald colors for success state and Red for error
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
    // Standardized error message
    $error = "User record not found! Please <strong>check your account data</strong>.";
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
        // Standardized error message
        $error = "Please <strong>select all options</strong> to generate a plan.";
        $canProceed = false;
    } elseif ($weight <= 0 || $height <= 0 || $age <= 0) {
        // Standardized error message
        $error = "Please update your <strong>weight, height, and age</strong> in your profile to generate a personalized plan.";
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
                // Standardized error message (using <strong>)
                $error = "Health Safety Alert: Your current BMI ({$bmi}) indicates a high-risk category. The selected goal ({$currentGoalDisplay}) is discouraged. Please select <strong>Weight Loss or Balanced Diet</strong>.";
                $canProceed = false;
            }
        }
        
        // --- Health/Goal Conflict Check 2 & Health Focus Determination ---
        if ($canProceed) {
            
            // Diabetes / PCOS / PCOD: BLOCK weight gain/muscle build, SET low_carb filter
            if (preg_match('/\b(diabetes|diabetic|pcod|pcos)\b/i', $health_issues)) {
                
                if ($currentGoal === 'weight_gain' || $currentGoal === 'muscle_build') {
                    // Standardized error message (using <strong>)
                    $error = "Health Safety Alert: Your goal ({$currentGoalDisplay}) is not recommended due to your health profile. Please select <strong>Weight Loss or Balanced Diet</strong>.";
                    $canProceed = false;
                }
                
                if ($canProceed) {
                    $finalHealthFocus = "low_carb"; // Set focus for SQL query
                    // Standardized health warning (using <strong>)
                    $healthWarning = "A specialized <strong>Low-Carb</strong> plan has been prepared to support optimal blood sugar control.";
                }
            } 
            
            // Heart Disease: BLOCK weight gain/muscle build, SET high_fiber filter
            elseif (preg_match('/\b(heart|cardiac|hypertension|cholesterol)\b/i', $health_issues)) {
                
                if ($currentGoal === 'weight_gain' || $currentGoal === 'muscle_build') {
                    // Standardized error message (using <strong>)
                    $error = "Health Safety Alert: Your goal ({$currentGoalDisplay}) is not recommended due to your health profile. Please select <strong>Weight Loss or Balanced Diet</strong>.";
                    $canProceed = false;
                }
                
                if ($canProceed) {
                    $finalHealthFocus = "high_fiber"; // Set focus for SQL query
                    // Standardized health warning (using <strong>)
                    $healthWarning = "A <strong>Heart-Healthy High-Fiber</strong> plan has been prepared. Please ensure your food choices are low in sodium and saturated fats.";
                }
            }
            
            // Obesity: BLOCK weight gain, SET high_fiber filter
            elseif (preg_match('/\b(obesity|overweight|high\s?bmi)\b/i', $health_issues) || $bmi >= 30) {
                
                if ($currentGoal === 'weight_gain') {
                    // Standardized error message (using <strong>)
                    $error = "Health Safety Alert: Your selected goal (Weight Gain) is not recommended. Please select <strong>Weight Loss or Balanced Diet</strong>.";
                    $canProceed = false;
                }
                
                if ($canProceed) {
                    $finalHealthFocus = "high_fiber"; // Set focus for SQL query
                    // Standardized health warning (using <strong>)
                    $healthWarning = "A <strong>High-Fiber</strong> plan has been prepared to support effective weight management.";
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
                 // Standardized fallback warning (using <br> and <strong>)
                 $healthWarning .= " <br><strong>NOTE: A highly specialized plan was unavailable. We have provided a general plan based on your goal.</strong>";
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
                $baseProtein = floatval($mealRow['protein']);
                $baseCarbs   = floatval($mealRow['carbs']);
                $baseFat     = floatval($mealRow['fat']);
                $baseCalories= floatval($mealRow['calories']);
                
                // CRITICAL FIX: Use the numeric 'base_quantity' (grams) for dynamic scaling, and 'quantity' for display text.
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
                    'base_quantity_grams' => $baseQuantityInGrams, // Numeric grams for scaling (e.g., 160)
                    'unit'          => $mealRow['unit'],          
                    
                    // Store the Goal-Adjusted BASE macros/calories directly from the DB
                    'protein'       => $baseProtein, 
                    'carbs'         => $baseCarbs,
                    'fat'           => $baseFat,
                    'calories'      => $baseCalories,
                    'quantity_string' => $displayQuantityString, // Descriptive text (e.g., "2 parathas + 1/2 cup curd")
                    'quantity'      => $displayQuantityString, // Used for initial UI display (will be overwritten by JS)
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
                 // Standardized error message
                 $error = "The plan generation failed: We could only find " . count($loadedPlan) . " out of 7 required days. Please <strong>try a different combination</strong> or contact support.";
                 $loadedPlan = []; // Clear partial data
            } else {
                
                // Set final parameters for saving and hidden fields
                $finalGoal = $currentGoal;
                $finalDietary = $inputDietary;
                $finalActivity = $currentActivity;
                $finalMealType = $currentMealType;

                $goalName = ucwords(str_replace('_', ' ', $finalGoal));
                // Standardized success message (no dynamic scaling mention)
                $success = "Your personalized <strong>{$dailyTarget} kcal</strong> plan has been successfully loaded." . ($healthWarning ? " <br>{$healthWarning}" : "");
                $showSaveButton = true;
            }
            
        } else {
             // Standardized error message (removed DB details)
             $error = "We could not find a complete plan for the selected options (Goal: {$currentGoalDisplay}, Diet: {$currentDietary}). Please <strong>try a different combination</strong> or contact support.";
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
        // Standardized error message
        $error = "Plan data was lost during submission. Please <strong>regenerate and try again</strong>.";
    } else {
        // Decode the JSON plan data passed from the hidden field (THIS IS THE SCALED DATA FROM JS)
        $plan = json_decode(html_entity_decode($_POST['plan']), true);

        if (!$plan || !is_array($plan)) {
            // Standardized error message
            $error = "<strong>Invalid plan structure</strong> or data loss during save. Please try regenerating the plan.";
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
                $insertStmt = $connection->prepare("
    INSERT INTO user_diet_plans 
    (user_id, day_number, meal_time, meal_text, quantity, `portion`, protein, carbs, fat, calories)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
                if (!$insertStmt) { throw new Exception("Prepare failed (INSERT): " . $connection->error); }

                foreach ($plan as $day) {
                    $day_number = intval($day['day_number']);
                    foreach ($day['meals'] as $meal) {
                        $meal_time  = trim($meal['meal_time'] ?? '');
                        $meal_text  = trim($meal['meal_text'] ?? '');
                        
                        // Only save non-SKIP meals.
                        if (trim(strtoupper($meal_text)) === 'SKIP') continue; 
                        
                        // 🔥 CRITICAL FIX: Extracting the separate numerical and descriptive values from the JS payload
                        
                        // 1. Scaled Numerical Weight (e.g., 160) -> user_diet_plans.quantity
                        // This is the numerical, SCALED value based on calories and base_quantity.
                        $scaled_numerical_quantity = trim($meal['scaled_grams_only'] ?? '');
                        // 2. Descriptive Text (e.g., "2 parathas + 1/2 cup curd") -> user_diet_plans.portion
                        // This is the exact descriptive text from diet_plans.quantity.
                        $descriptive_portion = $meal['quantity'] ; 

                        $protein    = intval($meal['protein'] ?? 0);
                        $carbs      = intval($meal['carbs'] ?? 0);
                        $fat        = intval($meal['fat'] ?? 0);
                        $calories   = intval($meal['calories'] ?? 0);

                        if (empty($meal_time) || empty($meal_text)) continue;

                        // CRITICAL: The bind_param must include 10 types (iisssiiiii) for 10 fields
                        $insertStmt->bind_param(
                            "iissssiiii", 
                            $user_id,
                            $day_number,
                            $meal_time,
                            $meal_text,
                            $scaled_numerical_quantity, // Binds to user_diet_plans.quantity (NUMERIC GRAMS)
                            $descriptive_portion,       // Binds to user_diet_plans.portion (DESCRIPTIVE TEXT)
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
                // Standardized redirect success message
                $_SESSION['success'] = "Plan saved successfully! Your preferences have been updated.";
                header("Location: user_dietPlan.php");
                exit;

            } catch (Exception $e) {
                // 🔸 Rollback if any query fails
                $connection->rollback();
                // Standardized error message (using <strong>)
                $error = "An unexpected error occurred while saving your plan. Please try again. (Details: <strong>" . $e->getMessage() . "</strong>)";
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
               <!-- Container for the calorie summary calculated by PHP/generator -->
        <div id="plan-summary-container" class="mt-6">
            <div class="bg-white p-6 border-l-4 border-emerald-500 rounded-lg shadow-xl">
                <h4 class="text-lg font-bold text-gray-800 mb-4">Your Personalized Calorie Goal</h4>
                
                <div class="flex flex-col sm:flex-row justify-between space-y-3 sm:space-y-0 sm:space-x-6">
                    
                    <!-- 1. Daily Target (The PHP calculated minimum/safe target) -->
                    <div class="p-3 bg-emerald-50 rounded-lg flex-1">
                        <p class="text-sm font-semibold text-gray-600">Daily Target (TDEE Adjusted)</p>
                        <p class="text-3xl font-extrabold text-emerald-800 mt-1">
                            <span id="daily-target-display"><?= $dailyTarget ?></span> kcal
                        </p>
                    </div>

                    <!-- 2. Final Scaled Plan Avg (Updated by JS after scaling the meal plan) -->
                    <!-- This will show the final average calories of the 7-day plan after quantity adjustments -->
                    <div class="p-3 bg-blue-50 rounded-lg flex-1">
                        <p class="text-sm font-semibold text-gray-600">Plan Average </p>
                        <p class="text-3xl font-extrabold text-blue-800 mt-1">
                            <span id="scaled-plan-avg-display">0</span> kcal
                        </p>
                    </div>
                    
                    <!-- 3. Base Plan Avg (The average of the plan fetched from the database)
                    <div class="p-3 bg-red-50 rounded-lg flex-1">
                        <p class="text-sm font-semibold text-gray-600">Base Plan Average</p>
                        <p class="text-3xl font-extrabold text-red-800 mt-1">
                            <span id="base-plan-avg-display"><?= $baseTotalCalories ?></span> kcal
                        </p>
                    </div> -->
                </div>
            </div>
        </div>

        <!-- Plan Display Table -->
        <div class="mt-8 overflow-x-auto">
            <table class="min-w-full bg-white rounded-lg shadow-xl border border-gray-200">
                <thead class="bg-gray-100 border-b border-gray-300">
                    <tr>
                        <th class="py-3 px-6 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Day / Meal</th>
                        <th class="py-3 px-6 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Food Item</th>
                        <th class="py-3 px-6 text-left text-xs font-bold text-gray-600 uppercase tracking-wider">Portion & Weight</th>
                        <th class="py-3 px-6 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">P</th>
                        <th class="py-3 px-6 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">C</th>
                        <th class="py-3 px-6 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">F</th>
                        <th class="py-3 px-6 text-right text-xs font-bold text-gray-600 uppercase tracking-wider">Kcal</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($loadedPlan as $dayIndex => $day): ?>
                    <?php $dayName = "Day " . ($dayIndex + 1); ?>
                    
                    <tr class="bg-emerald-50 border-b border-gray-200">
                        <td colspan="7" class="py-2 px-6 font-extrabold text-lg text-emerald-800">
                            <?= $dayName ?> 
                            <!-- Display for daily totals - updated by JS -->
                            <span class="ml-4 text-sm font-semibold text-gray-600 daily-cal-total" data-day="<?= $dayIndex + 1 ?>">
                                (Total Kcal: <span class="daily-cal-value">0</span>)
                            </span>
                        </td>
                    </tr>

                    <?php foreach ($day['meals'] as $mealIndex => $meal): 
                        // Skip meals are filtered out when saving but displayed here
                        if (trim(strtoupper($meal['meal_text'])) === 'SKIP') {
                             $meal['calories'] = $meal['protein'] = $meal['carbs'] = $meal['fat'] = 0; // Ensure skipped meals show 0 macros/calories
                        }
                    ?>
                    <tr class="hover:bg-gray-50 border-b border-gray-100 meal-row" data-day="<?= $dayIndex + 1 ?>" data-meal-index="<?= $mealIndex ?>">
                        <td class="py-3 px-6 text-sm font-semibold text-gray-700">
                            <?= ucwords(str_replace(['snack1', 'snack2', 'snack3'], ['Snack 1', 'Snack 2', 'Snack 3'], $meal['meal_time'])) ?>
                        </td>
                        <td class="py-3 px-6 text-sm text-gray-900 font-medium meal-name"><?= $meal['meal_text'] ?></td>
                        
                        <td class="py-3 px-6 text-sm text-gray-700">
                            <!-- 
                                CRITICAL UPDATE: 
                                1. 'meal-portion-text' holds the descriptive text (saved to 'portion' column).
                                2. 'scaled-quantity-grams' holds the numerical weight (saved to 'quantity' column).
                            -->
                            <p class="text-sm text-gray-600 mt-2">
                                <span class="font-medium">Portion:</span> <span class="meal-portion-text font-semibold text-emerald-800 mr-4"><?= $meal['quantity_string'] ?></span>
                                <!-- Initial value is the base quantity. JS will overwrite this with the scaled amount. -->
                                <span class="font-medium ml-2">Weight:</span> 
                                <span class="scaled-quantity-grams font-bold text-blue-800" 
                                      data-base-grams="<?= $meal['base_quantity_grams'] ?>" 
                                      data-unit="<?= $meal['unit'] ?>">
                                    <?= $meal['base_quantity_grams'] ?> <?= $meal['unit'] ?>
                                </span>
                            </p>
                        </td>

                        <!-- Macros and Calories (Initial values are BASE, JS will update these) -->
                        <td class="py-3 px-6 text-sm text-right font-medium text-gray-700 macro-P" data-base-p="<?= $meal['protein'] ?>" data-unit-p="<?= $meal['protein_per_unit'] ?>">
                            <?= $meal['protein'] ?> g
                        </td>
                        <td class="py-3 px-6 text-sm text-right font-medium text-gray-700 macro-C" data-base-c="<?= $meal['carbs'] ?>" data-unit-c="<?= $meal['carbs_per_unit'] ?>">
                            <?= $meal['carbs'] ?> g
                        </td>
                        <td class="py-3 px-6 text-sm text-right font-medium text-gray-700 macro-F" data-base-f="<?= $meal['fat'] ?>" data-unit-f="<?= $meal['fat_per_unit'] ?>">
                            <?= $meal['fat'] ?> g
                        </td>
                        <td class="py-3 px-6 text-sm text-right font-extrabold text-emerald-700 meal-cal" data-base-cal="<?= $meal['calories'] ?>" data-unit-cal="<?= $meal['calories_per_unit'] ?>">
                            <?= $meal['calories'] ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    </section>
    <?php endif; ?>

</main>

<!-- JS for Dynamic Scaling -->
<!-- The scaling logic is implemented in JS to ensure the user sees the real-time adjustments before saving. -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const basePlanDataEl = document.getElementById('js-base-plan-data');
        const targetCalEl = document.getElementById('js-daily-target');
        const baseAvgCalEl = document.getElementById('js-base-avg-cal');
        const saveBtn = document.getElementById('save-plan-btn');
        const scaledPlanInput = document.getElementById('js-scaled-plan-data');
        const scaledAvgDisplay = document.getElementById('scaled-plan-avg-display');
        const dailyCalDisplays = document.querySelectorAll('.daily-cal-total');

        if (!basePlanDataEl || !targetCalEl || !baseAvgCalEl) {
            console.error("Missing essential plan data elements.");
            return;
        }

        const basePlan = JSON.parse(basePlanDataEl.value || '[]');
        const dailyTarget = parseFloat(targetCalEl.value);
        const baseAvgCalories = parseFloat(baseAvgCalEl.value);

        if (basePlan.length === 0 || isNaN(dailyTarget) || dailyTarget <= 0 || isNaN(baseAvgCalories) || baseAvgCalories <= 0) {
            console.error("Base plan data is invalid or empty. Cannot scale.");
            // Still enable save if the button was rendered, allowing non-scaled save if PHP allowed it.
            if (saveBtn) saveBtn.disabled = false;
            return;
        }

        // Calculate the scaling factor based on the average calories of the base plan vs. the target calories.
        const scalingFactor = dailyTarget / baseAvgCalories;
        const totalScaledCalories = []; // To calculate the new 7-day average

        console.log(`Target: ${dailyTarget} kcal, Base Avg: ${baseAvgCalories} kcal. Factor: ${scalingFactor.toFixed(2)}`);

        /**
         * Scales a numerical value based on the scaling factor.
         * @param {number} value The base macro/calorie value.
         * @returns {number} The scaled, rounded value.
         */
        function scaleValue(value) {
            return Math.round(value * scalingFactor);
        }

        // Iterate through the DOM to apply changes and build the final scaled plan data
        basePlan.forEach((day, dayIndex) => {
            let dayTotalCalories = 0;
            const dayNum = day.day_number;

            day.meals.forEach((meal, mealIndex) => {
                const mealSelector = `.meal-row[data-day="${dayNum}"][data-meal-index="${mealIndex}"]`;
                const mealEl = document.querySelector(mealSelector);
                
                // For 'SKIP' meals, only update the basePlan object and totals, don't try to update DOM
                if (!mealEl || meal.meal_text.toUpperCase().trim() === 'SKIP') {
                    // Update macros/calories to 0 in the JS object for saving (already done in PHP for display)
                    meal.protein = 0;
                    meal.carbs = 0;
                    meal.fat = 0;
                    meal.calories = 0; 
                    meal.scaled_grams_only = 0; // Set scaled quantity to 0 for skipped meals
                    totalScaledCalories.push(0); 
                    return; 
                }
                
                // 1. Calculate Scaled Values
                meal.protein = scaleValue(meal.protein);
                meal.carbs = scaleValue(meal.carbs);
                meal.fat = scaleValue(meal.fat);
                meal.calories = scaleValue(meal.calories); // The final scaled calorie value
                
                // 2. Calculate Scaled Quantity (Numerical Weight/Grams)
                const baseGrams = parseFloat(meal.base_quantity_grams);
                
                // CRITICAL: The scaled numerical weight (in grams/ml)
                const scaledGramsOnly = scaleValue(baseGrams); 
                
                // Store the numerical value in the plan object for saving to the 'quantity' column
                meal.scaled_grams_only = scaledGramsOnly; 
                
                dayTotalCalories += meal.calories;

                // 3. Update DOM Elements
                
                // Update Macros/Calories
                mealEl.querySelector('.macro-P').textContent = `${meal.protein} g`;
                mealEl.querySelector('.macro-C').textContent = `${meal.carbs} g`;
                mealEl.querySelector('.macro-F').textContent = `${meal.fat} g`;
                mealEl.querySelector('.meal-cal').textContent = meal.calories;
                
                // Update Scaled Weight Display (The numerical part)
                const scaledWeightEl = mealEl.querySelector('.scaled-quantity-grams');
                if (scaledWeightEl) {
                    scaledWeightEl.textContent = `${scaledGramsOnly} ${meal.unit}`;
                }
                // Note: .meal-portion-text remains unchanged as requested.

                // Add the scaled calorie value to the array for final average calculation
                totalScaledCalories.push(meal.calories);
            });
            
            // Update Daily Total Display
            const dailyCalEl = document.querySelector(`.daily-cal-total[data-day="${dayNum}"] .daily-cal-value`);
            if (dailyCalEl) {
                dailyCalEl.textContent = dayTotalCalories;
            }
        });

        // 4. Calculate and Display Final 7-Day Average
        // Recalculate average based on the 7 daily totals (the number of days)
        const totalScaledDailySum = basePlan.map(day => day.meals.reduce((sum, meal) => sum + meal.calories, 0)).reduce((a, b) => a + b, 0);
        const finalAvgCalories = Math.round(totalScaledDailySum / basePlan.length);
        
        if (scaledAvgDisplay) {
            scaledAvgDisplay.textContent = finalAvgCalories;
        }

        // 5. Finalize the Scaled Plan for Submission
        // The original plan object in JS is now the SCALED plan.
        scaledPlanInput.value = JSON.stringify(basePlan);

        // 6. Enable Save Button
        if (saveBtn) {
            saveBtn.disabled = false;
        }
        
        console.log(`Scaled Plan Avg: ${finalAvgCalories} kcal. Ready to save.`);
    });
</script>
</body>
</html>