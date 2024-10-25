<?php

// Cek apakah pengguna sudah login dan mendapatkan peran admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit();
}

// Tambahkan logika untuk logout
if (isset($_POST['logout'])) {
    session_destroy();
    header("Location: ../index.php");
    exit();
}

// Fungsi untuk memeriksa apakah halaman saat ini cocok dengan jalur yang diberikan
function isCurrentPage($path) {
    $current_page = basename($_SERVER['PHP_SELF']);
    return $current_page === $path ? 'bg-blue-700' : '';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body>

<nav class="bg-blue-600 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Bagian kiri: Logo dan teks Dashboard -->
            <div class="flex items-center">
                <a href="dashboard_admin.php" class="flex items-center">
                    <img src="../logo/logoUAS.png" alt="Logo" class="h-12 w-12 mr-3">
                    <span class="text-white text-xl font-bold">Admin Dashboard</span>
                </a>
            </div>

            <!-- Tombol Hamburger untuk mobile -->
            <div class="md:hidden">
                <button id="hamburger-button" class="text-white focus:outline-none">
                    <svg class="h-6 w-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    </svg>
                </button>
            </div>

            <!-- Bagian kanan: Navigasi -->
            <div class="hidden md:flex items-center space-x-4">
                <a href="event_management.php" 
                   class="<?php echo isCurrentPage('event_management.php'); ?> text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition duration-150 ease-in-out">
                    Event Management
                </a>
                
                <a href="view_registrations.php" 
                   class="<?php echo isCurrentPage('view_registrations.php'); ?> text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition duration-150 ease-in-out">
                    View Registrations
                </a>
                
                <a href="./profile_admin.php" 
                   class="<?php echo isCurrentPage('profile_admin.php'); ?> text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition duration-150 ease-in-out">
                    Profile
                </a>

                <a href="user_management.php" 
                   class="<?php echo isCurrentPage('user_management.php'); ?> text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition duration-150 ease-in-out">
                    User Management
                </a>

                <!-- Tombol Logout -->
                <form action="" method="POST" class="m-0">
                    <button type="submit" 
                            name="logout"
                            class="bg-red-500 text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-red-600 transition duration-150 ease-in-out">
                        Logout
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- Menu Mobile -->
    <div id="mobile-menu" class="hidden md:hidden px-4 pb-4">
        <a href="event_management.php" 
           class="<?php echo isCurrentPage('event_management.php'); ?> text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition duration-150 ease-in-out">
            Event Management
        </a>
        
        <a href="view_registrations.php" 
           class="<?php echo isCurrentPage('view_registrations.php'); ?> text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition duration-150 ease-in-out">
            View Registrations
        </a>
        
        <a href="./profile_admin.php" 
           class="<?php echo isCurrentPage('profile_admin.php'); ?> text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition duration-150 ease-in-out">
            Profile
        </a>
        
        <a href="user_management.php" 
           class="<?php echo isCurrentPage('user_management.php'); ?> text-white block px-3 py-2 rounded-md text-base font-medium hover:bg-blue-700 transition duration-150 ease-in-out">
            User Management
        </a>

        <!-- Tombol Logout untuk mobile -->
        <form action="" method="POST" class="m-0">
            <button type="submit" 
                    name="logout"
                    class="bg-red-500 text-white w-full text-left px-3 py-2 rounded-md text-base font-medium hover:bg-red-600 transition duration-150 ease-in-out">
                Logout
            </button>
        </form>
    </div>
</nav>

<!-- JavaScript untuk toggle menu mobile -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const mobileMenu = document.getElementById('mobile-menu');
    const hamburgerButton = document.getElementById('hamburger-button');
    
    hamburgerButton.addEventListener('click', function() {
        mobileMenu.classList.toggle('hidden');
    });
});
</script>

</body>
</html>