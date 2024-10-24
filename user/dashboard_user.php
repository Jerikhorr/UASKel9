<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../user/login.php");
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
    e.date ASC"; // Urutkan berdasarkan tanggal setelah status

$result_events = mysqli_query($conn, $query_events);

// Ambil daftar event yang sudah didaftarkan user
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
    <style>
        .event-card {
            transition: transform 0.2s;
            cursor: pointer;
        }
        .event-card:hover {
            transform: scale(1.02);
        }
        .banner-image {
            height: 200px;
            object-fit: cover;
            width: 100%;
            border-top-left-radius: 0.5rem;
            border-top-right-radius: 0.5rem;
        }
        .registered-badge {
            position: absolute;
            top: 10px;
            left: 10px;
            background-color: #28a745;
            color: white;
            padding: 5px 10px;
            border-radius: 5px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="container mx-auto px-4 py-8">
        <!-- Navbar -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">
                <a href="../user/login.php" class="text-black hover:no-underline">User Dashboard</a>
            </h1>
            <div class="space-x-4">
                <a href="../user/profile.php" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                    Profile
                </a>
                <a href="../user/registered_event.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                    Registered Event
                </a>
            </div>
        </div>

        <!-- Alert Messages -->
        <?php if (isset($_SESSION['message'])): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4" role="alert">
                <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
            </div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4" role="alert">
                <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
            </div>
        <?php endif; ?>

        <h1 class="text-2xl font-semibold mb-6">Available Events</h1>

<!-- Available Events Display -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
    <?php if (mysqli_num_rows($result_events) > 0): ?>
        <?php while($event = mysqli_fetch_assoc($result_events)): 
            $is_registered = in_array($event['id'], $registered_events);
            // Ambil status dari database
            $status = htmlspecialchars($event['status']); // Pastikan untuk mengamankan output

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
                    $status_class = 'bg-gray-200 text-gray-800'; // Default color if status is unknown
            }
        ?>
            <div class="relative bg-white shadow-md rounded-lg overflow-hidden event-card" onclick="window.location.href='event_details.php?id=<?php echo $event['id']; ?>'">
                <?php if($is_registered): ?>
                    <div class="registered-badge">
                        Registered
                    </div>
                <?php endif; ?>
                
                <?php if($event['banner']): ?>
                    <img src="<?php echo htmlspecialchars($event['banner']); ?>" 
                         class="banner-image" 
                         alt="Event banner">
                <?php endif; ?>
                
                <div class="p-4">
                    <h2 class="text-xl font-bold mb-2"><?php echo htmlspecialchars($event['name']); ?></h2>
                    <p class="text-gray-700 mb-4"><?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?></p>
                    
                    <div class="text-sm text-gray-600 mb-2">
                        <i class="fas fa-calendar-alt"></i> 
                        <?php echo date('F d, Y', strtotime($event['date'])); ?>
                        at <?php echo date('h:i A', strtotime($event['time'])); ?>
                    </div>
                    <div class="text-sm text-gray-600">
                        <i class="fas fa-map-marker-alt"></i> 
                        <?php echo htmlspecialchars($event['location']); ?>
                    </div>

                    <div class="mt-3">
                        <span class="bg-blue-100 text-blue-800 text-xs font-medium mr-2 px-2.5 py-0.5 rounded">
                            <?php echo $event['current_participants']; ?>/<?php echo $event['max_participants']; ?> participants
                        </span>
                    </div>
                    
                    <!-- Tampilkan Status Event dari Database dengan Warna Berdasarkan Status -->
                    <div class="mt-2">
                        <span class="<?php echo $status_class; ?> text-xs font-medium mr-2 px-2.5 py-0.5 rounded">
                            Status: <?php echo $status; ?>
                        </span>
                    </div>
                </div>
            </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="col-span-1 md:col-span-2 lg:col-span-3">
            <div class="bg-yellow-100 border-l-4 border-yellow-500 text-yellow-700 p-4">
                <strong>No events available at the moment.</strong>
            </div>
        </div>
    <?php endif; ?>
</div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js"></script>
</body>
</html>
