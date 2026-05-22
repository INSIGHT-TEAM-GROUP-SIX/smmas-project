<?php $current = basename($_SERVER['PHP_SELF']); ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head><style>
.navbar{background:#2c3e50;padding:0 20px;position:fixed;top:0;left:0;right:0;z-index:1000}
.nav-container{max-width:1200px;margin:0 auto}
.nav-brand{float:left;padding:15px 0;font-size:24px;font-weight:bold}
.nav-brand a{color:white;text-decoration:none}
.nav-menu{float:right;list-style:none;margin:0;padding:0}
.nav-menu li{float:left;margin-left:5px}
.nav-menu li a{display:block;padding:2px 20px;color:white;text-decoration:none}
.nav-menu li a:hover,.nav-menu li a.active{background:#34495e}
.user-info{float:right;margin-left:20px;padding:15px 0;color:white}
.logout-btn{background:#e74c3c;padding:5px 10px;border-radius:5px;color:white;text-decoration:none;margin-left:10px}
.clearfix{clear:both}
</style></head>
<body>

<div class="navbar"><div class="nav-container">
<div class="nav-brand"><a href="dashboard.php">🏥 SMMAS</a></div>
<div class="user-info">👤 <?php echo $_SESSION['username']; ?> (<?php echo $_SESSION['role']; ?>) <a href="logout.php" class="logout-btn">Logout</a></div>
<ul class="nav-menu">
<li><a href="dashboard.php" <?php echo ($current=='dashboard.php')?'class="active"':''; ?>>Dashboard</a></li>
<li><a href="medicine.php" <?php echo ($current=='medicine.php')?'class="active"':''; ?>>Medicine</a></li>
<li><a href="batch.php" <?php echo ($current=='batch.php')?'class="active"':''; ?>>Batch</a></li>
<li><a href="patient.php" <?php echo ($current=='patient.php')?'class="active"':''; ?>>Patient</a></li>
<li><a href="transaction.php" <?php echo ($current=='transaction.php')?'class="active"':''; ?>>Transaction</a></li>
<li><a href="alert.php" <?php echo ($current=='alert.php')?'class="active"':''; ?>>Alerts</a></li>
<li><a href="reports.php" <?php echo ($current=='reports.php')?'class="active"':''; ?>>?? Reports</a></li>
<?php if(isAdmin()): ?><li><a href="users.php" <?php echo ($current=='users.php')?'class="active"':''; ?>>👥 Users</a></li><?php endif; ?>
</ul><div class="clearfix"></div>
</div></div><div style="height:60px"></div></body></html>