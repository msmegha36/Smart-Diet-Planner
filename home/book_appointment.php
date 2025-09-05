<?php
session_start();

error_reporting(E_ALL);
ini_set('display_errors', 1);
include(__DIR__ . '/../config/db_conn.php'); // DB connection

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    echo "<script>alert('Please login first.'); window.location.href='login.php';</script>";
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id        = $_SESSION['user_id'];
    $nutritionist_id = intval($_POST['nutritionist_id']);

    // Use session values (from login/registration)
    $name  = $_SESSION['name'];
    $email = $_SESSION['email'];
     // ✅ Ensure you store phone in session at login

    $date  = $_POST['date'];
    $phone  = $_POST['phone'];
    $time  = $_POST['time'];
    $notes = mysqli_real_escape_string($connection, $_POST['notes']);

    // Validate date & time
    $appointment_datetime = strtotime("$date $time");
    $now = time();

    if ($appointment_datetime <= $now) {
        echo "<script>alert('Please select a valid future date and time.'); window.history.back();</script>";
        exit();
    }

    // Insert appointment
    $sql = "INSERT INTO appointments 
            (user_id, nutritionist_id, name, email, phone, appointment_date, appointment_time, notes, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')";

    $stmt = $connection->prepare($sql);
    $stmt->bind_param(
        "iissssss",
        $user_id,
        $nutritionist_id,
        $name,
        $email,
        $phone,
        $date,
        $time,
        $notes
    );

    if ($stmt->execute()) {
        echo "<script>alert('Appointment booked successfully!'); window.location.href='../user/index.php';</script>";
    } else {
        echo "<script>alert('Error booking appointment. Please try again.'); window.history.back();</script>";
    }
}
?>
