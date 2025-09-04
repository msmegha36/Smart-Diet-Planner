<?php
session_start();
include(__DIR__ . '/../config/db_conn.php');

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];

    $name   = mysqli_real_escape_string($connection, $_POST['name']);
    $email  = mysqli_real_escape_string($connection, $_POST['email']);
    $age    = mysqli_real_escape_string($connection, $_POST['age']);
    $height = mysqli_real_escape_string($connection, $_POST['height']);
    $weight = mysqli_real_escape_string($connection, $_POST['weight']);

    // Save old data into history before updating
    $old_data = mysqli_query($connection, "SELECT height, weight FROM reg WHERE id='$user_id'");
    $old = mysqli_fetch_assoc($old_data);
    mysqli_query($connection, "INSERT INTO progress_history (user_id, weight, height) VALUES ('$user_id', '{$old['weight']}', '{$old['height']}')");

    // Update user info
    $update = "UPDATE reg SET name='$name', email='$email', age='$age', height='$height', weight='$weight' WHERE id='$user_id'";
    if (mysqli_query($connection, $update)) {
        echo "<script>alert('Profile updated successfully ✅'); window.location='index.php';</script>";
    } else {
        echo "<script>alert('Error: " . mysqli_error($connection) . "');</script>";
    }
}
?>
