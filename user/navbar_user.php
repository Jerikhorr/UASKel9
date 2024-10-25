<?php
// navbar.php
?>

<nav class="navbar flex justify-between items-center px-6 py-4 bg-blue-600 shadow-md">
    <div class="flex items-center">
        <img src="path_to_your_logo.png" alt="Logo" class="h-10 mr-2"> <!-- Ganti dengan path logo Anda -->
        <h1 class="text-white text-lg font-semibold">User Dashboard</h1>
    </div>
    <div class="flex space-x-4">
        <a href="event_management.php" class="nav-link">Event Management</a>
        <a href="view_registrations.php" class="nav-link">View Registrations</a>
        <a href="profile.php" class="nav-link">Profile</a>
        <a href="user_management.php" class="nav-link">User Management</a>
        <a href="../user/logout.php" class="logout nav-link">Logout</a>
    </div>
</nav>

<style>
    .nav-link {
        color: white;
        padding: 0.75rem 1rem;
        border-radius: 0.375rem;
        text-decoration: none;
        font-weight: 500;
        transition: background-color 0.3s ease;
    }

    .nav-link:hover {
        background-color: rgba(255, 255, 255, 0.2);
    }

    .logout {
        background-color: #f44336; /* Red for logout button */
        transition: background-color 0.3s ease;
    }

    .logout:hover {
        background-color: #c62828; /* Darker red on hover */
    }
</style>
