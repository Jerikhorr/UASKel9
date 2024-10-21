<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../classes/User.php';

$db = getDBConnection();
$user = new User($db);

$emailErr = $passwordErr = "";
$email = $password = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validasi email
    if (empty($_POST["email"])) {
        $emailErr = "Email is required";
    } else {
        $email = sanitizeInput($_POST["email"]);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $emailErr = "Invalid email format";
        }
    }

    // Validasi password
    if (empty($_POST["password"])) {
        $passwordErr = "Password is required";
    } else {
        $password = $_POST["password"];
    }

    // Jika tidak ada error, lanjutkan dengan login
    if (empty($emailErr) && empty($passwordErr)) {
        // Tentukan tabel berdasarkan role
        $user->setTable('admin'); // Ganti ini jika role berbeda
        if ($user->authenticate($email, $password)) {
            $_SESSION['user_id'] = $user->id;
            $_SESSION['is_admin'] = $user->is_admin;

            if ($user->is_admin) {
                header("Location: ../admin/dashboard.php");
            } else {
                header("Location: ../user/browse_events.php");
            }
            exit();
        } else {
            $error = "Invalid email or password";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Login</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto mt-10 max-w-md">
        <h1 class="text-3xl font-bold mb-5 text-center">User Login</h1>
        <?php if (isset($error)) : ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <strong class="font-bold">Error!</strong>
                <span class="block sm:inline"><?php echo $error; ?></span>
            </div>
        <?php endif; ?>
        <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post" class="bg-white shadow-md rounded px-8 pt-6 pb-8 mb-4">
            <div class="mb-4">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="email">
                    Email
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($emailErr)) ? 'border-red-500' : ''; ?>" id="email" type="email" name="email" value="<?php echo $email; ?>">
                <p class="text-red-500 text-xs italic"><?php echo $emailErr; ?></p>
            </div>
            <div class="mb-6 relative">
                <label class="block text-gray-700 text-sm font-bold mb-2" for="password">
                    Password
                </label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 mb-3 leading-tight focus:outline-none focus:shadow-outline <?php echo (!empty($passwordErr)) ? 'border-red-500' : ''; ?>" id="password" type="password" name="password" required>
                <span id="togglePassword" class="absolute inset-y-0 right-0 flex items-center pr-3 cursor-pointer">
                    <svg class="w-5 h-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12c0 4.418 3.582 8 8 8s8-3.582 8-8-3.582-8-8-8-8 3.582-8 8z" />
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12l-3 3m0 0l-3-3m3 3V9" />
                    </svg>
                </span>
                <p class="text-red-500 text-xs italic"><?php echo $passwordErr; ?></p>
            </div>
            <div class="flex items-center justify-between">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded focus:outline-none focus:shadow-outline" type="submit">
                    Login
                </button>
            </div>
        </form>
        <div class="text-center mt-4">
            <p class="text-gray-600">Belum punya akun? <a href="../user/register.php" class="text-blue-500 hover:underline">Registrasi</a></p>
        </div>
    </div>
    <script>
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        togglePassword.addEventListener('click', function () {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            this.classList.toggle('hidden');
        });
    </script>
</body>
</html>
