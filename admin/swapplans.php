<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . '/../config/db_conn.php');

if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

// Handle Update Action
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_id'])) {
    $id = intval($_POST['update_id']);
    $dietary = $_POST['dietary'];
    $meal_text = $_POST['meal_text'];
    $alternative_text = $_POST['alternative_text'];
    $protein = intval($_POST['protein']);
    $carbs = intval($_POST['carbs']);
    $fat = intval($_POST['fat']);
    $calories = intval($_POST['calories']);

    $meal_hash = hash('sha256', $meal_text);

    $update = $connection->prepare("UPDATE meal_swaps 
        SET meal_hash=?, dietary=?, alternative_text=?, protein=?, carbs=?, fat=?, calories=?
        WHERE id=?");
    $update->bind_param("sssiiiii", $meal_hash, $dietary, $alternative_text, $protein, $carbs, $fat, $calories, $id);
    $update->execute();

    $_SESSION['success'] = "✅ Swap meal updated successfully!";
    header("Location: swapplans.php");
    exit();
}

// Handle Delete Action
if (isset($_GET['delete_id'])) {
    $delete_id = intval($_GET['delete_id']);
    $delete = $connection->prepare("DELETE FROM meal_swaps WHERE id=?");
    $delete->bind_param("i", $delete_id);
    $delete->execute();

    $_SESSION['success'] = "✅ Swap meal deleted successfully!";
    header("Location: swapplans.php");
    exit();
}

// ✅ Fetch distinct dietary types for dropdown
$dietaryResult = $connection->query("SELECT DISTINCT dietary FROM meal_swaps");
$dietaries = [];
while($row = $dietaryResult->fetch_assoc()) $dietaries[] = $row['dietary'];

// Get selected filter values
$selectedDietary = $_GET['dietary'] ?? '';
$selectedProtein = $_GET['protein'] ?? '';
$selectedCalories = $_GET['calories'] ?? '';

// Build filter query
$where = [];
$params = [];
$types = '';

if($selectedDietary) {
    $where[] = "dietary = ?";
    $params[] = $selectedDietary;
    $types .= 's';
}
if($selectedProtein) {
    if($selectedProtein == 'low') { $where[] = "protein < 20"; }
    if($selectedProtein == 'medium') { $where[] = "protein BETWEEN 20 AND 50"; }
    if($selectedProtein == 'high') { $where[] = "protein > 50"; }
}
if($selectedCalories) {
    if($selectedCalories == 'low') { $where[] = "calories < 300"; }
    if($selectedCalories == 'medium') { $where[] = "calories BETWEEN 300 AND 600"; }
    if($selectedCalories == 'high') { $where[] = "calories > 600"; }
}

$sql = "SELECT * FROM meal_swaps";
if($where) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY id DESC";

$stmt = $connection->prepare($sql);
if($params) $stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

?>

<?php include 'components/head.php'; ?>
<?php include 'components/dashboard-nav.php'; ?>

<main class="bg-gray-100 w-full pt-10 px-6">
  <h2 class="text-3xl font-bold text-emerald-600 mb-6">🍴 Manage Swap Meals</h2>

  <!-- Filter Form -->
<!-- Filter Form -->
<form method="GET" class="mb-6 flex flex-wrap gap-4 items-center">
    <div>
        <label class="font-semibold">Dietary:</label>
        <select name="dietary" class="border rounded p-2">
            <!-- Set first value as default (e.g., 'veg') -->
            <option value="veg" <?= $selectedDietary=='veg' ? 'selected' : '' ?>>Vegetarian</option>
            <option value="nonveg" <?= $selectedDietary=='nonveg' ? 'selected' : '' ?>>Non-Vegetarian</option>
        </select>
    </div>

    <div>
        <label class="font-semibold">Protein:</label>
        <select name="protein" class="border rounded p-2">
            <!-- Set first value as default (e.g., 'low') -->
            <option value="low" <?= $selectedProtein=='low' || $selectedProtein=='' ? 'selected' : '' ?>>Low (&lt;20g)</option>
            <option value="medium" <?= $selectedProtein=='medium' ? 'selected' : '' ?>>Medium (20-50g)</option>
            <option value="high" <?= $selectedProtein=='high' ? 'selected' : '' ?>>High (&gt;50g)</option>
        </select>
    </div>

    <div>
        <label class="font-semibold">Calories:</label>
        <select name="calories" class="border rounded p-2">
            <!-- Set first value as default (e.g., 'medium') -->
            <option value="medium" <?= $selectedCalories=='medium' || $selectedCalories=='' ? 'selected' : '' ?>>Medium (300-600)</option>
            <option value="low" <?= $selectedCalories=='low' ? 'selected' : '' ?>>Low (&lt;300)</option>
            <option value="high" <?= $selectedCalories=='high' ? 'selected' : '' ?>>High (&gt;600)</option>
        </select>
    </div>

    <button type="submit" class="px-4 py-2 bg-emerald-600 text-white rounded hover:bg-emerald-700">Filter</button>
</form>


  <!-- Swap meals table -->
  <?php if ($result->num_rows > 0): ?>
    <div class="overflow-x-auto bg-white rounded-xl shadow-lg">
      <table class="w-full table-auto border-collapse">
        <thead class="bg-gray-900 text-white">
          <tr>
            <th class="px-4 py-3 text-left">Dietary</th>
            <th class="px-4 py-3 text-left">Original Meal Hash</th>
            <th class="px-4 py-3 text-left">Alternative Meal</th>
            <th class="px-4 py-3 text-left">Macros</th>
            <th class="px-4 py-3 text-left">Action</th>
          </tr>
        </thead>
        <tbody class="text-gray-700">
          <?php while($row = $result->fetch_assoc()): ?>
            <tr class="border-b hover:bg-gray-50">
              <td class="px-4 py-3"><?= htmlspecialchars($row['dietary']); ?></td>
              <td class="px-4 py-3 text-sm"><?= nl2br(htmlspecialchars($row['meal_hash'])); ?></td>
              <td class="px-4 py-3 text-sm"><?= nl2br(htmlspecialchars($row['alternative_text'])); ?></td>
              <td class="px-4 py-3 text-sm">
                🥩 Protein: <?= $row['protein']; ?>g <br>
                🍚 Carbs: <?= $row['carbs']; ?>g <br>
                🥑 Fat: <?= $row['fat']; ?>g <br>
                🔥 Calories: <?= $row['calories']; ?> kcal
              </td>
              <td class="px-4 py-3 flex gap-2">
                <button 
                  class="px-3 py-1 rounded bg-blue-600 text-white hover:bg-blue-700 text-sm"
                  onclick='openModal(<?= json_encode($row); ?>)'>
                  ✏ Edit
                </button>
                <a href="?delete_id=<?= $row['id']; ?>" 
                   class="px-3 py-1 rounded bg-red-600 text-white hover:bg-red-700 text-sm"
                   onclick="return confirm('Are you sure you want to delete this swap meal?');">
                  🗑 Delete
                </a>
              </td>
            </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  <?php else: ?>
    <p class="text-gray-500">No swap meals found.</p>
  <?php endif; ?>
</main>

<!-- Edit Modal code remains unchanged -->


<!-- Modal -->
<div id="editModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div id="editModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden z-50">
  <div class="bg-white p-6 rounded-xl shadow-lg w-full max-w-2xl relative">
    <h3 class="text-xl font-bold mb-4">Update Swap Meal</h3>
    <form method="POST" id="editForm" class="space-y-4">
      <input type="hidden" name="update_id" id="update_id">

      <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label class="block font-semibold">Dietary</label>
          <select name="dietary" id="dietary" class="w-full border rounded p-2" required>
            <option value="veg">Vegetarian</option>
            <option value="nonveg">Non-Vegetarian</option>
          </select>
        </div>

        <div>
          <label class="block font-semibold">Original Meal Description</label>
          <textarea name="meal_text" id="meal_text" rows="3" class="w-full border rounded p-2" required></textarea>
        </div>

        <div class="md:col-span-2">
          <label class="block font-semibold">Alternative Meal Description</label>
          <textarea name="alternative_text" id="alternative_text" rows="4" class="w-full border rounded p-2" required></textarea>
        </div>

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
  document.getElementById('dietary').value = data.dietary;
  document.getElementById('meal_text').value = data.meal_hash;
  document.getElementById('alternative_text').value = data.alternative_text;
  document.getElementById('protein').value = data.protein;
  document.getElementById('carbs').value = data.carbs;
  document.getElementById('fat').value = data.fat;
  document.getElementById('calories').value = data.calories;
}

function closeModal() {
  document.getElementById('editModal').classList.add('hidden');
}
</script>
