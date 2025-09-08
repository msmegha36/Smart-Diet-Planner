<?php
session_start();
include(__DIR__ . '/../config/db_conn.php'); 
header('Content-Type: application/json');

if(!isset($_SESSION['user_id'])){
    echo json_encode(['success'=>false,'msg'=>'Not logged in']);
    exit();
}

$user_id = $_SESSION['user_id'];
$goal      = $_GET['goal'] ?? '';
$dietary   = $_GET['dietary'] ?? '';
$activity  = $_GET['activity'] ?? '';
$meal_type = $_GET['meal_type'] ?? '';

if(empty($goal) || empty($dietary) || empty($activity) || empty($meal_type)){
    echo json_encode(['success'=>false,'msg'=>'Missing parameters']);
    exit();
}

$plan = [];

// Loop for 7 days
for($day=1; $day<=7; $day++){
    $stmt = $connection->prepare("
        SELECT * FROM diet_plans 
        WHERE goal=? AND dietary=? AND activity=? AND meal_type=? AND day_number=?
    ");
    $stmt->bind_param("ssssi", $goal, $dietary, $activity, $meal_type, $day);
    $stmt->execute();
    $res = $stmt->get_result();

    $meals = [];
    while($row = $res->fetch_assoc()){
        // Avoid duplicate insertion
        $meals[] = [
            'meal_time'=>$row['meal_time'],
            'meal_text'=>$row['meal_text'],
            'protein'=>$row['protein'],
            'carbs'=>$row['carbs'],
            'fat'=>$row['fat'],
            'calories'=>$row['calories']
        ];

        $check = $connection->prepare("SELECT id FROM user_diet_plans WHERE user_id=? AND day_number=? AND meal_time=?");
        $check->bind_param("iis", $user_id, $day, $row['meal_time']);
        $check->execute();
        $check->store_result();
        if($check->num_rows == 0){
            $insert = $connection->prepare("
                INSERT INTO user_diet_plans 
                (user_id, day_number, meal_time, meal_text, protein, carbs, fat, calories) 
                VALUES (?,?,?,?,?,?,?,?)
            ");
            $insert->bind_param("iissiiii", $user_id, $day, $row['meal_time'], $row['meal_text'], $row['protein'], $row['carbs'], $row['fat'], $row['calories']);
            $insert->execute();
        }
    }

    $plan[] = ['day_number'=>$day, 'meals'=>$meals];
}

echo json_encode(['success'=>true, 'plan'=>$plan]);
