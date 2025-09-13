<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . '/../config/db_conn.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../home/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meal_id'])) {
    $meal_id = intval($_POST['meal_id']);

    // Get current meal details
    $meal_res = mysqli_query($connection, "SELECT * FROM user_diet_plans WHERE id='$meal_id' AND user_id='$user_id'");
    $meal = mysqli_fetch_assoc($meal_res);

    if (!$meal) {
        $_SESSION['error'] = "Invalid meal selected.";
        header("Location: diet_plans.php");
        exit();
    }

    // Hash current meal text
    $currentHash = hash('sha256', $meal['meal_text']);

    // Fetch an alternative meal that is not the same as the current one
    $alt_res = mysqli_query($connection, "
        SELECT * FROM meal_swaps 
        WHERE meal_hash='$currentHash' AND alt_hash != '$currentHash' 
        ORDER BY RAND() LIMIT 1
    ");

    if (mysqli_num_rows($alt_res) > 0) {
        $alt = mysqli_fetch_assoc($alt_res);

        // Update user's plan with the alternative meal
        $stmt = $connection->prepare("
            UPDATE user_diet_plans
            SET meal_text=?, protein=?, carbs=?, fat=?, calories=?
            WHERE id=? AND user_id=?
        ");
        $stmt->bind_param(
            "siiiiii",
            $alt['alternative_text'],
            $alt['protein'],
            $alt['carbs'],
            $alt['fat'],
            $alt['calories'],
            $meal_id,
            $user_id
        );

        if ($stmt->execute()) {
            $_SESSION['success'] = "Meal swapped successfully!";
        } else {
            $_SESSION['error'] = "Error swapping meal: " . $stmt->error;
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "⚠️ No alternative swap available for this meal.";
    }

    header("Location: user_dietPlan.php");
    exit();
}
