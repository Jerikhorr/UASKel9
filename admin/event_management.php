<?php
// event_management.php
session_start();
require_once('../includes/config.php');
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit();
}

$conn = getDBConnection();
$error_message = '';
$success_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : null;
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $time = mysqli_real_escape_string($conn, $_POST['time']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $max_participants = intval($_POST['max_participants']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Handle image uploads
    $image_path = null;
    $banner_path = null;

    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
        $upload_result = handleFileUpload($_FILES['event_image'], '../uploads/images/');
        if ($upload_result['success']) {
            $image_path = $upload_result['path'];
        } else {
            $error_message = $upload_result['error'];
        }
    }

    if (isset($_FILES['event_banner']) && $_FILES['event_banner']['error'] === UPLOAD_ERR_OK) {
        $upload_result = handleFileUpload($_FILES['event_banner'], '../uploads/banners/');
        if ($upload_result['success']) {
            $banner_path = $upload_result['path'];
        } else {
            $error_message = $upload_result['error'];
        }
    }

    if (empty($error_message)) {
        if ($event_id) {
            // Update existing event
            $query = "UPDATE events SET 
                     name = ?, date = ?, time = ?, location = ?, 
                     description = ?, max_participants = ?, status = ?
                     WHERE id = ?";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssssiis", 
                $name, $date, $time, $location, 
                $description, $max_participants, $status, $event_id);
            
            // Update images only if new ones were uploaded
            if ($image_path) {
                $query_image = "UPDATE events SET image = ? WHERE id = ?";
                $stmt_image = mysqli_prepare($conn, $query_image);
                mysqli_stmt_bind_param($stmt_image, "si", $image_path, $event_id);
                mysqli_stmt_execute($stmt_image);
            }
            
            if ($banner_path) {
                $query_banner = "UPDATE events SET banner = ? WHERE id = ?";
                $stmt_banner = mysqli_prepare($conn, $query_banner);
                mysqli_stmt_bind_param($stmt_banner, "si", $banner_path, $event_id);
                mysqli_stmt_execute($stmt_banner);
            }
            
        } else {
            // Create new event
            $query = "INSERT INTO events (name, date, time, location, description, 
                     max_participants, status, image, banner) 
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, "sssssisss", 
                $name, $date, $time, $location, 
                $description, $max_participants, $status, $image_path, $banner_path);
        }

        if (mysqli_stmt_execute($stmt)) {
            $success_message = $event_id ? "Event updated successfully." : "Event created successfully.";
        } else {
            $error_message = "Error: " . mysqli_error($conn);
        }
    }
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $result = deleteEvent($conn, $_GET['delete']);
    if ($result['success']) {
        $success_message = $result['message'];
        header("Location: event_management.php?success=" . urlencode($result['message']));
        exit();
    } else {
        $error_message = $result['message'];
    }
}

if (isset($_GET['success'])) {
    $success_message = $_GET['success'];
}

if (isset($_GET['delete'])) {
    $event_id = validateEventId($_GET['delete']);
    
    if ($event_id === false) {
        $error_message = "Invalid event ID format";
    } else {
        // Verify event exists
        $check_query = "SELECT id FROM events WHERE id = ?";
        $stmt = mysqli_prepare($conn, $check_query);
        mysqli_stmt_bind_param($stmt, "i", $event_id);
        mysqli_stmt_execute($stmt);
        $check_result = mysqli_stmt_get_result($stmt);
        
        if (mysqli_num_rows($check_result) > 0) {
            $result = deleteEvent($conn, $event_id);
            if ($result['success']) {
                header("Location: event_management.php?success=" . urlencode($result['message']));
                exit();
            } else {
                $error_message = $result['message'];
            }
        } else {
            $error_message = "Event not found";
        }
    }
}

// Get event for editing
$edit_event = null;
if (isset($_GET['edit']) && is_numeric($_GET['edit'])) {
    $event_id = intval($_GET['edit']);
    $query = "SELECT * FROM events WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $event_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $edit_event = mysqli_fetch_assoc($result);
}

// Fetch all events
$query = "SELECT * FROM events ORDER BY date DESC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    
</head>
<body class="bg-gray-100">
    <div class="container mx-auto px-4 py-8">
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold">Event Management</h1>
            <a href="dashboard_admin.php" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                Back to Dashboard
            </a>
        </div>

        <?php if ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4">
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <?php if ($error_message): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <!-- Event Form -->
        <form action="event_management.php<?php echo $edit_event ? '?edit=' . $edit_event['id'] : ''; ?>" 
        method="POST" enctype="multipart/form-data" class="bg-white rounded-lg shadow-lg p-6 mb-8">
            <h2 class="text-2xl font-semibold mb-6">
                <?php echo $edit_event ? 'Edit Event' : 'Create New Event'; ?>
            </h2>

            <?php if ($edit_event): ?>
                <input type="hidden" name="event_id" value="<?php echo $edit_event['id']; ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Event Name -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="name">
                        Event Name
                    </label>
                    <input type="text" id="name" name="name" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="<?php echo $edit_event ? htmlspecialchars($edit_event['name']) : ''; ?>">
                </div>

                <!-- Date -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="date">
                        Date
                    </label>
                    <input type="date" id="date" name="date" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="<?php echo $edit_event ? $edit_event['date'] : ''; ?>">
                </div>

                <!-- Time -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="time">
                        Time
                    </label>
                    <input type="time" id="time" name="time" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="<?php echo $edit_event ? $edit_event['time'] : ''; ?>">
                </div>

                <!-- Location -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="location">
                        Location
                    </label>
                    <input type="text" id="location" name="location" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="<?php echo $edit_event ? htmlspecialchars($edit_event['location']) : ''; ?>">
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="status">
                        Status
                    </label>
                    <select id="status" name="status" required
        class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
    <option value="">Select Status</option>
    <option value="active" <?php echo ($edit_event && $edit_event['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
    <option value="upcoming" <?php echo ($edit_event && $edit_event['status'] === 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
    <option value="closed" <?php echo ($edit_event && $edit_event['status'] === 'closed') ? 'selected' : ''; ?>>Closed</option>
    <option value="canceled" <?php echo ($edit_event && $edit_event['status'] === 'canceled') ? 'selected' : ''; ?>>Canceled</option>
</select>
                </div>

                <!-- Max Participants -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="max_participants">
                        Maximum Participants
                    </label>
                    <input type="number" id="max_participants" name="max_participants" required min="1"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="<?php echo $edit_event ? $edit_event['max_participants'] : ''; ?>">
                </div>
            </div>

            <!-- Description -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2" for="description">
                    Description
                </label>
                <textarea id="description" name="description" rows="4" required
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo $edit_event ? htmlspecialchars($edit_event['description']) : ''; ?></textarea>
            </div>

            <!-- Image Uploads -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="event_image">
                        Event Image
                    </label>
                    <input type="file" id="event_image" name="event_image" accept="image/*"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           <?php echo $edit_event ? '' : 'required'; ?>>
                    <?php if ($edit_event && $edit_event['image']): ?>
                        <p class="text-sm text-gray-500 mt-1">Current image will be kept if no new image is uploaded</p>
                    <?php endif; ?>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="event_banner">
                        Event Banner
                    </label>
                    <input type="file" id="event_banner" name="event_banner" accept="image/*"
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <?php if ($edit_event && $edit_event['banner']): ?>
                        <p class="text-sm text-gray-500 mt-1">Current banner will be kept if no new banner is uploaded</p>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-6">
                <button type="submit" class="bg-blue-500 text-white px-6 py-2 rounded-md hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                    <?php echo $edit_event ? 'Update Event' : 'Create Event'; ?>
                </button>
            </div>
        </form>

        <!-- Events Table -->
        <div class="bg-white rounded-lg shadow-lg overflow-hidden">
            <h2 class="text-2xl font-semibold p-6">Existing Events</h2>
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead>
                        <tr class="bg-gray-50">
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Participants</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        <?php while ($event = mysqli_fetch_assoc($result)): ?>
                        <tr>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="flex items-center">
                                    <?php if ($event['image']): ?>
                                    <img class="h-10 w-10 rounded-full object-cover" 
                                         src="<?php echo htmlspecialchars($event['image']); ?>" 
                                         alt="<?php echo htmlspecialchars($event['name']); ?>">
                                    <?php endif; ?>
                                    <div class="ml-4">
                                        <div class="text-sm font-medium text-gray-900">
                                            <?php echo htmlspecialchars($event['name']); ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo date('M d, Y', strtotime($event['date'])); ?>
                                </div>
                                <div class="text-sm text-gray-500">
                                    <?php echo date('h:i A', strtotime($event['time'])); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <div class="text-sm text-gray-900">
                                    <?php echo htmlspecialchars($event['location']); ?>
                                </div>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap">
                                <span class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full
                                    <?php 
                                    echo match($event['status']) {
                                        'active' => 'bg-green-100 text-green-800',
                                        'upcoming' => 'bg-blue-100 text-blue-800',
                                        'closed' => 'bg-gray-100 text-gray-800',
                                        'canceled' => 'bg-red-100 text-red-800',
                                        default => 'bg-gray-100 text-gray-800'
                                    }; 
                                    ?>">
                                    <?php echo ucfirst(htmlspecialchars($event['status'])); ?>
                                </span>
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                <?php echo $event['max_participants']; ?> max
                            </td>
                            <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                <a href="?edit=<?php echo $event['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-900 mr-3">Edit</a>
                                   <a href="?delete=<?php echo (int)$event['id']; ?>" 
   onclick="return confirm('Are you sure you want to delete this event? This action cannot be undone.')"
   class="text-red-600 hover:text-red-900">Delete</a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>