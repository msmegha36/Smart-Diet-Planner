<?php
session_start();
if (!isset($_SESSION['admin_id'])) {
    header("Location: login.php");
    exit();
}

unset($_SESSION['admin_id']);
unset($_SESSION['admin_name']);
unset($_SESSION['role']);
header("Location: login.php");
exit();
?>
