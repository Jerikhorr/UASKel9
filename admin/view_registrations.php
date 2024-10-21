<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/db_connect.php');

// Pastikan pengguna sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit();
}

// Dapatkan koneksi database
$conn = getDBConnection();

// Query untuk mendapatkan registrasi
$query_registrations = "SELECT id, name, email, event_id FROM registrations";
$result_registrations = mysqli_query($conn, $query_registrations);

$registrations = [];
if ($result_registrations) {
    while ($row_registration = mysqli_fetch_assoc($result_registrations)) {
        $registrations[] = $row_registration;
    }
} else {
    echo "Error fetching registrations: " . mysqli_error($conn);
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Registrations</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">View Registrations</h1>
        
        <table class="w-full table-auto bg-white shadow-md rounded">
            <thead>
                <tr class="bg-gray-200">
                    <th class="px-4 py-2">Name</th>
                    <th class="px-4 py-2">Email</th>
                    <th class="px-4 py-2">Event ID</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($registrations)): ?>
                    <?php foreach ($registrations as $registration): ?>
                    <tr class="border-b">
                        <td class="px-4 py-2"><?php echo htmlspecialchars($registration['name']); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($registration['email']); ?></td>
                        <td class="px-4 py-2"><?php echo htmlspecialchars($registration['event_id']); ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3" class="px-4 py-2 text-center">No registrations found.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
        
    </div>
</body>
</html>
