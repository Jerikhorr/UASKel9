<?php
session_start();
require_once '../includes/config.php';
require_once '../includes/db_connect.php';
require_once '../classes/Event.php';
require_once '../classes/Registration.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$db = getDBConnection();
$event = new Event($db);
$registration = new Registration($db);

// Fetch user's registered events
$result = $registration->getUserRegisteredEvents($_SESSION['user_id']);

// Handle event cancellation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_registration'])) {
    $event_id = $_POST['event_id'];
    
    // Cancel the registration
    if ($registration->cancelRegistration($_SESSION['user_id'], $event_id)) {
        $success_message = "You have successfully canceled your registration.";
    } else {
        $error_message = "Cancellation failed. Please try again.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Registered Events</title>
    <link href="../assets/css/tailwind.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto mt-10 px-4">
        <h1 class="text-3xl font-bold mb-5">My Registered Events</h1>
        
        <?php if (isset($success_message)) : ?>
            <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $success_message; ?></span>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)) : ?>
            <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
                <span class="block sm:inline"><?php echo $error_message; ?></span>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php if ($result->num_rows > 0) : ?>
                <?php while ($row = $result->fetch_assoc()) : ?>
                    <div class="bg-white shadow-md rounded-lg overflow-hidden">
                        <?php if (!empty($row['banner_image'])) : ?>
                            <img src="<?php echo htmlspecialchars($row['banner_image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="w-full h-48 object-cover">
                        <?php endif; ?>
                        <div class="p-6">
                            <h2 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($row['name']); ?></h2>
                            <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($row['description']); ?></p>
                            <p class="text-sm text-gray-500 mb-2">
                                <strong>Date:</strong> <?php echo htmlspecialchars($row['date']); ?>
                            </p>
                            <p class="text-sm text-gray-500 mb-2">
                                <strong>Time:</strong> <?php echo htmlspecialchars($row['time']); ?>
                            </p>
                            <p class="text-sm text-gray-500 mb-4">
                                <strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?>
                            </p>
                            <form method="POST">
                                <input type="hidden" name="event_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="cancel_registration" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                    Cancel Registration
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else : ?>
                <p class="text-gray-500 text-lg">You have not registered for any events.</p>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
