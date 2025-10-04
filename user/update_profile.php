<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . '/../config/db_conn.php');

// Check if the request is a POST and the user is logged in
if ($_SERVER["REQUEST_METHOD"] !== "POST" || !isset($_SESSION['user_id'])) {
    header("Location: ../home/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// 1. Sanitize and retrieve ALL input fields, including the new 'health_issues'
$name          = mysqli_real_escape_string($connection, $_POST['name']);
$email         = mysqli_real_escape_string($connection, $_POST['email']);
$age           = mysqli_real_escape_string($connection, $_POST['age']);
$height        = mysqli_real_escape_string($connection, $_POST['height']);
$weight        = mysqli_real_escape_string($connection, $_POST['weight']);
$health_issues = mysqli_real_escape_string($connection, $_POST['health_issues']); // New field

// 2. Update user info in the main 'reg' table
$update_reg_sql = "UPDATE reg SET 
                   name='$name', 
                   email='$email', 
                   age='$age', 
                   height='$height', 
                   weight='$weight',
                   health_issues='$health_issues' 
                   WHERE id='$user_id'";

if (mysqli_query($connection, $update_reg_sql)) {
    // 3. Log the NEW profile state (weight, height, and health issue) into progress_history
    // This correctly logs the data the user just submitted, assuming the 
    // progress_history table has been updated to include a 'health_issues' column.
    $insert_history_sql = "INSERT INTO progress_history (user_id, weight, height, health_issues) 
                           VALUES ('$user_id', '$weight', '$height', '$health_issues')";
                           
    if (!mysqli_query($connection, $insert_history_sql)) {
        error_log("Progress history log failed: " . mysqli_error($connection));
        // Redirect with a warning if history failed, but main profile succeeded
        header("Location: profile.php?status=warning");
        exit();
    }

    // Redirect back to the profile page on success
    header("Location: index.php?status=success");
    exit();
} else {
    // Handle error
    error_log("Profile update failed: " . mysqli_error($connection));
    header("Location: index.php?status=error");
    exit();
}
?>
