<?php
require_once 'config/database.php';

$conn = getConnection();

// Get all non-admin users
$users = $conn->query("SELECT user_id, username FROM users WHERE role != 'Admin'")->fetchAll();

foreach($users as $user) {
    $new_password = 'KMC@2026';
    $hash = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
    $stmt->execute([$hash, $user['user_id']]);
    
    echo "? Password for {$user['username']} reset to: $new_password<br>";
}

echo "<br><a href='users.php'>Back to Users</a>";
?>