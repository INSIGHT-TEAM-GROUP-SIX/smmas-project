<?php
// Patients Report
$stmt = $conn->prepare("
    SELECT 
        p.*,
        COUNT(t.transaction_id) as visit_count,
        SUM(t.quantity) as total_medicines,
        COALESCE(SUM(t.quantity * t.unit_price), 0) as total_spent
    FROM Patient p
    LEFT JOIN `Transaction` t ON p.patient_id = t.patient_id AND t.transaction_type = 'Dispense'
    WHERE DATE(p.date_registered) BETWEEN ? AND ?
    GROUP BY p.patient_id
    ORDER BY p.date_registered DESC
");

$stmt->execute([$from_date, $to_date]);
$patients_data = $stmt->fetchAll();

$total_patients = count($patients_data);
$total_visits = 0;
$total_revenue = 0;

foreach($patients_data as $patient) {
    $total_visits += $patient['visit_count'];
    $total_revenue += $patient['total_spent'];
}
?>

<h2>?? Patient Report</h2>
<h3>Period: <?php echo date('d M Y', strtotime($from_date)); ?> - <?php echo date('d M Y', strtotime($to_date)); ?></h3>

<div style="margin-bottom: 20px;">
    <div class="summary-box">
        <div class="summary-value"><?php echo $total_patients; ?></div>
        <div class="summary-label">New Patients</div>
    </div>
    <div class="summary-box">
        <div class="summary-value"><?php echo $total_visits; ?></div>
        <div class="summary-label">Total Visits</div>
    </div>
    <div class="summary-box">
        <div class="summary-value">UGX <?php echo number_format($total_revenue); ?></div>
        <div class="summary-label">Revenue from Patients</div>
    </div>
</div>

<?php
// Patient type breakdown
$type_stats = $conn->query("
    SELECT patient_type, COUNT(*) as count 
    FROM Patient 
    WHERE DATE(date_registered) BETWEEN '$from_date' AND '$to_date'
    GROUP BY patient_type
")->fetchAll();
?>

<h3>Patient Type Distribution</h3>
<table class="report-table" style="width: 50%;">
    <thead>
        <tr><th>Patient Type</th><th>Number of Patients</th><th>Percentage</th></tr>
    </thead>
    <tbody>
        <?php foreach($type_stats as $type): ?>
        <tr>
            <td><?php echo $type['patient_type']; ?></td>
            <td><?php echo $type['count']; ?></td>
            <td><?php echo round(($type['count'] / $total_patients) * 100, 1); ?>%</td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h3 style="margin-top: 30px;">Patient Details</h3>
<table class="report-table">
    <thead>
        <tr>
            <th>Patient ID</th>
            <th>Name</th>
            <th>Type</th>
            <th>Date Registered</th>
            <th>Age</th>
            <th>Blood Group</th>
            <th>Visits</th>
            <th>Total Spent</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($patients_data as $patient): 
            $age = date_diff(date_create($patient['date_of_birth']), date_create('today'))->y;
        ?>
        <tr>
            <td><?php echo $patient['patient_id']; ?></td>
            <td><?php echo $patient['full_name']; ?></td>
            <td><?php echo $patient['patient_type']; ?></td>
            <td><?php echo date('d M Y', strtotime($patient['date_registered'])); ?></td>
            <td><?php echo $age; ?> years</td>
            <td><?php echo $patient['blood_group'] ?: 'N/A'; ?></td>
            <td><?php echo $patient['visit_count']; ?></td>
            <td>UGX <?php echo number_format($patient['total_spent']); ?></td>
        </tr>
        <?php endforeach; ?>
    </tbody>
</table>