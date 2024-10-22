<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../classes/User.php';

$db = getDBConnection();
$user = new User($db);

$nameErr = $emailErr = $passwordErr = $confirmPasswordErr = $roleErr = "";
$name = $email = $password = $confirmPassword = "";
$role = "user"; // Default role

// Function to check if email exists in 'admin' or 'users' tables
function isEmailExists($db, $email) {
    $query = "SELECT 1 FROM users WHERE email = ? UNION SELECT 1 FROM admin WHERE email = ?";
    $stmt = $db->prepare($query);
    $stmt->bind_param('ss', $email, $email);
    $stmt->execute();
    return $stmt->fetch() ? true : false;
}


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
        } elseif (isEmailExists($db, $email)) {
            $emailErr = "This email is already registered";
        }
    }

    // Validate password
    if (empty($_POST["password"])) {
        $passwordErr = "Password is required";
    } else {
        $password = $_POST["password"];
        if (strlen($password) < 8) {
            $passwordErr = "Password must be at least 8 characters long";
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $passwordErr = "Password must contain at least one uppercase letter";
        } elseif (!preg_match('/[a-z]/', $password)) {
            $passwordErr = "Password must contain at least one lowercase letter";
        } elseif (!preg_match('/[0-9]/', $password)) {
            $passwordErr = "Password must contain at least one number";
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
        $role = sanitizeInput($_POST["role"]);
    }

    // If no errors, proceed with registration
    if (empty($nameErr) && empty($emailErr) && empty($passwordErr) && empty($confirmPasswordErr) && empty($roleErr)) {
        $user->name = $name;
        $user->email = $email;
        $user->password = $password;
        $user->is_admin = ($role === 'admin') ? 1 : 0; // Set is_admin based on role

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
    <link rel="icon" href="../logo/logoUAS.png" type="image/png">
    <title>User Registration</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto mt-10 max-w-md">
        <h1 class="text-3xl font-bold mb-5 text-center">User Registration</h1>
        <?php if (isset($error)) : ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="name">
                    Name
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($nameErr)) ? 'border-red-500' : ''; ?>" id="name" type="text" name="name" value="<?php echo $name; ?>">
                <p class="text-red-500 text-xs italic"><?php echo $nameErr; ?></p>
            </div>
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                    Email
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($emailErr)) ? 'border-red-500' : ''; ?>" id="email" type="email" name="email" value="<?php echo $email; ?>">
                <p class="text-red-500 text-xs italic"><?php echo $emailErr; ?></p>
            </div>
            <div class="mb-4 relative">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">Password</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($passwordErr)) ? 'border-red-500' : ''; ?>" id="password" type="password" name="password" required>
            <span class="absolute inset-y-0 right-0 top-4 pr-3 flex items-center text-gray-700">
            <button type="button" onmousedown="showPassword()" onmouseup="hidePassword()" onmouseleave="hidePassword()" class="focus:outline-none">            <img src="https://img.icons8.com/ios-filled/16/000000/visible.png" id="passwordIcon" alt="Show Password" class="w-5 h-5"/></button>
             </span>
                <p class="text-red-500 text-xs italic"><?php echo $passwordErr; ?></p>  
            </div>
            <div class="mb-6 relative">
    <label class="block text-gray-700 text-sm font-bold mb-2" for="confirm_password">Confirm Password</label>
    <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($confirmPasswordErr)) ? 'border-red-500' : ''; ?>" id="confirm_password" type="password" name="confirm_password" required>
    <span class="absolute inset-y-0 right-0 top-4 pr-3 flex items-center text-gray-700">
        <button type="button" onmousedown="showConfirmPassword()" onmouseup="hideConfirmPassword()" onmouseleave="hideConfirmPassword()" class="focus:outline-none">
            <img src="https://img.icons8.com/ios-filled/16/000000/visible.png" id="confirmPasswordIcon" alt="Show Confirm Password" class="w-5 h-5"/>
        </button>
    </span>
    <p class="text-red-500 text-xs italic"><?php echo $confirmPasswordErr; ?></p>
</div>
<div class="mb-4">
    <label class="block text-gray-700 text-sm font-bold mb-2" for="role">
        Role
    </label>
    <select name="role" id="role" class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($roleErr)) ? 'border-red-500' : ''; ?>">
        <option value="" disabled selected>Pilih</option>
        <option value="user" <?php echo ($role === 'user') ? 'selected' : ''; ?>>User</option>
        <option value="admin" <?php echo ($role === 'admin') ? 'selected' : ''; ?>>Admin</option>
    </select>
    <p class="text-red-500 text-xs italic"><?php echo $roleErr; ?></p>
</div>


            <div class="flex items-center justify-between">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                    Register
                </button>
        </form>
        </div>
        <div class="text-center mt-4">
            <p class="text-gray-600">Sudah punya akun? <a href="../user/Login.php" class="text-blue-500 hover:underline">Login</a></p>
        </div>
    </div>
    <script>
    function showPassword() {
        const passwordField = document.getElementById('password');
        const passwordIcon = document.getElementById('passwordIcon');

        passwordField.type = 'text'; // Show password
        passwordIcon.src = 'https://img.icons8.com/ios-filled/16/000000/invisible.png'; // Change icon to "invisible"
    }

    function hidePassword() {
        const passwordField = document.getElementById('password');
        const passwordIcon = document.getElementById('passwordIcon');

        passwordField.type = 'password'; // Hide password
        passwordIcon.src = 'https://img.icons8.com/ios-filled/16/000000/visible.png'; // Change icon back to "visible"
    }

        function showConfirmPassword() {
        const confirmPasswordField = document.getElementById('confirm_password');
        const confirmPasswordIcon = document.getElementById('confirmPasswordIcon');

        confirmPasswordField.type = 'text'; // Show confirm password
        confirmPasswordIcon.src = 'https://img.icons8.com/ios-filled/16/000000/invisible.png'; // Change icon to "invisible"
    }

    function hideConfirmPassword() {
        const confirmPasswordField = document.getElementById('confirm_password');
        const confirmPasswordIcon = document.getElementById('confirmPasswordIcon');

        confirmPasswordField.type = 'password'; // Hide confirm password
        confirmPasswordIcon.src = 'https://img.icons8.com/ios-filled/16/000000/visible.png'; // Change icon back to "visible"
    }
</script>

</body>
</html>