<?php
// Complete fresh installation
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h2>SMMAS Fresh Installation</h2>";

try {
    // Connect without database
    $pdo = new PDO("mysql:host=localhost", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Drop and recreate database
    $pdo->exec("DROP DATABASE IF EXISTS smmas_db");
    $pdo->exec("CREATE DATABASE smmas_db");
    $pdo->exec("USE smmas_db");
    echo "? Database created<br>";
    
    // Create users table
    $pdo->exec("CREATE TABLE users (
        user_id VARCHAR(20) PRIMARY KEY,
        username VARCHAR(50) UNIQUE NOT NULL,
        password_hash VARCHAR(255) NOT NULL,
        full_name VARCHAR(150) NOT NULL,
        email VARCHAR(100) NOT NULL,
        role ENUM('Admin','Pharmacist','Doctor','Nurse','Receptionist','Cashier') NOT NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        last_login DATETIME NULL
    )");
    echo "? Users table created<br>";
    
    // Create Medicine table
    $pdo->exec("CREATE TABLE Medicine (
        medicine_id VARCHAR(20) PRIMARY KEY,
        medicine_name VARCHAR(100) NOT NULL,
        category VARCHAR(50) NOT NULL,
        dosage_form VARCHAR(30) NOT NULL,
        strength VARCHAR(20) NOT NULL,
        unit_of_measure VARCHAR(20) NOT NULL,
        reorder_level INT NOT NULL,
        unit_price DECIMAL(10,2) NOT NULL,
        status ENUM('Active','Discontinued','Low Stock','Stockout') DEFAULT 'Active'
    )");
    echo "? Medicine table created<br>";
    
    // Create Batch table
    $pdo->exec("CREATE TABLE Batch (
        batch_id VARCHAR(20) PRIMARY KEY,
        medicine_id VARCHAR(20) NOT NULL,
        batch_number VARCHAR(50) NOT NULL,
        supplier_name VARCHAR(100) NOT NULL,
        date_received DATE NOT NULL,
        expiry_date DATE NOT NULL,
        qty_received INT NOT NULL,
        qty_remaining INT NOT NULL,
        unit_cost DECIMAL(10,2),
        batch_status ENUM('Active','Depleted','Expired','Recalled') DEFAULT 'Active',
        FOREIGN KEY (medicine_id) REFERENCES Medicine(medicine_id)
    )");
    echo "? Batch table created<br>";
    
    // Create Patient table
    $pdo->exec("CREATE TABLE Patient (
        patient_id VARCHAR(20) PRIMARY KEY,
        full_name VARCHAR(150) NOT NULL,
        date_of_birth DATE NOT NULL,
        gender ENUM('Male','Female','Other') NOT NULL,
        phone_number VARCHAR(20),
        patient_type ENUM('Student','Staff','Community') NOT NULL,
        date_registered DATE NOT NULL,
        blood_group VARCHAR(5),
        known_allergies TEXT
    )");
    echo "? Patient table created<br>";
    
    // Create Transaction table
    $pdo->exec("CREATE TABLE `Transaction` (
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
    )");
    echo "? Transaction table created<br>";
    
    // Create Alert table
    $pdo->exec("CREATE TABLE Alert (
        alert_id VARCHAR(25) PRIMARY KEY,
        medicine_id VARCHAR(20) NOT NULL,
        batch_id VARCHAR(20),
        alert_type ENUM('Expiry Warning','Low Stock','Stockout') NOT NULL,
        severity_level ENUM('Critical','Warning','Info') NOT NULL,
        alert_status ENUM('Active','Acknowledged','Resolved') DEFAULT 'Active',
        date_generated DATETIME NOT NULL,
        date_resolved DATETIME,
        threshold_value INT,
        alert_message TEXT NOT NULL,
        FOREIGN KEY (medicine_id) REFERENCES Medicine(medicine_id),
        FOREIGN KEY (batch_id) REFERENCES Batch(batch_id)
    )");
    echo "? Alert table created<br>";
    
    // Insert admin user with correct password
    $password = 'Admin@123';
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("INSERT INTO users (user_id, username, password_hash, full_name, email, role, is_active, created_at) 
                           VALUES (?, ?, ?, ?, ?, ?, 1, NOW())");
    $stmt->execute(['USR-0001', 'admin', $hash, 'System Administrator', 'admin@smmas.com', 'Admin']);
    echo "? Admin user created<br>";
    
    // Insert sample data
    $pdo->exec("INSERT INTO Medicine VALUES
        ('MED-0001', 'Paracetamol', 'Analgesic', 'Tablet', '500mg', 'Tablet', 100, 5000.00, 'Active'),
        ('MED-0002', 'Amoxicillin', 'Antibiotic', 'Capsule', '250mg', 'Capsule', 50, 15000.00, 'Active'),
        ('MED-0003', 'Ibuprofen', 'Analgesic', 'Tablet', '400mg', 'Tablet', 75, 8000.00, 'Active')");
    
    $pdo->exec("INSERT INTO Patient VALUES
        ('PAT-0001', 'John Doe', '1990-05-15', 'Male', '0772123456', 'Student', CURDATE(), 'O+', NULL),
        ('PAT-0002', 'Jane Smith', '1985-08-20', 'Female', '0788123456', 'Staff', CURDATE(), 'A+', 'Penicillin')");
    
    echo "? Sample data inserted<br>";
    
    echo "<hr>";
    echo "<h3 style='color: green'>? INSTALLATION COMPLETE!</h3>";
    echo "<p><strong>Login Credentials:</strong></p>";
    echo "<ul>";
    echo "<li>URL: <a href='login.php'>http://localhost/smmas/login.php</a></li>";
    echo "<li>Username: <strong>admin</strong></li>";
    echo "<li>Password: <strong>Admin@123</strong></li>";
    echo "</ul>";
    echo "<a href='login.php' style='background: #0A4F6E; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px; display: inline-block; margin-top: 10px;'>Go to Login ?</a>";
    
} catch(PDOException $e) {
    echo "<h3 style='color: red'>? Error: " . $e->getMessage() . "</h3>";
}
?>