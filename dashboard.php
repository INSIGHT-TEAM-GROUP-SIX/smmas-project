<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
checkAuth();
$conn = getConnection();
$stats = [];
$queries = [
    'total_medicines' => "SELECT COUNT(*) as c FROM medicine",
    'total_patients' => "SELECT COUNT(*) as c FROM patient",
    'active_alerts' => "SELECT COUNT(*) as c FROM alert WHERE alert_status = 'Active'",
    'critical_alerts' => "SELECT COUNT(*) as c FROM alert WHERE severity_level = 'Critical' AND alert_status = 'Active'"
];
foreach($queries as $k => $sql) { $stats[$k] = $conn->query($sql)->fetch()['c']; }
$recent = $conn->query("SELECT t.*, m.medicine_name, p.full_name FROM `transaction` t JOIN medicine m ON t.medicine_id = m.medicine_id LEFT JOIN patient p ON t.patient_id = p.patient_id ORDER BY t.transaction_date DESC LIMIT 10")->fetchAll();
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head><meta http-equiv="Content-Type" content="text/html; charset=utf-8" /><title>Dashboard</title><link rel="stylesheet" href="css/style.css" /></head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container">
<div class="dashboard-header"><h1>Welcome, <?php echo $_SESSION['full_name']; ?></h1><p>Role: <?php echo $_SESSION['role']; ?></p></div>
<div class="stats-grid"><div class="stat-card"><div class="stat-value"><?php echo $stats['total_medicines']; ?></div><div class="stat-label">Medicines</div></div><div class="stat-card"><div class="stat-value"><?php echo $stats['total_patients']; ?></div><div class="stat-label">Patients</div></div><div class="stat-card alert-card"><div class="stat-value"><?php echo $stats['active_alerts']; ?></div><div class="stat-label">Active Alerts</div></div><div class="stat-card warning-card"><div class="stat-value"><?php echo $stats['critical_alerts']; ?></div><div class="stat-label">Critical</div></div></div>
<div class="data-table-container"><h2>Recent Transactions</h2><table class="data-table"><thead><tr><th>ID</th><th>Medicine</th><th>Patient</th><th>Type</th><th>Qty</th><th>Date</th></tr></thead><tbody><?php foreach($recent as $r): ?><tr><td><?php echo $r['transaction_id']; ?></td><td><?php echo $r['medicine_name']; ?></td><td><?php echo $r['full_name'] ?? 'N/A'; ?></td><td><?php echo $r['transaction_type']; ?></td><td><?php echo $r['quantity']; ?></td><td><?php echo date('d M Y H:i', strtotime($r['transaction_date'])); ?></td></tr><?php endforeach; ?></tbody></table></div>
</div></body></html>