<?php
// event_details.php
session_start();
require_once '../config/config.php';

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No event specified";
    header("Location: dashboard_user.php");
    exit();
}

$event_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

// Fetch event details
$sql = "SELECT e.*, 
        (SELECT COUNT(*) FROM registrations er WHERE er.event_id = e.id) as current_participants,
        (SELECT COUNT(*) FROM registrations er WHERE er.event_id = e.id AND er.user_id = ?) as is_registered
        FROM events e 
        WHERE e.id = ? AND e.date >= CURRENT_DATE AND e.status = 'active'";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $user_id, $event_id);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

if (!$result || mysqli_num_rows($result) == 0) {
    $_SESSION['error'] = "Event not found";
    header("Location: dashboard_user.php");
    exit();
}

$event = mysqli_fetch_assoc($result);
$is_registered = $event['is_registered'] > 0;
$is_full = ($event['current_participants'] >= $event['max_participants']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['name']); ?> - Event Details</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .event-image { 
            width: 100%; 
            max-height: 400px; 
            object-fit: cover; 
        }
        .registration-section {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-top: 20px;
        }
    </style>
</head>
<<<<<<< Updated upstream
<body>
    <div class="container py-4">
        <div class="row">
            <div class="col-12">
                <a href="dashboard_user.php" class="btn btn-secondary mb-4">← Back to Dashboard</a>
                
                <?php if (isset($_SESSION['message'])): ?>
                    <div class="alert alert-success">
                        <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                    </div>
                <?php elseif (isset($_SESSION['error'])): ?>
                    <div class="alert alert-danger">
                        <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <?php if($event['banner']): ?>
                    <img src="<?php echo htmlspecialchars($event['banner']); ?>" 
                         class="event-image mb-4" 
                         alt="Event banner">
=======
<body class="bg-gray-100">
    <?php include 'navbar_user.php'; ?>

    <div class="container mx-auto px-4 py-8">
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
>>>>>>> Stashed changes
                <?php endif; ?>

                <h1 class="mb-4"><?php echo htmlspecialchars($event['name']); ?></h1>
                
                <div class="mb-4">
                    <h5>Event Description</h5>
                    <p><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>
                </div>

                <div class="mb-4">
                    <h5>Event Details</h5>
                    <p><strong>Date:</strong> <?php echo date('F d, Y', strtotime($event['date'])); ?></p>
                    <p><strong>Time:</strong> <?php echo date('h:i A', strtotime($event['time'])); ?></p>
                    <p><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                    <p><strong>Participants:</strong> <?php echo $event['current_participants']; ?>/<?php echo $event['max_participants']; ?></p>
                </div>
            </div>

            <div class="col-md-4">
                <div class="registration-section">
                    <h4 class="mb-3">Registration Status</h4>
                    <?php if($is_registered): ?>
                        <div class="alert alert-success">
                            You are registered for this event!
                        </div>
                        <form action="cancel_registration.php" method="POST">
                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                            <button type="submit" class="btn btn-danger w-100">Cancel Registration</button>
                        </form>
                    <?php elseif($is_full): ?>
                        <div class="alert alert-warning">
                            This event is currently full.
                        </div>
                    <?php else: ?>
                        <p>Spots remaining: <?php echo $event['max_participants'] - $event['current_participants']; ?></p>
                        <form action="register_event.php" method="POST">
                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                            <button type="submit" class="btn btn-success w-100">Register for Event</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>