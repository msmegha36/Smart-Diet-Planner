<?php
session_start();

// ✅ Unset only nutritionist session
if (isset($_SESSION['nutritionist_id'])) {
    unset($_SESSION['nutritionist_id']);
}

// Optionally: if you want to destroy full session when only nutritionist is logged in
// session_destroy();

// Redirect back to nutritionist login
header("Location: login.php");
exit();
?>
