<?php
// Database configuration
define('DB_HOST', 'localhost');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
define('DB_NAME', 'event_registration_system');

// Application settings
define('SITE_URL', 'http://localhost/event_registration_system');
define('UPLOAD_DIR', $_SERVER['DOCUMENT_ROOT'] . '/event_registration_system/uploads/event_images/');

// Security settings
define('HASH_COST', 10); // For password hashing
?>