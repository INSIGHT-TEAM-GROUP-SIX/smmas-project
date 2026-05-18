<?php
// Expiry Report - Working Version

$stmt = $conn->query("
    SELECT 
        b.batch_id,
        m.medicine_id,
        m.medicine_name,
        b.batch_number,
        b.supplier_name,
        b.date_received,
        b.expiry_date,
        b.qty_received,
        b.qty_remaining,
        DATEDIFF(b.expiry_date, CURDATE()) as days_remaining
    FROM Batch b
    JOIN Medicine m ON b.medicine_id = m.medicine_id
    WHERE b.batch_status != 'Depleted'
    ORDER BY b.expiry_date ASC
");

$expiry_data = $stmt->fetchAll();

$expired_count = 0;
$expiring_soon_count = 0;

foreach($expiry_data as $item) {
    if($item['days_remaining'] < 0) $expired_count++;
    if($item['days_remaining'] >= 0 && $item['days_remaining'] <= 30) $expiring_soon_count++;
}
?>

<h2>?? Medicine Expiry Report</h2>

<div style="margin-bottom: 20px;">
    <div class="summary-box">
        <div class="summary-value" style="color: #e74c3c;"><?php echo $expired_count; ?></div>
        <div class="summary-label">Expired Batches</div>
    </div>
    <div class="summary-box">
        <div class="summary-value" style="color: #f39c12;"><?php echo $expiring_soon_count; ?></div>
        <div class="summary-label">Expiring Within 30 Days</div>
    </div>
    <div class="summary-box">
        <div class="summary-value"><?php echo count($expiry_data); ?></div>
        <div class="summary-label">Total Active Batches</div>
    </div>
</div>

<table class="report-table">
    <thead>
        <tr>
            <th>Medicine Name</th>
            <th>Batch Number</th>
            <th>Supplier</th>
            <th>Date Received</th>
            <th>Expiry Date</th>
            <th>Days Left</th>
            <th>Quantity Remaining</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($expiry_data as $item): ?>
        <tr style="<?php echo ($item['days_remaining'] < 0) ? 'background:#fee;' : (($item['days_remaining'] <= 30) ? 'background:#fff8e7;' : ''); ?>">
            <td><?php echo $item['medicine_name']; ?></td>
            <td><?php echo $item['batch_number']; ?></td>
            <td><?php echo $item['supplier_name']; ?></td>
            <td><?php echo date('d M Y', strtotime($item['date_received'])); ?></td>
            <td><?php echo date('d M Y', strtotime($item['expiry_date'])); ?></td>
            <td>
                <?php if($item['days_remaining'] < 0): ?>
                    <span style="color:#e74c3c;">Expired</span>
                <?php else: ?>
                    <?php echo $item['days_remaining']; ?> days
                <?php endif; ?>
            </td>
            <td><?php echo $item['qty_remaining']; ?> units</td>
            <td>
                <?php if($item['days_remaining'] < 0): ?>
                    <span style="color:#e74c3c;">?? EXPIRED</span>
                <?php elseif($item['days_remaining'] <= 7): ?>
                    <span style="color:#e74c3c;">?? Critical (<?php echo $item['days_remaining']; ?>d)</span>
                <?php elseif($item['days_remaining'] <= 30): ?>
                    <span style="color:#f39c12;">?? Expiring Soon</span>
                <?php else: ?>
                    <span style="color:#2ecc71;">?? Valid</span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if(count($expiry_data) == 0): ?>
        <tr><td colspan="8" style="text-align:center;">No batch data available</td></tr>
        <?php endif; ?>
    </tbody>
</table>

<?php if($expired_count > 0): ?>
<div style="margin-top: 20px; padding: 15px; background: #fee; border-left: 4px solid #e74c3c;">
    <strong>?? Recommendation:</strong> Dispose of <?php echo $expired_count; ?> expired batch(es) immediately and update records.
</div>
<?php endif; ?>