<?php
// registered_event.php (ubah rute dari registered_events.php)
session_start();
require_once '../config/config.php';

$user_id = $_SESSION['user_id'];

// Fetch user's registered events
$sql_registered = "SELECT e.* FROM events e 
                   JOIN registrations r ON e.id = r.event_id 
                   WHERE r.user_id = ?";
$stmt = mysqli_prepare($conn, $sql_registered);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result_registered = mysqli_stmt_get_result($stmt);
} else {
    die("Error preparing statement: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Registered Events</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <h1>My Registered Events</h1>

        <!-- Back to Dashboard Button -->
        <div class="mb-3">
            <a href="dashboard_user.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <?php if (mysqli_num_rows($result_registered) > 0): ?>
            <div class="row">
                <?php while($event = mysqli_fetch_assoc($result_registered)): ?>
                    <div class="col-md-4">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo htmlspecialchars($event['name']); ?></h5>
                                <p class="card-text"><?php echo htmlspecialchars($event['description']); ?></p>
                                <p class="card-text"><strong>Date:</strong> <?php echo date('F d, Y', strtotime($event['date'])); ?></p>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            </div>
        <?php else: ?>
            <div class="alert alert-warning">
                <strong>You have not registered for any events.</strong>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
