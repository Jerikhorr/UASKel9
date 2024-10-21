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

// Fetch available events
$result = $event->getAvailableEvents();

// Handle event registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['register_event'])) {
    $event_id = $_POST['event_id'];
    
    // Check if user is already registered
    if (!$registration->isUserRegistered($_SESSION['user_id'], $event_id)) {
        $registration->user_id = $_SESSION['user_id'];
        $registration->event_id = $event_id;
        
        if ($registration->register()) {
            $success_message = "You have successfully registered for the event!";
        } else {
            $error_message = "Registration failed. Please try again.";
        }
    } else {
        $error_message = "You are already registered for this event.";
    }
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Browse Events</title>
    <link href="../assets/css/tailwind.css" rel="stylesheet">
</head>
<body class="bg-gray-100">
    <div class="container mx-auto mt-10 px-4">
        <h1 class="text-3xl font-bold mb-5">Available Events</h1>
        
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
                        <?php
                        $registered = $registration->isUserRegistered($_SESSION['user_id'], $row['id']);
                        $full = $registration->getRegistrationCount($row['id']) >= $row['max_participants'];
                        ?>
                        <?php if ($registered) : ?>
                            <button class="bg-green-500 text-white font-bold py-2 px-4 rounded opacity-50 cursor-not-allowed" disabled>
                                Registered
                            </button>
                        <?php elseif ($full) : ?>
                            <button class="bg-red-500 text-white font-bold py-2 px-4 rounded opacity-50 cursor-not-allowed" disabled>
                                Event Full
                            </button>
                        <?php else : ?>
                            <form method="POST">
                                <input type="hidden" name="event_id" value="<?php echo $row['id']; ?>">
                                <button type="submit" name="register_event" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                    Register
                                </button>
                            </form>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>