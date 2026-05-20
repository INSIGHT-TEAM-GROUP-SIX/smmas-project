<?php
// Dashboard Report - Fixed Version

// Get key metrics - Simple working queries
$stmt = $conn->query("SELECT COUNT(*) as count FROM medicine");
$total_medicines = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM patient");
$total_patients = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM `transaction`");
$total_transactions = $stmt->fetch()['count'];

$stmt = $conn->query("SELECT COALESCE(SUM(quantity * unit_price), 0) as total FROM `transaction` WHERE transaction_type = 'Dispense'");
$total_revenue = $stmt->fetch()['total'];

// Fixed Low Stock Query - Simpler version that works
$stmt = $conn->query("
    SELECT m.medicine_id, m.reorder_level, COALESCE(SUM(b.qty_remaining), 0) as current_stock
    FROM medicine m
    LEFT JOIN Batch b ON m.medicine_id = b.medicine_id AND b.batch_status = 'Active'
    GROUP BY m.medicine_id, m.reorder_level
");
$all_medicines = $stmt->fetchAll();
$low_stock = 0;
foreach($all_medicines as $med) {
    if($med['current_stock'] <= $med['reorder_level']) {
        $low_stock++;
    }
}

// Fixed Expiring Soon Query
$stmt = $conn->query("
    SELECT COUNT(*) as count 
    FROM batch 
    WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)
    AND batch_status = 'Active'
");
$expiring_soon = $stmt->fetch()['count'];

// Top selling medicines
$stmt = $conn->query("
    SELECT m.medicine_name, SUM(t.quantity) as total_sold
    FROM `Transaction` t
    JOIN Medicine m ON t.medicine_id = m.medicine_id
    WHERE t.transaction_type = 'Dispense'
    GROUP BY m.medicine_id, m.medicine_name
    ORDER BY total_sold DESC
    LIMIT 5
");
$top_medicines = $stmt->fetchAll();

// Today's transactions
$stmt = $conn->query("
    SELECT COUNT(*) as count, COALESCE(SUM(quantity * unit_price), 0) as total
    FROM `transaction` 
    WHERE DATE(transaction_date) = CURDATE()
    AND transaction_type = 'Dispense'
");
$today_stats = $stmt->fetch();

// Recent activity
$stmt = $conn->query("
    SELECT 'Transaction' as type, transaction_id as id, transaction_date as date 
    FROM `transaction` 
    UNION ALL
    SELECT 'Alert' as type, alert_id as id, date_generated as date 
    FROM alert 
    ORDER BY date DESC 
    LIMIT 10
");
$recent_activity = $stmt->fetchAll();
?>

<!-- Dashboard Content -->
<div style="background: linear-gradient(135deg, #0A4F6E, #0E6B40); color: white; padding: 20px; border-radius: 10px; margin-bottom: 20px;">
    <h2 style="margin: 0; color: white;">?? Executive Dashboard</h2>
    <p style="margin: 5px 0 0;">Key Performance Indicators</p>
</div>

<div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin-bottom: 20px;">
    <div style="background: #e8f4f8; padding: 15px; border-radius: 8px; text-align: center;">
        <div style="font-size: 28px; font-weight: bold; color: #0A4F6E;"><?php echo $total_medicines; ?></div>
        <div style="color: #666;">Total Medicines</div>
    </div>
    <div style="background: #e8f4f8; padding: 15px; border-radius: 8px; text-align: center;">
        <div style="font-size: 28px; font-weight: bold; color: #0A4F6E;"><?php echo $total_patients; ?></div>
        <div style="color: #666;">Total Patients</div>
    </div>
    <div style="background: #e8f4f8; padding: 15px; border-radius: 8px; text-align: center;">
        <div style="font-size: 28px; font-weight: bold; color: #0A4F6E;"><?php echo $total_transactions; ?></div>
        <div style="color: #666;">Transactions</div>
    </div>
    <div style="background: #e8f4f8; padding: 15px; border-radius: 8px; text-align: center;">
        <div style="font-size: 28px; font-weight: bold; color: #27ae60;">UGX <?php echo number_format($total_revenue); ?></div>
        <div style="color: #666;">Total Revenue</div>
    </div>
</div>

<div style="display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin-bottom: 20px;">
    <div style="background: #fff3cd; padding: 15px; border-radius: 8px; text-align: center;">
        <div style="font-size: 32px; color: #f39c12;">??</div>
        <div style="font-size: 24px; font-weight: bold;"><?php echo $low_stock; ?></div>
        <div>Low Stock Items</div>
    </div>
    <div style="background: #f8d7da; padding: 15px; border-radius: 8px; text-align: center;">
        <div style="font-size: 32px;">??</div>
        <div style="font-size: 24px; font-weight: bold; color: #e74c3c;"><?php echo $expiring_soon; ?></div>
        <div>Expiring Within 30 Days</div>
    </div>
    <div style="background: #d4edda; padding: 15px; border-radius: 8px; text-align: center;">
        <div style="font-size: 32px;">??</div>
        <div style="font-size: 20px; font-weight: bold;">UGX <?php echo number_format($today_stats['total']); ?></div>
        <div>Today's Sales (<?php echo $today_stats['count']; ?> transactions)</div>
    </div>
</div>

<h3>?? Top 5 Selling Medicines</h3>
<table class="report-table" style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
    <thead>
        <tr style="background: #34495e; color: white;">
            <th style="padding: 10px; text-align: left;">Medicine Name</th>
            <th style="padding: 10px; text-align: left;">Units Sold</th>
        </tr>
    </thead>
    <tbody>
        <?php if(count($top_medicines) > 0): ?>
            <?php foreach($top_medicines as $med): ?>
            <tr style="border-bottom: 1px solid #ddd;">
                <td style="padding: 8px;"><?php echo $med['medicine_name']; ?></td>
                <td style="padding: 8px;"><?php echo $med['total_sold']; ?> units</td>
            </tr>
            <?php endforeach; ?>
        <?php else: ?>
            <tr><td colspan="2" style="padding: 20px; text-align: center;">No sales data available</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<h3>?? Recent Activity</h3>
<table class="report-table" style="width: 100%; border-collapse: collapse;">
    <thead>
        <tr style="background: #34495e; color: white;">
            <th style="padding: 10px; text-align: left;">Type</th>
            <th style="padding: 10px; text-align: left;">ID</th>
            <th style="padding: 10px; text-align: left;">Date & Time</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($recent_activity as $activity): ?>
        <tr style="border-bottom: 1px solid #ddd;">
            <td style="padding: 8px;"><?php echo $activity['type']; ?></td>
            <td style="padding: 8px;"><?php echo $activity['id']; ?></td>
            <td style="padding: 8px;"><?php echo date('d M Y H:i', strtotime($activity['date'])); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>