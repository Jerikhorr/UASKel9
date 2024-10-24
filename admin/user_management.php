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

// Get users and their registered events with additional statistics
$query = "SELECT u.id, u.name, u.email, u.created_at,
          GROUP_CONCAT(e.name SEPARATOR '|||') as registered_events,
          COUNT(DISTINCT r.event_id) as event_count
          FROM users u
          LEFT JOIN registrations r ON u.id = r.user_id
          LEFT JOIN events e ON r.event_id = e.id
          GROUP BY u.id, u.name, u.email
          ORDER BY u.name ASC";
$result = mysqli_query($conn, $query);

$users = [];
$total_users = 0;
$active_users = 0; // Users with at least one event
$total_registrations = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $total_users++;
    if ($row['event_count'] > 0) {
        $active_users++;
    }
    $total_registrations += $row['event_count'];
    // Convert the concatenated events string to an array
    $row['registered_events'] = $row['registered_events'] ? explode('|||', $row['registered_events']) : [];
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .fade-in {
            animation: fadeIn 0.3s ease-in;
        }
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'navbar_admin.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <!-- Header Section -->
        <div class="flex justify-between items-center mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-800">User Management</h1>
                <p class="text-gray-600 mt-2">Manage and monitor user activities</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
            <!-- Total Users Card -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                        <i class="fas fa-users text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 uppercase">Total Users</p>
                        <p class="text-2xl font-semibold text-gray-800"><?php echo $total_users; ?></p>
                    </div>
                </div>
            </div>

            <!-- Active Users Card -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-500">
                        <i class="fas fa-user-check text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 uppercase">Active Users</p>
                        <p class="text-2xl font-semibold text-gray-800"><?php echo $active_users; ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Registrations Card -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                        <i class="fas fa-ticket-alt text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 uppercase">Total Registrations</p>
                        <p class="text-2xl font-semibold text-gray-800"><?php echo $total_registrations; ?></p>
                    </div>
                </div>
            </div>

            <!-- Average Events per User -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-500">
                        <i class="fas fa-chart-line text-2xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 uppercase">Avg Events/User</p>
                        <p class="text-2xl font-semibold text-gray-800">
                            <?php echo $total_users > 0 ? number_format($total_registrations / $total_users, 1) : '0'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
            <div class="p-6 bg-white border-b">
                <div class="flex justify-between items-center">
                    <div>
                        <h2 class="text-xl font-semibold text-gray-800">
                            <i class="fas fa-user-circle mr-2 text-blue-500"></i>
                            User List
                        </h2>
                        <p class="text-sm text-gray-500 mt-1">
                            Manage all registered users and their event participation
                        </p>
                    </div>
                    <div class="relative">
                        <input type="text" id="searchInput" placeholder="Search users..." 
                               class="px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <i class="fas fa-search absolute right-3 top-3 text-gray-400"></i>
                    </div>
                </div>
            </div>
            
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Events</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($users as $user): ?>
                        <tr class="hover:bg-gray-50 transition duration-150">
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                        <span class="text-blue-600 font-semibold">
                                            <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                                        </span>
                                    </div>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                                        <div class="text-sm text-gray-500">ID: #<?php echo $user['id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <i class="fas fa-envelope mr-2 text-gray-400"></i>
                                    <?php echo htmlspecialchars($user['email']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <?php if (empty($user['registered_events'])): ?>
                                        <span class="text-gray-500">No events registered</span>
                                    <?php else: ?>
                                        <div class="flex flex-wrap gap-2">
                                            <?php foreach ($user['registered_events'] as $event): ?>
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                    <?php echo htmlspecialchars($event); ?>
                                                </span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <?php if ($user['event_count'] > 0): ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-100 text-green-800">
                                        Active
                                    </span>
                                <?php else: ?>
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-gray-100 text-gray-800">
                                        Inactive
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm">
                                <button onclick="confirmDelete(<?php echo $user['id']; ?>)" 
                                        class="bg-red-500 text-white px-3 py-1 rounded-lg hover:bg-red-600 transition duration-300 flex items-center">
                                    <i class="fas fa-trash-alt mr-1"></i> Delete
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Enhanced Modal -->
    <div id="confirmDeleteModal" class="fixed inset-0 flex items-center justify-center bg-black bg-opacity-50 hidden">
        <div class="bg-white p-6 rounded-xl shadow-lg max-w-md w-full mx-4 fade-in">
            <div class="text-center mb-6">
                <div class="mx-auto flex items-center justify-center h-12 w-12 rounded-full bg-red-100 mb-4">
                    <i class="fas fa-exclamation-triangle text-red-600 text-xl"></i>
                </div>
                <h2 class="text-2xl font-bold text-gray-900 mb-2">Confirm Deletion</h2>
                <p class="text-gray-500">Are you sure you want to delete this user? This action cannot be undone.</p>
            </div>
            <div class="flex justify-center space-x-4">
                <button onclick="closeModal()" 
                        class="bg-gray-100 text-gray-700 px-4 py-2 rounded-lg hover:bg-gray-200 transition duration-300 flex items-center">
                    <i class="fas fa-times mr-2"></i> Cancel
                </button>
                <form id="deleteForm" method="POST" class="inline">
                    <input type="hidden" name="delete_user_id" value="">
                    <button type="submit" 
                            class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition duration-300 flex items-center">
                        <i class="fas fa-trash-alt mr-2"></i> Yes, Delete
                    </button>
                </form>
            </div>
        </div>
    </div>

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

        // Search functionality
        document.getElementById('searchInput').addEventListener('keyup', function(e) {
            const searchText = e.target.value.toLowerCase();
            const tableRows = document.querySelectorAll('tbody tr');
            
            tableRows.forEach(row => {
                const name = row.querySelector('td:first-child').textContent.toLowerCase();
                const email = row.querySelector('td:nth-child(2)').textContent.toLowerCase();
                const events = row.querySelector('td:nth-child(3)').textContent.toLowerCase();
                
                if (name.includes(searchText) || email.includes(searchText) || events.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>
