<?php
// dashboard_user.php
session_start();
require_once '../config/config.php';

// Fetch all available events
$sql_events = "SELECT e.*, 
               (SELECT COUNT(*) FROM registrations er WHERE er.event_id = e.id) as current_participants 
               FROM events e 
               WHERE e.date >= CURRENT_DATE AND e.status = 'active' 
               ORDER BY e.date ASC";
$result_events = mysqli_query($conn, $sql_events);

if (!$result_events) {
    die("Error fetching available events: " . mysqli_error($conn));
}

// Fetch user's registered events
$user_id = $_SESSION['user_id'];
$sql_registered = "SELECT event_id FROM registrations WHERE user_id = ?";
$stmt = mysqli_prepare($conn, $sql_registered);

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);
    $result_registered = mysqli_stmt_get_result($stmt);
    
    // Store registered event IDs in an array
    $registered_events = [];
    while ($reg_event = mysqli_fetch_assoc($result_registered)) {
        $registered_events[] = $reg_event['event_id'];
    }
} else {
    die("Error preparing statement: " . mysqli_error($conn));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Events</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Your existing styles */
    </style>
</head>
<body>
    <div class="container">
        <!-- Alert Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-success">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <h1 class="page-title">Available Events</h1>

        <!-- Button to Register for Events -->
        <div class="mb-3">
            <a href="registered_event.php" class="btn btn-primary">My Registered Events</a> <!-- Ubah route ke registered_event.php -->
        </div>

        <!-- Available Events Display -->
        <div class="row">
            <?php if (mysqli_num_rows($result_events) > 0): ?>
                <?php while($event = mysqli_fetch_assoc($result_events)): 
                    // Check if user is already registered
                    $is_registered = in_array($event['id'], $registered_events);
                ?>
                    <div class="col-md-4">
                        <a href="event_details.php?id=<?php echo $event['id']; ?>" class="card-link">
                            <div class="card event-card">
                                <?php if($is_registered): ?>
                                    <div class="registered-badge">
                                        <span class="badge badge-success">Registered</span>
                                    </div>
                                <?php endif; ?>
                                
                                <?php if($event['banner']): ?>
                                    <img src="<?php echo htmlspecialchars($event['banner']); ?>" 
                                         class="banner-image" 
                                         alt="Event banner">
                                <?php endif; ?>
                                
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($event['name']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?></p>
                                    
                                    <div class="event-info">
                                        <div class="event-date">
                                            <i class="fas fa-calendar-alt"></i>
                                            <?php echo date('F d, Y', strtotime($event['date'])); ?>
                                            at <?php echo date('h:i A', strtotime($event['time'])); ?>
                                        </div>
                                        <div class="event-location">
                                            <i class="fas fa-map-marker-alt"></i>
                                            <?php echo htmlspecialchars($event['location']); ?>
                                        </div>
                                    </div>
                                    
                                    <div class="participants-badge">
                                        <span class="badge badge-info">
                                            <?php echo $event['current_participants']; ?>/<?php echo $event['max_participants']; ?> participants
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-12">
                    <div class="alert alert-warning">
                        <strong>No events available at the moment.</strong>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
</body>
</html>
