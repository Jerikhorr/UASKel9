<?php
// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit();
}

// Function to check if current page matches the given path
function isCurrentPage($path) {
    $current_page = basename($_SERVER['PHP_SELF']);
    return $current_page === $path ? 'bg-blue-700' : '';
}
?>

<nav class="bg-blue-600 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Left side: Logo and Dashboard text -->
            <div class="flex items-center">
                <!-- Logo placeholder - replace src with your actual logo path -->
                <a href="dashboard_admin.php" class="flex items-center">
                    <img src="../logo/logoUAS.png" alt="Logo" class="h-12 w-12 mr-3">
                    <span class="text-white text-xl font-bold">Admin Dashboard</span>
                </a>
            </div>

            <!-- Right side: Navigation links -->
            <div class="flex items-center space-x-4">
                <a href="event_management.php" 
                   class="<?php echo isCurrentPage('event_management.php'); ?> text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition duration-150 ease-in-out">
                    Event Management
                </a>
                
                <a href="view_registrations.php" 
                   class="<?php echo isCurrentPage('view_registrations.php'); ?> text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition duration-150 ease-in-out">
                    View Registrations
                </a>
                
                <a href="../user/profile.php" 
                   class="<?php echo isCurrentPage('profile.php'); ?> text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition duration-150 ease-in-out">
                    Profile
                </a>
                
                <a href="user_management.php" 
                   class="<?php echo isCurrentPage('user_management.php'); ?> text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition duration-150 ease-in-out">
                    User Management
                </a>

                <!-- Logout button -->
                <a href="../user/login.php" 
                   class="bg-red-500 text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-red-600 transition duration-150 ease-in-out">
                    Logout
                </a>
            </div>
        </div>
    </div>
</nav>