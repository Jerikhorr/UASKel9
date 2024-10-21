<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../classes/User.php';

$db = getDBConnection();
$user = new User($db);

$nameErr = $emailErr = $passwordErr = $confirmPasswordErr = $roleErr = "";
$name = $email = $password = $confirmPassword = $role = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate name
    if (empty($_POST["name"])) {
        $nameErr = "Name is required";
    } else {
        $name = sanitizeInput($_POST["name"]);
        if (!preg_match("/^[a-zA-Z ]*$/", $name)) {
            $nameErr = "Only letters and white space allowed";
        }
    }

    // Validate email
    if (empty($_POST["email"])) {
        $emailErr = "Email is required";
    } else {
        $email = sanitizeInput($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format";
        }
    }

    // Validate password
    if (empty($_POST["password"])) {
        $passwordErr = "Password is required";
    } else {
        $password = $_POST["password"];
        if (strlen($password) < 8) {
            $passwordErr = "Password must be at least 8 characters long";
        }
    }

    // Validate confirm password
    if (empty($_POST["confirm_password"])) {
        $confirmPasswordErr = "Please confirm your password";
    } else {
        $confirmPassword = $_POST["confirm_password"];
        if ($password != $confirmPassword) {
            $confirmPasswordErr = "Passwords do not match";
        }
    }

    // Validate role
    if (empty($_POST["role"])) {
        $roleErr = "Role is required";
    } else {
        $role = $_POST["role"];
    }

    // If no errors, proceed with registration
    if (empty($nameErr) && empty($emailErr) && empty($passwordErr) && empty($confirmPasswordErr) && empty($roleErr)) {
        $user->name = $name;
        $user->email = $email;
        $user->password = password_hash($password, PASSWORD_DEFAULT); // Hash the password
        $user->is_admin = ($role == 'admin') ? 1 : 0; // Set role based on selection

        if ($user->create()) {
            $_SESSION['success_message'] = "Registration successful! You can now log in.";
            header("Location: login.php");
            exit();
        } else {
            $error = "Registration failed. Please try again.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .password-container {
            position: relative;
        }
        .password-container input {
            padding-right: 40px; /* Space for the eye icon */
        }
        .password-container .eye-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-white">
    <div class="container mx-auto mt-10 max-w-md">
        <h1 class="text-3xl font-bold mb-5 text-center text-gray-800">Registrasi</h1>

        <?php if (isset($error)) : ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>

        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="bg-white shadow-md rounded-lg px-8 pt-6 pb-8 mb-4 border border-gray-300">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">Nama</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($nameErr)) ? 'border-red-500' : ''; ?>" id="name" type="text" name="name" value="<?php echo htmlspecialchars($name); ?>" required>
                <p class="text-red-500 text-xs italic"><?php echo $nameErr; ?></p>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">Email</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($emailErr)) ? 'border-red-500' : ''; ?>" id="email" type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
                <p class="text-red-500 text-xs italic"><?php echo $emailErr; ?></p>
            </div>

            <div class="mb-4 password-container">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($passwordErr)) ? 'border-red-500' : ''; ?>" id="password" type="password" name="password" required>
                <img src="https://img.icons8.com/material-outlined/24/000000/visible.png" class="eye-icon" onmousedown="showPassword('password')" onmouseup="hidePassword('password')" onmouseleave="hidePassword('password')" alt="Show Password" />
                <p class="text-red-500 text-xs italic"><?php echo $passwordErr; ?></p>
            </div>

            <div class="mb-6 password-container">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">Konfirmasi Password</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($confirmPasswordErr)) ? 'border-red-500' : ''; ?>" id="confirm_password" type="password" name="confirm_password" required>
                <img src="https://img.icons8.com/material-outlined/24/000000/visible.png" class="eye-icon" onmousedown="showPassword('confirm_password')" onmouseup="hidePassword('confirm_password')" onmouseleave="hidePassword('confirm_password')" alt="Show Password" />
                <p class="text-red-500 text-xs italic"><?php echo $confirmPasswordErr; ?></p>
            </div>

            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="role">Role</label>
                <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($roleErr)) ? 'border-red-500' : ''; ?>" id="role" name="role" required>
                    <option value="">Select Role</option>
                    <option value="user" <?php echo ($role === 'user') ? 'selected' : ''; ?>>User</option>
                    <option value="admin" <?php echo ($role === 'admin') ? 'selected' : ''; ?>>Admin</option>
                </select>
                <p class="text-red-500 text-xs italic"><?php echo $roleErr; ?></p>
            </div>

            <div class="flex items-center justify-between">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">Register</button>
            </div>
        </form>

        <div class="text-center mt-4">
           <p class="text-gray-600">SUdah punya akun? <a href="../user/login.php" class="text-blue-500 hover:underline">Login</a></p>
        </div>
    </div>

    <script>
        function showPassword(id) {
            const passwordField = document.getElementById(id);
            passwordField.setAttribute('type', 'text');
        }

        function hidePassword(id) {
            const passwordField = document.getElementById(id);
            passwordField.setAttribute('type', 'password');
        }
    </script>
</body>
</html>
