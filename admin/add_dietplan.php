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
    $meal_times_map = [
        '3_meals' => ['breakfast', 'lunch', 'dinner'],
        '5_small' => ['breakfast', 'mid_morning', 'lunch', 'snack', 'dinner'],
    ];

    // 1. Validate Config
    if (!in_array($config['goal'] ?? null, $allowed_goals)) return "Invalid fitness goal.";
    if (!in_array($config['dietary'] ?? null, $allowed_dietary)) return "Invalid food preference.";
    if (!in_array($config['activity'] ?? null, $allowed_activities)) return "Invalid activity level.";
    if (!in_array($config['meal_type'] ?? null, $allowed_meal_types)) return "Invalid meal type.";

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
            
            // Critical Check 2: Numeric fields validation (must be non-negative)
            $numeric_fields = ['protein', 'carbs', 'fat', 'calories'];
            foreach ($numeric_fields as $field) {
                $value = $meal[$field] ?? 0;
                
                // Ensure value is numeric and non-negative
                if (!is_numeric($value) || $value < 0) {
                    return "$field must be a non-negative number for Day $day_number, $meal_time.";
                }
            }
            
            // Critical Check 3: Calories must be positive (greater than 0)
            // We use the already validated numeric value
            if (intval($meal['calories']) <= 0) {
                return "Calories must be specified (greater than 0) for Day $day_number, $meal_time.";
            }

            // Boolean flags check (must be boolean 0 or 1)
            $flag_fields = ['low_carb', 'low_glycemic', 'high_fiber'];
            foreach ($flag_fields as $flag) {
                 $flag_value = $meal[$flag] ?? 0;
                 // Check if it's a valid 0 or 1 representation
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
        // Prepare the INSERT statement once outside the loop
        $insert = $connection->prepare("INSERT INTO diet_plans 
            (goal, dietary, activity, meal_type, day_number, meal_time, meal_text, quantity, protein, carbs, fat, calories, low_carb, low_glycemic, high_fiber) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

        // Bind parameters (type definition string: ssssissisiiiiii)
        $insert->bind_param("ssssissisiiiiii", 
            $config['goal'], 
            $config['dietary'], 
            $config['activity'], 
            $config['meal_type'], 
            $day_number, 
            $meal_time, 
            $meal_text, 
            $quantity,
            $protein, 
            $carbs, 
            $fat, 
            $calories,
            $low_carb,
            $low_glycemic,
            $high_fiber
        );

        // Meal times map for execution logic
        $meal_times_map = [
            '3_meals' => ['breakfast', 'lunch', 'dinner'],
            '5_small' => ['breakfast', 'mid_morning', 'lunch', 'snack', 'dinner'],
        ];
        $meal_times_for_plan = $meal_times_map[$config['meal_type']];


        foreach ($plan as $day_index => $day_meals) {
            $day_number = $day_index + 1;
            
            foreach ($meal_times_for_plan as $meal_time) {
                $meal = $day_meals[$meal_time];

                // Assign values to bound variables
                $meal_text = $meal['meal_text'];
                $quantity = $meal['quantity'];
                // Ensure explicit integer casting for database insertion
                $protein = intval($meal['protein']);
                $carbs = intval($meal['carbs']);
                $fat = intval($meal['fat']);
                $calories = intval($meal['calories']);
                $low_carb = $meal['low_carb'] ? 1 : 0;
                $low_glycemic = $meal['low_glycemic'] ? 1 : 0;
                $high_fiber = $meal['high_fiber'] ? 1 : 0;
                
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
            <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
                
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

                <!-- Preferred Meal Type -->
                <div>
                    <label class="block text-gray-700 font-medium mb-2">Preferred Meal Type</label>
                    <select name="meal_type" id="meal_type" required
                            class="w-full border rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-emerald-500 transition duration-150">
                        <option value="3_meals">3 Meals/Day (B, L, D)</option>
                        <option value="5_small">5 Small Meals/Day (B, MM, L, Snk, D)</option>
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
    });

    const createInitialPlanState = () => {
        // Initialize 7 days of data with the 5-meal structure (max structure)
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

    // Function to move to the next step (from config to meal entry)
    function nextStep() {
        // 1. Capture Global Config
        globalConfig = {
            dietary: $('dietary').value,
            goal: $('goal').value,
            activity: $('activity').value,
            meal_type: $('meal_type').value,
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
        const value = input.value.trim(); // Get the current DOM value
        const numValue = parseInt(value);
        
        // --- 1. Check for Required Fields being Empty ---
        const isRequiredField = ['meal_text', 'calories', 'protein', 'carbs', 'fat'].includes(field);

        if (isRequiredField && value === '') {
            isValid = false;
        } 
        
        // --- 2. Check for Numeric Validity (only if value is not empty) ---
        if (value !== '') {
            if (field === 'meal_text') {
                // Already covered above
            }
            // Calories must be a positive integer > 0
            else if (field === 'calories') {
                if (isNaN(numValue) || numValue <= 0) {
                    isValid = false;
                }
            } 
            // Other Macros must be non-negative integers >= 0
            else if (['protein', 'carbs', 'fat'].includes(field)) {
                if (isNaN(numValue) || numValue < 0) {
                    isValid = false;
                }
            }
        }
        
        if (isValid) {
            input.classList.remove('error-border');
        } else {
            // Apply error styling if validation failed (empty or invalid number)
            input.classList.add('error-border');
        }
    }
    
    // --- BULK DAY VALIDATION (for Next Day/Submit clicks) ---
    function validateCurrentDay(dayIndex) {
        const mealTimes = MEAL_TIMES[globalConfig.meal_type];
        const currentDayMeals = planData[dayIndex];
        let isValid = true;
        
        // Remove previous error message
        document.querySelectorAll('#validation-error-msg').forEach(el => el.remove());
        // Clear previous error highlighting (we re-validate below)
        document.querySelectorAll('.error-border').forEach(el => el.classList.remove('error-border'));
        
        mealTimes.forEach(time => {
            const meal = currentDayMeals[time];
            const mealTextEl = $(`meal_text_${dayIndex}_${time}`);
            
            // Check all inputs for the current meal using the saved state/DOM for highlighting
            const fieldsToValidate = ['meal_text', 'calories', 'protein', 'carbs', 'fat'];
            
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
        });

        if (!isValid) {
            // Show consolidated error message below the day header
            const errorContainer = $('step-2');
            const message = document.createElement('div');
            message.id = 'validation-error-msg';
            message.className = 'error-message p-3 mb-4 bg-red-100 text-red-700 rounded-lg font-medium shadow-md';
            message.innerHTML = '🚨 **Validation Error on Day ' + (dayIndex + 1) + ':** Please ensure all meals have a **Description**, **Calories ($\gt$ 0)**, and all macro values are **non-negative integers ($\ge$ 0)**. Highlighted fields need attention.';
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

            const mealHtml = `
                <div class="p-6 border border-gray-200 rounded-xl shadow-sm bg-white hover:shadow-md transition duration-200">
                    <h4 class="text-xl font-bold text-gray-700 mb-4 capitalize">${time.replace('_', ' ')}</h4>

                    <div class="space-y-3">
                        <label class="block">
                            <span class="text-sm font-medium text-gray-500">Meal Description <span class="text-red-500">*</span></span>
                            <textarea id="meal_text_${currentDay}_${time}" data-day="${currentDay}" data-time="${time}" data-field="meal_text" rows="2" class="w-full border rounded-lg px-4 py-2 text-base focus:ring-1 focus:ring-emerald-500">${meal.meal_text}</textarea>
                        </label>
                        <label class="block">
                            <span class="text-sm font-medium text-gray-500">Quantity/Serving Size (Optional)</span>
                            <input type="text" id="quantity_${currentDay}_${time}" data-day="${currentDay}" data-time="${time}" data-field="quantity" value="${meal.quantity}" placeholder="e.g., 100g chicken breast" class="w-full border rounded-lg px-4 py-2 text-base focus:ring-1 focus:ring-emerald-500">
                        </label>
                    </div>

                    <div class="grid grid-cols-4 gap-3 mt-4 text-center">
                        ${['protein', 'carbs', 'fat', 'calories'].map(field => `
                            <div>
                                <label class="block text-gray-700 font-medium mb-1 capitalize">${field} (g/${field === 'calories' ? 'kcal' : 'g'}) ${field === 'calories' ? '<span class="text-red-500">*</span>' : ''}</label>
                                <input type="number" id="${field}_${currentDay}_${time}" data-day="${currentDay}" data-time="${time}" data-field="${field}" value="${meal[field] > 0 || field === 'calories' ? meal[field] : ''}" min="${field === 'calories' ? '1' : '0'}" step="1" class="w-full border rounded-lg px-2 py-1 text-sm font-mono focus:ring-1 focus:ring-emerald-500">
                            </div>
                        `).join('')}
                    </div>

                    <div class="flex justify-around mt-4 pt-3 border-t">
                        ${['low_carb', 'low_glycemic', 'high_fiber'].map(field => `
                            <label class="inline-flex items-center text-xs text-gray-600 font-medium cursor-pointer">
                                <input type="checkbox" id="${field}_${currentDay}_${time}" data-day="${currentDay}" data-time="${time}" data-field="${field}" ${meal[field] ? 'checked' : ''} class="rounded text-emerald-600 shadow-sm focus:ring-emerald-500">
                                <span class="ml-2 capitalize">${field.replace('_', ' ')}</span>
                            </label>
                        `).join('')}
                    </div>
                </div>
            `;
            container.insertAdjacentHTML('beforeend', mealHtml);
        });

        // Attach event listeners to all newly rendered inputs
        container.querySelectorAll('[data-field]').forEach(input => {
            // Save data on input/change
            input.addEventListener('change', handleInputChange);
            input.addEventListener('input', handleInputChange);
            
            // Validate the input immediately after rendering
            validateField(input);
        });

        updateDayNavigation();
    }

    // Saves the input value to the planData state and triggers real-time validation
    function handleInputChange(e) {
        const input = e.target;
        const day = parseInt(input.dataset.day);
        const time = input.dataset.time;
        const field = input.dataset.field;
        let value;

        if (input.type === 'checkbox') {
            value = input.checked;
        } else if (input.type === 'number') {
            // Read the raw string value from the input
            const rawValue = input.value.trim();
            const numValue = parseInt(rawValue);

            // Determine the value to save to planData (safe integer)
            if (rawValue === '' || isNaN(numValue)) {
                // Save 0 to state for safety (this passes server-side >= 0 check)
                value = 0; 
            } else {
                value = Math.max(0, numValue);
                
                // If the user typed a negative number, clip the input value visually to 0
                if (numValue < 0) {
                   input.value = value; // Visually correct the input
                }
            }
            
        } else {
            // Text fields (meal_text, quantity)
            value = input.value;
        }

        // 1. Save data to the correct day/meal/field
        planData[day][time][field] = value;
        
        // 2. Run real-time validation on the specific input
        validateField(input); 

        // 3. If the overall validation error message is visible, re-validate the current day to update the error message's visibility.
        if ($('validation-error-msg')) {
             // We use a slight delay here to let all real-time field validation complete first
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

        // If on day 1, back button goes to config
        if (currentDay === 0) {
            prevBtn.textContent = '← Back to Config';
            prevBtn.onclick = prevStep;
        } else {
            prevBtn.textContent = '← Previous Day';
            prevBtn.onclick = () => changeDay(-1);
        }
        
        prevBtn.disabled = false; // Always enabled (either to config or previous day)
        nextBtn.disabled = currentDay === 6;

        if (currentDay === 6) {
            // Show submit button on Day 7
            nextBtn.classList.add('hidden');
            submitBtn.classList.remove('hidden');
        } else {
            // Show next day button on Day 1-6
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
        
        // Auto-hide after 10 seconds
        setTimeout(() => {
            statusDiv.classList.add('hidden');
        }, 10000);
    }
    
    // Final submission function
    async function submitPlan() {
        // 1. Client-side validation of the FINAL day (Day 7)
        if (!validateCurrentDay(currentDay)) {
            showStatus('error', 'Please correct the validation errors on Day 7 before submitting.');
            return;
        }

        // Use custom modal for confirmation
        if (!confirm("Are you sure you want to submit the entire 7-day plan? The server will perform a final validation check.")) {
            return;
        }
        
        const submitBtn = $('submit-btn');
        submitBtn.disabled = true;
        submitBtn.textContent = 'Submitting...';

        // Filter the planData to only include meals relevant to the selected meal_type
        const mealTimes = MEAL_TIMES[globalConfig.meal_type];
        const filteredPlan = planData.map(dayMeals => {
            const filteredDay = {};
            mealTimes.forEach(time => {
                filteredDay[time] = dayMeals[time];
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
                prevStep();
            } else {
                // If validation failed on the server, show the error message
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
        // Set up initial day data structure
        // No need to call renderCurrentDay here as it is called in nextStep()
    });

</script>
<?php include 'components/footer.php'; ?>
