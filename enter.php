<?php
session_start();
$_SESSION['user_id'] = 'USR-0001';
$_SESSION['username'] = 'admin';
$_SESSION['full_name'] = 'System Administrator';
$_SESSION['role'] = 'Admin';
header('Location: dashboard.php');
exit();
?>