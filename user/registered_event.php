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
            
            // Add success message
            $_SESSION['message'] = "Registration successfully cancelled.";
            $_SESSION['message_type'] = "success";
        }
    }
    
    // Redirect to refresh the page
    header("Location: registered_event.php");
    exit();
}

// Fetch user's registered events along with participants info
$sql_registered = "SELECT e.*, 
                   (SELECT COUNT(*) FROM registrations WHERE event_id = e.id) AS current_participants,
                   r.id AS registration_id,
                   r.registration_date
                   FROM events e 
                   JOIN registrations r ON e.id = r.event_id 
                   WHERE r.user_id = ?
                   ORDER BY e.date ASC";
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
            font-family: 'Segoe UI', system-ui, -apple-system, sans-serif;
            color: #333;
        }

        .page-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem;
        }

        .header {
            text-align: center;
            margin: 2rem 0 4rem;
            position: relative;
        }

        .header::after {
            content: '';
            position: absolute;
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            width: 100px;
            height: 4px;
            background: linear-gradient(90deg, #007bff, #00d4ff);
            border-radius: 2px;
        }

        .header h1 {
            font-size: 2.75rem;
            font-weight: 700;
            color: #2d3748;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 1rem;
        }

        .header p {
            color: #6c757d;
            font-size: 1.1rem;
        }

        .event-card {
            background: white;
            border-radius: 1rem;
            overflow: hidden;
            margin-bottom: 2rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 
                        0 2px 4px -1px rgba(0, 0, 0, 0.06);
            transition: all 0.3s ease;
        }

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1),
                        0 4px 6px -2px rgba(0, 0, 0, 0.05);
        }

        .banner-wrapper {
            position: relative;
            height: 220px;
            overflow: hidden;
        }

        .banner-image {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s ease;
        }

        .event-card:hover .banner-image {
            transform: scale(1.05);
        }

        .event-status {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: rgba(255, 255, 255, 0.95);
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 600;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .card-content {
            padding: 1.5rem;
        }

        .event-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .event-info {
            margin-bottom: 1rem;
        }

        .info-item {
            display: flex;
            align-items: center;
            margin-bottom: 0.5rem;
            color: #4a5568;
        }

        .info-item i {
            width: 20px;
            margin-right: 0.5rem;
            color: #007bff;
        }

        .participants-bar {
            background: #e9ecef;
            border-radius: 0.5rem;
            height: 8px;
            margin: 0.5rem 0 1rem;
            overflow: hidden;
        }

        .participants-progress {
            height: 100%;
            background: linear-gradient(90deg, #007bff, #00d4ff);
            transition: width 0.3s ease;
        }

        .btn-group {
            display: flex;
            gap: 0.5rem;
            margin-top: 1.5rem;
        }

        .btn {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .btn-view {
            background: #007bff;
            color: white;
            border: none;
        }

        .btn-view:hover {
            background: #0056b3;
            transform: translateY(-2px);
        }

        .btn-cancel {
            background: #dc3545;
            color: white;
            border: none;
        }

        .btn-cancel:hover {
            background: #c82333;
            transform: translateY(-2px);
        }

        .modal-content {
            border-radius: 1rem;
        }

        .modal-header {
            background: #f8f9fa;
            border-bottom: 2px solid #e9ecef;
        }

        .modal-footer {
            background: #f8f9fa;
            border-top: 2px solid #e9ecef;
        }

        .alert {
            border-radius: 0.5rem;
            margin-bottom: 2rem;
        }

        /* Empty state styling */
        .empty-state {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 1rem;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        .empty-state i {
            font-size: 4rem;
            color: #007bff;
            margin-bottom: 1rem;
        }

        .empty-state h3 {
            color: #2d3748;
            margin-bottom: 1rem;
        }

        .empty-state p {
            color: #6c757d;
            margin-bottom: 1.5rem;
        }

        .empty-state .btn {
            padding: 0.75rem 2rem;
        }
    </style>
</head>
<body>
    <?php include '../includes/navbar_user.php'; ?>
    
    <div class="page-container">
        <div class="header">
            <h1>My Registered Events</h1>
            <p>Manage your upcoming event registrations</p>
        </div>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="alert alert-<?php echo $_SESSION['message_type']; ?> alert-dismissible fade show" role="alert">
                <?php 
                    echo $_SESSION['message'];
                    unset($_SESSION['message']);
                    unset($_SESSION['message_type']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (mysqli_num_rows($result_registered) > 0): ?>
            <div class="row">
                <?php while($event = mysqli_fetch_assoc($result_registered)): 
                    $event_date = strtotime($event['date']);
                    $current_date = time();
                    $status = ($event_date < $current_date) ? 'Completed' : 'Upcoming';
                    $status_color = ($event_date < $current_date) ? 'text-gray-600' : 'text-green-600';
                    
                    $participants_percentage = ($event['current_participants'] / $event['max_participants']) * 100;
                ?>
                    <div class="col-md-6 col-lg-4">
                        <div class="event-card">
                            <div class="banner-wrapper">
                                <?php if($event['banner']): ?>
                                    <img src="<?php echo htmlspecialchars($event['banner']); ?>" 
                                         class="banner-image" 
                                         alt="Event banner">
                                <?php else: ?>
                                    <img src="../assets/default-event-banner.jpg" 
                                         class="banner-image" 
                                         alt="Default event banner">
                                <?php endif; ?>
                                <div class="event-status <?php echo $status_color; ?>">
                                    <?php echo $status; ?>
                                </div>
                            </div>
                            
                            <div class="card-content">
                                <h3 class="event-title"><?php echo htmlspecialchars($event['name']); ?></h3>
                                
                                <div class="event-info">
                                    <div class="info-item">
                                        <i class="fas fa-calendar"></i>
                                        <span><?php echo date('F d, Y', strtotime($event['date'])); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-clock"></i>
                                        <span><?php echo date('h:i A', strtotime($event['date'])); ?></span>
                                    </div>
                                    <div class="info-item">
                                        <i class="fas fa-map-marker-alt"></i>
                                        <span><?php echo htmlspecialchars($event['location']); ?></span>
                                    </div>
                                </div>

                                <div class="participants-info">
                                    <div class="d-flex justify-content-between">
                                        <span>Participants</span>
                                        <span><?php echo $event['current_participants']; ?> / <?php echo $event['max_participants']; ?></span>
                                    </div>
                                    <div class="participants-bar">
                                        <div class="participants-progress" style="width: <?php echo $participants_percentage; ?>%"></div>
                                    </div>
                                </div>

                                <div class="btn-group">
                                    <a href="event_details.php?id=<?php echo $event['id']; ?>" 
                                       class="btn btn-view flex-grow-1">
                                        <i class="fas fa-eye me-2"></i>View Details
                                    </a>
                                    <?php if ($status !== 'Completed'): ?>
                                        <button type="button" 
                                                class="btn btn-cancel" 
                                                data-bs-toggle="modal" 
                                                data-bs-target="#confirmCancelModal<?php echo $event['registration_id']; ?>">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <!-- Confirmation Modal -->
                        <div class="modal fade" id="confirmCancelModal<?php echo $event['registration_id']; ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title">Confirm Cancellation</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Are you sure you want to cancel your registration for "<strong><?php echo htmlspecialchars($event['name']); ?></strong>"?</p>
                                        <p class="text-muted mt-2">This action cannot be undone.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Registration</button>
                                        <form action="registered_event.php" method="POST">
                                            <input type="hidden" name="cancel_registration_id" value="<?php echo $event['registration_id']; ?>">
                                            <button type="submit" class="btn btn-danger">
                                                <i class="fas fa-times me-2"></i>Cancel Registration
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
                </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-calendar-times"></i>
                <h3>No Registered Events</h3>
                <p>You haven't registered for any events yet. Browse our upcoming events and join ones that interest you!</p>
                <a href="events.php" class="btn btn-primary">
                    <i class="fas fa-search me-2"></i>Browse Events
                </a>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-hide alerts after 5 seconds
        document.addEventListener('DOMContentLoaded', function() {
            let alerts = document.querySelectorAll('.alert');
            alerts.forEach(function(alert) {
                setTimeout(function() {
                    let bsAlert = new bootstrap.Alert(alert);
                    bsAlert.close();
                }, 5000);
            });
        });
    </script>
</body>
</html>