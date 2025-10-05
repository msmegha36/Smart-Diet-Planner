<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

// NOTE: We assume 'db_conn.php' exists in the config directory relative to this file's execution.
include(__DIR__ . '/../config/db_conn.php');
$error = "";

// Handle Login
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = mysqli_real_escape_string($connection, $_POST['password']);

    if (!empty($email) && !empty($password)) {
        $hashed_pass = md5($password);

        $sql = "SELECT * FROM nutritionists WHERE email='$email' AND password='$hashed_pass'";
        $result = mysqli_query($connection, $sql);

        if (mysqli_num_rows($result) == 1) {
            $row = mysqli_fetch_assoc($result);

            if ($row['status'] === 'approved') {
                // Set session data
                $_SESSION['nutritionist_id'] = $row['id'];
                
                echo "<script>alert('Login Successful ✅'); window.location='index.php';</script>";
                exit();
            } elseif ($row['status'] === 'pending') {
                $error = "⏳ Your account is pending approval. Please wait for admin approval.";
            } else {
                $error = "❌ Your account has been rejected. Contact admin.";
            }
        } else {
            $error = "❌ Invalid email or password.";
        }
    } else {
        $error = "⚠ Please fill in all fields.";
    }
}
?>

<?php include 'components/head.php'; ?>

<!-- Include Tailwind via CDN if not already done in components/head.php -->
<script src="https://cdn.tailwindcss.com"></script>

<style>
    /* Custom validity message style */
    input:invalid:not(:placeholder-shown) {
        border-color: #ef4444; /* red-500 */
    }
    .error-message {
        color: #ef4444;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        transition: opacity 0.3s ease;
    }
</style>

<?php include 'components/navbar.php'; ?>

<main class="flex items-center justify-center min-h-screen bg-gradient-to-r from-green-200 via-blue-200 to-purple-200 px-4">
  <div class="bg-white shadow-2xl rounded-2xl w-full max-w-md p-8">
    <!-- Logo & Title -->
    <div class="text-center mb-6">
      <img src="https://cdn-icons-png.flaticon.com/512/2927/2927347.png" class="w-16 mx-auto mb-3" alt="Logo">
      <h1 class="text-3xl font-bold text-blue-700">Nutritionist Login</h1>
      <p class="text-gray-500 text-base">Access your account</p>
    </div>

    <!-- Error Message (PHP backend error) -->
    <?php if (!empty($error)): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4">
        <?= $error ?>
      </div>
    <?php endif; ?>

    <!-- Login Form -->
    <form method="POST" class="space-y-6">
      <div>
        <label class="block text-gray-700 font-medium mb-2">Email</label>
        <!-- Added oninput and custom error span -->
        <input type="email" name="email" id="email" required
         oninvalid="validateEmail(this);" oninput="validateEmail(this);"
          class="w-full border rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-blue-500">
        <span class="error-message hidden" id="email-error"></span>
      </div>
      <div>
        <label class="block text-gray-700 font-medium mb-2">Password</label>
        <!-- Added oninput and custom error span -->
        <input type="password" name="password" id="password" required
           minlength="8" maxlength="16" oninvalid="validatePassword(this);" oninput="validatePassword(this);"
          class="w-full border rounded-lg px-4 py-3 text-lg focus:ring-2 focus:ring-blue-500">
        <span class="error-message hidden" id="password-error"></span>
      </div>

      <!-- Submit Button -->
      <button type="submit" class="w-full bg-blue-600 text-white font-semibold text-lg py-3 rounded-lg hover:bg-blue-700 transition">
        Login
      </button>
    </form>

    <!-- Divider -->
    <div class="flex items-center my-6">
      <hr class="flex-1 border-gray-300">
      <span class="px-3 text-gray-500 text-sm">OR</span>
      <hr class="flex-1 border-gray-300">
    </div>

    <!-- Signup Link -->
    <p class="text-center text-gray-600 text-base">
      Don’t have an account? 
      <a href="register.php" class="text-blue-600 font-semibold hover:underline">Register</a>
    </p>
  </div>
</main>

<!-- JavaScript for real-time validation -->
<script>
    // Helper function to update the visible error span
    function updateErrorMessage(textbox) {
        // We use the next sibling span with the error-message class
        const errorMessageElement = textbox.nextElementSibling;
        
        // Use checkValidity() to see if the element is valid based on HTML5 and setCustomValidity() checks
        if (!textbox.checkValidity()) {
            errorMessageElement.textContent = textbox.validationMessage;
            errorMessageElement.classList.remove('hidden');
        } else {
            errorMessageElement.classList.add('hidden');
        }
    }

  
    // --- GENERIC REQUIRED FIELD VALIDATION ---
    function validateRequired(textbox, fieldName) {
        textbox.setCustomValidity('');

        if (textbox.validity.valueMissing) {
            textbox.setCustomValidity(`${fieldName} is required.`);
        }
        
        updateErrorMessage(textbox);
    } 
</script>

<?php include 'components/footer.php'; ?>
<script src="../validation/validate.js"> </script>
