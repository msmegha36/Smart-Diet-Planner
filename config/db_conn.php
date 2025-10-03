<?php
$connection = new mysqli('localhost', 'root', '', 'DietPlan');
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}
?>