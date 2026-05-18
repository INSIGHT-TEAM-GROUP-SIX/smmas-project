<?php
require_once 'config/database.php';

$conn = getConnection();

// Delete existing admin
$conn->exec("DELETE FROM users WHERE username = 'admin'");

// Create new admin with plain text password
$stmt = $conn->prepare("INSERT INTO users (user_id, username, password_hash, full_name, email, role, is_active) VALUES (?, ?, ?, ?, ?, ?, 1)");
$stmt->execute(['USR-0001', 'admin', 'Admin@123', 'System Administrator', 'admin@smmas.com', 'Admin']);

echo "<h2 style='color:green'>? Admin user created/updated!</h2>";
echo "<p><strong>Login Credentials:</strong></p>";
echo "<ul>";
echo "<li>Username: <strong>admin</strong></li>";
echo "<li>Password: <strong>Admin@123</strong></li>";
echo "</ul>";
echo "<p><a href='login.php'>Go to Login ?</a></p>";
?>