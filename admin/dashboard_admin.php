<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit();
}

$conn = getDBConnection();

$query_events = "SELECT 
    e.*,
    COALESCE(r.registrant_count, 0) as registrant_count,
    (e.max_participants - COALESCE(r.registrant_count, 0)) as available_slots
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
    e.date ASC";

$result_events = mysqli_query($conn, $query_events);

$events = [];
if ($result_events) {
    while ($row = mysqli_fetch_assoc($result_events)) {
        $events[] = $row;
    }
}

// Get total registrants
$query_total = "SELECT COUNT(*) as total FROM registrations";
$result_total = mysqli_query($conn, $query_total);
$total_registrants = mysqli_fetch_assoc($result_total)['total'];

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <?php include 'navbar_admin.php'; ?>
    
    <div class="container mx-auto px-4 py-8">
        <!-- Welcome Section -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-800 mb-2">Welcome, Admin!</h1>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100 mr-4">
                        <i class="fas fa-calendar text-blue-600 text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-600">Total Events</h2>
                        <p class="text-2xl font-bold text-blue-600"><?php echo count($events); ?></p>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="h-1 w-full bg-blue-200 rounded">
                        <div class="h-1 bg-blue-600 rounded" style="width: <?php echo min(count($events) * 10, 100); ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100 mr-4">
                        <i class="fas fa-users text-green-600 text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-600">Total Registrants</h2>
                        <p class="text-2xl font-bold text-green-600"><?php echo $total_registrants; ?></p>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="h-1 w-full bg-green-200 rounded">
                        <div class="h-1 bg-green-600 rounded" style="width: <?php echo min($total_registrants, 100); ?>%"></div>
                    </div>
                </div>
            </div>

            <div class="bg-white p-6 rounded-xl shadow-md hover:shadow-lg transition-shadow duration-300 border border-gray-100">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100 mr-4">
                        <i class="fas fa-check-circle text-purple-600 text-xl"></i>
                    </div>
                    <div>
                        <h2 class="text-sm font-semibold text-gray-600">Active Events</h2>
                        <p class="text-2xl font-bold text-purple-600">
                            <?php 
                            $active_events = array_reduce($events, function($carry, $event) {
                                return $carry + ($event['status'] === 'active' ? 1 : 0);
                            }, 0);
                            echo $active_events;
                            ?>
                        </p>
                    </div>
                </div>
                <div class="mt-4">
                    <div class="h-1 w-full bg-purple-200 rounded">
                        <div class="h-1 bg-purple-600 rounded" style="width: <?php echo min(($active_events / max(count($events), 1)) * 100, 100); ?>%"></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Events Table -->
        <div class="bg-white rounded-xl shadow-md overflow-hidden">
            <div class="p-6 border-b border-gray-100">
                <div class="flex justify-between items-center">
                    <h2 class="text-xl font-semibold text-gray-800">Available Events</h2>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registrants</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Available Slots</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($events as $event): ?>
                        <tr class="hover:bg-gray-50 transition-colors duration-200">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <?php if ($event['image']): ?>
                                    <img class="h-10 w-10 rounded-full object-cover mr-3" src="<?php echo htmlspecialchars($event['image']); ?>" alt="">
                                    <?php else: ?>
                                    <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                                        <i class="fas fa-calendar-alt text-gray-500"></i>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($event['name']); ?>
                                        </div>
                                        <div class="text-sm text-gray-500">
                                            ID: #<?php echo $event['id']; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <i class="far fa-calendar mr-2"></i>
                                    <?php echo date('M d, Y', strtotime($event['date'])); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <i class="far fa-clock mr-2"></i>
                                    <?php echo date('h:i A', strtotime($event['time'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <i class="fas fa-map-marker-alt mr-2 text-gray-400"></i>
                                    <?php echo htmlspecialchars($event['location']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full 
                                    <?php echo getStatusBadgeClass($event['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($event['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <span class="text-sm text-gray-900 mr-2">
                                        <?php echo $event['registrant_count']; ?> / <?php echo $event['max_participants']; ?>
                                    </span>
                                    <div class="w-24 h-2 bg-gray-200 rounded-full">
                                        <div class="h-2 bg-green-500 rounded-full" 
                                             style="width: <?php echo ($event['registrant_count'] / $event['max_participants']) * 100; ?>%">
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <?php echo $event['available_slots']; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>