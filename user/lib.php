<?php
/**
 * Diet Plan Data Generator (Final Fix - Schema Alignment)
 *
 * This version implements the following critical fixes:
 * 1. Database Connection: Relies on the actual '$connection' variable.
 * 2. SCHEMA ALIGNMENT: All SQL column names are updated to match the provided table structure:
 * (e.g., 'dietary' instead of 'dietary_preference', 'meal_time' instead of 'meal_slot').
 * 3. UNIT MACRO CALCULATION: Logic now correctly calculates and inserts both
 * Total Macros (calories, protein, carbs, fat) and Unit Macros (*_per_unit).
 * 4. Data Structure Fix: Ensures all 48 plans have 42 records (7 days x 6 slots).
 * 5. SQL Safety: Properly uses $connection->real_escape_string() for all string values.
 */
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Assume this path is correct for your setup
include '../config/db_conn.php'; 

// Check for the actual connection object
if (!isset($connection) || $connection->connect_error) {
    die("Database connection failed. Please check db_conn.php or the \$connection variable.");
}


// --- DEFINITIONS ---
$goals          = ['weight_loss', 'weight_gain', 'muscle_build', 'balanced'];
$dietary        = ['veg', 'non_veg'];
$activity       = ['light', 'moderate', 'very_active'];
$meal_types     = ['3_meals', '5_small_meals']; 

$totalMealsInserted = 0;

// --- EXPANDED MEAL DATA DEFINITION (8+ UNIQUE OPTIONS PER SLOT) ---
$mealData = [
    // --- NON-VEG HIGH CALORIE / HIGH PROTEIN (for Weight Gain/Muscle Build) ---
    'non_veg_gain' => [
        'breakfast' => [
            "4-Egg Omelet w/ Cheese, 2 Slices Toast, Full-Fat Milk",
            "Chicken Sausage (3) & Scramble (4 eggs) w/ Hash Browns and Avocado",
            "Steak & Eggs (150g steak) w/ Sauteed Spinach and Butter",
            "Mass Gainer Shake (Oats, 2 scoops Protein, Walnuts, Milk)",
            "Mince Meat (Keema) Paratha (3) w/ Full-Fat Yogurt",
            "Smoked Salmon Bagel w/ Cream Cheese and Capers (High-Calorie)",
            "Leftover Chicken and Rice Stir-fry (Large portion)",
            "High-Protein Waffles (4) w/ Bacon (3) and Syrup" 
        ],
        'lunch' => [
            "250g Grilled Chicken Breast, 2 cups Rice, Lentil Soup, Avocado",
            "Large Mutton/Goat Curry, 3 Rotis, Salad w/ Olive Oil Dressing",
            "Large Chicken Biryani w/ Raita and 100g Paneer/Cheese",
            "2 Tuna Melts on Whole Wheat, Large Sweet Potato Fries",
            "200g Fish (Salmon/Mackerel), 1.5 cups Quinoa, Roasted Veggies",
            "Lean Ground Beef (200g) Patty, Whole-Wheat Bun, Large Side Salad",
            "Chicken and Cheese Pasta (extra large serving) w/ Cream Sauce",
            "Pulled Pork Sandwich w/ Cole Slaw and Baked Potato" 
        ],
        'dinner' => [
            "200g Lean Steak, 2 cups Mashed Potatoes (w/ heavy cream), Asparagus",
            "Large Turkey Meatballs (200g) w/ Whole-Wheat Spaghetti and Olive Oil",
            "Chicken Curry (250g chicken), 3 Rotis, 1 cup Black Dal, Ghee",
            "Pork Chops (2) w/ Baked Beans and large Corn on the Cob",
            "2 Fish Fillets (Tilapia/Cod), 2 cups Brown Rice, Green Beans",
            "Chicken Stir-fry w/ 2 cups Noodles, Cashews, Olive Oil, Sesame",
            "Large bowl of Chicken Stew w/ Potatoes, Carrots, and Cream",
            "Beef and Broccoli w/ 2 cups Fried Rice" 
        ],
        'snack1' => ["Protein Shake (2 scoops)", "Handful of Nuts (Almonds/Walnuts)", "Cottage Cheese (1 cup) and Berries", "Small bowl of Ice Cream", "High-Calorie Trail Mix", "Yogurt Parfait", "Hummus w/ Pita", "Mass Gainer Shake (Small)"],
        'snack2' => ["Mass Gainer Shake", "Hummus w/ Whole-Wheat Pita Bread", "Banana w/ 2 tbsp Peanut Butter", "Leftover Chicken Slice", "Rice Pudding", "Whole Milk Glass", "Protein Bar (High Carb)", "2 Hard-boiled Eggs"],
        'snack3' => ["Casein Protein Shake", "Greek Yogurt (1 cup) w/ Granola", "Trail Mix (Nuts, Seeds, Dried Fruit)", "Peanut Butter Toast (2 slices)", "Small Bowl Cottage Cheese", "Leftover Steak slice", "Full-Fat Milk Glass", "Protein Wafers"]
    ],

    // --- VEG HIGH CALORIE / HIGH PROTEIN (for Weight Gain/Muscle Build) ---
    'veg_gain' => [
        'breakfast' => [
            "Large Tofu Scramble w/ peppers, 2 slices Whole-Wheat Toast, Ghee",
            "3 large Paneer Parathas w/ 1 cup Full-Fat Curd (Yogurt)",
            "Oats w/ 2 scoops Protein Powder, Chia Seeds, Nuts, and Berries",
            "Peanut Butter & Banana Sandwich (2), 1 glass Full-Fat Milk",
            "3 large Besan Cheela (Chickpea Pancakes) w/ Cheese",
            "Quinoa Porridge w/ Flax Seeds, Honey, and Nuts (High-Calorie)",
            "Large bowl of Muesli soaked in Full-Fat Milk and Raisins, Honey",
            "High-Protein Smoothie (Tofu, Spinach, Peanut Butter, Milk, Dates)" 
        ],
        'lunch' => [
            "200g Tofu/Paneer Curry, 1.5 cups Rice, Salad w/ Olive Oil",
            "Large serving of Chhole (Chickpeas) Bhature (2), side of Raita",
            "Vegetable Biryani w/ Soya Chunks (large portion) and Curd/Ghee",
            "Black Bean Burger (2) on bun, side of Sweet Potato Fries",
            "1.5 cups Lentil Soup (Dal), 3 Rotis, and 100g Grilled Paneer",
            "Large serving of Rajma (Kidney Beans) Curry w/ Rice",
            "Quinoa bowl w/ Roasted Vegetables, Avocado, and Feta Cheese",
            "High-Protein Veggie Lasagna (large serving) w/ Cheese" 
        ],
        'dinner' => [
            "2 Tofu Steaks, 2 cups Mashed Potatoes (with butter), Roasted Broccoli",
            "Large serving of Whole-Wheat Pasta w/ Creamy Mushroom Sauce",
            "Paneer Bhurji (200g) w/ 3 Rotis and a side of Ghee/Butter",
            "Lentil Loaf, 2 cups Roasted Root Vegetables, and Gravy",
            "Large Veggie Burger w/ all the fixings and French Fries (Cooked in Oil)",
            "Mixed Vegetable and Cashew Curry w/ 2 cups Rice",
            "High-Protein Tofu/Soya Stir-fry w/ Noodles, Nuts, and Oil",
            "2 large Potato and Cheese Cutlets w/ 3 Rotis and Salad" 
        ],
        'snack1' => ["Protein Shake (Plant-Based)", "Mixed Nuts and Cheese cubes", "Apple slices w/ Almond Butter (2 tbsp)", "Small bowl of Rice Pudding", "Avocado Toast (1 slice)", "Handful of Dates", "Tofu/Paneer Skewers", "Veggie Protein Bar"],
        'snack2' => ["Mass Gainer Shake (w/ milk)", "Yogurt Parfait w/ Granola and Fruits", "Whole-Wheat Pita w/ Hummus (Large)", "Ghee/Butter Popcorn (large)", "Trail Mix (Nuts/Seeds)", "Banana w/ Honey", "2 Tofu Cubes", "Glass of Full-Fat Milk"],
        'snack3' => ["Casein/Milk Protein", "Cottage Cheese (Paneer) Cubes", "Glass of Full-Fat Milk and 2 Rusks", "Peanut Butter Cookies (2)", "Leftover Dal/Lentil Soup", "Yogurt w/ Muesli", "Tofu Scramble (small)", "Sweet Potato slices"]
    ],

    // --- NON-VEG BALANCED/MODERATE ---
    'non_veg_balanced' => [
        'breakfast' => ["3-Egg Scramble, whole-wheat toast", "Oats w/ Berries and 1 scoop Protein Powder", "Turkey Bacon (2) and English Muffin", "Small bowl Muesli", "Hard Boiled Eggs (3)", "Yogurt and fruit", "Small Chicken Sandwich"],
        'lunch' => ["Grilled Chicken Salad w/ light dressing", "Tuna Salad on lettuce wraps", "Small Chicken Curry w/ 1.5 Rotis", "Turkey Sandwich", "Lentil Soup w/ Chicken", "Fish Fillet w/ Quinoa", "Small Beef Patty w/ Salad"],
        'dinner' => ["Baked Fish (150g), 1 cup Brown Rice, Steamed Veggies", "Lean Ground Turkey Stir-fry", "Small Steak w/ Salad", "Chicken and Veggie Skewers", "Pork Loin w/ Asparagus", "Small Tuna Pasta", "Salmon w/ Potato"],
        'snack1' => ["Yogurt", "Orange", "Hard-boiled Egg", "Apple", "Handful Almonds", "Protein Shake (1 scoop)", "Pear"],
        'snack2' => ["Small handful Almonds", "Protein Bar (low sugar)", "Banana", "Cottage Cheese", "Carrots & Hummus", "Trail Mix (small)", "Milk"],
        'snack3' => ["Cottage Cheese", "Small Protein Shake", "Berries", "Greek Yogurt", "Turkey slice", "Cheese stick", "Apple Slices"]
    ],
    
    // --- VEG BALANCED/MODERATE ---
    'veg_balanced' => [
        'breakfast' => ["Oats w/ Berries", "2 Dosa w/ Sambar", "Greek Yogurt w/ Granola", "Tofu Scramble (small)", "Whole-Wheat Poha", "Banana Smoothie", "2 Idlis w/ Chutney"],
        'lunch' => ["Lentil Soup (Dal) w/ 2 Rotis", "Large Vegetable Salad w/ Tofu", "Black Bean Salad w/ Quinoa", "Paneer Wrap", "Veggie Burger (small)", "Hummus Plate", "Aloo Palak (Spinach) w/ Rice"],
        'dinner' => ["Tofu Stir-fry w/ Brown Rice", "Chhole (Chickpeas) w/ 2 Rotis", "Vegetable and Paneer Stew", "Dal Makhani w/ Rice", "Mixed Veg Curry w/ Roti", "Bean Tacos (2)", "Quinoa Bowl"],
        'snack1' => ["Apple", "Yogurt", "Handful of Peanuts", "Orange", "Cottage Cheese", "Small Protein Shake", "Grapes"],
        'snack2' => ["Carrots & Hummus", "Protein Bar (Veg)", "Banana", "Mixed Nuts (small)", "Rice Cakes w/ Peanut Butter", "Edamame", "Small Yogurt"],
        'snack3' => ["Cottage Cheese (low fat)", "Small handful Walnuts", "Glass of Milk (low fat)", "Fruit Salad", "Trail Mix (small)", "Tofu cubes", "Rice Cake"]
    ],

    // --- NON-VEG LOW CALORIE / HIGH PROTEIN (for Weight Loss) ---
    'non_veg_loss' => [
        'breakfast' => ["3 Egg Whites Omelet, Spinach, no cheese", "Small Oats w/ water, 1 scoop protein", "2 Hard-boiled Eggs, Grapefruit", "Turkey Slice (2) w/ egg whites", "Protein Shake (Water)", "Small Cottage Cheese", "Tuna Salad (small)"],
        'lunch' => ["200g Baked Chicken Breast, large steamed vegetables", "Tuna (water-packed) on lettuce, light dressing", "Turkey Mince Lettuce Wraps", "Grilled Shrimp Salad", "Chicken Soup (clear)", "Baked Cod w/ broccoli", "Lean Steak (small) w/ green beans"],
        'dinner' => ["150g Grilled Fish, large side salad", "Chicken Breast in air-fryer, Broccoli", "Lean Steak (120g), 1 cup Green Beans", "Turkey Meatloaf (small)", "Egg White Scramble w/ Veggies", "Baked Salmon (small) w/ Asparagus", "Chicken Stir-fry (no oil, water base)"],
        'snack1' => ["Protein Shake (Water)", "1 cup Black Coffee", "Celery", "Apple Slices", "Hard Boiled Egg", "Broccoli", "Small Yogurt (0% fat)"],
        'snack2' => ["Small handful Almonds", "Watermelon slices", "Hard-boiled Egg", "Protein Bar (0 sugar)", "Cottage Cheese (0% fat)", "Chicken Jerky", "Protein Shake"],
        'snack3' => ["Casein Protein (Water)", "Greek Yogurt (0% fat)", "Chicken Jerky", "Cottage Cheese (0% fat)", "Small Protein Shake", "Cucumber", "Egg White"]
    ],

    // --- VEG LOW CALORIE / HIGH PROTEIN (for Weight Loss) ---
    'veg_loss' => [
        'breakfast' => ["Tofu Scramble, no oil, light toast (1)", "Oats w/ water and few berries", "Small bowl Greek Yogurt (0% fat)", "Protein Shake (Plant, Water)", "Cottage Cheese w/ Berries", "Egg Whites Scramble (Veg)", "Vegetable Juice"],
        'lunch' => ["Large Salad w/ Tofu/Soya Chunks, vinegar dressing", "Lentil Soup, no oil", "Steamed Soya Chunks and Cauliflower", "Quinoa Salad w/ Beans", "Baked Paneer (low-fat)", "Rajma (Kidney Bean) Soup", "Large Veggie Salad"],
        'dinner' => ["Baked Paneer (low fat, 150g), steamed broccoli", "Large bowl of Raw Vegetables and Hummus (small serving)", "Rajma (Kidney Bean) Soup (low sodium)", "Tofu Stir-fry (water base)", "Lentil Curry (low fat) w/ 1 Roti", "Steamed Tofu w/ Spinach", "Chhole (Chickpeas) (small) w/ Salad"],
        'snack1' => ["Protein Shake (Plant, Water)", "Apple slices", "Carrots", "Cucumber", "Small Edamame", "Small Yogurt (low fat)", "Handful Walnuts"],
        'snack2' => ["Edamame (small portion)", "Small bowl Berries", "Low-fat Curd", "Tofu cubes", "Rice Cake", "Protein Bar (Veg)", "Small handful Almonds"],
        'snack3' => ["Cottage Cheese (low fat)", "Small Protein Shake (Plant, Water)", "Cucumber", "Berries", "Greek Yogurt (0% fat)", "Apple slices", "Carrots"]
    ]
];

/**
 * Calculates Macros for a meal. Now returns UNIT macros and TOTAL macros.
 * * @param string $base_meal The meal name.
 * @param string $goal User goal.
 * @param string $activity User activity level.
 * @param string $meal_type Meal type (3_meals or 5_small_meals).
 * @return array The calculated macros and unit information.
 */
function calculateMacros($base_meal, $goal, $activity, $meal_type) {
    // If the meal is a placeholder 'SKIP', return zero macros immediately.
    if ($base_meal === 'SKIP') {
        return [
            'calories' => 0, 'protein' => 0, 'carbs' => 0, 'fat' => 0,
            'calories_per_unit' => 0, 'protein_per_unit' => 0, 'carbs_per_unit' => 0, 'fat_per_unit' => 0,
            'base_quantity' => 0.00, 'unit' => 'g', 'multiplier' => 0, 'health_focus' => 'none',
        ];
    }
    
    // Use the meal name and plan parameters as a seed for unique but predictable values
    $seed = crc32($base_meal . $goal . $activity . $meal_type);
    srand($seed);

    // --- BASE UNIT MACROS (Per 100g or serving) ---
    // Start with a moderate unit value (e.g., per 100g)
    $unit_cals = 150 + rand(-20, 20);
    $unit_p = 10 + rand(-2, 5);
    $unit_c = 15 + rand(-3, 8);
    $unit_f = 5 + rand(-2, 4);

    // Adjust Unit Macros based on Goal (e.g., higher protein unit for muscle build)
    if (in_array($goal, ['weight_gain', 'muscle_build'])) {
        $unit_p *= 1.2;
        $unit_f *= 1.1;
    } elseif ($goal === 'weight_loss') {
        $unit_c *= 0.8;
        $unit_f *= 0.8;
    }
    
    $multiplier = 2 + rand(0, 3); // Serving Multiplier (e.g., a serving size is 200g to 500g)

    // Adjust multiplier based on meal type for total serving size
    if ($meal_type === '3_meals') {
        $multiplier *= 1.8; // Larger total serving size for fewer meals
    } else { // 5_small_meals
        $multiplier *= 0.9; // Smaller total serving size for more meals
    }

    // Final total macro calculation (Unit Macro * Multiplier)
    $total_cals = $unit_cals * $multiplier;
    $total_p    = $unit_p * $multiplier;
    $total_c    = $unit_c * $multiplier;
    $total_f    = $unit_f * $multiplier;

    // Apply activity level variance to final total serving size (to increase total intake)
    $activity_factor = ($activity === 'very_active') ? 1.2 : (($activity === 'light') ? 0.9 : 1.0);
    $total_cals *= $activity_factor;
    $total_p    *= $activity_factor;
    $total_c    *= $activity_factor;
    $total_f    *= $activity_factor;
    
    // Determine a dummy health focus for the new column
    $health_focus = 'none';
    if ($unit_f < 3) $health_focus = 'low_fat';
    if ($unit_c < 10) $health_focus = 'low_carb';


    return [
        // Total Macros (Columns: calories, protein, carbs, fat)
        'calories' => round($total_cals),
        'protein' => round($total_p),
        'carbs' => round($total_c),
        'fat' => round($total_f),
        
        // Unit Macros (Columns: calories_per_unit, protein_per_unit, carbs_per_unit, fat_per_unit)
        'calories_per_unit' => round($unit_cals),
        'protein_per_unit' => round($unit_p, 2),
        'carbs_per_unit' => round($unit_c, 2),
        'fat_per_unit' => round($unit_f, 2),
        
        // Unit and Quantity info (Columns: base_quantity, unit, multiplier)
        'base_quantity' => 100.00, 
        'unit' => 'g', // Default unit is 'g' (grams)
        'multiplier' => round($multiplier, 2),
        'health_focus' => $health_focus,
    ];
}

/**
 * Function to generate and insert a full 7-day plan
 * @param mysqli $db The actual database connection object.
 */
function generateAndInsertPlan($db, $goal, $dietary, $activity, $meal_type, $mealData, &$totalMealsInserted) {
    // Use the 6 standard slots for structural consistency
    $all_slots = ['breakfast', 'snack1', 'lunch', 'snack2', 'dinner', 'snack3'];

    // Determine which meal pool to use based on goal and preference
    $poolKey = "{$dietary}_" . (in_array($goal, ['weight_gain', 'muscle_build']) ? 'gain' : ($goal === 'weight_loss' ? 'loss' : 'balanced'));

    // Generate 7 days of meals
    for ($day = 1; $day <= 7; $day++) {

        // Use the modulo operator (%) to cycle through the unique meals, guaranteeing variety
        $mealPool = [
            'breakfast' => $mealData[$poolKey]['breakfast'][($day - 1) % count($mealData[$poolKey]['breakfast'])],
            'snack1'    => $mealData[$poolKey]['snack1'][($day - 1) % count($mealData[$poolKey]['snack1'])],
            'lunch'     => $mealData[$poolKey]['lunch'][($day - 1) % count($mealData[$poolKey]['lunch'])],
            'snack2'    => $mealData[$poolKey]['snack2'][($day - 1) % count($mealData[$poolKey]['snack2'])],
            'dinner'    => $mealData[$poolKey]['dinner'][($day - 1) % count($mealData[$poolKey]['dinner'])],
            'snack3'    => $mealData[$poolKey]['snack3'][($day - 1) % count($mealData[$poolKey]['snack3'])],
        ];

        // Iterate over all 6 slots and decide whether to insert a real meal or a placeholder.
        foreach ($all_slots as $slot) {
            
            // Check if this is a snack slot AND the plan is a 3-meal plan
            if ($meal_type === '3_meals' && strpos($slot, 'snack') !== false) {
                // Insert a 'SKIP' placeholder row for the 3-meal plan snacks
                $meal_name = 'SKIP';
                $macros = calculateMacros($meal_name, $goal, $activity, $meal_type); // Returns all zeros
            } else {
                // Insert the actual meal for 5-meal plans or the 3 main meals for the 3-meal plan
                $meal_name = $mealPool[$slot];
                $macros = calculateMacros($meal_name, $goal, $activity, $meal_type);
            }

            // Sanitize input using the real database connection
            // --- CRITICAL FIX: USING CORRECT COLUMN NAMES ---
            $goal_safe = $db->real_escape_string($goal);
            $dietary_safe = $db->real_escape_string($dietary); // Corrected: was dietary_preference
            $activity_safe = $db->real_escape_string($activity);
            $meal_type_safe = $db->real_escape_string($meal_type); // Corrected: was meal_type_count
            $meal_time_safe = $db->real_escape_string($slot); // Corrected: was meal_slot
            $meal_text_safe = $db->real_escape_string($meal_name); // Corrected: was meal_name
            $unit_safe = $db->real_escape_string($macros['unit']); // Corrected: was unit_label
            $health_focus_safe = $db->real_escape_string($macros['health_focus']);

            $sql = "INSERT INTO diet_plans (
                goal, dietary, activity, meal_type, 
                day_number, meal_time, meal_text, 
                
                -- Total Macros
                calories, protein, carbs, fat,
                
                -- Other fields with default values (0)
                low_carb, low_glycemic, high_fiber, 
                
                -- Health Focus and Base Quantity
                health_focus, base_quantity, unit, 
                
                -- Per Unit Macros
                protein_per_unit, carbs_per_unit, fat_per_unit, calories_per_unit
            ) VALUES (
                '{$goal_safe}', '{$dietary_safe}', '{$activity_safe}', '{$meal_type_safe}',
                {$day}, '{$meal_time_safe}', '{$meal_text_safe}',
                
                -- Total Macros (The final portion size)
                {$macros['calories']}, {$macros['protein']}, {$macros['carbs']}, {$macros['fat']},
                
                -- Dummy/Default values for boolean fields (assuming 0 is default)
                0, 0, 0, 
                
                -- Health Focus and Unit Info
                '{$health_focus_safe}', {$macros['base_quantity']}, '{$unit_safe}', 
                
                -- Per Unit Macros (The density of the food, independent of serving size)
                {$macros['protein_per_unit']}, {$macros['carbs_per_unit']}, {$macros['fat_per_unit']}, {$macros['calories_per_unit']}
            )";

            $result = $db->query($sql);
            
            if ($result === false) {
                 // Log error if insertion fails
                 echo "<p style='color: red;'>SQL Error on Plan: {$goal_safe}-{$dietary_safe}-{$activity_safe}-{$meal_type_safe}, Day: {$day}, Slot: {$slot} - " . $db->error . "</p>";
            } else {
                $totalMealsInserted++;
            }
        }
    }
}

// --- DATABASE SETUP AND TRUNCATION ---
echo "<h2>Generating Diet Plan Data...</h2>";

echo "Clearing existing data from 'diet_plans' table...<br>";
// Execute the TRUNCATE command on the actual connection
if ($connection->query("TRUNCATE TABLE diet_plans") === false) {
    echo "<p style='color: red;'>TRUNCATE TABLE FAILED: " . $connection->error . "</p>";
}


// --- EXECUTE GENERATION LOOP ---
foreach ($goals as $goal) {
    foreach ($dietary as $pref) {
        foreach ($activity as $level) {
            foreach ($meal_types as $type) {
                // Pass the real connection object
                generateAndInsertPlan($connection, $goal, $pref, $level, $type, $mealData, $totalMealsInserted);
            }
        }
    }
}

// --- SUMMARY OUTPUT ---
$totalExpectedPlans = count($goals) * count($dietary) * count($activity) * count($meal_types);
$expectedTotalMeals = $totalExpectedPlans * 7 * 6; // 48 plans * 7 days * 6 slots = 2016

echo "
<style>
    body { font-family: Arial, sans-serif; background-color: #f4f7f6; padding: 20px; color: #333; }
    .card { background: #fff; padding: 20px; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); max-width: 600px; margin: 20px auto; }
    h2 { color: #0066cc; border-bottom: 2px solid #0066cc; padding-bottom: 10px; margin-top: 0; }
    strong { color: #d9534f; font-size: 1.1em; }
    .success { color: #5cb85c; font-weight: bold; }
    ul { list-style-type: disc; margin-left: 20px; padding-left: 0; }
    li { margin-bottom: 5px; }
    .highlight { background-color: #e0f7fa; padding: 8px; border-radius: 6px; }
</style>
<div class='card'>
    <h2>Personalized Plan Data Generation Complete</h2>
    <p class='success'>✅ Data Generation and Insertion Complete!</p>
    <p>The script has been updated to use the **correct column names** as per your database schema, specifically addressing the <code>Unknown column 'dietary_preference'</code> error. It also correctly handles the insertion of **unit-based macros** and **total macros**.</p>
    
    <h3>Schema Alignment Changes</h3>
    <ul>
        <li><code>dietary_preference</code> is now **`dietary`**</li>
        <li><code>meal_type_count</code> is now **`meal_type`**</li>
        <li><code>meal_slot</code> is now **`meal_time`**</li>
        <li><code>meal_name</code> is now **`meal_text`**</li>
        <li><code>unit_label</code> is now **`unit`**</li>
        <li><code>base_qty</code> is now **`base_quantity`**</li>
        <li>New unit macro columns (`*_per_unit`) are calculated and inserted.</li>
    </ul>

    <h3>Insertion Totals</h3>
    <ul>
        <li>Total unique 7-Day Plans Generated: <strong>{$totalExpectedPlans}</strong></li>
        <li>Expected total individual meals inserted: <strong>{$expectedTotalMeals}</strong></li>
        <li>Actual total individual meals inserted: <strong>{$totalMealsInserted}</strong></li>
    </ul>
</div>
";
?>
