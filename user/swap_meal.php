<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . '/../config/db_conn.php');

// Redirect if not logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../home/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

if (!isset($_POST['meal_id'])) {
    $_SESSION['error'] = "Invalid request!";
    header("Location: user_diet_plans.php");
    exit();
}

$meal_id = intval($_POST['meal_id']);

// Fetch the meal to swap
$meal = mysqli_fetch_assoc(mysqli_query($connection, "SELECT * FROM user_diet_plans WHERE id='$meal_id' AND user_id='$user_id'"));

if (!$meal) {
    $_SESSION['error'] = "Meal not found!";
    header("Location: user_diet_plans.php");
    exit();
}

// Find a random alternative meal from diet_plans matching user's goal, dietary, activity, meal_type
$alt_meal_sql = "SELECT * FROM diet_plans 
                 WHERE goal = (SELECT goal FROM reg WHERE id='$user_id')
                   AND dietary = (SELECT dietary FROM reg WHERE id='$user_id')
                   AND activity = (SELECT activity FROM reg WHERE id='$user_id')
                   AND meal_type = '{$meal['meal_time']}'
                 ORDER BY RAND() LIMIT 1";

$alt_meal_res = mysqli_query($connection, $alt_meal_sql);
$alt_meal = mysqli_fetch_assoc($alt_meal_res);

if (!$alt_meal) {
    $_SESSION['error'] = "No alternative meal available for swapping!";
    header("Location: user_dietPlan.php");
    exit();
}

// Update user's meal with the alternative meal
$update_sql = "UPDATE user_diet_plans SET 
                meal_text = '".mysqli_real_escape_string($connection, $alt_meal['meal_text'])."',
                protein = {$alt_meal['protein']},
                carbs = {$alt_meal['carbs']},
                fat = {$alt_meal['fat']},
                calories = {$alt_meal['calories']}
               WHERE id='$meal_id' AND user_id='$user_id'";

if (mysqli_query($connection, $update_sql)) {
    $_SESSION['success'] = "Meal swapped successfully!";
} else {
    $_SESSION['error'] = "Failed to swap meal. Please try again.";
}

header("Location: user_dietPlan.php");
exit();
?>
