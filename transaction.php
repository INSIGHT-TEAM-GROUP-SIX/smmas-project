<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
checkAuth();

$conn = getConnection();
$message = '';

// NO function here - it's now in database.php

// Handle transaction submission
if(isset($_POST['process_transaction'])) {
    $transaction_id = generateTransactionID($conn);  // Calls function from database.php
    $medicine_id = $_POST['medicine_id'];
    $batch_id = $_POST['batch_id'];
    $patient_id = !empty($_POST['patient_id']) ? $_POST['patient_id'] : null;
    $transaction_type = $_POST['transaction_type'];
    $quantity = $_POST['quantity'];
    $unit_price = $_POST['unit_price'];
    $handled_by = $_SESSION['full_name'];
    $payment_method = $_POST['payment_method'] ?? null;
    
    // Check stock before processing
    if($transaction_type == 'Dispense') {
        $check = $conn->prepare("SELECT qty_remaining FROM batch WHERE batch_id = ?");
        $check->execute([$batch_id]);
        $available = $check->fetch()['qty_remaining'];
        
        if($available < $quantity) {
            $message = '<div class="error">❌ Insufficient stock! Only ' . $available . ' units available.</div>';
        } else {
            try {
                $conn->beginTransaction();
                
                $sql = "INSERT INTO `transaction` (transaction_id, medicine_id, batch_id, patient_id, transaction_type, transaction_date, quantity, unit_price, handled_by, payment_method) 
                        VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$transaction_id, $medicine_id, $batch_id, $patient_id, $transaction_type, $quantity, $unit_price, $handled_by, $payment_method]);
                
                $update = $conn->prepare("UPDATE batch SET qty_remaining = qty_remaining - ? WHERE batch_id = ?");
                $update->execute([$quantity, $batch_id]);
                
                $update_med = $conn->prepare("UPDATE medicine SET current_stock = current_stock - ? WHERE medicine_id = ?");
                $update_med->execute([$quantity, $medicine_id]);
                
                $conn->commit();
                
                $med_name = $conn->prepare("SELECT medicine_name FROM medicine WHERE medicine_id = ?");
                $med_name->execute([$medicine_id]);
                $medicine_name = $med_name->fetch()['medicine_name'];
                
                $message = '<div class="success">✅ Transaction successful!<br>
                            ID: ' . $transaction_id . '<br>
                            Medicine: ' . $medicine_name . '<br>
                            Quantity: ' . $quantity . '<br>
                            Total: UGX ' . number_format($quantity * $unit_price) . '<br>
                            <button onclick="window.print()" style="margin-top:10px;background:#0A4F6E;color:white;border:none;padding:8px 15px;border-radius:5px;cursor:pointer;">🖨️ Print Receipt</button></div>';
                
            } catch(PDOException $e) {
                $conn->rollBack();
                $message = '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
            }
        }
    } else {
        try {
            $sql = "INSERT INTO `transaction` (transaction_id, medicine_id, batch_id, patient_id, transaction_type, transaction_date, quantity, unit_price, handled_by, payment_method) 
                    VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);
            $stmt->execute([$transaction_id, $medicine_id, $batch_id, $patient_id, $transaction_type, $quantity, $unit_price, $handled_by, $payment_method]);
            $message = '<div class="success">✅ Transaction successful! ID: ' . $transaction_id . '</div>';
        } catch(PDOException $e) {
            $message = '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
        }
    }
}

// AJAX for batches
if(isset($_GET['get_batches']) && isset($_GET['medicine_id'])) {
    $medicine_id = $_GET['medicine_id'];
    $stmt = $conn->prepare("SELECT batch_id, batch_number, qty_remaining, expiry_date FROM batch WHERE medicine_id = ? AND batch_status = 'Active' AND qty_remaining > 0 AND expiry_date > CURDATE() ORDER BY expiry_date ASC");
    $stmt->execute([$medicine_id]);
    $batches = $stmt->fetchAll();
    
    echo '<option value="">-- Select Batch (FEFO) --</option>';
    foreach($batches as $batch) {
        echo '<option value="' . $batch['batch_id'] . '">' . $batch['batch_number'] . ' - ' . $batch['qty_remaining'] . ' units left (Exp: ' . date('d/m/Y', strtotime($batch['expiry_date'])) . ')</option>';
    }
    if(count($batches) == 0) {
        echo '<option value="">No active batches available</option>';
    }
    exit;
}

$medicines = $conn->query("SELECT medicine_id, medicine_name, unit_price FROM medicine WHERE status = 'Active' ORDER BY medicine_name")->fetchAll();
$patients = $conn->query("SELECT patient_id, full_name FROM patient ORDER BY full_name")->fetchAll();
$recent = $conn->query("SELECT t.*, m.medicine_name FROM `transaction` t JOIN medicine m ON t.medicine_id = m.medicine_id ORDER BY t.transaction_date DESC LIMIT 20")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Transaction - SMMAS</title>
<link rel="stylesheet" href="css/style.css">
<script src="https://code.jquery.com/jquery-1.7.2.min.js"></script>
<style>
.form-container{background:white;padding:20px;border-radius:10px;margin-bottom:20px}
.form-row{margin-bottom:15px}
.form-row label{display:inline-block;width:150px;font-weight:bold}
.form-row input,.form-row select{width:250px;padding:8px;border:1px solid #ddd;border-radius:5px}
.btn-primary{background:#0A4F6E;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer}
.total-box{background:#0A4F6E;color:white;padding:15px;text-align:center;border-radius:5px;margin:15px 0}
.data-table{width:100%;border-collapse:collapse;background:white}
.data-table th{background:#34495e;color:white;padding:12px;text-align:left}
.data-table td{padding:10px;border-bottom:1px solid #ddd}
.success{background:#d4edda;color:#155724;padding:15px;border-radius:5px;margin-bottom:20px}
.error{background:#f8d7da;color:#721c24;padding:15px;border-radius:5px;margin-bottom:20px}
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container">
<h1>💰 Transaction Processing</h1>
<p style="color:#666;margin-bottom:20px">Transaction IDs are auto-generated: TXN-20260507-001, TXN-20260507-002...</p>
<?php echo $message; ?>

<div class="form-container">
<h2>➕ New Transaction</h2>
<form method="post" id="transForm">
<div class="form-row"><label>Transaction ID:</label><input type="text" value="Auto-generated" disabled style="background:#f0f0f0;width:300px"></div>
<div class="form-row"><label>Transaction Type:</label><select name="transaction_type" id="transType"><option value="Dispense">💊 Dispense to Patient</option><option value="Restock">📦 Restock Inventory</option><option value="Return">🔄 Return from Patient</option></select></div>
<div class="form-row"><label>Medicine:</label><select name="medicine_id" id="medicine" required><option value="">-- Select Medicine --</option><?php foreach($medicines as $m): ?><option value="<?php echo $m['medicine_id']; ?>" data-price="<?php echo $m['unit_price']; ?>"><?php echo $m['medicine_name']; ?> - UGX <?php echo number_format($m['unit_price']); ?></option><?php endforeach; ?></select></div>
<div class="form-row"><label>Batch (FEFO):</label><select name="batch_id" id="batch" required><option value="">-- First select a medicine --</option></select><small>FEFO = First Expiry, First Out</small></div>
<div class="form-row" id="patientRow"><label>Patient:</label><select name="patient_id"><option value="">-- Select Patient --</option><?php foreach($patients as $p): ?><option value="<?php echo $p['patient_id']; ?>"><?php echo $p['full_name']; ?></option><?php endforeach; ?></select></div>
<div class="form-row"><label>Quantity:</label><input type="number" name="quantity" id="quantity" min="1" required></div>
<div class="form-row"><label>Unit Price (UGX):</label><input type="number" name="unit_price" id="price" readonly style="background:#f0f0f0"></div>
<div class="form-row" id="paymentRow"><label>Payment Method:</label><select name="payment_method"><option>Cash</option><option>Insurance</option><option>Waived</option></select></div>
<div class="total-box">💰 TOTAL: UGX <span id="totalAmt">0</span></div>
<input type="submit" name="process_transaction" value="✅ Process Transaction" class="btn-primary">
</form>
</div>

<div class="data-table-container">
<h2>📋 Recent Transactions</h2>
<table class="data-table">
<thead><tr><th>ID</th><th>Medicine</th><th>Type</th><th>Qty</th><th>Amount</th><th>Staff</th><th>Date</th></tr></thead>
<tbody>
<?php foreach($recent as $r): $total = $r['quantity'] * $r['unit_price']; ?>
<tr><td style="font-family:monospace"><?php echo $r['transaction_id']; ?></td><td><?php echo $r['medicine_name']; ?></td><td><?php echo $r['transaction_type']; ?></td><td><?php echo $r['quantity']; ?></td><td>UGX <?php echo number_format($total); ?></td><td><?php echo $r['handled_by']; ?></td><td><?php echo date('d M Y H:i', strtotime($r['transaction_date'])); ?></td></tr>
<?php endforeach; ?>
</tbody>
</table>
</div>
</div>

<script>
$(document).ready(function(){
    $('#medicine').change(function(){
        var price = $(this).find(':selected').data('price');
        $('#price').val(price);
        var mid = $(this).val();
        if(mid) {
            $.get('transaction.php', {get_batches: 1, medicine_id: mid}, function(data) {
                $('#batch').html(data);
            });
        } else {
            $('#batch').html('<option value="">-- First select a medicine --</option>');
        }
        calculateTotal();
    });
    
    $('#quantity').on('input', calculateTotal);
    
    function calculateTotal() {
        var qty = $('#quantity').val() || 0;
        var price = $('#price').val() || 0;
        $('#totalAmt').text((qty * price).toLocaleString());
    }
    
    $('#transType').change(function(){
        if($(this).val() == 'Dispense') {
            $('#patientRow').show();
            $('#paymentRow').show();
        } else {
            $('#patientRow').hide();
            $('#paymentRow').hide();
        }
    }).trigger('change');
});
</script>
</body>
</html>