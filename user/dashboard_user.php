<?php
// dashboard_user.php
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
<<<<<<< Updated upstream
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
=======
        <h1 class="text-3xl font-semibold mb-8">Available Events</h1>

        <!-- Tampilkan Event yang Tersedia -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if (mysqli_num_rows($result_events) > 0): ?>
                <?php while($event = mysqli_fetch_assoc($result_events)): 
                    $is_registered = in_array($event['id'], $registered_events);
                    $status = htmlspecialchars($event['status']);

                    // Tentukan warna berdasarkan status
                    switch ($status) {
                        case 'upcoming':
                            $status_class = 'bg-blue-100 text-blue-800';
                            break;
                        case 'active':
                            $status_class = 'bg-green-100 text-green-800';
                            break;
                        case 'canceled':
                            $status_class = 'bg-red-100 text-red-800';
                            break;
                        case 'completed':
                            $status_class = 'bg-gray-100 text-gray-800';
                            break;
                        default:
                            $status_class = 'bg-gray-200 text-gray-800';
                    }
                ?>
                    <div class="relative bg-white shadow-lg rounded-lg event-card" onclick="window.location.href='event_details.php?id=<?php echo $event['id']; ?>'">
                        <?php if($is_registered): ?>
                            <div class="registered-badge">Registered</div>
                        <?php endif; ?>

                        <?php if($event['banner']): ?>
                            <img src="<?php echo htmlspecialchars($event['banner']); ?>" 
                                 class="banner-image" 
                                 alt="Event banner">
                        <?php endif; ?>
                        
                        <div class="p-6">
                            <h2 class="text-xl font-bold mb-3"><?php echo htmlspecialchars($event['name']); ?></h2>
                            <p class="text-gray-600 mb-3">
                                <?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?>
                            </p>
                            
                            <div class="text-sm text-gray-500 mb-2">
                                <i class="fas fa-calendar-alt"></i> 
                                <?php echo date('F d, Y', strtotime($event['date'])); ?>
                                at <?php echo date('h:i A', strtotime($event['time'])); ?>
                            </div>
                            <div class="text-sm text-gray-500 mb-2">
                                <i class="fas fa-map-marker-alt"></i> 
                                <?php echo htmlspecialchars($event['location']); ?>
                            </div>

                            <div class="mt-4">
                                <span class="status-badge <?php echo $status_class; ?>">
                                    Status: <?php echo ucfirst($status); ?>
                                </span>
                                <span class="ml-2 text-gray-700">
                                    <?php echo $event['current_participants']; ?>/<?php echo $event['max_participants']; ?> participants
                                </span>
                            </div>
                        </div>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-1 md:col-span-2 lg:col-span-3">
                    <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4">
>>>>>>> Stashed changes
                        <strong>No events available at the moment.</strong>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
</body>
</html>
