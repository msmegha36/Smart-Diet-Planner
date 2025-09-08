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

<main class="bg-gray-100 w-full pt-10 px-6">
  <h2 class="text-3xl font-bold text-emerald-600 mb-6">➕ Add Diet Plan</h2>

  <?php if (isset($_SESSION['success'])): ?>
    <p class="p-3 mb-4 bg-green-100 text-green-700 rounded"><?= $_SESSION['success']; unset($_SESSION['success']); ?></p>
  <?php elseif (isset($_SESSION['error'])): ?>
    <p class="p-3 mb-4 bg-red-100 text-red-700 rounded"><?= $_SESSION['error']; unset($_SESSION['error']); ?></p>
  <?php endif; ?>

  <div class="bg-white p-6 rounded-xl shadow-lg w-full max-w-3xl">
    <form method="POST" class="space-y-4">
      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block font-semibold">Goal</label>
          <select name="goal" class="w-full border rounded p-2" required>
            <option value="">-- Select --</option>
            <option value="weight_loss">Weight Loss</option>
            <option value="weight_gain">Weight Gain</option>
            <option value="muscle_build">Muscle Build</option>
            <option value="balanced">Balanced</option>
          </select>
        </div>
        <div>
          <label class="block font-semibold">Dietary</label>
          <select name="dietary" class="w-full border rounded p-2" required>
            <option value="">-- Select --</option>
            <option value="veg">Vegetarian</option>
            <option value="nonveg">Non-Vegetarian</option>
            <option value="vegan">Vegan</option>
          </select>
        </div>
        <div>
          <label class="block font-semibold">Activity</label>
          <select name="activity" class="w-full border rounded p-2" required>
            <option value="">-- Select --</option>
            <option value="sedentary">Sedentary</option>
            <option value="light">Light</option>
            <option value="moderate">Moderate</option>
            <option value="active">Active</option>
          </select>
        </div>
        <div>
          <label class="block font-semibold">Meal Type</label>
          <select name="meal_type" class="w-full border rounded p-2" required>
            <option value="">-- Select --</option>
            <option value="3_meals">3 Meals</option>
            <option value="5_small">5 Small Meals</option>
            <option value="intermittent">Intermittent Fasting</option>
          </select>
        </div>
        <div>
          <label class="block font-semibold">Day Number (1-7)</label>
          <input type="number" name="day_number" min="1" max="7" class="w-full border rounded p-2" required>
        </div>
        <div>
          <label class="block font-semibold">Meal Time</label>
          <select name="meal_time" class="w-full border rounded p-2" required>
            <option value="">-- Select --</option>
            <option value="breakfast">Breakfast</option>
            <option value="lunch">Lunch</option>
            <option value="dinner">Dinner</option>
            <option value="snack">Snack</option>
          </select>
        </div>
      </div>

      <div>
        <label class="block font-semibold">Meal Description</label>
        <textarea name="meal_text" rows="4" class="w-full border rounded p-2" required></textarea>
      </div>

      <div class="grid grid-cols-4 gap-4">
        <div>
          <label class="block font-semibold">Protein (g)</label>
          <input type="number" name="protein" class="w-full border rounded p-2">
        </div>
        <div>
          <label class="block font-semibold">Carbs (g)</label>
          <input type="number" name="carbs" class="w-full border rounded p-2">
        </div>
        <div>
          <label class="block font-semibold">Fat (g)</label>
          <input type="number" name="fat" class="w-full border rounded p-2">
        </div>
        <div>
          <label class="block font-semibold">Calories</label>
          <input type="number" name="calories" class="w-full border rounded p-2">
        </div>
      </div>

      <div class="flex justify-end gap-3 mt-4">
        <a href="dietplans.php" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Back</a>
        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700">Add Plan</button>
      </div>
    </form>
  </div>
</main>
