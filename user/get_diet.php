<?php
session_start();
include(__DIR__ . '/../config/db_conn.php');  // adjust path if needed

// Handle only GET requests
if ($_SERVER["REQUEST_METHOD"] === "GET") {
    
    // Collect input safely
    $goal     = mysqli_real_escape_string($connection, $_GET['goal'] ?? '');
    $dietary  = mysqli_real_escape_string($connection, $_GET['dietary'] ?? '');
    $activity = mysqli_real_escape_string($connection, $_GET['activity'] ?? '');

    // Validate required fields
    if (!empty($goal) && !empty($dietary) && !empty($activity)) {

        // Fetch matching plan
        $query = "SELECT * FROM diet_plans 
                  WHERE goal='$goal' AND dietary='$dietary' AND activity='$activity' 
                  LIMIT 1";
        $result = mysqli_query($connection, $query);

        if ($row = mysqli_fetch_assoc($result)) {
            echo json_encode([
                "success" => true,
                "plan"    => $row
            ]);
        } else {
            echo json_encode([
                "success" => false,
                "message" => "No matching plan found"
            ]);
        }

    } else {
        echo json_encode([
            "success" => false,
            "message" => "Missing required parameters"
        ]);
    }
    
} else {
    echo json_encode([
        "success" => false,
        "message" => "Invalid request method"
    ]);
}
?>
