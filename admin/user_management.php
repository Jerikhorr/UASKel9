<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit();
}

$conn = getDBConnection();

// Get users and their registered events
$query = "SELECT u.id, u.name, u.email, GROUP_CONCAT(e.name SEPARATOR ', ') as registered_events
          FROM users u
          LEFT JOIN registrations r ON u.id = r.user_id
          LEFT JOIN events e ON r.event_id = e.id
          GROUP BY u.id, u.name, u.email
          ORDER BY u.name ASC";
$result = mysqli_query($conn, $query);

$users = [];
while ($row = mysqli_fetch_assoc($result)) {
    $users[] = $row;
}

// Handle delete request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_user_id'])) {
    $user_id = intval($_POST['delete_user_id']);
    $delete_query = "DELETE FROM users WHERE id = ?";
    $stmt = $conn->prepare($delete_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->close();
    
    // Redirect back to the same page
    header("Location: user_management.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function confirmDelete(userId) {
            const modal = document.getElementById('confirmDeleteModal');
            const deleteForm = document.getElementById('deleteForm');
            deleteForm.querySelector('input[name="delete_user_id"]').value = userId;
            modal.classList.remove('hidden');
        }

        function closeModal() {
            const modal = document.getElementById('confirmDeleteModal');
            modal.classList.add('hidden');
        }
    </script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-4">User Management</h1>
        <a href="../admin/dashboard_admin.php" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded mb-4 inline-block">
            Back to Dashboard
        </a>
        <div class="bg-white rounded-lg shadow-lg p-6">
            <table class="w-full">
                <thead>
                    <tr class="bg-gray-50">
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">ID</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Name</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Email</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registered Events</th>
                        <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php foreach ($users as $user): ?>
                    <tr class="hover:bg-gray-50">
                        <td class="px-6 py-4"><?php echo $user['id']; ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($user['name']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($user['email']); ?></td>
                        <td class="px-6 py-4"><?php echo htmlspecialchars($user['registered_events'] ?: 'No events'); ?></td>
                        <td class="px-6 py-4">
                            <button onclick="confirmDelete(<?php echo $user['id']; ?>)" class="bg-red-500 text-white px-3 py-1 rounded hover:bg-red-600">
                                Delete
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal -->
    <div id="confirmDeleteModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4">Confirm Deletion</h2>
            <p>Are you sure you want to delete this user?</p>
            <div class="mt-4">
                <button onclick="closeModal()" class="bg-gray-300 hover:bg-gray-400 px-4 py-2 rounded">Cancel</button>
                <form id="deleteForm" method="POST" class="inline">
                    <input type="hidden" name="delete_user_id" value="">
                    <button type="submit" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded">Yes, Delete</button>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
