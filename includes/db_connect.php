<?php
$servername = "localhost";
$username = "root"; // Sesuaikan jika berbeda
$password = ""; // Sesuaikan jika ada password
$database = "tugasevent";

require_once 'config.php';

function getDBConnection() {
    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);
    
    if ($conn->connect_error) {
        die("Connection failed: " . $conn->connect_error);
    }
    
    return $conn;
}

// Function to sanitize user inputs
function sanitizeInput($input) {
    $conn = getDBConnection();
    return $conn->real_escape_string(strip_tags(trim($input)));
}

// Function to prevent XSS
function escapeOutput($output) {
    return htmlspecialchars($output, ENT_QUOTES, 'UTF-8');
}
?>