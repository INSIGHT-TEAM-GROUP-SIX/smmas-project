<?php
require_once 'config_online.php';

// Database configuration for PDO
if(!defined('DB_HOST')){
define('DB_HOST', 'localhost');
define('DB_NAME', 'smmas_db1');
define('DB_USER', 'root');
define('DB_PASS', '');
}
class Database {
    private static $instance = null;
    private $conn;
    
    private function __construct() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->conn = new PDO($dsn, DB_USER, DB_PASS);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
        } catch(PDOException $e) {
            die("Connection failed: " . $e->getMessage());
        }
    }
    
    public static function getConnection() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance->conn;
    }
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function getConnection() {
    return Database::getConnection();
}

// ============================================
// ID GENERATOR FUNCTIONS
// ============================================

// Generate Medicine ID (MED-0001, MED-0002, MED-0003...)
function generateMedicineID($conn) {
    $stmt = $conn->query("SELECT MAX(CAST(SUBSTRING(medicine_id, 5) AS UNSIGNED)) as max_id FROM Medicine");
    $row = $stmt->fetch();
    $next_id = ($row['max_id'] ?? 0) + 1;
    return 'MED-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);
}

// Generate Batch ID (BAT-0001, BAT-0002, BAT-0003...)
function generateBatchID($conn) {
    $stmt = $conn->query("SELECT MAX(CAST(SUBSTRING(batch_id, 5) AS UNSIGNED)) as max_id FROM Batch");
    $row = $stmt->fetch();
    $next_id = ($row['max_id'] ?? 0) + 1;
    return 'BAT-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);
}

// Generate Patient ID (PAT-0001, PAT-0002, PAT-0003...)
function generatePatientID($conn) {
    $stmt = $conn->query("SELECT MAX(CAST(SUBSTRING(patient_id, 5) AS UNSIGNED)) as max_id FROM Patient");
    $row = $stmt->fetch();
    $next_id = ($row['max_id'] ?? 0) + 1;
    return 'PAT-' . str_pad($next_id, 4, '0', STR_PAD_LEFT);
}

// Generate Transaction ID (TXN-20260501-001, TXN-20260501-002...)
function generateTransactionID($conn) {
    $date = date('Ymd');
    $prefix = 'TXN-' . $date . '-';
    
    $stmt = $conn->prepare("SELECT transaction_id FROM `Transaction` WHERE transaction_id LIKE ? ORDER BY transaction_id DESC LIMIT 1");
    $stmt->execute([$prefix . '%']);
    $last = $stmt->fetch();
    
    if($last) {
        $last_num = intval(substr($last['transaction_id'], -3));
        $next_num = $last_num + 1;
    } else {
        $next_num = 1;
    }
    
    return $prefix . str_pad($next_num, 3, '0', STR_PAD_LEFT);
}
// Generate User ID - Based on COUNT (Always works)
function generateUserID($conn) {
    $stmt = $conn->query("SELECT COUNT(*) as total FROM users");
    $row = $stmt->fetch();
    $next_num = $row['total'] + 1;
    return 'USR-' . str_pad($next_num, 4, '0', STR_PAD_LEFT);
}

// Generate Alert ID (ALT-20260501-001, ALT-20260501-002...)
function generateAlertID($conn) {
    $prefix = 'ALT';
    $date = date('Ymd');
    $stmt = $conn->prepare("SELECT MAX(CAST(SUBSTRING(alert_id, 13) AS UNSIGNED)) as max_id 
                            FROM Alert 
                            WHERE alert_id LIKE ?");
    $stmt->execute([$prefix . '-' . $date . '-%']);
    $row = $stmt->fetch();
    $next_id = ($row['max_id'] ?? 0) + 1;
    return $prefix . '-' . $date . '-' . str_pad($next_id, 3, '0', STR_PAD_LEFT);
}
?>