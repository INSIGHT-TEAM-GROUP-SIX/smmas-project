<?php
// Transactions Report
$stmt = $conn->prepare("
    SELECT 
        DATE(t.transaction_date) as trans_date,
        t.transaction_type,
        COUNT(*) as transaction_count,
        SUM(t.quantity) as total_quantity,
        SUM(t.quantity * t.unit_price) as total_amount
    FROM `transaction` t
    WHERE DATE(t.transaction_date) BETWEEN ? AND ?
    GROUP BY DATE(t.transaction_date), t.transaction_type
    ORDER BY trans_date DESC
");

$stmt->execute([$from_date, $to_date]);
$transaction_summary = $stmt->fetchAll();

// Transaction Details
$stmt2 = $conn->prepare("
    SELECT 
        t.transaction_id,
        t.transaction_type,
        m.medicine_name,
        t.quantity,
        t.unit_price,
        (t.quantity * t.unit_price) as total,
        t.transaction_date,
        t.handled_by,
        p.full_name as patient_name,
        t.payment_method
    FROM `transaction` t
    JOIN medicine m ON t.medicine_id = m.medicine_id
    LEFT JOIN patient p ON t.patient_id = p.patient_id
    WHERE DATE(t.transaction_date) BETWEEN ? AND ?
    ORDER BY t.transaction_date DESC
    LIMIT 200
");

$stmt2->execute([$from_date, $to_date]);
$transaction_details = $stmt2->fetchAll();

// Calculate totals
$total_income = 0;
$total_dispensed = 0;
$total_restocked = 0;

foreach($transaction_summary as $trans) {
    if($trans['transaction_type'] == 'Dispense') {
        $total_income += $trans['total_amount'];
        $total_dispensed += $trans['total_quantity'];
    }
}
?>

<h2>?? Financial Transaction Report</h2>
<h3>Period: <?php echo date('d M Y', strtotime($from_date)); ?> - <?php echo date('d M Y', strtotime($to_date)); ?></h3>

<div style="margin-bottom: 20px;">
    <div class="summary-box">
        <div class="summary-value">UGX <?php echo number_format($total_income); ?></div>
        <div class="summary-label">Total Income</div>
    </div>
    <div class="summary-box">
        <div class="summary-value"><?php echo $total_dispensed; ?></div>
        <div class="summary-label">Units Dispensed</div>
    </div>
    <div class="summary-box">
        <div class="summary-value"><?php echo count($transaction_details); ?></div>
        <div class="summary-label">Total Transactions</div>
    </div>
</div>

<h3>Transaction Summary by Type</h3>
<table class="report-table">
    <thead>
        <tr>
            <th>Date</th>
            <th>Transaction Type</th>
            <th>Number of Transactions</th>
            <th>Total Quantity</th>
            <th>Total Amount (UGX)</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($transaction_summary as $trans): ?>
        <tr>
            <td><?php echo date('d M Y', strtotime($trans['trans_date'])); ?></td>
            <td>
                <?php 
                $type_icon = '';
                if($trans['transaction_type'] == 'Dispense') $type_icon = '??';
                elseif($trans['transaction_type'] == 'Restock') $type_icon = '??';
                elseif($trans['transaction_type'] == 'Return') $type_icon = '??';
                else $type_icon = '???';
                echo $type_icon . ' ' . $trans['transaction_type'];
                ?>
             </td>
            <td><?php echo $trans['transaction_count']; ?></td>
            <td><?php echo $trans['total_quantity']; ?></td>
            <td>UGX <?php echo number_format($trans['total_amount']); ?></td>
         </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h3 style="margin-top: 30px;">Transaction Details</h3>
<table class="report-table">
    <thead>
        <tr>
            <th>Transaction ID</th>
            <th>Date & Time</th>
            <th>Type</th>
            <th>Medicine</th>
            <th>Patient</th>
            <th>Quantity</th>
            <th>Unit Price</th>
            <th>Total</th>
            <th>Handled By</th>
            <th>Payment</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($transaction_details as $trans): ?>
        <tr>
            <td><?php echo $trans['transaction_id']; ?></td>
            <td><?php echo date('d M Y H:i', strtotime($trans['transaction_date'])); ?></td>
            <td><?php echo $trans['transaction_type']; ?></td>
            <td><?php echo $trans['medicine_name']; ?></td>
            <td><?php echo $trans['patient_name'] ?? 'N/A'; ?></td>
            <td><?php echo $trans['quantity']; ?></td>
            <td>UGX <?php echo number_format($trans['unit_price']); ?></td>
            <td>UGX <?php echo number_format($trans['total']); ?></td>
            <td><?php echo $trans['handled_by']; ?></td>
            <td><?php echo $trans['payment_method'] ?? 'N/A'; ?></td>
         </tr>
        <?php endforeach; ?>
    </tbody>
</table>