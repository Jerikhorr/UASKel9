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
    
    // Get event_id from the registration
    $query_get_event = "SELECT event_id FROM registrations WHERE id = ?";
    $stmt_get_event = mysqli_prepare($conn, $query_get_event);
    if ($stmt_get_event) {
        mysqli_stmt_bind_param($stmt_get_event, "i", $registration_id);
        mysqli_stmt_execute($stmt_get_event);
        mysqli_stmt_bind_result($stmt_get_event, $event_id);
        mysqli_stmt_fetch($stmt_get_event);
        mysqli_stmt_close($stmt_get_event);
        
        // Delete registration from the registrations table
        $query_cancel = "DELETE FROM registrations WHERE id = ?";
        $stmt_cancel = mysqli_prepare($conn, $query_cancel);

        if ($stmt_cancel) {
            mysqli_stmt_bind_param($stmt_cancel, "i", $registration_id);
            mysqli_stmt_execute($stmt_cancel);
            mysqli_stmt_close($stmt_cancel);

            // Update current_participants count in the events table
            $query_update_participants = "UPDATE events SET current_participants = current_participants - 1 WHERE id = ?";
            $stmt_update = mysqli_prepare($conn, $query_update_participants);
            if ($stmt_update) {
                mysqli_stmt_bind_param($stmt_update, "i", $event_id);
                mysqli_stmt_execute($stmt_update);
                mysqli_stmt_close($stmt_update);

                $_SESSION['message'] = "Registration successfully cancelled.";
            }
        }
    }
    
    header("Location: registered_event.php");
    exit();
}

// Fetch user's registered events with the same ordering as dashboard
$sql_registered = "SELECT e.*, 
                   r.id AS registration_id,
                   r.registration_date,
                   COALESCE(reg.registrant_count, 0) as current_participants
                   FROM events e 
                   JOIN registrations r ON e.id = r.event_id 
                   LEFT JOIN (
                       SELECT event_id, COUNT(*) as registrant_count
                       FROM registrations
                       GROUP BY event_id
                   ) reg ON e.id = reg.event_id
                   WHERE r.user_id = ?
                   ORDER BY 
                       CASE 
                           WHEN e.status = 'active' THEN 1
                           WHEN e.status = 'upcoming' THEN 2
                           ELSE 3 
                       END,
                       e.date ASC";

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
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
        }

        .event-card {
            background-color: white;
            border: 1px solid #e2e8f0;
            border-radius: 0.75rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
        }

        .banner-image {
            height: 150px;
            object-fit: cover;
            width: 100%;
            transition: transform 0.3s ease;
        }

        .event-card:hover .banner-image {
            transform: scale(1.05);
        }

        .card-body {
            padding: 1rem;
        }

        .event-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: #2d3748;
            margin-bottom: 0.5rem;
        }

        .event-description {
            color: #4a5568;
            font-size: 0.875rem;
            margin-bottom: 0.75rem;
            overflow: hidden;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            text-overflow: ellipsis;
        }

        .event-info {
            display: flex;
            align-items: center;
            font-size: 0.875rem;
            color: #718096;
            margin-bottom: 0.5rem;
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../includes/navbar_user.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold text-gray-900 mb-8 text-center">My Registered Events</h1>

        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-8">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if (mysqli_num_rows($result_registered) > 0): ?>
                <?php while($event = mysqli_fetch_assoc($result_registered)): 
                    $status = htmlspecialchars($event['status']);

                    switch ($status) {
                        case 'upcoming':
                            $status_class = 'bg-blue-100 text-blue-800';
                            $status_icon = 'fa-clock';
                            break;
                        case 'active':
                            $status_class = 'bg-green-100 text-green-800';
                            $status_icon = 'fa-check-circle';
                            break;
                        case 'canceled':
                            $status_class = 'bg-red-100 text-red-800';
                            $status_icon = 'fa-times-circle';
                            break;
                        case 'completed':
                            $status_class = 'bg-gray-100 text-gray-800';
                            $status_icon = 'fa-flag-checkered';
                            break;
                        default:
                            $status_class = 'bg-gray-100 text-gray-800';
                            $status_icon = 'fa-info-circle';
                    }
                ?>
                    <div class="event-card">
                        <div class="overflow-hidden">
                            <?php if($event['banner']): ?>
                                <img src="<?php echo htmlspecialchars($event['banner']); ?>" 
                                     class="banner-image" 
                                     alt="Event banner">
                            <?php endif; ?>
                        </div>
                        
                        <div class="card-body">
                            <h2 class="event-title">
                                <?php echo htmlspecialchars($event['name']); ?>
                            </h2>
                            
                            <p class="event-description">
                                <?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?>
                            </p>
                            
                            <div class="event-info">
                                <i class="fas fa-calendar-alt text-blue-500"></i>
                                <span><?php echo date('F d, Y', strtotime($event['date'])); ?>
                                at <?php echo date('h:i A', strtotime($event['time'])); ?></span>
                            </div>
                            
                            <div class="event-info">
                                <i class="fas fa-map-marker-alt text-red-500"></i>
                                <span><?php echo htmlspecialchars($event['location']); ?></span>
                            </div>

                            <div class="mt-4 flex items-center justify-between">
                                <span class="badge <?php echo $status_class; ?>">
                                    <i class="fas <?php echo $status_icon; ?>"></i>
                                    <?php echo ucfirst($status); ?>
                                </span>
                                
                                <div class="badge bg-gray-200 text-gray-700">
                                    <i class="fas fa-users text-gray-500"></i>
                                    <span><?php echo $event['current_participants']; ?>/<?php echo $event['max_participants']; ?></span>
                                </div>
                            </div>

                            <?php if ($status !== 'completed' && $status !== 'canceled'): ?>
    <form id="cancelForm-<?php echo $event['registration_id']; ?>" action="" method="post" class="mt-4">
        <input type="hidden" name="cancel_registration_id" value="<?php echo $event['registration_id']; ?>">
        <button type="button" onclick="showConfirmationModal(<?php echo $event['registration_id']; ?>)" class="w-full bg-red-500 hover:bg-red-600 text-white py-2 rounded-lg mt-3 transition-colors duration-300">
            Cancel Registration
        </button>
    </form>

    <!-- Modal Box -->
    <div id="confirmationModal-<?php echo $event['registration_id']; ?>" class="fixed inset-0 bg-gray-600 bg-opacity-50 flex items-center justify-center hidden">
        <div class="bg-white rounded-lg p-6 w-96">
            <h2 class="text-lg font-semibold mb-4">Konfirmasi Pembatalan</h2>
            <p>Apakah Anda yakin ingin membatalkan registrasi ini?</p>
            <div class="flex justify-end mt-6">
                <button type="button" onclick="hideConfirmationModal(<?php echo $event['registration_id']; ?>)" class="bg-gray-300 hover:bg-gray-400 text-gray-700 py-2 px-4 rounded-lg mr-2">
                    Batal
                </button>
                <button type="button" onclick="document.getElementById('cancelForm-<?php echo $event['registration_id']; ?>').submit()" class="bg-red-500 hover:bg-red-600 text-white py-2 px-4 rounded-lg">
                    Ya, Batalkan
                </button>
            </div>
        </div>
    </div>

    <script>
        function showConfirmationModal(id) {
            document.getElementById('confirmationModal-' + id).classList.remove('hidden');
        }

        function hideConfirmationModal(id) {
            document.getElementById('confirmationModal-' + id).classList.add('hidden');
        }
    </script>

                            <?php endif; ?>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-1 md:col-span-2 lg:col-span-3">
                    <p class="text-center text-gray-700">No events registered yet.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
