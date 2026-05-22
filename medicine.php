<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
checkAuth();

$conn = getConnection();
$message = '';

if(isset($_POST['add_medicine'])) {
    $medicine_id = generateMedicineID($conn);
    $medicine_name = $_POST['medicine_name'];
    $category = $_POST['category'];
    $dosage_form = $_POST['dosage_form'];
    $strength = $_POST['strength'];
    $unit_of_measure = $_POST['unit_of_measure'];
    $current_stock = $_POST['current_stock'];
    $reorder_level = $_POST['reorder_level'];
    $unit_price = $_POST['unit_price'];
    $status = $_POST['status'];
    
    $sql = "INSERT INTO medicine (medicine_id, medicine_name, category, dosage_form, strength, unit_of_measure, current_stock, reorder_level, unit_price, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    try {
        $stmt->execute([$medicine_id, $medicine_name, $category, $dosage_form, $strength, $unit_of_measure, $current_stock, $reorder_level, $unit_price, $status]);
        $message = '<div class="success">✅ Medicine added! ID: ' . $medicine_id . '</div>';
    } catch(PDOException $e) {
        $message = '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
    }
}

$medicines = $conn->query("SELECT * FROM medicine ORDER BY medicine_name")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Medicine Management</title>
<link rel="stylesheet" href="css/style.css">
<style>
*{margin:0;padding:0;box-sizing:border-box}
body{font-family:Arial;background:#f5f5f5}
.container{max-width:1200px;margin:20px auto;padding:0 20px}
h1{color:#2c3e50;margin-bottom:20px}
.form-container{background:white;padding:20px;border-radius:10px;margin-bottom:20px;border:1px solid #ddd}
.form-row{margin-bottom:15px}
.form-row label{display:inline-block;width:150px;font-weight:bold}
.form-row input,.form-row select{width:250px;padding:8px;border:1px solid #ddd;border-radius:5px}
.btn-primary{background:#0A4F6E;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer}
.data-table{width:100%;border-collapse:collapse;background:white;border-radius:10px;overflow:hidden}
.data-table th{background:#34495e;color:white;padding:12px;text-align:left}
.data-table td{padding:10px 12px;border-bottom:1px solid #ddd}
.success{background:#d4edda;color:#155724;padding:10px;border-radius:5px;margin-bottom:20px}
.error{background:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin-bottom:20px}
.status-good{color:#2ecc71;font-weight:bold}
.status-warning{color:#f39c12;font-weight:bold}
.status-low{color:#e67e22;font-weight:bold}
.status-out{color:#e74c3c;font-weight:bold}
.stock-value{font-weight:bold}
.legend{background:#e8f4f8;padding:15px;border-radius:10px;margin-top:20px;text-align:center}
.legend span{display:inline-block;margin:0 15px}
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container">
<h1>💊 Medicine Management</h1>
<?php echo $message; ?>

<div class="form-container">
<h2>➕ Add New Medicine</h2>
<form method="post">
<div class="form-row"><label>Medicine Name:</label><input type="text" name="medicine_name" required></div>
<div class="form-row"><label>Category:</label><select name="category"><option>Antibiotic</option><option>Analgesic</option><option>Antifungal</option><option>Antiviral</option></select></div>
<div class="form-row"><label>Dosage Form:</label><select name="dosage_form"><option>Tablet</option><option>Capsule</option><option>Syrup</option><option>Injection</option></select></div>
<div class="form-row"><label>Strength:</label><input type="text" name="strength" placeholder="e.g., 500mg" required></div>
<div class="form-row"><label>Unit of Measure:</label><select name="unit_of_measure"><option>Tablet</option><option>Bottle</option><option selected>Vial</option>
    <option>Capsule</option>
</select></div>
<div class="form-row"><label style="color:#e74c3c">Current Stock:</label><input type="number" name="current_stock" value="0" required></div>
<div class="form-row"><label>Reorder Level:</label><input type="number" name="reorder_level" required><small>Minimum stock before reorder</small></div>
<div class="form-row"><label>Unit Price (UGX):</label><input type="number" name="unit_price" step="0.01" required></div>
<div class="form-row"><label>Status:</label><select name="status"><option>Active</option><option>Discontinued</option></select></div>
<input type="submit" name="add_medicine" value="Add Medicine" class="btn-primary">
</form>
</div>

<div class="data-table-container">
<table class="data-table">
<thead>
<tr>
<th>ID</th><th>Name</th><th>Category</th><th>Strength</th><th>Current Stock</th><th>Reorder Level</th><th>Price</th><th>Status</th>
</tr>
</thead>
<tbody>
<?php foreach($medicines as $m): 
$stock = $m['current_stock'];
$reorder = $m['reorder_level'];
$dispense = $m['unit_of_measure'];

// Determine status text and class
if($m['status'] == 'Discontinued') {
    $status_text = '⚪ DISCONTINUED';
    $status_class = 'status-out';
} elseif($stock <= 0) {
    $status_text = '🔴 OUT OF STOCK';
    $status_class = 'status-out';
} elseif($stock < $reorder) {
    $status_text = '🟡 LOW STOCK (Need ' . ($reorder - $stock) . ' more)';
    $status_class = 'status-low';
} elseif($stock == $reorder) {
    $status_text = '🟠 AT REORDER LEVEL (Restock soon)';
    $status_class = 'status-warning';
} else {
    $status_text = '🟢 IN STOCK (Good)';
    $status_class = 'status-good';
}

// Stock color
$stock_class = ($stock <= $reorder) ? 'status-low' : 'status-good';
?>
<tr>
<td><?php echo $m['medicine_id']; ?></td>
<td><strong><?php echo $m['medicine_name']; ?></strong></td>
<td><?php echo $m['category']; ?></td>
<td><?php echo $m['strength']; ?> <?php echo $dispense; ?></td>
<td class="<?php echo $stock_class; ?>"><?php echo $stock; ?> <?php echo $dispense; ?></td>
<td><?php echo $reorder; ?> <?php echo $dispense; ?></td>
<td>UGX <?php echo number_format($m['unit_price']); ?></td>
<td class="<?php echo $status_class; ?>"><?php echo $status_text; ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div class="legend">
<strong>Status Guide:</strong>
<span style="color:#2ecc71;">🟢 IN STOCK</span>
<span style="color:#f39c12;">🟠 AT REORDER LEVEL</span>
<span style="color:#e67e22;">🟡 LOW STOCK</span>
<span style="color:#e74c3c;">🔴 OUT OF STOCK</span>
<span style="color:#95a5a6;">⚪ DISCONTINUED</span>
</div>
</div>
</body>
</html>