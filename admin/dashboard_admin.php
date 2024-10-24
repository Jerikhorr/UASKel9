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
    
    <div class="container mx-auto px-4 py-4 sm:py-6">
        <!-- Welcome Section - More compact on mobile -->
        <div class="mb-4 sm:mb-6">
            <h1 class="text-xl sm:text-3xl font-bold text-gray-800">Welcome, Admin!</h1>
        </div>

        <!-- Stats Cards - Stack on mobile -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-3 mb-4">
            <!-- Total Events Card -->
            <div class="bg-white p-3 sm:p-4 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center space-x-3">
                    <div class="p-2 rounded-full bg-blue-100">
                        <i class="fas fa-calendar text-blue-600 text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-xs sm:text-sm font-medium text-gray-600">Total Events</h2>
                        <p class="text-lg sm:text-xl font-bold text-blue-600"><?php echo count($events); ?></p>
                    </div>
                </div>
            </div>

            <!-- Total Registrants Card -->
            <div class="bg-white p-3 sm:p-4 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center space-x-3">
                    <div class="p-2 rounded-full bg-green-100">
                        <i class="fas fa-users text-green-600 text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-xs sm:text-sm font-medium text-gray-600">Total Registrants</h2>
                        <p class="text-lg sm:text-xl font-bold text-green-600"><?php echo $total_registrants; ?></p>
                    </div>
                </div>
            </div>

            <!-- Active Events Card -->
            <div class="bg-white p-3 sm:p-4 rounded-lg shadow-sm hover:shadow-md transition-shadow">
                <div class="flex items-center space-x-3">
                    <div class="p-2 rounded-full bg-purple-100">
                        <i class="fas fa-check-circle text-purple-600 text-lg"></i>
                    </div>
                    <div>
                        <h2 class="text-xs sm:text-sm font-medium text-gray-600">Active Events</h2>
                        <p class="text-lg sm:text-xl font-bold text-purple-600">
                            <?php 
                            $active_events = array_reduce($events, function($carry, $event) {
                                return $carry + ($event['status'] === 'active' ? 1 : 0);
                            }, 0);
                            echo $active_events;
                            ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Events Section -->
        <div class="bg-white rounded-lg shadow-sm overflow-hidden">
            <div class="p-4 border-b border-gray-100">
                <h2 class="text-lg font-semibold text-gray-800">Available Events</h2>
            </div>

            <!-- Mobile View (Card Layout) -->
            <div class="block lg:hidden">
                <?php foreach ($events as $event): ?>
                <div class="p-4 border-b border-gray-100">
                    <!-- Event Header -->
                    <div class="flex items-center mb-3">
                        <?php if ($event['image']): ?>
                            <img class="h-10 w-10 rounded-full object-cover mr-3" src="<?php echo htmlspecialchars($event['image']); ?>" alt="">
                        <?php else: ?>
                            <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                                <i class="fas fa-calendar-alt text-gray-500"></i>
                            </div>
                        <?php endif; ?>
                        <div class="flex-1">
                            <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($event['name']); ?></div>
                            <div class="text-xs text-gray-500">ID: #<?php echo $event['id']; ?></div>
                        </div>
                        <span class="<?php echo getStatusBadgeClass($event['status']); ?>">
                            <?php echo ucfirst(htmlspecialchars($event['status'])); ?>
                        </span>
                    </div>
                    
                    <!-- Event Details -->
                    <div class="space-y-2 text-sm">
                        <div class="flex items-center text-gray-600">
                            <i class="far fa-calendar w-5"></i>
                            <span><?php echo date('M d, Y', strtotime($event['date'])); ?></span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="far fa-clock w-5"></i>
                            <span><?php echo date('h:i A', strtotime($event['time'])); ?></span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-map-marker-alt w-5"></i>
                            <span><?php echo htmlspecialchars($event['location']); ?></span>
                        </div>
                        <div class="flex items-center text-gray-600">
                            <i class="fas fa-users w-5"></i>
                            <span><?php echo $event['registrant_count']; ?> / <?php echo $event['max_participants']; ?> participants</span>
                        </div>
                        
                        <!-- Progress bar -->
                        <div class="mt-2">
                            <div class="w-full h-2 bg-gray-200 rounded-full">
                                <div class="h-2 bg-green-500 rounded-full" 
                                     style="width: <?php echo ($event['registrant_count'] / $event['max_participants']) * 100; ?>%">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- Desktop View (Table Layout) -->
            <div class="hidden lg:block overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Event</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Date & Time</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Location</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Status</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Registrants</th>
                            <th class="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase">Available</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($events as $event): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-4 py-4">
                                <div class="flex items-center">
                                    <?php if ($event['image']): ?>
                                        <img class="h-10 w-10 rounded-full object-cover mr-3" src="<?php echo htmlspecialchars($event['image']); ?>" alt="">
                                    <?php else: ?>
                                        <div class="h-10 w-10 rounded-full bg-gray-200 flex items-center justify-center mr-3">
                                            <i class="fas fa-calendar-alt text-gray-500"></i>
                                        </div>
                                    <?php endif; ?>
                                    <div>
                                        <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($event['name']); ?></div>
                                        <div class="text-xs text-gray-500">ID: #<?php echo $event['id']; ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm text-gray-900"><?php echo date('M d, Y', strtotime($event['date'])); ?></div>
                                <div class="text-xs text-gray-500"><?php echo date('h:i A', strtotime($event['time'])); ?></div>
                            </td>
                            <td class="px-4 py-4">
                                <div class="text-sm text-gray-900"><?php echo htmlspecialchars($event['location']); ?></div>
                            </td>
                            <td class="px-4 py-4">
                                <span class="<?php echo getStatusBadgeClass($event['status']); ?>">
                                    <?php echo ucfirst(htmlspecialchars($event['status'])); ?>
                                </span>
                            </td>
                            <td class="px-4 py-4">
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
                            <td class="px-4 py-4 text-sm text-gray-700">
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