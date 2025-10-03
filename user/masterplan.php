<?php
// generate_diet_plans.php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config/db_conn.php';

// Truncate diet_plans table to start fresh
$connection->query("TRUNCATE TABLE diet_plans");

// --- 1. FETCH MEAL LIBRARY FROM DATABASE ---
$masterMeals = [];
$masterQuery = "SELECT dietary, meal_type_plan, meal_time, meal_text, protein, carbs, fat, calories 
                FROM master_meals";
$result = $connection->query($masterQuery);

if ($result) {
    while ($row = $result->fetch_assoc()) {
        $d = $row['dietary'];
        $m = $row['meal_type_plan'];
        $mt = $row['meal_time'];
        
        // Structure the data in the same way the original array was structured for easy looping
        $masterMeals[$d][$m][$mt][] = [
            'meal_text' => $row['meal_text'],
            'protein'   => $row['protein'],
            'carbs'     => $row['carbs'],
            'fat'       => $row['fat'],
            'calories'  => $row['calories'],
        ];
    }
} else {
    die("Error fetching master meals: " . $connection->error);
}

// Check if any meals were fetched
if (empty($masterMeals)) {
    die("Error: No meals found in the master_meals table.");
}

// --- 2. DEFINE COMBINATIONS ---
$goals      = ['weight_loss', 'weight_gain', 'muscle_build', 'balanced'];
$dietary    = array_keys($masterMeals); // Use fetched dietary types
$activities = ['light', 'moderate', 'active'];
// Extract meal types dynamically
$meal_types = [];
foreach ($masterMeals as $d => $plans) {
    $meal_types = array_merge($meal_types, array_keys($plans));
}
$meal_types = array_unique($meal_types);


// --- 3. PREPARE STATEMENT FOR INSERTION INTO diet_plans ---
$stmt = $connection->prepare("
    INSERT INTO diet_plans
    (goal,dietary,activity,meal_type,day_number,meal_time,meal_text,protein,carbs,fat,calories)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if(!$stmt){
    die("Prepare failed: ".$connection->error);
}

// --- 4. RUN GENERATION LOOPS ---
$planCount = 0;
foreach($goals as $g){
    foreach($dietary as $d){
        if (!isset($masterMeals[$d])) continue;

        foreach($activities as $a){
            foreach($meal_types as $m){
                if (!isset($masterMeals[$d][$m])) continue;

                for($day=1; $day<=7; $day++){
                    
                    foreach($masterMeals[$d][$m] as $meal_time => $mealsPool){
                        
                        // Select a meal based on the day number to ensure variation
                        $meal = $mealsPool[($day-1) % count($mealsPool)];

                        // Basic check to prevent empty insertion
                        if(empty($meal['meal_text']) || $meal['calories'] <= 0) {
                            continue;
                        }

                        // Start with base values
                        $protein = $meal['protein'];
                        $carbs   = $meal['carbs'];
                        $fat     = $meal['fat'];
                        $calories = $meal['calories'];

                        // Goal-based adjustment
                        switch($g){
                            case 'weight_loss':
                                $calories = round($calories * 0.85); // reduce 15%
                                $carbs = round($carbs * 0.85);
                                break;
                            case 'weight_gain':
                                $calories = round($calories * 1.10); // increase 10%
                                $carbs = round($carbs * 1.10);
                                break;
                            case 'muscle_build':
                                $protein = round($protein * 1.15); // increase 15%
                                break;
                            case 'balanced':
                            default:
                                // keep original
                                break;
                        }

                        $stmt->bind_param(
                            "ssssissiiii",
                            $g, $d, $a, $m, $day, $meal_time,
                            $meal['meal_text'], $protein,
                            $carbs, $fat, $calories
                        );
                        
                        if ($stmt->execute()) {
                            $planCount++;
                        }
                    }
                }
            }
        }
    }
}

$stmt->close();
echo "✅ Diet plan generation complete! Total plans inserted: " . $planCount;
?>













<?php
// populate_master_meals.php
ini_set('display_errors', 1);
error_reporting(E_ALL);

include '../config/db_conn.php';

// Define the hardcoded meal library (copied from your original script)
$sampleMeals = [
    'veg' => [
        '3_meals' => [
          'breakfast' => [
                ['meal_text'=>'Oatmeal with fruits','protein'=>20,'carbs'=>30,'fat'=>10,'calories'=>300],
                ['meal_text'=>'Vegetable poha with peanuts','protein'=>18,'carbs'=>32,'fat'=>9,'calories'=>280],
                ['meal_text'=>'Idli with sambhar','protein'=>15,'carbs'=>35,'fat'=>8,'calories'=>290],
                ['meal_text'=>'Upma with veggies','protein'=>16,'carbs'=>34,'fat'=>7,'calories'=>270],
                ['meal_text'=>'Paratha with curd','protein'=>20,'carbs'=>38,'fat'=>12,'calories'=>350],
                ['meal_text'=>'Moong dal chilla','protein'=>22,'carbs'=>28,'fat'=>9,'calories'=>310],
                ['meal_text'=>'Smoothie bowl with nuts','protein'=>19,'carbs'=>30,'fat'=>10,'calories'=>300],
            ],
            'lunch' => [
                ['meal_text'=>'Paneer salad with quinoa','protein'=>25,'carbs'=>40,'fat'=>15,'calories'=>500],
                ['meal_text'=>'Rajma with brown rice','protein'=>24,'carbs'=>42,'fat'=>14,'calories'=>480],
                ['meal_text'=>'Chole with chapati','protein'=>23,'carbs'=>39,'fat'=>12,'calories'=>460],
                ['meal_text'=>'Mixed veg pulao with curd','protein'=>20,'carbs'=>36,'fat'=>11,'calories'=>450],
                ['meal_text'=>'Dal tadka with rice','protein'=>22,'carbs'=>38,'fat'=>10,'calories'=>440],
                ['meal_text'=>'Vegetable khichdi','protein'=>21,'carbs'=>37,'fat'=>10,'calories'=>430],
                ['meal_text'=>'Stuffed paratha with raita','protein'=>26,'carbs'=>41,'fat'=>14,'calories'=>490],
            ],
            'dinner' => [
                ['meal_text'=>'Grilled tofu with veggies','protein'=>25,'carbs'=>35,'fat'=>15,'calories'=>450],
                ['meal_text'=>'Dal palak with chapati','protein'=>24,'carbs'=>32,'fat'=>12,'calories'=>420],
                ['meal_text'=>'Vegetable soup with bread','protein'=>18,'carbs'=>28,'fat'=>8,'calories'=>350],
                ['meal_text'=>'Paneer bhurji with roti','protein'=>27,'carbs'=>36,'fat'=>16,'calories'=>480],
                ['meal_text'=>'Vegetable biryani with curd','protein'=>22,'carbs'=>39,'fat'=>13,'calories'=>460],
                ['meal_text'=>'Moong dal khichdi','protein'=>21,'carbs'=>34,'fat'=>10,'calories'=>400],
                ['meal_text'=>'Soyabean curry with rice','protein'=>28,'carbs'=>37,'fat'=>14,'calories'=>490],
            ],
        ],
        '5_small' => [
            'breakfast' => [
                ['meal_text'=>'Smoothie bowl','protein'=>15,'carbs'=>25,'fat'=>5,'calories'=>250],
                ['meal_text'=>'Sprout salad','protein'=>16,'carbs'=>20,'fat'=>6,'calories'=>230],
                ['meal_text'=>'Idli with chutney','protein'=>14,'carbs'=>30,'fat'=>5,'calories'=>270],
                ['meal_text'=>'Fruit yogurt parfait','protein'=>15,'carbs'=>28,'fat'=>7,'calories'=>260],
                ['meal_text'=>'Moong dal dosa','protein'=>18,'carbs'=>26,'fat'=>6,'calories'=>280],
                ['meal_text'=>'Oats chilla','protein'=>17,'carbs'=>25,'fat'=>7,'calories'=>270],
                ['meal_text'=>'Vegetable sandwich','protein'=>16,'carbs'=>27,'fat'=>8,'calories'=>290],
            ],
            'mid_morning' => [
                ['meal_text'=>'Fruit salad','protein'=>10,'carbs'=>15,'fat'=>5,'calories'=>150],
                ['meal_text'=>'Dry fruits mix','protein'=>8,'carbs'=>12,'fat'=>7,'calories'=>160],
                ['meal_text'=>'Chana chaat','protein'=>12,'carbs'=>18,'fat'=>4,'calories'=>170],
                ['meal_text'=>'Boiled corn','protein'=>9,'carbs'=>20,'fat'=>3,'calories'=>150],
                ['meal_text'=>'Sprouts bowl','protein'=>11,'carbs'=>16,'fat'=>5,'calories'=>160],
                ['meal_text'=>'Roasted makhana','protein'=>9,'carbs'=>14,'fat'=>4,'calories'=>140],
                ['meal_text'=>'Banana with peanut butter','protein'=>12,'carbs'=>20,'fat'=>8,'calories'=>190],
            ],
            'lunch' => [
                ['meal_text'=>'Quinoa and veggies','protein'=>20,'carbs'=>35,'fat'=>10,'calories'=>450],
                ['meal_text'=>'Vegetable pulao with raita','protein'=>22,'carbs'=>38,'fat'=>11,'calories'=>460],
                ['meal_text'=>'Dal with chapati','protein'=>18,'carbs'=>32,'fat'=>9,'calories'=>420],
                ['meal_text'=>'Mixed veg khichdi','protein'=>19,'carbs'=>34,'fat'=>10,'calories'=>430],
                ['meal_text'=>'Paneer tikka with salad','protein'=>21,'carbs'=>36,'fat'=>12,'calories'=>440],
                ['meal_text'=>'Rajma with brown rice','protein'=>20,'carbs'=>37,'fat'=>10,'calories'=>450],
                ['meal_text'=>'Chole with chapati','protein'=>22,'carbs'=>38,'fat'=>11,'calories'=>460],
            ],
            'snack' => [
                ['meal_text'=>'Nuts and yogurt','protein'=>10,'carbs'=>15,'fat'=>5,'calories'=>150],
                ['meal_text'=>'Vegetable sticks with hummus','protein'=>9,'carbs'=>14,'fat'=>6,'calories'=>140],
                ['meal_text'=>'Boiled chickpeas','protein'=>11,'carbs'=>16,'fat'=>4,'calories'=>160],
                ['meal_text'=>'Sprouts sandwich','protein'=>10,'carbs'=>18,'fat'=>5,'calories'=>150],
                ['meal_text'=>'Fruit smoothie','protein'=>8,'carbs'=>20,'fat'=>4,'calories'=>160],
                ['meal_text'=>'Roasted peanuts','protein'=>12,'carbs'=>10,'fat'=>8,'calories'=>170],
                ['meal_text'=>'Vegetable dhokla','protein'=>9,'carbs'=>15,'fat'=>5,'calories'=>140],
            ],
            'dinner' => [
                ['meal_text'=>'Grilled paneer with salad','protein'=>20,'carbs'=>30,'fat'=>10,'calories'=>400],
                ['meal_text'=>'Vegetable soup with bread','protein'=>18,'carbs'=>28,'fat'=>8,'calories'=>350],
                ['meal_text'=>'Moong dal khichdi','protein'=>21,'carbs'=>34,'fat'=>10,'calories'=>400],
                ['meal_text'=>'Paneer bhurji with roti','protein'=>22,'carbs'=>36,'fat'=>11,'calories'=>420],
                ['meal_text'=>'Dal palak with chapati','protein'=>24,'carbs'=>32,'fat'=>12,'calories'=>420],
                ['meal_text'=>'Vegetable biryani with raita','protein'=>22,'carbs'=>38,'fat'=>13,'calories'=>460],
                ['meal_text'=>'Soyabean curry with rice','protein'=>23,'carbs'=>37,'fat'=>14,'calories'=>470],
            ],
        ],

    ],
    // Non-veg meals
    'nonveg' => [
        '3_meals' => [
            'breakfast' => [
                ['meal_text'=>'Egg omelette with toast','protein'=>20,'carbs'=>30,'fat'=>10,'calories'=>300],
                ['meal_text'=>'Scrambled eggs with veggies','protein'=>22,'carbs'=>28,'fat'=>12,'calories'=>320],
                ['meal_text'=>'Boiled eggs with avocado','protein'=>21,'carbs'=>25,'fat'=>11,'calories'=>310],
                ['meal_text'=>'Chicken sandwich','protein'=>23,'carbs'=>35,'fat'=>12,'calories'=>360],
                ['meal_text'=>'Fish cutlet with bread','protein'=>24,'carbs'=>33,'fat'=>13,'calories'=>370],
                ['meal_text'=>'Egg wrap with spinach','protein'=>22,'carbs'=>29,'fat'=>11,'calories'=>340],
                ['meal_text'=>'Protein shake with eggs','protein'=>25,'carbs'=>20,'fat'=>10,'calories'=>330],
            ],
            'lunch' => [
                ['meal_text'=>'Grilled chicken salad','protein'=>25,'carbs'=>40,'fat'=>15,'calories'=>500],
                ['meal_text'=>'Chicken curry with rice','protein'=>28,'carbs'=>42,'fat'=>16,'calories'=>520],
                ['meal_text'=>'Fish fry with chapati','protein'=>27,'carbs'=>38,'fat'=>14,'calories'=>480],
                ['meal_text'=>'Egg curry with rice','protein'=>26,'carbs'=>40,'fat'=>15,'calories'=>490],
                ['meal_text'=>'Chicken biryani','protein'=>29,'carbs'=>45,'fat'=>17,'calories'=>550],
                ['meal_text'=>'Fish curry with red rice','protein'=>28,'carbs'=>43,'fat'=>15,'calories'=>530],
                ['meal_text'=>'Chicken wrap with veggies','protein'=>27,'carbs'=>39,'fat'=>14,'calories'=>500],
            ],
            'dinner' => [
                ['meal_text'=>'Steamed fish with veggies','protein'=>25,'carbs'=>35,'fat'=>15,'calories'=>450],
                ['meal_text'=>'Grilled chicken with salad','protein'=>28,'carbs'=>30,'fat'=>14,'calories'=>460],
                ['meal_text'=>'Egg bhurji with roti','protein'=>24,'carbs'=>32,'fat'=>13,'calories'=>420],
                ['meal_text'=>'Chicken stew with appam','protein'=>26,'carbs'=>34,'fat'=>14,'calories'=>440],
                ['meal_text'=>'Fish curry with chapati','protein'=>27,'carbs'=>36,'fat'=>15,'calories'=>470],
                ['meal_text'=>'Chicken soup with bread','protein'=>23,'carbs'=>28,'fat'=>12,'calories'=>410],
                ['meal_text'=>'Egg fried rice with veggies','protein'=>25,'carbs'=>40,'fat'=>15,'calories'=>480],
            ],
        ],
        '5_small' => [
            'breakfast' => [
                ['meal_text'=>'Protein smoothie with eggs','protein'=>15,'carbs'=>25,'fat'=>5,'calories'=>250],
                ['meal_text'=>'Boiled eggs with toast','protein'=>16,'carbs'=>20,'fat'=>6,'calories'=>260],
                ['meal_text'=>'Chicken salad wrap','protein'=>17,'carbs'=>22,'fat'=>7,'calories'=>270],
                ['meal_text'=>'Fish sandwich','protein'=>18,'carbs'=>24,'fat'=>8,'calories'=>280],
                ['meal_text'=>'Egg muffin','protein'=>15,'carbs'=>18,'fat'=>6,'calories'=>240],
                ['meal_text'=>'Tuna salad bowl','protein'=>19,'carbs'=>23,'fat'=>7,'calories'=>290],
                ['meal_text'=>'Chicken sausage with veggies','protein'=>20,'carbs'=>21,'fat'=>8,'calories'=>300],
            ],
            'mid_morning' => [
                ['meal_text'=>'Chicken salad','protein'=>10,'carbs'=>15,'fat'=>5,'calories'=>150],
                ['meal_text'=>'Boiled eggs','protein'=>12,'carbs'=>5,'fat'=>4,'calories'=>120],
                ['meal_text'=>'Fish cutlet','protein'=>13,'carbs'=>12,'fat'=>6,'calories'=>160],
                ['meal_text'=>'Egg sandwich','protein'=>11,'carbs'=>14,'fat'=>5,'calories'=>150],
                ['meal_text'=>'Chicken soup','protein'=>10,'carbs'=>8,'fat'=>4,'calories'=>130],
                ['meal_text'=>'Fish fingers','protein'=>14,'carbs'=>13,'fat'=>7,'calories'=>170],
                ['meal_text'=>'Tuna wrap','protein'=>12,'carbs'=>12,'fat'=>5,'calories'=>140],
            ],
            'lunch' => [
                ['meal_text'=>'Grilled chicken with rice','protein'=>20,'carbs'=>35,'fat'=>10,'calories'=>450],
                ['meal_text'=>'Fish curry with rice','protein'=>22,'carbs'=>37,'fat'=>11,'calories'=>470],
                ['meal_text'=>'Egg curry with chapati','protein'=>19,'carbs'=>32,'fat'=>9,'calories'=>420],
                ['meal_text'=>'Chicken pulao','protein'=>21,'carbs'=>36,'fat'=>11,'calories'=>460],
                ['meal_text'=>'Fish biryani','protein'=>23,'carbs'=>38,'fat'=>12,'calories'=>490],
                ['meal_text'=>'Egg fried rice','protein'=>20,'carbs'=>34,'fat'=>10,'calories'=>440],
                ['meal_text'=>'Grilled chicken wrap','protein'=>21,'carbs'=>33,'fat'=>10,'calories'=>450],
            ],
            'snack' => [
                ['meal_text'=>'Yogurt with boiled eggs','protein'=>10,'carbs'=>15,'fat'=>5,'calories'=>150],
                ['meal_text'=>'Chicken tikka pieces','protein'=>12,'carbs'=>5,'fat'=>6,'calories'=>160],
                ['meal_text'=>'Fish salad','protein'=>13,'carbs'=>8,'fat'=>7,'calories'=>170],
                ['meal_text'=>'Egg salad','protein'=>11,'carbs'=>7,'fat'=>5,'calories'=>140],
                ['meal_text'=>'Chicken soup cup','protein'=>10,'carbs'=>6,'fat'=>4,'calories'=>130],
                ['meal_text'=>'Tuna sandwich small','protein'=>12,'carbs'=>10,'fat'=>6,'calories'=>150],
                ['meal_text'=>'Boiled chicken pieces','protein'=>14,'carbs'=>5,'fat'=>5,'calories'=>160],
            ],
            'dinner' => [
                ['meal_text'=>'Fish curry with veggies','protein'=>20,'carbs'=>30,'fat'=>10,'calories'=>400],
                ['meal_text'=>'Grilled chicken with salad','protein'=>22,'carbs'=>28,'fat'=>11,'calories'=>420],
                ['meal_text'=>'Egg curry with rice','protein'=>19,'carbs'=>32,'fat'=>9,'calories'=>410],
                ['meal_text'=>'Chicken stew with chapati','protein'=>21,'carbs'=>34,'fat'=>11,'calories'=>430],
                ['meal_text'=>'Fish fry with roti','protein'=>23,'carbs'=>33,'fat'=>12,'calories'=>440],
                ['meal_text'=>'Egg bhurji with paratha','protein'=>20,'carbs'=>35,'fat'=>10,'calories'=>420],
                ['meal_text'=>'Chicken soup with rice','protein'=>22,'carbs'=>31,'fat'=>11,'calories'=>430],
            ],
        ],
    ],
];


// Prepare statement for insertion into master_meals
$stmt = $connection->prepare("
    INSERT INTO master_meals
    (dietary, meal_type_plan, meal_time, meal_text, protein, carbs, fat, calories)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

if(!$stmt){
    die("Prepare failed: ".$connection->error);
}

$insertCount = 0;

foreach($sampleMeals as $dietaryType => $mealTypePlans){
    foreach($mealTypePlans as $planType => $mealTimes){
        foreach($mealTimes as $mealTime => $mealsPool){
            foreach($mealsPool as $meal){
                $stmt->bind_param(
                    "ssssiiii",
                    $dietaryType, 
                    $planType, 
                    $mealTime,
                    $meal['meal_text'], 
                    $meal['protein'], 
                    $meal['carbs'], 
                    $meal['fat'], 
                    $meal['calories']
                );
                
                // Use execute() and check for success
                if ($stmt->execute()) {
                    $insertCount++;
                } else {
                    // Ignore duplicate key errors if the script is run multiple times
                    if ($connection->errno != 1062) {
                        echo "Error inserting meal: " . $stmt->error . "<br>";
                    }
                }
            }
        }
    }
}

$stmt->close();
echo "✅ Master meal library populated successfully. Total records inserted: " . $insertCount;
// IMPORTANT: STOP EXECUTION AFTER THIS SCRIPT.
// You now have the data in the master_meals table.
?>





CREATE TABLE IF NOT EXISTS master_meals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    dietary ENUM('veg', 'nonveg') NOT NULL,
    meal_type_plan ENUM('3_meals', '5_small') NOT NULL,
    meal_time ENUM('breakfast', 'mid_morning', 'lunch', 'snack', 'dinner') NOT NULL,
    meal_text VARCHAR(255) NOT NULL,
    protein INT NOT NULL,
    carbs INT NOT NULL,
    fat INT NOT NULL,
    calories INT NOT NULL,
    UNIQUE KEY unique_meal (dietary, meal_time, meal_text)
);