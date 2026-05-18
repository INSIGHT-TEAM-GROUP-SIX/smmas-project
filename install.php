<?php
require_once 'config/database.php';

try {
    $conn = getConnection();
    $conn->exec("CREATE DATABASE IF NOT EXISTS smmas_db");
    $conn->exec("USE smmas_db");
    
    $tables = [
        "CREATE TABLE IF NOT EXISTS users (
            user_id VARCHAR(20) PRIMARY KEY,
            username VARCHAR(50) UNIQUE NOT NULL,
            password_hash VARCHAR(255) NOT NULL,
            full_name VARCHAR(150) NOT NULL,
            email VARCHAR(100) NOT NULL,
            role ENUM('Admin', 'Pharmacist', 'Doctor', 'Nurse', 'Receptionist', 'Cashier') NOT NULL,
            is_active BOOLEAN DEFAULT TRUE,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            last_login DATETIME NULL
        )",
        "CREATE TABLE IF NOT EXISTS Medicine (
            medicine_id VARCHAR(20) PRIMARY KEY,
            medicine_name VARCHAR(100) NOT NULL,
            category VARCHAR(50) NOT NULL,
            dosage_form VARCHAR(30) NOT NULL,
            strength VARCHAR(20) NOT NULL,
            unit_of_measure VARCHAR(20) NOT NULL,
            reorder_level INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            status ENUM('Active','Discontinued','Low Stock','Stockout') NOT NULL DEFAULT 'Active'
        )",
        "CREATE TABLE IF NOT EXISTS Batch (
            batch_id VARCHAR(20) PRIMARY KEY,
            medicine_id VARCHAR(20) NOT NULL,
            batch_number VARCHAR(50) NOT NULL,
            supplier_name VARCHAR(100) NOT NULL,
            date_received DATE NOT NULL,
            expiry_date DATE NOT NULL,
            qty_received INT NOT NULL,
            qty_remaining INT NOT NULL,
            unit_cost DECIMAL(10,2),
            batch_status ENUM('Active','Depleted','Expired','Recalled') NOT NULL DEFAULT 'Active',
            FOREIGN KEY (medicine_id) REFERENCES Medicine(medicine_id) ON DELETE RESTRICT
        )",
        "CREATE TABLE IF NOT EXISTS Patient (
            patient_id VARCHAR(20) PRIMARY KEY,
            full_name VARCHAR(150) NOT NULL,
            date_of_birth DATE NOT NULL,
            gender ENUM('Male','Female','Other') NOT NULL,
            phone_number VARCHAR(20),
            patient_type ENUM('Student','Staff','Community') NOT NULL,
            date_registered DATE NOT NULL,
            blood_group VARCHAR(5),
            known_allergies TEXT
        )",
        "CREATE TABLE IF NOT EXISTS `Transaction` (
            transaction_id VARCHAR(25) PRIMARY KEY,
            medicine_id VARCHAR(20) NOT NULL,
            batch_id VARCHAR(20) NOT NULL,
            patient_id VARCHAR(20),
            transaction_type ENUM('Dispense','Restock','Return','Disposal') NOT NULL,
            transaction_date DATETIME NOT NULL,
            quantity INT NOT NULL,
            unit_price DECIMAL(10,2) NOT NULL,
            handled_by VARCHAR(100) NOT NULL,
            payment_method ENUM('Cash','Insurance','Waived'),
            FOREIGN KEY (medicine_id) REFERENCES Medicine(medicine_id),
            FOREIGN KEY (batch_id) REFERENCES Batch(batch_id),
            FOREIGN KEY (patient_id) REFERENCES Patient(patient_id)
        )",
        "CREATE TABLE IF NOT EXISTS Alert (
            alert_id VARCHAR(25) PRIMARY KEY,
            medicine_id VARCHAR(20) NOT NULL,
            batch_id VARCHAR(20),
            alert_type ENUM('Expiry Warning','Low Stock','Stockout') NOT NULL,
            severity_level ENUM('Critical','Warning','Info') NOT NULL,
            alert_status ENUM('Active','Acknowledged','Resolved') NOT NULL DEFAULT 'Active',
            date_generated DATETIME NOT NULL,
            date_resolved DATETIME,
            threshold_value INT,
            alert_message TEXT NOT NULL,
            FOREIGN KEY (medicine_id) REFERENCES Medicine(medicine_id),
            FOREIGN KEY (batch_id) REFERENCES Batch(batch_id)
        )"
    ];
    
    foreach($tables as $sql) {
        $conn->exec($sql);
    }
    
    $hashed = password_hash('Admin@123', PASSWORD_DEFAULT);
    $stmt = $conn->prepare("INSERT IGNORE INTO users VALUES ('USR-0001', 'admin', ?, 'System Administrator', 'admin@smmas.com', 'Admin', 1, NOW(), NULL)");
    $stmt->execute([$hashed]);
    
    echo "<h2 style=\"color:green\">✅ Installation Complete!</h2>";
    echo "<p><strong>Login:</strong> admin / Admin@123</p>";
    echo "<p><a href=\"login.php\">Go to Login →</a></p>";
} catch(PDOException $e) {
    echo "<h2 style=\"color:red\">❌ Error: " . $e->getMessage() . "</h2>";
}
?>