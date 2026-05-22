<?php
require_once 'config/database.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    
    try {
        $conn = getConnection();
        $stmt = $conn->prepare("SELECT * FROM users WHERE username = ? AND is_active = 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user) {
            // Check if password matches (works for both plain text and hashed)
            $password_valid = false;
            
            // Check plain text
            if($password == $user['password_hash']) {
                $password_valid = true;
            }
            // Check hashed
            elseif(password_verify($password, $user['password_hash'])) {
                $password_valid = true;
            }
            
            if($password_valid) {
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['full_name'] = $user['full_name'];
                $_SESSION['role'] = $user['role'];
                
                $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
                $update->execute([$user['user_id']]);
                
                header('Location: dashboard.php');
                exit();
            } else {
                $error = 'Invalid password';
            }
        } else {
            $error = 'Username not found';
        }
    } catch(PDOException $e) {
        $error = 'Database error: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html>
<head><title>SMMAS Login</title>
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Arial;background:linear-gradient(135deg,#0A4F6E,#0E6B40);height:100vh;display:flex;justify-content:center;align-items:center}
.login-box{background:white;padding:40px;border-radius:15px;width:380px;text-align:center}
h2{color:#0A4F6E;margin-bottom:10px}
input{width:100%;padding:12px;margin:10px 0;border:1px solid #ddd;border-radius:8px}
button{width:100%;padding:12px;background:#0A4F6E;color:white;border:none;border-radius:8px;cursor:pointer}
.error{background:#f8d7da;color:#721c24;padding:12px;border-radius:8px;margin-bottom:20px}
</style>
</head>
<body>
<div class="login-box">
<h2>?? SMMAS</h2>
<p>Smart Medicine Monitoring &amp; Alert System </p>
<p>Kyambogo Medical Centre</p>
<?php if($error) echo "<div class='error'>$error</div>"; ?>
<form method="post">
<input type="text" name="username" placeholder="Username" required>
<input type="password" name="password" placeholder="Password" required>
<button type="submit">Login into SMMAS </button>
</form>
<p style="margin-top:15px;font-size:12px">Login in using credentials provided</p>
<p style="margin-top:15px;font-size:12px"> by the system Adminstrator <br>
demo username : admin / Admin@123 <br>
non-admins username : eli@KMMAS / KMC@2026</p>
</div>
</body>
</html>