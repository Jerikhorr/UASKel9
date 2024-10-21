<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

// Periksa apakah pengguna sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit();
}

// Proses form submission untuk membuat atau mengedit user
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = isset($_POST['user_id']) ? intval($_POST['user_id']) : null;
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $role = mysqli_real_escape_string($conn, $_POST['role']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);
    $password = !empty($_POST['password']) ? password_hash($_POST['password'], PASSWORD_BCRYPT) : null;

    if ($user_id) {
        // Update existing user
        if ($password) {
            $query = "UPDATE users SET name = ?, email = ?, role = ?, status = ?, password = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssssi", $name, $email, $role, $status, $password, $user_id);
        } else {
            $query = "UPDATE users SET name = ?, email = ?, role = ?, status = ? WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "ssssi", $name, $email, $role, $status, $user_id);
        }
    } else {
        // Create new user
        $query = "INSERT INTO users (name, email, role, status, password) VALUES (?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssss", $name, $email, $role, $status, $password);
    }

    if (mysqli_stmt_execute($stmt)) {
        $success_message = $user_id ? "User updated successfully." : "User created successfully.";
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}

// Delete user
if (isset($_GET['delete'])) {
    $user_id = intval($_GET['delete']);
    $query = "DELETE FROM users WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $user_id);

    if (mysqli_stmt_execute($stmt)) {
        $success_message = "User deleted successfully.";
    } else {
        $error_message = "Error deleting user: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}

// Fetch all users
$query = "SELECT * FROM users ORDER BY name ASC";
$result = mysqli_query($conn, $query);

include('../includes/header.php');
?>

<div class="container mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold mb-8">User Management</h1>

    <?php if (isset($success_message)): ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $success_message; ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)): ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline"><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>

    <form action="user_management.php" method="post" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
        <input type="hidden" name="user_id" value="">
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                Name
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="name" type="text" name="name" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                Email
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="email" type="email" name="email" required>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="role">
                Role
            </label>
            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="role" name="role" required>
                <option value="user">User</option>
                <option value="admin">Admin</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="status">
                Status
            </label>
            <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="status" name="status" required>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </div>
        <div class="mb-4">
            <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                Password
            </label>
            <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline" id="password" type="password" name="password" placeholder="Leave blank to keep current password">
        </div>
        <div class="flex items-center justify-between">
            <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                Create User
            </button>
        </div>
    </form>

    <h2 class="text-2xl font-bold mt-8 mb-4">Existing Users</h2>
    <table class="w-full bg-white shadow-md rounded mb-4">
        <thead>
            <tr>
                <th class="text-left py-2 px-4 bg-gray-100 font-semibold text-gray-600">Name</th>
                <th class="text-left py-2 px-4 bg-gray-100 font-semibold text-gray-600">Email</th>
                <th class="text-left py-2 px-4 bg-gray-100 font-semibold text-gray-600">Role</th>
                <th class="text-left py-2 px-4 bg-gray-100 font-semibold text-gray-600">Status</th>
                <th class="text-left py-2 px-4 bg-gray-100 font-semibold text-gray-600">Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php while ($row = mysqli_fetch_assoc($result)): ?>
                <tr>
                    <td class="py-2 px-4"><?php echo htmlspecialchars($row['name']); ?></td>
                    <td class="py-2 px-4"><?php echo htmlspecialchars($row['email']); ?></td>
                    <td class="py-2 px-4"><?php echo htmlspecialchars($row['role']); ?></td>
                    <td class="py-2 px-4"><?php echo htmlspecialchars($row['status']); ?></td>
                    <td class="py-2 px-4">
                        <a href="user_management.php?edit=<?php echo $row['id']; ?>" class="text-blue-500 hover:text-blue-700">Edit</a>
                        <a href="user_management.php?delete=<?php echo $row['id']; ?>" class="text-red-500 hover:text-red-700 ml-2" onclick="return confirm('Are you sure you want to delete this user?')">Delete</a>
                    </td>
                </tr>
            <?php endwhile; ?>
        </tbody>
    </table>
</div>

<?php include('../includes/footer.php'); ?>
