<?php
// config.php - letakkan di UASKel9/config/config.php

// Database configuration
define('DB_SERVER', 'localhost');
define('DB_USERNAME', 'root');
define('DB_PASSWORD', '');
define('DB_NAME', 'tugasevent'); // Updated to match your database name

// Attempt to connect to MySQL database
try {
    $conn = mysqli_connect(DB_SERVER, DB_USERNAME, DB_PASSWORD, DB_NAME);
    
    // Check connection
    if($conn === false) {
        throw new Exception("ERROR: Could not connect. " . mysqli_connect_error());
    }
} catch (Exception $e) {
    die("Connection failed: " . $e->getMessage());
}

// Set charset to ensure proper character encoding
mysqli_set_charset($conn, "utf8");

// Set timezone
date_default_timezone_set('Asia/Jakarta');
?>