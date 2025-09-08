<?php
$connection = new mysqli('localhost', 'root', 'password', 'DietPlan');
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}
?>