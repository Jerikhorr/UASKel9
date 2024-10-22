<?php
session_start();
require '../includes/db_connect.php'; // Sesuaikan path ini dengan struktur folder Anda

// Dapatkan koneksi database
$conn = getDBConnection();

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

// Fetch user data from database
$user_id = $_SESSION['user_id'];

// Check if the user is admin or user and fetch data accordingly
$query = "SELECT * FROM users WHERE id = ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// If not found in users, check admin table
if (!$user) {
    $query_admin = "SELECT * FROM admin WHERE id = ?";
    $stmt_admin = $conn->prepare($query_admin);
    $stmt_admin->bind_param("i", $user_id);
    $stmt_admin->execute();
    $result_admin = $stmt_admin->get_result();
    $user = $result_admin->fetch_assoc();

    // If user data not found in both tables
    if (!$user) {
        echo "User data not found.";
        exit();
    }

    // Set role for admin
    $user['is_admin'] = 1; // assuming admin has is_admin set to 1
}

// Handle profile update
$update_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];

    // Validate input and update user data
    $query = "UPDATE users SET name = ?, email = ?" . (!empty($password) ? ", password = ?" : "") . " WHERE id = ?";
    $stmt = $conn->prepare($query);
    if (!empty($password)) {
        $hashed_password = password_hash($password, PASSWORD_BCRYPT);
        $stmt->bind_param("sssi", $name, $email, $hashed_password, $user_id);
    } else {
        $stmt->bind_param("ssi", $name, $email, $user_id);
    }

    if ($stmt->execute()) {
        $update_success = true;
        // Refresh user data
        $user['name'] = $name;
        $user['email'] = $email;
    }
}

// Fetch event registration history
$query_events = "SELECT events.name AS event_name, registrations.registration_date
                 FROM registrations
                 JOIN events ON events.id = registrations.event_id
                 WHERE registrations.user_id = ?";
$stmt_events = $conn->prepare($query_events);
$stmt_events->bind_param("i", $user_id);
$stmt_events->execute();
$result_events = $stmt_events->get_result();

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 p-6">
    <div class="max-w-2xl mx-auto bg-white p-6 rounded-md shadow-md">
        <h1 class="text-2xl font-bold mb-4">Profile</h1>

        <?php if ($update_success): ?>
            <div class="bg-green-100 text-green-800 p-2 rounded mb-4">
                Profile updated successfully.
            </div>
        <?php endif; ?>

        <form action="profile.php" method="POST" class="space-y-4">
            <div>
                <label for="name" class="block text-sm font-medium">Name:</label>
                <input type="text" id="name" name="name" value="<?= escapeOutput($user['name']) ?>" class="w-full mt-1 p-2 border rounded-md" required disabled>
            </div>

            <div>
                <label for="email" class="block text-sm font-medium">Email:</label>
                <input type="email" id="email" name="email" value="<?= escapeOutput($user['email']) ?>" class="w-full mt-1 p-2 border rounded-md" required disabled>
            </div>

            <div>
                <label for="password" class="block text-sm font-medium">Password (Leave blank to keep current password):</label>
                <input type="password" id="password" name="password" class="w-full mt-1 p-2 border rounded-md" disabled>
            </div>

            <button type="button" id="editBtn" class="bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Edit</button>
            <button type="submit" class="bg-green-500 text-white px-4 py-2 rounded-md hover:bg-green-600 hidden" id="updateBtn">Update Profile</button>
        </form>

        <h2 class="text-xl font-bold mt-6">Event Registration History</h2>
        <div class="mt-4">
            <?php if ($result_events->num_rows > 0): ?>
                <ul class="list-disc pl-5 space-y-2">
                    <?php while ($event = $result_events->fetch_assoc()): ?>
                        <li>
                            <span class="font-medium"><?= escapeOutput($event['event_name']) ?></span> - 
                            <span class="text-gray-600"><?= escapeOutput($event['registration_date']) ?></span>
                        </li>
                    <?php endwhile; ?>
                </ul>
            <?php else: ?>
                <p class="text-gray-500">WKWKWKWK ga daftar event, bokek ya?.</p>
            <?php endif; ?>
        </div>

        <div class="mt-4">
    <a href="<?php echo $user['is_admin'] ? '../admin/dashboard_admin.php' : 'dashboard_user.php'; ?>" class="inline-block bg-blue-500 text-white px-4 py-2 rounded-md hover:bg-blue-600">Back to Dashboard</a>
</div>

    </div>

    <script>
        document.getElementById('editBtn').addEventListener('click', function() {
            // Enable the input fields and show the update button
            document.querySelectorAll('input').forEach(input => {
                input.disabled = false;
            });
            this.classList.add('hidden');
            document.getElementById('updateBtn').classList.remove('hidden');
        });
    </script>
</body>
</html>
