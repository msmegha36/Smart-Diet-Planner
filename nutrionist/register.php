<?php 
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include(__DIR__ . '/../config/db_conn.php');
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Sanitize and escape POST data
    $name  = mysqli_real_escape_string($connection, $_POST['name']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = mysqli_real_escape_string($connection, $_POST['password']);
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $specialization = mysqli_real_escape_string($connection, $_POST['specialization']);
    // Ensure experience is treated as an integer
    $experience = isset($_POST['experience']) ? (int)$_POST['experience'] : 0;
    $description = mysqli_real_escape_string($connection, $_POST['description']);

    // Handle Image Upload
    $imagePath = "";
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) { mkdir($targetDir, 0777, true); }
        $imagePath = $targetDir . time() . "_" . basename($_FILES['image']['name']);
        
        // Basic file type check before moving
        $imageFileType = strtolower(pathinfo($imagePath, PATHINFO_EXTENSION));
        if (in_array($imageFileType, ['jpg', 'png', 'jpeg', 'gif'])) {
            move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
        } else {
            // Handle invalid file type error gracefully
            $imagePath = ""; 
            // Note: A real application should set a user-facing error here.
        }
    }

    if (!empty($name) && !empty($email) && !empty($password)) {
        // Check if email already exists
        $checkEmail = "SELECT id FROM nutritionists WHERE email='$email' LIMIT 1";
        $result = mysqli_query($connection, $checkEmail);

        if (mysqli_num_rows($result) > 0) {
            $error = "Email already registered. Please login.";
        } else {
            // Hash password using md5 (Consider using a stronger hashing algorithm like password_hash in production)
            $hashed_pass = md5($password);

            $sql = "INSERT INTO nutritionists 
                (name, email, password, phone, specialization, experience, description, image, status) 
                VALUES ('$name','$email','$hashed_pass','$phone','$specialization','$experience','$description','$imagePath','pending')";

            if (mysqli_query($connection, $sql)) {
                echo "<script>alert('Registration Successful 🎉. Wait for Admin Approval.'); window.location='login.php';</script>";
                exit();
            } else {
                $error = "Error: " . mysqli_error($connection);
            }
        }
    } else {
        $error = "Please fill all required fields (Name, Email, Password).";
    }
}

?>

<?php include 'components/head.php'; ?>

<!-- Include Tailwind via CDN -->
<script src="https://cdn.tailwindcss.com"></script>

<style>
    /* Custom style for validation feedback */
    input:invalid:not(:placeholder-shown), textarea:invalid:not(:placeholder-shown) {
        border-color: #ef4444; /* red-500 */
    }
    .error-message {
        color: #ef4444;
        margin-top: 0.25rem;
        font-size: 0.875rem;
        /* Tailwind classes for hiding/showing */
    }
</style>

<?php include 'components/navbar.php'; ?>

<main class="flex items-center justify-center min-h-screen bg-gradient-to-r from-purple-200 to-blue-100 px-4 py-10">
  <div class="bg-white shadow-xl rounded-2xl w-full max-w-2xl p-10">
    <h1 class="text-3xl font-bold text-center text-purple-700 mb-4">👨‍⚕️ Nutritionist Register</h1>
    <p class="text-gray-500 text-center mb-6">Fill in your details. Admin will review and approve.</p>

    <?php if (!empty($error)): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4">
        <?= $error ?>
      </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" class="space-y-5">
      <!-- Full Name (Required) -->
      <div>
          <input type="text" name="name" id="name" placeholder="Full Name (Required)" required 
            oninput="validateRequired(this, 'Full Name')"
            class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500">
          <span class="error-message hidden" id="name-error"></span>
      </div>

      <!-- Email (Required, Format Validation) -->
      <div>
          <input type="email" name="email" id="email" placeholder="Email (Required)" required 
            oninput="validateEmail(this)"
            class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500">
          <span class="error-message hidden" id="email-error"></span>
      </div>

      <!-- Password (Required, Min Length Validation) -->
      <div>
          <input type="password" name="password" id="password" placeholder="Password (Min 8 characters)" required 
            oninput="validatePassword(this)"
            class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500">
          <span class="error-message hidden" id="password-error"></span>
      </div>

      <!-- Phone Number (Optional, Format Validation) -->
      <div>
          <input type="text" name="phone" id="phone" placeholder="Phone Number (Optional)" 
            oninput="validatePhone(this)" required 
            class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500">
          <span class="error-message hidden" id="phone-error"></span>
      </div>

      <!-- Specialization (Optional) -->
      <input type="text" name="specialization" id="specialization" placeholder="Specialization (e.g. Sports, Diabetes) (Optional)" 
        class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500" required >

      <!-- Experience (Optional, Number Validation) -->
      <div>
          <input type="number" name="experience" id="experience" placeholder="Experience (years, must be ≥ 0)" min="0"  required 
            oninput="validateNumber(this, 'Experience')"
            class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500">
          <span class="error-message hidden" id="experience-error"></span>
      </div>

      <!-- Description (Optional) -->
      <textarea name="description" id="description" placeholder="About You (Optional)" rows="4"  required 
        class="w-full border rounded-lg px-4 py-3 focus:ring-2 focus:ring-purple-500"></textarea>
      
      <!-- Image Upload (Optional) -->
      <div>
        <label for="image" class="block text-gray-700 font-medium mb-2">Upload Profile Image (Optional)</label>
        <input type="file" name="image" id="image" accept="image/*" class="w-full border rounded-lg px-4 py-3 bg-gray-50 focus:ring-2 focus:ring-purple-500" required >
      </div>


      <button type="submit" class="w-full bg-purple-600 text-white font-semibold text-lg py-3 rounded-lg hover:bg-purple-700 transition">
        Register
      </button>
    </form>

    <p class="text-center text-gray-600 mt-6">
      Already registered? <a href="login.php" class="text-purple-600 font-semibold hover:underline">Login</a>
    </p>
  </div>
</main>


<!-- JavaScript for real-time validation -->
<script>
    // Helper function to show/hide the error message span
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

    // --- EMAIL VALIDATION ---
    function validateEmail(textbox) {
        textbox.setCustomValidity('');

        if (textbox.validity.valueMissing) {
            textbox.setCustomValidity('Entering an email address is required!');
        } else if (textbox.validity.typeMismatch) {
            textbox.setCustomValidity('Please enter a valid email address (e.g., user@example.com).');
        }
        
        updateErrorMessage(textbox);
    } 

    // --- PASSWORD VALIDATION (Min 8 chars) ---
// --- UPDATED PASSWORD VALIDATION ---
function validatePassword(textbox) {
    // MUST clear validity first to allow subsequent checks to pass or fail
    textbox.setCustomValidity(''); 

    const value = textbox.value;
    
    // Regular expression to check for minimum complexity:
    // ^: start of string
    // (?=.*[a-z]): Lookahead for at least one lowercase letter
    // (?=.*[A-Z]): Lookahead for at least one uppercase letter
    // (?=.*[0-9]): Lookahead for at least one number
    // [a-zA-Z0-9!@#$%^&*()_+={}\[\]:;<>,.?\/\\~-]{8,}: Matches any allowed character (including special), minimum 8 times
    // $: end of string
    const passwordPattern = /^(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])[a-zA-Z0-9!@#$%^&*()_+={}\[\]:;<>,.?\/\\~-]{8,}$/;

    if (value === '') {
        textbox.setCustomValidity('A password is required!');
    } 
    // Use the single pattern check
    else if (!passwordPattern.test(value)) {
        textbox.setCustomValidity('Password must be at least 8 characters long and contain at least one uppercase letter, one lowercase letter, and one number. Special characters are allowed.');
    }
    
    // Returns true, but the setCustomValidity call handles the error message.
    return true; 
} 

    // --- PHONE NUMBER VALIDATION (Basic pattern check - optional field) ---
    function validatePhone(textbox) {
        textbox.setCustomValidity('');
        // Basic pattern for digits, spaces, hyphens, and optional '+' at the start
        const phoneRegex = /^\+?[\d\s-]{7,20}$/; 
        
        if (textbox.value.trim() !== '' && !phoneRegex.test(textbox.value)) {
            textbox.setCustomValidity('Please enter a valid phone number (digits, spaces, hyphens only).');
        }

        updateErrorMessage(textbox);
    }

    // --- NUMBER VALIDATION (For Experience - optional field) ---
    function validateNumber(textbox, fieldName) {
        textbox.setCustomValidity('');
        
        // Only validate if the field is not empty
        if (textbox.value.trim() !== '') {
             if (textbox.validity.rangeUnderflow) {
                textbox.setCustomValidity(`${fieldName} cannot be negative.`);
            } else if (textbox.validity.badInput || isNaN(textbox.value)) {
                // Check if it's a number and not a float if 'step' isn't defined, 
                // but relying on type="number" and range is generally enough.
                textbox.setCustomValidity(`${fieldName} must be a valid non-negative number.`);
            }
        }
        
        updateErrorMessage(textbox);
    }
</script>

<?php include 'components/footer.php'; ?>
