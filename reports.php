<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
checkAuth();

$conn = getConnection();
$report_type = isset($_GET['type']) ? $_GET['type'] : 'dashboard';
$from_date = isset($_GET['from_date']) ? $_GET['from_date'] : date('Y-m-01');
$to_date = isset($_GET['to_date']) ? $_GET['to_date'] : date('Y-m-d');
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Reports - SMMAS</title>
<link rel="stylesheet" href="css/style.css" />
<style type="text/css">
.report-header { background: linear-gradient(135deg, #0A4F6E, #0E6B40); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px; }
.report-tabs { margin-bottom: 20px; }
.report-tab { display: inline-block; padding: 10px 20px; background: #ecf0f1; text-decoration: none; color: #333; margin-right: 5px; border-radius: 5px; }
.report-tab.active { background: #0A4F6E; color: white; }
.filter-bar { background: #f5f5f5; padding: 15px; margin-bottom: 20px; border-radius: 5px; }
.filter-form select, .filter-form input { padding: 8px; margin: 0 10px; }
.summary-box { display: inline-block; background: white; padding: 15px; margin: 10px; border-radius: 5px; min-width: 150px; text-align: center; box-shadow: 0 2px 5px rgba(0,0,0,0.1); }
.summary-value { font-size: 24px; font-weight: bold; color: #0A4F6E; }
.btn-print { background: #34495e; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; margin-right: 10px; }
.btn-print:hover { background: #2c3e50; }
.btn-export { background: #27ae60; color: white; padding: 10px 20px; border: none; border-radius: 5px; cursor: pointer; }
.report-actions { margin-bottom: 20px; text-align: right; }
@media print {
    .navbar, .report-tabs, .filter-bar, .report-actions, .btn-print, .btn-export, .no-print {
        display: none !important;
    }
    body { background: white; padding: 0; margin: 0; }
    .container { margin: 0; padding: 0; }
    .report-header { background: #0A4F6E; -webkit-print-color-adjust: exact; print-color-adjust: exact; }
}
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>

<div class="container">
    <div class="report-header">
        <h1 style="margin: 0;">📊 SMMAS Reports</h1>
        <p style="margin: 5px 0 0;">Generated: <?php echo date('l, F d, Y h:i A'); ?></p>
        <p style="margin: 0;">Period: <?php echo date('d M Y', strtotime($from_date)); ?> - <?php echo date('d M Y', strtotime($to_date)); ?></p>
    </div>
    
    <!-- Report Tabs -->
    <div class="report-tabs no-print">
        <a href="?type=dashboard" class="report-tab <?php echo $report_type == 'dashboard' ? 'active' : ''; ?>">📈 Dashboard</a>
        <a href="?type=stock" class="report-tab <?php echo $report_type == 'stock' ? 'active' : ''; ?>">📦 Stock Report</a>
        <a href="?type=expiry" class="report-tab <?php echo $report_type == 'expiry' ? 'active' : ''; ?>">⚠️ Expiry Report</a>
        <a href="?type=transactions" class="report-tab <?php echo $report_type == 'transactions' ? 'active' : ''; ?>">💰 Transaction Report</a>
        <a href="?type=patients" class="report-tab <?php echo $report_type == 'patients' ? 'active' : ''; ?>">👥 Patient Report</a>
        <a href="?type=alerts" class="report-tab <?php echo $report_type == 'alerts' ? 'active' : ''; ?>">🔔 Alert History</a>
    </div>
    
    <!-- Date Filter -->
    <div class="filter-bar no-print">
        <form method="get" class="filter-form">
            <input type="hidden" name="type" value="<?php echo $report_type; ?>">
            <label>📅 From: <input type="date" name="from_date" value="<?php echo $from_date; ?>"></label>
            <label>To: <input type="date" name="to_date" value="<?php echo $to_date; ?>"></label>
            <button type="submit" class="btn-primary">Apply Filter</button>
        </form>
    </div>
    
    <!-- Print/Export Buttons -->
    <div class="report-actions no-print">
        <button onclick="window.print()" class="btn-print">🖨️ Print / Save as PDF</button>
        <button onclick="exportToCSV()" class="btn-export">📎 Export to CSV</button>
    </div>
    
    <!-- Report Content -->
    <?php
    if($report_type == 'stock') {
        // STOCK REPORT
        $stmt = $conn->query("
            SELECT m.medicine_name, m.category, m.strength, m.reorder_level, m.unit_price,
                   COALESCE(SUM(b.qty_remaining), 0) as current_stock
            FROM medicine m
            LEFT JOIN batch b ON m.medicine_id = b.medicine_id AND b.batch_status = 'Active'
            GROUP BY m.medicine_id, m.medicine_name, m.category, m.strength, m.reorder_level, m.unit_price
            ORDER BY current_stock ASC
        ");
        $stock_data = $stmt->fetchAll();
        $total_value = 0;
        foreach($stock_data as $item) {
            $total_value += $item['current_stock'] * $item['unit_price'];
        }
        ?>
        <h2>📦 Stock Status Report</h2>
        <p><strong>Total Inventory Value:</strong> UGX <?php echo number_format($total_value); ?></p>
        <table class="data-table" id="reportTable">
            <thead>
                <tr><th>Medicine</th><th>Category</th><th>Strength</th><th>Current Stock</th><th>Reorder Level</th><th>Unit Price</th><th>Total Value</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach($stock_data as $item): ?>
                <tr>
                    <td><?php echo $item['medicine_name']; ?></td>
                    <td><?php echo $item['category']; ?></td>
                    <td><?php echo $item['strength']; ?></td>
                    <td><?php echo $item['current_stock']; ?></td>
                    <td><?php echo $item['reorder_level']; ?></td>
                    <td>UGX <?php echo number_format($item['unit_price']); ?></td>
                    <td>UGX <?php echo number_format($item['current_stock'] * $item['unit_price']); ?></td>
                    <td><?php echo ($item['current_stock'] <= $item['reorder_level']) ? '⚠️ Low Stock' : '✅ In Stock'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    elseif($report_type == 'expiry') {
        // EXPIRY REPORT
        $stmt = $conn->query("
            SELECT m.medicine_name, b.batch_number, b.supplier_name, b.expiry_date, b.qty_remaining,
                   DATEDIFF(b.expiry_date, CURDATE()) as days_left
            FROM batch b
            JOIN medicine m ON b.medicine_id = m.medicine_id
            WHERE b.batch_status = 'Active'
            ORDER BY b.expiry_date ASC
        ");
        $expiry_data = $stmt->fetchAll();
        ?>
        <h2>⚠️ Expiry Tracking Report</h2>
        <table class="data-table" id="reportTable">
            <thead>
                <tr><th>Medicine</th><th>Batch Number</th><th>Supplier</th><th>Expiry Date</th><th>Days Left</th><th>Quantity</th><th>Status</th></tr>
            </thead>
            <tbody>
                <?php foreach($expiry_data as $item): ?>
                <tr style="<?php echo ($item['days_left'] <= 30) ? 'background:#fff3cd;' : ''; ?>">
                    <td><?php echo $item['medicine_name']; ?></td>
                    <td><?php echo $item['batch_number']; ?></td>
                    <td><?php echo $item['supplier_name']; ?></td>
                    <td><?php echo date('d M Y', strtotime($item['expiry_date'])); ?></td>
                    <td><?php echo $item['days_left']; ?> days</td>
                    <td><?php echo $item['qty_remaining']; ?></td>
                    <td><?php echo ($item['days_left'] <= 0) ? '🔴 EXPIRED' : (($item['days_left'] <= 30) ? '🟡 Expiring Soon' : '🟢 Valid'); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    elseif($report_type == 'transactions') {
        // TRANSACTION REPORT
        $stmt = $conn->prepare("
            SELECT DATE(transaction_date) as date, COUNT(*) as count, 
                   SUM(quantity) as items, SUM(quantity * unit_price) as total
            FROM `transaction`
            WHERE transaction_type = 'Dispense' AND DATE(transaction_date) BETWEEN ? AND ?
            GROUP BY DATE(transaction_date)
            ORDER BY date DESC
        ");
        $stmt->execute([$from_date, $to_date]);
        $sales_data = $stmt->fetchAll();
        $total_revenue = 0;
        foreach($sales_data as $sale) { $total_revenue += $sale['total']; }
        ?>
        <h2>💰 Transaction Report</h2>
        <p><strong>Period Total:</strong> UGX <?php echo number_format($total_revenue); ?></p>
        <table class="data-table" id="reportTable">
            <thead>
                <tr><th>Date</th><th>Transactions</th><th>Items Sold</th><th>Revenue (UGX)</th></tr>
            </thead>
            <tbody>
                <?php foreach($sales_data as $sale): ?>
                <tr>
                    <td><?php echo date('d M Y', strtotime($sale['date'])); ?></td>
                    <td><?php echo $sale['count']; ?></td>
                    <td><?php echo $sale['items']; ?></td>
                    <td>UGX <?php echo number_format($sale['total']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    elseif($report_type == 'patients') {
        // PATIENT REPORT
        $stmt = $conn->prepare("
            SELECT patient_id, full_name, patient_type, date_registered, phone_number, gender
            FROM patient
            WHERE DATE(date_registered) BETWEEN ? AND ?
            ORDER BY date_registered DESC
        ");
        $stmt->execute([$from_date, $to_date]);
        $patient_data = $stmt->fetchAll();
        ?>
        <h2>👥 Patient Registration Report</h2>
        <p><strong>New Patients:</strong> <?php echo count($patient_data); ?></p>
        <table class="data-table" id="reportTable">
            <thead>
                <tr><th>Patient ID</th><th>Full Name</th><th>Gender</th><th>Type</th><th>Registration Date</th><th>Phone</th></tr>
            </thead>
            <tbody>
                <?php foreach($patient_data as $patient): ?>
                <tr>
                    <td><?php echo $patient['patient_id']; ?></td>
                    <td><?php echo $patient['full_name']; ?></td>
                    <td><?php echo $patient['gender']; ?></td>
                    <td><?php echo $patient['patient_type']; ?></td>
                    <td><?php echo date('d M Y', strtotime($patient['date_registered'])); ?></td>
                    <td><?php echo $patient['phone_number'] ?: 'N/A'; ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    elseif($report_type == 'alerts') {
        // ALERT REPORT
        $stmt = $conn->prepare("
            SELECT a.*, m.medicine_name
            FROM alert a
            JOIN medicine m ON a.medicine_id = m.medicine_id
            WHERE DATE(a.date_generated) BETWEEN ? AND ?
            ORDER BY a.date_generated DESC
            LIMIT 100
        ");
        $stmt->execute([$from_date, $to_date]);
        $alert_data = $stmt->fetchAll();
        ?>
        <h2>🔔 Alert History Report</h2>
        <p><strong>Total Alerts:</strong> <?php echo count($alert_data); ?></p>
        <table class="data-table" id="reportTable">
            <thead>
                <tr><th>Date</th><th>Medicine</th><th>Alert Type</th><th>Severity</th><th>Status</th><th>Message</th></tr>
            </thead>
            <tbody>
                <?php foreach($alert_data as $alert): ?>
                <tr style="<?php echo ($alert['severity_level'] == 'Critical') ? 'background:#fee;' : ''; ?>">
                    <td><?php echo date('d M Y H:i', strtotime($alert['date_generated'])); ?></td>
                    <td><?php echo $alert['medicine_name']; ?></td>
                    <td><?php echo $alert['alert_type']; ?></td>
                    <td><?php echo $alert['severity_level']; ?></td>
                    <td><?php echo $alert['alert_status']; ?></td>
                    <td><?php echo substr($alert['alert_message'], 0, 60); ?>...</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
    else {
        // DASHBOARD - Include the dashboard report
        include 'reports/dashboard_report.php';
    }
    ?>
    
 <!-- Auto-Generated Insights -->
    <?php
    // Gather insight data fresh (independent of which report tab is active)
    $insights = [];

    // 1. Expiry insights
    $exp = $conn->query("SELECT COUNT(*) as cnt FROM Batch WHERE batch_status='Active' AND DATEDIFF(expiry_date, CURDATE()) BETWEEN 0 AND 7")->fetch();
    if($exp['cnt'] > 0)
        $insights[] = ['critical', '🔥', "URGENT: {$exp['cnt']} batch(es) expire within 7 days — dispense immediately (FIFO)."];

    $exp30 = $conn->query("SELECT COUNT(*) as cnt FROM Batch WHERE batch_status='Active' AND DATEDIFF(expiry_date, CURDATE()) BETWEEN 0 AND 30")->fetch();
    if($exp30['cnt'] > $exp['cnt'])
        $insights[] = ['warning', '⚠️', ($exp30['cnt'] - $exp['cnt']) . " additional batch(es) expire within 30 days — monitor closely."];

    $expired = $conn->query("SELECT COUNT(*) as cnt FROM Batch WHERE batch_status='Active' AND expiry_date < CURDATE()")->fetch();
    if($expired['cnt'] > 0)
        $insights[] = ['critical', '🗑️', "{$expired['cnt']} batch(es) have already expired and should be removed from stock."];

    // 2. Stock insights
    $low = $conn->query("
        SELECT COUNT(*) as cnt FROM (
            SELECT m.medicine_id FROM Medicine m
            LEFT JOIN Batch b ON m.medicine_id = b.medicine_id AND b.batch_status='Active'
            GROUP BY m.medicine_id, m.reorder_level
            HAVING COALESCE(SUM(b.qty_remaining),0) <= m.reorder_level
            AND COALESCE(SUM(b.qty_remaining),0) > 0
        ) t
    ")->fetch();
    if($low['cnt'] > 0)
        $insights[] = ['warning', '📉', "{$low['cnt']} medicine(s) are below reorder level — consider restocking soon."];

    $out = $conn->query("
        SELECT COUNT(*) as cnt FROM (
            SELECT m.medicine_id FROM Medicine m
            LEFT JOIN Batch b ON m.medicine_id = b.medicine_id AND b.batch_status='Active'
            GROUP BY m.medicine_id
            HAVING COALESCE(SUM(b.qty_remaining),0) = 0
        ) t
    ")->fetch();
    if($out['cnt'] > 0)
        $insights[] = ['critical', '🚨', "{$out['cnt']} medicine(s) are completely out of stock."];

    // 3. Top selling medicine
$top = $conn->query("
        SELECT m.medicine_name, SUM(t.quantity) as total
        FROM `Transaction` t
        JOIN Medicine m ON t.medicine_id = m.medicine_id
        WHERE t.transaction_type = 'Dispense'
        GROUP BY m.medicine_id ORDER BY total DESC LIMIT 1
    ")->fetch();
    if($top)
        $insights[] = ['info', '🏆', "Top selling medicine is <strong>{$top['medicine_name']}</strong> with {$top['total']} units dispensed overall."];

    // 4. Today's activity
    $today = $conn->query("
        SELECT COUNT(*) as cnt, COALESCE(SUM(quantity * unit_price),0) as rev
        FROM `Transaction`
        WHERE DATE(transaction_date) = CURDATE() AND transaction_type='Dispense'
    ")->fetch();
    if($today['cnt'] > 0)
        $insights[] = ['info', '📅', "Today: {$today['cnt']} transaction(s) processed, earning UGX " . number_format($today['rev']) . "."];
    else
        $insights[] = ['info', '📅', "No transactions recorded today yet."];

    // 5. Active alerts
    $active_al = $conn->query("SELECT COUNT(*) as cnt FROM Alert WHERE alert_status='Active'")->fetch();
    if($active_al['cnt'] > 0)
        $insights[] = ['warning', '🔔', "{$active_al['cnt']} alert(s) are currently active and unresolved."];

    $critical_al = $conn->query("SELECT COUNT(*) as cnt FROM Alert WHERE alert_status='Active' AND severity_level='Critical'")->fetch();
    if($critical_al['cnt'] > 0)
        $insights[] = ['critical', '🚨', "{$critical_al['cnt']} of those alert(s) are CRITICAL severity — immediate action required."];

    // 6. Patient insight
    $new_pts = $conn->query("SELECT COUNT(*) as cnt FROM Patient WHERE DATE(date_registered) = CURDATE()")->fetch();
    if($new_pts['cnt'] > 0)
        $insights[] = ['info', '👤', "{$new_pts['cnt']} new patient(s) registered today."];

    // Color map
    $colors = [
        'critical' => ['bg' => '#fff0f0', 'border' => '#e74c3c', 'label' => '#e74c3c'],
        'warning'  => ['bg' => '#fffbf0', 'border' => '#f39c12', 'label' => '#f39c12'],
        'info'     => ['bg' => '#f0f7ff', 'border' => '#3498db', 'label' => '#3498db'],
    ];
    ?>

    <div class="no-print" style="margin-top:30px;">
        <div style="background:linear-gradient(135deg,#1a1a2e,#16213e);color:white;padding:16px 20px;border-radius:10px 10px 0 0;display:flex;align-items:center;gap:10px;">
            <span style="font-size:22px;">🤖</span>
            <div>
                <strong style="font-size:16px;">Auto-Generated Insights</strong>
                <div style="font-size:11px;opacity:0.7;margin-top:2px;">Generated at <?php echo date('h:i A'); ?> · Based on current system data</div>
            </div>
            <span style="margin-left:auto;background:#27ae60;padding:3px 10px;border-radius:20px;font-size:11px;"><?php echo count($insights); ?> insight(s)</span>
        </div>
        <div style="border:1px solid #ddd;border-top:none;border-radius:0 0 10px 10px;overflow:hidden;">
            <?php if(empty($insights)): ?>
                <div style="padding:20px;text-align:center;color:#27ae60;font-weight:bold;">✅ All systems normal — no issues detected.</div>
            <?php else: ?>
                <?php foreach($insights as $i => $ins):
                    $c = $colors[$ins[0]];
                ?>
                <div style="display:flex;align-items:flex-start;gap:12px;padding:13px 18px;background:<?php echo $c['bg']; ?>;border-left:4px solid <?php echo $c['border']; ?>;<?php echo $i < count($insights)-1 ? 'border-bottom:1px solid #eee;' : ''; ?>">
                    <span style="font-size:20px;line-height:1.4;"><?php echo $ins[1]; ?></span>
                    <span style="color:#333;line-height:1.6;"><?php echo $ins[2]; ?></span>
                </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- Footer -->
    <div style="margin-top:15px;padding:10px;background:#e7f3ff;border-radius:5px;text-align:center;font-size:11px;color:#666;">
        <strong>SMMAS - Smart Medicine Monitoring & Alert System</strong><br>
        Kyambogo Medical Centre | Generated by: <?php echo $_SESSION['full_name']; ?> | Date: <?php echo date('Y-m-d H:i:s'); ?>
    </div>
</div>

<script type="text/javascript">
function exportToCSV() {
    var table = document.getElementById('reportTable');
    if(!table) {
        alert('No table found to export');
        return;
    }
    var rows = table.querySelectorAll('tr');
    var csv = [];
    for(var i = 0; i < rows.length; i++) {
        var row = [], cols = rows[i].querySelectorAll('td, th');
        for(var j = 0; j < cols.length; j++) {
            row.push('"' + cols[j].innerText.replace(/"/g, '""') + '"');
        }
        csv.push(row.join(','));
    }
    var blob = new Blob([csv.join('\n')], {type: 'text/csv'});
    var a = document.createElement('a');
    var url = URL.createObjectURL(blob);
    a.href = url;
    a.download = 'smmas_<?php echo $report_type; ?>_report_<?php echo date('Y-m-d'); ?>.csv';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    URL.revokeObjectURL(url);
}
</script>
</body>
</html>