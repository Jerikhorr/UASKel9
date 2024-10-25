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

// Initialize variables
$total_registrants = 0;
$recent_count = 0;
$registrants = [];
$selected_event = null;

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
if (isset($_GET['event_id'])) {
    $selected_event = intval($_GET['event_id']);
    
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
    mysqli_stmt_bind_param($stmt, "i", $selected_event);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    
    while ($row = mysqli_fetch_assoc($result)) {
        $registrants[] = $row;
    }

    // Calculate statistics after we have the registrants
    if (!empty($registrants)) {
        $total_registrants = count($registrants);
        $recent_registrations = array_filter($registrants, function($reg) {
            return strtotime($reg['registration_date']) > strtotime('-24 hours');
        });
        $recent_count = count($recent_registrations);
    }
}

// Close the database connection
mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Registrations</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        @media (max-width: 640px) {
            .table-scroll {
                display: block;
                -webkit-overflow-scrolling: touch;
            }
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include 'navbar_admin.php'; ?>
    
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-6 sm:py-8">
        <!-- Header Section -->
        <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-6 sm:mb-8">
            <div class="mb-4 sm:mb-0">
                <h1 class="text-2xl sm:text-3xl font-bold text-gray-800">Event Registrations</h1>
                <p class="text-sm sm:text-base text-gray-600 mt-1 sm:mt-2">Manage and view all event registrations</p>
            </div>
        </div>

        <!-- Event Selection Card -->
        <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 mb-6 sm:mb-8 border border-gray-100">
            <h2 class="text-lg sm:text-xl font-semibold mb-4 text-gray-800 flex items-center">
                <i class="fas fa-calendar-alt mr-2 text-blue-500"></i> Select Event
            </h2>
            <form action="view_registrations.php" method="get" class="flex flex-col sm:flex-row gap-3 sm:gap-4">
                <select name="event_id" class="flex-1 border rounded-lg px-3 sm:px-4 py-2 text-sm sm:text-base focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition duration-300">
                    <option value="">Select an event...</option>
                    <?php foreach ($events as $event): ?>
                        <option value="<?php echo $event['id']; ?>" 
                                <?php echo $selected_event == $event['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($event['name']); ?> - 
                            <?php echo date('M d, Y', strtotime($event['date'])); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="bg-blue-500 text-white px-4 sm:px-6 py-2 rounded-lg hover:bg-blue-600 transition duration-300 flex items-center justify-center">
                    <i class="fas fa-search mr-2"></i> View Registrants
                </button>
            </form>
        </div>

        <?php if ($selected_event && count($registrants) > 0): ?>
            <!-- Statistics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6 mb-6 sm:mb-8">
                <!-- Total Registrants Card -->
                <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-100">
                    <div class="flex items-center">
                        <div class="p-2 sm:p-3 rounded-full bg-blue-100 text-blue-500">
                            <i class="fas fa-users text-lg sm:text-2xl"></i>
                        </div>
                        <div class="ml-3 sm:ml-4">
                            <p class="text-xs sm:text-sm text-gray-500 uppercase">Total Registrants</p>
                            <p class="text-xl sm:text-2xl font-semibold text-gray-800"><?php echo $total_registrants; ?></p>
                        </div>
                    </div>
                </div>
                
                <!-- Last 24 Hours Card -->
                <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-100">
                    <div class="flex items-center">
                        <div class="p-2 sm:p-3 rounded-full bg-green-100 text-green-500">
                            <i class="fas fa-clock text-lg sm:text-2xl"></i>
                        </div>
                        <div class="ml-3 sm:ml-4">
                            <p class="text-xs sm:text-sm text-gray-500 uppercase">Last 24 Hours</p>
                            <p class="text-xl sm:text-2xl font-semibold text-gray-800"><?php echo $recent_count; ?></p>
                        </div>
                    </div>
                </div>

                <!-- Event Date Card -->
                <div class="bg-white rounded-xl shadow-md p-4 sm:p-6 border border-gray-100">
                    <div class="flex items-center">
                        <div class="p-2 sm:p-3 rounded-full bg-purple-100 text-purple-500">
                            <i class="fas fa-calendar-check text-lg sm:text-2xl"></i>
                        </div>
                        <div class="ml-3 sm:ml-4">
                            <p class="text-xs sm:text-sm text-gray-500 uppercase">Event Date</p>
                            <p class="text-xl sm:text-2xl font-semibold text-gray-800">
                                <?php echo date('M d, Y', strtotime($registrants[0]['event_date'])); ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Registrants List -->
            <div class="bg-white rounded-xl shadow-md overflow-hidden border border-gray-100">
                <!-- List Header -->
                <div class="p-4 sm:p-6 bg-white border-b flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
                    <div>
                        <h2 class="text-lg sm:text-xl font-semibold text-gray-800">
                            <i class="fas fa-list-alt mr-2 text-blue-500"></i>
                            Registrants for <?php echo htmlspecialchars($registrants[0]['event_name']); ?>
                        </h2>
                        <p class="text-xs sm:text-sm text-gray-500 mt-1">
                            Showing all registered participants
                        </p>
                    </div>
                    <a href="view_registrations.php?export=csv&event_id=<?php echo $selected_event; ?>" 
                       class="w-full sm:w-auto bg-green-500 text-white px-4 py-2 rounded-lg hover:bg-green-600 transition duration-300 flex items-center justify-center">
                        <i class="fas fa-download mr-2"></i> Export to CSV
                    </a>
                </div>
                
                <!-- Responsive Table -->
                <div class="overflow-x-auto table-scroll">
                    <table class="w-full">
                        <thead>
                            <tr class="bg-gray-50">
                                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">ID</th>
                                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Name</th>
                                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider hidden sm:table-cell">Email</th>
                                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Registration</th>
                                <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white divide-y divide-gray-200">
                            <?php foreach ($registrants as $registrant): 
                                $isRecent = strtotime($registrant['registration_date']) > strtotime('-24 hours');
                            ?>
                            <tr class="hover:bg-gray-50 transition duration-150">
                                <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">
                                    #<?php echo $registrant['id']; ?>
                                </td>
                                <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                                    <div class="flex items-center">
                                        <div class="flex-shrink-0 h-6 w-6 sm:h-8 sm:w-8 bg-gray-100 rounded-full flex items-center justify-center">
                                            <i class="fas fa-user text-xs sm:text-sm text-gray-500"></i>
                                        </div>
                                        <div class="ml-3 sm:ml-4">
                                            <div class="text-xs sm:text-sm font-medium text-gray-900">
                                                <?php echo htmlspecialchars($registrant['name']); ?>
                                            </div>
                                            <!-- Mobile-only email display -->
                                            <div class="text-xs text-gray-500 sm:hidden">
                                                <?php echo htmlspecialchars($registrant['email']); ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap hidden sm:table-cell">
                                    <div class="text-xs sm:text-sm text-gray-900">
                                        <i class="fas fa-envelope mr-2 text-gray-400"></i>
                                        <?php echo htmlspecialchars($registrant['email']); ?>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap text-xs sm:text-sm text-gray-900">
                                    <div class="flex items-center">
                                        <i class="fas fa-clock mr-2 <?php echo $isRecent ? 'text-green-500' : 'text-gray-400'; ?>"></i>
                                        <?php echo date('M d, Y', strtotime($registrant['registration_date'])); ?>
                                        <span class="hidden sm:inline"><?php echo date('H:i', strtotime($registrant['registration_date'])); ?></span>
                                    </div>
                                </td>
                                <td class="px-4 sm:px-6 py-3 sm:py-4 whitespace-nowrap">
                                    <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                        <?php echo $isRecent ? 'bg-green-100 text-green-800' : 'bg-blue-100 text-blue-800'; ?>">
                                        <?php echo $isRecent ? 'New' : 'Registered'; ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php elseif ($selected_event): ?>
            <!-- No Registrants Message -->
            <div class="bg-white rounded-xl shadow-md p-6 sm:p-8 text-center border border-gray-100">
                <div class="text-gray-400 mb-4">
                    <i class="fas fa-users-slash text-4xl sm:text-6xl"></i>
                </div>
                <h3 class="text-lg sm:text-xl font-medium text-gray-800 mb-2">No Registrants Found</h3>
                <p class="text-sm sm:text-base text-gray-500">There are currently no registrants for this event.</p>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>