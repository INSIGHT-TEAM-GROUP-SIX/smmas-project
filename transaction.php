<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
checkAuth();

$conn = getConnection();
$message = '';

/* =========================================================
   HANDLE TRANSACTION SUBMISSION
   ========================================================= */
if(isset($_POST['process_transaction'])) {

    $transaction_id   = generateTransactionID($conn);
    $medicine_id      = $_POST['medicine_id'];
    $batch_id         = $_POST['batch_id'];
    $patient_id       = !empty($_POST['patient_id']) ? $_POST['patient_id'] : null;
    $transaction_type = $_POST['transaction_type'];
    $quantity         = $_POST['quantity'];
    $unit_price       = $_POST['unit_price'];
    $handled_by       = $_SESSION['full_name'];
    $payment_method   = $_POST['payment_method'] ?? null;
    $dispense_duration = isset($_POST['dispense_duration'])
        ? (int)$_POST['dispense_duration']
        : null;

    if($transaction_type == 'Dispense') {
        $check = $conn->prepare("SELECT qty_remaining FROM Batch WHERE batch_id = ?");
        $check->execute([$batch_id]);

        $available = $check->fetch()['qty_remaining'];

        if($available < $quantity) {

            $message = '<div class="error">
                        Insufficient stock! Only ' . $available . ' units available.
                        </div>';

        } else {

            try {

                $conn->beginTransaction();

                $sql = "INSERT INTO `Transaction`
                        (
                            transaction_id,
                            medicine_id,
                            batch_id,
                            patient_id,
                            transaction_type,
                            transaction_date,
                            quantity,
                            unit_price,
                            handled_by,
                            payment_method
                        )
                        VALUES
                        (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)";

                $stmt = $conn->prepare($sql);

                $stmt->execute([
                    $transaction_id,
                    $medicine_id,
                    $batch_id,
                    $patient_id,
                    $transaction_type,
                    $quantity,
                    $unit_price,
                    $handled_by,
                    $payment_method
                ]);

                /* Reduce batch stock */
                $update = $conn->prepare("
                    UPDATE Batch
                    SET qty_remaining = qty_remaining - ?
                    WHERE batch_id = ?
                ");

                $update->execute([$quantity, $batch_id]);

                $update_med = $conn->prepare("UPDATE Medicine SET current_stock = current_stock - ? WHERE medicine_id = ?");
                $update_med->execute([$quantity, $medicine_id]);

                $conn->commit();


                $med_name = $conn->prepare("SELECT medicine_name FROM Medicine WHERE medicine_id = ?");
                $med_name->execute([$medicine_id]);

                $medicine_name_for_tracker = $med_name->fetch()['medicine_name'];

                $message = '<div class="success">✅ Transaction successful!<br>

                $medicine_name = $med_name->fetch()['medicine_name'];

                $message = '
                <div class="success">
                    ✅ Transaction successful!<br><br>

                            ID: ' . $transaction_id . '<br>
                            Medicine: ' . $medicine_name_for_tracker . '<br>
                            Quantity: ' . $quantity . '<br>
                            Total: UGX ' . number_format($quantity * $unit_price) . '<br>

                            <button onclick="window.print()" style="margin-top:10px;background:#0A4F6E;color:white;border:none;padding:8px 15px;border-radius:5px;cursor:pointer;">🖨️ Print Receipt</button></div>';

                            <button onclick="window.print()" style="margin-top:10px;background:#0A4F6E;color:white;border:none;padding:8px 15px;border-radius:5px;cursor:pointer;">Print Receipt</button></div>';


            } catch(PDOException $e) {

                $conn->rollBack();

                $message = '<div class="error">
                            Error: ' . $e->getMessage() . '
                            </div>';
            }
        }

    } else {

        try {
            $sql = "INSERT INTO `Transaction` (transaction_id, medicine_id, batch_id, patient_id, transaction_type, transaction_date, quantity, unit_price, handled_by, payment_method)
                    VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)";
            $stmt = $conn->prepare($sql);

            $stmt->execute([
                $transaction_id,
                $medicine_id,
                $batch_id,
                $patient_id,
                $transaction_type,
                $quantity,
                $unit_price,
                $handled_by,
                $payment_method
            ]);

            $message = '<div class="success">
                        Transaction successful! ID: ' . $transaction_id . '
                        </div>';

        } catch(PDOException $e) {

            $message = '<div class="error">
                        Error: ' . $e->getMessage() . '
                        </div>';
        }
    }
}

/* =========================================================
   AJAX FOR BATCHES
   ========================================================= */
if(isset($_GET['get_batches']) && isset($_GET['medicine_id'])) {

    $medicine_id = $_GET['medicine_id'];
    $stmt = $conn->prepare("SELECT batch_id, batch_number, qty_remaining, expiry_date FROM Batch WHERE medicine_id = ? AND batch_status = 'Active' AND qty_remaining > 0 AND expiry_date > CURDATE() ORDER BY expiry_date ASC");
    $stmt->execute([$medicine_id]);

    $batches = $stmt->fetchAll();

    echo '<option value="">-- Select Batch (FEFO) --</option>';

    foreach($batches as $batch) {

        echo '<option value="' . $batch['batch_id'] . '">
                ' . $batch['batch_number'] . ' -
                ' . $batch['qty_remaining'] . ' units left
                (Exp: ' . date('d/m/Y', strtotime($batch['expiry_date'])) . ')
              </option>';
    }

    if(count($batches) == 0) {
        echo '<option value="">No active batches available</option>';
    }

    exit;
}


$medicines = $conn->query("SELECT medicine_id, medicine_name, unit_price FROM Medicine WHERE status = 'Active' ORDER BY medicine_name")->fetchAll();
$patients  = $conn->query("SELECT patient_id, full_name FROM Patient ORDER BY full_name")->fetchAll();
$recent    = $conn->query("SELECT t.*, m.medicine_name FROM `Transaction` t JOIN Medicine m ON t.medicine_id = m.medicine_id ORDER BY t.transaction_date DESC LIMIT 20")->fetchAll();
?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Transaction - SMMAS</title>

<link rel="stylesheet" href="css/style.css">

<script src="https://code.jquery.com/jquery-1.7.2.min.js"></script>

<style>

.form-container{
    background:white;
    padding:20px;
    border-radius:10px;
    margin-bottom:20px;
}

.form-row{
    margin-bottom:15px;
}

.form-row label{
    display:inline-block;
    width:150px;
    font-weight:bold;
}

.form-row input,
.form-row select{
    width:250px;
    padding:8px;
    border:1px solid #ddd;
    border-radius:5px;
}

.btn-primary{
    background:#0A4F6E;
    color:white;
    padding:10px 20px;
    border:none;
    border-radius:5px;
    cursor:pointer;
}

.total-box{
    background:#0A4F6E;
    color:white;
    padding:15px;
    text-align:center;
    border-radius:5px;
    margin:15px 0;
}

.data-table{
    width:100%;
    border-collapse:collapse;
    background:white;
}

.data-table th{
    background:#34495e;
    color:white;
    padding:12px;
    text-align:left;
}

.data-table td{
    padding:10px;
    border-bottom:1px solid #ddd;
}

.success{
    background:#d4edda;
    color:#155724;
    padding:15px;
    border-radius:5px;
    margin-bottom:20px;
}

.error{
    background:#f8d7da;
    color:#721c24;
    padding:15px;
    border-radius:5px;
    margin-bottom:20px;
}

</style>
</head>

<body>

<?php include 'includes/navbar.php'; ?>

<div class="container">

<h1>Transaction Processing</h1>

<p style="color:#666;margin-bottom:20px">
Transaction IDs are auto-generated:
TXN-20260507-001, TXN-20260507-002...
</p>

<?php echo $message; ?>

<!-- =========================================================
     TRANSACTION FORM
     ========================================================= -->

<div class="form-container">

<h2>➕ New Transaction</h2>

<form method="post" id="transForm">

<input type="hidden"
       name="dispense_duration"
       id="dispenseDuration"
       value="">

<div class="form-row">
    <label>Transaction ID:</label>

    <input type="text"
           value="Auto-generated"
           disabled
           style="background:#f0f0f0;width:300px">
</div>

<div class="form-row">

<label>Transaction Type:</label>

<select name="transaction_type" id="transType">

<option value="Dispense">
💊 Dispense to Patient
</option>

<option value="Restock">
📦 Restock Inventory
</option>

<option value="Return">
🔄 Return from Patient
</option>

</select>
</div>

<div class="form-row">

<label>Medicine:</label>

<select name="medicine_id"
        id="medicine"
        required>

<option value="">-- Select Medicine --</option>

<?php foreach($medicines as $m): ?>

<option value="<?php echo $m['medicine_id']; ?>"
        data-price="<?php echo $m['unit_price']; ?>"
        data-name="<?php echo htmlspecialchars($m['medicine_name']); ?>">

<?php echo $m['medicine_name']; ?>
- UGX <?php echo number_format($m['unit_price']); ?>

</option>

<?php endforeach; ?>

</select>
</div>

<div class="form-row">

<label>Batch (FEFO):</label>

<select name="batch_id"
        id="batch"
        required>

<option value="">
-- First select a medicine --
</option>

</select>

<small>FEFO = First Expiry, First Out</small>

</div>

<div class="form-row" id="patientRow">

<label>Patient:</label>

<select name="patient_id">

<option value="">-- Select Patient --</option>

<?php foreach($patients as $p): ?>

<option value="<?php echo $p['patient_id']; ?>">

<?php echo $p['full_name']; ?>

</option>

<?php endforeach; ?>

</select>
</div>

<div class="form-row">

<label>Quantity:</label>

<input type="number"
       name="quantity"
       id="quantity"
       min="1"
       required>

</div>

<div class="form-row">

<label>Unit Price (UGX):</label>

<input type="number"
       name="unit_price"
       id="price"
       readonly
       style="background:#f0f0f0">

</div>

<div class="form-row" id="paymentRow">

<label>Payment Method:</label>

<select name="payment_method">
<option>Cash</option>
<option>Insurance</option>
<option>Waived</option>
</select>

</div>

<div class="total-box">
💰 TOTAL: UGX <span id="totalAmt">0</span>
</div>

<input type="submit"
       name="process_transaction"
       value="✅ Process Transaction"
       class="btn-primary">

</form>
</div>

<!-- =========================================================
     RECENT TRANSACTIONS
     ========================================================= -->

<div class="data-table-container">

<h2>📋 Recent Transactions</h2>

<table class="data-table">
<thead><tr><th>ID</th><th>Medicine</th><th>Type</th><th>Qty</th><th>Amount</th><th>Staff</th><th>Date</th></tr></thead>
<tbody>
<?php foreach($recent as $r): $total = $r['quantity'] * $r['unit_price']; ?>
<tr>
  <td style="font-family:monospace"><?php echo $r['transaction_id']; ?></td>
  <td><?php echo $r['medicine_name']; ?></td>
  <td><?php echo $r['transaction_type']; ?></td>
  <td><?php echo $r['quantity']; ?></td>
  <td>UGX <?php echo number_format($total); ?></td>
  <td><?php echo $r['handled_by']; ?></td>
  <td><?php echo date('d M Y H:i', strtotime($r['transaction_date'])); ?></td>
</tr>
<?php endforeach; ?>
</tbody>
</table>
</div>

</div><!-- /.container -->

<!-- ════════════════════════════════════════════
     SCRIPTS
     ════════════════════════════════════════════ -->
<script>
/* ── Existing transaction form JS ── */

    <h2>Recent Transactions</h2>
    <table class="data-table">
        <thead>
            <tr>
                <th>ID</th>
                <th>Medicine</th>
                <th>Type</th>
                <th>Qty</th>
                <th>Amount</th>
                <th>Staff</th>
                <th>Date</th>
            </tr>
        </thead>
        <tbody>
        <?php foreach($recent as $r): $total = $r['quantity'] * $r['unit_price']; ?>
            <tr>
                <td style="font-family:monospace"><?php echo $r['transaction_id']; ?></td>
                <td><?php echo $r['medicine_name']; ?></td>
                <td><?php echo $r['transaction_type']; ?></td>
                <td><?php echo $r['quantity']; ?></td>
                <td>UGX <?php echo number_format($total); ?></td>
                <td><?php echo $r['handled_by']; ?></td>
                <td><?php echo date('d M Y H:i', strtotime($r['transaction_date'])); ?></td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

</div>



<script>

$(document).ready(function(){

    /* Medicine selection */
    $('#medicine').change(function(){

        var price = $(this).find(':selected').data('price');

        $('#price').val(price);

        var mid = $(this).val();

        if(mid){

            $.get(
                'transaction.php',
                {
                    get_batches: 1,
                    medicine_id: mid
                },
                function(data){
                    $('#batch').html(data);
                }
            );

        } else {

            $('#batch').html(
                '<option value="">-- First select a medicine --</option>'
            );
        }

        calculateTotal();
    });



    // Recalculate total whenever quantity changes

    $('#quantity').on('input', calculateTotal);

    function calculateTotal(){

        var qty   = $('#quantity').val() || 0;
        var price = $('#price').val() || 0;

        $('#totalAmt').text(
            (qty * price).toLocaleString()
        );
    }



    // Show/hide patient and payment fields based on transaction type

    $('#transType').change(function(){

        if($(this).val() == 'Dispense'){

            $('#patientRow').show();
            $('#paymentRow').show();

        } else {

            $('#patientRow').hide();
            $('#paymentRow').hide();
        }

    }).trigger('change');


    /* On form submit: save current elapsed time to hidden field */
    $('#transForm').on('submit', function(){
        $('#dispenseDuration').val(dspElapsed);
        /* Auto-record the dispense in tracker if timer was running */
        if(dspRunning) {
            var medName = $('#medicine').find(':selected').data('name') || 'Unknown';

    // Before form submits: save timer value into hidden field
    // and auto-record the dispense in the tracker
    $('#transForm').on('submit', function(){
        $('#dispenseDuration').val(dspElapsed);
        if(dspRunning) {
            var medName = $('#medicine').find(':selected').data('name') || 'Unknown Medicine';

            dspRecordEntry(medName, dspElapsed);
            dspReset();
        }
    });
});

/* ── Dispense Speed Tracker JS ── */
var dspInterval  = null;
var dspStartTime = null;
var dspElapsed   = 0;
var dspRunning   = false;
var dspRecords   = [];
var dspMedCounts = {};
var dspCounter   = 0;
var dspChart     = null;

function dspFmt(sec) {
    var m = Math.floor(sec / 60);
    var s = sec % 60;
    return String(m).padStart(2,'0') + ':' + String(s).padStart(2,'0');
}

function dspStart() {
    if(dspRunning) return;
    dspRunning   = true;
    dspStartTime = Date.now();
    document.getElementById('btnStart').disabled = true;
    document.getElementById('btnStop').disabled  = false;
    document.getElementById('timerBadge').textContent  = 'active';
    document.getElementById('timerBadge').className    = 'dsp-status-badge active';
    document.getElementById('dspHint').innerHTML = 'Timer running — fill and submit the form below, then click <strong>Record &amp; Stop</strong>.';


// Global variables to track state
var dspInterval   = null;   // holds the setInterval reference (the ticking clock)
var dspStartTime  = null;   // exact millisecond when Start was clicked
var dspElapsed    = 0;      // number of seconds counted so far
var dspRunning    = false;  // is the timer currently running?
var dspRecords    = [];     // list of all completed dispense records this session
var dspMedCounts  = {};     // dictionary: medicine name -> how many times dispensed
var dspCounter    = 0;      // total number of dispenses recorded
var dspChart      = null;   // Chart.js chart object (created on first record)

// Formats seconds into MM:SS display e.g. 90 becomes 01:30
function dspFmt(sec) {
    var m = Math.floor(sec / 60);
    var s = sec % 60;
    return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
}

// START button clicked - begins timing the dispense
function dspStart() {
    if(dspRunning) return;
    dspRunning   = true;
    dspStartTime = Date.now(); // records current time in milliseconds
    document.getElementById('btnStart').disabled = true;
    document.getElementById('btnStop').disabled  = false;
    document.getElementById('timerBadge').textContent = 'active';
    document.getElementById('timerBadge').className   = 'dsp-badge active';
    document.getElementById('dspHint').innerHTML =
        'Timer is running. Fill in the form below and click <strong>Process Transaction</strong>, then press <strong>Record and Stop</strong>.';

    // setInterval fires every 500ms (half a second) to update the display

    dspInterval = setInterval(function(){
        dspElapsed = Math.round((Date.now() - dspStartTime) / 1000);
        document.getElementById('timerDisplay').textContent = dspFmt(dspElapsed);
    }, 500);
}


function dspStop() {
    if(!dspRunning) return;
    clearInterval(dspInterval);
    dspRunning = false;
    var duration = dspElapsed;
    var medName  = $('#medicine').find(':selected').data('name') || ('Dispense #' + (dspCounter + 1));
    dspRecordEntry(medName, duration);
    dspReset();
    document.getElementById('timerBadge').textContent = 'recorded';
    document.getElementById('timerBadge').className   = 'dsp-status-badge done';
    document.getElementById('dspHint').innerHTML = '✅ Dispense recorded! Press <strong>Start Dispense</strong> for the next one.';
}


// RECORD AND STOP button clicked - saves the dispense record
function dspStop() {
    if(!dspRunning) return;
    clearInterval(dspInterval); // stops the ticking
    dspRunning = false;
    var duration = dspElapsed;
    var medName  = $('#medicine').find(':selected').data('name') || ('Dispense ' + (dspCounter + 1));
    dspRecordEntry(medName, duration);
    document.getElementById('timerBadge').textContent = 'recorded';
    document.getElementById('timerBadge').className   = 'dsp-badge done';
    document.getElementById('dspHint').innerHTML =
        'Dispense recorded successfully! Press <strong>Start Dispense</strong> for the next patient.';
    dspElapsed = 0;
    document.getElementById('btnStart').disabled = false;
    document.getElementById('btnStop').disabled  = true;
}

// RESET button - clears the timer back to 00:00

function dspReset() {
    clearInterval(dspInterval);
    dspRunning = false;
    dspElapsed = 0;

    document.getElementById('timerDisplay').textContent = '00:00';
    document.getElementById('timerBadge').textContent   = 'idle';
    document.getElementById('timerBadge').className     = 'dsp-status-badge idle';
    document.getElementById('btnStart').disabled = false;
    document.getElementById('btnStop').disabled  = true;
    document.getElementById('dspHint').innerHTML = 'Press <strong>Start Dispense</strong> when you begin filling the form below, then <strong>Record &amp; Stop</strong> after submitting.';
}

function dspSpeedLabel(sec) {
    if(sec <= 30)  return {cls:'spd-fast', txt:'fast'};
    if(sec <= 90)  return {cls:'spd-avg',  txt:'avg'};
    return {cls:'spd-slow', txt:'slow'};
}

function dspRecordEntry(name, sec) {
    dspCounter++;
    dspRecords.push({name: name, sec: sec, ts: new Date()});

    document.getElementById('timerDisplay').textContent  = '00:00';
    document.getElementById('timerBadge').textContent    = 'idle';
    document.getElementById('timerBadge').className      = 'dsp-badge idle';
    document.getElementById('btnStart').disabled = false;
    document.getElementById('btnStop').disabled  = true;
    document.getElementById('dspHint').innerHTML =
        'Press <strong>Start Dispense</strong> when you begin filling the form below, then press <strong>Record and Stop</strong> after clicking Process Transaction.';
}

// Returns speed label and CSS class based on how many seconds it took
function dspSpeedLabel(sec) {
    if(sec <= 30) return { cls: 'spd-quick',   txt: 'Quick'   };
    if(sec <= 90) return { cls: 'spd-normal',  txt: 'Normal'  };
    return            { cls: 'spd-delayed', txt: 'Delayed' };
}

// Saves a dispense record and updates all panels
function dspRecordEntry(name, sec) {
    dspCounter++;
    dspRecords.push({ name: name, sec: sec, ts: new Date() });

    dspMedCounts[name] = (dspMedCounts[name] || 0) + 1;
    dspUpdateStats();
    dspUpdateLog();
    dspUpdateChart();
    document.getElementById('dspChartPanel').style.display = '';
}



// Recalculates and updates the 4 summary stat cards

function dspUpdateStats() {
    var n = dspRecords.length;
    document.getElementById('statCount').textContent = n;
    if(n === 0) {

        document.getElementById('statAvg').textContent  = '—';
        document.getElementById('statFast').textContent = '—';
        document.getElementById('statRate').textContent = '—';
        return;
    }
    var total = dspRecords.reduce(function(a,r){ return a + r.sec; }, 0);

        document.getElementById('statAvg').textContent  = '--';
        document.getElementById('statFast').textContent = '--';
        document.getElementById('statRate').textContent = '--';
        return;
    }
    var total = dspRecords.reduce(function(a, r){ return a + r.sec; }, 0);

    var avg   = Math.round(total / n);
    var fast  = Math.min.apply(null, dspRecords.map(function(r){ return r.sec; }));
    var sessionSec = (dspRecords[n-1].ts - dspRecords[0].ts) / 1000 + dspRecords[0].sec;
    var rate  = sessionSec > 0 ? Math.round((n / sessionSec) * 3600) : 0;
    document.getElementById('statAvg').textContent  = avg;
    document.getElementById('statFast').textContent = fast;
    document.getElementById('statRate').textContent = rate;
}


function dspUpdateLog() {
    var ul   = document.getElementById('dspLog');
    ul.innerHTML = '';
    var shown = dspRecords.slice(-15).reverse();
    shown.forEach(function(r, i){
        var sp  = dspSpeedLabel(r.sec);
        var li  = document.createElement('li');
        li.innerHTML =
            '<span class="log-name">' + r.name + '</span>' +
            '<span class="log-meta">' +
                '<span>' + r.sec + 's</span>' +

// Redraws the dispense log list (shows last 15 records, newest first)
function dspUpdateLog() {
    var ul    = document.getElementById('dspLog');
    ul.innerHTML = '';
    var shown = dspRecords.slice(-15).reverse();
    shown.forEach(function(r){
        var sp = dspSpeedLabel(r.sec);
        var li = document.createElement('li');
        li.innerHTML =
            '<span class="log-name">' + r.name + '</span>' +
            '<span class="log-meta">' +
                '<span style="color:#555">' + r.sec + ' sec</span>' +

                '<span class="dsp-speed-badge ' + sp.cls + '">' + sp.txt + '</span>' +
            '</span>';
        ul.appendChild(li);
    });
}


function dspUpdateChart() {
    var last10  = dspRecords.slice(-10);
    var labels  = last10.map(function(_, i){ return '#' + (dspRecords.length - last10.length + i + 1); });

// Draws/updates the Chart.js bar chart and medicine volume bars
function dspUpdateChart() {
    var last10  = dspRecords.slice(-10);
    var labels  = last10.map(function(_, i){ return 'No.' + (dspRecords.length - last10.length + i + 1); });

    var data    = last10.map(function(r){ return r.sec; });
    var colors  = data.map(function(s){ return s <= 30 ? '#1a7a4a' : s <= 90 ? '#b8860b' : '#c0392b'; });

    if(!dspChart) {
        var ctx = document.getElementById('dspSpeedChart').getContext('2d');
        dspChart = new Chart(ctx, {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{

                    label: 'Duration (sec)',

                    label: 'Duration (seconds)',

                    data: data,
                    backgroundColor: colors,
                    borderRadius: 4,
                    borderSkipped: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false },

                    tooltip: { callbacks: { label: function(c){ return c.parsed.y + 's'; } } }

                    tooltip: {
                        callbacks: {
                            label: function(c){ return c.parsed.y + ' seconds'; }
                        }
                    }

                },
                scales: {
                    y: {
                        beginAtZero: true,

                        ticks: { callback: function(v){ return v + 's'; }, font: { size: 11 } },

                        ticks: {
                            callback: function(v){ return v + 's'; },
                            font: { size: 11 }
                        },

                        grid: { color: 'rgba(0,0,0,0.05)' }
                    },
                    x: {
                        ticks: { font: { size: 11 } },
                        grid: { display: false }
                    }
                }
            }
        });
    } else {
        dspChart.data.labels = labels;

        dspChart.data.datasets[0].data   = data;

        dspChart.data.datasets[0].data = data;

        dspChart.data.datasets[0].backgroundColor = colors;
        dspChart.update();
    }


    /* Medicine volume bars */
    var barsEl = document.getElementById('dspMedBars');
    var sorted = Object.entries(dspMedCounts).sort(function(a,b){ return b[1]-a[1]; }).slice(0,5);
    if(sorted.length === 0) { barsEl.innerHTML = '<p class="dsp-empty">No data yet.</p>'; return; }
    var max = sorted[0][1];
    barsEl.innerHTML = sorted.map(function(entry){
        var name = entry[0], cnt = entry[1];
        var pct = Math.round((cnt / max) * 100);

    // Medicine volume bars
    var barsEl = document.getElementById('dspMedBars');
    var sorted = Object.entries(dspMedCounts).sort(function(a, b){ return b[1] - a[1]; }).slice(0, 5);
    if(sorted.length === 0) {
        barsEl.innerHTML = '<p class="dsp-empty">No data yet.</p>';
        return;
    }
    var max = sorted[0][1];
    barsEl.innerHTML = sorted.map(function(entry){
        var name = entry[0], cnt = entry[1];
        var pct  = Math.round((cnt / max) * 100);

        return '<div class="dsp-bar-row">' +
            '<span class="dsp-bar-label" title="' + name + '">' + name + '</span>' +
            '<div class="dsp-bar-track"><div class="dsp-bar-fill" style="width:' + pct + '%"></div></div>' +
            '<span class="dsp-bar-count">' + cnt + '</span>' +
        '</div>';
    }).join('');
}
</script>

</body>
</html>