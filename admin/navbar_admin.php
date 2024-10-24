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
                <a href="dashboard_admin.php" class="flex items-center">
                    <img src="../logo/logoUAS.png" alt="Logo" class="h-12 w-12 mr-3">
                    <span class="text-white text-xl font-bold">Admin Dashboard</span>
                </a>
            </div>

            <!-- Mobile menu button -->
            <div class="flex md:hidden">
                <button id="mobile-menu-btn" class="text-white focus:outline-none">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <!-- Right side: Navigation links (hidden on mobile by default) -->
            <div class="hidden md:flex items-center space-x-4">
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

    <!-- Mobile Menu -->
    <div id="mobile-menu" class="hidden md:hidden">
        <a href="event_management.php" 
           class="<?php echo isCurrentPage('event_management.php'); ?> text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition duration-150 ease-in-out">
            Event Management
        </a>
        
        <a href="view_registrations.php" 
           class="<?php echo isCurrentPage('view_registrations.php'); ?> text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition duration-150 ease-in-out">
            View Registrations
        </a>
        
        <a href="../user/profile.php" 
           class="<?php echo isCurrentPage('profile.php'); ?> text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition duration-150 ease-in-out">
            Profile
        </a>
        
        <a href="user_management.php" 
           class="<?php echo isCurrentPage('user_management.php'); ?> text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition duration-150 ease-in-out">
            User Management
        </a>

        <!-- Logout button -->
        <a href="../user/login.php" 
           class="bg-red-500 text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-red-600 transition duration-150 ease-in-out">
            Logout
        </a>
    </div>
</nav>

<script>
    const mobileMenuBtn = document.getElementById('mobile-menu-btn');
    const mobileMenu = document.getElementById('mobile-menu');

    mobileMenuBtn.addEventListener('click', () => {
        mobileMenu.classList.toggle('hidden');
    });
</script>
