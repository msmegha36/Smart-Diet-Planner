<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

include(__DIR__ . '/../config/db_conn.php');
$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name  = mysqli_real_escape_string($connection, $_POST['name']);
    $email = mysqli_real_escape_string($connection, $_POST['email']);
    $password = mysqli_real_escape_string($connection, $_POST['password']);
    $phone = mysqli_real_escape_string($connection, $_POST['phone']);
    $specialization = mysqli_real_escape_string($connection, $_POST['specialization']);
    $experience = (int)$_POST['experience'];
    $description = mysqli_real_escape_string($connection, $_POST['description']);

    // Handle Image Upload
    $imagePath = "";
    if (!empty($_FILES['image']['name'])) {
        $targetDir = "uploads/";
        if (!is_dir($targetDir)) { mkdir($targetDir, 0777, true); }
        $imagePath = $targetDir . time() . "_" . basename($_FILES['image']['name']);
        move_uploaded_file($_FILES['image']['tmp_name'], $imagePath);
    }

    if (!empty($name) && !empty($email) && !empty($password)) {
        // Check if email already exists
        $checkEmail = "SELECT id FROM nutritionists WHERE email='$email' LIMIT 1";
        $result = mysqli_query($connection, $checkEmail);

        if (mysqli_num_rows($result) > 0) {
            $error = "Email already registered. Please login.";
        } else {
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
        $error = "Please fill all required fields.";
    }
}

?>


<?php include 'components/head.php'; ?>

<?php include 'components/navbar.php'; ?>

<main class="flex items-center justify-center min-h-screen bg-gradient-to-r from-purple-200 to-blue-100 px-4 mt-10">
  <div class="bg-white shadow-xl rounded-2xl w-full max-w-2xl p-10">
    <h1 class="text-3xl font-bold text-center text-purple-700 mb-4">👨‍⚕️ Nutritionist Register</h1>
    <p class="text-gray-500 text-center mb-6">Fill in your details. Admin will review and approve.</p>

    <?php if (!empty($error)): ?>
      <div class="bg-red-100 text-red-700 px-4 py-2 rounded mb-4">
        <?= $error ?>
      </div>
    <?php endif; ?>

    <form action="" method="POST" enctype="multipart/form-data" class="space-y-5">
      <input type="text" name="name" placeholder="Full Name" required class="w-full border rounded-lg px-4 py-3">
      <input type="email" name="email" placeholder="Email" required class="w-full border rounded-lg px-4 py-3">
      <input type="password" name="password" placeholder="Password" required class="w-full border rounded-lg px-4 py-3">
      <input type="text" name="phone" placeholder="Phone Number" class="w-full border rounded-lg px-4 py-3">
      <input type="text" name="specialization" placeholder="Specialization (e.g. Sports, Diabetes)" class="w-full border rounded-lg px-4 py-3">
      <input type="number" name="experience" placeholder="Experience (years)" class="w-full border rounded-lg px-4 py-3">
      <textarea name="description" placeholder="About You" rows="4" class="w-full border rounded-lg px-4 py-3"></textarea>
      <input type="file" name="image" accept="image/*" class="w-full border rounded-lg px-4 py-3">

      <button type="submit" class="w-full bg-purple-600 text-white font-semibold text-lg py-3 rounded-lg hover:bg-purple-700 transition">
        Register
      </button>
    </form>

    <p class="text-center text-gray-600 mt-6">
      Already registered? <a href="login.php" class="text-purple-600 font-semibold hover:underline">Login</a>
    </p>
  </div>
</main>


<?php include 'components/footer.php'; ?>