<?php
// Cek apakah pengguna telah login dan memiliki peran 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../user/login.php");
    exit();
}

// Tambahkan logic untuk logout
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

<nav class="bg-blue-600 shadow-lg">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <div class="flex items-center">
                <a href="dashboard_user.php" class="flex items-center">
                    <img src="../logo/logoUAS.png" alt="Logo" class="h-12 w-12 mr-3">
                    <span class="text-white text-xl font-bold">User Dashboard</span>
                </a>
            </div>
            <div class="flex items-center space-x-4">
                <a href="registered_event.php" 
                   class="<?php echo isCurrentPage('../user/registered_event.php'); ?> text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition duration-150 ease-in-out">
                    Registered Event
                </a>
                
                <a href="profile_user.php" 
                   class="<?php echo isCurrentPage('../user/profile_user.php'); ?> text-white px-3 py-2 rounded-md text-sm font-medium hover:bg-blue-700 transition duration-150 ease-in-out">
                    Profile
                </a>

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
</nav>