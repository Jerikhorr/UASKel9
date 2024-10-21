<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

// Pastikan pengguna sudah login dan memiliki role admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit();
}

// Dapatkan koneksi database
$conn = getDBConnection();

// Query untuk mengambil jumlah event
$query_events = "SELECT COUNT(*) AS total_events FROM events";
$result_events = mysqli_query($conn, $query_events);

if ($result_events) {
    $row_events = mysqli_fetch_assoc($result_events);
    $total_events = $row_events['total_events'];
} else {
    $total_events = 0;
}

// Query untuk mengambil total registrasi
$query_registrants = "SELECT COUNT(*) AS total_registrants FROM registrations";
$result_registrants = mysqli_query($conn, $query_registrants);

if ($result_registrants) {
    $row_registrants = mysqli_fetch_assoc($result_registrants);
    $total_registrants = $row_registrants['total_registrants'];
} else {
    $total_registrants = 0;
}

// Query untuk mengambil event yang akan datang
$query_upcoming_events = "SELECT e.id, e.name, e.date, COUNT(r.id) AS registrants 
                          FROM events e 
                          LEFT JOIN registrations r ON e.id = r.event_id 
                          WHERE e.date >= CURDATE() 
                          GROUP BY e.id 
                          ORDER BY e.date ASC";
$result_upcoming_events = mysqli_query($conn, $query_upcoming_events);

$events = [];
if ($result_upcoming_events) {
    while ($row_event = mysqli_fetch_assoc($result_upcoming_events)) {
        $events[] = $row_event;
    }
} else {
    $events = null;
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

    <div class="container mx-auto px-4 py-8"> 
        <h1 class="text-3xl font-bold mb-8">Admin Dashboard</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-8">
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-semibold mb-2">Total Events</h2>
                <p class="text-4xl font-bold"><?php echo $total_events; ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow">
                <h2 class="text-xl font-semibold mb-2">Total Registrants</h2>
                <p class="text-4xl font-bold"><?php echo $total_registrants; ?></p>
            </div>
        </div>
        
        <div class="bg-white p-6 rounded-lg shadow">
            <h2 class="text-2xl font-semibold mb-4">Upcoming Events</h2>
            <table class="w-full table-auto">
                <thead>
                    <tr class="bg-gray-200">
                        <th class="text-left px-4 py-2">Event Name</th>
                        <th class="text-left px-4 py-2">Date</th>
                        <th class="text-left px-4 py-2">Registrants</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($events)): ?>
                        <?php foreach ($events as $event): ?>
                        <tr class="border-b">
                            <td class="px-4 py-2"><?php echo htmlspecialchars($event['name']); ?></td>
                            <td class="px-4 py-2"><?php echo htmlspecialchars($event['date']); ?></td>
                            <td class="px-4 py-2"><?php echo $event['registrants']; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" class="px-4 py-2 text-center">No upcoming events.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
        
        <div class="mt-8">
            <a href="event_management.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">Manage Events</a>
            <a href="view_registrations.php" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600 ml-4">View Registrations</a>
        </div>
    </div>

</body>
</html>
