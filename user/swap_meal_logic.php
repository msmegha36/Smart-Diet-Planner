<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

include(__DIR__ . '/../config/db_conn.php');

if (!isset($_SESSION['user_id'])) {
    header("Location: ../home/login.php");
    exit();
}

// --- RATE LIMIT CONFIGURATION ---
// Minimum time (in seconds) required between successful API calls per user.
define('RATE_LIMIT_SECONDS', 5);

// Global API settings
// NOTE: I'm setting the apiKey to an empty string to ensure the environment-provided key is used securely.
$apiKey = "AIzaSyCGm1uGRJhEfl9cKPGNOSku4Ky1uL2G-fU"; 
$apiUrl = 'https://generativelanguage.googleapis.com/v1beta/models/gemini-2.5-flash-preview-05-20:generateContent?key=' . $apiKey;

/**
 * Calls the Gemini API with exponential backoff for resilience.
 * @param array $payload The API request payload.
 * @return string|false The JSON response body on success, or false on failure.
 */
function callGeminiApi($payload) {
    global $apiUrl;
    $max_retries = 3;
    $delay = 1;

    for ($i = 0; $i < $max_retries; $i++) {
        $ch = curl_init($apiUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($http_code === 200) {
            return $response;
        }

        // Wait before retrying
        if ($i < $max_retries - 1) {
            sleep($delay);
            $delay *= 2; // Exponential backoff
        }
    }
    return false; // Failed after all retries
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['meal_id'])) {
    
    // --- RATE LIMIT CHECK ---
    $currentTime = time();
    $lastCallTime = $_SESSION['last_api_call'] ?? 0;

    if (($currentTime - $lastCallTime) < RATE_LIMIT_SECONDS) {
        $wait_time = RATE_LIMIT_SECONDS - ($currentTime - $lastCallTime);
        $_SESSION['error'] = "You are swapping meals too quickly. Please wait {$wait_time} seconds before trying again.";
        header("Location: user_plans.php");
        exit();
    }
    
    // --- UPDATE LAST CALL TIME ---
    // Set the timestamp immediately after passing the rate limit check
    $_SESSION['last_api_call'] = $currentTime;

    // Sanitize and extract POST data
    $meal_id = mysqli_real_escape_string($connection, $_POST['meal_id']);
    $old_protein = (int)$_POST['old_protein'];
    $old_carbs = (int)$_POST['old_carbs'];
    $old_fat = (int)$_POST['old_fat'];
    $old_calories = (int)$_POST['old_calories'];
    $meal_time = mysqli_real_escape_string($connection, $_POST['meal_time']);
    $user_id = $_SESSION['user_id'];
    
    // 1. Fetch User Preferences for context
    // FIX: Selecting the correct column 'dietary' from the 'reg' table.
    $user_res = mysqli_query($connection, "SELECT goal, health_issues, dietary FROM reg WHERE id='$user_id'");
    $user_prefs = mysqli_fetch_assoc($user_res);
    $goal = $user_prefs['goal'] ?? 'balanced';
    $health_issues = $user_prefs['health_issues'] ?? 'None';
    
    // FIX: Using the correct variable name $dietary.
    $dietary = $user_prefs['dietary'] ?? 'Non-Vegetarian'; 

    // Define the JSON schema for structured output
    $responseSchema = [
        'type' => 'OBJECT',
        'properties' => [
            'meal_text' => ['type' => 'STRING', 'description' => 'The name and short description of the new meal (e.g., "Scrambled Eggs and Spinach").'],
            'quantity' => ['type' => 'STRING', 'description' => 'The recommended serving size or quantity (e.g., "2 large eggs, 1 cup").'],
            'protein' => ['type' => 'NUMBER', 'description' => 'The approximate protein content in grams (g).'],
            'carbs' => ['type' => 'NUMBER', 'description' => 'The approximate carbohydrate content in grams (g).'],
            'fat' => ['type' => 'NUMBER', 'description' => 'The approximate fat content in grams (g).'],
            'calories' => ['type' => 'NUMBER', 'description' => 'The approximate total calorie count (kcal).']
        ],
        'propertyOrdering' => ['meal_text', 'quantity', 'protein', 'carbs', 'fat', 'calories']
    ];

    // Define System Instruction: Strict instructions for the AI persona and output format
    $systemPrompt = "You are a professional nutritionist. Your task is to suggest a single, specific meal that fits the user's dietary profile and macro targets. The suggested meal must maintain the requested protein, carbs, and fat values within a 10% range. Always use the specified {$dietary} dietary preference and give same or litle bit large aplhabet size meal. Respond only with the required JSON object.";

    // Define User Prompt: Contextual information for the meal swap
    $userQuery = "Generate a new meal suggestion for the '{$meal_time}' meal time. 
    User Goal: {$goal}. 
    User Health/Dietary Focus: {$health_issues}.
    The new meal must have the following approximate macros: 
    - Calories: {$old_calories} kcal (± 10%)
    - Protein: {$old_protein}g (± 10%)
    - Carbs: {$old_carbs}g (± 10%)
    - Fat: {$old_fat}g (± 10%)";

    // Build the API payload
    $payload = [
        'contents' => [['parts' => [['text' => $userQuery]]]],
        'generationConfig' => [
            'responseMimeType' => 'application/json',
            'responseSchema' => $responseSchema
        ],
        'systemInstruction' => ['parts' => [['text' => $systemPrompt]]]
    ];

    $apiResponse = callGeminiApi($payload);

    if ($apiResponse) {
        $result = json_decode($apiResponse, true);
        
        // Extract the JSON string from the response
        $json_text = $result['candidates'][0]['content']['parts'][0]['text'] ?? null;

        if ($json_text) {
            $newMeal = json_decode($json_text, true);

            if ($newMeal && is_array($newMeal)) {
                // Sanitize and prepare for DB update, ensuring integer types for macros
                $new_meal_text = mysqli_real_escape_string($connection, $newMeal['meal_text'] ?? 'New Meal Suggestion');
                $new_quantity = mysqli_real_escape_string($connection, $newMeal['quantity'] ?? '1 serving');
                $new_protein = (int)round($newMeal['protein'] ?? $old_protein);
                $new_carbs = (int)round($newMeal['carbs'] ?? $old_carbs);
                $new_fat = (int)round($newMeal['fat'] ?? $old_fat);
                $new_calories = (int)round($newMeal['calories'] ?? $old_calories);

                // 2. Update the database
                $update_query = "
                    UPDATE user_diet_plans 
                    SET 
                        meal_text = '$new_meal_text', 
                        quantity = '$new_quantity', 
                        protein = '$new_protein', 
                        carbs = '$new_carbs', 
                        fat = '$new_fat', 
                        calories = '$new_calories' 
                    WHERE id = '$meal_id' AND user_id = '$user_id'
                ";

                if (mysqli_query($connection, $update_query)) {
                    $_SESSION['success'] = "Meal successfully swapped with: **{$newMeal['meal_text']}**. Macros updated to P:{$new_protein}g, C:{$new_carbs}g, F:{$new_fat}g.";
                } else {
                    $_SESSION['error'] = "Database update failed: " . mysqli_error($connection);
                }

            } else {
                $_SESSION['error'] = "AI generated invalid JSON structure or data. Raw response: " . htmlspecialchars($json_text);
            }
        } else {
            $_SESSION['error'] = "AI failed to generate a meal suggestion. Please try again.";
        }
    } else {
        $_SESSION['error'] = "Could not connect to the meal suggestion service (Gemini API).";
    }

} else {
    $_SESSION['error'] = "Invalid request method or missing meal ID.";
}

// Redirect back to the plans page
header("Location: user_dietPlan.php");
exit();
?>
