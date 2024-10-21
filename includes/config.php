<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'root'); // Ganti ke 'root' jika itu username MySQL kamu
define('DB_PASS', ''); // Kosongkan jika menggunakan default Laragon tanpa password
define('DB_NAME', 'tugasevent'); // Sesuaikan dengan nama database kamu

// Application settings
define('SITE_URL', 'http://localhost/event_registration_system');
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/event_registration_system/uploads/event_images/');

// Security settings
define('HASH_COST', 10); // For password hashing
?>
