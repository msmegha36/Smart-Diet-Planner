<?php
include '../config/db_conn.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// Ensure dietary column exists

// Clear old swaps
$connection->query("TRUNCATE TABLE meal_swaps");

// Fetch all meals grouped by dietary + meal_type + meal_time
$result = $connection->query("
    SELECT meal_text, protein, carbs, fat, calories, dietary, meal_type, meal_time 
    FROM diet_plans
");

$meals = [];
while($row = $result->fetch_assoc()){
    $dietary = strtolower(trim($row['dietary']));
    $meal_type = $row['meal_type'];
    $meal_time = $row['meal_time'];
    $meals[$dietary][$meal_type][$meal_time][] = [
        'meal_text' => $row['meal_text'],
        'protein' => (int)$row['protein'],
        'carbs'   => (int)$row['carbs'],
        'fat'     => (int)$row['fat'],
        'calories'=> (int)$row['calories']
    ];
}

// ✅ Prepare insert once
$insertStmt = $connection->prepare("
    INSERT INTO meal_swaps
    (meal_hash, alternative_text, protein, carbs, fat, calories, dietary)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
if(!$insertStmt){
    die("Prepare failed: ".$connection->error);
}

$insertedPairs = [];

foreach($meals as $dietary => $types){
    foreach($types as $meal_type => $times){
        foreach($times as $meal_time => $mealPool){
            $count = count($mealPool);
            for($i=0; $i<$count; $i++){
                $meal = $mealPool[$i];
                for($j=0; $j<$count; $j++){
                    if($i == $j) continue;

                    $alt = $mealPool[$j];

                    // Avoid duplicate reverse
                    $pairKey = $meal['meal_text'] < $alt['meal_text']
                        ? $meal['meal_text'].'|'.$alt['meal_text']
                        : $alt['meal_text'].'|'.$meal['meal_text'];
                    if(isset($insertedPairs[$pairKey])) continue;
                    $insertedPairs[$pairKey] = true;

                    $mealHash = hash('sha256', $meal['meal_text']);
                    $altText  = $alt['meal_text'];

                    // Bind and execute
                    $insertStmt->bind_param(
                        "ssiiiis",
                        $mealHash,
                        $altText,
                        $alt['protein'],
                        $alt['carbs'],
                        $alt['fat'],
                        $alt['calories'],
                        $dietary
                    );
                    $insertStmt->execute();

                    // Optional: insert reverse swap
                    $altHash = hash('sha256', $altText);
                    $insertStmt->bind_param(
                        "ssiiiis",
                        $altHash,
                        $meal['meal_text'],
                        $meal['protein'],
                        $meal['carbs'],
                        $meal['fat'],
                        $meal['calories'],
                        $dietary
                    );
                    $insertStmt->execute();
                }
            }
        }
    }
}

$insertStmt->close();
echo "✅ Meal swaps generated successfully for each dietary type, meal_type, and meal_time!";
