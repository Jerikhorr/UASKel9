<?php
require_once 'db_connect.php';

// Fungsi untuk membersihkan input dari pengguna
function sanitize($data) {
    // Menghapus spasi yang tidak perlu dari awal dan akhir
    $data = trim($data);
    // Menghapus karakter backslashes (\) yang mungkin ada
    $data = stripslashes($data);
    // Mengkonversi karakter khusus menjadi entitas HTML
    $data = htmlspecialchars($data);
    return $data;
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function isAdmin() {
    return isset($_SESSION['is_admin']) && $_SESSION['is_admin'] == true;
}

function redirectTo($location) {
    header("Location: " . SITE_URL . $location);
    exit();
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function uploadImage($file) {
    $target_dir = UPLOAD_DIR;
    $target_file = $target_dir . basename($file["name"]);
    $imageFileType = strtolower(pathinfo($target_file, PATHINFO_EXTENSION));
    
    // Check if image file is an actual image or fake image
    $check = getimagesize($file["tmp_name"]);
    if ($check === false) {
        return "File is not an image.";
    }
    
    // Check file size
    if ($file["size"] > 500000) {
        return "Sorry, your file is too large.";
    }
    
    // Allow certain file formats
    if ($imageFileType != "jpg" && $imageFileType != "png" && $imageFileType != "jpeg" && $imageFileType != "gif") {
        return "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
    }
    
    if (move_uploaded_file($file["tmp_name"], $target_file)) {
        return basename($file["name"]);
    } else {
        return "Sorry, there was an error uploading your file.";
    }
}
?>
