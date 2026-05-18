<?php
session_start();

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Simple hardcoded check
    if($username == 'admin' && $password == 'Admin@123') {
        $_SESSION['user_id'] = 'USR-0001';
        $_SESSION['username'] = 'admin';
        $_SESSION['full_name'] = 'System Administrator';
        $_SESSION['role'] = 'Admin';
        header('Location: dashboard.php');
        exit();
    } else {
        $error = "Invalid login. Use admin / Admin@123";
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>SMMAS Login</title>
<style>
body{font-family:Arial;background:linear-gradient(135deg,#0A4F6E,#0E6B40);height:100vh;display:flex;justify-content:center;align-items:center}
.login-box{background:white;padding:40px;border-radius:15px;width:350px;text-align:center}
input{width:100%;padding:12px;margin:10px 0;border:1px solid #ddd;border-radius:8px}
button{width:100%;padding:12px;background:#0A4F6E;color:white;border:none;border-radius:8px;cursor:pointer}
.error{color:#e74c3c;margin-bottom:15px}
</style>
</head>
<body>
<div class="login-box">
<h2>?? SMMAS</h2>
<p>Kyambogo Medical Centre</p>
<?php if(isset($error)) echo "<div class='error'>$error</div>"; ?>
<form method="post">
<input type="text" name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<button type="submit">Login</button>
</form>
<p style="margin-top:15px;font-size:12px">admin / Admin@123</p>
</div>
</body>
</html>