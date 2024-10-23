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

// Fetch registered events
$registeredEvents = $registration->getRegisteredEvents($_SESSION['user_id']);

// Handle event cancellation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_registration'])) {
    $event_id = $_POST['event_id'];

    if ($registration->cancelRegistration($_SESSION['user_id'], $event_id)) {
        $success_message = "You have successfully canceled your registration for the event.";
        // Refresh the list of registered events
        $registeredEvents = $registration->getRegisteredEvents($_SESSION['user_id']);
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
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h1 class="mb-4">My Registered Events</h1>

        <?php if (isset($success_message)) : ?>
            <div class="alert alert-success" role="alert">
                <?php echo $success_message; ?>
            </div>
        <?php endif; ?>

        <?php if (isset($error_message)) : ?>
            <div class="alert alert-danger" role="alert">
                <?php echo $error_message; ?>
            </div>
        <?php endif; ?>

        <?php if ($registeredEvents->num_rows > 0) : ?>
            <div class="row">
                <?php while ($row = $registeredEvents->fetch_assoc()) : ?>
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <?php if (!empty($row['banner_image'])) : ?>
                                <img src="<?php echo htmlspecialchars($row['banner_image']); ?>" alt="<?php echo htmlspecialchars($row['name']); ?>" class="card-img-top">
                            <?php endif; ?>
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($row['name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($row['description']); ?></p>
                                <p class="card-text"><strong>Date:</strong> <?php echo htmlspecialchars($row['date']); ?></p>
                                <p class="card-text"><strong>Time:</strong> <?php echo htmlspecialchars($row['time']); ?></p>
                                <p class="card-text"><strong>Location:</strong> <?php echo htmlspecialchars($row['location']); ?></p>
                            </div>
                            <div class="card-footer">
                                <form method="POST">
                                    <input type="hidden" name="event_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="cancel_registration" class="btn btn-danger btn-block">Cancel Registration</button>
                                </form>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else : ?>
            <p class="text-muted">You have not registered for any events.</p>
        <?php endif; ?>
    </div>
</body>
</html>

<?php
// Handle cancellation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_registration'])) {
    $event_id = $_POST['event_id'];
    
    if ($registration->cancelRegistration($_SESSION['user_id'], $event_id)) {
        $success_message = "Event registration has been cancelled successfully.";
    } else {
        $error_message = "Failed to cancel registration. Please try again.";
    }
}

// Fetch user's registered events
$registered_events = $registration->getUserRegisteredEvents($_SESSION['user_id']);
?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">My Registered Events</h1>
    </div>

    <?php if (isset($success_message)) : ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?php echo $success_message; ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)) : ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>

    <?php if ($registered_events->num_rows > 0) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($event = $registered_events->fetch_assoc()) : ?>
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <?php if (!empty($event['banner_image'])) : ?>
                        <img src="<?php echo htmlspecialchars($event['banner_image']); ?>" 
                             alt="<?php echo htmlspecialchars($event['name']); ?>" 
                             class="w-full h-48 object-cover">
                    <?php endif; ?>
                    <div class="p-6">
                        <h2 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($event['name']); ?></h2>
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($event['description']); ?></p>
                        <div class="space-y-2 mb-4">
                            <p class="text-sm text-gray-500">
                                <strong>Date:</strong> <?php echo htmlspecialchars($event['date']); ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                <strong>Time:</strong> <?php echo htmlspecialchars($event['time']); ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?>
                            </p>
                        </div>
                        
                        <!-- Cancel Registration Button with Confirmation Dialog -->
                        <div x-data="{ showModal: false }">
                            <button @click="showModal = true" 
                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded w-full">
                                Cancel Registration
                            </button>

                            <!-- Modal -->
                            <div x-show="showModal" 
                                 class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                <div class="bg-white p-6 rounded-lg shadow-lg max-w-sm mx-4">
                                    <h3 class="text-lg font-bold mb-4">Confirm Cancellation</h3>
                                    <p class="mb-4">Are you sure you want to cancel your registration for this event?</p>
                                    <div class="flex justify-end space-x-3">
                                        <button @click="showModal = false" 
                                                class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded">
                                            No, Keep
                                        </button>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <button type="submit" 
                                                    name="cancel_registration" 
                                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                                Yes, Cancel
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else : ?>
        <div class="bg-white shadow-md rounded-lg p-6 text-center">
            <p class="text-gray-600">You haven't registered for any events yet.</p>
            <a href="?page=browse_events" class="inline-block mt-4 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Browse Available Events
            </a>
        </div>
    <?php endif; ?>
</div>