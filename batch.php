<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
checkAuth();

$conn = getConnection();
$message = '';

// Function to auto-generate batch number
function generateBatchNumber($conn) {
    // Get the last batch number
    $stmt = $conn->query("SELECT batch_number FROM Batch ORDER BY created_at DESC LIMIT 1");
    $last = $stmt->fetch();
    
    if($last && preg_match('/BAT-(\d+)/', $last['batch_number'], $matches)) {
        $next_num = intval($matches[1]) + 1;
    } else {
        $next_num = 1;
    }
    
    return 'BAT-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
}

if(isset($_POST['add_batch'])) {
    // Auto-generate batch ID and number
    $batch_id = generateBatchID($conn);
    $batch_number = generateBatchNumber($conn);  // Auto-generated
    $medicine_id = $_POST['medicine_id'];
    $supplier_name = $_POST['supplier_name'];
    $date_received = $_POST['date_received'];
    $expiry_date = $_POST['expiry_date'];
    $qty_received = $_POST['qty_received'];
    $unit_cost = $_POST['unit_cost'];
    
    if(strtotime($expiry_date) <= strtotime($date_received)) {
        $message = '<div class="error">❌ Expiry date must be after received date!</div>';
    } else {
        $sql = "INSERT INTO Batch (batch_id, medicine_id, batch_number, supplier_name, date_received, expiry_date, qty_received, qty_remaining, unit_cost, batch_status, created_by, created_at) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Active', ?, NOW())";
        $stmt = $conn->prepare($sql);
        try {
            $stmt->execute([$batch_id, $medicine_id, $batch_number, $supplier_name, $date_received, $expiry_date, $qty_received, $qty_received, $unit_cost, $_SESSION['username']]);
            $message = '<div class="success">✅ Batch added successfully!<br>Batch ID: ' . $batch_id . '<br>Batch Number: ' . $batch_number . '</div>';
        } catch(PDOException $e) {
            $message = '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
        }
    }
}

$medicines = $conn->query("SELECT medicine_id, medicine_name FROM Medicine WHERE status='Active'")->fetchAll();
$batches = $conn->query("
    SELECT b.*, m.medicine_name, 
           (b.qty_remaining / b.qty_received * 100) as stock_percentage
    FROM Batch b 
    JOIN Medicine m ON b.medicine_id = m.medicine_id 
    ORDER BY b.expiry_date ASC
")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Batch Management - SMMAS</title>
<link rel="stylesheet" href="css/style.css">
<style>
.batch-stats{display:flex;gap:20px;margin-bottom:25px;flex-wrap:wrap}
.stat-card{flex:1;background:white;padding:20px;border-radius:10px;text-align:center;box-shadow:0 2px 5px rgba(0,0,0,0.1);border-left:4px solid}
.stat-card.total{border-left-color:#0A4F6E}
.stat-card.active{border-left-color:#2ecc71}
.stat-card.expiring{border-left-color:#f39c12}
.stat-number{font-size:32px;font-weight:bold;color:#2c3e50}
.stat-label{color:#666;margin-top:5px}
.batch-table{width:100%;border-collapse:collapse;background:white;border-radius:10px;overflow:hidden}
.batch-table th{background:#0A4F6E;color:white;padding:12px;text-align:left}
.batch-table td{padding:10px 12px;border-bottom:1px solid #eee}
.batch-table tr:hover{background:#f8f9fa}
.batch-table tr.expired{background:#fee}
.batch-table tr.expiring{background:#fff8e7}
.status-batch{display:inline-block;padding:4px 10px;border-radius:20px;font-size:11px;font-weight:bold}
.status-Active{background:#2ecc71;color:white}
.status-Depleted{background:#95a5a6;color:white}
.status-Expired{background:#e74c3c;color:white}
.stock-bar{width:80px;background:#ecf0f1;border-radius:10px;height:6px;margin-top:5px}
.stock-bar-fill{height:6px;border-radius:10px}
.stock-high{background:#2ecc71}
.stock-medium{background:#f39c12}
.stock-low{background:#e74c3c}
.form-container{background:white;padding:20px;border-radius:10px;margin-bottom:25px;border:1px solid #ddd}
.form-row{margin-bottom:15px}
.form-row label{display:inline-block;width:150px;font-weight:bold}
.form-row input,.form-row select{width:250px;padding:8px;border:1px solid #ddd;border-radius:5px}
.btn-primary{background:#0A4F6E;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer}
.btn-primary:hover{background:#0E6B40}
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container">
<h1>📦 Batch Management</h1>
<p style="color:#666;margin-bottom:20px">Batch numbers are auto-generated sequentially (BAT-0001, BAT-0002...)</p>

<?php echo $message; ?>

<?php
$total = count($batches);
$active = 0;
$expiring = 0;
foreach($batches as $b) {
    if($b['batch_status'] == 'Active') $active++;
    $days = (strtotime($b['expiry_date']) - time()) / 86400;
    if($days <= 30 && $days >= 0) $expiring++;
}
?>
<div class="batch-stats">
<div class="stat-card total"><div class="stat-number"><?php echo $total; ?></div><div class="stat-label">Total Batches</div></div>
<div class="stat-card active"><div class="stat-number"><?php echo $active; ?></div><div class="stat-label">Active Batches</div></div>
<div class="stat-card expiring"><div class="stat-number"><?php echo $expiring; ?></div><div class="stat-label">Expiring Within 30 Days</div></div>
</div>

<div class="form-container">
<h2>➕ Add New Batch</h2>
<form method="post">
<div class="form-row">
<label>Batch Number:</label>
<input type="text" value="Will be auto-generated (BAT-0001, BAT-0002...)" disabled style="background:#f0f0f0;width:300px">
<small>Auto-generated sequentially</small>
</div>
<div class="form-row">
<label>Medicine *:</label>
<select name="medicine_id" required>
<option value="">-- Select Medicine --</option>
<?php foreach($medicines as $m): ?>
<option value="<?php echo $m['medicine_id']; ?>"><?php echo $m['medicine_name']; ?></option>
<?php endforeach; ?>
</select>
</div>
<div class="form-row">
<label>Supplier Name *:</label>
<input type="text" name="supplier_name" required>
</div>
<div class="form-row">
<label>Date Received *:</label>
<input type="date" name="date_received" required>
</div>
<div class="form-row">
<label>Expiry Date *:</label>
<input type="date" name="expiry_date" required>
<small>Must be after received date</small>
</div>
<div class="form-row">
<label>Quantity Received *:</label>
<input type="number" name="qty_received" min="1" required>
</div>
<div class="form-row">
<label>Unit Cost (UGX):</label>
<input type="number" name="unit_cost" step="0.01">
</div>
<input type="submit" name="add_batch" value="➕ Add Batch" class="btn-primary">
</form>
</div>

<div style="overflow-x:auto">
<table class="batch-table">
<thead>
<tr>
<th>Batch Number</th>
<th>Medicine</th>
<th>Supplier</th>
<th>Date Received</th>
<th>Expiry Date</th>
<th>Received</th>
<th>Remaining</th>
<th>Stock Level</th>
<th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach($batches as $b): 
$days_left = (strtotime($b['expiry_date']) - time()) / 86400;
$row_class = '';
$stock_class = 'stock-high';
if($days_left < 0) {
    $row_class = 'expired';
    $stock_class = 'stock-low';
} elseif($days_left <= 30) {
    $row_class = 'expiring';
    $stock_class = 'stock-medium';
}
$percent = min(100, $b['stock_percentage']);
?>
<tr class="<?php echo $row_class; ?>">
<td><strong><?php echo $b['batch_number']; ?></strong></td>
<td><?php echo $b['medicine_name']; ?></td>
<td><?php echo $b['supplier_name']; ?></td>
<td><?php echo date('d M Y', strtotime($b['date_received'])); ?></td>
<td><?php echo date('d M Y', strtotime($b['expiry_date'])); ?></td>
<td><?php echo $b['qty_received']; ?></td>
<td><?php echo $b['qty_remaining']; ?> units</td>
<td><div class="stock-bar"><div class="stock-bar-fill <?php echo $stock_class; ?>" style="width: <?php echo $percent; ?>%"></div></div><small><?php echo round($percent); ?>%</small></td>
<td><span class="status-batch status-<?php echo $b['batch_status']; ?>"><?php echo $b['batch_status']; ?></span></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div style="margin-top:20px;padding:10px;background:#e7f3ff;border-radius:5px;text-align:center;font-size:12px">
<strong>ℹ️ Batch Number Format:</strong> BAT-0001, BAT-0002, BAT-0003... (Auto-generated sequentially)
</div>
</div>
</body>
</html>