<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
checkAuth();

$conn = getConnection();
$message = '';

// Handle form submission
if(isset($_POST['add_patient'])) {
    $patient_id = generatePatientID($conn);  // Now generates sequential IDs
    $full_name = $_POST['full_name'];
    $date_of_birth = $_POST['date_of_birth'];
    $gender = $_POST['gender'];
    $phone_number = $_POST['phone_number'];
    $patient_type = $_POST['patient_type'];
    $date_registered = date('Y-m-d');
    $blood_group = $_POST['blood_group'];
    $known_allergies = $_POST['known_allergies'];
    
    $sql = "INSERT INTO Patient (patient_id, full_name, date_of_birth, gender, phone_number, patient_type, date_registered, blood_group, known_allergies) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($sql);
    try {
        $stmt->execute([$patient_id, $full_name, $date_of_birth, $gender, $phone_number, $patient_type, $date_registered, $blood_group, $known_allergies]);
        $message = '<div class="success">✅ Patient registered! ID: ' . $patient_id . '</div>';
    } catch(PDOException $e) {
        $message = '<div class="error">❌ Error: ' . $e->getMessage() . '</div>';
    }
}

$patients = $conn->query("SELECT * FROM Patient ORDER BY date_registered DESC")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head><title>Patient Management</title>
<link rel="stylesheet" href="css/style.css">
<style>
.form-container{background:white;padding:20px;border-radius:10px;margin-bottom:20px;border:1px solid #ddd}
.form-row{margin-bottom:15px}
.form-row label{display:inline-block;width:150px;font-weight:bold}
.form-row input,.form-row select,.form-row textarea{width:250px;padding:8px;border:1px solid #ddd;border-radius:5px}
.btn-primary{background:#0A4F6E;color:white;padding:10px 20px;border:none;border-radius:5px;cursor:pointer}
.data-table{width:100%;border-collapse:collapse;background:white;border-radius:10px;overflow:hidden}
.data-table th{background:#34495e;color:white;padding:12px;text-align:left}
.data-table td{padding:10px 12px;border-bottom:1px solid #ddd}
.success{background:#d4edda;color:#155724;padding:10px;border-radius:5px;margin-bottom:20px}
.error{background:#f8d7da;color:#721c24;padding:10px;border-radius:5px;margin-bottom:20px}
.allergy-warning{background:#f8d7da;color:#721c24;padding:3px 8px;border-radius:5px;font-size:11px;display:inline-block}
.search-box{text-align:right;margin-bottom:20px}
.search-box input{padding:8px;width:250px;border:1px solid #ddd;border-radius:5px}
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container">
<h1>👥 Patient Registration</h1>
<p style="color:#666;margin-bottom:20px">Patient IDs are sequential: PAT-0001, PAT-0002, PAT-0003...</p>
<?php echo $message; ?>

<div class="form-container">
<h2>➕ Register New Patient</h2>
<form method="post">
<div class="form-row"><label>Patient ID:</label><input type="text" value="Will be auto-generated (PAT-0001, PAT-0002...)" disabled style="background:#f0f0f0;width:300px"></div>
<div class="form-row"><label>Full Name *:</label><input type="text" name="full_name" required></div>
<div class="form-row"><label>Date of Birth *:</label><input type="date" name="date_of_birth" required></div>
<div class="form-row"><label>Gender *:</label><select name="gender"><option>Male</option><option>Female</option><option>Other</option></select></div>
<div class="form-row"><label>Phone Number:</label><input type="text" name="phone_number"></div>
<div class="form-row"><label>Patient Type *:</label><select name="patient_type"><option>Student</option><option>Staff</option><option>Community</option></select></div>
<div class="form-row"><label>Blood Group:</label><select name="blood_group"><option value="">Not Specified</option><option>A+</option><option>A-</option><option>B+</option><option>B-</option><option>O+</option><option>O-</option><option>AB+</option><option>AB-</option></select></div>
<div class="form-row"><label>Known Allergies:</label><textarea name="known_allergies" rows="2" cols="30" placeholder="e.g., Penicillin, Peanuts, Latex"></textarea><br><small>⚠️ This information is shown to medical staff</small></div>
<input type="submit" name="add_patient" value="➕ Register Patient" class="btn-primary">
</form>
</div>

<div class="search-box"><input type="text" id="searchInput" placeholder="🔍 Search patient by name, ID, or phone..." onkeyup="searchPatient()"></div>

<div class="data-table-container">
<table class="data-table" id="patientTable">
<thead>
<tr>
<th>ID</th><th>Full Name</th><th>Age/Gender</th><th>Phone</th><th>Type</th><th>Blood Group</th><th>Allergies</th><th>Registered</th>
</tr>
</thead>
<tbody>
<?php foreach($patients as $p): 
$age = date_diff(date_create($p['date_of_birth']), date_create('today'))->y;
$has_allergy = !empty($p['known_allergies']);
?>
<tr style="<?php echo $has_allergy ? 'border-left:3px solid #e74c3c' : ''; ?>">
<td><?php echo $p['patient_id']; ?></td>
<td><strong><?php echo $p['full_name']; ?></strong></td>
<td><?php echo $age; ?> yrs / <?php echo $p['gender']; ?></td>
<td><?php echo $p['phone_number'] ?: 'N/A'; ?></td>
<td><?php echo $p['patient_type']; ?></td>
<td><?php echo $p['blood_group'] ?: 'N/A'; ?></td>
<td style="background:<?php echo $has_allergy ? '#f8d7da' : 'transparent'; ?>">
<?php if($has_allergy): ?>
<span class="allergy-warning">⚠️ <?php echo $p['known_allergies']; ?></span>
<?php else: ?>
<span style="color:#999;">No known allergies</span>
<?php endif; ?>
</td>
<td><?php echo date('d M Y', strtotime($p['date_registered'])); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

<div style="margin-top:20px;padding:15px;background:#e7f3ff;border-radius:10px;">
<strong>⚠️ Medical Alert:</strong> Always check the <strong style="color:#e74c3c;">Allergies column</strong> before dispensing medication. Red background indicates patient has allergies.
</div>
</div>

<script>
function searchPatient() {
    var input = document.getElementById('searchInput');
    var filter = input.value.toUpperCase();
    var table = document.getElementById('patientTable');
    var tr = table.getElementsByTagName('tr');
    for(var i=1; i<tr.length; i++) {
        var tdId = tr[i].getElementsByTagName('td')[0];
        var tdName = tr[i].getElementsByTagName('td')[1];
        var tdPhone = tr[i].getElementsByTagName('td')[3];
        if(tdId || tdName || tdPhone) {
            var id = tdId ? (tdId.textContent || tdId.innerText) : '';
            var name = tdName ? (tdName.textContent || tdName.innerText) : '';
            var phone = tdPhone ? (tdPhone.textContent || tdPhone.innerText) : '';
            if(id.toUpperCase().indexOf(filter) > -1 || name.toUpperCase().indexOf(filter) > -1 || phone.toUpperCase().indexOf(filter) > -1) {
                tr[i].style.display = '';
            } else {
                tr[i].style.display = 'none';
            }
        }
    }
}
</script>
</body>
</html>