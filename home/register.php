<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// NOTE: We assume 'db_conn.php' exists in the config directory relative to this file's execution.
// This PHP logic remains untouched as it handles the backend registration and diet planning.
include(__DIR__ . '/../config/db_conn.php');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Escape and sanitize inputs
    $name         = mysqli_real_escape_string($connection, $_POST['name']);
    $age          = mysqli_real_escape_string($connection, $_POST['age']);
    $weight       = mysqli_real_escape_string($connection, $_POST['weight']);
    $height       = mysqli_real_escape_string($connection, $_POST['height']);
    $email        = mysqli_real_escape_string($connection, $_POST['email']);
    $pass         = mysqli_real_escape_string($connection, $_POST['password']);
    $gender       = mysqli_real_escape_string($connection, $_POST['gender']);
    $health_issues = isset($_POST['health_issues']) ? implode(', ', $_POST['health_issues']) : 'None';
    $health_issues = mysqli_real_escape_string($connection, $health_issues);

    $dietary      = mysqli_real_escape_string($connection, $_POST['food']);
    $goal         = mysqli_real_escape_string($connection, $_POST['goal']);
    $activity     = mysqli_real_escape_string($connection, $_POST['activity']);
    $meal_type    = mysqli_real_escape_string($connection, $_POST['meal_type']);
    $type         = 1; // user role

    // Check if email already exists
    $check = "SELECT * FROM reg WHERE email='$email'";
    $res = mysqli_query($connection, $check);
    if (mysqli_num_rows($res) > 0) {
        echo "<script>alert('User already exists!'); window.location='login.php';</script>";
        exit();
    }

    // Hash password
    $hashed_pass = md5($pass);

    // Insert user into reg table
    $sql = "INSERT INTO reg 
        (name, age, weight, height, email, password, gender, health_issues, dietary, goal, activity, meal_type, type) 
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_reg = $connection->prepare($sql);
    $stmt_reg->bind_param("siiissssssssi", $name, $age, $weight, $height, $email, $hashed_pass, $gender, $health_issues, $dietary, $goal, $activity, $meal_type, $type);


    if ($stmt_reg->execute()) {
        $user_id = mysqli_insert_id($connection);
        $stmt_reg->close();

        // --- 1. Map Inputs for Calculation ---
        $inputActivity = $activity;
        $currentGoal = $goal;
        $inputDietary = $dietary;

        // --- 2. Correct BMR and TDEE Calculation (Mifflin-St Jeor) ---
        // Calculate BMR
        $bmr = ($gender === 'male')
            ? (10 * $weight) + (6.25 * $height) - (5 * $age) + 5
            : (10 * $weight) + (6.25 * $height) - (5 * $age) - 161;

        $bmrAdjusted = $bmr;
        // Apply Activity Multiplier to get TDEE (Total Daily Energy Expenditure)
        switch ($inputActivity) {
            case 'sedentary': $bmrAdjusted *= 1.2; break;
            case 'light': $bmrAdjusted *= 1.375; break;
            case 'moderate': $bmrAdjusted *= 1.55; break;
            case 'active': $bmrAdjusted *= 1.725; break;
            default: $bmrAdjusted *= 1.2;
        }

        $tdee = round($bmrAdjusted);
        $bmi = $weight / pow($height / 100, 2);
        
        // --- 3. Calculate Final Target Calories & Goal Adjustment ---
        $calorieAdjustment = 0;
        switch ($currentGoal) {
            case 'weight_loss': $calorieAdjustment = -500; break;
            case 'weight_gain': $calorieAdjustment = 500; break;
            case 'muscle_build': $calorieAdjustment = 300; break;
            case 'balanced': $calorieAdjustment = 0; break;
        }

        $targetCalories = $tdee + $calorieAdjustment;

        // Ensure target calories is safe minimum (WHO/medical standards)
        $dailyTarget = max(($gender === 'male' ? 1500 : 1200), round($targetCalories));
        
        $recommendedGoal = $currentGoal;
        $recommendedDietary = $inputDietary;
        $recommendationNote = "";
        $finalHealthFocus = "none"; // Default focus

        // --- 4. Smart Health Adjustment & Health Focus Determination ---
        $health = strtolower($health_issues);

        // Adjust parameters based on detected health issues
        if (str_contains($health, 'diabetes')) {
            $finalHealthFocus = 'diabetes';
            $recommendedDietary = 'veg';
            $recommendationNote .= "For Diabetes, a low-GI vegetarian plan is prioritized. ";
            $dailyTarget = max(1500, $dailyTarget - 300);
        }

        if (str_contains($health, 'hypertension')) {
            $finalHealthFocus = 'hypertension';
            $recommendationNote .= "For Hypertension, a low-sodium plan is prioritized. ";
            $dailyTarget = max(1500, $dailyTarget - 100);
        }

        if (str_contains($health, 'obesity')) {
            $finalHealthFocus = 'obesity';
            if ($currentGoal !== 'weight_loss') {
                $recommendedGoal = 'weight_loss';
                $dailyTarget = max(1400, $dailyTarget - 400);
                $recommendationNote .= "Due to Obesity, the goal has been adjusted to Weight Loss. ";
            }
        }

        if (str_contains($health, 'heart')) {
            $finalHealthFocus = 'heart_disease';
            $recommendedDietary = 'veg';
            $recommendationNote .= "For Heart health, a low-fat vegetarian plan is assigned. ";
            $dailyTarget = max(1500, $dailyTarget - 200);
        }

        if (str_contains($health, 'pcos') || str_contains($health, 'pcod')) {
            $finalHealthFocus = 'pcos';
            $recommendedDietary = 'veg';
            $recommendationNote .= "For PCOS/PCOD, a low-GI vegetarian plan is used. ";
            $dailyTarget = max(1600, $dailyTarget - 150);
        }
        
        if (str_contains($health, 'thyroid') && $dailyTarget < 1700) {
            $dailyTarget = 1700;
            $recommendationNote .= "Thyroid condition detected — adjusted to ensure moderate calorie intake. ";
        }

        // --- 5. Plan Fetching & Insertion (Using new parameters) ---
        $planFound = false;
        $focusesToTry = [$finalHealthFocus, 'none'];
        $finalPlanFocus = '';
        $tempPlan = [];

        foreach ($focusesToTry as $focus) {
            // Attempt to fetch the plan using the current focus
            $planQuery = "
                SELECT 
                    day_number, meal_time, meal_text, quantity, 
                    protein, carbs, fat, calories
                FROM diet_plans 
                WHERE goal=? AND dietary=? AND activity=? AND health_focus=?
                ORDER BY day_number ASC, FIELD(meal_time, 'breakfast','mid_morning','lunch','snack','dinner','snack3')
            ";
            
            $stmt = $connection->prepare($planQuery);
            $stmt->bind_param("ssss", $recommendedGoal, $recommendedDietary, $activity, $focus);
            $stmt->execute();
            $res = $stmt->get_result();
            
            if ($res->num_rows >= 35) { // Check for a reasonable number of meals (7 days * 5 meals minimum)
                while ($meal = $res->fetch_assoc()) {
                    $tempPlan[] = $meal;
                }
                $planFound = true;
                $finalPlanFocus = $focus;
                $stmt->close();
                break; // Exit the loop if plan is found
            }
            
            $stmt->close();
        }

        if ($planFound) {
            // Insert the found plan into user_diet_plans
            foreach ($tempPlan as $meal) {
                $insert = $connection->prepare("
                    INSERT INTO user_diet_plans
                    (user_id, day_number, meal_time, meal_text, protein, carbs, fat, calories)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $insert->bind_param(
                    "iissiiii",
                    $user_id,
                    $meal['day_number'],
                    $meal['meal_time'],
                    $meal['meal_text'],
                    $meal['protein'],
                    $meal['carbs'],
                    $meal['fat'],
                    $meal['calories']
                );
                $insert->execute();
                $insert->close();
            }

            // 🥗 Smart Summary
            $planLabel = ucfirst(str_replace('_', ' ', $recommendedGoal)) . " – " . ucfirst($recommendedDietary) . " Plan" . ($finalPlanFocus !== 'none' ? " (Focus: " . ucfirst($finalPlanFocus) . ")" : "");
            if ($finalPlanFocus !== $finalHealthFocus) {
                 $recommendationNote .= " **Note: A health-specific plan was unavailable, falling back to a general plan.**";
            }

            echo "<script>
                alert('✅ Personalized {$planLabel} generated successfully!\\n'.
                'BMI: ".round($bmi,1)."\\nCalculated TDEE: ".round($tdee)." kcal\\nTarget: ~{$dailyTarget} kcal/day\\n{$recommendationNote}');
                window.location='login.php';
            </script>";

        } else {
            // If no plan was found, even with fallback
            echo "<script>
                alert('❌ Error: No complete 7-day plan found for the combination (Goal: {$recommendedGoal}, Diet: {$recommendedDietary}, Activity: {$activity}, Health Focus: {$finalHealthFocus} or none). Please contact support or try different options.');
                window.location='register.php'; // Redirect back to registration
            </script>";
        }
    } else {
        echo "<script>alert('Error inserting user: " . mysqli_error($connection) . "');</script>";
    }
}
?>


<?php include 'components/head.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>


  <style>
    /* Custom Animations */
    @keyframes spin-slow {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    .animate-spin-slow { animation: spin-slow 25s linear infinite; }

    @keyframes fade-in {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in { animation: fade-in 1s ease-in-out; }
    .animate-fade-in-up { animation: fade-in 1.2s ease-in-out; }

    /* Custom validity message style for better visibility */
    input:invalid:not(:placeholder-shown) {
        border-color: #ef4444; /* red-500 */
    }
    .error-message {
        color: #ef4444;
        margin-top: 0.25rem;
        font-size: 0.875rem;
    }
    /* Style for progress bar steps */
    .progress-step {
        transition: all 0.3s ease;
    }
  </style>

<?php include 'components/navbar.php'; ?>

<!-- Main Form Container -->
<main class="flex items-center justify-center min-h-screen px-6 py-16 bg-gray-50">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl p-12">
    <h1 class="text-3xl font-bold text-emerald-700 mb-8 text-center">Create Your Account</h1>

    <!-- Progress Bar -->
    <div class="flex justify-center mb-10 space-x-4 text-lg font-semibold items-center">
      <span id="progress-step-1" class="progress-step w-10 h-10 flex items-center justify-center rounded-full border border-emerald-600 bg-emerald-600 text-white">1</span>
      <span class="text-gray-400">───</span>
      <span id="progress-step-2" class="progress-step w-10 h-10 flex items-center justify-center rounded-full border border-emerald-600 text-gray-700">2</span>
      <span class="text-gray-400">───</span>
      <span id="progress-step-3" class="progress-step w-10 h-10 flex items-center justify-center rounded-full border border-emerald-600 text-gray-700">3</span>
      <span class="text-gray-400">───</span>
      <span id="progress-step-4" class="progress-step w-10 h-10 flex items-center justify-center rounded-full border border-emerald-600 text-gray-700">4</span>
    </div>

    <form action="register.php" method="POST" class="space-y-10" id="registrationForm">
      
      <!-- STEP 1 -->
      <div id="step1" class="step">
        <h2 class="text-xl font-semibold text-gray-700 mb-6">👤 Personal Information</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div>
            <label class="block text-gray-700 font-medium mb-2">Full Name</label>
            <input type="text" name="name" required 
                   class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-2">Age</label>
            <input type="number" name="age" required min="18" max="100"
                   oninput="validateNumberInput(this, 18, 100)"
                   class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
            <span class="error-message hidden"></span>
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-2">Weight (kg)</label>
            <input type="number" name="weight" required min="30" max="300"
                   oninput="validateNumberInput(this, 30, 300)"
                   class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
            <span class="error-message hidden"></span>
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-2">Height (cm)</label>
            <input type="number" name="height" required min="100" max="250"
                   oninput="validateNumberInput(this, 100, 250)"
                   class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
            <span class="error-message hidden"></span>
          </div>
        </div>
        <div class="flex justify-end mt-8">
          <!-- UPDATED: Use validateAndGoToStep(1, 2) -->
          <button type="button" onclick="validateAndGoToStep(1, 2)" 
                  class="bg-emerald-600 text-white px-8 py-3 text-lg rounded-lg hover:bg-emerald-700 transition">
            Next →
          </button>
        </div>
      </div>

      <!-- STEP 2 -->
      <div id="step2" class="step hidden">
        <h2 class="text-xl font-semibold text-gray-700 mb-6">📧 Account Details</h2>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
          <div>
            <label class="block text-gray-700 font-medium mb-2">Email</label>
            <input type="email" name="email" required 
                   oninput="validateEmail(this)"
                   class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
            <span class="error-message hidden"></span>
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-2">Password</label>
            <input type="password" name="password" required 
                   oninput="validatePassword(this)"
                   class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
            <span class="error-message hidden"></span>
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-2">Gender</label>
            <select name="gender" required 
                    class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
              <option value="">-- Select --</option>
              <option value="male">Male</option>
              <option value="female">Female</option>
              <option value="other">Other</option>
            </select>
          </div>

        <!-- Health Issues Dropdown (Alpine.js) - Keep this as is -->
        <div x-data="{ open: false, selected: [] }" class="relative">
          <label class="block text-gray-700 font-medium mb-2">Health Issues</label>
          <div @click="open = !open"
               class="border rounded-lg px-5 py-3 text-lg bg-white flex justify-between items-center cursor-pointer focus:ring-2 focus:ring-emerald-500">
            <span x-text="selected.length ? selected.join(', ') : 'Select health issues...'"></span>
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
              <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
            </svg>
          </div>
          <div x-show="open" @click.away="open = false"
               class="absolute z-10 mt-2 w-full bg-white border rounded-lg shadow-lg">
            <ul class="max-h-56 overflow-y-auto">
              <template x-for="issue in ['None', 'Diabetes', 'Hypertension', 'Thyroid Disorder', 'Obesity', 'Heart Disease', 'PCOS / PCOD']">
                <li class="px-4 py-2 hover:bg-emerald-50 flex items-center space-x-2">
                  <input type="checkbox" :value="issue" 
                         @change="if($event.target.checked){ selected.push(issue) } else { selected = selected.filter(i => i !== issue) }"
                         class="form-checkbox text-emerald-600 rounded">
                  <span x-text="issue"></span>
                </li>
              </template>
            </ul>
          </div>
          <template x-for="issue in selected">
            <input type="hidden" name="health_issues[]" :value="issue">
          </template>
          <p class="text-sm text-gray-500 mt-2">Select one or more health issues.</p>
        </div>
        <!-- End Health Issues Dropdown -->

        </div>
        <div class="flex justify-between mt-8">
          <button type="button" onclick="nextStep(1)" 
                  class="bg-gray-300 text-gray-700 px-8 py-3 text-lg rounded-lg hover:bg-gray-400 transition">
            ← Back
          </button>
          <!-- UPDATED: Use validateAndGoToStep(2, 3) -->
          <button type="button" onclick="validateAndGoToStep(2, 3)" 
                  class="bg-emerald-600 text-white px-8 py-3 text-lg rounded-lg hover:bg-emerald-700 transition">
            Next →
          </button>
        </div>
      </div>

<!-- STEP 3 -->
<div id="step3" class="step hidden">
  <h2 class="text-xl font-semibold text-gray-700 mb-6">🥗 Diet Preferences</h2>
  <div class="grid grid-cols-1 md:grid-cols-2 gap-8">

    <!-- Food Preference -->
    <div>
      <label class="block text-gray-700 font-medium mb-2">Food Preference</label>
      <select name="food" required 
              class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
        <option value="">-- Select --</option>
        <option value="veg">Vegetarian</option>
        <option value="nonveg">Non-Vegetarian</option>
      </select>
    </div>

    <!-- Fitness Goal -->
    <div>
      <label class="block text-gray-700 font-medium mb-2">Fitness Goal</label>
      <select name="goal" required 
              class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
        <option value="">-- Select --</option>
        <option value="weight_loss">Weight Loss</option>
        <option value="weight_gain">Weight Gain</option>
        <option value="muscle_build">Muscle Building</option>
        <option value="balanced">Balanced Diet</option>
      </select>
    </div>

    <!-- Activity Level -->
    <div>
      <label class="block text-gray-700 font-medium mb-2">Activity Level</label>
      <select name="activity" required 
              class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
        <option value="">-- Select --</option>
        <option value="light">Light Activity</option>
        <option value="moderate">Moderate Activity</option>
        <option value="active">Active</option>
      </select>
    </div>

    <!-- Meal Type -->
    <div>
      <label class="block text-gray-700 font-medium mb-2">Preferred Meal Type</label>
      <select name="meal_type" required 
              class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
        <option value="">-- Select --</option>
        <option value="3_meals">3 Meals/Day</option>
        <option value="5_small">5 Small Meals</option>
      </select>
    </div>

  </div>
  <div class="flex justify-between mt-8">
    <button type="button" onclick="nextStep(2)" 
            class="bg-gray-300 text-gray-700 px-8 py-3 text-lg rounded-lg hover:bg-gray-400 transition">
      ← Back
    </button>
    <!-- UPDATED: Use validateAndGoToStep(3, 4) -->
    <button type="button" onclick="validateAndGoToStep(3, 4)" 
            class="bg-emerald-600 text-white px-8 py-3 text-lg rounded-lg hover:bg-emerald-700 transition">
      Next →
    </button>
  </div>
</div>


      <!-- STEP 4 -->
      <div id="step4" class="step hidden">
        <h2 class="text-xl font-semibold text-gray-700 mb-6">✅ Final Step</h2>
        <p class="text-gray-600 mb-6">Review your details and submit your registration.</p>
        <div class="flex justify-between mt-8">
          <button type="button" onclick="nextStep(3)" 
                  class="bg-gray-300 text-gray-700 px-8 py-3 text-lg rounded-lg hover:bg-gray-400 transition">
            ← Back
          </button>
          <button type="submit" 
                  class="bg-emerald-600 text-white px-8 py-3 text-lg rounded-lg hover:bg-emerald-700 transition">
            Submit
          </button>
        </div>
        <p class="text-center text-gray-600 mt-6">
          Already have an account? 
          <a href="login.php" class="text-emerald-600 font-semibold hover:underline">Login</a>
        </p>
      </div>

    </form>
  </div>
</main>

<?php include 'components/footer.php'; ?>

<!-- Alpine.js is needed for the multi-select dropdown -->
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>


<!-- JavaScript Validation and Step Logic -->
<script>
    // --- STEP NAVIGATION LOGIC ---
    function nextStep(stepNum) {
        const totalSteps = 4;
        for (let i = 1; i <= totalSteps; i++) {
            const stepElement = document.getElementById('step' + i);
            const progressElement = document.getElementById('progress-step-' + i);

            if (stepElement) {
                stepElement.classList.toggle('hidden', i !== stepNum);
            }
            
            if (progressElement) {
                // Update progress bar indicator style
                if (i <= stepNum) {
                    progressElement.classList.add('bg-emerald-600', 'text-white');
                    progressElement.classList.remove('bg-gray-300', 'text-gray-700');
                } else {
                    progressElement.classList.remove('bg-emerald-600', 'text-white');
                    progressElement.classList.add('bg-gray-300', 'text-gray-700');
                }
            }
        }
    }

    // Function to check if all required fields in the current step are valid
    function validateCurrentStep(currentStepNum) {
        const step = document.getElementById('step' + currentStepNum);
        // Find all required inputs/selects within the current step
        const inputs = step.querySelectorAll('[required]');
        let allValid = true;

        inputs.forEach(input => {
            // Trigger the real-time validation functions if they exist (for custom messages)
            if (input.name === 'email' && input.oninput) {
                validateEmail(input);
            } else if (input.name === 'password' && input.oninput) {
                validatePassword(input);
            } else if ((input.name === 'age' || input.name === 'weight' || input.name === 'height') && input.oninput) {
                // Extract min/max from element attributes for the check
                validateNumberInput(input, parseInt(input.min), parseInt(input.max));
            }
            
            // Check browser's built-in validation (which includes the result of setCustomValidity)
            if (!input.checkValidity()) {
                allValid = false;
                // Force the browser to display its native error tooltip briefly
                input.reportValidity(); 
                // Also ensures our custom error span (if present) is updated
                const errorMessageElement = input.nextElementSibling;
                if (errorMessageElement && input.customError) {
                    errorMessageElement.textContent = input.validationMessage;
                    errorMessageElement.classList.remove('hidden');
                }
            }
        });
        return allValid;
    }

    // Combined validation and navigation function
    function validateAndGoToStep(currentStepNum, nextStepNum) {
        if (validateCurrentStep(currentStepNum)) {
            nextStep(nextStepNum);
        }
    }

    // Initialize to step 1
    document.addEventListener('DOMContentLoaded', () => {
        // Start on step 1 and remove the hidden class from the container
        nextStep(1); 
    });

    // --- CUSTOM VALIDATION FUNCTIONS ---
    
    // Helper function to update the visible error span
    function updateErrorMessage(textbox) {
        const errorMessageElement = textbox.nextElementSibling;
        if (errorMessageElement) {
            if (textbox.customError || textbox.validationMessage) {
                 // Prioritize custom message if set, otherwise use native message
                errorMessageElement.textContent = textbox.validationMessage; 
                errorMessageElement.classList.remove('hidden');
            } else {
                errorMessageElement.classList.add('hidden');
            }
        }
    }

    // --- PASSWORD VALIDATION ---
    function validatePassword(textbox) {
        // Clear previous custom error message
        textbox.setCustomValidity(''); 
        const value = textbox.value;
        
        // RegEx for minimum complexity: >= 8 chars, 1 uppercase, 1 lowercase, 1 number
        const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])[a-zA-Z0-9!@#$%^&*()_+={}\[\]:;<>,.?\/\\~-]{8,}$/;

        if (value === '') {
            textbox.setCustomValidity('A password is required!');
        } 
        else if (!passwordPattern.test(value)) {
            textbox.setCustomValidity('Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number.');
        }
        
        updateErrorMessage(textbox);
        return textbox.checkValidity(); 
    }
        
    // --- EMAIL VALIDATION ---
    function validateEmail(textbox) {
        textbox.setCustomValidity('');

        if (textbox.validity.valueMissing) {
            textbox.setCustomValidity('Entering an email address is required!');
        } else if (textbox.validity.typeMismatch) {
            textbox.setCustomValidity('Please enter a valid email address (e.g., user@example.com).');
        }
        
        updateErrorMessage(textbox);
        return textbox.checkValidity();
    } 

    // --- NUMBER INPUT VALIDATION (Age, Weight, Height) ---
    function validateNumberInput(textbox, minVal, maxVal) {
        textbox.setCustomValidity('');
        const value = parseFloat(textbox.value);

        if (textbox.validity.valueMissing) {
            textbox.setCustomValidity('This field is required.');
        } else if (isNaN(value)) {
            textbox.setCustomValidity('Please enter a valid number.');
        } else if (value < minVal) {
            textbox.setCustomValidity(`Value must be at least ${minVal}.`);
        } else if (value > maxVal) {
            textbox.setCustomValidity(`Value must be no more than ${maxVal}.`);
        }

        updateErrorMessage(textbox);
        return textbox.checkValidity();
    }
</script>
