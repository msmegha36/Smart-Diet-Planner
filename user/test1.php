<?php
include '../config/db_conn.php';
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Clear old swaps to start fresh
$connection->query("TRUNCATE TABLE meal_swaps");

// Fetch all meals grouped by dietary + meal_type + meal_time
$result = $connection->query("
    -- Note: We are fetching from the diet_plans table after it has been populated by the generation script
    SELECT meal_text, protein, carbs, fat, calories, dietary, meal_type, meal_time 
    FROM diet_plans
");

$meals = [];
while($row = $result->fetch_assoc()){
    // Clean and normalize the key data
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

// Prepare insert statement
$insertStmt = $connection->prepare("
    INSERT INTO meal_swaps
    (meal_hash, alternative_text, protein, carbs, fat, calories, dietary)
    VALUES (?, ?, ?, ?, ?, ?, ?)
");
if(!$insertStmt){
    die("Prepare failed: ".$connection->error);
}

// Stores processed pairs (e.g., 'Meal A|Meal B') to prevent double work
$processedPairs = [];
$totalSwapsInserted = 0;

foreach($meals as $dietary => $types){
    foreach($types as $meal_type => $times){
        foreach($times as $meal_time => $mealPool){
            $count = count($mealPool);
            
            // Loop through every meal (Meal A) in the pool
            for($i=0; $i<$count; $i++){
                $mealA = $mealPool[$i];
                $hashA = hash('sha256', $mealA['meal_text']);

                // Loop through every other meal (Meal B) as a potential alternative
                for($j=0; $j<$count; $j++){
                    if($i == $j) continue; // Skip swapping a meal with itself

                    $mealB = $mealPool[$j];
                    $hashB = hash('sha256', $mealB['meal_text']);

                    // Create a canonical key (alphabetical) to check if the pair has been processed
                    $pairKey = $mealA['meal_text'] < $mealB['meal_text']
                        ? $mealA['meal_text'].'|'.$mealB['meal_text']
                        : $mealB['meal_text'].'|'.$mealA['meal_text'];
                    
                    // If this pair (regardless of direction A->B or B->A) has already been processed, skip.
                    if(isset($processedPairs[$pairKey])) continue;
                    
                    // --- 1. Insert Swap A -> B ---
                    $insertStmt->bind_param(
                        "ssiiiis",
                        $hashA,               // meal_hash: Hash of Meal A
                        $mealB['meal_text'],  // alternative_text: Meal B
                        $mealB['protein'],
                        $mealB['carbs'],
                        $mealB['fat'],
                        $mealB['calories'],
                        $dietary
                    );
                    $insertStmt->execute();
                    $totalSwapsInserted++;

                    // --- 2. Insert Swap B -> A (The required reverse swap) ---
                    $insertStmt->bind_param(
                        "ssiiiis",
                        $hashB,               // meal_hash: Hash of Meal B
                        $mealA['meal_text'],  // alternative_text: Meal A
                        $mealA['protein'],
                        $mealA['carbs'],
                        $mealA['fat'],
                        $mealA['calories'],
                        $dietary
                    );
                    $insertStmt->execute();
                    $totalSwapsInserted++;

                    // Mark the pair as processed to skip future iterations (e.g., when i=j and j=i)
                    $processedPairs[$pairKey] = true;
                }
            }
        }
    }
}

$insertStmt->close();
echo "✅ Meal swaps generated successfully for each dietary type, meal_type, and meal_time! Total bidirectional swaps: " . $totalSwapsInserted;
?>
