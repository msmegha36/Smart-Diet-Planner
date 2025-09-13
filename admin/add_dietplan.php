<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . '/../config/db_conn.php');

// ✅ Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle Add Diet Plan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $goal = $_POST['goal'];
    $dietary = $_POST['dietary'];
    $activity = $_POST['activity'];
    $meal_type = $_POST['meal_type'];
    $day_number = intval($_POST['day_number']);
    $meal_time = $_POST['meal_time'];
    $meal_text = $_POST['meal_text'];
    $protein = intval($_POST['protein']);
    $carbs = intval($_POST['carbs']);
    $fat = intval($_POST['fat']);
    $calories = intval($_POST['calories']);

    $insert = $connection->prepare("INSERT INTO diet_plans 
        (goal, dietary, activity, meal_type, day_number, meal_time, meal_text, protein, carbs, fat, calories) 
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $insert->bind_param("ssssissiiii", 
        $goal, $dietary, $activity, $meal_type, $day_number, $meal_time, $meal_text, $protein, $carbs, $fat, $calories
    );

    if ($insert->execute()) {
        $_SESSION['success'] = "✅ Diet plan added successfully!";
    } else {
        $_SESSION['error'] = "❌ Error adding diet plan.";
    }

    header("Location: add_dietplan.php");
    exit();
}
?>

<?php include 'components/head.php'; ?>
<?php include 'components/dashboard-nav.php'; ?>

<main class="bg-gray-100 min-h-screen p-6">
  <div class="max-w-7xl mx-auto bg-white p-8 rounded-xl shadow-lg w-full">
    <h2 class="text-3xl font-bold text-emerald-600 mb-8 text-center">➕ Add Diet Plan</h2>

    <?php if (isset($_SESSION['success'])): ?>
      <p class="p-3 mb-4 bg-green-100 text-green-700 rounded"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
    <?php elseif (isset($_SESSION['error'])): ?>
      <p class="p-3 mb-4 bg-red-100 text-red-700 rounded"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
    <?php endif; ?>

    <form method="POST" class="space-y-6 w-full">

      <!-- Step 1: Preferences -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
        <div>
          <label class="block text-gray-700 font-medium mb-2">Food Preference</label>
          <select name="dietary" required
                  class="w-full border rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
            <option value="">-- Select --</option>
            <option value="veg">Vegetarian</option>
            <option value="nonveg">Non-Vegetarian</option>
          </select>
        </div>

        <div>
          <label class="block text-gray-700 font-medium mb-2">Fitness Goal</label>
          <select name="goal" required
                  class="w-full border rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
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
                  class="w-full border rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
            <option value="">-- Select --</option>
            <option value="light">Light Activity</option>
            <option value="moderate">Moderate Activity</option>
            <option value="active">Active</option>
          </select>
        </div>

        <div>
          <label class="block text-gray-700 font-medium mb-2">Preferred Meal Type</label>
          <select name="meal_type" required
                  class="w-full border rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
            <option value="">-- Select --</option>
            <option value="3_meals">3 Meals/Day</option>
            <option value="5_small">5 Small Meals</option>
          </select>
        </div>
      </div>

      <!-- Step 2: Day & Meal Time -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <div>
          <label class="block text-gray-700 font-medium mb-2">Day Number (1-7)</label>
          <input type="number" name="day_number" min="1" max="7"
                 class="w-full border rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-emerald-500" required>
        </div>

        <div>
          <label class="block text-gray-700 font-medium mb-2">Meal Time</label>
          <select name="meal_time" required
                  class="w-full border rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
            <option value="">-- Select --</option>
            <option value="breakfast">Breakfast</option>
            <option value="lunch">Lunch</option>
            <option value="dinner">Dinner</option>
            <option value="snack">Snack</option>
          </select>
        </div>
      </div>

      <!-- Step 3: Meal Description -->
      <div>
        <label class="block text-gray-700 font-medium mb-2">Meal Description</label>
        <textarea name="meal_text" rows="4"
                  class="w-full border rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-emerald-500" required></textarea>
      </div>

      <!-- Step 4: Macros -->
      <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 gap-6">
        <div>
          <label class="block text-gray-700 font-medium mb-2">Protein (g)</label>
          <input type="number" name="protein"
                 class="w-full border rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-2">Carbs (g)</label>
          <input type="number" name="carbs"
                 class="w-full border rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-2">Fat (g)</label>
          <input type="number" name="fat"
                 class="w-full border rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
        </div>
        <div>
          <label class="block text-gray-700 font-medium mb-2">Calories</label>
          <input type="number" name="calories"
                 class="w-full border rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-emerald-500">
        </div>
      </div>

      <!-- Buttons -->
      <div class="flex flex-col sm:flex-row justify-center gap-4 mt-6">
        <a href="dietplans.php" class="px-6 py-3 bg-gray-400 text-white rounded-lg hover:bg-gray-500 text-center">Back</a>
        <button type="submit"
                class="px-6 py-3 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 text-center">Add Plan</button>
      </div>

    </form>
  </div>
</main>
</div>