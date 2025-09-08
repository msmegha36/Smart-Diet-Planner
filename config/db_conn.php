<?php
$connection = new mysqli('localhost', 'root', '', 'dietplan');
if ($connection->connect_error) {
    die("Connection failed: " . $connection->connect_error);
}
?>