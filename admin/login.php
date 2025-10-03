<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include(__DIR__ . '/../config/db_conn.php');
$error = "";

// Handle Login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = mysqli_real_escape_string($connection, $_POST['password']);

    if (!empty($email) && !empty($password)) {
        $hashed_pass = md5($password);

        $sql = "SELECT * FROM admins WHERE email='$email' AND password='$hashed_pass'";
        $result = mysqli_query($connection, $sql);

        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);

            $_SESSION['admin_id'] = $row['id'];
            $_SESSION['admin_name'] = $row['name'];
            $_SESSION['role'] = $row['role'];

            echo "<script>alert('Welcome Admin ✅'); window.location='index.php';</script>";
            exit();
        } else {
            $error = "❌ Invalid email or password.";
        }
    } else {
        $error = "⚠ Please fill in all fields.";
    }
}
?>

<?php include 'components/head.php'; ?>
<?php include 'components/navbar.php'; ?>

<main class="flex items-center justify-center min-h-screen bg-gray-900 px-4">
  <div class="bg-gray-800 shadow-xl rounded-xl w-full max-w-md p-8 text-white">
    <!-- Logo & Title -->
    <div class="text-center mb-6">
      <img src="https://cdn-icons-png.flaticon.com/512/1828/1828466.png" class="w-16 mx-auto mb-3" alt="Admin Logo">
      <h1 class="text-3xl font-bold text-green-400">Admin Login</h1>
      <p class="text-gray-400 text-base">Manage the platform</p>
    </div>

    <!-- Error Message -->
    <?php if (!empty($error)): ?>
      <div class="bg-red-700 text-white px-4 py-2 rounded mb-4">
        <?= $error ?>
      </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" class="space-y-6">
      <div>
        <label class="block text-gray-300 font-medium mb-2">Email</label>
        <input type="email" name="email" required
          class="w-full border border-gray-600 bg-gray-700 text-white rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-green-400"
           oninvalid="InvalidMsg4(this);" oninput="InvalidMsg4(this);">
      </div>
      <div>
        <label class="block text-gray-300 font-medium mb-2">Password</label>
        <input type="password" name="password" required
          class="w-full border border-gray-600 bg-gray-700 text-white rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-green-400"
          minlength="3" maxlength="16" oninvalid="InvalidMsg2(this);" oninput="InvalidMsg2(this);">
      </div>

      <!-- Submit Button -->
      <button type="submit" class="w-full bg-green-500 text-black font-semibold text-lg py-3 rounded-lg hover:bg-green-600 transition">
        Login
      </button>
    </form>

    <!-- Divider -->
    <div class="flex items-center my-6">
      <hr class="flex-1 border-gray-600">
      <span class="px-3 text-gray-400 text-sm">Admin Access Only</span>
      <hr class="flex-1 border-gray-600">
    </div>
  </div>
</main>

<?php include 'components/footer.php'; ?>



<script>



// --- FIXED PASSWORD VALIDATION ---
  function InvalidMsg2(textbox) {
    // MUST clear validity first on input to allow subsequent checks
    textbox.setCustomValidity(''); 

    if (textbox.value === '') {
        textbox.setCustomValidity('A password is necessary!');
    } else if (textbox.value.length <= 3) { 
        textbox.setCustomValidity('Please enter at least 4 characters!');
    }
    // No 'else' block needed, as we cleared it at the start.
    return true;
  }
    
  // --- FIXED EMAIL VALIDATION ---
  function InvalidMsg4(textbox) {
    // MUST clear validity first on input to allow subsequent checks
    textbox.setCustomValidity('');

    if (textbox.validity.valueMissing) {
        textbox.setCustomValidity('Entering an email address is necessary!');
    } else if (textbox.validity.typeMismatch) {
        textbox.setCustomValidity('Please enter a valid email address!');
    }
    // No 'else' block needed, as we cleared it at the start.
    return true;
  }
  
</script>
