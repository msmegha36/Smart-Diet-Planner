<?php 

session_start();
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

// --- Define Goal-Specific Macro Ratios (P:C:F as percentage of calories) ---
// This ensures that for a 2000 kcal Weight Loss plan, the protein percentage 
// is higher than a Balanced plan, promoting better results.
const MACRO_RATIOS = [
    'muscle_build' => ['P' => 0.40, 'C' => 0.40, 'F' => 0.20], // 40% P, 40% C, 20% F
    'weight_loss'  => ['P' => 0.35, 'C' => 0.45, 'F' => 0.20], // 35% P, 45% C, 20% F
    'weight_gain'  => ['P' => 0.30, 'C' => 0.50, 'F' => 0.20], // 30% P, 50% C, 20% F
    'balanced'     => ['P' => 0.25, 'C' => 0.50, 'F' => 0.25], // 25% P, 50% C, 25% F
];
// Macro Calorie Conversions (Kcal per gram)
const CALORIES_PER_GRAM = ['P' => 4, 'C' => 4, 'F' => 9];

// --- Fetch user details ---
$userRes = $connection->prepare("SELECT * FROM reg WHERE id = ?");
$userRes->bind_param("i", $user_id);
$userRes->execute();
$user = $userRes->get_result()->fetch_assoc();
$userRes->close();

if (!$user) {
    $error = "User record not found!";
} else {
    // Ensure all necessary user data is available
    $weight = floatval($user['weight'] ?? 0);
    $height = floatval($user['height'] ?? 0);
    $age = intval($user['age'] ?? 0);
    $gender = strtolower($user['gender'] ?? 'female');
    // Normalize health issues for robust checking
    $health_issues = strtolower(trim($user['health_issues'] ?? ''));
}

// Initialize variables to store the final plan parameters used
$finalGoal = $_POST['goal'] ?? $user['goal'] ?? '';
$finalDietary = $_POST['food'] ?? $user['dietary'] ?? '';
$finalActivity = $_POST['activity'] ?? $user['activity'] ?? '';
$finalMealType = $_POST['meal_type'] ?? $user['meal_type'] ?? '';

// ================== HANDLE PLAN GENERATION ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_plan'])) {
    $inputGoal      = $_POST['goal'] ?? '';
    $inputDietary   = $_POST['food'] ?? '';
    $inputActivity  = $_POST['activity'] ?? '';
    $inputMealType  = $_POST['meal_type'] ?? '';
    $canProceed     = true; // Flag to control execution flow

    // --- Initial Validation ---
    if (empty($inputGoal) || empty($inputDietary) || empty($inputActivity) || empty($inputMealType)) {
        $error = "Please select all options to generate a plan.";
        $canProceed = false;
    } elseif ($weight <= 0 || $height <= 0 || $age <= 0) {
        $error = "Please update your weight, height, and age in your profile to generate a personalized plan.";
        $canProceed = false;
    } 
    
    if ($canProceed) {
        // --- 1: TDEE Calculation ---
        // Harris-Benedict (Revised) or similar BMR formula
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

        // --- 2: Health-Aware Filtering and Goal Modification ---
        $currentGoal      = $inputGoal;
        $currentDietary   = $inputDietary;
        $currentActivity  = $inputActivity;
        $currentMealType  = $inputMealType;
        
        $specialFilter = "";
        $healthWarning = "";
        
        $currentGoalDisplay = ucwords(str_replace('_', ' ', $currentGoal));
        
        // Use more robust checks with word boundaries (\b) and case-insensitivity (i)
        
        // Diabetes / PCOS / PCOD: BLOCK weight gain/muscle build
        if (preg_match('/\b(diabetes|diabetic|pcod|pcos)\b/i', $health_issues)) {
            
            if ($currentGoal === 'weight_gain' || $currentGoal === 'muscle_build') {
                $error = "🚫 **Health Safety Alert:** Your selected goal (**{$currentGoalDisplay}**) is highly discouraged for users with Diabetes/PCOS/PCOD. Please select **Weight Loss** or **Balanced Diet**.";
                $canProceed = false;
            }
            
            if ($canProceed) {
                $specialFilter .= " AND low_carb=1 AND low_glycemic=1";
                $healthWarning = "A specialized **Low-Carb, Low-Glycemic** filter has been applied to your plan for optimal blood sugar control.";
            }
        } 
        
        // Heart Disease: BLOCK weight gain/muscle build
        elseif (preg_match('/\b(heart|cardiac|hypertension|cholesterol)\b/i', $health_issues)) {
            
            if ($currentGoal === 'weight_gain' || $currentGoal === 'muscle_build') {
                $error = "🚫 **Health Safety Alert:** Your selected goal (**{$currentGoalDisplay}**) is unsafe for users with Heart Disease. Please select **Weight Loss** or **Balanced Diet**.";
                $canProceed = false;
            }
            
            if ($canProceed) {
                $specialFilter .= " AND high_fiber=1 AND low_glycemic=1";
                $healthWarning = "A **Heart-Healthy High-Fiber/Low-GI** filter has been applied. Please ensure your food choices are low in sodium and saturated fats.";
            }
        }
        
        // Obesity: BLOCK weight gain
        elseif (preg_match('/\b(obesity|overweight|high\s?bmi)\b/i', $health_issues)) {
            
            if ($currentGoal === 'weight_gain') {
                $error = "🚫 **Health Safety Alert:** Your selected goal (**Weight Gain**) is unsafe for users with Obesity. Please select **Weight Loss** or **Balanced Diet**.";
                $canProceed = false;
            }
            
            if ($canProceed) {
                $specialFilter .= " AND high_fiber=1";
                $healthWarning = "A **High-Fiber** filter has been applied to support effective weight management.";
            }
        }
    }

    if ($canProceed) {
        // --- 3: Calculate Final Target Calories ---
        switch ($currentGoal) {
            case 'weight_loss': $targetCalories = $tdee - 500; break;
            case 'weight_gain': $targetCalories = $tdee + 500; break;
            case 'muscle_build': $targetCalories = $tdee + 300; break;
            default: $targetCalories = $tdee;
        }

        // Ensure target calories is safe minimum (This is the crucial number for scaling)
        $dailyTarget = max(($gender === 'male' ? 1500 : 1200), round($targetCalories));
        $totalTargetCalories = $dailyTarget * 7;
        $finalDailyTarget = $dailyTarget; // Initialize the final average target

        // --- 4: Two-Pass Plan Fetching (Attempt 1: Filtered, Attempt 2: Fallback) ---
        
        $loadedPlan = [];
        $originalPlan = []; 
        $planFound = false;
        $isFallback = false;
        
        // 💡 FIX 1: Meal ID Tracker to prevent repetition across the 7 days
        $usedMealIds = [];

        // 💡 MODIFIED: Define a reusable function to fetch and validate a 7-day plan.
        // Passed $usedMealIds by reference to track unique meals.
        $fetchPlan = function($goal, $dietary, $activity, $meal_type, $filter, &$usedMealIds) use ($connection) {
            $plan = [];
            $found = true;
            
            // Determine the required meal times based on meal_type
            $requiredMealTimes = ($meal_type === '5_small') 
                ? ['breakfast', 'mid_morning', 'lunch', 'snack', 'dinner'] 
                : ['breakfast', 'lunch', 'dinner'];

            // Loop for 7 days
            for ($day = 1; $day <= 7; $day++) {
                $meals = [];
                $dayFound = true; // Flag for current day's completeness
                
                // Loop through required meal times
                foreach ($requiredMealTimes as $meal_time) {
                    // Build the exclusion clause to ensure meal uniqueness across the 7-day plan
                    $excludeClause = !empty($usedMealIds) 
                        ? " AND id NOT IN (" . implode(",", array_map('intval', $usedMealIds)) . ")" 
                        : "";

                    $query = "
                        SELECT * FROM diet_plans 
                        WHERE goal=? AND dietary=? AND activity=? AND meal_time=?
                        {$filter}
                        {$excludeClause}
                        ORDER BY RAND() LIMIT 1 
                    ";
                    
                    $stmt = $connection->prepare($query);
                    // Bind parameters for goal, dietary, activity, and specific meal_time
                    $stmt->bind_param("ssss", $goal, $dietary, $activity, $meal_time);
                    $stmt->execute();
                    $res = $stmt->get_result();

                    if ($res->num_rows > 0) {
                        $row = $res->fetch_assoc();
                        
                        // 💡 FIX 1: Track the meal ID
                        $usedMealIds[] = $row['id'];

                        // Add meal data
                        $meals[] = [
                            'meal_time' => $row['meal_time'],
                            'meal_text' => $row['meal_text'],
                            'quantity'  => $row['quantity'] ?? '',
                            'protein'   => $row['protein'],
                            'carbs'     => $row['carbs'],
                            'fat'       => $row['fat'],
                            'calories'  => $row['calories'],
                            'id'        => $row['id']
                        ];
                    } else {
                        // If any required meal slot is empty, the day is incomplete
                        $dayFound = false; 
                        break; 
                    }
                }
                
                if (!$dayFound) {
                    // If any day is incomplete, we cannot build a 7-day plan
                    $found = false;
                    break; 
                }
                
                // Sort meals back to standard order for consistent display
                usort($meals, function($a, $b) {
                    $order = ['breakfast' => 1, 'mid_morning' => 2, 'lunch' => 3, 'snack' => 4, 'dinner' => 5, 'pre_workout' => 6, 'post_workout' => 7];
                    // Handle cases where a meal_time might be missing from the order array safely
                    $orderA = $order[$a['meal_time']] ?? 99;
                    $orderB = $order[$b['meal_time']] ?? 99;
                    return $orderA <=> $orderB;
                });
            
                $plan[] = ['day_number' => $day, 'meals' => $meals];
            }
            
            return $found ? $plan : false;
        };

        // Attempt 1: Highly Filtered Plan (Safety First)
        $result = $fetchPlan($currentGoal, $currentDietary, $currentActivity, $currentMealType, $specialFilter, $usedMealIds);
        
        // Attempt 2: Fallback Plan (If Attempt 1 failed to load 7 complete days)
        if ($result === false && !empty($specialFilter)) {
            $isFallback = true;
            $healthWarning = "⚠️ **Warning:** No complete 7-day plan matching all health filters was found. A standard **{$currentGoalDisplay}** plan was loaded instead.";
            
            // Clear used IDs for a fresh attempt with a less strict filter
            $usedMealIds = []; 
            $result = $fetchPlan($currentGoal, $currentDietary, $currentActivity, $currentMealType, "", $usedMealIds); // Empty filter
        }

        if ($result !== false) {
            $planFound = true;
            $originalPlan = $result; // Store the fetched (unscaled) plan
        }

        // --- 5: Advanced Macro Scaling and Calorie Recalculation (Personalization Step) ---
        if ($planFound) { 
            
            $ratios = MACRO_RATIOS[$currentGoal];
            
            // 5.1: Calculate total macro targets for the 7-day plan (in grams)
            $targetMacrosGrams = [
                'protein' => round(($totalTargetCalories * $ratios['P']) / CALORIES_PER_GRAM['P']),
                'carbs'   => round(($totalTargetCalories * $ratios['C']) / CALORIES_PER_GRAM['C']),
                'fat'     => round(($totalTargetCalories * $ratios['F']) / CALORIES_PER_GRAM['F']),
            ];

            // 5.2: Calculate total macros and calories in the ORIGINAL fetched plan (7-day total)
            $originalTotalCalories = 0;
            foreach ($originalPlan as $day) {
                foreach ($day['meals'] as $meal) {
                    $originalTotalCalories += $meal['calories'];
                }
            }
            
            // 5.3: Determine the overall scaling factor based on total calories
            $adjustmentFactor = 1;
            if ($originalTotalCalories > 0 && $totalTargetCalories > 0) {
                $adjustmentFactor = $totalTargetCalories / $originalTotalCalories;
            } else {
                $error = "Plan loaded but had zero calories. Cannot scale.";
                $planFound = false;
            }

            if ($planFound) {
                $scaledPlan = [];
                $totalCaloriesCheck = 0;
                
                // 5.4: Apply global caloric scaling to all meals
                foreach ($originalPlan as $day) {
                    $scaledMeals = [];
                    foreach ($day['meals'] as $meal) {
                        // Apply the caloric adjustment factor to all macros
                        $p = max(0, $meal['protein'] * $adjustmentFactor);
                        $c = max(0, $meal['carbs'] * $adjustmentFactor);
                        $f = max(0, $meal['fat'] * $adjustmentFactor);
                        
                        // Recalculate CALORIES based on the newly scaled macros
                        $mealCalories = ($p * CALORIES_PER_GRAM['P']) + ($c * CALORIES_PER_GRAM['C']) + ($f * CALORIES_PER_GRAM['F']);
                        
                        $scaledMeals[] = [
                            'meal_time' => $meal['meal_time'],
                            'meal_text' => $meal['meal_text'],
                            'quantity'  => $meal['quantity'], 
                            'protein'   => $p, // Note: Still float/unrounded here
                            'carbs'     => $c,
                            'fat'       => $f,
                            'calories'  => $mealCalories, // Note: Still float/unrounded here
                        ];
                    }
                    $scaledPlan[] = ['day_number' => $day['day_number'], 'meals' => $scaledMeals];
                }
                
                // --- 6: Final Macro Alignment & Rounding (Crucial for hitting P:C:F ratios exactly) ---
                // We process the scaled plan day-by-day to enforce the P:C:F ratios precisely
                $finalPlan = [];
                
                foreach ($scaledPlan as $day) {
                    $meals = $day['meals'];
                    
                    // --- Calculate current macro totals for the day ---
                    $currentDayP = array_sum(array_column($meals, 'protein'));
                    $currentDayC = array_sum(array_column($meals, 'carbs'));
                    $currentDayF = array_sum(array_column($meals, 'fat'));
                    
                    // --- Calculate target macro totals for the day ---
                    $targetDayP = ($finalDailyTarget * $ratios['P']) / CALORIES_PER_GRAM['P'];
                    $targetDayC = ($finalDailyTarget * $ratios['C']) / CALORIES_PER_GRAM['C'];
                    $targetDayF = ($finalDailyTarget * $ratios['F']) / CALORIES_PER_GRAM['F'];
                    
                    // --- Calculate macro adjustment factors ---
                    // This ratio scales the current day's macro total to what it *should* be 
                    $p_factor = $currentDayP > 0 ? $targetDayP / $currentDayP : 1;
                    $c_factor = $currentDayC > 0 ? $targetDayC / $currentDayC : 1;
                    $f_factor = $currentDayF > 0 ? $targetDayF / $currentDayF : 1;
                    
                    // Apply fine-tuning adjustment and rounding
                    $newMeals = [];
                    $dayTotalCalCheck = 0;

                    foreach($meals as $meal) {
                        // Apply fine-tuning factor and round to nearest gram
                        $meal['protein'] = max(0, round($meal['protein'] * $p_factor));
                        $meal['carbs'] = max(0, round($meal['carbs'] * $c_factor));
                        $meal['fat'] = max(0, round($meal['fat'] * $f_factor));
                        
                        // Recalculate calories based on the newly aligned and rounded macros
                        $meal['calories'] = round(
                            ($meal['protein'] * CALORIES_PER_GRAM['P']) +
                            ($meal['carbs'] * CALORIES_PER_GRAM['C']) +
                            ($meal['fat'] * CALORIES_PER_GRAM['F'])
                        );
                        
                        $meal['calories'] = max(50, $meal['calories']); // Safety minimum
                        
                        $newMeals[] = $meal;
                        $dayTotalCalCheck += $meal['calories'];
                    }

                    // A final rounding check: if total calories of the day are off due to rounding, 
                    // adjust the first meal's calories to match the `$finalDailyTarget` exactly.
                    $dayCalDiff = $finalDailyTarget - $dayTotalCalCheck;
                    if (!empty($newMeals) && abs($dayCalDiff) < 10) { // Only adjust if difference is small (for rounding fix)
                         $newMeals[0]['calories'] += $dayCalDiff;
                    }
                    
                    $day['meals'] = $newMeals;
                    $finalPlan[] = $day;
                }
                
                // Final check on the 7-day total for the success message (should be very close to target)
                $finalTotalCaloriesCheck = array_sum(array_map(function($day) {
                    return array_sum(array_column($day['meals'], 'calories'));
                }, $finalPlan));

                $loadedPlan = $finalPlan; 

                // Recalculate the daily target based on the final 7-day total for the success message
                $finalDailyTarget = round($finalTotalCaloriesCheck / 7);

                
                // Set final parameters for saving and hidden fields
                $finalGoal = $currentGoal;
                $finalDietary = $currentDietary;
                $finalActivity = $currentActivity;
                $finalMealType = $currentMealType;

                // Success message
                $goalName = ucwords(str_replace('_', ' ', $finalGoal));
                $success = "✅ Personalized Plan generated successfully! **Daily Target Achieved: {$finalDailyTarget} kcal** (Maintenance: {$tdee} kcal). Macros aligned to **{$goalName}**." . ($healthWarning ? " <br>{$healthWarning}" : "");
                $showSaveButton = true;


            } // End if ($planFound) after scaling
        } else {
            // Error if neither highly-filtered nor standard plan was found
            $minMealsPerDay = ($inputMealType === '5_small') ? 5 : 3;
            $dietDisplay = $currentDietary === 'veg' ? 'Vegetarian' : 'Non-Vegetarian';
            $error = "No complete 7-day **{$dietDisplay}** plan found for the selected options. Please ensure your database contains enough unique meals for all required slots (at least {$minMealsPerDay} meals per day) for your selection.";
        }
    }
}


// ================== HANDLE SAVE PLAN ==================
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_plan'])) {
    // These values are passed via hidden fields to save user preferences
    $savedGoal      = $_POST['goal'] ?? '';
    $savedDietary   = $_POST['food'] ?? '';
    $savedActivity  = $_POST['activity'] ?? '';
    $savedMealType  = $_POST['meal_type'] ?? '';

    if (empty($_POST['plan'])) {
        $error = "No plan to save!";
    } else {
        // Use html_entity_decode to handle the JSON string which was htmlspecialchars-encoded
        $plan = json_decode(html_entity_decode($_POST['plan']), true);

        if (!$plan || !is_array($plan)) {
            $error = "Invalid plan structure!";
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
                    (user_id, day_number, meal_time, meal_text, quantity, protein, carbs, fat, calories)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                if (!$insertStmt) { throw new Exception("Prepare failed (INSERT): " . $connection->error); }

                foreach ($plan as $day) {
                    $day_number = intval($day['day_number']);
                    foreach ($day['meals'] as $meal) {
                        $meal_time  = trim($meal['meal_time'] ?? '');
                        $meal_text  = trim($meal['meal_text'] ?? '');
                        $quantity   = trim($meal['quantity'] ?? '');
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
                $_SESSION['success'] = "✅ Plan saved successfully and preferences updated! See your new, calorie-correct plan below.";
                header("Location: user_dietPlan.php");
                exit;

            } catch (Exception $e) {
                // 🔸 Rollback if any query fails
                $connection->rollback();
                $error = "MySQL error: " . $e->getMessage();
                error_log($e->getMessage());
            }
        }
    }
}
?>

<main class="flex-1 overflow-y-auto p-8 bg-gray-50">
    <h1 class="text-3xl font-extrabold text-gray-800 mb-8 border-b-2 border-sky-400 pb-2">Plan Generation</h1>

    <?php if($success): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4 font-semibold"><?= $success ?></div>
    <?php endif; ?>

    <?php if($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4 font-semibold"><?= $error ?></div>
    <?php endif; ?>

    <!-- Input Form -->
    <section class="bg-white rounded-xl shadow-lg p-6 mb-8">
        <h2 class="text-2xl font-bold text-sky-700 mb-6">Personalized Nutrition Plan Generator</h2>
        <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-6">

            <!-- Note: Value is set to the last selected/finalized value -->
            <div>
                <label class="block text-gray-700 font-medium">Activity Level</label>
                <select name="activity" class="w-full border rounded-lg px-3 py-2 mt-1 focus:ring-sky-500 focus:border-sky-500" required>
                    <option value="light" <?= $finalActivity == 'light' ? 'selected' : '' ?>>Lightly Active</option>
                    <option value="moderate" <?= $finalActivity == 'moderate' ? 'selected' : '' ?>>Moderately Active</option>
                    <option value="active" <?= $finalActivity == 'active' ? 'selected' : '' ?>>Very Active</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Food Preference</label>
                <select name="food" class="w-full border rounded-lg px-3 py-2 mt-1 focus:ring-sky-500 focus:border-sky-500" required>
                    <option value="veg" <?= $finalDietary == 'veg' ? 'selected' : '' ?>>Vegetarian</option>
                    <option value="nonveg" <?= $finalDietary == 'nonveg' ? 'selected' : '' ?>>Non-Vegetarian</option>
                </select>
            </div>

            <div>
                <label class="block text-gray-700 font-medium">Meal Type</label>
                <select name="meal_type" class="w-full border rounded-lg px-3 py-2 mt-1 focus:ring-sky-500 focus:border-sky-500" required>
                    <option value="3_meals" <?= $finalMealType == '3_meals' ? 'selected' : '' ?>>3 Meals</option>
                    <option value="5_small" <?= $finalMealType == '5_small' ? 'selected' : '' ?>>5 Small Meals</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <label class="block text-gray-700 font-medium">Fitness Goal</label>
                <select name="goal" class="w-full border rounded-lg px-3 py-2 mt-1 focus:ring-sky-500 focus:border-sky-500" required>
                    <option value="weight_loss" <?= $finalGoal == 'weight_loss' ? 'selected' : '' ?>>Weight Loss</option>
                    <option value="weight_gain" <?= $finalGoal == 'weight_gain' ? 'selected' : '' ?>>Weight Gain</option>
                    <option value="muscle_build" <?= $finalGoal == 'muscle_build' ? 'selected' : '' ?>>Muscle Building</option>
                    <option value="balanced" <?= $finalGoal == 'balanced' ? 'selected' : '' ?>>Balanced Diet</option>
                </select>
            </div>

            <div class="md:col-span-2">
                <button type="submit" name="generate_plan" class="w-full bg-sky-600 text-white px-4 py-3 rounded-lg font-semibold hover:bg-sky-700 transition-colors shadow-md">
                    📝 Generate Personalized Plan
                </button>
            </div>
        </form>
    </section>
    <!-- Display loaded plan and Save button -->
    <?php if(!empty($loadedPlan)): ?>
    <section class="bg-white rounded-xl shadow-lg p-6 mb-8 border-t-4 border-sky-500">
        <h3 class="text-xl font-bold text-sky-700 mb-4">7-Day Personalized Meal Plan (Check Calories!)</h3>
        
        <!-- Save Form for the loaded plan -->
        <form method="post" class="mb-6">
            <!-- Hidden fields to retain the final, possibly health-modified selections -->
            <input type="hidden" name="goal" value="<?= htmlspecialchars($finalGoal) ?>">
            <input type="hidden" name="food" value="<?= htmlspecialchars($finalDietary) ?>">
            <input type="hidden" name="activity" value="<?= htmlspecialchars($finalActivity) ?>">
            <input type="hidden" name="meal_type" value="<?= htmlspecialchars($finalMealType) ?>">
            <!-- Hidden plan data (URL encoded to handle JSON correctly) -->
            <input type="hidden" name="plan" value='<?= htmlspecialchars(json_encode($loadedPlan), ENT_QUOTES, 'UTF-8') ?>'>
            <button type="submit" name="save_plan" 
                    class="w-full bg-emerald-600 text-white px-4 py-3 rounded-lg font-semibold hover:bg-emerald-700 transition-colors shadow-xl">
                💾 Save This Plan to My Profile
            </button>
        </form>
        <?php foreach($loadedPlan as $day): ?>
            <?php
                // Calculate daily totals for display after scaling
                $dayTotalCalories = 0;
                $dayTotalProtein = 0;
                $dayTotalCarbs = 0;
                $dayTotalFat = 0;
                foreach ($day['meals'] as $meal) {
                    $dayTotalCalories += $meal['calories'];
                    $dayTotalProtein += $meal['protein'];
                    $dayTotalCarbs += $meal['carbs'];
                    $dayTotalFat += $meal['fat'];
                }
            ?>
            <div class="border rounded-xl shadow-md overflow-hidden mb-6">
                <div class="bg-sky-600 text-white px-4 py-3 font-bold text-lg flex justify-between items-center">
                    <span>📅 Day <?= $day['day_number'] ?></span>
                    <span class="bg-white text-sky-800 px-3 py-1 rounded-full font-extrabold text-sm">
                        TOTAL: <?= $dayTotalCalories ?> CAL
                    </span>
                </div>
                <!-- Display Daily Macro Totals -->
                <div class="bg-sky-100 text-sky-800 px-4 py-2 font-semibold text-sm flex gap-4 justify-between">
                    <span class="flex-1 text-center border-r border-sky-300">P: <?= $dayTotalProtein ?>g</span>
                    <span class="flex-1 text-center border-r border-sky-300">C: <?= $dayTotalCarbs ?>g</span>
                    <span class="flex-1 text-center">F: <?= $dayTotalFat ?>g</span>
                </div>

                <div class="divide-y divide-gray-200 bg-gray-50">
                    <?php foreach($day['meals'] as $meal): ?>
                        <div class="p-3 hover:bg-gray-100 transition flex flex-col md:flex-row justify-between items-start gap-2">
                            <div class="flex flex-col">
                                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-sky-200 text-sky-800 self-start">
                                    <?= strtoupper(str_replace('_', ' ', $meal['meal_time'])) ?>
                                </span>
                                <span class="font-medium text-gray-800 text-lg mt-1"><?= htmlspecialchars($meal['meal_text']) ?></span>
                                <span class="text-sm text-gray-500">Quantity: <?= htmlspecialchars($meal['quantity']) ?></span>
                            </div>
                            <div class="flex gap-2 text-sm flex-wrap justify-end">
                                <span class="bg-blue-100 text-blue-800 px-2 py-0.5 rounded font-medium">P: <?= $meal['protein'] ?>g</span>
                                <span class="bg-red-100 text-red-800 px-2 py-0.5 rounded font-medium">C: <?= $meal['carbs'] ?>g</span>
                                <span class="bg-yellow-100 text-yellow-800 px-2 py-0.5 rounded font-medium">F: <?= $meal['fat'] ?>g</span>
                                <span class="bg-emerald-100 text-emerald-800 px-2 py-0.5 rounded font-bold">CAL: <?= $meal['calories'] ?></span>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endforeach; ?>
    </section>
    <?php endif; ?>
</main>
