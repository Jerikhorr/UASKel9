<?php
session_start(); // Pastikan session dimulai
require '../includes/db_connect.php';

// Dapatkan koneksi database
$conn = getDBConnection();

// Cek apakah pengguna telah login dan adalah admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../user/login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Ambil data admin dari tabel 'admin'
$query = "SELECT * FROM admin WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$admin = $result->fetch_assoc();

// Jika pengguna tidak ditemukan, arahkan ke login
if (!$admin) {
    session_destroy();
    header('Location: ../user/login.php'); // Perbaiki URL
    exit();
}

// Tangani pembaruan profil
$update_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];

    // Update data admin
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
        // Refresh data admin
        $admin['name'] = $name;
        $admin['email'] = $email;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
    <title>Edit Admin Profile</title>
</head>
<body class="bg-gray-100">

    <nav class="bg-blue-600 shadow-lg">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <div class="flex items-center">
                    <a href="dashboard_admin.php" class="flex items-center">
                        <img src="../logo/logoUAS.png" alt="Logo" class="h-12 w-12 mr-3">
                        <span class="text-white text-xl font-bold">Admin Dashboard</span>
                    </a>
                </div>

                <div class="hidden md:flex items-center space-x-4">
                    <a href="event_management.php" class="text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition duration-150 ease-in-out">Event Management</a>
                    <a href="view_registrations.php" class="text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition duration-150 ease-in-out">View Registrations</a>
                    <a href="profile_admin.php" class="text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition duration-150 ease-in-out">Profile</a>
                    <a href="user_management.php" class="text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition duration-150 ease-in-out">User Management</a>
                    <form action="" method="POST" class="m-0">
                        <button type="submit" name="logout" class="bg-red-500 text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-red-600 transition duration-150 ease-in-out">Logout</button>
                    </form>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-2xl mx-auto bg-white p-6 rounded-md shadow-md mt-6">
        <h1 class="text-2xl font-bold mb-4">Edit Admin Profile</h1>

        <?php if ($update_success): ?>
            <div class="bg-green-100 text-green-800 p-2 rounded mb-4">
                Profile updated successfully.
            </div>
        <?php endif; ?>

        <form action="profile_admin.php" method="POST" class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium">Name:</label>
                <input type="text" id="name" name="name" value="<?= escapeOutput($admin['name']) ?>" class="w-full mt-1 p-2 border rounded-md" required disabled>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium">Email:</label>
                <input type="email" id="email" name="email" value="<?= escapeOutput($admin['email']) ?>" class="w-full mt-1 p-2 border rounded-md" required disabled>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">Password (Leave blank to keep current password):</label>
                <input type="password" id="password" name="password" class="w-full mt-1 p-2 border rounded-md" disabled>
            </div>

            <button type="button" id="editBtn" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Edit</button>
            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 hidden" id="updateBtn">Update Profile</button>
        </form>
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
