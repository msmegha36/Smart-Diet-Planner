<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . '/../config/db_conn.php');

// If no session, redirect to login
if (!isset($_SESSION['nutritionist_id'])) {
    header("Location: login.php");
    exit();
}

$nutritionist_id = $_SESSION['nutritionist_id'];

// Fetch nutritionist info
$sql = "SELECT * FROM nutritionists WHERE id = ?";
$stmt = $connection->prepare($sql);
$stmt->bind_param("i", $nutritionist_id);
$stmt->execute();
$result = $stmt->get_result();
$nutritionist = $result->fetch_assoc();
?>
<?php include 'components/head.php'; ?>
<?php include 'components/dashboard-nav.php'; ?>

<main class="flex-1 overflow-y-auto p-8 bg-gray-50">
  <!-- Profile Section -->
  <section class="bg-white rounded-xl shadow-lg p-6">
    <div class="flex justify-between items-center mb-6 border-b pb-4">
      <h2 class="text-2xl font-bold text-emerald-700">👨‍⚕️ Nutritionist Profile</h2>
      <button onclick="toggleModal(true)" 
        class="bg-emerald-600 text-white px-5 py-2 rounded-lg hover:bg-emerald-700 transition">
        Update Profile
      </button>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 text-gray-700">
      <p><strong>Name:</strong> <?= htmlspecialchars($nutritionist['name']); ?></p>
      <p><strong>Email:</strong> <?= htmlspecialchars($nutritionist['email']); ?></p>
      <p><strong>Phone:</strong> <?= htmlspecialchars($nutritionist['phone']); ?></p>
      <p><strong>Specialization:</strong> <?= htmlspecialchars($nutritionist['specialization']); ?></p>
      <p><strong>Experience:</strong> <?= htmlspecialchars($nutritionist['experience']); ?> years</p>
      <p><strong>Status:</strong> 
        <span class="px-2 py-1 rounded text-white 
          <?= $nutritionist['status']=='approved' ? 'bg-green-600' : ($nutritionist['status']=='pending' ? 'bg-yellow-500' : 'bg-red-600'); ?>">
          <?= ucfirst($nutritionist['status']); ?>
        </span>
      </p>
    </div>

    <?php if (!empty($nutritionist['description'])): ?>
      <div class="mt-6">
        <h3 class="text-lg font-semibold text-gray-800">About Me</h3>
        <p class="text-gray-600 mt-2 leading-relaxed"><?= nl2br(htmlspecialchars($nutritionist['description'])); ?></p>
      </div>
    <?php endif; ?>

    <?php if (!empty($nutritionist['image'])): ?>
      <div class="mt-6 flex justify-center">
        <img src="<?= htmlspecialchars($nutritionist['image']); ?>" 
             alt="Profile Picture" 
             class="w-40 h-40 object-cover rounded-full border-4 border-emerald-500 shadow-md">
      </div>
    <?php endif; ?>
  </section>
</main>

<!-- Update Modal -->
<div id="updateModal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50">
  <div class="bg-white rounded-xl shadow-lg w-full max-w-3xl p-6">
    <!-- Header -->
    <div class="flex justify-between items-center border-b pb-3 mb-4">
      <h5 class="text-xl font-bold text-emerald-700">✏️ Update Profile</h5>
      <button onclick="toggleModal(false)" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
    </div>

    <!-- Form -->
    <form method="POST" action="update_nutritionist_profile.php" enctype="multipart/form-data" 
          class="grid grid-cols-1 md:grid-cols-2 gap-4" id="updateForm">

      <input type="hidden" name="nutritionist_id" value="<?= $nutritionist_id; ?>">

      <div>
        <label class="block text-gray-700 font-medium mb-1">Name</label>
        <input type="text" name="name" class="w-full border rounded-lg px-3 py-2" 
               value="<?= htmlspecialchars($nutritionist['name']); ?>">
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Email</label>
        <input type="email" name="email" class="w-full border rounded-lg px-3 py-2" 
               value="<?= htmlspecialchars($nutritionist['email']); ?>">
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Phone</label>
        <input type="text" name="phone" class="w-full border rounded-lg px-3 py-2" 
               value="<?= htmlspecialchars($nutritionist['phone']); ?>">
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Specialization</label>
        <input type="text" name="specialization" class="w-full border rounded-lg px-3 py-2" 
               value="<?= htmlspecialchars($nutritionist['specialization']); ?>">
      </div>

      <div>
        <label class="block text-gray-700 font-medium mb-1">Experience (years)</label>
        <input type="number" name="experience" class="w-full border rounded-lg px-3 py-2" 
               value="<?= htmlspecialchars($nutritionist['experience']); ?>">
      </div>

      <div class="md:col-span-2">
        <label class="block text-gray-700 font-medium mb-1">Description</label>
        <textarea name="description" rows="4" class="w-full border rounded-lg px-3 py-2"><?= htmlspecialchars($nutritionist['description']); ?></textarea>
      </div>

      <div class="md:col-span-2">
        <label class="block text-gray-700 font-medium mb-1">Profile Picture</label>
        <input type="file" name="image" class="w-full border rounded-lg px-3 py-2">
      </div>
    </form>

    <!-- Actions -->
    <div class="flex justify-end mt-6 space-x-3">
      <button onclick="toggleModal(false)" class="px-4 py-2 rounded-lg bg-gray-300 hover:bg-gray-400">
        Cancel
      </button>
      <button type="submit" form="updateForm" class="px-5 py-2 rounded-lg bg-emerald-600 text-white hover:bg-emerald-700">
        Save Changes
      </button>
    </div>
  </div>
</div>

<script>
function toggleModal(show) {
  const modal = document.getElementById("updateModal");
  modal.classList.toggle("hidden", !show);
  modal.classList.toggle("flex", show);
}
</script>
</div>