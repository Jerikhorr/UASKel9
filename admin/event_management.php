<?php
// event_management.php
session_start();
require_once('../includes/config.php');
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

// Check if the user is logged in and has admin role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit();
}

$conn = getDBConnection();
$error_message = '';
$success_message = '';

// Valid statuses
$valid_statuses = ['active', 'upcoming', 'completed', 'canceled'];

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

    // Validate status
    if (!in_array($status, $valid_statuses)) {
        $error_message = "Invalid status provided.";
        error_log($error_message);
    }

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

            // Log status before execution
            error_log("Status before update: '" . $status . "'");

            // Execute statement
            if (!mysqli_stmt_execute($stmt)) {
                $error_message = "Error updating event: " . mysqli_error($conn);
                error_log($error_message); // Log error
            }

            // Update images only if new ones were uploaded
            if ($image_path) {
                $query_image = "UPDATE events SET image = ? WHERE id = ?";
                $stmt_image = mysqli_prepare($conn, $query_image);
                mysqli_stmt_bind_param($stmt_image, "si", $image_path, $event_id);
                if (!mysqli_stmt_execute($stmt_image)) {
                    $error_message = "Error updating image: " . mysqli_error($conn);
                    error_log($error_message); // Log error
                }
            }

            if ($banner_path) {
                $query_banner = "UPDATE events SET banner = ? WHERE id = ?";
                $stmt_banner = mysqli_prepare($conn, $query_banner);
                mysqli_stmt_bind_param($stmt_banner, "si", $banner_path, $event_id);
                if (!mysqli_stmt_execute($stmt_banner)) {
                    $error_message = "Error updating banner: " . mysqli_error($conn);
                    error_log($error_message); // Log error
                }
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

            // Log status before execution
            error_log("Status before insert: '" . $status . "'");

            // Execute statement for creating new event
            if (!mysqli_stmt_execute($stmt)) {
                $error_message = "Error creating event: " . mysqli_error($conn);
                error_log($error_message); // Log error
            }
        }
    }
    
    // After executing the queries, you can check for errors
    if (!empty($error_message)) {
        echo "An error occurred: " . $error_message;
    } else {
        $success_message = "Event updated/created successfully.";
    }
}

// Handle event deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $event_id = intval($_GET['delete']);
    $result = deleteEvent($conn, $event_id);
    if ($result['success']) {
        header("Location: event_management.php?success=" . urlencode($result['message']));
        exit();
    } else {
        $error_message = $result['message'];
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

                <!-- Max Participants -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="max_participants">
                        Max Participants
                    </label>
                    <input type="number" id="max_participants" name="max_participants" required
                           class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                           value="<?php echo $edit_event ? $edit_event['max_participants'] : ''; ?>">
                </div>

                <!-- Status -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="status">
                        Status
                    </label>
                    <select id="status" name="status" required
                            class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="upcoming" <?php echo ($edit_event && $edit_event['status'] === 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                        <option value="active" <?php echo ($edit_event && $edit_event['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                        <option value="completed" <?php echo ($edit_event && $edit_event['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                        <option value="canceled" <?php echo ($edit_event && $edit_event['status'] === 'canceled') ? 'selected' : ''; ?>>Canceled</option>
                    </select>
                </div>
            </div>

            <!-- Description -->
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2" for="description">
                    Description
                </label>
                <textarea id="description" name="description" rows="4" required
                          class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"><?php echo $edit_event ? htmlspecialchars($edit_event['description']) : ''; ?></textarea>
            </div>

            <!-- Image Upload -->
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2" for="event_image">
                    Event Image
                </label>
                <input type="file" id="event_image" name="event_image"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <!-- Banner Upload -->
            <div class="mt-4">
                <label class="block text-sm font-medium text-gray-700 mb-2" for="event_banner">
                    Event Banner
                </label>
                <input type="file" id="event_banner" name="event_banner"
                       class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500">
            </div>

            <div class="mt-6">
                <button type="submit"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">
                    <?php echo $edit_event ? 'Update Event' : 'Create Event'; ?>
                </button>
            </div>
        </form>

        <!-- Event List -->
        <h2 class="text-2xl font-semibold mb-6">Event List</h2>
        <table class="min-w-full bg-white border border-gray-300">
            <thead>
                <tr>
                    <th class="py-2 px-4 border-b text-left">Banner</th> 
                    <th class="py-2 px-4 border-b text-left">Name</th>
                    <th class="py-2 px-4 border-b text-left">Date</th>
                    <th class="py-2 px-4 border-b text-left">Time</th>
                    <th class="py-2 px-4 border-b text-left">Location</th>
                    <th class="py-2 px-4 border-b text-left">Status</th>
                    <th class="py-2 px-4 border-b text-left">Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php while ($event = mysqli_fetch_assoc($result)): ?>
                    <tr>
                         <td class="py-2 px-4 border-b">
                         <?php if (!empty($event['event_banner'])): ?>
                        <img src="../uploads/banners/<?php echo htmlspecialchars($event['event_banner']); ?>" alt="Banner" class="h-20 w-32 object-cover"> <!-- Tampilkan Banner -->
                            <?php else: ?>
                                 No Banner
                            <?php endif; ?>
                         </td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($event['name']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($event['date']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($event['time']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($event['location']); ?></td>
                        <td class="py-2 px-4 border-b"><?php echo htmlspecialchars($event['status']); ?></td>
                        <td class="py-2 px-4 border-b">
                            <a href="event_management.php?edit=<?php echo $event['id']; ?>" class="text-blue-500 hover:underline">Edit</a>
                            <a href="event_management.php?delete=<?php echo $event['id']; ?>" class="text-red-500 hover:underline ml-4" 
                               onclick="return confirm('Are you sure you want to delete this event?');">Delete</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>
</body>
</html>
