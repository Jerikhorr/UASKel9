<?php
session_start();
require_once '../config/config.php';

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../user/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle cancellation request
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['cancel_registration_id'])) {
    $registration_id = $_POST['cancel_registration_id'];

    // Delete registration from the registrations table
    $query_cancel = "DELETE FROM registrations WHERE id = ?";
    $stmt_cancel = mysqli_prepare($conn, $query_cancel);

    if ($stmt_cancel) {
        mysqli_stmt_bind_param($stmt_cancel, "i", $registration_id);
        mysqli_stmt_execute($stmt_cancel);

        // Update current_participants count in the events table
        $query_update_participants = "UPDATE events SET current_participants = current_participants - 1 WHERE id = (SELECT event_id FROM registrations WHERE id = ?)";
        $stmt_update = mysqli_prepare($conn, $query_update_participants);
        
        if ($stmt_update) {
            mysqli_stmt_bind_param($stmt_update, "i", $registration_id);
            mysqli_stmt_execute($stmt_update);
        }
    }
}

// Fetch user's registered events along with participants info
$sql_registered = "SELECT e.*, 
                   (SELECT COUNT(*) FROM registrations WHERE event_id = e.id) AS current_participants,
                   r.id AS registration_id 
                   FROM events e 
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
    <style>
        .banner-image {
            width: 100%; /* Atur lebar gambar */
            height: 200px; /* Atur tinggi gambar */
            object-fit: cover; /* Pastikan gambar tetap proporsional */
        }
    </style>
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
                                <?php if($event['banner']): ?>
                                    <img src="<?php echo htmlspecialchars($event['banner']); ?>" 
                                         class="banner-image" 
                                         alt="Event banner">
                                <?php endif; ?>
                                <h4 class="card-title"><?php echo htmlspecialchars($event['name']); ?></h4> 
                                <p class="card-text"><?php echo htmlspecialchars($event['description']); ?></p>
                                <p class="card-text"><strong>Date:</strong> <?php echo date('F d, Y', strtotime($event['date'])); ?></p>
                                <p class="card-text"><strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?></p>
                                <p class="card-text">
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
                                </p>

                                <a href="event_details.php?id=<?php echo $event['id']; ?>" class="btn btn-info">View Details</a>

                                <!-- Cancel Registration Button -->
                                <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#confirmCancelModal<?php echo $event['registration_id']; ?>">
                                    Cancel Registration
                                </button>

                                <!-- Confirmation Modal -->
                                <div class="modal fade" id="confirmCancelModal<?php echo $event['registration_id']; ?>" tabindex="-1" aria-labelledby="confirmCancelModalLabel" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="confirmCancelModalLabel">Confirm Cancellation</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                            </div>
                                            <div class="modal-body">
                                                Are you sure you want to cancel your registration for "<?php echo htmlspecialchars($event['name']); ?>"?
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                <form action="registered_event.php" method="POST">
                                                    <input type="hidden" name="cancel_registration_id" value="<?php echo $event['registration_id']; ?>">
                                                    <button type="submit" class="btn btn-danger">Yes, Cancel Registration</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>

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
