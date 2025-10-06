<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// IMPORTANT: Ensure this path is correct for your environment
include(__DIR__ . '/../config/db_conn.php');

// ✅ Check if admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle Update Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    // 1. Sanitize and validate input data
    $id = intval($_POST['update_id']);
    $goal = trim($_POST['goal']);
    $dietary = trim($_POST['dietary']);
    $activity = trim($_POST['activity']);
    $meal_type = trim($_POST['meal_type']);
    $day_number = intval($_POST['day_number']);
    $meal_time = trim($_POST['meal_time']);
    $meal_text = trim($_POST['meal_text']);
    
    // Ensure numeric fields are safe integers (default to 0 if empty/invalid)
    $protein = intval($_POST['protein']);
    $carbs = intval($_POST['carbs']);
    $fat = intval($_POST['fat']);
    $calories = intval($_POST['calories']);

    // 2. Prepare the update statement
    $update = $connection->prepare("UPDATE diet_plans 
        SET goal=?, dietary=?, activity=?, meal_type=?, day_number=?, meal_time=?, meal_text=?, protein=?, carbs=?, fat=?, calories=? 
        WHERE id=?");
        
    // The bind string must match the 12 parameters (5 strings, 7 integers)
    // ssss i s s i i i i i
    $update->bind_param("ssssissiiiii", 
        $goal, $dietary, $activity, $meal_type, $day_number, $meal_time, $meal_text, $protein, $carbs, $fat, $calories, $id
    );
    
    // 3. Execute and redirect
    if (!$update->execute()) {
        // In a real app, log the error and show a user-friendly message
        error_log("Database Update Error: " . $update->error);
    }
    $update->close();

    // Redirect to the same page with the currently selected goal to maintain filter view
    $redirectUrl = "dietplans.php" . (!empty($goal) ? "?goal=" . urlencode($goal) : "");
    header("Location: " . $redirectUrl);
    exit();
}

// ✅ Fetch distinct goals for filter dropdown
$filterSql = "SELECT DISTINCT goal FROM diet_plans ORDER BY goal ASC";
$filterResult = $connection->query($filterSql);
$goals = [];
if ($filterResult && $filterResult->num_rows > 0) {
    while($row = $filterResult->fetch_assoc()) {
        $goals[] = $row['goal'];
    }
}

// Get selected goal from GET (default first goal if none)
$selectedGoal = isset($_GET['goal']) ? trim($_GET['goal']) : ($goals[0] ?? '');

// ✅ Fetch diet plans based on selected goal
if (!empty($selectedGoal)) {
    $stmt = $connection->prepare("SELECT * FROM diet_plans WHERE goal = ? ORDER BY day_number ASC, id DESC");
    $stmt->bind_param("s", $selectedGoal);
    $stmt->execute();
    $result = $stmt->get_result();
    $stmt->close();
} else {
    // Fallback: Fetch all if no specific goal is available or selected
    $result = $connection->query("SELECT * FROM diet_plans ORDER BY id DESC");
}

// Ensure results are valid before proceeding
if (!$result) {
    error_log("Diet Plan Fetch Error: " . $connection->error);
    $result = new stdClass(); // Create a mock object to avoid errors later
    $result->num_rows = 0;
}
?>

<?php include 'components/head.php'; ?>
<?php include 'components/dashboard-nav.php'; ?>

<main class="bg-gray-100 w-full pt-10 px-6 min-h-screen">
  <h2 class="text-3xl font-bold text-emerald-600 mb-6">🥗 Manage Diet Plans</h2>

  <!-- Filter Dropdown -->
  <?php if(count($goals) > 0): ?>
    <div class="mb-4 flex items-center gap-2">
      <form method="GET" class="flex items-center gap-2">
        <label for="goal-filter" class="font-semibold text-gray-700">Filter by Goal:</label>
        <select name="goal" id="goal-filter" class="border rounded p-2 focus:ring-emerald-500 focus:border-emerald-500 transition duration-150 ease-in-out">
          <?php foreach($goals as $goal): ?>
            <option value="<?= htmlspecialchars($goal); ?>" <?= $goal === $selectedGoal ? 'selected' : '' ?>>
              <?= htmlspecialchars($goal); ?>
            </option>
          <?php endforeach; ?>
        </select>
        <button type="submit" class="px-3 py-2 bg-emerald-600 text-white rounded-lg shadow hover:bg-emerald-700 transition duration-150 ease-in-out">
            Apply Filter
        </button>
      </form>
    </div>
  <?php endif; ?>

  <?php if ($result->num_rows > 0): ?>
    <div class="overflow-x-auto bg-white rounded-xl shadow-2xl">
      <table class="w-full table-auto border-collapse">
        <thead class="bg-gray-800 text-white">
          <tr>
            <th class="px-4 py-3 text-left">Goal</th>
            <th class="px-4 py-3 text-left">Dietary</th>
            <th class="px-4 py-3 text-left">Activity</th>
            <th class="px-4 py-3 text-left">Meal Type</th>
            <th class="px-4 py-3 text-left">Day</th>
            <th class="px-4 py-3 text-left">Meal Time</th>
            <th class="px-4 py-3 text-left">Meal Description</th>
            <th class="px-4 py-3 text-left">Macros</th>
            <th class="px-4 py-3 text-center">Action</th>
          </tr>
        </thead>
        <tbody class="text-gray-700">
          <?php while($row = $result->fetch_assoc()): ?>
            <tr class="border-b border-gray-200 hover:bg-emerald-50 transition duration-150 ease-in-out">
              <td class="px-4 py-3 font-semibold text-emerald-800"><?= htmlspecialchars($row['goal']); ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['dietary']); ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['activity']); ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['meal_type']); ?></td>
              <td class="px-4 py-3 font-medium">Day <?= $row['day_number']; ?></td>
              <td class="px-4 py-3 text-sm"><?= htmlspecialchars($row['meal_time']); ?></td>
              <td class="px-4 py-3 text-sm max-w-xs overflow-hidden text-ellipsis"><?= nl2br(htmlspecialchars($row['meal_text'])); ?></td>
              <td class="px-4 py-3 text-xs">
                <span class="font-bold">P:</span> <?= $row['protein']; ?>g | 
                <span class="font-bold">C:</span> <?= $row['carbs']; ?>g | 
                <span class="font-bold">F:</span> <?= $row['fat']; ?>g <br>
                <span class="font-bold text-red-600">Total Cal:</span> <?= $row['calories']; ?>
              </td>
              <td class="px-4 py-3 text-center">
                <button 
                  class="px-3 py-1 rounded-lg bg-blue-600 text-white hover:bg-blue-700 text-sm font-medium shadow-md transition duration-150 ease-in-out"
                  onclick='openModal(<?= json_encode($row); ?>)'>
                  ✏ Edit
                </button>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <div class="p-6 bg-white rounded-xl shadow-lg mt-4">
      <p class="text-gray-500 font-medium">No diet plans found for the selected goal: **<?= htmlspecialchars($selectedGoal) ?>**.</p>
    </div>
  <?php endif; ?>
</main>

<!-- Modal -->
<!-- Fixed the duplicate div ID here -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50 transition-opacity duration-300">
  <div class="bg-white p-8 rounded-2xl shadow-2xl w-full max-w-3xl relative transform scale-95 transition-transform duration-300 ease-in-out">
    <h3 class="text-2xl font-bold text-gray-800 mb-6 border-b pb-2">Update Diet Plan Record</h3>
    <form method="POST" id="editForm" class="space-y-6">
      <input type="hidden" name="update_id" id="update_id">

      <!-- Row 1: Goal, Dietary, Activity, Meal Type -->
      <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
        <div>
          <label for="goal" class="block font-semibold text-gray-700 mb-1">Goal</label>
          <input type="text" name="goal" id="goal" class="w-full border-gray-300 rounded-lg p-2.5 focus:border-emerald-500 focus:ring-emerald-500" required>
        </div>
        <div>
          <label for="dietary" class="block font-semibold text-gray-700 mb-1">Dietary</label>
          <input type="text" name="dietary" id="dietary" class="w-full border-gray-300 rounded-lg p-2.5 focus:border-emerald-500 focus:ring-emerald-500" required>
        </div>
        <div>
          <label for="activity" class="block font-semibold text-gray-700 mb-1">Activity</label>
          <input type="text" name="activity" id="activity" class="w-full border-gray-300 rounded-lg p-2.5 focus:border-emerald-500 focus:ring-emerald-500" required>
        </div>
        <div>
          <label for="meal_type" class="block font-semibold text-gray-700 mb-1">Meal Type</label>
          <input type="text" name="meal_type" id="meal_type" class="w-full border-gray-300 rounded-lg p-2.5 focus:border-emerald-500 focus:ring-emerald-500" required>
        </div>
      </div>
      
      <!-- Row 2: Day Number, Meal Time -->
      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label for="day_number" class="block font-semibold text-gray-700 mb-1">Day Number (1-7)</label>
          <input type="number" name="day_number" id="day_number" class="w-full border-gray-300 rounded-lg p-2.5 focus:border-emerald-500 focus:ring-emerald-500" min="1" max="7" required>
        </div>
        <div>
          <label for="meal_time" class="block font-semibold text-gray-700 mb-1">Meal Time</label>
          <input type="text" name="meal_time" id="meal_time" class="w-full border-gray-300 rounded-lg p-2.5 focus:border-emerald-500 focus:ring-emerald-500" required>
        </div>
      </div>

      <!-- Row 3: Meal Description -->
      <div>
        <label for="meal_text" class="block font-semibold text-gray-700 mb-1">Meal Description</label>
        <textarea name="meal_text" id="meal_text" rows="4" class="w-full border-gray-300 rounded-lg p-2.5 focus:border-emerald-500 focus:ring-emerald-500" required></textarea>
      </div>

      <!-- Row 4: Macros -->
      <div class="grid grid-cols-2 md:grid-cols-4 gap-4 pt-2 border-t">
        <h4 class="col-span-full text-lg font-bold text-gray-800">Macro Nutrients (g / kcal)</h4>
        <div>
          <label for="protein" class="block font-semibold text-gray-700 mb-1">Protein (g)</label>
          <input type="number" name="protein" id="protein" class="w-full border-gray-300 rounded-lg p-2.5 focus:border-emerald-500 focus:ring-emerald-500">
        </div>
        <div>
          <label for="carbs" class="block font-semibold text-gray-700 mb-1">Carbs (g)</label>
          <input type="number" name="carbs" id="carbs" class="w-full border-gray-300 rounded-lg p-2.5 focus:border-emerald-500 focus:ring-emerald-500">
        </div>
        <div>
          <label for="fat" class="block font-semibold text-gray-700 mb-1">Fat (g)</label>
          <input type="number" name="fat" id="fat" class="w-full border-gray-300 rounded-lg p-2.5 focus:border-emerald-500 focus:ring-emerald-500">
        </div>
        <div>
          <label for="calories" class="block font-semibold text-gray-700 mb-1">Calories</label>
          <input type="number" name="calories" id="calories" class="w-full border-gray-300 rounded-lg p-2.5 focus:border-emerald-500 focus:ring-emerald-500">
        </div>
      </div>

      <div class="flex justify-end gap-3 mt-4">
        <button type="button" onclick="closeModal()" class="px-6 py-2 bg-gray-500 text-white rounded-lg hover:bg-gray-600 font-semibold transition duration-150 ease-in-out">Cancel</button>
        <button type="submit" class="px-6 py-2 bg-emerald-600 text-white rounded-lg hover:bg-emerald-700 font-semibold shadow-md transition duration-150 ease-in-out">Save Changes</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(data) {
  const modal = document.getElementById('editModal');
  const form = document.getElementById('editForm');
  
  // Populate form fields with data
  document.getElementById('update_id').value = data.id;
  document.getElementById('goal').value = data.goal;
  document.getElementById('dietary').value = data.dietary;
  document.getElementById('activity').value = data.activity;
  document.getElementById('meal_type').value = data.meal_type;
  document.getElementById('day_number').value = data.day_number;
  document.getElementById('meal_time').value = data.meal_time;
  document.getElementById('meal_text').value = data.meal_text;
  document.getElementById('protein').value = data.protein;
  document.getElementById('carbs').value = data.carbs;
  document.getElementById('fat').value = data.fat;
  document.getElementById('calories').value = data.calories;

  // Show the modal
  modal.classList.remove('hidden');
  modal.classList.add('flex');
}

function closeModal() {
  const modal = document.getElementById('editModal');
  // Hide the modal
  modal.classList.add('hidden');
  modal.classList.remove('flex');
}
</script>


<?php
// Close the database connection gracefully
if (isset($connection)) {
    $connection->close();
}
?>
