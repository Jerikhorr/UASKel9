<?php
session_start();
require_once '../config/config.php';

<<<<<<< Updated upstream
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
=======
// Cek apakah user sudah login dan peran adalah 'user'
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../user/login.php");
    exit();
}

// Jika tombol logout diklik
if (isset($_POST['logout'])) {
    session_destroy(); // Menghapus semua session
    header("Location: ../user/login.php"); // Redirect ke halaman login
    exit();
}

$conn = getDBConnection();

// Query untuk mendapatkan semua event dengan prioritas status
$query_events = "SELECT 
    e.*, 
    COALESCE(r.registrant_count, 0) as current_participants
FROM 
    events e
LEFT JOIN (
    SELECT 
        event_id, 
        COUNT(*) as registrant_count
    FROM 
        registrations
    GROUP BY 
        event_id
) r ON e.id = r.event_id
ORDER BY 
    CASE 
        WHEN e.status = 'active' THEN 1
        WHEN e.status = 'upcoming' THEN 2
        ELSE 3 
    END,
    e.date ASC";

$result_events = mysqli_query($conn, $query_events);

// Ambil daftar event yang sudah didaftarkan user
>>>>>>> Stashed changes
$user_id = $_SESSION['user_id'];
$registered_events_query = "SELECT event_id FROM registrations WHERE user_id = '$user_id'";
$result_registered_events = mysqli_query($conn, $registered_events_query);
$registered_events = [];
if ($result_registered_events) {
    while ($row = mysqli_fetch_assoc($result_registered_events)) {
        $registered_events[] = $row['event_id'];
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Events</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
<<<<<<< Updated upstream
        /* Your existing styles */
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../includes/navbar_user.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message bg-green-100 border-l-4 border-green-500 text-green-700">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            </div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
=======
        /* Kustomisasi tampilan */
        .event-card {
            transition: transform 0.3s ease-in-out, box-shadow 0.3s ease-in-out;
            cursor: pointer;
            border-radius: 0.5rem;
            overflow: hidden;
        }

        .event-card:hover {
            transform: scale(1.03);
            box-shadow: 0px 8px 15px rgba(0, 0, 0, 0.1);
        }

        .banner-image {
            height: 180px;
            object-fit: cover;
            width: 100%;
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
        }

        .registered-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: #4CAF50;
            color: white;
            padding: 6px 12px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 0.25rem;
        }

        .status-badge {
            font-size: 0.875rem;
            font-weight: bold;
            border-radius: 0.375rem;
            padding: 0.25rem 0.5rem;
            display: inline-block;
        }

        .message {
            padding: 1rem;
            border-radius: 0.5rem;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <?php include 'navbar_user.php'; ?>

    <!-- Tombol Logout -->
    <div class="flex justify-end mr-8 mt-4">
        <form method="POST">
            <button type="submit" name="logout" class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                Logout
            </button>
        </form>
    </div>

    <!-- Container Utama -->
    <div class="container mx-auto px-4 py-8">
        <!-- Pesan Peringatan -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message bg-green-100 border-l-4 border-green-500 text-green-700" role="alert">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="message bg-red-100 border-l-4 border-red-500 text-red-700" role="alert">
>>>>>>> Stashed changes
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

<<<<<<< Updated upstream
        <h1 class="page-title">Available Events</h1>

        <!-- Button to Register for Events -->
        <div class="mb-3">
            <a href="registered_event.php" class="btn btn-primary">My Registered Events</a> <!-- Ubah route ke registered_event.php -->
        </div>

        <!-- Available Events Display -->
        <div class="row">
            <?php if (mysqli_num_rows($result_events) > 0): ?>
                <?php while($event = mysqli_fetch_assoc($result_events)): 
                    $is_registered = in_array($event['id'], $registered_events);
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
                    <div class="event-card rounded-xl">
                        <?php if($is_registered): ?>
                            <div class="registered-badge">
                                <i class="fas fa-check-circle mr-1"></i> Registered
                            </div>
                        <?php endif; ?>

                        <a href="event_details.php?id=<?php echo $event['id']; ?>" class="block">
                            <?php if($event['banner']): ?>
                                <div class="overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($event['banner']); ?>" 
                                        class="banner-image" 
                                        alt="Event banner">
                                </div>
                            <?php endif; ?>
                            
                            <div class="p-6">
                                <h2 class="text-xl font-bold mb-3 text-gray-900">
                                    <?php echo htmlspecialchars($event['name']); ?>
                                </h2>
                                
                                <p class="text-gray-600 mb-4 line-clamp-2">
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

                                <div class="mt-6 flex items-center justify-between">
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <i class="fas <?php echo $status_icon; ?>"></i>
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                    
                                    <div class="participants-badge">
                                        <i class="fas fa-users text-gray-500"></i>
                                        <span><?php echo $event['current_participants']; ?>/<?php echo $event['max_participants']; ?></span>
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
