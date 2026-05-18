<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
checkAuth();

// ONLY ADMIN can access this page
if($_SESSION['role'] !== 'Admin') {
    header('HTTP/1.0 403 Forbidden');
    die('<h2>Access Denied</h2><p>Only Administrators can access this page.</p><a href="dashboard.php">Return to Dashboard</a>');
}

$conn = getConnection();
$message = '';
$error = '';

// Handle user deletion
if(isset($_GET['delete'])) {
    $user_id = $_GET['delete'];
    
    if($user_id === $_SESSION['user_id']) {
        $error = '❌ You cannot delete your own account!';
    } else {
        $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ? AND role != 'Admin'");
        $stmt->execute([$user_id]);
        if($stmt->rowCount() > 0) {
            $message = '<div class="success">✅ User deleted successfully!</div>';
        } else {
            $error = '❌ Cannot delete admin user!';
        }
    }
}

// Handle user addition - SIMPLE COUNT METHOD (Always works)
if($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['add_user'])) {
    // Get total count of users and add 1 - THIS ALWAYS WORKS
    $count_stmt = $conn->query("SELECT COUNT(*) as total FROM users");
    $total = $count_stmt->fetch();
    $next_num = $total['total'] + 1;
    $user_id = 'USR-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
    
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);
    $full_name = trim($_POST['full_name']);
    $email = trim($_POST['email']);
    $role = $_POST['role'];
    
    // Check if username exists
    $check = $conn->prepare("SELECT user_id FROM users WHERE username = ?");
    $check->execute([$username]);
    if($check->rowCount() > 0) {
        $error = '❌ Username already exists!';
    } else {
        $stmt = $conn->prepare("INSERT INTO users (user_id, username, password_hash, full_name, email, role, is_active, created_at) VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
        if($stmt->execute([$user_id, $username, $hashed_password, $full_name, $email, $role])) {
            $message = '<div class="success">✅ User added successfully!<br>User ID: <strong>' . $user_id . '</strong><br>Username: ' . $username . '<br>Password: ' . $password . '</div>';
        } else {
            $error = '❌ Error adding user!';
        }
    }
}

// Handle password reset
if(isset($_POST['reset_password'])) {
    $user_id = $_POST['user_id'];
    $new_password = $_POST['new_password'];
    $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
    
    $stmt = $conn->prepare("UPDATE users SET password_hash = ? WHERE user_id = ?");
    if($stmt->execute([$hashed_password, $user_id])) {
        $message = '<div class="success">✅ Password reset successfully!<br>New Password: <strong>' . $new_password . '</strong></div>';
    } else {
        $error = '❌ Error resetting password!';
    }
}

// Handle status toggle
if(isset($_GET['toggle_status'])) {
    $user_id = $_GET['toggle_status'];
    if($user_id !== $_SESSION['user_id']) {
        $stmt = $conn->prepare("UPDATE users SET is_active = NOT is_active WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $message = '<div class="success">✅ User status updated!</div>';
    } else {
        $error = '❌ You cannot deactivate your own account!';
    }
}

// Get all users
$stmt = $conn->query("
    SELECT 
        user_id, 
        username, 
        full_name, 
        email, 
        role, 
        is_active, 
        created_at, 
        last_login,
        DATE_FORMAT(last_login, '%d %b %Y %h:%i %p') as last_login_formatted,
        CASE 
            WHEN last_login IS NULL THEN 'Never'
            WHEN DATE(last_login) = CURDATE() THEN 'Today'
            WHEN DATE(last_login) = DATE_SUB(CURDATE(), INTERVAL 1 DAY) THEN 'Yesterday'
            ELSE CONCAT(DATEDIFF(CURDATE(), DATE(last_login)), ' days ago')
        END as login_status
    FROM users 
    ORDER BY user_id ASC
");
$users = $stmt->fetchAll();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>User Management - SMMAS</title>
<link rel="stylesheet" href="css/style.css" />
<style>
.admin-header {
    background: #e74c3c;
    color: white;
    padding: 10px 15px;
    border-radius: 5px;
    margin-bottom: 20px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
}
.user-form {
    background: white;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    border: 1px solid #ddd;
}
.form-row {
    margin-bottom: 15px;
}
.form-row label {
    display: inline-block;
    width: 150px;
    font-weight: bold;
}
.form-row input, .form-row select {
    width: 250px;
    padding: 8px;
    border: 1px solid #ddd;
    border-radius: 4px;
}
.status-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
}
.status-active { background: #2ecc71; color: white; }
.status-inactive { background: #e74c3c; color: white; }
.role-badge {
    display: inline-block;
    padding: 4px 10px;
    border-radius: 20px;
    font-size: 11px;
    font-weight: bold;
}
.role-Admin { background: #e74c3c; color: white; }
.role-Pharmacist { background: #3498db; color: white; }
.role-Doctor { background: #2ecc71; color: white; }
.role-Nurse { background: #9b59b6; color: white; }
.role-Receptionist { background: #f39c12; color: white; }
.role-Cashier { background: #1abc9c; color: white; }
.action-btn {
    padding: 4px 8px;
    margin: 2px;
    border: none;
    border-radius: 3px;
    cursor: pointer;
    font-size: 11px;
    text-decoration: none;
    display: inline-block;
}
.btn-reset { background: #f39c12; color: white; }
.btn-delete { background: #e74c3c; color: white; }
.btn-toggle { background: #3498db; color: white; }
.login-status {
    font-size: 11px;
    padding: 2px 6px;
    border-radius: 3px;
    display: inline-block;
}
.login-recent { background: #d4edda; color: #155724; }
.login-never { background: #f8d7da; color: #721c24; }
.login-old { background: #fff3cd; color: #856404; }
.last-login-time {
    font-size: 10px;
    color: #666;
    margin-top: 3px;
}
.modal {
    display: none;
    position: fixed;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: white;
    padding: 25px;
    border-radius: 10px;
    box-shadow: 0 0 100px rgba(0,0,0,0.5);
    z-index: 1000;
    width: 350px;
}
.overlay {
    display: none;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(0,0,0,0.5);
    z-index: 999;
}
.search-box {
    margin-bottom: 20px;
    text-align: right;
}
.search-box input {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 5px;
    width: 250px;
}
.data-table-container {
    overflow-x: auto;
}
.data-table {
    width: 100%;
    border-collapse: collapse;
    min-width: 900px;
}
.data-table th, .data-table td {
    padding: 10px 8px;
    text-align: left;
    vertical-align: middle;
}
.data-table th {
    background: #34495e;
    color: white;
    font-weight: bold;
}
.data-table td {
    border-bottom: 1px solid #ddd;
}
.data-table tr:hover {
    background: #f5f5f5;
}
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="admin-header">
        <div>🔒 <strong>ADMIN AREA</strong> - User Management</div>
        <div>Logged in as: <?php echo $_SESSION['full_name']; ?> (<?php echo $_SESSION['role']; ?>)</div>
    </div>
    
    <h1>👥 System Users Management</h1>
    <?php echo $message; ?>
    <?php echo $error ? "<div class='error'>$error</div>" : ""; ?>
    
    <!-- Add User Form -->
    <div class="user-form">
        <h2>➕ Add New User</h2>
        <form method="post">
            <div class="form-row">
                <label>User ID:</label>
                <input type="text" value="Auto-generated (USR-0001, USR-0002...)" disabled style="background:#f0f0f0; width: 300px;" />
                <small style="margin-left: 10px;">Sequential IDs based on user count</small>
            </div>
            <div class="form-row">
                <label>Username *:</label>
                <input type="text" name="username" required />
            </div>
            <div class="form-row">
                <label>Password *:</label>
                <input type="text" name="password" value="KMC@2026" required />
            </div>
            <div class="form-row">
                <label>Full Name *:</label>
                <input type="text" name="full_name" required />
            </div>
            <div class="form-row">
                <label>Email *:</label>
                <input type="email" name="email" required />
            </div>
            <div class="form-row">
                <label>Role *:</label>
                <select name="role">
                    <option value="Pharmacist">Pharmacist</option>
                    <option value="Doctor">Doctor</option>
                    <option value="Nurse">Nurse</option>
                    <option value="Receptionist">Receptionist</option>
                    <option value="Cashier">Cashier</option>
                </select>
            </div>
            <div class="form-row">
                <input type="submit" name="add_user" value="➕ Create User" class="btn-primary" />
            </div>
        </form>
    </div>
    
    <!-- Current Stats -->
    <div style="background: #e8f4f8; padding: 10px 15px; border-radius: 5px; margin-bottom: 20px;">
        <strong>📊 Current Statistics:</strong> 
        Total Users: <?php echo count($users); ?> | 
        Next User ID will be: <strong>USR-<?php echo str_pad(count($users) + 1, 4, '0', STR_PAD_LEFT); ?></strong>
    </div>
    
    <!-- Search Box -->
    <div class="search-box">
        <input type="text" id="searchInput" placeholder="🔍 Search by username, name or email..." onkeyup="searchUser()" />
    </div>
    
    <!-- Users Table -->
    <div class="data-table-container">
        <h2>📋 Registered Users</h2>
        <table class="data-table" id="userTable">
            <thead>
                <tr>
                    <th>User ID</th>
                    <th>Username</th>
                    <th>Full Name</th>
                    <th>Email</th>
                    <th>Role</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($users as $user): 
                    $status_class = $user['is_active'] ? 'status-active' : 'status-inactive';
                    $status_text = $user['is_active'] ? 'Active' : 'Inactive';
                    
                    if($user['login_status'] == 'Never') {
                        $login_class = 'login-never';
                        $login_display = '❌ Never';
                        $time_display = '';
                    } elseif($user['login_status'] == 'Today') {
                        $login_class = 'login-recent';
                        $login_display = '✅ Today';
                        $time_display = '<div class="last-login-time">🕐 ' . $user['last_login_formatted'] . '</div>';
                    } elseif($user['login_status'] == 'Yesterday') {
                        $login_class = 'login-recent';
                        $login_display = '✅ Yesterday';
                        $time_display = '<div class="last-login-time">🕐 ' . $user['last_login_formatted'] . '</div>';
                    } else {
                        $login_class = 'login-old';
                        $login_display = '⚠️ ' . $user['login_status'];
                        $time_display = '<div class="last-login-time">🕐 ' . $user['last_login_formatted'] . '</div>';
                    }
                ?>
                <tr>
                    <td><strong><?php echo $user['user_id']; ?></strong> <?php if($user['role']=='Admin') echo '👑'; ?></td>
                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                    <td><?php echo htmlspecialchars($user['full_name']); ?></td>
                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                    <td><span class="role-badge role-<?php echo $user['role']; ?>"><?php echo $user['role']; ?></span></td>
                    <td><span class="status-badge <?php echo $status_class; ?>">🟢 <?php echo $status_text; ?></span></td>
                    <td style="min-width: 140px;">
                        <span class="login-status <?php echo $login_class; ?>"><?php echo $login_display; ?></span>
                        <?php echo $time_display; ?>
                     </small></td>
                    <td><?php echo date('d M Y', strtotime($user['created_at'])); ?></small></td>
                    <td style="white-space: nowrap;">
                        <button class="action-btn btn-reset" onclick="openResetModal('<?php echo $user['user_id']; ?>', '<?php echo htmlspecialchars($user['username']); ?>')">🔑 Reset</button>
                        <?php if($user['role'] != 'Admin'): ?>
                        <a href="?toggle_status=<?php echo $user['user_id']; ?>" class="action-btn btn-toggle" onclick="return confirm('Toggle user status?')">🔄 <?php echo $user['is_active'] ? 'Disable' : 'Enable'; ?></a>
                        <a href="?delete=<?php echo $user['user_id']; ?>" class="action-btn btn-delete" onclick="return confirm('Delete user <?php echo $user['username']; ?>?')">🗑️ Delete</a>
                        <?php else: ?>
                        <span style="color:#999; font-size:11px;">🔒 Protected</span>
                        <?php endif; ?>
                     </small></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    
    <div style="margin-top: 20px; padding: 15px; background: #e7f3ff; border-radius: 5px;">
        <h3>✅ How User IDs Work:</h3>
        <ul>
            <li>User IDs are based on <strong>TOTAL USER COUNT</strong> (not the last ID number)</li>
            <li>If you have 3 users → Next ID is <strong>USR-0004</strong></li>
            <li>If you have 10 users → Next ID is <strong>USR-0011</strong></li>
            <li>This works even if you delete users in between!</li>
            <li>Admin accounts are protected with <strong>👑</strong> icon</li>
        </ul>
    </div>
</div>

<!-- Reset Password Modal -->
<div class="overlay" id="modalOverlay" onclick="closeResetModal()"></div>
<div class="modal" id="resetModal">
    <h3>🔑 Reset User Password</h3>
    <form method="post">
        <input type="hidden" name="user_id" id="reset_user_id">
        <div class="form-group">
            <label>Username:</label>
            <input type="text" id="reset_username" readonly style="background:#f5f5f5; width: 100%; padding: 8px;">
        </div>
        <div class="form-group">
            <label>New Password:</label>
            <input type="text" name="new_password" id="new_password" required style="width: 100%; padding: 8px; margin: 10px 0;">
            <small>Suggest: Temp@123 or User@2025</small>
        </div>
        <div class="form-group">
            <label>Confirm Password:</label>
            <input type="text" id="confirm_password" required style="width: 100%; padding: 8px; margin: 10px 0;">
            <span id="match_msg"></span>
        </div>
        <button type="submit" name="reset_password" class="btn-primary" style="margin-top: 10px;">✅ Reset Password</button>
        <button type="button" onclick="closeResetModal()" style="margin-top: 10px; margin-left: 10px;">Cancel</button>
    </form>
</div>

<script>
function openResetModal(userId, username) {
    document.getElementById('reset_user_id').value = userId;
    document.getElementById('reset_username').value = username;
    document.getElementById('resetModal').style.display = 'block';
    document.getElementById('modalOverlay').style.display = 'block';
    document.getElementById('new_password').value = '';
    document.getElementById('confirm_password').value = '';
    document.getElementById('match_msg').innerHTML = '';
}

function closeResetModal() {
    document.getElementById('resetModal').style.display = 'none';
    document.getElementById('modalOverlay').style.display = 'none';
}

function checkMatch() {
    var pwd = document.getElementById('new_password').value;
    var confirm = document.getElementById('confirm_password').value;
    var msg = document.getElementById('match_msg');
    if(pwd == confirm && pwd != '') {
        msg.innerHTML = '✅ Passwords match';
        msg.style.color = 'green';
    } else if(pwd != '') {
        msg.innerHTML = '❌ Passwords do not match';
        msg.style.color = 'red';
    } else {
        msg.innerHTML = '';
    }
}

function searchUser() {
    var input = document.getElementById('searchInput');
    var filter = input.value.toUpperCase();
    var table = document.getElementById('userTable');
    var tr = table.getElementsByTagName('tr');
    for(var i = 1; i < tr.length; i++) {
        var tdUsername = tr[i].getElementsByTagName('td')[1];
        var tdName = tr[i].getElementsByTagName('td')[2];
        var tdEmail = tr[i].getElementsByTagName('td')[3];
        if(tdUsername || tdName || tdEmail) {
            var username = tdUsername ? (tdUsername.textContent || tdUsername.innerText) : '';
            var name = tdName ? (tdName.textContent || tdName.innerText) : '';
            var email = tdEmail ? (tdEmail.textContent || tdEmail.innerText) : '';
            if(username.toUpperCase().indexOf(filter) > -1 || 
               name.toUpperCase().indexOf(filter) > -1 ||
               email.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = '';
            } else {
                tr[i].style.display = 'none';
            }
        }
    }
}

document.getElementById('confirm_password')?.addEventListener('keyup', checkMatch);
document.getElementById('new_password')?.addEventListener('keyup', checkMatch);
</script>
</body>
</html>