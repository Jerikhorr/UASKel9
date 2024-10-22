<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

// Check if user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit();
}

$conn = getDBConnection();

// Handle CSV Export
if (isset($_GET['export']) && isset($_GET['event_id'])) {
    $event_id = intval($_GET['event_id']);
    
    // Get event details
    $event_query = "SELECT name FROM events WHERE id = ?";
    $stmt = mysqli_prepare($conn, $event_query);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $event_result = mysqli_stmt_get_result($stmt);
    $event = mysqli_fetch_assoc($event_result);
    
    // Get registrants
    $query = "SELECT 
        r.id,
        r.registration_date,
        u.name,
        u.email,
        e.name as event_name,
        e.date as event_date,
        e.location
    FROM registrations r
    JOIN users u ON r.user_id = u.id
    JOIN events e ON r.event_id = e.id
    WHERE r.event_id = ?
    ORDER BY r.registration_date DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="' . $event['name'] . '_registrants.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add headers to CSV
    fputcsv($output, array('Registration ID', 'Registration Date', 'Name', 'Email', 'Event', 'Event Date', 'Location'));
    
    // Add data to CSV
    while ($row = mysqli_fetch_assoc($result)) {
        fputcsv($output, array(
            $row['id'],
            $row['registration_date'],
            $row['name'],
            $row['email'],
            $row['event_name'],
            $row['event_date'],
            $row['location']
        ));
    }
    
    fclose($output);
    exit();
}

// Get list of events
$events_query = "SELECT id, name, date FROM events ORDER BY date DESC";
$events_result = mysqli_query($conn, $events_query);
$events = [];
while ($row = mysqli_fetch_assoc($events_result)) {
    $events[] = $row;
}

// Get registrants for specific event if selected
$registrants = [];
$selected_event = null;
if (isset($_GET['event_id'])) {
    $event_id = intval($_GET['event_id']);
    
    $query = "SELECT 
        r.id,
        r.registration_date,
        u.name,
        u.email,
        e.name as event_name,
        e.date as event_date
    FROM registrations r
    JOIN users u ON r.user_id = u.id
    JOIN events e ON r.event_id = e.id
    WHERE r.event_id = ?
    ORDER BY r.registration_date DESC";
    
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $registrants[] = $row;
    }
    
    $selected_event = $event_id;
}

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Registrations</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Event Registrations</h1>
            <a href="dashboard_admin.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                Back to Dashboard
            </a>
        </div>

        <!-- Event Selection -->
        <div class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-xl font-semibold mb-4">Select Event</h2>
            <form action="view_registrations.php" method="get" class="flex gap-4">
                <select name="event_id" class="flex-1 border rounded-lg px-4 py-2">
                    <option value="">Select an event...</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?php echo $event['id']; ?>" 
                                <?php echo $selected_event == $event['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($event['name']); ?> - 
                            <?php echo date('M d, Y', strtotime($event['date'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600">
                    View Registrants
                </button>
            </form>
        </div>

        <?php if ($selected_event && count($registrants) > 0): ?>
        <!-- Registrants List -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="p-6 bg-gray-50 border-b flex justify-between items-center">
                <h2 class="text-xl font-semibold">
                    Registrants for <?php echo htmlspecialchars($registrants[0]['event_name']); ?>
                </h2>
                <a href="view_registrations.php?export=csv&event_id=<?php echo $selected_event; ?>" 
                   class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    Export to CSV
                </a>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Email</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registration Date</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php foreach ($registrants as $registrant): ?>
                        <tr class="hover:bg-gray-50">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo $registrant['id']; ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($registrant['name']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo htmlspecialchars($registrant['email']); ?>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900">
                                <?php echo date('M d, Y H:i', strtotime($registrant['registration_date'])); ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php elseif ($selected_event): ?>
        <div class="bg-white rounded-lg shadow-lg p-6">
            <p class="text-gray-500">No registrants found for this event.</p>
        </div>
        <?php endif; ?>
    </div>
</body>
</html>