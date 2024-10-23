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
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <!-- Ubah h1 menjadi tautan dengan warna hitam dan tanpa efek hover underline -->
            <h1 class="text-3xl font-bold">
                <a href="../user/login.php" class="text-black hover:no-underline">Admin Dashboard</a>
            </h1>
            <div class="space-x-4">
                <a href="event_management.php" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    Event Management
                </a>
                <a href="view_registrations.php" class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    View Registrations
                </a>
                <a href="../user/profile.php" class="bg-purple-500 text-white px-4 py-2 rounded hover:bg-purple-600">
                    Profile
                </a>
                <a href="user_management.php" class="bg-red-500 text-white px-4 py-2 rounded hover:bg-red-600">
                    User Management
                </a>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Total Events</h2>
                <p class="text-4xl font-bold text-blue-600"><?php echo count($events); ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Total Registrants</h2>
                <p class="text-4xl font-bold text-green-600"><?php echo $total_registrants; ?></p>
            </div>
            <div class="bg-white p-6 rounded-lg shadow-lg">
                <h2 class="text-xl font-semibold text-gray-800 mb-2">Active Events</h2>
                <p class="text-4xl font-bold text-purple-600">
                    <?php echo array_reduce($events, function($carry, $event) {
                        return $carry + ($event['status'] === 'active' ? 1 : 0);
                    }, 0); ?>
                </p>
            </div>
        </div>

        <!-- Events Table -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <h2 class="text-2xl font-semibold p-6 bg-gray-50 border-b">Available Events</h2>
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
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <?php if ($event['image']): ?>
                                    <img class="h-10 w-10 rounded-full object-cover mr-3" src="<?php echo htmlspecialchars($event['image']); ?>" alt="">
                                    <?php endif; ?>
                                    <div class="text-sm font-medium text-gray-900">
                                        <?php echo htmlspecialchars($event['name']); ?>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <?php echo date('M d, Y', strtotime($event['date'])); ?><br>
                                <?php echo date('h:i A', strtotime($event['time'])); ?>
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-700">
                                <?php echo htmlspecialchars($event['location']); ?>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                <?php echo !empty($event['status']) ? getStatusBadgeClass($event['status']) : 'bg-gray-100 text-gray-800'; 
                            ?>">
                            <?php echo !empty($event['status']) ? ucfirst(htmlspecialchars($event['status'])) : 'Unknown'; ?>
                                </span>
                            </td>

                            <td class="px-6 py-4 text-sm text-gray-700">
                                <?php echo $event['registrant_count']; ?> / <?php echo $event['max_participants']; ?>
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
