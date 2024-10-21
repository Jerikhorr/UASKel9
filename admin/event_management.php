<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

// Ensure the user is logged in and is an admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../user/login.php");
    exit();
}

$conn = getDBConnection();

// Handle form submission for creating or editing an event
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : null;
    $name = mysqli_real_escape_string($conn, $_POST['name']);
    $date = mysqli_real_escape_string($conn, $_POST['date']);
    $time = mysqli_real_escape_string($conn, $_POST['time']);
    $location = mysqli_real_escape_string($conn, $_POST['location']);
    $description = mysqli_real_escape_string($conn, $_POST['description']);
    $max_participants = intval($_POST['max_participants']);
    $status = mysqli_real_escape_string($conn, $_POST['status']);

    // Handle event image upload
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] === UPLOAD_ERR_OK) {
        $image_tmp = $_FILES['event_image']['tmp_name'];
        $image_name = $_FILES['event_image']['name'];
        $image_ext = pathinfo($image_name, PATHINFO_EXTENSION);
        $image_path = '../uploads/' . uniqid() . '.' . $image_ext;

        if (move_uploaded_file($image_tmp, $image_path)) {
            $event_image = $image_path;
        } else {
            $error_message = "Error uploading image.";
        }
    }

    // Handle banner upload
    if (isset($_FILES['event_banner']) && $_FILES['event_banner']['error'] === UPLOAD_ERR_OK) {
        $banner_tmp = $_FILES['event_banner']['tmp_name'];
        $banner_name = $_FILES['event_banner']['name'];
        $banner_ext = pathinfo($banner_name, PATHINFO_EXTENSION);
        $banner_path = '../uploads/' . uniqid() . '_banner.' . $banner_ext;

        if (move_uploaded_file($banner_tmp, $banner_path)) {
            $event_banner = $banner_path;
        } else {
            $error_message = "Error uploading banner.";
        }
    }
    $upload_dir = '../uploads/';
if (!is_dir($upload_dir)) {
    mkdir($upload_dir, 0777, true); // Buat folder uploads jika belum ada
}


    if ($event_id) {
        // Update existing event
        $query = "UPDATE events SET name = ?, date = ?, time = ?, location = ?, description = ?, max_participants = ?, status = ?, image = ?, banner = ? WHERE id = ?";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssssiissi", $name, $date, $time, $location, $description, $max_participants, $status, $event_image, $event_banner, $event_id);
    } else {
        // Create new event
        $query = "INSERT INTO events (name, date, time, location, description, max_participants, status, image, banner) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        $stmt = mysqli_prepare($conn, $query);
        mysqli_stmt_bind_param($stmt, "sssssiiss", $name, $date, $time, $location, $description, $max_participants, $status, $event_image, $event_banner);
    }

    if (mysqli_stmt_execute($stmt)) {
        $success_message = $event_id ? "Event updated successfully." : "Event created successfully.";
    } else {
        $error_message = "Error: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}

// Delete event
if (isset($_GET['delete'])) {
    $event_id = intval($_GET['delete']);
    $query = "DELETE FROM events WHERE id = ?";
    $stmt = mysqli_prepare($conn, $query);
    mysqli_stmt_bind_param($stmt, "i", $event_id);

    if (mysqli_stmt_execute($stmt)) {
        $success_message = "Event deleted successfully.";
    } else {
        $error_message = "Error deleting event: " . mysqli_error($conn);
    }

    mysqli_stmt_close($stmt);
}

// Fetch all events
$query = "SELECT * FROM events ORDER BY date DESC";
$result = mysqli_query($conn, $query);

include('../includes/header.php');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50 dark:bg-gray-900 transition duration-300 ease-in-out">
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-4xl font-extrabold mb-8 text-gray-800 dark:text-white">Event Management</h1>

        <!-- Feedback Notification -->
        <?php if (isset($success_message)): ?>
            <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded-lg" role="alert">
                <p><?php echo $success_message; ?></p>
            </div>
        <?php elseif (isset($error_message)): ?>
            <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4 rounded-lg" role="alert">
                <p><?php echo $error_message; ?></p>
            </div>
        <?php endif; ?>

        <!-- Form for Creating or Editing Event -->
        <form action="event_management.php" method="post" enctype="multipart/form-data" class="bg-white dark:bg-gray-800 shadow-md rounded-lg px-8 pt-6 pb-8 mb-8">
            <h2 class="text-2xl font-semibold mb-4 text-gray-800 dark:text-white">Create or Edit Event</h2>

            <!-- Event Name -->
            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-200 font-medium mb-2" for="name">Event Name</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 focus:outline-none focus:shadow-outline transition duration-200" id="name" type="text" name="name" required>
            </div>

            <!-- Event Date -->
            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-200 font-medium mb-2" for="date">Date</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 focus:outline-none focus:shadow-outline transition duration-200" id="date" type="date" name="date" required>
            </div>

            <!-- Event Time -->
            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-200 font-medium mb-2" for="time">Time</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 focus:outline-none focus:shadow-outline transition duration-200" id="time" type="time" name="time" required>
            </div>

            <!-- Event Location -->
            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-200 font-medium mb-2" for="location">Location</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 focus:outline-none focus:shadow-outline transition duration-200" id="location" type="text" name="location" required>
            </div>

            <!-- Event Description -->
            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-200 font-medium mb-2" for="description">Description</label>
                <textarea class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 focus:outline-none focus:shadow-outline transition duration-200" id="description" name="description" required></textarea>
            </div>

            <!-- Event Status -->
<div class="mb-4">
    <label class="block text-gray-700 dark:text-gray-200 font-medium mb-2" for="status">Status</label>
    <select class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 focus:outline-none focus:shadow-outline transition duration-200" id="status" name="status" required>
        <option value="active">Active</option>
        <option value="upcoming">Upcoming</option>
        <option value="completed">Completed</option>
    </select>
</div>


            <!-- Max Participants -->
            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-200 font-medium mb-2" for="max_participants">Max Participants</label>
                <input class="shadow appearance-none border rounded w-full py-2 px-3 text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 focus:outline-none focus:shadow-outline transition duration-200" id="max_participants" type="number" name="max_participants" required>
            </div>

            <!-- Upload Event Image -->
            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-200 font-medium mb-2" for="event_image">Upload Event Image</label>
                <input class="file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition duration-200" type="file" name="event_image" accept="image/*" required>
            </div>

            <!-- Upload Event Banner -->
            <div class="mb-4">
                <label class="block text-gray-700 dark:text-gray-200 font-medium mb-2" for="event_banner">Upload Event Banner</label>
                <input class="file:py-2 file:px-4 file:rounded file:border-0 file:text-sm file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 transition duration-200" type="file" name="event_banner" accept="image/*">
            </div>

            <!-- Submit Button -->
            <div class="flex items-center justify-between">
                <button class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded transition duration-200" type="submit">
                    Save Event
                </button>
            </div>
        </form>

        <!-- Existing Events Table -->
        <h2 class="text-2xl font-bold mb-4 text-gray-800 dark:text-white">Existing Events</h2>
        <table class="w-full bg-white dark:bg-gray-800 shadow-md rounded-lg mb-4 overflow-hidden">
            <thead>
                <tr class="bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-300">
                    <th class="text-left py-3 px-4">Event Name</th>
                    <th class="text-left py-3 px-4">Date</th>
                    <th class="text-left py-3 px-4">Location</th>
                    <th class="text-left py-3 px-4">Status</th>
                    <th class="text-left py-3 px-4">Actions</th>
                </tr>
            </thead>
            <tbody>
                <!-- Example of dynamic rows -->
                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-200">
                    <td class="py-3 px-4">Tech Conference</td>
                    <td class="py-3 px-4">2024-10-21</td>
                    <td class="py-3 px-4">New York</td>
                    <td class="py-3 px-4">Active</td>
                    <td class="py-3 px-4 flex space-x-2">
                        <a href="#" class="text-blue-500 hover:text-blue-700 transition duration-200">Edit</a>
                        <a href="#" class="text-red-500 hover:text-red-700 transition duration-200" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
                <tr class="hover:bg-gray-100 dark:hover:bg-gray-700 transition duration-200">
                    <td class="py-3 px-4">Art Exhibition</td>
                    <td class="py-3 px-4">2024-11-10</td>
                    <td class="py-3 px-4">Paris</td>
                    <td class="py-3 px-4">Upcoming</td>
                    <td class="py-3 px-4 flex space-x-2">
                        <a href="#" class="text-blue-500 hover:text-blue-700 transition duration-200">Edit</a>
                        <a href="#" class="text-red-500 hover:text-red-700 transition duration-200" onclick="return confirm('Are you sure?')">Delete</a>
                    </td>
                </tr>
                <!-- End of dynamic rows -->
            </tbody>
        </table>
    </div>
</body>
</html>
