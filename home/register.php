<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);


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
    
    // Health issues handling: Implode array into comma-separated string
    // Since Alpine manages hidden inputs named health_issues[], we implode the array.
    $health_issues = isset($_POST['health_issues']) ? implode(', ', $_POST['health_issues']) : 'None';
    // Ensure 'None' is handled if other issues are present
    $health_issues = (str_contains($health_issues, 'None') && count($_POST['health_issues']) > 1) 
                     ? trim(str_replace('None,', '', $health_issues), ', ') 
                     : $health_issues;
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

    // Hash password (using MD5 for existing system compatibility)
    $hashed_pass = md5($pass);

    // Insert user into reg table
    $sql = "INSERT INTO reg 
        (name, age, weight, height, email, password, gender, health_issues, dietary, goal, activity, meal_type, type) 
        VALUES 
        (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    
    $stmt_reg = $connection->prepare($sql);
    $stmt_reg->bind_param("siiissssssssi", $name, $age, $weight, $height, $email, $hashed_pass, $gender, $health_issues, $dietary, $goal, $activity, $meal_type, $type);


    if ($stmt_reg->execute()) {
        $stmt_reg->close();
        
        echo "<script>alert('✅ Registration successful! Please log in to continue.'); window.location='login.php';</script>";

    } else {
        echo "<script>alert('Error inserting user: " . mysqli_error($connection) . "');</script>";
    }
}
?>


<?php include 'components/head.php'; ?>
<script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>


  <style>
    :root { 
        font-family: 'Inter', sans-serif; 
    } 
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
    input:invalid:not(:placeholder-shown), select:invalid:not([value=""]) { 
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
<main class="flex items-center justify-center min-h-screen px-4 sm:px-6 py-16 bg-gray-50">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl p-8 sm:p-12">
    <h1 class="text-3xl font-bold text-emerald-700 mb-8 text-center">Create Your Account</h1>

    <!-- Progress Bar -->
    <div class="flex justify-center mb-10 space-x-2 sm:space-x-4 text-sm sm:text-lg font-semibold items-center">
      <span id="progress-step-1" class="progress-step w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-full border border-emerald-600 bg-emerald-600 text-white">1</span>
      <span class="text-gray-400">───</span>
      <span id="progress-step-2" class="progress-step w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-full border border-emerald-600 text-gray-700">2</span>
      <span class="text-gray-400">───</span>
      <span id="progress-step-3" class="progress-step w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-full border border-emerald-600 text-gray-700">3</span>
      <span class="text-gray-400">───</span>
      <span id="progress-step-4" class="progress-step w-8 h-8 sm:w-10 sm:h-10 flex items-center justify-center rounded-full border border-emerald-600 text-gray-700">4</span>
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
          <button type="button" onclick="validateAndGoToStep(1, 2)" 
                  class="bg-emerald-600 text-white px-8 py-3 text-lg rounded-lg hover:bg-emerald-700 transition shadow-md">
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

          <!-- Health Issues Dropdown (Alpine.js) - CRITICAL for GOAL RESTRICTION -->
          <div x-data="{  
              open: false,  
              selected: ['None'], // Initialize default to 'None' 
              issues: ['None', 'Diabetes', 'Hypertension', 'Thyroid Disorder', 'Obesity', 'Heart Disease', 'PCOS / PCOD'],
              toggleIssue(issue) {
                  const index = this.selected.indexOf(issue); 

                  if (issue === 'None') {
                      // 'None' is exclusive
                      this.selected = ['None'];
                  } else if (index > -1) { 
                      // Deselect the item
                      this.selected.splice(index, 1);
                  } else { 
                      // Select the item and remove 'None' if it exists
                      const noneIndex = this.selected.indexOf('None');
                      if (noneIndex > -1) {
                          this.selected.splice(noneIndex, 1);
                      }
                      this.selected.push(issue);
                  } 

                  // If array is empty after toggling, revert to 'None'
                  if (this.selected.length === 0) {
                      this.selected = ['None'];
                  }

                  // Force reactivity update. The $watch will call updateGoalOptions.
                  this.selected = [...this.selected]; 
                  
              },
              init() {  
                  // Initial call and watch for reactivity 
                  $watch('selected', (value) => { 
                      // This ensures that if the user selects something, 'None' is removed.
                      if (value.length > 1 && value.includes('None')) { 
                          this.selected = value.filter(i => i !== 'None'); 
                          // Prevent infinite loop by not calling updateGoalOptions here, 
                          // the watcher will fire again with the filtered list.
                          return;
                      } else if (value.length === 0) { 
                          this.selected = ['None']; 
                           // Prevent infinite loop
                           return;
                      } 
                      
                      // Now call the global filtering function with the definitive list
                      if (typeof updateGoalOptions === 'function') {
                          updateGoalOptions(this.selected);
                      }
                  }); 
                  
                  // Initial setup: call updateGoalOptions once the Alpine component is ready
                  if (typeof updateGoalOptions === 'function') {
                      updateGoalOptions(this.selected);
                  }
              } 
          }" class="relative">
              <label class="block text-gray-700 font-medium mb-2">Health Issues</label>
              <div @click="open = !open"
                  class="border rounded-lg px-5 py-3 text-lg bg-white flex justify-between items-center cursor-pointer focus:ring-2 focus:ring-emerald-500 shadow-sm">
                  <!-- Display selected issues, ignoring 'None' if others exist -->
                  <span x-text="selected.filter(i => i !== 'None').length ? selected.filter(i => i !== 'None').join(', ') : 'None selected'"></span>
                  <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
                      <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
                  </svg>
              </div>
              <div x-show="open" @click.away="open = false"
                  class="absolute z-10 mt-2 w-full bg-white border rounded-lg shadow-lg">
                  <ul class="max-h-56 overflow-y-auto">
                      <template x-for="issue in issues" :key="issue">
                          <li class="px-4 py-2 hover:bg-emerald-50 flex items-center space-x-2 cursor-pointer" 
                              @click.prevent="toggleIssue(issue)"
                              >
                              <input type="checkbox" :value="issue" :checked="selected.includes(issue)"
                                  class="form-checkbox text-emerald-600 rounded pointer-events-none">
                              <span x-text="issue"></span>
                          </li>
                      </template>
                  </ul>
              </div>
              <!-- Hidden inputs for submission -->
              <template x-for="issue in selected" :key="issue">
                  <!-- We use a hidden input array for PHP submission -->
                  <input type="hidden" name="health_issues[]" :value="issue">
              </template>
              <p class="text-sm text-gray-500 mt-2">Select one or more health issues.</p>
          </div>
          <!-- End Health Issues Dropdown -->

        </div>
        <div class="flex justify-between mt-8">
          <button type="button" onclick="nextStep(1)" 
                  class="bg-gray-300 text-gray-700 px-8 py-3 text-lg rounded-lg hover:bg-gray-400 transition shadow-md">
            ← Back
          </button>
          <button type="button" onclick="validateAndGoToStep(2, 3)" 
                  class="bg-emerald-600 text-white px-8 py-3 text-lg rounded-lg hover:bg-emerald-700 transition shadow-md">
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

    <!-- Fitness Goal (Dynamically updated by JS) -->
    <div>
      <label class="block text-gray-700 font-medium mb-2">Fitness Goal</label>
      <select name="goal" required id="goalSelect"
              class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
        <option value="">-- Select --</option>
        <!-- Options will be populated by JavaScript -->
      </select>
      <!-- Message for goal restriction -->
      <p id="goalRestrictionMessage" class="error-message hidden mt-2 text-sm font-medium"></p>
    </div>

    <!-- Activity Level -->
    <div>
      <label class="block text-gray-700 font-medium mb-2">Activity Level</label>
      <select name="activity" required 
              class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
        <option value="">-- Select --</option>
        <option value="light">Light Activity (Desk job, little exercise)</option>
        <option value="moderate">Moderate Activity (Active job, 3-4 gym sessions/week)</option>
        <option value="active">Active (Heavy physical labor, daily intense training)</option>
      </select>
    </div>

    <!-- Meal Type -->
    <div>
      <label class="block text-gray-700 font-medium mb-2">Preferred Meal Type</label>
      <select name="meal_type" required 
              class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
        <option value="">-- Select --</option>
        <option value="3_meals">3 Main Meals (Breakfast, Lunch, Dinner)</option>
        <option value="5_small">5 Small Meals (3 Main + 2 Snacks)</option>
      </select>
    </div>

  </div>
  <div class="flex justify-between mt-8">
    <button type="button" onclick="nextStep(2)" 
            class="bg-gray-300 text-gray-700 px-8 py-3 text-lg rounded-lg hover:bg-gray-400 transition shadow-md">
      ← Back
    </button>
    <button type="button" onclick="validateAndGoToStep(3, 4)" 
            class="bg-emerald-600 text-white px-8 py-3 text-lg rounded-lg hover:bg-emerald-700 transition shadow-md">
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
                  class="bg-gray-300 text-gray-700 px-8 py-3 text-lg rounded-lg hover:bg-gray-400 transition shadow-md">
            ← Back
          </button>
          <button type="submit" 
                  class="bg-emerald-600 text-white px-8 py-3 text-lg rounded-lg hover:bg-emerald-700 transition shadow-lg">
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
    // Store original goal options for restoration 
    const originalGoalOptions = [ 
        { value: 'weight_loss', text: 'Weight Loss' }, 
        { value: 'weight_gain', text: 'Weight Gain' }, 
        { value: 'muscle_build', text: 'Muscle Building' }, 
        { value: 'balanced', text: 'Balanced Diet' }, 
    ]; 
     
    // --- CONDITIONAL GOAL LOGIC --- 
    function updateGoalOptions(selectedIssues) { 
        const goalSelect = document.getElementById('goalSelect'); 
        const messageElement = document.getElementById('goalRestrictionMessage'); 
         
        if (!goalSelect || !messageElement) return; 

        const restrictiveIssues = [ 
            'Diabetes',  
            'Hypertension',  
            'Obesity',  
            'Heart Disease',  
            'PCOS / PCOD' 
        ]; 
         
        // Check if any restrictive issue is selected (ignoring 'None' if others exist) 
        const issuesForCheck = selectedIssues.filter(issue => issue !== 'None');
        const isRestricted = issuesForCheck.some(issue => restrictiveIssues.includes(issue)); 
         
        // Save the current value before clearing 
        const currentValue = goalSelect.value; 
        
        // Temporarily store the options to ensure 'Select' is always first
        const optionsToKeep = [];
        optionsToKeep.push('<option value="">-- Select --</option>'); // Always keep the default option

        if (isRestricted) { 
            // --- Restriction Logic: Only show non-aggressive goals --- 
            const restrictedGoals = ['weight_gain', 'muscle_build']; 
            let restrictionText = "⚠️ Goals focused on high caloric surplus (Weight Gain / Muscle Building) are disabled due to selected health issues."; 

            // Add only non-restricted options (Weight Loss, Balanced Diet) 
            originalGoalOptions.forEach(option => { 
                if (!restrictedGoals.includes(option.value)) { 
                    optionsToKeep.push(`<option value="${option.value}">${option.text}</option>`); 
                } 
            }); 
             
            // Rebuild the select options
            goalSelect.innerHTML = optionsToKeep.join('');

            // Clear goal selection if the previously selected goal is now restricted
            if (restrictedGoals.includes(currentValue)) { 
                goalSelect.value = ''; 
                // Set custom validity to force a selection 
                goalSelect.setCustomValidity("Please select an available goal."); 
            } else if (currentValue && !restrictedGoals.includes(currentValue)) {
                // Restore selection if it's a valid, non-restricted option
                goalSelect.value = currentValue;
            } else {
                 goalSelect.value = '';
            }


            messageElement.innerHTML = restrictionText; 
            messageElement.classList.remove('hidden'); 

        } else { 
            // --- No Restriction Logic: Show all options --- 
             
            // Add all original options back 
            originalGoalOptions.forEach(option => { 
                optionsToKeep.push(`<option value="${option.value}">${option.text}</option>`);
            }); 
            
            // Rebuild the select options
            goalSelect.innerHTML = optionsToKeep.join('');

            // Restore the previously selected value 
            if (currentValue && goalSelect.querySelector(`option[value="${currentValue}"]`)) { 
                 goalSelect.value = currentValue; 
            } else {
                 goalSelect.value = '';
            }
             
            // Clear custom validity and hide message 
            goalSelect.setCustomValidity("");  
            messageElement.classList.add('hidden'); 
            messageElement.textContent = ''; 
        } 

        // Trigger validation if the goal select is currently visible 
        if (!document.getElementById('step3').classList.contains('hidden')) { 
            goalSelect.reportValidity(); 
        } 
    } 

    // --- STEP NAVIGATION LOGIC ---
    function nextStep(stepNum) {
        const totalSteps = 4;
        for (let i = 1; i <= totalSteps; i++) {
            const stepElement = document.getElementById('step' + i);
            const progressElement = document.getElementById('progress-step-' + i);

            if (stepElement) {
                stepElement.classList.toggle('hidden', i !== stepNum);
                if (i === stepNum) {
                     stepElement.classList.add('animate-fade-in'); // Apply animation on display
                } else {
                     stepElement.classList.remove('animate-fade-in');
                }
            }
            
            if (progressElement) {
                // Update progress bar indicator style
                if (i <= stepNum) {
                    progressElement.classList.add('bg-emerald-600', 'text-white');
                    progressElement.classList.remove('text-gray-700', 'border-emerald-600');
                } else {
                    progressElement.classList.remove('bg-emerald-600', 'text-white');
                    progressElement.classList.add('border-emerald-600', 'text-gray-700');
                }
            }
        }
         
        // CRITICAL FIX: When navigating to step 3, reliably read the current selection from the hidden inputs
        if (stepNum === 3) { 
            // Read values from the hidden inputs generated by Alpine in step 2
            const hiddenInputs = document.querySelectorAll('#step2 input[name="health_issues[]"]');
            let selectedIssues = Array.from(hiddenInputs).map(input => input.value).filter(v => v); 
            
            // Ensure the array is never empty for the function call
            if (selectedIssues.length === 0) {
                 selectedIssues = ['None'];
            }
            
            updateGoalOptions(selectedIssues); 
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
            }
             
            // Custom logic for SELECT elements which don't trigger native error messages well 
            if (input.tagName === 'SELECT' && input.value === '') { 
                 input.setCustomValidity('Please select an option.'); 
                 input.reportValidity(); 
                 allValid = false; 
            } else if (input.tagName === 'SELECT') { 
                input.setCustomValidity(''); // Clear if valid 
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
        if (errorMessageElement && errorMessageElement.classList.contains('error-message')) {
            // Prioritize custom message if set, otherwise use native message 
            if (textbox.validationMessage && !textbox.checkValidity()) {  
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
        textbox.setCustomValidity("");
        // Simple regex check for email format
        const emailPattern = /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$/;
        if (!emailPattern.test(textbox.value)) {
            textbox.setCustomValidity("Please enter a valid email address (e.g., user@example.com).");
        } else {
            textbox.setCustomValidity("");
        }
        updateErrorMessage(textbox);
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
