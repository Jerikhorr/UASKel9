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
$active_users = 0;
$total_registrations = 0;

while ($row = mysqli_fetch_assoc($result)) {
    $total_users++;
    if ($row['event_count'] > 0) {
        $active_users++;
    }
    $total_registrations += $row['event_count'];
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
        
        .custom-scrollbar::-webkit-scrollbar {
            height: 4px;
            width: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f1f1;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #888;
            border-radius: 2px;
        }
        
        @media (max-width: 768px) {
            .user-card {
                border-radius: 0.5rem;
                margin-bottom: 1rem;
                transition: transform 0.2s;
            }
            .user-card:active {
                transform: scale(0.98);
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'navbar_admin.php'; ?>
    
    <div class="container mx-auto px-4 py-4 sm:py-8">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6">
            <div class="w-full sm:w-auto mb-4 sm:mb-0">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">User Management</h1>
                <p class="text-gray-600 mt-1">Manage and monitor user activities</p>
            </div>
            
            <!-- Search Box -->
            <div class="w-full sm:w-auto">
                <div class="relative">
                    <input type="text" id="searchInput" 
                           placeholder="Search users..." 
                           class="w-full sm:w-64 px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <i class="fas fa-search absolute right-3 top-3 text-gray-400"></i>
                </div>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <!-- Total Users -->
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 text-blue-500">
                        <i class="fas fa-users text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 uppercase">Total Users</p>
                        <p class="text-xl font-semibold text-gray-800"><?php echo $total_users; ?></p>
                    </div>
                </div>
            </div>

            <!-- Active Users -->
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 text-green-500">
                        <i class="fas fa-user-check text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 uppercase">Active Users</p>
                        <p class="text-xl font-semibold text-gray-800"><?php echo $active_users; ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Registrations -->
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 text-purple-500">
                        <i class="fas fa-ticket-alt text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 uppercase">Registrations</p>
                        <p class="text-xl font-semibold text-gray-800"><?php echo $total_registrations; ?></p>
                    </div>
                </div>
            </div>

            <!-- Average Events -->
            <div class="bg-white rounded-xl shadow-sm p-4 border border-gray-200">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100 text-yellow-500">
                        <i class="fas fa-chart-line text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-sm text-gray-500 uppercase">Avg Events/User</p>
                        <p class="text-xl font-semibold text-gray-800">
                            <?php echo $total_users > 0 ? number_format($total_registrations / $total_users, 1) : '0'; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Users List Section -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-200">
            <!-- List Header -->
            <div class="p-4 sm:p-6 bg-white border-b">
                <div class="flex items-center">
                    <i class="fas fa-user-circle text-blue-500 text-xl sm:text-2xl mr-2"></i>
                    <h2 class="text-lg sm:text-xl font-semibold text-gray-800">User List</h2>
                </div>
            </div>

            <!-- Desktop View -->
            <div class="hidden md:block overflow-x-auto custom-scrollbar">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Contact</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Events</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Actions</th>
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
                                <?php if (empty($user['registered_events'])): ?>
                                    <span class="text-sm text-gray-500">No events registered</span>
                                <?php else: ?>
                                    <div class="flex flex-wrap gap-2">
                                        <?php foreach ($user['registered_events'] as $event): ?>
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800">
                                                <?php echo htmlspecialchars($event); ?>
                                            </span>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
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

            <!-- Mobile View -->
            <div class="md:hidden">
                <?php foreach ($users as $user): ?>
                <div class="user-card p-4 bg-white border-b border-gray-200">
                    <!-- User Header -->
                    <div class="flex items-center justify-between mb-3">
                        <div class="flex items-center">
                            <div class="flex-shrink-0 h-10 w-10 bg-blue-100 rounded-full flex items-center justify-center">
                                <span class="text-blue-600 font-semibold">
                                    <?php echo strtoupper(substr($user['name'], 0, 2)); ?>
                                </span>
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($user['name']); ?></div>
                                <div class="text-xs text-gray-500">ID: #<?php echo $user['id']; ?></div>
                            </div>
                        </div>
                        <?php if ($user['event_count'] > 0): ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-green-100 text-green-800">
                                Active
                            </span>
                        <?php else: ?>
                            <span class="px-2 py-1 text-xs font-semibold rounded-full bg-gray-100 text-gray-800">
                                Inactive
                            </span>
                        <?php endif; ?>
                    </div>

                    <!-- User Details -->
                    <div class="space-y-2">
                        <!-- Email -->
                        <div class="flex items-center text-sm">
                            <i class="fas fa-envelope text-gray-400 w-5"></i>
                            <span class="ml-2 text-gray-600 break-all"><?php echo htmlspecialchars($user['email']); ?></span>
                        </div>

                        <!-- Events -->
                        <div class="mt-2">
                            <div class="flex items-start">
                                <i class="fas fa-calendar text-gray-400 w-5 mt-1"></i>
                                <div class="ml-2">
                                    <?php if (empty($user['registered_events'])): ?>
                                        <span class="text-sm text-gray-500">No events registered</span>
                                    <?php else: ?>
                                        <div class="flex flex-wrap gap-2">
                                        <?php foreach ($user['registered_events'] as $event): ?>
    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-blue-100 text-blue-800 mb-1">
        <?php echo htmlspecialchars($event); ?>
    </span>
<?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="mt-4 flex justify-end">
                            <button onclick="confirmDelete(<?php echo $user['id']; ?>)" 
                                    class="bg-red-500 text-white px-4 py-2 rounded-lg hover:bg-red-600 transition duration-300 flex items-center text-sm">
                                <i class="fas fa-trash-alt mr-2"></i> Delete User
                            </button>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="fixed inset-0 bg-gray-600 bg-opacity-50 hidden items-center justify-center z-50">
        <div class="bg-white rounded-lg p-6 m-4 max-w-sm w-full fade-in">
            <div class="flex items-center justify-center mb-4">
                <div class="rounded-full bg-red-100 p-3">
                    <i class="fas fa-exclamation-triangle text-red-500 text-xl"></i>
                </div>
            </div>
            <h3 class="text-xl font-bold text-center mb-4">Confirm Delete</h3>
            <p class="text-gray-600 text-center mb-6">Are you sure you want to delete this user? This action cannot be undone.</p>
            <form id="deleteForm" method="POST" class="flex justify-center gap-3">
                <input type="hidden" id="delete_user_id" name="delete_user_id" value="">
                <button type="button" onclick="closeDeleteModal()" 
                        class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition duration-300">
                    Cancel
                </button>
                <button type="submit" 
                        class="px-4 py-2 bg-red-500 text-white rounded-lg hover:bg-red-600 transition duration-300">
                    Delete
                </button>
            </form>
        </div>
    </div>

    <!-- Search functionality script -->
    <script>
        const searchInput = document.getElementById('searchInput');
        const userCards = document.querySelectorAll('.user-card');
        const userRows = document.querySelectorAll('tbody tr');

        searchInput.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase();

            // Filter desktop view
            userRows.forEach(row => {
                const userName = row.querySelector('.text-gray-900').textContent.toLowerCase();
                const userEmail = row.querySelector('.text-gray-900:last-of-type').textContent.toLowerCase();
                const shouldShow = userName.includes(searchTerm) || userEmail.includes(searchTerm);
                row.style.display = shouldShow ? '' : 'none';
            });

            // Filter mobile view
            userCards.forEach(card => {
                const userName = card.querySelector('.font-medium').textContent.toLowerCase();
                const userEmail = card.querySelector('.text-gray-600').textContent.toLowerCase();
                const shouldShow = userName.includes(searchTerm) || userEmail.includes(searchTerm);
                card.style.display = shouldShow ? '' : 'none';
            });
        });

        // Modal functionality
        function confirmDelete(userId) {
            document.getElementById('deleteModal').style.display = 'flex';
            document.getElementById('delete_user_id').value = userId;
        }

        function closeDeleteModal() {
            document.getElementById('deleteModal').style.display = 'none';
        }

        // Close modal when clicking outside
        document.getElementById('deleteModal').addEventListener('click', function(e) {
            if (e.target === this) {
                closeDeleteModal();
            }
        });

        // Add touch feedback for mobile devices
        document.querySelectorAll('.user-card').forEach(card => {
            card.addEventListener('touchstart', function() {
                this.classList.add('scale-98');
            });
            card.addEventListener('touchend', function() {
                this.classList.remove('scale-98');
            });
        });

        // Add responsive navigation menu toggle
        const menuToggle = document.querySelector('.menu-toggle');
        const mobileMenu = document.querySelector('.mobile-menu');
        
        if (menuToggle && mobileMenu) {
            menuToggle.addEventListener('click', () => {
                mobileMenu.classList.toggle('hidden');
            });
        }
    </script>

    <!-- Add additional styles for better mobile experience -->
    <style>
        @media (max-width: 640px) {
            .container {
                padding-left: 1rem;
                padding-right: 1rem;
            }
            
            .user-card {
                margin-bottom: 0.75rem;
            }
            
            .statistics-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 0.75rem;
            }
            
            .search-input {
                width: 100%;
                margin-bottom: 1rem;
            }
        }

        /* Add smooth scaling animation for touch feedback */
        .scale-98 {
            transform: scale(0.98);
            transition: transform 0.1s ease-in-out;
        }

        /* Improve scrolling on mobile */
        .custom-scrollbar {
            -webkit-overflow-scrolling: touch;
            scrollbar-width: thin;
        }

        /* Optimize tap targets for mobile */
        @media (max-width: 640px) {
            button, 
            .user-card,
            input[type="text"] {
                min-height: 44px;
                padding: 0.75rem;
            }
            
            .badge {
                padding: 0.5rem 0.75rem;
            }
        }
    </style>
</body>
</html>