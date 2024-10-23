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
