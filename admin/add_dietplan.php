<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// NOTE: Replace this with your actual path to db_conn.php
include(__DIR__ . '/../config/db_conn.php'); 

// ✅ Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// --- SERVER-SIDE VALIDATION FUNCTION ---
function validate_plan_data($config, $plan) {
    $allowed_goals = ['weight_loss', 'weight_gain', 'muscle_build', 'balanced'];
    $allowed_dietary = ['veg', 'nonveg'];
    $allowed_activities = ['light', 'moderate', 'active'];
    $allowed_meal_types = ['3_meals', '5_small'];
    $allowed_health_focus = ['none', 'diabetes', 'hypertension', 'obesity', 'heart_disease', 'pcos', 'thyroid', 'kidney']; // Added kidney
    
    $meal_times_map = [
        '3_meals' => ['breakfast', 'lunch', 'dinner'],
        '5_small' => ['breakfast', 'mid_morning', 'lunch', 'snack', 'dinner'],
    ];

    // 1. Validate Config
    if (!in_array($config['goal'] ?? null, $allowed_goals)) return "Invalid fitness goal.";
    if (!in_array($config['dietary'] ?? null, $allowed_dietary)) return "Invalid food preference.";
    if (!in_array($config['activity'] ?? null, $allowed_activities)) return "Invalid activity level.";
    if (!in_array($config['meal_type'] ?? null, $allowed_meal_types)) return "Invalid meal type.";
    if (!in_array($config['health_focus'] ?? null, $allowed_health_focus)) return "Invalid health focus.";

    $meal_type_key = $config['meal_type'];
    if (!isset($meal_times_map[$meal_type_key])) return "Unknown meal type structure.";
    $meal_times_for_plan = $meal_times_map[$meal_type_key];

    // 2. Validate Meal Plan Data (Day by Day)
    if (count($plan) !== 7) return "Plan must contain exactly 7 days of data.";

    foreach ($plan as $day_index => $day_meals) {
        $day_number = $day_index + 1;
        
        foreach ($meal_times_for_plan as $meal_time) {
            $meal = $day_meals[$meal_time] ?? null;

            if (!$meal) return "Missing meal data for Day $day_number, $meal_time.";
            
            // Critical Check 1: Meal description text field
            $meal_text = $meal['meal_text'] ?? '';
            if (empty(trim($meal_text))) {
                return "Meal description is required for Day $day_number, $meal_time.";
            }
            
            // Critical Check 2: Serving Macro Numeric fields validation (must be non-negative)
            $serving_numeric_fields = ['protein', 'carbs', 'fat', 'calories'];
            foreach ($serving_numeric_fields as $field) {
                $value = $meal[$field] ?? 0;
                if (!is_numeric($value) || intval($value) < 0) {
                    return "Serving $field must be a non-negative integer for Day $day_number, $meal_time.";
                }
            }
            
            // Calories must be positive (greater than 0)
            if (intval($meal['calories']) <= 0) {
                return "Serving Calories must be specified (greater than 0) for Day $day_number, $meal_time.";
            }

            // Critical Check 3: Scaling fields validation
            $scaling_numeric_fields = ['base_quantity', 'protein_per_unit', 'carbs_per_unit', 'fat_per_unit', 'calories_per_unit'];
            foreach ($scaling_numeric_fields as $field) {
                $value = $meal[$field] ?? 0;
                // Note: base_quantity is decimal, others are int/decimal
                if (!is_numeric($value) || floatval($value) < 0) {
                    return "Scaling field $field must be a non-negative number for Day $day_number, $meal_time.";
                }
            }
            
            // Base Quantity and Calories Per Unit must be positive
            if (floatval($meal['base_quantity']) <= 0) {
                return "Base Quantity must be greater than 0 for Day $day_number, $meal_time.";
            }
            if (intval($meal['calories_per_unit']) <= 0) {
                return "Calories Per Unit must be greater than 0 (calculated from serving) for Day $day_number, $meal_time.";
            }

            // Unit check
            if (empty(trim($meal['unit'] ?? ''))) {
                 return "Unit field is required for Day $day_number, $meal_time.";
            }

            // Boolean flags check (must be boolean 0 or 1)
            $flag_fields = ['low_carb', 'low_glycemic', 'high_fiber'];
            foreach ($flag_fields as $flag) {
                 $flag_value = $meal[$flag] ?? 0;
                 if (!(is_bool($flag_value) || in_array($flag_value, [0, 1], true))) {
                      return "Flag '$flag' has an invalid value for Day $day_number, $meal_time.";
                 }
            }
        }
    }

    return true; // Validation passed
}

// --- NEW PHP LOGIC FOR MULTI-STEP JSON SUBMISSION ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && str_contains($_SERVER['CONTENT_TYPE'], 'application/json')) {
    $json_data = file_get_contents('php://input');
    $data = json_decode($json_data, true);
    
    if (!$data || !isset($data['config']) || !isset($data['plan'])) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid JSON payload received.']);
        exit();
    }

    $config = $data['config'];
    $plan = $data['plan'];
    
    // --- EXECUTE SERVER-SIDE VALIDATION ---
    $validation_result = validate_plan_data($config, $plan);
    if ($validation_result !== true) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => "❌ Validation failed: $validation_result"]);
        exit();
    }
    // --- VALIDATION PASSED, PROCEED TO TRANSACTION ---

    $connection->begin_transaction();
    $success_count = 0;

    try {
        // Total 22 columns
        $insert = $connection->prepare("INSERT INTO diet_plans 
            (goal, dietary, activity, meal_type, day_number, meal_time, meal_text, quantity, protein, carbs, fat, calories, low_carb, low_glycemic, high_fiber, health_focus, base_quantity, unit, protein_per_unit, carbs_per_unit, fat_per_unit, calories_per_unit) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Type definition string: ssssissisiiiiisfsfffi
        // s: string, i: int, f: float
        // Note: Decimals in MySQL map to floats in PHP
        $insert->bind_param("ssssissisiiiiisfsfffi", 
            $config['goal'], 
            $config['dietary'], 
            $config['activity'], 
            $config['meal_type'], 
            $day_number, // i
            $meal_time, // s
            $meal_text, // s
            $quantity, // s
            $protein, // i
            $carbs, // i
            $fat, // i
            $calories, // i
            $low_carb, // i
            $low_glycemic, // i
            $high_fiber, // i
            $health_focus, // s (NEW)
            $base_quantity, // f (NEW)
            $unit, // s (NEW)
            $protein_per_unit, // f (NEW)
            $carbs_per_unit, // f (NEW)
            $fat_per_unit, // f (NEW)
            $calories_per_unit // i (NEW)
        );

        // Meal times map for execution logic
        $meal_times_map = [
            '3_meals' => ['breakfast', 'lunch', 'dinner'],
            '5_small' => ['breakfast', 'mid_morning', 'lunch', 'snack', 'dinner'],
        ];
        $meal_times_for_plan = $meal_times_map[$config['meal_type']];
        $health_focus = $config['health_focus'];


        foreach ($plan as $day_index => $day_meals) {
            $day_number = $day_index + 1;
            
            foreach ($meal_times_for_plan as $meal_time) {
                $meal = $day_meals[$meal_time];

                // Assign values to bound variables
                $meal_text = $meal['meal_text'];
                $quantity = $meal['quantity'];
                
                // Serving Macros
                $protein = intval($meal['protein']);
                $carbs = intval($meal['carbs']);
                $fat = intval($meal['fat']);
                $calories = intval($meal['calories']);
                
                // Flags
                $low_carb = $meal['low_carb'] ? 1 : 0;
                $low_glycemic = $meal['low_glycemic'] ? 1 : 0;
                $high_fiber = $meal['high_fiber'] ? 1 : 0;
                
                // Scaling Fields
                $base_quantity = floatval($meal['base_quantity']);
                $unit = $meal['unit'];
                $protein_per_unit = floatval($meal['protein_per_unit']);
                $carbs_per_unit = floatval($meal['carbs_per_unit']);
                $fat_per_unit = floatval($meal['fat_per_unit']);
                $calories_per_unit = intval($meal['calories_per_unit']);
                
                // Execute the statement
                if (!$insert->execute()) {
                    throw new Exception("Database error: " . $insert->error);
                }
                $success_count++;
            }
        }

        $connection->commit();
        echo json_encode(['status' => 'success', 'message' => "✅ Successfully added $success_count meal entries for the 7-day plan!"]);
        exit();

    } catch (Exception $e) {
        $connection->rollback();
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => "❌ Transaction failed. No plans were added. Error: " . $e->getMessage()]);
        exit();
    }
}
// --- END OF NEW PHP LOGIC ---

// --- OLD PHP LOGIC (Retained as warning) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !str_contains($_SERVER['CONTENT_TYPE'], 'application/json')) {
    $_SESSION['error'] = "❌ Legacy form submission detected. Please use the multi-step form.";
    header("Location: add_dietplan.php");
    exit();
}
// --- END OLD PHP LOGIC ---
?>

<?php include 'components/head.php'; ?>
<?php include 'components/dashboard-nav.php'; ?>

<!-- Inject CSS for validation feedback -->
<style>
.error-border {
    border-color: #ef4444 !important; /* red-500, use !important to override Tailwind/browser defaults */
    box-shadow: 0 0 0 1px #ef4444;
}
</style>

<main class="bg-gray-50 min-h-screen p-4 md:p-6">
  <div class="max-w-7xl mx-auto bg-white p-6 md:p-10 rounded-xl shadow-2xl w-full">
    <h2 class="text-3xl font-bold text-emerald-600 mb-8 text-center">➕ Add Full 7-Day Diet Plan</h2>

    <!-- PHP Session Messages for old logic -->
    <?php if (isset($_SESSION['success'])): ?>
      <p class="p-3 mb-4 bg-green-100 text-green-700 rounded-lg font-medium"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
    <?php elseif (isset($_SESSION['error'])): ?>
      <p class="p-3 mb-4 bg-red-100 text-red-700 rounded-lg font-medium"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>

    <!-- JS Feedback Area -->
    <div id="status-message" class="hidden p-4 mb-4 rounded-lg font-medium shadow-md"></div>

    <div id="form-container" class="space-y-8 w-full">

        <!-- STEP 1: GLOBAL CONFIGURATION -->
        <div id="step-1" class="step-content">
            <h3 class="text-xl font-semibold text-gray-700 mb-6 border-b pb-2">Step 1: Plan Preferences (Global)</h3>
            <div class="grid grid-cols-1 md:grid-cols-5 gap-6">
                
                <!-- Food Preference -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Food Preference</label>
                    <select name="dietary" id="dietary" required
                            class="w-full border rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-emerald-500 transition duration-150">
                        <option value="veg">Vegetarian</option>
                        <option value="nonveg">Non-Vegetarian</option>
                    </select>
                </div>

                <!-- Fitness Goal -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Fitness Goal</label>
                    <select name="goal" id="goal" required
                            class="w-full border rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-emerald-500 transition duration-150">
                        <option value="weight_loss">Weight Loss</option>
                        <option value="weight_gain">Weight Gain</option>
                        <option value="muscle_build">Muscle Building</option>
                        <option value="balanced">Balanced Diet</option>
                    </select>
                </div>

                <!-- Activity Level -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Activity Level</label>
                    <select name="activity" id="activity" required
                            class="w-full border rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-emerald-500 transition duration-150">
                        <option value="light">Light Activity</option>
                        <option value="moderate">Moderate Activity</option>
                        <option value="active">Active</option>
                    </select>
                </div>

                <!-- Meal Type -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Preferred Meal Type</label>
                    <select name="meal_type" id="meal_type" required
                            class="w-full border rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-emerald-500 transition duration-150">
                        <option value="3_meals">3 Meals/Day (B, L, D)</option>
                        <option value="5_small">5 Small Meals/Day (B, MM, L, Snk, D)</option>
                    </select>
                </div>
                
                <!-- Health Focus (NEW) -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Health Focus</label>
                    <select name="health_focus" id="health_focus" required
                            class="w-full border rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-emerald-500 transition duration-150">
                        <option value="none">None (General)</option>
                        <option value="diabetes">Diabetes (Low GI)</option>
                        <option value="hypertension">Hypertension (Low Sodium)</option>
                        <option value="obesity">Obesity (Strict Calorie)</option>
                        <option value="heart_disease">Heart Disease (Low Fat)</option>
                        <option value="pcos">PCOS (Low GI/Moderate Carb)</option>
                        <option value="thyroid">Thyroid (Balanced)</option>
                        <option value="kidney">Kidney (Low Protein)</option>
                    </select>
                </div>
            </div>

            <div class="flex justify-end mt-8">
                <button type="button" onclick="nextStep()" class="px-8 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-center font-semibold transition duration-200 shadow-md">
                    Next: Enter Meals (Day 1) &rarr;
                </button>
            </div>
        </div>
        
        <!-- STEP 2: DAY-BY-DAY MEAL ENTRY (Hidden by default) -->
        <div id="step-2" class="step-content hidden">
            <h3 class="text-xl font-semibold text-gray-700 mb-4 border-b pb-2">
                Step 2: Meal Entry - <span id="current-day-text" class="text-emerald-600">Day 1 of 7</span>
            </h3>
            
            <!-- Meal Inputs will be dynamically generated here -->
            <div id="meal-inputs-container" class="space-y-8">
                <!-- Dynamic meal fields go here -->
            </div>

            <div class="flex justify-between mt-8 pt-4 border-t">
                <button type="button" onclick="prevStep()" class="px-6 py-3 bg-gray-400 text-white rounded-lg hover:bg-gray-500 text-center font-semibold transition duration-200">
                    &larr; Back to Config
                </button>
                <div class="flex space-x-4">
                    <button type="button" id="prev-day-btn" onclick="changeDay(-1)" class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-center font-semibold transition duration-200 disabled:opacity-50">
                        &larr; Previous Day
                    </button>
                    <button type="button" id="next-day-btn" onclick="changeDay(1)" class="px-6 py-3 bg-blue-500 text-white rounded-lg hover:bg-blue-600 text-center font-semibold transition duration-200 disabled:opacity-50">
                        Next Day &rarr;
                    </button>
                    <button type="button" id="submit-btn" onclick="submitPlan()" class="hidden px-8 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-center font-bold transition duration-200">
                        Submit 7-Day Plan
                    </button>
                </div>
            </div>
        </div>

    </div>
  </div>
</main>
</div>

<script>
    // --- JAVASCRIPT FOR MULTI-STEP FORM LOGIC AND VALIDATION ---

    const MEAL_TIMES = {
        '3_meals': ['breakfast', 'lunch', 'dinner'],
        '5_small': ['breakfast', 'mid_morning', 'lunch', 'snack', 'dinner'],
    };

    const initialMealData = () => ({
        meal_text: '',
        quantity: '',
        protein: 0,
        carbs: 0,
        fat: 0,
        calories: 0,
        low_carb: false,
        low_glycemic: false,
        high_fiber: false,
        
        // NEW SCALING FIELDS
        base_quantity: 100.00, // Default to 100g for easy calculation
        unit: 'g',        
        
        // CALCULATED/SYSTEM FIELDS (will be updated by JS)
        protein_per_unit: 0.00,
        carbs_per_unit: 0.00,
        fat_per_unit: 0.00,
        calories_per_unit: 0,
    });

    const createInitialPlanState = () => {
        return Array(7).fill(null).map(() => {
            const dayMeals = {};
            [...MEAL_TIMES['5_small']].forEach(time => {
                dayMeals[time] = initialMealData();
            });
            return dayMeals;
        });
    };

    let currentStep = 1;
    let currentDay = 0; // 0-indexed (Day 1)
    let globalConfig = {};
    let planData = createInitialPlanState(); // Holds data for all 7 days

    const $ = (id) => document.getElementById(id);
    
    // --- UTILITY FUNCTIONS ---
    function calculatePerUnitMacros(day, time) {
        const meal = planData[day][time];
        const baseGrams = parseFloat(meal.base_quantity);
        
        // Only calculate if base grams is valid and positive
        if (baseGrams > 0) {
            const factor = 100 / baseGrams;

            // Calculate per-unit values (per 100g/unit)
            meal.protein_per_unit = (meal.protein * factor).toFixed(2);
            meal.carbs_per_unit = (meal.carbs * factor).toFixed(2);
            meal.fat_per_unit = (meal.fat * factor).toFixed(2);
            meal.calories_per_unit = Math.round(meal.calories * factor);
        } else {
            // Reset to 0 if base quantity is invalid
            meal.protein_per_unit = 0.00;
            meal.carbs_per_unit = 0.00;
            meal.fat_per_unit = 0.00;
            meal.calories_per_unit = 0;
        }

        // Update read-only fields in the UI
        $(`protein_per_unit_${day}_${time}`).value = meal.protein_per_unit;
        $(`carbs_per_unit_${day}_${time}`).value = meal.carbs_per_unit;
        $(`fat_per_unit_${day}_${time}`).value = meal.fat_per_unit;
        $(`calories_per_unit_${day}_${time}`).value = meal.calories_per_unit;
    }

    // Function to move to the next step (from config to meal entry)
    function nextStep() {
        // 1. Capture Global Config
        globalConfig = {
            dietary: $('dietary').value,
            goal: $('goal').value,
            activity: $('activity').value,
            meal_type: $('meal_type').value,
            health_focus: $('health_focus').value, // NEW CONFIG FIELD
        };
        
        // 2. Transition to Step 2
        $('step-1').classList.add('hidden');
        $('step-2').classList.remove('hidden');
        currentStep = 2;
        
        // 3. Render Day 1
        renderCurrentDay();
    }

    // Function to move back to the previous step (from meal entry to config)
    function prevStep() {
        $('step-2').classList.add('hidden');
        $('step-1').classList.remove('hidden');
        currentStep = 1;
        // Clear status message when going back
        document.querySelectorAll('.error-message').forEach(el => el.remove());
    }
    
    // --- TARGETED REAL-TIME FIELD VALIDATION ---
    function validateField(input) {
        let isValid = true;
        const field = input.dataset.field;
        const value = input.value.trim();
        let numValue;
        
        // Required Fields (must not be empty)
        const isRequired = ['meal_text', 'calories', 'protein', 'carbs', 'fat', 'base_quantity', 'unit'].includes(field);
        
        if (isRequired && value === '') {
            isValid = false;
        } 
        
        if (value !== '') {
            if (field === 'meal_text' || field === 'quantity' || field === 'unit') {
                // Text fields, already checked for emptiness
            }
            // Base Quantity (must be positive decimal > 0)
            else if (field === 'base_quantity') {
                 numValue = parseFloat(value);
                 if (isNaN(numValue) || numValue <= 0) {
                     isValid = false;
                 }
            }
            // Calories (must be positive integer > 0)
            else if (field === 'calories') {
                numValue = parseInt(value);
                if (isNaN(numValue) || numValue <= 0) {
                    isValid = false;
                }
            } 
            // Other Macros (must be non-negative integers >= 0)
            else if (['protein', 'carbs', 'fat'].includes(field)) {
                numValue = parseInt(value);
                if (isNaN(numValue) || numValue < 0) {
                    isValid = false;
                }
            }
        }
        
        if (isValid) {
            input.classList.remove('error-border');
        } else {
            input.classList.add('error-border');
        }
    }
    
    // --- BULK DAY VALIDATION (for Next Day/Submit clicks) ---
    function validateCurrentDay(dayIndex) {
        const mealTimes = MEAL_TIMES[globalConfig.meal_type];
        let isValid = true;
        
        // Remove previous error message
        document.querySelectorAll('#validation-error-msg').forEach(el => el.remove());
        // Clear previous error highlighting (we re-validate below)
        document.querySelectorAll('.error-border').forEach(el => el.classList.remove('error-border'));
        
        mealTimes.forEach(time => {
            // Check inputs using DOM elements for visual feedback
            const fieldsToValidate = ['meal_text', 'quantity', 'base_quantity', 'unit', 'calories', 'protein', 'carbs', 'fat'];
            
            fieldsToValidate.forEach(field => {
                const inputEl = $(`${field}_${dayIndex}_${time}`);
                
                // Manually trigger validation on the element
                if(inputEl) {
                    validateField(inputEl);
                    if (inputEl.classList.contains('error-border')) {
                        isValid = false;
                    }
                }
            });
            
            // Also check the calculated calories_per_unit, which should be > 0 if serving calories > 0
            if (planData[dayIndex][time].calories > 0 && planData[dayIndex][time].calories_per_unit <= 0) {
                 isValid = false;
                 // Note: We don't error-border the read-only field, the error message explains it
            }
        });

        if (!isValid) {
            const errorContainer = $('step-2');
            const message = document.createElement('div');
            message.id = 'validation-error-msg';
            message.className = 'error-message p-3 mb-4 bg-red-100 text-red-700 rounded-lg font-medium shadow-md';
            message.innerHTML = '🚨 **Validation Error on Day ' + (dayIndex + 1) + ':** Please ensure all highlighted fields are valid. **Base Quantity** and **Serving Calories** must be $\gt$ 0. Macros must be $\ge$ 0.';
            errorContainer.insertBefore(message, $('meal-inputs-container'));
        }

        return isValid;
    }

    // Renders the input fields for the currently selected day
    function renderCurrentDay() {
        const mealTimes = MEAL_TIMES[globalConfig.meal_type];
        const container = $('meal-inputs-container');
        container.innerHTML = '';
        
        const currentDayMeals = planData[currentDay];

        // Clear any previous error message when rendering a new day
        document.querySelectorAll('#validation-error-msg').forEach(el => el.remove());

        // Update day text display
        $('current-day-text').textContent = `Day ${currentDay + 1} of 7 (${globalConfig.meal_type.replace('_', ' ')})`;

        mealTimes.forEach(time => {
            const meal = currentDayMeals[time] || initialMealData(); 
            const dayTimeKey = `${currentDay}_${time}`;

            const mealHtml = `
                <div class="p-6 border border-gray-200 rounded-xl shadow-sm bg-white hover:shadow-md transition duration-200">
                    <h4 class="text-xl font-bold text-gray-700 mb-4 capitalize">${time.replace('_', ' ')}</h4>

                    <div class="space-y-3 mb-4">
                        <label class="block">
                            <span class="text-sm font-medium text-gray-500">Meal Description <span class="text-red-500">*</span></span>
                            <textarea id="meal_text_${dayTimeKey}" data-day="${currentDay}" data-time="${time}" data-field="meal_text" rows="2" class="w-full border rounded-lg px-4 py-2 text-base focus:ring-1 focus:ring-emerald-500">${meal.meal_text}</textarea>
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium text-gray-500">Serving Size/Quantity (Text, Optional)</span>
                            <input type="text" id="quantity_${dayTimeKey}" data-day="${currentDay}" data-time="${time}" data-field="quantity" value="${meal.quantity}" placeholder="e.g., 1 plate or 2 chapatis" class="w-full border rounded-lg px-4 py-2 text-base focus:ring-1 focus:ring-emerald-500">
                        </label>
                    </div>

                    <!-- SCALING BASE INPUTS -->
                    <div class="grid grid-cols-2 gap-4 border-t pt-4">
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">Base Quantity (Grams) <span class="text-red-500">*</span></label>
                            <input type="number" id="base_quantity_${dayTimeKey}" data-day="${currentDay}" data-time="${time}" data-field="base_quantity" value="${meal.base_quantity}" min="0.01" step="0.01" placeholder="e.g., 150.50" class="w-full border rounded-lg px-2 py-1 text-sm font-mono focus:ring-1 focus:ring-emerald-500">
                        </div>
                        <div>
                            <label class="block text-gray-700 font-medium mb-1">Base Unit <span class="text-red-500">*</span></label>
                            <input type="text" id="unit_${dayTimeKey}" data-day="${currentDay}" data-time="${time}" data-field="unit" value="${meal.unit}" placeholder="g, ml, unit" class="w-full border rounded-lg px-2 py-1 text-sm font-mono focus:ring-1 focus:ring-emerald-500">
                        </div>
                    </div>

                    <div class="mt-4">
                        <h5 class="text-md font-semibold text-gray-600 mb-2">Serving Macro Details</h5>
                        <div class="grid grid-cols-4 gap-3 text-center">
                            ${['protein', 'carbs', 'fat', 'calories'].map(field => `
                                <div>
                                    <label class="block text-gray-700 font-medium mb-1 capitalize text-sm">${field} (serving) ${field === 'calories' ? '<span class="text-red-500">*</span>' : ''}</label>
                                    <input type="number" id="${field}_${dayTimeKey}" data-day="${currentDay}" data-time="${time}" data-field="${field}" value="${meal[field] > 0 ? meal[field] : ''}" min="${field === 'calories' ? '1' : '0'}" step="1" class="w-full border rounded-lg px-2 py-1 text-sm font-mono focus:ring-1 focus:ring-emerald-500">
                                </div>
                            `).join('')}
                        </div>
                    </div>

                    <!-- CALCULATED PER-UNIT OUTPUTS (READ-ONLY) -->
                    <div class="mt-4 p-3 bg-gray-50 border rounded-lg">
                        <h5 class="text-xs font-bold text-gray-600 mb-2">Calculated Macros (Per 100g/unit)</h5>
                        <div class="grid grid-cols-4 gap-3 text-center">
                            ${['protein', 'carbs', 'fat', 'calories'].map(field => `
                                <div>
                                    <label class="block text-gray-500 font-medium mb-1 capitalize text-xs">${field} / 100g</label>
                                    <input type="text" id="${field}_per_unit_${dayTimeKey}" value="${field === 'calories' ? meal.calories_per_unit : meal[`${field}_per_unit`].toFixed(2)}" readonly class="w-full border border-gray-300 rounded-lg px-2 py-1 text-xs font-mono bg-white text-gray-700 text-center">
                                </div>
                            `).join('')}
                        </div>
                    </div>

                    <!-- FLAGS -->
                    <div class="flex justify-around mt-4 pt-3 border-t">
                        ${['low_carb', 'low_glycemic', 'high_fiber'].map(field => `
                            <label class="inline-flex items-center text-xs text-gray-600 font-medium cursor-pointer">
                                <input type="checkbox" id="${field}_${dayTimeKey}" data-day="${currentDay}" data-time="${time}" data-field="${field}" ${meal[field] ? 'checked' : ''} class="rounded text-emerald-600 shadow-sm focus:ring-emerald-500">
                                <span class="ml-2 capitalize">${field.replace('_', ' ')}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', mealHtml);
            
            // After rendering, ensure the initial calculated fields are up-to-date
            calculatePerUnitMacros(currentDay, time);
        });

        // Attach event listeners to all newly rendered inputs
        container.querySelectorAll('[data-field]').forEach(input => {
            input.addEventListener('input', handleInputChange);
            input.addEventListener('change', handleInputChange);
            
            // Validate the input immediately after rendering
            validateField(input);
        });

        updateDayNavigation();
    }

    // Saves the input value to the planData state and triggers real-time validation and calculation
    function handleInputChange(e) {
        const input = e.target;
        const day = parseInt(input.dataset.day);
        const time = input.dataset.time;
        const field = input.dataset.field;
        let value;

        if (input.type === 'checkbox') {
            value = input.checked;
        } else if (input.type === 'number') {
            const rawValue = input.value.trim();
            const numValue = field === 'base_quantity' ? parseFloat(rawValue) : parseInt(rawValue);

            if (rawValue === '' || isNaN(numValue)) {
                value = 0; 
            } else {
                value = Math.max(0, numValue);
                // For base_quantity, allow decimals
                if (field === 'base_quantity') {
                    value = Math.max(0.01, parseFloat(rawValue).toFixed(2));
                }
                
                if (numValue < 0) {
                   input.value = value;
                }
            }
        } else {
            value = input.value;
        }

        // 1. Save data to the correct day/meal/field
        planData[day][time][field] = value;
        
        // 2. Run real-time validation
        validateField(input); 

        // 3. Auto-Calculate Per-Unit Macros if a relevant field changed
        const relevantFields = ['base_quantity', 'protein', 'carbs', 'fat', 'calories'];
        if (relevantFields.includes(field)) {
            calculatePerUnitMacros(day, time);
        }

        // 4. Update validation error message if needed
        if ($('validation-error-msg')) {
             setTimeout(() => validateCurrentDay(currentDay), 50);
        }
    }

    // Handles moving between days
    function changeDay(direction) {
        // Validation check only when moving FORWARD
        if (direction === 1) {
            if (!validateCurrentDay(currentDay)) {
                return; // Stop if validation fails
            }
        }
        
        let newDay = currentDay + direction;
        
        if (newDay >= 0 && newDay < 7) {
            currentDay = newDay;
            renderCurrentDay();
        }
    }

    // Updates the state of the day navigation buttons
    function updateDayNavigation() {
        const prevBtn = $('prev-day-btn');
        const nextBtn = $('next-day-btn');
        const submitBtn = $('submit-btn');

        if (currentDay === 0) {
            prevBtn.textContent = '← Back to Config';
            prevBtn.onclick = prevStep;
        } else {
            prevBtn.textContent = '← Previous Day';
            prevBtn.onclick = () => changeDay(-1);
        }
        
        prevBtn.disabled = false;
        nextBtn.disabled = currentDay === 6;

        if (currentDay === 6) {
            nextBtn.classList.add('hidden');
            submitBtn.classList.remove('hidden');
        } else {
            nextBtn.classList.remove('hidden');
            submitBtn.classList.add('hidden');
        }
    }

    // Displays feedback messages
    function showStatus(type, message) {
        const statusDiv = $('status-message');
        statusDiv.classList.remove('hidden', 'bg-green-100', 'text-green-700', 'bg-red-100', 'text-red-700', 'border-green-400', 'border-red-400');
        
        if (type === 'success') {
            statusDiv.classList.add('bg-green-100', 'text-green-700', 'border-green-400');
        } else {
            statusDiv.classList.add('bg-red-100', 'text-red-700', 'border-red-400');
        }
        
        statusDiv.innerHTML = `<p class="font-bold">${type.toUpperCase()}:</p><p>${message}</p>`;
        
        setTimeout(() => {
            statusDiv.classList.add('hidden');
        }, 10000);
    }
    
    // Custom confirm dialog replacement
    function confirm(message) {
        return window.confirm(message);
    }

    // Final submission function
    async function submitPlan() {
        // 1. Client-side validation of the FINAL day (Day 7)
        if (!validateCurrentDay(currentDay)) {
            showStatus('error', 'Please correct the validation errors on Day 7 before submitting.');
            return;
        }

        if (!confirm("Are you sure you want to submit the entire 7-day plan? The server will perform a final validation check.")) {
            return;
        }
        
        const submitBtn = $('submit-btn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        // Filter and prepare the data for submission
        const mealTimes = MEAL_TIMES[globalConfig.meal_type];
        const filteredPlan = planData.map(dayMeals => {
            const filteredDay = {};
            mealTimes.forEach(time => {
                const meal = dayMeals[time];
                // Ensure decimal values are stored as floats for PHP binding
                meal.base_quantity = parseFloat(meal.base_quantity);
                meal.protein_per_unit = parseFloat(meal.protein_per_unit);
                meal.carbs_per_unit = parseFloat(meal.carbs_per_unit);
                meal.fat_per_unit = parseFloat(meal.fat_per_unit);
                
                filteredDay[time] = meal;
            });
            return filteredDay;
        });

        const submissionPayload = {
            config: globalConfig,
            plan: filteredPlan
        };

        try {
            const response = await fetch(window.location.href, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(submissionPayload)
            });

            const result = await response.json();

            if (response.ok && result.status === 'success') {
                showStatus('success', result.message);
                // Reset form to Step 1 and clear data
                planData = createInitialPlanState();
                currentDay = 0;
                // Wait briefly for state reset before going back
                setTimeout(() => prevStep(), 500); 
            } else {
                showStatus('error', result.message || 'An unknown error occurred on the server.');
            }

        } catch (error) {
            console.error('Fetch error:', error);
            showStatus('error', 'A network error occurred during submission. Check console for details.');
        } finally {
            submitBtn.disabled = false;
            submitBtn.textContent = 'Submit 7-Day Plan';
        }
    }

    // Initial load setup
    document.addEventListener('DOMContentLoaded', () => {
        // No need to call renderCurrentDay here as it is called in nextStep()
    });

</script>
