<?php 
session_start();
include 'components/head.php'; 
include 'components/navbar.php'; 
include(__DIR__ . '/../config/db_conn.php'); 

if(!isset($_SESSION['user_id'])){
    header("Location: ../home/login.php");
    exit;
}
$user_id = $_SESSION['user_id'];
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$success = $error = "";
$loadedPlan = [];
$showSaveButton = false;

// Handle Generate Plan submission (fetch from diet_plans table)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['generate_plan'])) {

    $goal      = $_POST['goal'] ?? '';
    $dietary   = $_POST['food'] ?? '';
    $activity  = $_POST['activity'] ?? '';
    $meal_type = $_POST['meal_type'] ?? '';

    if(empty($goal) || empty($dietary) || empty($activity) || empty($meal_type)){
        $error = "Please select all options to generate a plan.";
    } else {
        $loadedPlan = [];
        for($day=1; $day<=7; $day++){
            $stmt = $connection->prepare("
                SELECT * FROM diet_plans 
                WHERE goal=? AND dietary=? AND activity=? AND meal_type=? AND day_number=?
            ");
            $stmt->bind_param("ssssi", $goal, $dietary, $activity, $meal_type, $day);
            $stmt->execute();
            $res = $stmt->get_result();

            $meals = [];
            while($row = $res->fetch_assoc()){
                $meals[] = [
                    'meal_time'=>$row['meal_time'],
                    'meal_text'=>$row['meal_text'],
                    'protein'=>$row['protein'],
                    'carbs'=>$row['carbs'],
                    'fat'=>$row['fat'],
                    'calories'=>$row['calories']
                ];
            }
            $loadedPlan[] = ['day_number'=>$day, 'meals'=>$meals];
        }

        if(!empty($loadedPlan)){
            $success = "✅ Plan generated successfully! You can now save it.";
            $showSaveButton = true;
        } else {
            $error = "No plan found for the selected options.";
        }
    }
}

// Handle Save Plan submission (insert/update user_diet_plans)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_plan'])) {
    if(empty($_POST['plan'])){
        $error = "No plan to save!";
    } else {
        $plan = json_decode($_POST['plan'], true);
        if(!$plan || !is_array($plan)){
            $error = "Invalid plan structure!";
        } else {
            $connection->begin_transaction();
            try {
                // Delete existing user plan completely
                $delStmt = $connection->prepare("DELETE FROM user_diet_plans WHERE user_id=?");
                $delStmt->bind_param("i", $user_id);
                $delStmt->execute();
                $delStmt->close();

                // Insert new plan
                $insertStmt = $connection->prepare("
                    INSERT INTO user_diet_plans 
                    (user_id, day_number, meal_time, meal_text, protein, carbs, fat, calories)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                foreach ($plan as $day) {
                    $day_number = intval($day['day_number']);
                    foreach ($day['meals'] as $meal) {
                        $meal_time  = trim($meal['meal_time']);
                        $meal_text  = trim($meal['meal_text']);
                        $protein    = intval($meal['protein']);
                        $carbs      = intval($meal['carbs']);
                        $fat        = intval($meal['fat']);
                        $calories   = intval($meal['calories']);

                        // Skip empty meals just in case
                        if(empty($meal_time) || empty($meal_text)) continue;

                        $insertStmt->bind_param(
                            "iissiiii",
                            $user_id,
                            $day_number,
                            $meal_time,
                            $meal_text,
                            $protein,
                            $carbs,
                            $fat,
                            $calories
                        );
                        $insertStmt->execute();
                    }
                }

                $insertStmt->close();
                $connection->commit();
                $success = "✅ Plan saved/updated successfully!";
                $showSaveButton = false;
            } catch (Exception $e) {
                $connection->rollback();
                $error = "MySQL error: " . $e->getMessage();
                error_log($e->getMessage());
            }
        }
    }
}

?>

<main class="flex-1 overflow-y-auto p-8 bg-gray-50">

  <?php if($success): ?>
    <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
        <?= $success ?>
    </div>
  <?php endif; ?>

  <?php if($error): ?>
    <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
        <?= $error ?>
    </div>
  <?php endif; ?>

  <!-- Input Form -->
  <section class="bg-white rounded-xl shadow-lg p-6 mb-8">
    <h2 class="text-2xl font-bold text-emerald-700 mb-6">Personalized Nutrition Plan</h2>
    <form method="post" class="grid grid-cols-1 md:grid-cols-2 gap-6">
      
      <!-- Form fields -->
      <div>
        <label class="block text-gray-700 font-medium">Activity Level</label>
        <select name="activity" class="w-full border rounded-lg px-3 py-2 mt-1">
          <option value="light">Lightly Active</option>
          <option value="moderate">Moderately Active</option>
          <option value="active">Very Active</option>
        </select>
      </div>
      <div>
        <label class="block text-gray-700 font-medium">Food Preference</label>
        <select name="food" class="w-full border rounded-lg px-3 py-2 mt-1">
          <option value="veg">Vegetarian</option>
          <option value="nonveg">Non-Vegetarian</option>
        </select>
      </div>
      <div>
        <label class="block text-gray-700 font-medium">Meal Type</label>
        <select name="meal_type" class="w-full border rounded-lg px-3 py-2 mt-1">
          <option value="3_meals">3 Meals</option>
          <option value="5_small">5 Small Meals</option>
        </select>
      </div>
      <div class="md:col-span-2">
        <label class="block text-gray-700 font-medium">Fitness Goal</label>
        <select name="goal" class="w-full border rounded-lg px-3 py-2 mt-1">
          <option value="weight_loss">Weight Loss</option>
          <option value="weight_gain">Weight Gain</option>
          <option value="muscle_build">Muscle Building</option>
          <option value="balanced">Balanced Diet</option>
        </select>
      </div>

      <!-- Generate Plan button -->
      <div class="md:col-span-2">
        <button type="submit" name="generate_plan" class="w-full bg-sky-600 text-white px-4 py-2 rounded-lg hover:bg-sky-700">
          📝 Generate Plan
        </button>
      </div>

      <!-- Save Plan button at the bottom, only if plan loaded -->
      <?php if($showSaveButton): ?>
        <div class="md:col-span-2">
          <input type="hidden" name="plan" value='<?= htmlspecialchars(json_encode($loadedPlan)) ?>'>
          <button type="submit" name="save_plan" class="w-full bg-emerald-600 text-white px-4 py-2 rounded-lg hover:bg-emerald-700">
            💾 Save Plan
          </button>
        </div>
      <?php endif; ?>

    </form>
  </section>

  <!-- Display loaded plan -->
  <?php if(!empty($loadedPlan)): ?>
    <section class="bg-white rounded-xl shadow-lg p-6 mb-8">
      <h3 class="text-xl font-bold text-emerald-700 mb-4">7-Day Meal Plan</h3>
      <?php foreach($loadedPlan as $day): ?>
        <div class="border rounded-lg shadow-md overflow-hidden mb-4">
          <div class="bg-emerald-600 text-white px-4 py-2 font-bold">Day <?= $day['day_number'] ?></div>
          <div class="divide-y divide-gray-200 bg-gray-50">
            <?php foreach($day['meals'] as $meal): ?>
              <div class="p-3 hover:bg-gray-100 transition flex flex-col md:flex-row justify-between items-start md:items-center gap-2">
                <!-- Meal Time Badge -->
                <span class="px-2 py-0.5 rounded-full text-xs font-semibold bg-emerald-200 text-emerald-800">
                  <?= strtoupper($meal['meal_time']) ?>
                </span>
                <!-- Meal Text -->
                <span class="font-medium text-gray-800 flex-1"><?= htmlspecialchars($meal['meal_text']) ?></span>
                <!-- Macros -->
                <div class="flex gap-2 text-sm mt-1 md:mt-0">
                  <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded">P: <?= $meal['protein'] ?>g</span>
                  <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded">C: <?= $meal['carbs'] ?>g</span>
                  <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded">F: <?= $meal['fat'] ?>g</span>
                  <span class="bg-green-100 text-green-800 px-2 py-0.5 rounded">Cal: <?= $meal['calories'] ?>kcal</span>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        </div>
      <?php endforeach; ?>
    </section>
  <?php endif; ?>

</main>
