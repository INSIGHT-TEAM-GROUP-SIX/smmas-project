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
/* ==================== EXPIRY HEATMAP & ANIMATIONS ==================== */
.batch-stats{display:flex;gap:20px;margin-bottom:25px;flex-wrap:wrap}
.stat-card{flex:1;background:white;padding:20px;border-radius:10px;text-align:center;box-shadow:0 2px 5px rgba(0,0,0,0.1);border-left:5px solid}
.stat-card.total{border-left-color:#0A4F6E}
.stat-card.active{border-left-color:#2ecc71}
.stat-card.expiring{border-left-color:#f39c12}

.stat-number{font-size:32px;font-weight:bold;color:#2c3e50}
.stat-label{color:#666;margin-top:5px}

/* Expiry Badge */
.expiry-badge {
    padding: 8px 14px;
    border-radius: 25px;
    font-weight: 700;
    font-size: 0.95rem;
    display: inline-flex;
    align-items: center;
    gap: 6px;
}

.expires-soon {
    background: linear-gradient(135deg, #ff9800, #f57c00);
    color: white;
    animation: pulse 2s infinite;
}

.critical {
    background: linear-gradient(135deg, #e74c3c, #c0392b);
    color: white;
    animation: shake 0.6s infinite alternate;
    box-shadow: 0 0 15px rgba(231, 76, 60, 0.7);
}

.fire-emoji {
    animation: fire-shake 0.8s infinite alternate;
    display: inline-block;
}

/* Animations */
@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.8; }
}

@keyframes shake {
    0% { transform: translateX(0); }
    25% { transform: translateX(-5px); }
    75% { transform: translateX(5px); }
    100% { transform: translateX(0); }
}

@keyframes fire-shake {
    0%   { transform: scale(1) rotate(-12deg); }
    100% { transform: scale(1.25) rotate(12deg); }
}

/* Heatmap Row Colors */
.batch-table tr.low-risk    { background-color: #e8f5e9 !important; }
.batch-table tr.medium-risk { background-color: #fff3e0 !important; }
.batch-table tr.high-risk   { background-color: #ffebee !important; }
.batch-table tr.critical-risk { background-color: #ffcdd2 !important; font-weight: 500; }

.batch-table th{background:#0A4F6E;color:white;padding:12px;text-align:left}
.batch-table td{padding:12px;border-bottom:1px solid #eee}
.batch-table tr:hover{filter: brightness(0.98);}

.status-batch{display:inline-block;padding:5px 12px;border-radius:20px;font-size:13px;font-weight:bold}
.stock-bar{width:90px;background:#ecf0f1;border-radius:10px;height:8px;margin-top:4px}
.stock-bar-fill{height:8px;border-radius:10px}
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
            <th>Expiry Status</th>
        </tr>
        </thead>
        <tbody>
        <?php foreach($batches as $b): 
            $days_left = floor((strtotime($b['expiry_date']) - time()) / 86400);
            
            // Heatmap logic
            if($days_left < 0) {
                $row_class = 'expired critical-risk';
                $status_text = 'Expired';
            } elseif($days_left <= 7) {
                $row_class = 'critical-risk';
                $status_text = 'Critical';
            } elseif($days_left <= 30) {
                $row_class = 'high-risk';
                $status_text = 'Expiring Soon';
            } elseif($days_left <= 90) {
                $row_class = 'medium-risk';
                $status_text = 'Medium Risk';
            } else {
                $row_class = 'low-risk';
                $status_text = 'Safe';
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
            <td><strong><?php echo $b['qty_remaining']; ?></strong> units</td>
            <td>
                <div class="stock-bar"><div class="stock-bar-fill stock-high" style="width: <?php echo $percent; ?>%"></div></div>
                <small><?php echo round($percent); ?>%</small>
            </td>
            <td>
                <?php if($days_left <= 30 && $days_left >= 0): ?>
                    <span class="expiry-badge <?php echo ($days_left <= 7) ? 'critical' : 'expires-soon'; ?>">
                        <?php if($days_left <= 7): ?>🔥<?php endif; ?>
                        Expires in <strong><?php echo $days_left; ?></strong> days
                    </span>
                <?php elseif($days_left < 0): ?>
                    <span class="expiry-badge critical">Expired</span>
                <?php else: ?>
                    <span class="expiry-badge" style="background:#2ecc71;color:white;">Safe (<?php echo $days_left; ?> days)</span>
                <?php endif; ?>
            </td>
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