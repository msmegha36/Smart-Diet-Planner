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

// Handle Update Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id = intval($_POST['update_id']);
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

    $update = $connection->prepare("UPDATE diet_plans 
        SET goal=?, dietary=?, activity=?, meal_type=?, day_number=?, meal_time=?, meal_text=?, protein=?, carbs=?, fat=?, calories=? 
        WHERE id=?");
    $update->bind_param("ssssissiiiii", 
        $goal, $dietary, $activity, $meal_type, $day_number, $meal_time, $meal_text, $protein, $carbs, $fat, $calories, $id
    );
    $update->execute();

    header("Location: dietplans.php");
    exit();
}

// Fetch all diet plans
$sql = "SELECT * FROM diet_plans ORDER BY id DESC";
$result = $connection->query($sql);
?>

<?php include 'components/head.php'; ?>
<?php include 'components/dashboard-nav.php'; ?>

<main class="bg-gray-100 w-full pt-10 px-6">
  <h2 class="text-3xl font-bold text-emerald-600 mb-6">🥗 Manage Diet Plans</h2>

  <?php if ($result->num_rows > 0): ?>
    <div class="overflow-x-auto bg-white rounded-xl shadow-lg">
      <table class="w-full table-auto border-collapse">
        <thead class="bg-gray-900 text-white">
          <tr>
            <th class="px-4 py-3 text-left">Goal</th>
            <th class="px-4 py-3 text-left">Dietary</th>
            <th class="px-4 py-3 text-left">Activity</th>
            <th class="px-4 py-3 text-left">Meal Type</th>
            <th class="px-4 py-3 text-left">Day</th>
            <th class="px-4 py-3 text-left">Meal Time</th>
            <th class="px-4 py-3 text-left">Meal</th>
            <th class="px-4 py-3 text-left">Macros</th>
            <th class="px-4 py-3 text-left">Action</th>
          </tr>
        </thead>
        <tbody class="text-gray-700">
          <?php while($row = $result->fetch_assoc()): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="px-4 py-3 font-semibold"><?= htmlspecialchars($row['goal']); ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['dietary']); ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['activity']); ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['meal_type']); ?></td>
              <td class="px-4 py-3">Day <?= $row['day_number']; ?></td>
              <td class="px-4 py-3"><?= htmlspecialchars($row['meal_time']); ?></td>
              <td class="px-4 py-3 text-sm"><?= nl2br(htmlspecialchars($row['meal_text'])); ?></td>
              <td class="px-4 py-3 text-sm">
                🥩 Protein: <?= $row['protein']; ?>g <br>
                🍚 Carbs: <?= $row['carbs']; ?>g <br>
                🥑 Fat: <?= $row['fat']; ?>g <br>
                🔥 Calories: <?= $row['calories']; ?> kcal
              </td>
              <td class="px-4 py-3">
                <button 
                  class="px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700 text-sm"
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
    <p class="text-gray-500">No diet plans found.</p>
  <?php endif; ?>
</main>

<!-- Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white p-6 rounded-xl shadow-lg w-full max-w-2xl relative">
    <h3 class="text-xl font-bold mb-4">Update Diet Plan</h3>
    <form method="POST" id="editForm" class="space-y-4">
      <input type="hidden" name="update_id" id="update_id">

      <div class="grid grid-cols-2 gap-4">
        <div>
          <label class="block font-semibold">Goal</label>
          <input type="text" name="goal" id="goal" class="w-full border rounded p-2" required>
        </div>
        <div>
          <label class="block font-semibold">Dietary</label>
          <input type="text" name="dietary" id="dietary" class="w-full border rounded p-2" required>
        </div>
        <div>
          <label class="block font-semibold">Activity</label>
          <input type="text" name="activity" id="activity" class="w-full border rounded p-2" required>
        </div>
        <div>
          <label class="block font-semibold">Meal Type</label>
          <input type="text" name="meal_type" id="meal_type" class="w-full border rounded p-2" required>
        </div>
        <div>
          <label class="block font-semibold">Day Number (1-7)</label>
          <input type="number" name="day_number" id="day_number" class="w-full border rounded p-2" min="1" max="7" required>
        </div>
        <div>
          <label class="block font-semibold">Meal Time</label>
          <input type="text" name="meal_time" id="meal_time" class="w-full border rounded p-2" required>
        </div>
      </div>

      <div>
        <label class="block font-semibold">Meal Description</label>
        <textarea name="meal_text" id="meal_text" rows="4" class="w-full border rounded p-2" required></textarea>
      </div>

      <div class="grid grid-cols-4 gap-4">
        <div>
          <label class="block font-semibold">Protein (g)</label>
          <input type="number" name="protein" id="protein" class="w-full border rounded p-2">
        </div>
        <div>
          <label class="block font-semibold">Carbs (g)</label>
          <input type="number" name="carbs" id="carbs" class="w-full border rounded p-2">
        </div>
        <div>
          <label class="block font-semibold">Fat (g)</label>
          <input type="number" name="fat" id="fat" class="w-full border rounded p-2">
        </div>
        <div>
          <label class="block font-semibold">Calories</label>
          <input type="number" name="calories" id="calories" class="w-full border rounded p-2">
        </div>
      </div>

      <div class="flex justify-end gap-3 mt-4">
        <button type="button" onclick="closeModal()" class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500">Cancel</button>
        <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700">Save</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(data) {
  document.getElementById('editModal').classList.remove('hidden');
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
}

function closeModal() {
  document.getElementById('editModal').classList.add('hidden');
}
</script>
