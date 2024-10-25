<?php
session_start();
require '../includes/db_connect.php';

// Dapatkan koneksi database
$conn = getDBConnection();

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['is_admin'] != 1) {
    header('Location: login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch admin data from the 'admin' table
$query = "SELECT * FROM admin WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// If admin not found, redirect to login
if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

// Handle profile update
$update_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];

    // Update admin data
    $query = "UPDATE admin SET name = ?, email = ?" . (!empty($password) ? ", password = ?" : "") . " WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt->bind_param("sssi", $name, $email, $hashed_password, $user_id);
    } else {
        $stmt->bind_param("ssi", $name, $email, $user_id);
    }

    if ($stmt->execute()) {
        $update_success = true;
        // Refresh admin data
        $user['name'] = $name;
        $user['email'] = $email;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-md shadow-md">
        <h1 class="text-2xl font-bold mb-4">Admin Profile</h1>

        <?php if ($update_success): ?>
            <div class="bg-green-100 text-green-800 p-2 rounded mb-4">
                Profile updated successfully.
            </div>
        <?php endif; ?>

        <form action="profile_admin.php" method="POST" class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium">Name:</label>
                <input type="text" id="name" name="name" value="<?= escapeOutput($user['name']) ?>" class="w-full mt-1 p-2 border rounded-md" required disabled>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium">Email:</label>
                <input type="email" id="email" name="email" value="<?= escapeOutput($user['email']) ?>" class="w-full mt-1 p-2 border rounded-md" required disabled>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">Password (Leave blank to keep current password):</label>
                <input type="password" id="password" name="password" class="w-full mt-1 p-2 border rounded-md" disabled>
            </div>

            <button type="button" id="editBtn" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Edit</button>
            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 hidden" id="updateBtn">Update Profile</button>
        </form>

        <div class="mt-4">
            <a href="dashboard_admin.php" class="inline-block bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Back to Admin Dashboard</a>
        </div>
    </div>

    <script>
        document.getElementById('editBtn').addEventListener('click', function() {
            document.querySelectorAll('input').forEach(input => {
                input.disabled = false;
            });
            this.classList.add('hidden');
            document.getElementById('updateBtn').classList.remove('hidden');
        });
    </script>
</body>
</html>
