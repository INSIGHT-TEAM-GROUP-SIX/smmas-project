<?php
session_start();

// If user is already logged in, send them to dashboard
if(isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
} 
// Otherwise, send them to login page
else {
    header("Location: login.php");
    exit();
}
?>
