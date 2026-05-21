<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
checkAuth();

$conn = getConnection();
$message = '';

// Handle transaction submission
if(isset($_POST['process_transaction'])) {
    $transaction_id = generateTransactionID($conn);
    $medicine_id = $_POST['medicine_id'];
    $batch_id = $_POST['batch_id'];
    $patient_id = !empty($_POST['patient_id']) ? $_POST['patient_id'] : null;
    $transaction_type = $_POST['transaction_type'];
    $quantity = $_POST['quantity'];
    $unit_price = $_POST['unit_price'];
    $handled_by = $_SESSION['full_name'];
    $payment_method = $_POST['payment_method'] ?? null;
    $dispense_duration = isset($_POST['dispense_duration']) ? (int)$_POST['dispense_duration'] : null;
    $medicine_name_for_tracker = '';

    if($transaction_type == 'Dispense') {
        $check = $conn->prepare("SELECT qty_remaining FROM Batch WHERE batch_id = ?");
        $check->execute([$batch_id]);
        $available = $check->fetch()['qty_remaining'];

        if($available < $quantity) {
            $message = '<div class="error">❌ Insufficient stock! Only ' . $available . ' units available.</div>';
        } else {
            try {
                $conn->beginTransaction();

                $sql = "INSERT INTO `Transaction` (transaction_id, medicine_id, batch_id, patient_id, transaction_type, transaction_date, quantity, unit_price, handled_by, payment_method)
                        VALUES (?, ?, ?, ?, ?, NOW(), ?, ?, ?, ?)";
                $stmt = $conn->prepare($sql);
                $stmt->execute([$transaction_id, $medicine_id, $batch_id, $patient_id, $transaction_type, $quantity, $unit_price, $handled_by, $payment_method]);

                $update = $conn->prepare("UPDATE Batch SET qty_remaining = qty_remaining - ? WHERE batch_id = ?");
                $update->execute([$quantity, $batch_id]);

                $update_med = $conn->prepare("UPDATE Medicine SET current_stock = current_stock - ? WHERE medicine_id = ?");
                $update_med->execute([$quantity, $medicine_id]);

                $conn->commit();

                $med_name = $conn->prepare("SELECT medicine_name FROM Medicine WHERE medicine_id = ?");
                $med_name->execute([$medicine_id]);
                $medicine_name_for_tracker = $med_name->fetch()['medicine_name'];

                $message = '<div class="success">✅ Transaction successful!<br>
                            ID: ' . $transaction_id . '<br>
                            Medicine: ' . $medicine_name_for_tracker . '<br>
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
            $sql = "INSERT INTO `Transaction` (transaction_id, medicine_id, batch_id, patient_id, transaction_type, transaction_date, quantity, unit_price, handled_by, payment_method)
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
    $stmt = $conn->prepare("SELECT batch_id, batch_number, qty_remaining, expiry_date FROM Batch WHERE medicine_id = ? AND batch_status = 'Active' AND qty_remaining > 0 AND expiry_date > CURDATE() ORDER BY expiry_date ASC");
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

$medicines = $conn->query("SELECT medicine_id, medicine_name, unit_price FROM Medicine WHERE status = 'Active' ORDER BY medicine_name")->fetchAll();
$patients  = $conn->query("SELECT patient_id, full_name FROM Patient ORDER BY full_name")->fetchAll();
$recent    = $conn->query("SELECT t.*, m.medicine_name FROM `Transaction` t JOIN Medicine m ON t.medicine_id = m.medicine_id ORDER BY t.transaction_date DESC LIMIT 20")->fetchAll();
?>
<!DOCTYPE html>
<html>
<head>
<title>Transaction - SMMAS</title>
<link rel="stylesheet" href="css/style.css">
<script src="https://code.jquery.com/jquery-1.7.2.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/Chart.js/4.4.1/chart.umd.js"></script>
<style>
/* ── existing styles ── */
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

/* ── Dispense Speed Tracker styles ── */
.dsp-section{margin-bottom:30px}
.dsp-heading{font-size:18px;font-weight:bold;color:#0A4F6E;margin-bottom:15px;display:flex;align-items:center;gap:8px}
.dsp-stats-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(140px,1fr));gap:12px;margin-bottom:20px}
.dsp-stat-card{background:#f0f7fb;border-radius:8px;padding:15px;border-left:4px solid #0A4F6E}
.dsp-stat-label{font-size:12px;color:#555;margin:0 0 4px}
.dsp-stat-value{font-size:26px;font-weight:bold;color:#0A4F6E;margin:0}
.dsp-stat-unit{font-size:11px;color:#777}
.dsp-panel{background:white;border:1px solid #ddd;border-radius:10px;padding:20px;margin-bottom:15px}
.dsp-panel-title{font-size:14px;font-weight:bold;color:#34495e;margin:0 0 15px;display:flex;align-items:center;gap:6px}
.dsp-timer-display{font-size:48px;font-weight:bold;color:#0A4F6E;font-family:monospace;letter-spacing:3px}
.dsp-status-badge{display:inline-block;font-size:12px;padding:4px 12px;border-radius:20px;margin-left:12px;vertical-align:middle}
.dsp-status-badge.idle{background:#e9ecef;color:#555}
.dsp-status-badge.active{background:#d4edda;color:#155724}
.dsp-status-badge.done{background:#cce5ff;color:#004085}
.dsp-btn{background:#0A4F6E;color:white;border:none;padding:9px 18px;border-radius:5px;cursor:pointer;font-size:13px;margin-right:8px;margin-top:10px}
.dsp-btn:disabled{background:#aaa;cursor:not-allowed}
.dsp-btn.secondary{background:white;color:#0A4F6E;border:1px solid #0A4F6E}
.dsp-hint{font-size:13px;color:#666;margin-top:10px}
.dsp-log{list-style:none;margin:0;padding:0;max-height:220px;overflow-y:auto}
.dsp-log li{display:flex;justify-content:space-between;align-items:center;padding:9px 0;border-bottom:1px solid #f0f0f0;font-size:13px}
.dsp-log li:last-child{border-bottom:none}
.dsp-log .log-name{color:#222;font-weight:bold}
.dsp-log .log-meta{display:flex;align-items:center;gap:8px;color:#555}
.dsp-speed-badge{font-size:11px;padding:2px 9px;border-radius:20px;font-weight:bold}
.spd-fast{background:#d4edda;color:#155724}
.spd-avg{background:#fff3cd;color:#856404}
.spd-slow{background:#f8d7da;color:#721c24}
.dsp-bar-row{display:flex;align-items:center;gap:10px;margin-bottom:8px}
.dsp-bar-label{font-size:12px;color:#555;width:120px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis}
.dsp-bar-track{flex:1;height:9px;background:#e9ecef;border-radius:99px;overflow:hidden}
.dsp-bar-fill{height:100%;border-radius:99px;background:#0A4F6E;transition:width 0.4s ease}
.dsp-bar-count{font-size:12px;color:#555;min-width:18px;text-align:right}
.dsp-empty{font-size:13px;color:#999;text-align:center;padding:20px 0}
.dsp-sep{height:1px;background:#eee;margin:15px 0}
</style>
</head>
<body>
<?php include 'includes/navbar.php'; ?>
<div class="container">
<h1>💰 Transaction Processing</h1>
<p style="color:#666;margin-bottom:20px">Transaction IDs are auto-generated: TXN-20260507-001, TXN-20260507-002...</p>
<?php echo $message; ?>

<!-- ════════════════════════════════════════════
     DISPENSE SPEED TRACKER
     ════════════════════════════════════════════ -->
<div class="dsp-section">
  <div class="dsp-heading">⏱️ Dispense Speed Tracker</div>

  <!-- Summary stats -->
  <div class="dsp-stats-grid">
    <div class="dsp-stat-card">
      <p class="dsp-stat-label">Session dispenses</p>
      <p class="dsp-stat-value" id="statCount">0</p>
    </div>
    <div class="dsp-stat-card">
      <p class="dsp-stat-label">Avg. speed</p>
      <p class="dsp-stat-value" id="statAvg">—</p>
      <span class="dsp-stat-unit">sec / dispense</span>
    </div>
    <div class="dsp-stat-card">
      <p class="dsp-stat-label">Fastest</p>
      <p class="dsp-stat-value" id="statFast">—</p>
      <span class="dsp-stat-unit">sec</span>
    </div>
    <div class="dsp-stat-card">
      <p class="dsp-stat-label">Rate</p>
      <p class="dsp-stat-value" id="statRate">—</p>
      <span class="dsp-stat-unit">dispenses / hr</span>
    </div>
  </div>

  <!-- Live timer panel -->
  <div class="dsp-panel">
    <div class="dsp-panel-title">🕐 Live Timer</div>
    <div>
      <span class="dsp-timer-display" id="timerDisplay">00:00</span>
      <span class="dsp-status-badge idle" id="timerBadge">idle</span>
    </div>
    <div>
      <button class="dsp-btn" id="btnStart" onclick="dspStart()">▶ Start Dispense</button>
      <button class="dsp-btn" id="btnStop" onclick="dspStop()" disabled>⏹ Record &amp; Stop</button>
      <button class="dsp-btn secondary" onclick="dspReset()">↺ Reset</button>
    </div>
    <p class="dsp-hint" id="dspHint">
      Press <strong>Start Dispense</strong> when you begin filling the form below, then <strong>Record &amp; Stop</strong> after submitting.
    </p>
  </div>

  <!-- Log panel -->
  <div class="dsp-panel">
    <div class="dsp-panel-title">📋 Dispense Log <span style="font-weight:normal;color:#999;font-size:12px">(this session)</span></div>
    <ul class="dsp-log" id="dspLog">
      <li><span class="dsp-empty" style="width:100%">No dispenses recorded yet.</span></li>
    </ul>
  </div>

  <!-- Chart panel (hidden until first record) -->
  <div class="dsp-panel" id="dspChartPanel" style="display:none">
    <div class="dsp-panel-title">📊 Speed Trend (last 10 dispenses)</div>
    <div style="position:relative;width:100%;height:200px">
      <canvas id="dspSpeedChart" role="img" aria-label="Bar chart showing dispense duration in seconds per recorded transaction.">Dispense speed trend.</canvas>
    </div>
    <div class="dsp-sep"></div>
    <div class="dsp-panel-title">💊 Medicines Dispensed (by volume)</div>
    <div id="dspMedBars"><p class="dsp-empty">No data yet.</p></div>
  </div>
</div>
<!-- ════════════════════════════════════════════
     END DISPENSE SPEED TRACKER
     ════════════════════════════════════════════ -->

<!-- Transaction Form -->
<div class="form-container">
<h2>➕ New Transaction</h2>
<form method="post" id="transForm">
<!-- Hidden field: captures dispense duration from the tracker timer -->
<input type="hidden" name="dispense_duration" id="dispenseDuration" value="">

<div class="form-row"><label>Transaction ID:</label><input type="text" value="Auto-generated" disabled style="background:#f0f0f0;width:300px"></div>
<div class="form-row"><label>Transaction Type:</label><select name="transaction_type" id="transType"><option value="Dispense">💊 Dispense to Patient</option><option value="Restock">📦 Restock Inventory</option><option value="Return">🔄 Return from Patient</option></select></div>
<div class="form-row"><label>Medicine:</label><select name="medicine_id" id="medicine" required><option value="">-- Select Medicine --</option><?php foreach($medicines as $m): ?><option value="<?php echo $m['medicine_id']; ?>" data-price="<?php echo $m['unit_price']; ?>" data-name="<?php echo htmlspecialchars($m['medicine_name']); ?>"><?php echo $m['medicine_name']; ?> - UGX <?php echo number_format($m['unit_price']); ?></option><?php endforeach; ?></select></div>
<div class="form-row"><label>Batch (FEFO):</label><select name="batch_id" id="batch" required><option value="">-- First select a medicine --</option></select><small>FEFO = First Expiry, First Out</small></div>
<div class="form-row" id="patientRow"><label>Patient:</label><select name="patient_id"><option value="">-- Select Patient --</option><?php foreach($patients as $p): ?><option value="<?php echo $p['patient_id']; ?>"><?php echo $p['full_name']; ?></option><?php endforeach; ?></select></div>
<div class="form-row"><label>Quantity:</label><input type="number" name="quantity" id="quantity" min="1" required></div>
<div class="form-row"><label>Unit Price (UGX):</label><input type="number" name="unit_price" id="price" readonly style="background:#f0f0f0"></div>
<div class="form-row" id="paymentRow"><label>Payment Method:</label><select name="payment_method"><option>Cash</option><option>Insurance</option><option>Waived</option></select></div>
<div class="total-box">💰 TOTAL: UGX <span id="totalAmt">0</span></div>
<input type="submit" name="process_transaction" value="✅ Process Transaction" class="btn-primary" id="submitBtn">
</form>
</div>

<!-- Recent Transactions Table -->
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

    /* On form submit: save current elapsed time to hidden field */
    $('#transForm').on('submit', function(){
        $('#dispenseDuration').val(dspElapsed);
        /* Auto-record the dispense in tracker if timer was running */
        if(dspRunning) {
            var medName = $('#medicine').find(':selected').data('name') || 'Unknown';
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
    dspMedCounts[name] = (dspMedCounts[name] || 0) + 1;
    dspUpdateStats();
    dspUpdateLog();
    dspUpdateChart();
    document.getElementById('dspChartPanel').style.display = '';
}

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
                '<span class="dsp-speed-badge ' + sp.cls + '">' + sp.txt + '</span>' +
            '</span>';
        ul.appendChild(li);
    });
}

function dspUpdateChart() {
    var last10  = dspRecords.slice(-10);
    var labels  = last10.map(function(_, i){ return '#' + (dspRecords.length - last10.length + i + 1); });
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
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: { callback: function(v){ return v + 's'; }, font: { size: 11 } },
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