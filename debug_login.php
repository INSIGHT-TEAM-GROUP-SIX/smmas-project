<?php
require_once 'config/database.php';

$conn = getConnection();
$message = '';

// Test 1: Check if users table exists
$tables = $conn->query("SHOW TABLES LIKE 'users'");
if($tables->rowCount() == 0) {
    die("? USERS TABLE DOES NOT EXIST! Run the SQL script first.");
} else {
    echo "? Users table exists<br><br>";
}

// Test 2: Get all users
$users = $conn->query("SELECT user_id, username, password_hash, role FROM users")->fetchAll();

echo "<h3>Users in Database:</h3>";
if(count($users) == 0) {
    echo "? NO USERS FOUND!<br>";
} else {
    echo "<table border='1' cellpadding='5'>";
    echo "<tr><th>User ID</th><th>Username</th><th>Password Hash</th><th>Role</th></tr>";
    foreach($users as $u) {
        echo "<tr>";
        echo "<td>" . $u['user_id'] . "</td>";
        echo "<td>" . $u['username'] . "</td>";
        echo "<td style='font-family:monospace;font-size:11px;word-break:break-all;max-width:300px'>" . substr($u['password_hash'], 0, 50) . "...</td>";
        echo "<td>" . $u['role'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
}

// Test 3: Check if admin exists and test password
$admin = $conn->query("SELECT * FROM users WHERE username = 'admin'")->fetch();

if($admin) {
    echo "<h3>Admin User Test:</h3>";
    echo "Username: " . $admin['username'] . "<br>";
    
    // Test various passwords
    $test_passwords = ['Admin@123', 'admin123', 'password123', 'admin', 'Admin123'];
    
    echo "<br>Testing passwords:<br>";
    foreach($test_passwords as $test) {
        if(password_verify($test, $admin['password_hash'])) {
            echo "? <span style='color:green'>Password '$test' is CORRECT!</span><br>";
        } else {
            echo "? <span style='color:red'>Password '$test' is INCORRECT</span><br>";
        }
    }
    
    // Also test direct comparison
    echo "<br>Direct comparison test:<br>";
    if($admin['password_hash'] == 'Admin@123') {
        echo "? Password hash is stored as plain text 'Admin@123'<br>";
    } else {
        echo "? Password hash is NOT plain text. It's: " . substr($admin['password_hash'], 0, 30) . "...<br>";
    }
    
} else {
    echo "<h3 style='color:red'>? ADMIN USER NOT FOUND!</h3>";
    echo "<a href='fix_admin.php'>Click here to create admin user</a>";
}
?>

<br><br>
<hr>
<h3>Quick Fix Options:</h3>
<ul>
    <li><a href="fix_admin.php">Click here to FIX admin password</a></li>
    <li><a href="simple_login.php">Use simple login (no password hash)</a></li>
</ul>