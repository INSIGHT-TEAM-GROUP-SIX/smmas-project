<?php
// Alerts Report
$stmt = $conn->prepare("
    SELECT 
        a.*,
        m.medicine_name,
        CASE 
            WHEN a.alert_status = 'Active' THEN 'Active'
            WHEN a.alert_status = 'Acknowledged' THEN 'Acknowledged'
            ELSE 'Resolved'
        END as status_display
    FROM alert a
    JOIN medicine m ON a.medicine_id = m.medicine_id
    WHERE DATE(a.date_generated) BETWEEN ? AND ?
    ORDER BY a.date_generated DESC
");

$stmt->execute([$from_date, $to_date]);
$alerts_data = $stmt->fetchAll();

$active_alerts = 0;
$resolved_alerts = 0;
$critical_alerts = 0;

foreach($alerts_data as $alert) {
    if($alert['alert_status'] == 'Active') $active_alerts++;
    if($alert['alert_status'] == 'Resolved') $resolved_alerts++;
    if($alert['severity_level'] == 'Critical') $critical_alerts++;
}
?>

<h2>?? Alert History Report</h2>
<h3>Period: <?php echo date('d M Y', strtotime($from_date)); ?> - <?php echo date('d M Y', strtotime($to_date)); ?></h3>

<div style="margin-bottom: 20px;">
    <div class="summary-box">
        <div class="summary-value" style="color: #e74c3c;"><?php echo $critical_alerts; ?></div>
        <div class="summary-label">Critical Alerts</div>
    </div>
    <div class="summary-box">
        <div class="summary-value" style="color: #f39c12;"><?php echo $active_alerts; ?></div>
        <div class="summary-label">Active Alerts</div>
    </div>
    <div class="summary-box">
        <div class="summary-value" style="color: #2ecc71;"><?php echo $resolved_alerts; ?></div>
        <div class="summary-label">Resolved Alerts</div>
    </div>
</div>

<table class="report-table">
    <thead>
        <tr>
            <th>Alert ID</th>
            <th>Medicine</th>
            <th>Alert Type</th>
            <th>Severity</th>
            <th>Message</th>
            <th>Date Generated</th>
            <th>Date Resolved</th>
            <th>Status</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($alerts_data as $alert): ?>
        <tr style="<?php echo ($alert['severity_level'] == 'Critical' && $alert['alert_status'] == 'Active') ? 'background:#fee;' : ''; ?>">
            <td><?php echo $alert['alert_id']; ?></td>
            <td><?php echo $alert['medicine_name']; ?></td>
            <td><?php echo $alert['alert_type']; ?></td>
            <td>
                <?php if($alert['severity_level'] == 'Critical'): ?>
                    <span style="color:#e74c3c;">?? Critical</span>
                <?php elseif($alert['severity_level'] == 'Warning'): ?>
                    <span style="color:#f39c12;">?? Warning</span>
                <?php else: ?>
                    <span style="color:#3498db;">?? Info</span>
                <?php endif; ?>
             </td>
            <td><?php echo substr($alert['alert_message'], 0, 80); ?>...</td>
            <td><?php echo date('d M Y H:i', strtotime($alert['date_generated'])); ?></td>
            <td><?php echo $alert['date_resolved'] ? date('d M Y H:i', strtotime($alert['date_resolved'])) : 'Not resolved'; ?></td>
            <td>
                <?php 
                if($alert['alert_status'] == 'Active') echo '<span style="color:#e74c3c;">?? Active</span>';
                elseif($alert['alert_status'] == 'Acknowledged') echo '<span style="color:#3498db;">??? Acknowledged</span>';
                else echo '<span style="color:#2ecc71;">? Resolved</span>';
                ?>
             </td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>