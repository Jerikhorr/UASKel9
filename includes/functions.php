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

/**
 * Enhanced file upload handler with improved security and error handling
 */
function handleFileUpload($file, $upload_dir, $allowed_types = null, $max_size = 5242880) {
    // Set default allowed types if none specified
    if ($allowed_types === null) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    }

    // Validate upload directory
    if (!file_exists($upload_dir)) {
        if (!mkdir($upload_dir, 0755, true)) {
            return ['success' => false, 'error' => 'Upload directory could not be created'];
        }
    }

    // Basic error checking
    if ($file['error'] !== UPLOAD_ERR_OK) {
        $upload_errors = [
            UPLOAD_ERR_INI_SIZE => 'File exceeds PHP maximum file size',
            UPLOAD_ERR_FORM_SIZE => 'File exceeds form maximum file size',
            UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
            UPLOAD_ERR_NO_FILE => 'No file was uploaded',
            UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
            UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
            UPLOAD_ERR_EXTENSION => 'A PHP extension stopped the file upload'
        ];
        return ['success' => false, 'error' => $upload_errors[$file['error']] ?? 'Unknown upload error'];
    }

    // Check file size
    if ($file['size'] > $max_size) {
        return ['success' => false, 'error' => 'File size exceeds maximum allowed size'];
    }

    // Verify MIME type
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime_type = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);

    if (!in_array($mime_type, $allowed_types)) {
        return ['success' => false, 'error' => 'Invalid file type'];
    }

    // Generate unique filename to prevent overwriting
    $file_extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $unique_filename = uniqid() . '_' . time() . '.' . $file_extension;
    $upload_path = $upload_dir . $unique_filename;

    // Attempt to move the file
    if (!move_uploaded_file($file['tmp_name'], $upload_path)) {
        return ['success' => false, 'error' => 'Failed to move uploaded file'];
    }

    // Set proper permissions
    chmod($upload_path, 0644);

    return [
        'success' => true,
        'path' => $upload_path
    ];
}

/**
 * Legacy uploadImage function maintained for backward compatibility
 * Now uses handleFileUpload internally
 */
function uploadImage($file) {
    if (!defined('UPLOAD_DIR')) {
        return "Upload directory not configured.";
    }

    $result = handleFileUpload(
        $file,
        UPLOAD_DIR,
        ['image/jpeg', 'image/png', 'image/gif'],
        500000 // 500KB limit as per original function
    );

    if ($result['success']) {
        return basename($result['path']);
    } else {
        return $result['error'];
    }
}


function getStatusBadgeClass($status) {
    $status_colors = [
        'upcoming' => 'inline-flex px-2 sm:px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-yellow-100 text-yellow-800',
        'active' => 'inline-flex px-2 sm:px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800',
        'completed' => 'inline-flex px-2 sm:px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800',
        'canceled' => 'inline-flex px-2 sm:px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-red-100 text-red-800'
    ];
    
    return $status_colors[strtolower($status)] ?? 'inline-flex px-2 sm:px-3 py-1 text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800';
}


function validateEventId($id) {
    // Ensure $id is properly sanitized and converted to integer
    $clean_id = filter_var($id, FILTER_SANITIZE_NUMBER_INT);
    return filter_var($clean_id, FILTER_VALIDATE_INT, ["options" => ["min_range" => 1]]);
}
function deleteEvent($conn, $event_id) {
    // First validate the event_id
    $validated_id = validateEventId($event_id);
    if ($validated_id === false) {
        return [
            'success' => false,
            'message' => 'Invalid event ID format'
        ];
    }

    // Mulai transaction
    mysqli_begin_transaction($conn);
    try {
        // Get event details for image deletion
        $query = "SELECT id, image, banner FROM events WHERE id = ? LIMIT 1";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $validated_id);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);
        
        if (!$result || mysqli_num_rows($result) === 0) {
            throw new Exception('Event not found');
        }

        $event = mysqli_fetch_assoc($result);
        
        // Delete registrations first (if any)
        $query_reg = "DELETE FROM registrations WHERE event_id = ?";
        $stmt_reg = mysqli_prepare($conn, $query_reg);
        mysqli_stmt_bind_param($stmt_reg, "i", $validated_id);
        if (!mysqli_stmt_execute($stmt_reg)) {
            throw new Exception('Failed to delete registrations');
        }

        // Delete event
        $query = "DELETE FROM events WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "i", $validated_id);
        
        if (mysqli_stmt_execute($stmt)) {
            // Delete associated images
            if (!empty($event['image'])) {
                $image_path = __DIR__ . '/../uploads/images/' . basename($event['image']);
                if (file_exists($image_path)) {
                    unlink($image_path);
                }
            }
            
            if (!empty($event['banner'])) {
                $banner_path = __DIR__ . '/../uploads/banners/' . basename($event['banner']);
                if (file_exists($banner_path)) {
                    unlink($banner_path);
                }
            }
            
            mysqli_commit($conn);
            return [
                'success' => true,
                'message' => 'Event deleted successfully'
            ];
        } else {
            throw new Exception('Failed to delete event');
        }
    } catch (Exception $e) {
        mysqli_rollback($conn);
        return [
            'success' => false,
            'message' => 'Error: ' . $e->getMessage()
        ];
    }
}

