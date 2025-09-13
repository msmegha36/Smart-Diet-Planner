<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

include '../config/db_conn.php';

// Truncate swaps table before fresh insert
$connection->query("TRUNCATE TABLE meal_swaps");

// Define swaps for all meals
$mealSwaps = [
    'Oatmeal with fruits' => [
        ['alt' => 'Smoothie bowl with banana and chia seeds', 'protein'=>18,'carbs'=>28,'fat'=>8,'calories'=>280],
        ['alt' => 'Vegetable upma with sprouts', 'protein'=>16,'carbs'=>32,'fat'=>7,'calories'=>290]
    ],
    'Paneer salad with quinoa' => [
        ['alt' => 'Chickpea salad with avocado and spinach', 'protein'=>22,'carbs'=>38,'fat'=>14,'calories'=>460],
        ['alt' => 'Mixed lentil salad with veggies', 'protein'=>21,'carbs'=>36,'fat'=>12,'calories'=>440]
    ],
    'Grilled tofu with veggies' => [
        ['alt' => 'Lentil curry with brown rice', 'protein'=>27,'carbs'=>36,'fat'=>12,'calories'=>480],
        ['alt' => 'Stuffed bell peppers with beans', 'protein'=>23,'carbs'=>34,'fat'=>11,'calories'=>430]
    ],

    // ✅ Non-veg swaps
    'Egg omelette with toast' => [
        ['alt' => 'Boiled eggs with sautéed spinach', 'protein'=>22,'carbs'=>20,'fat'=>12,'calories'=>310],
        ['alt' => 'Scrambled eggs with mushrooms', 'protein'=>21,'carbs'=>18,'fat'=>11,'calories'=>300]
    ],
    'Grilled chicken salad' => [
        ['alt' => 'Boiled egg & spinach wrap', 'protein'=>26,'carbs'=>28,'fat'=>12,'calories'=>420],
        ['alt' => 'Turkey breast with steamed broccoli', 'protein'=>30,'carbs'=>20,'fat'=>10,'calories'=>400]
    ],
    'Steamed fish with veggies' => [
        ['alt' => 'Chicken curry with brown rice', 'protein'=>28,'carbs'=>36,'fat'=>14,'calories'=>480],
        ['alt' => 'Egg curry with roti', 'protein'=>24,'carbs'=>34,'fat'=>13,'calories'=>450]
    ]
];

// Insert swaps into DB including reverse
foreach ($mealSwaps as $originalMeal => $alts) {
    $mealHash = hash('sha256', $originalMeal);

    foreach ($alts as $swap) {
        if(empty($swap['alt'])) continue;

        // ✅ Insert forward mapping
        $stmt = $connection->prepare("
            INSERT INTO meal_swaps (meal_hash, alternative_text, protein, carbs, fat, calories)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "ssiiii",
            $mealHash,
            $swap['alt'],
            $swap['protein'],
            $swap['carbs'],
            $swap['fat'],
            $swap['calories']
        );
        $stmt->execute();
        $stmt->close();

        // ✅ Insert reverse mapping (alt → original)
        $altHash = hash('sha256', $swap['alt']);
        $stmt2 = $connection->prepare("
            INSERT INTO meal_swaps (meal_hash, alternative_text, protein, carbs, fat, calories)
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        $stmt2->bind_param(
            "ssiiii",
            $altHash,
            $originalMeal,
            $swap['protein'], // You can adjust if needed, for now mirror macros
            $swap['carbs'],
            $swap['fat'],
            $swap['calories']
        );
        $stmt2->execute();
        $stmt2->close();
    }
}

echo "✅ Meal swaps (forward + reverse) inserted successfully!";
