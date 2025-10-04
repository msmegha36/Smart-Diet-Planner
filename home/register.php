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
    //$health_issues= mysqli_real_escape_string($connection, $_POST['health_issues']);
    $health_issues = isset($_POST['health_issues']) ? implode(', ', $_POST['health_issues']) : 'None';
$health_issues = mysqli_real_escape_string($connection, $health_issues);

    $dietary      = mysqli_real_escape_string($connection, $_POST['food']); // updated field name
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
        ('$name', '$age', '$weight', '$height', '$email', '$hashed_pass', '$gender', '$health_issues', '$dietary', '$goal', '$activity', '$meal_type', '$type')";
    


if (mysqli_query($connection, $sql)) {
    $user_id = mysqli_insert_id($connection);

    // 🧩 Basic Calorie & BMI Setup
    $bmi = $weight / pow($height / 100, 2);
    $targetCalories = 10 * $weight + 6.25 * $height - 5 * $age + ($gender === 'male' ? 5 : -161);
    
    // Adjust based on goal
    if ($goal === 'weight_loss') {
        $targetCalories -= 400;
    } elseif ($goal === 'weight_gain') {
        $targetCalories += 400;
    }
    
    // Adjust based on activity
    switch ($activity) {
        case 'sedentary': $targetCalories *= 1.2; break;
        case 'light': $targetCalories *= 1.375; break;
        case 'moderate': $targetCalories *= 1.55; break;
        case 'active': $targetCalories *= 1.725; break;
        default: $targetCalories *= 1.2; break;
    }
    
    $targetCalories = round($targetCalories);
    $recommendedGoal = $goal;
    $recommendedDietary = $dietary;
    $recommendationNote = "";

    // ⚕️ Smart Health Adjustment
    $health = strtolower($health_issues);

    // Diabetes — lower carbs, prefer veg or low-carb
    if (str_contains($health, 'diabetes')) {
        $recommendedDietary = 'veg';
        $recommendationNote .= "For Diabetes, we’ve switched to a low-carb vegetarian plan. ";
        $targetCalories = max(1500, $targetCalories - 300);
    }

    // Hypertension — low sodium, avoid non-veg fats
    if (str_contains($health, 'hypertension')) {
        $recommendedDietary = 'veg';
        $recommendationNote .= "For Hypertension, we’ve applied a low-sodium vegetarian plan. ";
        $targetCalories = max(1500, $targetCalories - 200);
    }

    // Obesity — force weight_loss
    if (str_contains($health, 'obesity') && $goal === 'weight_gain') {
        $recommendedGoal = 'weight_loss';
        $recommendationNote .= "For Obesity, we’ve switched your goal to Weight Loss. ";
        $targetCalories = max(1400, $targetCalories - 400);
    }

    // Heart Disease — low fat
    if (str_contains($health, 'heart')) {
        $recommendedDietary = 'veg';
        $recommendationNote .= "For Heart health, we’ve assigned a low-fat vegetarian plan. ";
        $targetCalories = max(1500, $targetCalories - 250);
    }

    // Thyroid — balanced plan, avoid too low calories
    if (str_contains($health, 'thyroid') && $targetCalories < 1500) {
        $targetCalories = 1700;
        $recommendationNote .= "Thyroid condition detected — adjusted to moderate calorie balanced plan. ";
    }

    // PCOS/PCOD — low GI, moderate carbs
    if (str_contains($health, 'pcos') || str_contains($health, 'pcod')) {
        $recommendedDietary = 'veg';
        $recommendationNote .= "For PCOS/PCOD, we’ve used a low-GI vegetarian plan. ";
        $targetCalories = max(1600, $targetCalories - 200);
    }

    // ✅ Fetch optimized 7-day plan
    for ($day = 1; $day <= 7; $day++) {
        $stmt = $connection->prepare("
            SELECT * FROM diet_plans 
            WHERE goal=? AND dietary=? AND activity=? AND day_number=?
            ORDER BY FIELD(meal_time, 'breakfast','mid_morning','lunch','snack','dinner')
        ");
        $stmt->bind_param("sssi", $recommendedGoal, $recommendedDietary, $activity, $day);
        $stmt->execute();
        $res = $stmt->get_result();

        while ($meal = $res->fetch_assoc()) {
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
        $stmt->close();
    }

    // 🥗 Smart Summary
    $planLabel = ucfirst(str_replace('_', ' ', $recommendedGoal)) . " – " . ucfirst($recommendedDietary) . " Plan";

    echo "<script>
        alert('✅ Personalized {$planLabel} generated successfully!\\n'.
        'BMI: ".round($bmi,1)."\\nTarget: ~{$targetCalories} kcal/day\\n{$recommendationNote}');
        window.location='login.php';
    </script>";
} else {
    echo "<script>alert('Error: " . mysqli_error($connection) . "');</script>";
}
}
?>



<?php include 'components/head.php'; ?>



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
  </style>

  <script>
    function nextStep(step) {
      document.querySelectorAll(".step").forEach((el) => el.classList.add("hidden"));
      document.getElementById("step" + step).classList.remove("hidden");

      // Update progress bar
      document.querySelectorAll(".progress-step").forEach((el, i) => {
        el.classList.remove("bg-emerald-600", "text-white");
        if (i < step) el.classList.add("bg-emerald-600", "text-white");
      });
    }

        function nextStep(step) {
      document.querySelectorAll(".step").forEach((el) => el.classList.add("hidden"));
      document.getElementById("step" + step).classList.remove("hidden");
    }
  </script>

<?php include 'components/navbar.php'; ?>





<!-- Main Form Container -->
<main class="flex items-center justify-center min-h-screen px-6 py-16 bg-gray-50">
  <div class="bg-white rounded-2xl shadow-2xl w-full max-w-4xl p-12">
    <h1 class="text-3xl font-bold text-emerald-700 mb-8 text-center">Create Your Account</h1>

    <!-- Progress Bar -->
    <div class="flex justify-center mb-10 space-x-4 text-lg font-semibold">
      <span id="p1" class="progress-step w-10 h-10 flex items-center justify-center rounded-full border border-emerald-600">1</span>
      <span>───</span>
      <span id="p2" class="progress-step w-10 h-10 flex items-center justify-center rounded-full border border-emerald-600">2</span>
      <span>───</span>
      <span id="p3" class="progress-step w-10 h-10 flex items-center justify-center rounded-full border border-emerald-600">3</span>
      <span>───</span>
      <span id="p4" class="progress-step w-10 h-10 flex items-center justify-center rounded-full border border-emerald-600">4</span>
    </div>

    <form action="register.php" method="POST" class="space-y-10">
      
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
            <input type="number" name="age" required 
                   class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-2">Weight (kg)</label>
            <input type="number" name="weight" required 
                   class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-2">Height (cm)</label>
            <input type="number" name="height" required 
                   class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
          </div>
        </div>
        <div class="flex justify-end mt-8">
          <button type="button" onclick="nextStep(2)" 
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
                   class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-2">Password</label>
            <input type="password" name="password" required 
                   class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
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



<div x-data="{ open: false, selected: [] }" class="relative">
  <label class="block text-gray-700 font-medium mb-2">Health Issues</label>

  <!-- Dropdown button -->
  <div @click="open = !open"
       class="border rounded-lg px-5 py-3 text-lg bg-white flex justify-between items-center cursor-pointer focus:ring-2 focus:ring-emerald-500">
    <span x-text="selected.length ? selected.join(', ') : 'Select health issues...'"></span>
    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-600" viewBox="0 0 20 20" fill="currentColor">
      <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 10.94l3.71-3.71a.75.75 0 111.06 1.06l-4.24 4.25a.75.75 0 01-1.06 0L5.21 8.29a.75.75 0 01.02-1.08z" clip-rule="evenodd" />
    </svg>
  </div>

  <!-- Dropdown list -->
  <div x-show="open" @click.away="open = false"
       class="absolute z-10 mt-2 w-full bg-white border rounded-lg shadow-lg">
    <ul class="max-h-56 overflow-y-auto">
      <template x-for="issue in ['None', 'Diabetes', 'Hypertension', 'Thyroid Disorder', 'Obesity', 'Heart Disease', 'PCOS / PCOD']">
        <li class="px-4 py-2 hover:bg-emerald-50 flex items-center space-x-2">
          <input type="checkbox" :value="issue" 
                 @change="if($event.target.checked){ selected.push(issue) } else { selected = selected.filter(i => i !== issue) }"
                 class="form-checkbox text-emerald-600">
          <span x-text="issue"></span>
        </li>
      </template>
    </ul>
  </div>

  <!-- Hidden input to submit selected items -->
  <template x-for="issue in selected">
    <input type="hidden" name="health_issues[]" :value="issue">
  </template>

  <p class="text-sm text-gray-500 mt-2">Select one or more health issues.</p>
</div>






        </div>
        <div class="flex justify-between mt-8">
          <button type="button" onclick="nextStep(1)" 
                  class="bg-gray-300 text-gray-700 px-8 py-3 text-lg rounded-lg hover:bg-gray-400 transition">
            ← Back
          </button>
          <button type="button" onclick="nextStep(3)" 
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
    <button type="button" onclick="nextStep(4)" 
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

<script>


  const menuBtn = document.getElementById("menu-btn");
  const mobileMenu = document.getElementById("mobile-menu");

  if(menuBtn){
    menuBtn.addEventListener("click", () => {
      mobileMenu.classList.toggle("hidden");
    });
  }

  // initialize step 1 as active
  nextStep(1);


  
</script>
<script src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js" defer></script>




<?php include 'components/footer.php'; ?>


