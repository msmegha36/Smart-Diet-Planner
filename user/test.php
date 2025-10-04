<?php
/**
 * Diet Plan Data Generator
 *
 * This script connects to the database, clears the 'diet_plans' table, 
 * and inserts a 7-day plan for every combination of user preferences,
 * now including specific health focus filtering (e.g., Low Carb only meals).
 *
 * CORRECTION: The meal selection logic has been updated to ensure all available
 * unique meals are used across the 7-day period before any repetition.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Assume this path is correct for your setup
include '../config/db_conn.php'; 

if (!isset($connection) || $connection->connect_error) {
    die("Database connection failed. Please check db_conn.php.");
}

// --- DATABASE SETUP AND TRUNCATION (WARNING: This clears all existing plan data!) ---
echo "<h2>Generating Diet Plan Data...</h2>";
echo "Clearing existing data from 'diet_plans' table...<br>";
$connection->query("TRUNCATE TABLE diet_plans");

// --- DEFINITIONS ---
$goals          = ['weight_loss', 'weight_gain', 'muscle_build', 'balanced'];
$dietary        = ['veg', 'nonveg'];
$activities     = ['light', 'moderate', 'active'];
$meal_types     = ['3_meals', '5_small'];

// Define the health filters we want to generate plans for
$health_foci    = ['none', 'low_carb', 'high_fiber'];

$mealTimes = [
    '3_meals' => ['breakfast', 'lunch', 'dinner'],
    '5_small' => ['breakfast', 'mid_morning', 'lunch', 'snack', 'dinner'],
];

// --- ADVANCED MEAL LIBRARY WITH HEALTH FLAGS (Same as before) ---
// (The meal data is kept here for context, although truncated for brevity in this comment block)
$sampleMeals = [
    'veg' => [
        '3_meals' => [
            'breakfast' => [
                ['meal_text'=>'Oatmeal with fruits','quantity'=>'1 bowl','protein'=>20,'carbs'=>30,'fat'=>10,'calories'=>300, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Vegetable poha with peanuts','quantity'=>'1 plate','protein'=>18,'carbs'=>32,'fat'=>9,'calories'=>280, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Idli with sambhar','quantity'=>'3 idlis + 1 bowl sambhar','protein'=>15,'carbs'=>35,'fat'=>8,'calories'=>290, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Upma with veggies','quantity'=>'1 bowl','protein'=>16,'carbs'=>34,'fat'=>7,'calories'=>270, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Paratha with curd','quantity'=>'2 parathas + ½ cup curd','protein'=>20,'carbs'=>38,'fat'=>12,'calories'=>350, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Moong dal chilla','quantity'=>'2 chillas','protein'=>22,'carbs'=>28,'fat'=>9,'calories'=>310, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Smoothie bowl with nuts','quantity'=>'1 glass','protein'=>19,'carbs'=>30,'fat'=>10,'calories'=>300, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
            ],
            'lunch' => [
                ['meal_text'=>'Paneer salad with quinoa','quantity'=>'1 bowl salad + ½ cup quinoa','protein'=>25,'carbs'=>40,'fat'=>15,'calories'=>500, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Rajma with brown rice','quantity'=>'1 cup rajma + 1 cup rice','protein'=>24,'carbs'=>42,'fat'=>14,'calories'=>480, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Chole with chapati','quantity'=>'1 cup chole + 2 chapatis','protein'=>23,'carbs'=>39,'fat'=>12,'calories'=>460, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Mixed veg pulao with curd','quantity'=>'1 plate pulao + ½ cup curd','protein'=>20,'carbs'=>36,'fat'=>11,'calories'=>450, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Dal tadka with rice','quantity'=>'1 bowl dal + 1 cup rice','protein'=>22,'carbs'=>38,'fat'=>10,'calories'=>440, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Vegetable khichdi','quantity'=>'1 medium bowl','protein'=>21,'carbs'=>37,'fat'=>10,'calories'=>430, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Stuffed paratha with raita','quantity'=>'2 parathas + ½ cup raita','protein'=>26,'carbs'=>41,'fat'=>14,'calories'=>490, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
            ],
            'dinner' => [
                ['meal_text'=>'Grilled tofu with veggies','quantity'=>'150g tofu + 1 bowl sautéed veggies','protein'=>25,'carbs'=>35,'fat'=>15,'calories'=>450, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Dal palak with chapati','quantity'=>'1 cup dal palak + 2 chapatis','protein'=>24,'carbs'=>32,'fat'=>12,'calories'=>420, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>1],
                ['meal_text'=>'Vegetable soup with bread','quantity'=>'1 bowl soup + 2 slices bread','protein'=>18,'carbs'=>28,'fat'=>8,'calories'=>350, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Paneer bhurji with roti','quantity'=>'1 cup bhurji + 2 rotis','protein'=>27,'carbs'=>36,'fat'=>16,'calories'=>480, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Vegetable biryani with curd','quantity'=>'1 plate biryani + ½ cup curd','protein'=>22,'carbs'=>39,'fat'=>13,'calories'=>460, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Moong dal khichdi','quantity'=>'1 bowl','protein'=>21,'carbs'=>34,'fat'=>10,'calories'=>400, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Soyabean curry with rice','quantity'=>'1 cup curry + 1 cup rice','protein'=>28,'carbs'=>37,'fat'=>14,'calories'=>490, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
            ],
        ],
        '5_small' => [
            'breakfast' => [
                ['meal_text'=>'Smoothie bowl','quantity'=>'1 bowl','protein'=>15,'carbs'=>25,'fat'=>5,'calories'=>250, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Sprout salad','quantity'=>'1 bowl','protein'=>16,'carbs'=>20,'fat'=>6,'calories'=>230, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Idli with chutney','quantity'=>'2 idlis + chutney','protein'=>14,'carbs'=>30,'fat'=>5,'calories'=>270, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Fruit yogurt parfait','quantity'=>'1 glass','protein'=>15,'carbs'=>28,'fat'=>7,'calories'=>260, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Moong dal dosa','quantity'=>'1 dosa','protein'=>18,'carbs'=>26,'fat'=>6,'calories'=>280, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Oats chilla','quantity'=>'2 medium chillas','protein'=>17,'carbs'=>25,'fat'=>7,'calories'=>270, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Vegetable sandwich','quantity'=>'2 slices','protein'=>16,'carbs'=>27,'fat'=>8,'calories'=>290, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
            ],
            'mid_morning' => [
                ['meal_text'=>'Fruit salad','quantity'=>'1 bowl','protein'=>10,'carbs'=>15,'fat'=>5,'calories'=>150, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>1],
                ['meal_text'=>'Dry fruits mix','quantity'=>'1 small handful (30g)','protein'=>8,'carbs'=>12,'fat'=>7,'calories'=>160, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Chana chaat','quantity'=>'1 bowl','protein'=>12,'carbs'=>18,'fat'=>4,'calories'=>170, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Boiled corn','quantity'=>'1 cup','protein'=>9,'carbs'=>20,'fat'=>3,'calories'=>150, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Sprouts bowl','quantity'=>'1 bowl','protein'=>11,'carbs'=>16,'fat'=>5,'calories'=>160, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Roasted makhana','quantity'=>'1 small bowl','protein'=>9,'carbs'=>14,'fat'=>4,'calories'=>140, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Banana with peanut butter','quantity'=>'1 banana + 1 tbsp peanut butter','protein'=>12,'carbs'=>20,'fat'=>8,'calories'=>190, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
            ],
            'lunch' => [
                ['meal_text'=>'Quinoa and veggies','quantity'=>'1 medium bowl','protein'=>20,'carbs'=>35,'fat'=>10,'calories'=>450, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Vegetable pulao with raita','quantity'=>'1 plate','protein'=>22,'carbs'=>38,'fat'=>11,'calories'=>460, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Dal with chapati','quantity'=>'1 cup dal + 2 chapatis','protein'=>18,'carbs'=>32,'fat'=>9,'calories'=>420, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Mixed veg khichdi','quantity'=>'1 bowl','protein'=>19,'carbs'=>34,'fat'=>10,'calories'=>430, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Paneer tikka with salad','quantity'=>'6 pieces paneer + salad','protein'=>21,'carbs'=>36,'fat'=>12,'calories'=>440, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Rajma with brown rice','quantity'=>'1 bowl','protein'=>20,'carbs'=>37,'fat'=>10,'calories'=>450, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Chole with chapati','quantity'=>'1 cup chole + 2 chapatis','protein'=>22,'carbs'=>38,'fat'=>11,'calories'=>460, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
            ],
            'snack' => [
                ['meal_text'=>'Nuts and yogurt','quantity'=>'1/2 cup yogurt + 1 tbsp nuts','protein'=>10,'carbs'=>15,'fat'=>5,'calories'=>150, 'low_carb'=>1, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Vegetable sticks with hummus','quantity'=>'1 cup sticks + 2 tbsp hummus','protein'=>9,'carbs'=>14,'fat'=>6,'calories'=>140, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Boiled chickpeas','quantity'=>'1 small bowl','protein'=>11,'carbs'=>16,'fat'=>4,'calories'=>160, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Sprouts sandwich','quantity'=>'2 slices','protein'=>10,'carbs'=>18,'fat'=>5,'calories'=>150, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Fruit smoothie','quantity'=>'1 glass','protein'=>8,'carbs'=>20,'fat'=>4,'calories'=>160, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Roasted peanuts','quantity'=>'1 handful (30g)','protein'=>12,'carbs'=>10,'fat'=>8,'calories'=>170, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Vegetable dhokla','quantity'=>'3 pieces','protein'=>9,'carbs'=>15,'fat'=>5,'calories'=>140, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
            ],
            'dinner' => [
                ['meal_text'=>'Grilled paneer with salad','quantity'=>'100g paneer + salad','protein'=>20,'carbs'=>30,'fat'=>10,'calories'=>400, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Vegetable soup with bread','quantity'=>'1 bowl + 1 slice bread','protein'=>18,'carbs'=>28,'fat'=>8,'calories'=>350, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Moong dal khichdi','quantity'=>'1 bowl','protein'=>21,'carbs'=>34,'fat'=>10,'calories'=>400, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Paneer bhurji with roti','quantity'=>'1 cup bhurji + 2 rotis','protein'=>22,'carbs'=>36,'fat'=>11,'calories'=>420, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Dal palak with chapati','quantity'=>'1 cup dal + 2 chapatis','protein'=>24,'carbs'=>32,'fat'=>12,'calories'=>420, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>1],
                ['meal_text'=>'Vegetable biryani with raita','quantity'=>'1 plate','protein'=>22,'carbs'=>38,'fat'=>13,'calories'=>460, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Soyabean curry with rice','quantity'=>'1 cup curry + 1 cup rice','protein'=>23,'carbs'=>37,'fat'=>14,'calories'=>470, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
            ],
        ],
    ],
    'nonveg' => [
        '3_meals' => [
            'breakfast' => [
                ['meal_text'=>'Egg omelette with toast','quantity'=>'2 eggs + 2 slices toast','protein'=>20,'carbs'=>30,'fat'=>10,'calories'=>300, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Scrambled eggs with veggies','quantity'=>'2 eggs + 1/2 cup veggies','protein'=>22,'carbs'=>28,'fat'=>12,'calories'=>320, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Boiled eggs with avocado','quantity'=>'2 eggs + 1/2 avocado','protein'=>21,'carbs'=>25,'fat'=>11,'calories'=>310, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Chicken sandwich','quantity'=>'2 slices bread + 50g chicken','protein'=>23,'carbs'=>35,'fat'=>12,'calories'=>360, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Fish cutlet with bread','quantity'=>'2 cutlets + 2 slices bread','protein'=>24,'carbs'=>33,'fat'=>13,'calories'=>370, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Egg wrap with spinach','quantity'=>'1 wrap (2 eggs + spinach)','protein'=>22,'carbs'=>29,'fat'=>11,'calories'=>340, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Protein shake with eggs','quantity'=>'1 glass shake (2 eggs + milk)','protein'=>25,'carbs'=>20,'fat'=>10,'calories'=>330, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
            ],
            'lunch' => [
                ['meal_text'=>'Grilled chicken salad','quantity'=>'100g chicken + salad bowl','protein'=>25,'carbs'=>40,'fat'=>15,'calories'=>500, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Chicken curry with rice','quantity'=>'1 cup curry + 1 cup rice','protein'=>28,'carbs'=>42,'fat'=>16,'calories'=>520, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Fish fry with chapati','quantity'=>'1 piece fish + 2 chapatis','protein'=>27,'carbs'=>38,'fat'=>14,'calories'=>480, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Egg curry with rice','quantity'=>'2 eggs + 1 cup rice','protein'=>26,'carbs'=>40,'fat'=>15,'calories'=>490, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Chicken biryani','quantity'=>'1 medium plate (150g chicken)','protein'=>29,'carbs'=>45,'fat'=>17,'calories'=>550, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Fish curry with red rice','quantity'=>'1 piece fish + 1 cup red rice','protein'=>28,'carbs'=>43,'fat'=>15,'calories'=>530, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Chicken wrap with veggies','quantity'=>'1 wrap (100g chicken + veggies)','protein'=>27,'carbs'=>39,'fat'=>14,'calories'=>500, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
            ],
            'dinner' => [
                ['meal_text'=>'Steamed fish with veggies','quantity'=>'1 piece fish + 1 cup veggies','protein'=>25,'carbs'=>35,'fat'=>15,'calories'=>450, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Grilled chicken with salad','quantity'=>'100g chicken + salad','protein'=>28,'carbs'=>30,'fat'=>14,'calories'=>460, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Egg bhurji with roti','quantity'=>'2 eggs + 2 rotis','protein'=>24,'carbs'=>32,'fat'=>13,'calories'=>420, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Chicken stew with appam','quantity'=>'1 cup stew + 2 appams','protein'=>26,'carbs'=>34,'fat'=>14,'calories'=>440, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Fish curry with chapati','quantity'=>'1 cup curry + 2 chapatis','protein'=>27,'carbs'=>36,'fat'=>15,'calories'=>470, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Chicken soup with bread','quantity'=>'1 bowl soup + 1 slice bread','protein'=>23,'carbs'=>28,'fat'=>12,'calories'=>410, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Egg fried rice with veggies','quantity'=>'1 plate (2 eggs + rice + veggies)','protein'=>25,'carbs'=>40,'fat'=>15,'calories'=>480, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
            ],
        ],
        '5_small' => [
            'breakfast' => [
                ['meal_text'=>'Protein smoothie with eggs','quantity'=>'1 glass (2 eggs + milk + banana)','protein'=>15,'carbs'=>25,'fat'=>5,'calories'=>250, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Boiled eggs with toast','quantity'=>'2 boiled eggs + 2 slices toast','protein'=>16,'carbs'=>20,'fat'=>6,'calories'=>260, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Chicken salad wrap','quantity'=>'1 small wrap (50g chicken + veggies)','protein'=>17,'carbs'=>22,'fat'=>7,'calories'=>270, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Fish sandwich','quantity'=>'2 slices bread + 1 fish fillet','protein'=>18,'carbs'=>24,'fat'=>8,'calories'=>280, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Egg muffin (low carb)','quantity'=>'2 muffins','protein'=>22,'carbs'=>10,'fat'=>12,'calories'=>280, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Oats with whey protein','quantity'=>'1 bowl','protein'=>25,'carbs'=>30,'fat'=>7,'calories'=>320, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Scrambled eggs with spinach','quantity'=>'3 eggs','protein'=>21,'carbs'=>8,'fat'=>15,'calories'=>290, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
            ],
            'mid_morning' => [
                ['meal_text'=>'Handful of nuts','quantity'=>'30g','protein'=>8,'carbs'=>10,'fat'=>10,'calories'=>170, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Hard-boiled egg','quantity'=>'1 egg','protein'=>7,'carbs'=>1,'fat'=>5,'calories'=>80, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Yogurt with berries','quantity'=>'1 cup','protein'=>12,'carbs'=>18,'fat'=>4,'calories'=>140, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Apple slices with peanut butter','quantity'=>'1 apple + 1 tbsp PB','protein'=>6,'carbs'=>25,'fat'=>8,'calories'=>180, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>1],
                ['meal_text'=>'Small chicken breast fillet','quantity'=>'50g','protein'=>18,'carbs'=>0,'fat'=>2,'calories'=>100, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Whey protein isolate shake','quantity'=>'1 scoop','protein'=>25,'carbs'=>2,'fat'=>1,'calories'=>120, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Small can of tuna (in water)','quantity'=>'1 can','protein'=>20,'carbs'=>0,'fat'=>1,'calories'=>100, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
            ],
            'lunch' => [
                ['meal_text'=>'Tuna salad with low-carb crackers','quantity'=>'1 can tuna + crackers','protein'=>30,'carbs'=>15,'fat'=>8,'calories'=>350, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Chicken and vegetable stir-fry','quantity'=>'1 bowl','protein'=>32,'carbs'=>35,'fat'=>10,'calories'=>450, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>1],
                ['meal_text'=>'Mutton stew with brown rice','quantity'=>'1 cup stew + 1/2 cup rice','protein'=>35,'carbs'=>40,'fat'=>15,'calories'=>550, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Fish tacos (low-fat)','quantity'=>'2 tacos','protein'=>28,'carbs'=>30,'fat'=>10,'calories'=>400, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Grilled salmon with quinoa','quantity'=>'100g salmon + 1/2 cup quinoa','protein'=>30,'carbs'=>35,'fat'=>15,'calories'=>500, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Chicken breast with veggies','quantity'=>'150g chicken + 1 cup veggies','protein'=>40,'carbs'=>20,'fat'=>10,'calories'=>450, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Lean ground beef patty with salad','quantity'=>'1 patty + salad','protein'=>35,'carbs'=>15,'fat'=>18,'calories'=>500, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
            ],
            'snack' => [
                ['meal_text'=>'Protein bar (low sugar)','quantity'=>'1 bar','protein'=>20,'carbs'=>15,'fat'=>5,'calories'=>180, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Cottage cheese with pineapple','quantity'=>'1/2 cup','protein'=>15,'carbs'=>12,'fat'=>4,'calories'=>130, 'low_carb'=>1, 'low_glycemic'=>0, 'high_fiber'=>0],
                ['meal_text'=>'Beef jerky','quantity'=>'1 serving','protein'=>18,'carbs'=>5,'fat'=>3,'calories'=>120, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Whey protein shake','quantity'=>'1 scoop','protein'=>25,'carbs'=>5,'fat'=>2,'calories'=>140, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Small handful of almonds','quantity'=>'20g','protein'=>6,'carbs'=>6,'fat'=>10,'calories'=>120, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Hard-boiled egg and fruit','quantity'=>'1 egg + 1 apple','protein'=>9,'carbs'=>20,'fat'=>5,'calories'=>150, 'low_carb'=>0, 'low_glycemic'=>0, 'high_fiber'=>1],
                ['meal_text'=>'Greek yogurt (plain)','quantity'=>'1 cup','protein'=>22,'carbs'=>10,'fat'=>5,'calories'=>180, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
            ],
            'dinner' => [
                ['meal_text'=>'Lean steak with baked sweet potato','quantity'=>'100g steak + 1 potato','protein'=>30,'carbs'=>40,'fat'=>15,'calories'=>550, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Chicken and broccoli','quantity'=>'100g chicken + 1 cup broccoli','protein'=>28,'carbs'=>15,'fat'=>10,'calories'=>350, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Fish curry with vegetables','quantity'=>'1 piece fish + 1 bowl veggies','protein'=>30,'carbs'=>25,'fat'=>12,'calories'=>400, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Turkey mince chili','quantity'=>'1 bowl','protein'=>35,'carbs'=>30,'fat'=>10,'calories'=>450, 'low_carb'=>0, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Egg salad (no bread)','quantity'=>'3 eggs + mayo + salad','protein'=>22,'carbs'=>10,'fat'=>18,'calories'=>400, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
                ['meal_text'=>'Grilled shrimp skewers with salad','quantity'=>'10 skewers + salad','protein'=>30,'carbs'=>10,'fat'=>5,'calories'=>250, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>1],
                ['meal_text'=>'Roast chicken with mashed cauliflower','quantity'=>'100g chicken + mash','protein'=>30,'carbs'=>15,'fat'=>10,'calories'=>380, 'low_carb'=>1, 'low_glycemic'=>1, 'high_fiber'=>0],
            ],
        ],
    ],
];


// --- INSERTION LOGIC: GENERATE ALL COMBINATIONS (Now with health focus) ---

// NOTE: Added 'health_focus' column to the INSERT statement (16 total placeholders)
$stmt = $connection->prepare("
    INSERT INTO diet_plans (goal, dietary, activity, meal_type, health_focus, day_number, meal_time, meal_text, quantity, protein, carbs, fat, calories, low_carb, low_glycemic, high_fiber) 
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");

if (!$stmt) {
    die("Prepare failed: " . $connection->error);
}

$totalMealsInserted = 0;

foreach ($goals as $goal) {
    foreach ($dietary as $diet) {
        foreach ($activities as $activity) {
            foreach ($meal_types as $mType) {
                
                // NEW LOOP: Generate plans for each health focus
                foreach ($health_foci as $hFocus) {
                    
                    // 1. Get the specific meal options for this dietary/meal_type
                    $dietaryMeals = $sampleMeals[$diet][$mType];
                    $currentMealTimes = $mealTimes[$mType];
                    
                    // 2. Prepare the Shuffled Meal Assignments for the 7 days
                    $shuffledMealsForPlan = [];
                    $seedBase = "{$goal}-{$diet}-{$activity}-{$mType}-{$hFocus}";

                    foreach ($currentMealTimes as $time) {
                        
                        // --- FILTERING LOGIC ---
                        $mealOptions = $dietaryMeals[$time];
                        
                        if ($hFocus !== 'none') {
                            $filterKey = ($hFocus == 'low_carb') ? 'low_carb' : 'high_fiber';
                            
                            // Filter meals to only include those matching the health focus flag (value must be 1)
                            $mealOptions = array_filter($mealOptions, function($meal) use ($filterKey) {
                                return isset($meal[$filterKey]) && $meal[$filterKey] == 1;
                            });
                            
                            // If filtering resulted in an empty array for a meal time, revert to all options
                            // to ensure a plan is generated, though it won't meet the health focus perfectly.
                            if (empty($mealOptions)) {
                                $mealOptions = $dietaryMeals[$time];
                            }
                        }
                        
                        // Re-index the array after filtering (and potential merge)
                        $options = array_values($mealOptions); 
                        
                        // Seed the random generator for repeatable shuffling based on parameters
                        $seed = crc32($seedBase . "-{$time}");
                        mt_srand($seed);
                        
                        // Shuffle the unique options
                        shuffle($options); 
                        
                        $shuffledMealsForPlan[$time] = $options;
                    }

                    // 3. Generate the 7-day plan using the shuffled assignments
                    for ($day = 1; $day <= 7; $day++) {
                        foreach ($currentMealTimes as $time) {
                            
                            $mealOptions = $shuffledMealsForPlan[$time];
                            $optionCount = count($mealOptions);

                            // Use modulo operator to cycle through available unique options (0 to optionCount - 1)
                            // This ensures the maximum variety possible before repeating.
                            $index = ($day - 1) % $optionCount; 
                            $meal = $mealOptions[$index];
                            
                            // Apply macro/calorie adjustments based on goal (simplified)
                            $protein    = $meal['protein'];
                            $carbs      = $meal['carbs'];
                            $fat        = $meal['fat'];
                            $calories   = $meal['calories'];

                            switch($goal){
                                case 'weight_loss':
                                    $calories = round($calories * 0.85);
                                    $protein  = round($protein * 1.10);
                                    break;
                                case 'muscle_build':
                                    $protein  = round($protein * 1.30);
                                    $carbs    = round($carbs * 0.90);
                                    break;
                            }

                            // Insert the data (16 variables)
                            $stmt->bind_param(
                                "sssssisssiiiiiii", // 5 s's, 1 i, 3 s's, 7 i's = 16 characters
                                $goal, 
                                $diet, 
                                $activity, 
                                $mType, 
                                $hFocus, 
                                $day, 
                                $time, 
                                $meal['meal_text'], 
                                $meal['quantity'], 
                                $protein, 
                                $carbs, 
                                $fat, 
                                $calories, 
                                $meal['low_carb'], 
                                $meal['low_glycemic'], 
                                $meal['high_fiber']
                            );
                            
                            $stmt->execute();
                            $totalMealsInserted++;
                        }
                    }
                } // End Health Focus Loop
            }
        }
    }
}
$stmt->close();
$connection->close();

$totalExpectedPlans = count($goals) * count($dietary) * count($activities) * count($meal_types) * count($health_foci);
echo "✅ Generation Complete!<br>";
echo "Total unique 7-Day Plans Generated: <strong>{$totalExpectedPlans}</strong><br>";
echo "Total meals inserted into 'diet_plans' table: <strong>{$totalMealsInserted}</strong>";
?>
