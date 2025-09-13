<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config/db_conn.php';

// Truncate table to start fresh
$connection->query("TRUNCATE TABLE diet_plans");

// Define combinations
$goals      = ['weight_loss', 'weight_gain', 'muscle_build', 'balanced'];
$dietary    = ['veg', 'nonveg'];
$activities = ['light', 'moderate', 'active'];
$meal_types = ['3_meals', '5_small'];

// Sample meals separated by dietary preference
$sampleMeals = [
    'veg' => [
        '3_meals' => [
            ['meal_time'=>'breakfast', 'meal_text'=>'Oatmeal with fruits', 'protein'=>20,'carbs'=>30,'fat'=>10,'calories'=>300],
            ['meal_time'=>'lunch', 'meal_text'=>'Paneer salad with quinoa', 'protein'=>25,'carbs'=>40,'fat'=>15,'calories'=>500],
            ['meal_time'=>'dinner', 'meal_text'=>'Grilled tofu with veggies', 'protein'=>25,'carbs'=>35,'fat'=>15,'calories'=>450],
        ],
        '5_small' => [
            ['meal_time'=>'breakfast','meal_text'=>'Smoothie bowl','protein'=>15,'carbs'=>25,'fat'=>5,'calories'=>250],
            ['meal_time'=>'mid_morning','meal_text'=>'Fruit salad','protein'=>10,'carbs'=>15,'fat'=>5,'calories'=>150],
            ['meal_time'=>'lunch','meal_text'=>'Quinoa and veggies','protein'=>20,'carbs'=>35,'fat'=>10,'calories'=>450],
            ['meal_time'=>'snack','meal_text'=>'Nuts and yogurt','protein'=>10,'carbs'=>15,'fat'=>5,'calories'=>150],
            ['meal_time'=>'dinner','meal_text'=>'Grilled paneer with salad','protein'=>20,'carbs'=>30,'fat'=>10,'calories'=>400],
        ]
    ],
    'nonveg' => [
        '3_meals' => [
            ['meal_time'=>'breakfast', 'meal_text'=>'Egg omelette with toast', 'protein'=>20,'carbs'=>30,'fat'=>10,'calories'=>300],
            ['meal_time'=>'lunch', 'meal_text'=>'Grilled chicken salad', 'protein'=>25,'carbs'=>40,'fat'=>15,'calories'=>500],
            ['meal_time'=>'dinner', 'meal_text'=>'Steamed fish with veggies', 'protein'=>25,'carbs'=>35,'fat'=>15,'calories'=>450],
        ],
        '5_small' => [
            ['meal_time'=>'breakfast','meal_text'=>'Protein smoothie with eggs','protein'=>15,'carbs'=>25,'fat'=>5,'calories'=>250],
            ['meal_time'=>'mid_morning','meal_text'=>'Chicken salad','protein'=>10,'carbs'=>15,'fat'=>5,'calories'=>150],
            ['meal_time'=>'lunch','meal_text'=>'Grilled chicken with rice','protein'=>20,'carbs'=>35,'fat'=>10,'calories'=>450],
            ['meal_time'=>'snack','meal_text'=>'Yogurt with boiled eggs','protein'=>10,'carbs'=>15,'fat'=>5,'calories'=>150],
            ['meal_time'=>'dinner','meal_text'=>'Fish curry with veggies','protein'=>20,'carbs'=>30,'fat'=>10,'calories'=>400],
        ]
    ]
];

// Insert meals correctly based on dietary type
foreach($goals as $g){
    foreach($dietary as $d){
        foreach($activities as $a){
            foreach($meal_types as $m){
                for($day=1; $day<=7; $day++){
                    foreach($sampleMeals[$d][$m] as $meal){
                        // Skip if any required field is empty
                        if(empty($meal['meal_time']) || empty($meal['meal_text'])) continue;

                        // Check if the row already exists
                        $check = $connection->prepare("
                            SELECT id FROM diet_plans 
                            WHERE goal=? AND dietary=? AND activity=? AND meal_type=? AND day_number=? AND meal_time=?
                        ");
                        $check->bind_param("ssssis", $g, $d, $a, $m, $day, $meal['meal_time']);
                        $check->execute();
                        $check->store_result();

                        if($check->num_rows == 0){
                            // Insert only if not exists
                            $stmt = $connection->prepare("
                                INSERT INTO diet_plans
                                (goal,dietary,activity,meal_type,day_number,meal_time,meal_text,protein,carbs,fat,calories)
                                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                            ");

                            $stmt->bind_param(
                                "ssssissiiii",
                                $g,                 // goal
                                $d,                 // dietary
                                $a,                 // activity
                                $m,                 // meal_type
                                $day,               // day_number
                                $meal['meal_time'], // meal_time
                                $meal['meal_text'], // meal_text
                                $meal['protein'],   // protein
                                $meal['carbs'],     // carbs
                                $meal['fat'],       // fat
                                $meal['calories']   // calories
                            );

                            $stmt->execute();
                            $stmt->close();
                        }

                        $check->close();
                    }
                }
            }
        }
    }
}

echo "✅ Diet plans inserted successfully with proper veg/non-veg separation and no duplicates!";
