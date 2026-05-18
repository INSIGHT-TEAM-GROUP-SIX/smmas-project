<?php
// Authentication Functions - No encryption functions here

function checkAuth() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

function requireRole($roles) {
    if (!isset($_SESSION['role'])) {
        header('Location: login.php');
        exit();
    }
    
    if (!in_array($_SESSION['role'], (array)$roles)) {
        die('<h2>Access Denied</h2><p>You do not have permission to access this page.</p><a href="dashboard.php">Return to Dashboard</a>');
    }
}

function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'Admin';
}

// NO encryption functions here - they are in encryption.php
?>