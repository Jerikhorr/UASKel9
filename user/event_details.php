<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../user/login.php");
    exit();
}

if (!isset($_GET['id'])) {
    header("Location: dashboard_user.php");
    exit();
}

$event_id = $_GET['id'];
$user_id = $_SESSION['user_id'];
$conn = getDBConnection();

$query_event_details = "SELECT e.*, 
    (SELECT COUNT(*) FROM registrations WHERE event_id = e.id) AS current_participants 
    FROM events e WHERE e.id = ?";
$stmt_event = mysqli_prepare($conn, $query_event_details);
mysqli_stmt_bind_param($stmt_event, "i", $event_id);
mysqli_stmt_execute($stmt_event);
$result_event_details = mysqli_stmt_get_result($stmt_event);

if (mysqli_num_rows($result_event_details) == 0) {
    echo "Event not found.";
    exit();
}

$event = mysqli_fetch_assoc($result_event_details);
$registration_message = "";
$success_message = "";

// Process registration
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['action'])) {
    
    // Check if user is already registered for this event
    $check_registration = "SELECT * FROM registrations WHERE user_id = ? AND event_id = ?";
    $stmt_check = mysqli_prepare($conn, $check_registration);
    mysqli_stmt_bind_param($stmt_check, "ii", $user_id, $event_id);
    mysqli_stmt_execute($stmt_check);
    $result_check = mysqli_stmt_get_result($stmt_check);

    // Get max participants from event data
    $max_participants = (int)$event['max_participants'];

    if ($_POST['action'] === 'register') {
        // Ensure the user is not already registered and there's room for one more participant
        if (mysqli_num_rows($result_check) == 0 && $event['current_participants'] < $max_participants) {
            // Insert into registrations table
            $query_register = "INSERT INTO registrations (user_id, event_id) VALUES (?, ?)";
            $stmt = mysqli_prepare($conn, $query_register);
            mysqli_stmt_bind_param($stmt, "ii", $user_id, $event_id);
            mysqli_stmt_execute($stmt);

            // Set success message
            $success_message = "You have successfully registered for the event.";

            // Refresh event data to reflect changes
            mysqli_stmt_execute($stmt_event);
            $result_event_details = mysqli_stmt_get_result($stmt_event);
            $event = mysqli_fetch_assoc($result_event_details);
        } else {
            $registration_message = "You cannot register. Either you are already registered or the event is full.";
        }
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['name']); ?> - Event Details</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .banner-image {
            height: 300px;
            object-fit: cover;
            width: 100%;
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <!-- Navbar -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">
                <a href="dashboard_user.php" class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700 ">Back to Dashboard</a>
            </h1> 
            <div class="space-x-4">
                <a href="../user/profile.php" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                    Profile
                </a>
                <a href="../user/registered_event.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                    Registered Event
                </a>
            </div>
        </div>

        <!-- Event Details -->
        <div class="bg-white shadow-md rounded-lg overflow-hidden">
            <?php if ($event['banner']): ?>
                <img src="<?php echo htmlspecialchars($event['banner']); ?>" class="banner-image" alt="Event banner">
            <?php endif; ?>
            
            <div class="p-6">
                <!-- Display messages at the top -->
                <?php if (!empty($success_message)): ?>
                    <div class="bg-green-500 text-white p-4 rounded mb-4"><?php echo $success_message; ?></div>
                <?php endif; ?>
                <?php if (!empty($registration_message)): ?>
                    <div class="bg-red-500 text-white p-4 rounded mb-4"><?php echo $registration_message; ?></div>
                <?php endif; ?>

                <h2 class="text-2xl font-bold mb-4"><?php echo htmlspecialchars($event['name']); ?></h2>
                <h3 class="text-lg font-semibold">Description</h3>
                <p class="text-gray-700 mb-4"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>

                <div class="text-gray-600 mb-4">
                    <h3 class="text-lg font-semibold">Schedule</h3>
                    <p>
                        <i class="fas fa-calendar-alt"></i> 
                        <?php echo date('F d, Y', strtotime($event['date'])); ?>
                        at <?php echo date('h:i A', strtotime($event['time'])); ?>
                    </p>
                </div>

                <div class="text-gray-600 mb-4">
                    <h3 class="text-lg font-semibold">Location</h3>
                    <p>
                        <i class="fas fa-map-marker-alt"></i> 
                        <?php echo htmlspecialchars($event['location']); ?>
                    </p>
                </div>

                <div class="text-gray-600 mb-4">
                    <h3 class="text-lg font-semibold">Participants</h3>
                    <p>
                        <?php 
                            // Display current participants and max participants
                            $current_participants = (int)$event['current_participants'];
                            $max_participants = (int)$event['max_participants'];
                            echo htmlspecialchars($current_participants) . ' / ' . htmlspecialchars($max_participants) . ' participants';
                        ?>
                    </p>
                </div>

                <!-- Registration Form -->
                <form action="" method="POST" class="mb-4">
                    <input type="hidden" name="action" value="register">
                    <?php if ($event['status'] === 'active'): ?>
                        <button type="submit" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                            Register for Event
                        </button>
                    <?php else: ?>
                        <button type="button" class="bg-gray-400 text-white px-4 py-2 rounded cursor-not-allowed" disabled>
                            Registration Closed
                        </button>
                    <?php endif; ?>
                </form>
            </div>
        </div>
    </div>
</body>
</html>
