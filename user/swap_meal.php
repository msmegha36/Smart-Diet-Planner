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
    $meal_stmt = $connection->prepare("SELECT * FROM user_diet_plans WHERE id=? AND user_id=?");
    $meal_stmt->bind_param("ii", $meal_id, $user_id);
    $meal_stmt->execute();
    $meal_res = $meal_stmt->get_result();
    $meal = $meal_res->fetch_assoc();
    $meal_stmt->close();

    if (!$meal) {
        $_SESSION['error'] = "Invalid meal selected.";
        header("Location: diet_plans.php");
        exit();
    }

    $currentHash = hash('sha256', $meal['meal_text']);

    // Get dietary type of the current meal
    $diet_stmt = $connection->prepare("SELECT dietary FROM diet_plans WHERE meal_text=? LIMIT 1");
    $diet_stmt->bind_param("s", $meal['meal_text']);
    $diet_stmt->execute();
    $diet_row = $diet_stmt->get_result()->fetch_assoc();
    $diet_stmt->close();

    $dietary = $diet_row['dietary'] ?? null;

    if (!$dietary) {
        $_SESSION['error'] = "Cannot determine dietary type of the selected meal.";
        header("Location: user_dietPlan.php");
        exit();
    }

    // Fetch alternative meals with SAME dietary type and similar macros (tolerance)
    $macroTolerance = [
        'protein' => 2,  // ±2g
        'carbs'   => 5,  // ±5g
        'fat'     => 2,  // ±2g
        'calories'=> 30  // ±30 kcal
    ];

    $stmt = $connection->prepare("
        SELECT * FROM meal_swaps
        WHERE meal_hash=? 
          AND alternative_text != ?
          AND ABS(protein - ?) <= ?
          AND ABS(carbs - ?) <= ?
          AND ABS(fat - ?) <= ?
          AND ABS(calories - ?) <= ?
          AND dietary=?
        ORDER BY RAND()
        LIMIT 1
    ");

   $stmt->bind_param(
    "ssiiiiiiiis",
    $currentHash,
    $meal['meal_text'],
    $meal['protein'], $macroTolerance['protein'],
    $meal['carbs'],   $macroTolerance['carbs'],
    $meal['fat'],     $macroTolerance['fat'],
    $meal['calories'],$macroTolerance['calories'],
    $dietary
);


    $stmt->execute();
    $alt_res = $stmt->get_result();

    if ($alt_res && $alt_res->num_rows > 0) {
        $alt = $alt_res->fetch_assoc();

        // Update user's plan
        $update_stmt = $connection->prepare("
            UPDATE user_diet_plans
            SET meal_text=?, protein=?, carbs=?, fat=?, calories=?
            WHERE id=? AND user_id=?
        ");
        $update_stmt->bind_param(
            "siiiiii",
            $alt['alternative_text'],
            $alt['protein'],
            $alt['carbs'],
            $alt['fat'],
            $alt['calories'],
            $meal_id,
            $user_id
        );

        if ($update_stmt->execute()) {
            $_SESSION['success'] = "Meal swapped successfully with a matching alternative!";
        } else {
            $_SESSION['error'] = "Error swapping meal: " . $update_stmt->error;
        }
        $update_stmt->close();
    } else {
        $_SESSION['error'] = "⚠️ No alternative swap available with similar macros and dietary type.";
    }

    $stmt->close();
    header("Location: user_dietPlan.php");
    exit();
}
