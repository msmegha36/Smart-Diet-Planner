<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . '/../config/db_conn.php');

// Only allow POST
if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $nutritionist_id = intval($_POST['nutritionist_id']);

    // Sanitize inputs
    $name           = mysqli_real_escape_string($connection, $_POST['name']);
    $email          = mysqli_real_escape_string($connection, $_POST['email']);
    $phone          = mysqli_real_escape_string($connection, $_POST['phone']);
    $specialization = mysqli_real_escape_string($connection, $_POST['specialization']);
    $experience     = intval($_POST['experience']);
    $description    = mysqli_real_escape_string($connection, $_POST['description']);

    // Handle image upload
    $imagePath = null;
    if (!empty($_FILES['image']['name'])) {
        $uploadDir = __DIR__ . "/uploads/";
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fileName   = time() . "_" . basename($_FILES['image']['name']);
        $targetFile = $uploadDir . $fileName;

        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            // Save relative path for DB
            $imagePath = "uploads/" . $fileName;
        }
    }

    // Build SQL query dynamically
    if ($imagePath) {
        $sql = "UPDATE nutritionists 
                SET name=?, email=?, phone=?, specialization=?, experience=?, description=?, image=? 
                WHERE id=?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("ssssiisi", $name, $email, $phone, $specialization, $experience, $description, $imagePath, $nutritionist_id);
    } else {
        $sql = "UPDATE nutritionists 
                SET name=?, email=?, phone=?, specialization=?, experience=?, description=? 
                WHERE id=?";
        $stmt = $connection->prepare($sql);
        $stmt->bind_param("ssssisi", $name, $email, $phone, $specialization, $experience, $description, $nutritionist_id);
    }

    if ($stmt->execute()) {
        echo "<script>alert('Profile updated successfully ✅'); window.location='index.php';</script>";
    } else {
        echo "<script>alert('Error updating profile ❌'); window.history.back();</script>";
    }
}
?>
