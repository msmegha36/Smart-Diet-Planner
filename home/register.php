<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);



// go up one level from /home to /config
include(__DIR__ . '/../config/db_conn.php');



if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Escape and sanitize inputs
    $name = mysqli_real_escape_string($connection, $_POST['name']);
    $age = mysqli_real_escape_string($connection, $_POST['age']);
    $weight = mysqli_real_escape_string($connection, $_POST['weight']);
    $height = mysqli_real_escape_string($connection, $_POST['height']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $pass = mysqli_real_escape_string($connection, $_POST['password']);
    $gender = mysqli_real_escape_string($connection, $_POST['gender']);
    $health_issues = mysqli_real_escape_string($connection, $_POST['health_issues']);
    $dietary = mysqli_real_escape_string($connection, $_POST['dietary']);
    $goal = mysqli_real_escape_string($connection, $_POST['goal']);
    $activity = mysqli_real_escape_string($connection, $_POST['activity']);
    $meal_type = mysqli_real_escape_string($connection, $_POST['meal_type']);
    $type = 1; // user role (1 = normal user, 0 = admin, etc.)

    // Check if email already exists
    $check = "SELECT * FROM reg WHERE email='$email'";
    $res = mysqli_query($connection, $check);
    if (mysqli_num_rows($res) > 0) {
        echo "<script>alert('User already exists!'); window.location='login.php';</script>";
        exit();
    }

    // Hash password
    $hashed_pass = md5($pass);

    // Insert into database
    $sql = "INSERT INTO reg 
        (name, age, weight, height, email, password, gender, health_issues, dietary, goal, activity, meal_type, type) 
        VALUES 
        ('$name', '$age', '$weight', '$height', '$email', '$hashed_pass', '$gender', '$health_issues', '$dietary', '$goal', '$activity', '$meal_type', '$type')";
    
    if (mysqli_query($connection, $sql)) {
        echo "<script>alert('Successfully Registered ✅'); window.location='login.php';</script>";
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
          <div>
            <label class="block text-gray-700 font-medium mb-2">Health Issues</label>
            <input type="text" name="health_issues" placeholder="e.g. Diabetes, Hypertension" 
                   class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
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
          <div>
            <label class="block text-gray-700 font-medium mb-2">Dietary Restriction</label>
            <select name="dietary" required 
                    class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
              <option value="">-- Select --</option>
              <option value="veg">Vegetarian</option>
              <option value="nonveg">Non-Vegetarian</option>
              <option value="vegan">Vegan</option>
            </select>
          </div>
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
          <div>
            <label class="block text-gray-700 font-medium mb-2">Activity Level</label>
            <select name="activity" required 
                    class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
              <option value="">-- Select --</option>
              <option value="sedentary">Sedentary</option>
              <option value="light">Light Activity</option>
              <option value="moderate">Moderate</option>
              <option value="active">Active</option>
            </select>
          </div>
          <div>
            <label class="block text-gray-700 font-medium mb-2">Preferred Meal Type</label>
            <select name="meal_type" required 
                    class="w-full border rounded-lg px-5 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
              <option value="">-- Select --</option>
              <option value="3_meals">3 Meals/Day</option>
              <option value="5_small">5 Small Meals</option>
              <option value="intermittent">Intermittent Fasting</option>
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




<?php include 'components/footer.php'; ?>


