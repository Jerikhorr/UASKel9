<?php
require_once '../includes/db_connect.php'; // Ensure this path is correct

$errors = [];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Sanitize input from form
    $email = sanitizeInput($_POST['email']);
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    // Validate inputs
    if (empty($email) || empty($new_password) || empty($confirm_password)) {
        $errors[] = "Email, new password, and confirmation password are required.";
    } elseif ($new_password !== $confirm_password) {
        $errors[] = "New password and confirmation password do not match.";
    } else {
        // Get the database connection
        $conn = getDBConnection();

        // Check if the email exists in the users table
        $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->bind_param("s", $email); // Bind the email parameter
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();

        // Check if the email exists in the admin table
        $stmt_admin = $conn->prepare("SELECT * FROM admin WHERE email = ?");
        $stmt_admin->bind_param("s", $email); // Bind the email parameter
        $stmt_admin->execute();
        $admin = $stmt_admin->get_result()->fetch_assoc();

        if ($user || $admin) {
            // Hash the new password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);

            // Update the password in the users table if found
            if ($user) {
                $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
                $update_stmt->bind_param("ss", $hashed_password, $email); // Bind parameters
                $update_stmt->execute();
            }

            // Update the password in the admin table if found
            if ($admin) {
                $update_stmt_admin = $conn->prepare("UPDATE admin SET password = ? WHERE email = ?");
                $update_stmt_admin->bind_param("ss", $hashed_password, $email); // Bind parameters
                $update_stmt_admin->execute();
            }

            // Password reset successful, redirect to login
            header('Location: ../user/login.php'); // Change this to the desired login URL
            exit();
        } else {
            $errors[] = "No user found with that email address.";
        }
    }
}
?>

<!-- HTML Form -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        function showPassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const passwordIcon = document.getElementById(iconId);
            passwordInput.type = 'text';
            passwordIcon.src = 'https://img.icons8.com/ios-filled/16/000000/invisible.png'; // Change icon to 'invisible'
        }

        function hidePassword(inputId, iconId) {
            const passwordInput = document.getElementById(inputId);
            const passwordIcon = document.getElementById(iconId);
            passwordInput.type = 'password';
            passwordIcon.src = 'https://img.icons8.com/ios-filled/16/000000/visible.png'; // Change icon to 'visible'
        }
    </script>
</head>
<body class="bg-gray-100 flex items-center justify-center min-h-screen">
    <div class="bg-white p-8 rounded-lg shadow-md w-96">
        <h2 class="text-2xl font-semibold text-center mb-4">Reset Password</h2>
        <?php if (!empty($errors)): ?>
            <div class="mb-4">
                <?php foreach ($errors as $error): ?>
                    <p class="text-red-500"><?php echo htmlspecialchars($error); ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <form method="POST" action="lupa_pass.php">
            <div class="mb-4">
                <input type="email" name="email" required placeholder="Email" class="border border-gray-300 p-2 rounded w-full">
            </div>
            <div class="mb-4 relative">
                <input id="new_password" type="password" name="new_password" required placeholder="New Password" class="border border-gray-300 p-2 rounded w-full">
                <span class="absolute inset-y-0 right-0 top-0 pr-3 flex items-center">
                    <button type="button" onmousedown="showPassword('new_password', 'newPasswordIcon')" onmouseup="hidePassword('new_password', 'newPasswordIcon')" onmouseleave="hidePassword('new_password', 'newPasswordIcon')" class="focus:outline-none">
                        <img src="https://img.icons8.com/ios-filled/16/000000/visible.png" id="newPasswordIcon" alt="Show Password" class="w-5 h-5"/>
                    </button>
                </span>
            </div>
            <div class="mb-4 relative">
                <input id="confirm_password" type="password" name="confirm_password" required placeholder="Confirm Password" class="border border-gray-300 p-2 rounded w-full">
                <span class="absolute inset-y-0 right-0 top-0 pr-3 flex items-center">
                    <button type="button" onmousedown="showPassword('confirm_password', 'confirmPasswordIcon')" onmouseup="hidePassword('confirm_password', 'confirmPasswordIcon')" onmouseleave="hidePassword('confirm_password', 'confirmPasswordIcon')" class="focus:outline-none">
                        <img src="https://img.icons8.com/ios-filled/16/000000/visible.png" id="confirmPasswordIcon" alt="Show Password" class="w-5 h-5"/>
                    </button>
                </span>
            </div>
            <button type="submit" class="bg-blue-500 text-white py-2 rounded w-full">Reset Password</button>
        </form>
        <div class="mt-4 text-center">
            <a href="../user/login.php" class="text-blue-500 hover:underline">Back to Login</a>
        </div>
    </div>
</body>
</html>
                    