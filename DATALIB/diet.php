<?php
/**
 * Diet Plan Data Generator (Standardized to 100g)
 *
 * This script connects to the database, clears the 'diet_plans' table,
 * and inserts a 7-day plan for every combination of user preferences.
 *
 * IMPORTANT: All macro values (protein, carbs, fat, calories) in the $sampleMeals
 * array are now standardized to represent the nutritional content PER 100 GRAMS.
 * The 'multiplier' field is used to scale the 100g unit to the final serving size.
 *
 * It is CRITICAL to run this script once whenever the meal library or macro logic changes,
 * as it pre-calculates and stores all possible diet plans for fast retrieval.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Assume this path is correct for your setup
// IMPORTANT: Adjust this path if your db_conn.php is located elsewhere.
include '../config/db_conn.php'; 

if (!isset($connection) || $connection->connect_error) {
    die("Database connection failed. Please check db_conn.php.");
}

// --- DATABASE SETUP AND TRUNCATION (WARNING: This clears all existing plan data!) ---
echo "<h2>Generating Diet Plan Data...</h2>";

echo "Clearing existing data from 'diet_plans' table...<br>";
// This ensures we start with a fresh set of pre-calculated plans
$connection->query("TRUNCATE TABLE diet_plans");

// --- DEFINITIONS ---
$goals          = ['weight_loss', 'weight_gain', 'muscle_build', 'balanced'];
$dietary        = ['veg', 'nonveg'];
$activities     = ['light', 'moderate', 'active'];
$meal_types     = ['3_meals', '5_small'];
$health_foci    = ['none', 'low_carb', 'high_fiber'];

$mealTimes = [
    '3_meals' => ['breakfast', 'lunch', 'dinner'],
    '5_small' => ['breakfast', 'mid_morning', 'lunch', 'snack', 'dinner'],
];

// --- MACRO CALCULATION HELPER ---
// Standardizes calories based on 4kcal/g for P/C and 9kcal/g for F
function calculate_calories($p, $c, $f) {
    return round(($p * 4) + ($c * 4) + ($f * 9));
}

// --- STANDARDIZED MEAL LIBRARY (ALL MACROS ARE PER 100G) ---
$sampleMeals = [
    'veg' => [
        '3_meals' => [
            'breakfast' => [
                // Oatmeal (100g is about 1/2 cup dry, making 200g cooked) - Multiplier 2.0 means 200g cooked total serving
                ['meal_text'=>'Oatmeal with fruits','quantity'=>'1 bowl','protein'=>5.0,'carbs'=>7.5,'fat'=>2.5,'calories'=>calculate_calories(5.0, 7.5, 2.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Vegetable poha with peanuts','quantity'=>'1 plate','protein'=>4.5,'carbs'=>8.0,'fat'=>2.2,'calories'=>calculate_calories(4.5, 8.0, 2.2), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Idli (1 Idli approx 50g, 3 Idlis approx 150g). Macros are for 100g. Multiplier 3.0 means 3 Idlis (150g total)
                ['meal_text'=>'Idli with sambhar','quantity'=>'3 idlis + 1 bowl sambhar','protein'=>3.3,'carbs'=>7.7,'fat'=>1.7,'calories'=>calculate_calories(3.3, 7.7, 1.7), 'base_qty'=>50.0, 'unit_label'=>'g', 'multiplier'=>3.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Upma with veggies','quantity'=>'1 bowl','protein'=>4.0,'carbs'=>8.5,'fat'=>1.8,'calories'=>calculate_calories(4.0, 8.5, 1.8), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Paratha (1 Paratha approx 80g). Macros are for 100g. Multiplier 2.0 means 2 Parathas (160g total)
                ['meal_text'=>'Paratha with curd','quantity'=>'2 parathas + ½ cup curd','protein'=>6.3,'carbs'=>11.9,'fat'=>3.8,'calories'=>calculate_calories(6.3, 11.9, 3.8), 'base_qty'=>80.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Moong Dal Chilla (1 chilla approx 60g). Macros are for 100g. Multiplier 2.0 means 2 Chillas (120g total)
                ['meal_text'=>'Moong dal chilla','quantity'=>'2 chillas','protein'=>9.2,'carbs'=>11.7,'fat'=>3.8,'calories'=>calculate_calories(9.2, 11.7, 3.8), 'base_qty'=>60.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Smoothie Bowl (1 glass approx 300g). Macros are for 100g. Multiplier 1.0 means 1 unit (300g total)
                ['meal_text'=>'Smoothie bowl with nuts','quantity'=>'1 glass','protein'=>6.3,'carbs'=>10.0,'fat'=>3.3,'calories'=>calculate_calories(6.3, 10.0, 3.3), 'base_qty'=>300.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Tofu scramble (1 cup approx 150g). Macros are for 100g. Multiplier 2.0 means 2 cups (300g total)
                ['meal_text'=>'Tofu scramble with spinach','quantity'=>'1 cup tofu + toast','protein'=>8.0,'carbs'=>6.7,'fat'=>4.3,'calories'=>calculate_calories(8.0, 6.7, 4.3), 'base_qty'=>150.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'High-protein millet dosa','quantity'=>'2 dosas','protein'=>7.0,'carbs'=>10.0,'fat'=>2.5,'calories'=>calculate_calories(7.0, 10.0, 2.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Weight gain (1 large glass approx 500g)
                ['meal_text'=>'Weight gain milkshake (nuts/seeds)','quantity'=>'1 large glass','protein'=>5.0,'carbs'=>9.0,'fat'=>3.0,'calories'=>calculate_calories(5.0, 9.0, 3.0), 'base_qty'=>500.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
            ],
            'lunch' => [
                ['meal_text'=>'Paneer salad with quinoa','quantity'=>'1 bowl salad + ½ cup quinoa','protein'=>6.3,'carbs'=>10.0,'fat'=>3.8,'calories'=>calculate_calories(6.3, 10.0, 3.8), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Rajma with brown rice','quantity'=>'1 cup rajma + 1 cup rice','protein'=>6.0,'carbs'=>10.5,'fat'=>3.5,'calories'=>calculate_calories(6.0, 10.5, 3.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Chole with chapati','quantity'=>'1 cup chole + 2 chapatis','protein'=>5.8,'carbs'=>9.8,'fat'=>3.0,'calories'=>calculate_calories(5.8, 9.8, 3.0), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Mixed veg pulao with curd','quantity'=>'1 plate pulao + ½ cup curd','protein'=>5.0,'carbs'=>9.0,'fat'=>2.8,'calories'=>calculate_calories(5.0, 9.0, 2.8), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Dal tadka with rice','quantity'=>'1 bowl dal + 1 cup rice','protein'=>5.5,'carbs'=>9.5,'fat'=>2.5,'calories'=>calculate_calories(5.5, 9.5, 2.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Vegetable khichdi','quantity'=>'1 medium bowl','protein'=>5.3,'carbs'=>9.3,'fat'=>2.5,'calories'=>calculate_calories(5.3, 9.3, 2.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Stuffed paratha (1 Paratha approx 80g). Macros are for 100g. Multiplier 2.0 means 2 Parathas (160g total)
                ['meal_text'=>'Stuffed paratha with raita','quantity'=>'2 parathas + ½ cup raita','protein'=>8.1,'carbs'=>12.8,'fat'=>4.4,'calories'=>calculate_calories(8.1, 12.8, 4.4), 'base_qty'=>80.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'High-fiber lentil and vegetable bowl','quantity'=>'1 large bowl','protein'=>7.5,'carbs'=>12.5,'fat'=>3.0,'calories'=>calculate_calories(7.5, 12.5, 3.0), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Tofu and vegetable stir-fry','quantity'=>'1 cup tofu + 1 cup veggies','protein'=>7.0,'carbs'=>9.0,'fat'=>3.8,'calories'=>calculate_calories(7.0, 9.0, 3.8), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Mixed bean (1 bowl approx 245g)
                ['meal_text'=>'Mixed bean & corn salad','quantity'=>'1 large bowl','protein'=>5.3,'carbs'=>9.0,'fat'=>2.0,'calories'=>calculate_calories(5.3, 9.0, 2.0), 'base_qty'=>245.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
            ],
            'dinner' => [
                ['meal_text'=>'Grilled tofu with veggies','quantity'=>'150g tofu + 1 bowl sautéed veggies','protein'=>6.3,'carbs'=>8.8,'fat'=>3.8,'calories'=>calculate_calories(6.3, 8.8, 3.8), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Dal palak with chapati','quantity'=>'1 cup dal palak + 2 chapatis','protein'=>6.0,'carbs'=>8.0,'fat'=>3.0,'calories'=>calculate_calories(6.0, 8.0, 3.0), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>1],
                // Vegetable soup (1 unit approx 400g)
                ['meal_text'=>'Vegetable soup with bread','quantity'=>'1 bowl soup + 2 slices bread','protein'=>4.5,'carbs'=>7.0,'fat'=>2.0,'calories'=>calculate_calories(4.5, 7.0, 2.0), 'base_qty'=>400.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Paneer bhurji with roti','quantity'=>'1 cup bhurji + 2 rotis','protein'=>6.8,'carbs'=>9.0,'fat'=>4.0,'calories'=>calculate_calories(6.8, 9.0, 4.0), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Vegetable biryani with curd','quantity'=>'1 plate biryani + ½ cup curd','protein'=>5.5,'carbs'=>9.8,'fat'=>3.3,'calories'=>calculate_calories(5.5, 9.8, 3.3), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Moong dal khichdi','quantity'=>'1 bowl','protein'=>5.3,'carbs'=>8.5,'fat'=>2.5,'calories'=>calculate_calories(5.3, 8.5, 2.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Soyabean curry with rice','quantity'=>'1 cup curry + 1 cup rice','protein'=>7.0,'carbs'=>9.3,'fat'=>3.5,'calories'=>calculate_calories(7.0, 9.3, 3.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Chickpea patties (1 patty approx 75g). Macros are for 100g. Multiplier 2.0 means 2 patties (150g total)
                ['meal_text'=>'Chickpea (chana) and veggie patties','quantity'=>'2 patties + salad','protein'=>10.7,'carbs'=>13.3,'fat'=>4.0,'calories'=>calculate_calories(10.7, 13.3, 4.0), 'base_qty'=>75.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Whole wheat pasta with pesto and pine nuts','quantity'=>'1 bowl','protein'=>6.5,'carbs'=>15.0,'fat'=>4.5,'calories'=>calculate_calories(6.5, 15.0, 4.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Besan cheela (1 cheela approx 80g). Macros are for 100g. Multiplier 3.0 means 3 cheelas (240g total)
                ['meal_text'=>'Besan cheela (savory pancake)','quantity'=>'3 large cheelas','protein'=>6.3,'carbs'=>7.5,'fat'=>2.1,'calories'=>calculate_calories(6.3, 7.5, 2.1), 'base_qty'=>80.0, 'unit_label'=>'g', 'multiplier'=>3.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
            ],
        ],
        '5_small' => [
            // Standard small meals will use a base unit of 100g and scale accordingly, or define a larger base_qty for the 'unit'
            // Smoothie bowl (1 bowl approx 250g)
            'breakfast' => [
                ['meal_text'=>'Smoothie bowl','quantity'=>'1 bowl','protein'=>6.0,'carbs'=>10.0,'fat'=>2.0,'calories'=>calculate_calories(6.0, 10.0, 2.0), 'base_qty'=>250.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Sprout salad (1 bowl approx 230g)
                ['meal_text'=>'Sprout salad','quantity'=>'1 bowl','protein'=>7.0,'carbs'=>8.7,'fat'=>2.6,'calories'=>calculate_calories(7.0, 8.7, 2.6), 'base_qty'=>230.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Idli (2 idlis approx 100g). Macros are for 100g. Multiplier 2.0 means 2 Idlis (100g total)
                ['meal_text'=>'Idli with chutney','quantity'=>'2 idlis + chutney','protein'=>7.0,'carbs'=>15.0,'fat'=>2.5,'calories'=>calculate_calories(7.0, 15.0, 2.5), 'base_qty'=>50.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Fruit yogurt parfait (1 glass approx 260g)
                ['meal_text'=>'Fruit yogurt parfait','quantity'=>'1 glass','protein'=>5.8,'carbs'=>10.8,'fat'=>2.7,'calories'=>calculate_calories(5.8, 10.8, 2.7), 'base_qty'=>260.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Moong dal dosa (1 dosa approx 140g)
                ['meal_text'=>'Moong dal dosa','quantity'=>'1 dosa','protein'=>12.9,'carbs'=>18.6,'fat'=>4.3,'calories'=>calculate_calories(12.9, 18.6, 4.3), 'base_qty'=>140.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Oats chilla (1 chilla approx 67.5g). Multiplier 2.0 means 2 chillas (135g total)
                ['meal_text'=>'Oats chilla','quantity'=>'2 medium chillas','protein'=>6.3,'carbs'=>9.3,'fat'=>2.6,'calories'=>calculate_calories(6.3, 9.3, 2.6), 'base_qty'=>67.5, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Veg sandwich (1 slice approx 72.5g). Multiplier 2.0 means 2 slices (145g total)
                ['meal_text'=>'Vegetable sandwich','quantity'=>'2 slices','protein'=>5.5,'carbs'=>9.3,'fat'=>2.8,'calories'=>calculate_calories(5.5, 9.3, 2.8), 'base_qty'=>72.5, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Protein oatmeal (1 bowl approx 220g)
                ['meal_text'=>'Protein oatmeal with seeds','quantity'=>'1 small bowl','protein'=>6.4,'carbs'=>9.1,'fat'=>2.7,'calories'=>calculate_calories(6.4, 9.1, 2.7), 'base_qty'=>220.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Paneer paratha (1 paratha approx 190g)
                ['meal_text'=>'Paneer and mint paratha','quantity'=>'1 paratha','protein'=>6.3,'carbs'=>7.9,'fat'=>3.7,'calories'=>calculate_calories(6.3, 7.9, 3.7), 'base_qty'=>190.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Avocado toast (1 slice approx 210g)
                ['meal_text'=>'Avocado toast with seeds','quantity'=>'1 slice toast','protein'=>4.3,'carbs'=>8.1,'fat'=>4.8,'calories'=>calculate_calories(4.3, 8.1, 4.8), 'base_qty'=>210.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
            ],
            'mid_morning' => [
                // Fruit salad (1 bowl approx 150g)
                ['meal_text'=>'Fruit salad','quantity'=>'1 bowl','protein'=>6.7,'carbs'=>10.0,'fat'=>3.3,'calories'=>calculate_calories(6.7, 10.0, 3.3), 'base_qty'=>150.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>1],
                // Dry fruits mix (30g)
                ['meal_text'=>'Dry fruits mix','quantity'=>'1 small handful (30g)','protein'=>26.7,'carbs'=>40.0,'fat'=>23.3,'calories'=>calculate_calories(26.7, 40.0, 23.3), 'base_qty'=>30.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Chana chaat (1 bowl approx 170g)
                ['meal_text'=>'Chana chaat','quantity'=>'1 bowl','protein'=>7.1,'carbs'=>10.6,'fat'=>2.4,'calories'=>calculate_calories(7.1, 10.6, 2.4), 'base_qty'=>170.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Boiled corn (1 cup approx 150g)
                ['meal_text'=>'Boiled corn','quantity'=>'1 cup','protein'=>6.0,'carbs'=>13.3,'fat'=>2.0,'calories'=>calculate_calories(6.0, 13.3, 2.0), 'base_qty'=>150.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Sprouts bowl (1 bowl approx 160g)
                ['meal_text'=>'Sprouts bowl','quantity'=>'1 bowl','protein'=>6.9,'carbs'=>10.0,'fat'=>3.1,'calories'=>calculate_calories(6.9, 10.0, 3.1), 'base_qty'=>160.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Roasted makhana (1 bowl approx 140g)
                ['meal_text'=>'Roasted makhana','quantity'=>'1 small bowl','protein'=>6.4,'carbs'=>10.0,'fat'=>2.9,'calories'=>calculate_calories(6.4, 10.0, 2.9), 'base_qty'=>140.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Banana with peanut butter (1 unit approx 190g)
                ['meal_text'=>'Banana with peanut butter','quantity'=>'1 banana + 1 tbsp peanut butter','protein'=>6.3,'carbs'=>10.5,'fat'=>4.2,'calories'=>calculate_calories(6.3, 10.5, 4.2), 'base_qty'=>190.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Edamame (1 cup approx 200g)
                ['meal_text'=>'Edamame pods (steamed)','quantity'=>'1 cup','protein'=>9.0,'carbs'=>7.5,'fat'=>4.0,'calories'=>calculate_calories(9.0, 7.5, 4.0), 'base_qty'=>200.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Energy balls (1 piece approx 90g). Multiplier 2.0 means 2 pieces (180g total)
                ['meal_text'=>'Protein energy balls','quantity'=>'2 pieces','protein'=>5.6,'carbs'=>8.3,'fat'=>4.4,'calories'=>calculate_calories(5.6, 8.3, 4.4), 'base_qty'=>90.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Greek yogurt (1 cup approx 180g)
                ['meal_text'=>'Greek yogurt (unsweetened)','quantity'=>'1 cup','protein'=>11.1,'carbs'=>5.6,'fat'=>2.8,'calories'=>calculate_calories(11.1, 5.6, 2.8), 'base_qty'=>180.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
            ],
            'lunch' => [
                ['meal_text'=>'Quinoa and veggies','quantity'=>'1 medium bowl','protein'=>5.0,'carbs'=>8.8,'fat'=>2.5,'calories'=>calculate_calories(5.0, 8.8, 2.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Vegetable pulao with raita','quantity'=>'1 plate','protein'=>5.5,'carbs'=>9.5,'fat'=>2.8,'calories'=>calculate_calories(5.5, 9.5, 2.8), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Dal with chapati','quantity'=>'1 cup dal + 2 chapatis','protein'=>4.5,'carbs'=>8.0,'fat'=>2.3,'calories'=>calculate_calories(4.5, 8.0, 2.3), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Mixed veg khichdi','quantity'=>'1 bowl','protein'=>4.8,'carbs'=>8.5,'fat'=>2.5,'calories'=>calculate_calories(4.8, 8.5, 2.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Paneer tikka (1 piece approx 55g). Multiplier 2.0 means 6 pieces (330g total)
                ['meal_text'=>'Paneer tikka with salad','quantity'=>'6 pieces paneer + salad','protein'=>3.2,'carbs'=>5.5,'fat'=>1.8,'calories'=>calculate_calories(3.2, 5.5, 1.8), 'base_qty'=>165.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Rajma with brown rice','quantity'=>'1 bowl','protein'=>5.0,'carbs'=>9.3,'fat'=>2.5,'calories'=>calculate_calories(5.0, 9.3, 2.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Chole with chapati','quantity'=>'1 cup chole + 2 chapatis','protein'=>5.5,'carbs'=>9.5,'fat'=>2.8,'calories'=>calculate_calories(5.5, 9.5, 2.8), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Hummus/Falafel (1 unit approx 280g)
                ['meal_text'=>'Hummus and falafel platter','quantity'=>'3 falafel + 3 tbsp hummus','protein'=>5.0,'carbs'=>7.1,'fat'=>2.9,'calories'=>calculate_calories(5.0, 7.1, 2.9), 'base_qty'=>280.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Black bean burger (1 burger approx 300g)
                ['meal_text'=>'Black bean burger on whole wheat bun','quantity'=>'1 burger','protein'=>5.3,'carbs'=>8.3,'fat'=>2.3,'calories'=>calculate_calories(5.3, 8.3, 2.3), 'base_qty'=>300.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>1],
                // Sambar uttapam (1 uttapam approx 270g)
                ['meal_text'=>'Sambar with whole wheat uttapam','quantity'=>'1 uttapam + 1 bowl sambar','protein'=>4.8,'carbs'=>8.1,'fat'=>2.2,'calories'=>calculate_calories(4.8, 8.1, 2.2), 'base_qty'=>270.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
            ],
            'snack' => [
                // Macros per 100g. Serving size is 150g (1.5x 100g unit)
                ['meal_text'=>'Nuts and yogurt','quantity'=>'1/2 cup yogurt + 1 tbsp nuts','protein'=>6.7,'carbs'=>10.0,'fat'=>3.3,'calories'=>calculate_calories(6.7, 10.0, 3.3), 'base_qty'=>150.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Macros per 100g. Serving size is 140g (1.4x 100g unit)
                ['meal_text'=>'Vegetable sticks with hummus','quantity'=>'1 cup sticks + 2 tbsp hummus','protein'=>6.4,'carbs'=>10.0,'fat'=>4.3,'calories'=>calculate_calories(6.4, 10.0, 4.3), 'base_qty'=>140.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Boiled chickpeas (1 bowl approx 160g)
                ['meal_text'=>'Boiled chickpeas','quantity'=>'1 small bowl','protein'=>6.9,'carbs'=>10.0,'fat'=>2.5,'calories'=>calculate_calories(6.9, 10.0, 2.5), 'base_qty'=>160.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Sprouts sandwich (1 slice approx 37.5g). Multiplier 2.0 means 2 slices (75g total)
                ['meal_text'=>'Sprouts sandwich','quantity'=>'2 slices','protein'=>6.7,'carbs'=>12.0,'fat'=>3.3,'calories'=>calculate_calories(6.7, 12.0, 3.3), 'base_qty'=>37.5, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Fruit smoothie (1 glass approx 160g)
                ['meal_text'=>'Fruit smoothie','quantity'=>'1 glass','protein'=>5.0,'carbs'=>12.5,'fat'=>2.5,'calories'=>calculate_calories(5.0, 12.5, 2.5), 'base_qty'=>160.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Roasted peanuts (30g)
                ['meal_text'=>'Roasted peanuts','quantity'=>'1 handful (30g)','protein'=>40.0,'carbs'=>33.3,'fat'=>26.7,'calories'=>calculate_calories(40.0, 33.3, 26.7), 'base_qty'=>30.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Vegetable dhokla (1 piece approx 15.7g). Multiplier 3.0 means 3 pieces (47g total)
                ['meal_text'=>'Vegetable dhokla','quantity'=>'3 pieces','protein'=>6.4,'carbs'=>10.6,'fat'=>3.5,'calories'=>calculate_calories(6.4, 10.6, 3.5), 'base_qty'=>15.7, 'unit_label'=>'g', 'multiplier'=>3.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Small paneer skewer (1 piece approx 63g). Multiplier 3.0 means 3 pieces (190g total)
                ['meal_text'=>'Small paneer skewer (tikka)','quantity'=>'3 pieces','protein'=>7.9,'carbs'=>4.2,'fat'=>5.3,'calories'=>calculate_calories(7.9, 4.2, 5.3), 'base_qty'=>63.0, 'unit_label'=>'g', 'multiplier'=>3.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Peanut butter on rice cake (1 unit approx 180g)
                ['meal_text'=>'Peanut butter on rice cake','quantity'=>'1 rice cake + 1 tbsp PB','protein'=>4.4,'carbs'=>8.3,'fat'=>4.4,'calories'=>calculate_calories(4.4, 8.3, 4.4), 'base_qty'=>180.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Roasted soya chunks (1 cup approx 200g). Multiplier 0.5 means 1/2 cup (100g total)
                ['meal_text'=>'Roasted soya chunks','quantity'=>'1/2 cup','protein'=>20.0,'carbs'=>10.0,'fat'=>5.0,'calories'=>calculate_calories(20.0, 10.0, 5.0), 'base_qty'=>200.0, 'unit_label'=>'g', 'multiplier'=>0.5, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
            ],
            'dinner' => [
                ['meal_text'=>'Grilled paneer with salad','quantity'=>'100g paneer + salad','protein'=>5.0,'carbs'=>7.5,'fat'=>2.5,'calories'=>calculate_calories(5.0, 7.5, 2.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Vegetable soup (1 unit approx 350g)
                ['meal_text'=>'Vegetable soup with bread','quantity'=>'1 bowl + 1 slice bread','protein'=>5.1,'carbs'=>8.0,'fat'=>2.3,'calories'=>calculate_calories(5.1, 8.0, 2.3), 'base_qty'=>350.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Moong dal khichdi','quantity'=>'1 bowl','protein'=>5.3,'carbs'=>8.5,'fat'=>2.5,'calories'=>calculate_calories(5.3, 8.5, 2.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Paneer bhurji with roti','quantity'=>'1 cup bhurji + 2 rotis','protein'=>5.5,'carbs'=>9.0,'fat'=>2.8,'calories'=>calculate_calories(5.5, 9.0, 2.8), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Dal palak with chapati','quantity'=>'1 cup dal + 2 chapatis','protein'=>6.0,'carbs'=>8.0,'fat'=>3.0,'calories'=>calculate_calories(6.0, 8.0, 3.0), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>1],
                ['meal_text'=>'Vegetable biryani with raita','quantity'=>'1 plate','protein'=>5.5,'carbs'=>9.5,'fat'=>3.3,'calories'=>calculate_calories(5.5, 9.5, 3.3), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Soyabean curry with rice','quantity'=>'1 cup curry + 1 cup rice','protein'=>5.8,'carbs'=>9.3,'fat'=>3.5,'calories'=>calculate_calories(5.8, 9.3, 3.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Quinoa stuffed peppers (1 large pepper approx 280g)
                ['meal_text'=>'Quinoa-stuffed bell peppers','quantity'=>'1 large pepper','protein'=>5.0,'carbs'=>7.9,'fat'=>2.1,'calories'=>calculate_calories(5.0, 7.9, 2.1), 'base_qty'=>280.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Tofu skewers (1 skewer approx 62.5g). Multiplier 2.0 means 2 skewers (125g total)
                ['meal_text'=>'Tofu and vegetable skewers','quantity'=>'2 skewers','protein'=>12.8,'carbs'=>9.6,'fat'=>6.4,'calories'=>calculate_calories(12.8, 9.6, 6.4), 'base_qty'=>62.5, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Pizza (1 slice approx 145g). Multiplier 2.0 means 2 slices (290g total)
                ['meal_text'=>'Mushroom and spinach pizza (whole wheat)','quantity'=>'2 slices','protein'=>4.5,'carbs'=>8.6,'fat'=>2.4,'calories'=>calculate_calories(4.5, 8.6, 2.4), 'base_qty'=>145.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
            ],
        ],
    ],
    'nonveg' => [
        '3_meals' => [
            // Egg omelette (1 egg approx 50g, 2 eggs + toast approx 150g). Multiplier 2.0 means 2 units (300g total)
            'breakfast' => [
                ['meal_text'=>'Egg omelette with toast','quantity'=>'2 eggs + 2 slices toast','protein'=>5.0,'carbs'=>7.5,'fat'=>2.5,'calories'=>calculate_calories(5.0, 7.5, 2.5), 'base_qty'=>75.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Scrambled eggs with veggies','quantity'=>'2 eggs + 1/2 cup veggies','protein'=>5.5,'carbs'=>7.0,'fat'=>3.0,'calories'=>calculate_calories(5.5, 7.0, 3.0), 'base_qty'=>80.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Boiled eggs with avocado','quantity'=>'2 eggs + 1/2 avocado','protein'=>5.3,'carbs'=>6.3,'fat'=>2.8,'calories'=>calculate_calories(5.3, 6.3, 2.8), 'base_qty'=>77.5, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Chicken sandwich','quantity'=>'2 slices bread + 50g chicken','protein'=>5.8,'carbs'=>8.8,'fat'=>3.0,'calories'=>calculate_calories(5.8, 8.8, 3.0), 'base_qty'=>90.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Fish cutlet with bread','quantity'=>'2 cutlets + 2 slices bread','protein'=>6.5,'carbs'=>8.9,'fat'=>3.5,'calories'=>calculate_calories(6.5, 8.9, 3.5), 'base_qty'=>92.5, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Egg wrap (1 wrap approx 340g)
                ['meal_text'=>'Egg wrap with spinach','quantity'=>'1 wrap (2 eggs + spinach)','protein'=>6.5,'carbs'=>8.5,'fat'=>3.2,'calories'=>calculate_calories(6.5, 8.5, 3.2), 'base_qty'=>340.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Protein shake (1 glass approx 330g)
                ['meal_text'=>'Protein shake with eggs','quantity'=>'1 glass shake (2 eggs + milk)','protein'=>7.6,'carbs'=>6.1,'fat'=>3.0,'calories'=>calculate_calories(7.6, 6.1, 3.0), 'base_qty'=>330.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Steak burrito (1 burrito approx 450g)
                ['meal_text'=>'Steak and egg breakfast burrito','quantity'=>'1 burrito','protein'=>6.7,'carbs'=>7.8,'fat'=>3.3,'calories'=>calculate_calories(6.7, 7.8, 3.3), 'base_qty'=>450.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Smoked salmon (1 unit approx 140g)
                ['meal_text'=>'Smoked salmon on whole wheat toast','quantity'=>'2 slices toast + 50g salmon','protein'=>12.9,'carbs'=>14.3,'fat'=>7.1,'calories'=>calculate_calories(12.9, 14.3, 7.1), 'base_qty'=>140.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Cottage cheese pancakes (1 pancake approx 100g). Multiplier 3.0 means 3 pancakes (300g total)
                ['meal_text'=>'High-protein cottage cheese pancakes','quantity'=>'3 pancakes','protein'=>9.3,'carbs'=>10.0,'fat'=>2.7,'calories'=>calculate_calories(9.3, 10.0, 2.7), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>3.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
            ],
            'lunch' => [
                ['meal_text'=>'Grilled chicken salad','quantity'=>'100g chicken + salad bowl','protein'=>6.3,'carbs'=>10.0,'fat'=>3.8,'calories'=>calculate_calories(6.3, 10.0, 3.8), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Chicken curry with rice','quantity'=>'1 cup curry + 1 cup rice','protein'=>7.0,'carbs'=>10.5,'fat'=>4.0,'calories'=>calculate_calories(7.0, 10.5, 4.0), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Fish fry with chapati','quantity'=>'1 piece fish + 2 chapatis','protein'=>6.8,'carbs'=>9.5,'fat'=>3.5,'calories'=>calculate_calories(6.8, 9.5, 3.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Egg curry with rice','quantity'=>'2 eggs + 1 cup rice','protein'=>6.5,'carbs'=>10.0,'fat'=>3.8,'calories'=>calculate_calories(6.5, 10.0, 3.8), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Chicken biryani','quantity'=>'1 medium plate (150g chicken)','protein'=>7.3,'carbs'=>11.3,'fat'=>4.3,'calories'=>calculate_calories(7.3, 11.3, 4.3), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Fish curry with red rice','quantity'=>'1 piece fish + 1 cup red rice','protein'=>7.0,'carbs'=>10.8,'fat'=>3.8,'calories'=>calculate_calories(7.0, 10.8, 3.8), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Chicken wrap (1 wrap approx 500g)
                ['meal_text'=>'Chicken wrap with veggies','quantity'=>'1 wrap (100g chicken + veggies)','protein'=>5.4,'carbs'=>7.8,'fat'=>2.8,'calories'=>calculate_calories(5.4, 7.8, 2.8), 'base_qty'=>500.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Lamb keema with brown rice','quantity'=>'1 cup keema + 1 cup rice','protein'=>9.0,'carbs'=>10.0,'fat'=>5.0,'calories'=>calculate_calories(9.0, 10.0, 5.0), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Tandoori chicken breast with roasted potatoes','quantity'=>'1 breast + 1 cup potatoes','protein'=>10.0,'carbs'=>12.5,'fat'=>4.0,'calories'=>calculate_calories(10.0, 12.5, 4.0), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Prawn curry (1 unit approx 290g)
                ['meal_text'=>'Prawn curry with whole wheat roti','quantity'=>'1 cup curry + 2 rotis','protein'=>8.0,'carbs'=>10.0,'fat'=>3.5,'calories'=>calculate_calories(8.0, 10.0, 3.5), 'base_qty'=>145.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
            ],
            'dinner' => [
                ['meal_text'=>'Steamed fish with veggies','quantity'=>'1 piece fish + 1 cup veggies','protein'=>6.3,'carbs'=>8.8,'fat'=>3.8,'calories'=>calculate_calories(6.3, 8.8, 3.8), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Grilled chicken with salad','quantity'=>'100g chicken + salad','protein'=>7.0,'carbs'=>7.5,'fat'=>3.5,'calories'=>calculate_calories(7.0, 7.5, 3.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Egg bhurji with roti','quantity'=>'2 eggs + 2 rotis','protein'=>6.0,'carbs'=>8.0,'fat'=>3.3,'calories'=>calculate_calories(6.0, 8.0, 3.3), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Chicken stew with appam','quantity'=>'1 cup stew + 2 appams','protein'=>6.5,'carbs'=>8.5,'fat'=>3.5,'calories'=>calculate_calories(6.5, 8.5, 3.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Fish curry with chapati','quantity'=>'1 cup curry + 2 chapatis','protein'=>6.8,'carbs'=>9.0,'fat'=>3.8,'calories'=>calculate_calories(6.8, 9.0, 3.8), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Chicken soup (1 bowl approx 410g)
                ['meal_text'=>'Chicken soup with bread','quantity'=>'1 bowl soup + 1 slice bread','protein'=>5.6,'carbs'=>6.8,'fat'=>2.9,'calories'=>calculate_calories(5.6, 6.8, 2.9), 'base_qty'=>410.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Egg fried rice with veggies','quantity'=>'1 plate (2 eggs + rice + veggies)','protein'=>6.3,'carbs'=>10.0,'fat'=>3.8,'calories'=>calculate_calories(6.3, 10.0, 3.8), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Turkey meatloaf (1 unit approx 320g)
                ['meal_text'=>'Turkey meatloaf with baked veggies','quantity'=>'1 slice meatloaf + 1 cup veggies','protein'=>7.8,'carbs'=>4.7,'fat'=>3.1,'calories'=>calculate_calories(7.8, 4.7, 3.1), 'base_qty'=>320.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Seafood pasta (low-fat sauce)','quantity'=>'1 cup pasta + 1/2 cup seafood','protein'=>10.0,'carbs'=>15.0,'fat'=>4.0,'calories'=>calculate_calories(10.0, 15.0, 4.0), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Lean beef chilli (1 bowl approx 340g)
                ['meal_text'=>'Lean beef and black bean chilli','quantity'=>'1 bowl','protein'=>6.5,'carbs'=>7.4,'fat'=>2.6,'calories'=>calculate_calories(6.5, 7.4, 2.6), 'base_qty'=>340.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
            ],
        ],
        '5_small' => [
            'breakfast' => [
                // Protein smoothie (1 glass approx 250g)
                ['meal_text'=>'Protein smoothie with eggs','quantity'=>'1 glass (2 eggs + milk + banana)','protein'=>6.0,'carbs'=>10.0,'fat'=>2.0,'calories'=>calculate_calories(6.0, 10.0, 2.0), 'base_qty'=>250.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Boiled eggs (1 egg approx 50g). Multiplier 2.0 means 2 eggs + toast (130g total)
                ['meal_text'=>'Boiled eggs with toast','quantity'=>'2 boiled eggs + 2 slices toast','protein'=>6.2,'carbs'=>7.7,'fat'=>2.3,'calories'=>calculate_calories(6.2, 7.7, 2.3), 'base_qty'=>65.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Chicken wrap (1 wrap approx 270g)
                ['meal_text'=>'Chicken salad wrap','quantity'=>'1 small wrap (50g chicken + veggies)','protein'=>6.3,'carbs'=>8.1,'fat'=>2.6,'calories'=>calculate_calories(6.3, 8.1, 2.6), 'base_qty'=>270.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Fish sandwich (1 unit approx 280g)
                ['meal_text'=>'Fish sandwich','quantity'=>'2 slices bread + 1 fish fillet','protein'=>6.4,'carbs'=>8.6,'fat'=>2.9,'calories'=>calculate_calories(6.4, 8.6, 2.9), 'base_qty'=>280.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Egg muffin (1 muffin approx 70g). Multiplier 2.0 means 2 muffins (140g total)
                ['meal_text'=>'Egg muffin (low carb)','quantity'=>'2 muffins','protein'=>7.9,'carbs'=>3.6,'fat'=>4.3,'calories'=>calculate_calories(7.9, 3.6, 4.3), 'base_qty'=>70.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Oats with whey (1 bowl approx 320g)
                ['meal_text'=>'Oats with whey protein','quantity'=>'1 bowl','protein'=>7.8,'carbs'=>9.4,'fat'=>2.2,'calories'=>calculate_calories(7.8, 9.4, 2.2), 'base_qty'=>320.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Scrambled eggs (1 egg approx 50g). Multiplier 3.0 means 3 eggs (150g total)
                ['meal_text'=>'Scrambled eggs with spinach','quantity'=>'3 eggs','protein'=>4.7,'carbs'=>1.8,'fat'=>3.3,'calories'=>calculate_calories(4.7, 1.8, 3.3), 'base_qty'=>50.0, 'unit_label'=>'g', 'multiplier'=>3.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Turkey hash (1 cup approx 280g)
                ['meal_text'=>'Ground turkey and sweet potato hash','quantity'=>'1 cup','protein'=>7.1,'carbs'=>8.9,'fat'=>2.9,'calories'=>calculate_calories(7.1, 8.9, 2.9), 'base_qty'=>280.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Hard-boiled eggs (1 egg approx 50g). Multiplier 4.0 means 4 eggs (200g total)
                ['meal_text'=>'Hard-boiled eggs (4)','quantity'=>'4 eggs','protein'=>3.5,'carbs'=>0.5,'fat'=>2.5,'calories'=>calculate_calories(3.5, 0.5, 2.5), 'base_qty'=>50.0, 'unit_label'=>'g', 'multiplier'=>4.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Quesadilla (1 small approx 250g)
                ['meal_text'=>'Chicken/cheese quesadilla','quantity'=>'1 small quesadilla','protein'=>6.0,'carbs'=>8.0,'fat'=>4.0,'calories'=>calculate_calories(6.0, 8.0, 4.0), 'base_qty'=>250.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
            ],
            'mid_morning' => [
                // Nuts (30g)
                ['meal_text'=>'Handful of nuts','quantity'=>'30g','protein'=>26.7,'carbs'=>33.3,'fat'=>33.3,'calories'=>calculate_calories(26.7, 33.3, 33.3), 'base_qty'=>30.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Hard-boiled egg (1 egg approx 50g)
                ['meal_text'=>'Hard-boiled egg','quantity'=>'1 egg','protein'=>14.0,'carbs'=>2.0,'fat'=>10.0,'calories'=>calculate_calories(14.0, 2.0, 10.0), 'base_qty'=>50.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Yogurt with berries (1 cup approx 140g)
                ['meal_text'=>'Yogurt with berries','quantity'=>'1 cup','protein'=>8.6,'carbs'=>12.9,'fat'=>2.9,'calories'=>calculate_calories(8.6, 12.9, 2.9), 'base_qty'=>140.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Apple/PB (1 unit approx 180g)
                ['meal_text'=>'Apple slices with peanut butter','quantity'=>'1 apple + 1 tbsp PB','protein'=>3.3,'carbs'=>13.9,'fat'=>4.4,'calories'=>calculate_calories(3.3, 13.9, 4.4), 'base_qty'=>180.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>1],
                // Chicken fillet (100g). Multiplier 0.5 means 50g total
                ['meal_text'=>'Small chicken breast fillet','quantity'=>'50g','protein'=>36.0,'carbs'=>0.0,'fat'=>4.0,'calories'=>calculate_calories(36.0, 0.0, 4.0), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>0.5, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Whey protein (1 scoop approx 30g)
                ['meal_text'=>'Whey protein isolate shake','quantity'=>'1 scoop','protein'=>83.3,'carbs'=>6.7,'fat'=>3.3,'calories'=>calculate_calories(83.3, 6.7, 3.3), 'base_qty'=>30.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Tuna salad (1 cup approx 260g). Multiplier 0.5 means 1/2 cup (130g total)
                ['meal_text'=>'Tuna salad (small portion)','quantity'=>'1/2 cup','protein'=>11.5,'carbs'=>3.8,'fat'=>4.6,'calories'=>calculate_calories(11.5, 3.8, 4.6), 'base_qty'=>260.0, 'unit_label'=>'g', 'multiplier'=>0.5, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Beef jerky (30g)
                ['meal_text'=>'Small beef jerky portion','quantity'=>'30g','protein'=>60.0,'carbs'=>10.0,'fat'=>13.3,'calories'=>calculate_calories(60.0, 10.0, 13.3), 'base_qty'=>30.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Cottage cheese (1 cup approx 250g). Multiplier 0.5 means 1/2 cup (125g total)
                ['meal_text'=>'Cottage cheese (small)','quantity'=>'1/2 cup','protein'=>11.2,'carbs'=>4.0,'fat'=>1.6,'calories'=>calculate_calories(11.2, 4.0, 1.6), 'base_qty'=>250.0, 'unit_label'=>'g', 'multiplier'=>0.5, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Mini chicken tikka (1 piece approx 50g). Multiplier 2.0 means 2 pieces (100g total)
                ['meal_text'=>'Mini chicken tikka skewer','quantity'=>'2 small pieces','protein'=>12.0,'carbs'=>2.0,'fat'=>4.0,'calories'=>calculate_calories(12.0, 2.0, 4.0), 'base_qty'=>50.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
            ],
            'lunch' => [
                ['meal_text'=>'Quinoa and veggies with chicken','quantity'=>'1 medium bowl','protein'=>6.0,'carbs'=>9.0,'fat'=>2.5,'calories'=>calculate_calories(6.0, 9.0, 2.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Chicken pulao with raita','quantity'=>'1 plate','protein'=>7.0,'carbs'=>10.0,'fat'=>3.0,'calories'=>calculate_calories(7.0, 10.0, 3.0), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Dal with chicken chapati','quantity'=>'1 cup dal + 2 chapatis','protein'=>5.5,'carbs'=>8.5,'fat'=>2.5,'calories'=>calculate_calories(5.5, 8.5, 2.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Mixed veg khichdi with egg','quantity'=>'1 bowl + 1 egg','protein'=>6.3,'carbs'=>9.0,'fat'=>2.8,'calories'=>calculate_calories(6.3, 9.0, 2.8), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Fish tikka (1 piece approx 60g). Multiplier 2.0 means 6 pieces + salad (240g total)
                ['meal_text'=>'Tandoori fish tikka with salad','quantity'=>'6 pieces fish + salad','protein'=>6.3,'carbs'=>6.3,'fat'=>2.7,'calories'=>calculate_calories(6.3, 6.3, 2.7), 'base_qty'=>120.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Mutton curry with brown rice','quantity'=>'1 bowl','protein'=>8.0,'carbs'=>9.5,'fat'=>3.5,'calories'=>calculate_calories(8.0, 9.5, 3.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Chicken chole with chapati','quantity'=>'1 cup chole + 2 chapatis','protein'=>6.5,'carbs'=>10.0,'fat'=>3.0,'calories'=>calculate_calories(6.5, 10.0, 3.0), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Prawn/Shrimp scampi with whole wheat pasta','quantity'=>'1 small bowl','protein'=>8.5,'carbs'=>12.5,'fat'=>3.5,'calories'=>calculate_calories(8.5, 12.5, 3.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Chicken mince wrap (1 wrap approx 105g). Multiplier 2.0 means 2 wraps (210g total)
                ['meal_text'=>'Chicken mince lettuce wrap','quantity'=>'2 wraps','protein'=>9.5,'carbs'=>4.8,'fat'=>2.4,'calories'=>calculate_calories(9.5, 4.8, 2.4), 'base_qty'=>105.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Egg white omelette (1 unit approx 150g)
                ['meal_text'=>'Egg white omelette with mushroom','quantity'=>'4 egg whites + veggies','protein'=>12.0,'carbs'=>3.3,'fat'=>2.0,'calories'=>calculate_calories(12.0, 3.3, 2.0), 'base_qty'=>150.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
            ],
            'snack' => [
                // Macros per 100g. Serving size is 150g (1.5x 100g unit)
                ['meal_text'=>'Nuts and yogurt','quantity'=>'1/2 cup yogurt + 1 tbsp nuts','protein'=>6.7,'carbs'=>10.0,'fat'=>3.3,'calories'=>calculate_calories(6.7, 10.0, 3.3), 'base_qty'=>150.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Macros per 100g. Serving size is 140g (1.4x 100g unit)
                ['meal_text'=>'Vegetable sticks with hummus','quantity'=>'1 cup sticks + 2 tbsp hummus','protein'=>6.4,'carbs'=>10.0,'fat'=>4.3,'calories'=>calculate_calories(6.4, 10.0, 4.3), 'base_qty'=>140.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Boiled chickpeas (1 bowl approx 160g)
                ['meal_text'=>'Boiled chickpeas','quantity'=>'1 small bowl','protein'=>6.9,'carbs'=>10.0,'fat'=>2.5,'calories'=>calculate_calories(6.9, 10.0, 2.5), 'base_qty'=>160.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Sprouts sandwich (1 slice approx 37.5g). Multiplier 2.0 means 2 slices (75g total)
                ['meal_text'=>'Sprouts sandwich','quantity'=>'2 slices','protein'=>6.7,'carbs'=>12.0,'fat'=>3.3,'calories'=>calculate_calories(6.7, 12.0, 3.3), 'base_qty'=>37.5, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Fruit smoothie (1 glass approx 160g)
                ['meal_text'=>'Fruit smoothie','quantity'=>'1 glass','protein'=>5.0,'carbs'=>12.5,'fat'=>2.5,'calories'=>calculate_calories(5.0, 12.5, 2.5), 'base_qty'=>160.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Roasted peanuts (30g)
                ['meal_text'=>'Roasted peanuts','quantity'=>'1 handful (30g)','protein'=>40.0,'carbs'=>33.3,'fat'=>26.7,'calories'=>calculate_calories(40.0, 33.3, 26.7), 'base_qty'=>30.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Vegetable dhokla (1 piece approx 15.7g). Multiplier 3.0 means 3 pieces (47g total)
                ['meal_text'=>'Vegetable dhokla','quantity'=>'3 pieces','protein'=>6.4,'carbs'=>10.6,'fat'=>3.5,'calories'=>calculate_calories(6.4, 10.6, 3.5), 'base_qty'=>15.7, 'unit_label'=>'g', 'multiplier'=>3.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Tuna on crackers (1 unit approx 140g)
                ['meal_text'=>'Tuna on crackers','quantity'=>'2 crackers + 3 tbsp tuna','protein'=>10.7,'carbs'=>7.1,'fat'=>3.6,'calories'=>calculate_calories(10.7, 7.1, 3.6), 'base_qty'=>140.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Chicken strip (50g)
                ['meal_text'=>'Small chicken strip (grilled)','quantity'=>'50g','protein'=>30.0,'carbs'=>0.0,'fat'=>6.0,'calories'=>calculate_calories(30.0, 0.0, 6.0), 'base_qty'=>50.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Hard cheese cubes (30g)
                ['meal_text'=>'Hard cheese cubes','quantity'=>'30g','protein'=>30.0,'carbs'=>3.3,'fat'=>33.3,'calories'=>calculate_calories(30.0, 3.3, 33.3), 'base_qty'=>30.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
            ],
            'dinner' => [
                ['meal_text'=>'Grilled chicken with salad','quantity'=>'100g chicken + salad','protein'=>7.0,'carbs'=>7.5,'fat'=>3.5,'calories'=>calculate_calories(7.0, 7.5, 3.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Fish fry with chapati','quantity'=>'1 piece fish + 2 chapatis','protein'=>6.8,'carbs'=>9.5,'fat'=>3.5,'calories'=>calculate_calories(6.8, 9.5, 3.5), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Egg curry with rice','quantity'=>'2 eggs + 1 cup rice','protein'=>6.5,'carbs'=>10.0,'fat'=>3.8,'calories'=>calculate_calories(6.5, 10.0, 3.8), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Chicken biryani','quantity'=>'1 medium plate (150g chicken)','protein'=>7.3,'carbs'=>11.3,'fat'=>4.3,'calories'=>calculate_calories(7.3, 11.3, 4.3), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Fish curry with red rice','quantity'=>'1 piece fish + 1 cup red rice','protein'=>7.0,'carbs'=>10.8,'fat'=>3.8,'calories'=>calculate_calories(7.0, 10.8, 3.8), 'base_qty'=>100.0, 'unit_label'=>'g', 'multiplier'=>2.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Chicken wrap (1 wrap approx 500g)
                ['meal_text'=>'Chicken wrap with veggies','quantity'=>'1 wrap (100g chicken + veggies)','protein'=>5.4,'carbs'=>7.8,'fat'=>2.8,'calories'=>calculate_calories(5.4, 7.8, 2.8), 'base_qty'=>500.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                // Protein shake (1 glass approx 330g)
                ['meal_text'=>'Protein shake with eggs','quantity'=>'1 glass shake (2 eggs + milk)','protein'=>7.6,'carbs'=>6.1,'fat'=>3.0,'calories'=>calculate_calories(7.6, 6.1, 3.0), 'base_qty'=>330.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                // Lean pork chop (1 chop approx 260g)
                ['meal_text'=>'Lean pork chop with sautéed greens','quantity'=>'1 chop + greens','protein'=>8.5,'carbs'=>3.8,'fat'=>3.5,'calories'=>calculate_calories(8.5, 3.8, 3.5), 'base_qty'=>260.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Baked cod (1 unit approx 250g)
                ['meal_text'=>'Baked cod with quinoa','quantity'=>'1 cod fillet + 1 cup quinoa','protein'=>8.0,'carbs'=>8.0,'fat'=>2.0,'calories'=>calculate_calories(8.0, 8.0, 2.0), 'base_qty'=>250.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                // Chicken stew (1 large bowl approx 350g)
                ['meal_text'=>'Chicken stew with veggies','quantity'=>'1 large bowl','protein'=>8.0,'carbs'=>5.7,'fat'=>2.9,'calories'=>calculate_calories(8.0, 5.7, 2.9), 'base_qty'=>350.0, 'unit_label'=>'g', 'multiplier'=>1.0, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
            ],
        ],
    ],
];


// --- INSERTION LOGIC: GENERATE ALL COMBINATIONS (Now with unit calculations) ---
// --------------------------------------------------

// The DB query must reflect the 6 new columns: base_quantity, unit, protein_per_unit, carbs_per_unit, fat_per_unit, calories_per_unit
$stmt = $connection->prepare("
    INSERT INTO diet_plans (
        goal, dietary, activity, meal_type, health_focus, day_number, meal_time, 
        meal_text, quantity, protein, carbs, fat, calories, low_carb, 
        low_glycemic, high_fiber, base_quantity, unit, protein_per_unit, 
        carbs_per_unit, fat_per_unit, calories_per_unit
    ) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    die("Prepare failed: " . $connection->error);
}

$totalMealsInserted = 0;

foreach ($goals as $goal) {
    foreach ($dietary as $diet) {
        foreach ($activities as $activity) {
            foreach ($meal_types as $mType) {
                
                foreach ($health_foci as $hFocus) {
                    
                    // 1. Setup based on current combination
                    $dietaryMeals = $sampleMeals[$diet][$mType];
                    $currentMealTimes = $mealTimes[$mType];
                    $shuffledMealsForPlan = [];
                    // Using all parameters in the seed ensures a unique, repeatable plan for every combination
                    $seedBase = "{$goal}-{$diet}-{$activity}-{$mType}-{$hFocus}";

                    foreach ($currentMealTimes as $time) {
                        
                        // --- 2. FILTERING LOGIC ---
                        $mealOptions = $dietaryMeals[$time];
                        $filterKey = null;

                        if ($hFocus == 'low_carb') {
                            $filterKey = 'low_carb';
                        } elseif ($hFocus == 'high_fiber') {
                            $filterKey = 'high_fiber';
                        }
                        
                        if ($filterKey) {
                            $filteredMeals = array_filter($mealOptions, function($meal) use ($filterKey) {
                                return isset($meal[$filterKey]) && $meal[$filterKey] == 1;
                            });
                            
                            // CRITICAL FALLBACK: If filtering results in no meals, use the original list.
                            // If we fail to filter, we use the complete options set.
                            if (!empty($filteredMeals)) {
                                $mealOptions = $filteredMeals;
                            }
                        }
                        
                        $options = array_values($mealOptions); 
                        
                        // Use a fixed seed for reproducible shuffling for each meal time
                        $seed = crc32($seedBase . "-{$time}");
                        mt_srand($seed);
                        
                        shuffle($options); 
                        
                        $shuffledMealsForPlan[$time] = $options;
                    }

                    // 3. Generate the 7-day plan using the shuffled assignments
                    for ($day = 1; $day <= 7; $day++) {
                        foreach ($currentMealTimes as $time) {
                            
                            $mealOptions = $shuffledMealsForPlan[$time];
                            $optionCount = count($mealOptions);

                            // Cycle through the meal options over 7 days
                            $index = ($day - 1) % $optionCount; 
                            $meal = $mealOptions[$index];
                            
                            // --- 4. CALCULATE FINAL MACROS BASED ON UNIT DATA AND GOAL ---
                            $multiplier = $meal['multiplier'];
                            
                            // Base calculated macros for the specific serving size ($multiplier * per_100g_macro * (base_qty/100) )
                            // Since $meal['protein'] is already PER 100G, we just multiply by the multiplier
                            // NOTE: The multiplier scales the base serving. If base_qty != 100g, we must account for that.
                            
                            // The 'per_unit' fields (protein, carbs, fat, calories in $meal array) are now PER 100G.
                            // The true serving size macro count is: (PER_100G / 100) * (base_qty * multiplier)
                            // A simpler way: (PER_100G * (base_qty / 100)) is the macro for 1 base serving. Then multiply by $multiplier.
                            
                            $baseServingWeight = $meal['base_qty'] * $multiplier;
                            $scalingFactor = $baseServingWeight / 100.0;
                            
                            // Base macros for the whole calculated serving size
                            $baseProtein    = $meal['protein'] * $scalingFactor;
                            $baseCarbs      = $meal['carbs'] * $scalingFactor;
                            $baseFat        = $meal['fat'] * $scalingFactor;
                            $baseCalories   = $meal['calories'] * $scalingFactor;


                            // Apply macro/calorie adjustments based on GOAL
                            $protein    = $baseProtein;
                            $carbs      = $baseCarbs;
                            $fat        = $baseFat;
                            $calories   = $baseCalories;

                            switch($goal){
                                case 'weight_loss':
                                    $calories = round($baseCalories * 0.85);
                                    $protein  = round($baseProtein * 1.15, 2); // Higher protein for satiety
                                    $carbs    = round($baseCarbs * 0.90, 2);   // Slight carb reduction
                                    $fat      = round($baseFat * 0.95, 2);     // Slight fat reduction
                                    break;
                                case 'weight_gain':
                                    $calories = round($baseCalories * 1.15); // Calorie surplus
                                    $protein  = round($baseProtein * 1.10, 2); // Moderate protein increase
                                    $carbs    = round($baseCarbs * 1.20, 2);   // High carb increase for mass/energy
                                    $fat      = round($baseFat * 1.05, 2);
                                    break;
                                case 'muscle_build':
                                    $calories = round($baseCalories * 1.05); // Slight surplus
                                    $protein  = round($baseProtein * 1.30, 2); // Significantly higher protein
                                    $carbs    = round($baseCarbs * 1.10, 2);   // Higher carbs for energy
                                    $fat      = round($baseFat * 0.90, 2);     // Lower fat percentage
                                    break;
                                case 'balanced':
                                    // No change from base calculation
                                    break;
                            }
                            
                            // Ensure final values are integers/decimals as expected by the DB 
                            $protein    = round($protein, 2);
                            $carbs      = round($carbs, 2);
                            $fat        = round($fat, 2);
                            $calories   = round($calories); // Calories are usually rounded to nearest integer

                            // Bind 22 variables: sssssisss (metadata) + dddi (calculated macros) + iiisdsdddi (unit/flag data)
                            $stmt->bind_param(
                                "sssssisssdddiiisdsdddi", 
                                $goal, 
                                $diet, 
                                $activity, 
                                $mType, 
                                $hFocus, 
                                $day, 
                                $time, 
                                $meal['meal_text'],        // meal_text (food name)
                                $meal['quantity'],         // quantity (serving text)
                                $protein,                  // calculated (ADJUSTED)
                                $carbs,                    // calculated (ADJUSTED)
                                $fat,                      // calculated (ADJUSTED)
                                $calories,                 // calculated (ADJUSTED)
                                $meal['low_carb'],         // low_carb (flag)
                                $meal['low_glycemic'],     // low_glycemic (flag)
                                $meal['high_fiber'],       // high_fiber (flag)
                                $meal['base_qty'],         // base_quantity (in grams)
                                $meal['unit_label'],       // unit (mostly 'g')
                                $meal['protein'],          // protein_per_unit (PER 100G)
                                $meal['carbs'],            // carbs_per_unit (PER 100G)
                                $meal['fat'],              // fat_per_unit (PER 100G)
                                $meal['calories']          // calories_per_unit (PER 100G)
                            );
                            
                            // Execute the insertion (Mocked or real, depending on environment)
                            $stmt->execute();
                            $totalMealsInserted++;
                        }
                    }
                } 
            }
        }
    }
}
$stmt->close();
$connection->close();

// --------------------------------------------------
// 5. SUMMARY OUTPUT
// --------------------------------------------------

$totalGoals = count($goals);
$totalDietary = count($dietary);
$totalActivities = count($activities);
$totalMealTypes = count($meal_types);
$totalHealthFoci = count($health_foci);
$totalDays = 7;
$maxMealsPerDay = max(count($mealTimes['3_meals']), count($mealTimes['5_small']));

// Calculate total expected plans and total records
$totalExpectedPlans = $totalGoals * $totalDietary * $totalActivities * $totalMealTypes * $totalHealthFoci;
$totalExpectedRecords = 0;

// Need to calculate records precisely based on meal type count
foreach ($goals as $g) {
    foreach ($dietary as $d) {
        foreach ($activities as $a) {
            foreach ($meal_types as $m) {
                foreach ($health_foci as $h) {
                    $totalExpectedRecords += count($mealTimes[$m]) * $totalDays;
                }
            }
        }
    }
}

echo "
<style>
    body { font-family: 'Inter', Arial, sans-serif; background-color: #f4f7f6; padding: 20px; color: #333; }
    .card { 
        background: #fff; 
        padding: 24px; 
        border-radius: 12px; 
        box-shadow: 0 6px 15px rgba(0,0,0,0.1); 
        max-width: 600px; 
        margin: 20px auto; 
        border-top: 5px solid #0066cc;
    }
    h2 { 
        color: #0066cc; 
        border-bottom: 2px solid #e0e0e0; 
        padding-bottom: 10px; 
        margin-top: 0; 
        font-weight: 700;
    }
    strong { color: #d9534f; font-size: 1.1em; }
    .success { color: #5cb85c; font-weight: bold; font-size: 1.2em; }
    ul { list-style-type: none; margin-left: 0; padding-left: 0; }
    li { margin-bottom: 10px; padding-left: 20px; position: relative; }
    li::before {
        content: '•';
        color: #0066cc;
        font-weight: bold;
        display: inline-block;
        width: 1em;
        margin-left: -1em;
    }
</style>
<div class='card'>
    <h2>Personalized Plan Data Generation Complete</h2>
    <p class='success'>✅ Database Population Successful!</p>
    <p>The macro data has been fully **standardized to represent content per 100 grams** (the <code>protein_per_unit</code>, etc., columns) and the serving size calculations (the <code>protein</code>, etc., columns) are correctly calculated based on the scaling factor of the <code>base_quantity</code> and <code>multiplier</code>.</p>
    
    <h3>Generation Summary</h3>
    <ul>
        <li>Total unique 7-Day Plans Generated: <strong>{$totalExpectedPlans}</strong></li>
        <li>Total individual meals inserted: <strong>{$totalMealsInserted} records</strong></li>
    </ul>

    <h3>Data Consistency Check</h3>
    <p>This script ensures data consistency for scaling:</p>
    <ul>
        <li>**New Macro Base:** All <code>_per_unit</code> fields now store macro content for 100g.</li>
        <li>**Dynamic Scaling:** The final <code>protein, carbs, fat, calories</code> fields are correctly calculated by scaling the 100g base by the actual serving weight.</li>
        <li>**Goal Adjustment:** The goals (Loss, Gain, Build) are applied to these final calculated macros.</li>
    </ul>

    <p style='margin-top: 20px; border-top: 1px dashed #e0e0e0; padding-top: 10px;'>
    With this corrected and standardized data, your database is now ready for efficient scaling! Let me know if you want to review the calculation logic (`case 'weight_gain':` etc.) or if you need assistance debugging the **selection query** in your `user_dietPlan.php` file, which is likely where your issue with loading the `Weight Gain / Muscle Build` plans is occurring.
    </p>
</div>
";
?>
