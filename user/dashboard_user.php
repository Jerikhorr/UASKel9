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
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <h1 class="mb-4">Available Events</h1>
        
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

        <div class="row">
            <?php while ($row = $result->fetch_assoc()) : ?>
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
                            <?php
                            $registered = $registration->isUserRegistered($_SESSION['user_id'], $row['id']);
                            $full = $registration->getRegistrationCount($row['id']) >= $row['max_participants'];
                            ?>
                            <?php if ($registered) : ?>
                                <button class="btn btn-success btn-block" disabled>Registered</button>
                            <?php elseif ($full) : ?>
                                <button class="btn btn-danger btn-block" disabled>Event Full</button>
                            <?php else : ?>
                                <form method="POST">
                                    <input type="hidden" name="event_id" value="<?php echo $row['id']; ?>">
                                    <button type="submit" name="register_event" class="btn btn-primary btn-block">Register</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</body>
</html>
