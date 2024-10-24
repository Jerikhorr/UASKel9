<?php
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
                      description = ?, max_participants = ?, status = ?";
            $params = [$name, $date, $time, $location, 
                       $description, $max_participants, $status];
            $types = "sssssss";
    
            // Add image and banner to update if they exist
            if ($image_path) {
                $query .= ", image = ?";
                $params[] = $image_path;
                $types .= "s";
            }
            if ($banner_path) {
                $query .= ", banner = ?";
                $params[] = $banner_path;
                $types .= "s";
            }
    
            $query .= " WHERE id = ?";
            $params[] = $event_id;
            $types .= "i";
    
            $stmt = mysqli_prepare($conn, $query);
            mysqli_stmt_bind_param($stmt, $types, ...$params);
    
            if (!mysqli_stmt_execute($stmt)) {
                $error_message = "Error updating event: " . mysqli_error($conn);
                error_log($error_message);
            }
        } else {
            // Create new event
            $query = "INSERT INTO events (name, date, time, location, description, 
                      max_participants, status, image, banner) 
                      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
            $stmt = mysqli_prepare($conn, $query);
            
            // If no image/banner uploaded, use null
            $image_path = $image_path ?? null;
            $banner_path = $banner_path ?? null;
            
            mysqli_stmt_bind_param($stmt, "sssssisss", 
                $name, $date, $time, $location, 
                $description, $max_participants, $status, $image_path, $banner_path);
    
            if (!mysqli_stmt_execute($stmt)) {
                $error_message = "Error creating event: " . mysqli_error($conn);
                error_log($error_message);
            }
        }
    }

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
$query = "SELECT e.*, 
    COALESCE(r.registrant_count, 0) as registrant_count,
    (e.max_participants - COALESCE(r.registrant_count, 0)) as available_slots 
    FROM events e 
    LEFT JOIN (
        SELECT event_id, COUNT(*) as registrant_count
        FROM registrations 
        GROUP BY event_id
    ) r ON e.id = r.event_id 
    ORDER BY e.date DESC";
$result = mysqli_query($conn, $query);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Management</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100 min-h-screen">
    <?php include 'navbar_admin.php'; ?>

    <!-- Main Content -->
    <div class="container mx-auto px-4 py-8">
        <!-- Header -->
        <div class="flex justify-between items-center mb-8">
            <h1 class="text-3xl font-bold text-gray-800">
                <i class="fas fa-calendar-alt text-blue-600 mr-2"></i>Event Management
            </h1>
            
        </div>

        <!-- Success Message -->
        <?php if ($success_message): ?>
        <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-6 rounded-r flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <?php echo $success_message; ?>
        </div>
        <?php endif; ?>

        <!-- Error Message -->
        <?php if ($error_message): ?>
        <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-6 rounded-r flex items-center">
            <i class="fas fa-exclamation-circle mr-2"></i>
            <?php echo $error_message; ?>
        </div>
        <?php endif; ?>

        <!-- Event Form -->
        <form action="event_management.php<?php echo $edit_event ? '?edit=' . $edit_event['id'] : ''; ?>" 
              method="POST" enctype="multipart/form-data" 
              class="bg-white rounded-xl shadow-lg p-6 mb-8 transition-all duration-300"
              id="eventForm">
            <h2 class="text-2xl font-semibold mb-6 flex items-center text-gray-800">
                <i class="fas <?php echo $edit_event ? 'fa-edit' : 'fa-plus-circle'; ?> text-blue-600 mr-2"></i>
                <?php echo $edit_event ? 'Edit Event' : 'Create New Event'; ?>
            </h2>

            <?php if ($edit_event): ?>
                <input type="hidden" name="event_id" value="<?php echo $edit_event['id']; ?>">
            <?php endif; ?>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Event Name -->
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="name">
                        Event Name
                    </label>
                    <div class="relative">
                        <i class="fas fa-bookmark absolute left-3 top-3 text-gray-400"></i>
                        <input type="text" id="name" name="name" required
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               value="<?php echo $edit_event ? htmlspecialchars($edit_event['name']) : ''; ?>"
                               placeholder="Enter event name">
                    </div>
                </div>

                <!-- Date -->
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="date">
                        Date
                    </label>
                    <div class="relative">
                        <i class="fas fa-calendar absolute left-3 top-3 text-gray-400"></i>
                        <input type="date" id="date" name="date" required
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               value="<?php echo $edit_event ? $edit_event['date'] : ''; ?>">
                    </div>
                </div>

                <!-- Time -->
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="time">
                        Time
                    </label>
                    <div class="relative">
                        <i class="fas fa-clock absolute left-3 top-3 text-gray-400"></i>
                        <input type="time" id="time" name="time" required
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               value="<?php echo $edit_event ? $edit_event['time'] : ''; ?>">
                    </div>
                </div>

                <!-- Location -->
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="location">
                        Location
                    </label>
                    <div class="relative">
                        <i class="fas fa-map-marker-alt absolute left-3 top-3 text-gray-400"></i>
                        <input type="text" id="location" name="location" required
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               value="<?php echo $edit_event ? htmlspecialchars($edit_event['location']) : ''; ?>"
                               placeholder="Enter location">
                    </div>
                </div>

                <!-- Max Participants -->
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="max_participants">
                        Max Participants
                    </label>
                    <div class="relative">
                        <i class="fas fa-users absolute left-3 top-3 text-gray-400"></i>
                        <input type="number" id="max_participants" name="max_participants" required
                               class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                               value="<?php echo $edit_event ? $edit_event['max_participants'] : ''; ?>"
                               placeholder="Enter maximum participants">
                    </div>
                </div>

                <!-- Status -->
                <div class="relative">
                    <label class="block text-sm font-medium text-gray-700 mb-2" for="status">
                        Status
                    </label>
                    <div class="relative">
                        <i class="fas fa-tag absolute left-3 top-3 text-gray-400"></i>
                        <select id="status" name="status" required
                                class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none">
                            <option value="upcoming" <?php echo ($edit_event && $edit_event['status'] === 'upcoming') ? 'selected' : ''; ?>>Upcoming</option>
                            <option value="active" <?php echo ($edit_event && $edit_event['status'] === 'active') ? 'selected' : ''; ?>>Active</option>
                            <option value="completed" <?php echo ($edit_event && $edit_event['status'] === 'completed') ? 'selected' : ''; ?>>Completed</option>
                            <option value="canceled" <?php echo ($edit_event && $edit_event['status'] === 'canceled') ? 'selected' : ''; ?>>Canceled</option>
                        </select>
                        <i class="fas fa-chevron-down absolute right-3 top-3 text-gray-400 pointer-events-none"></i>
                    </div>
                </div>
            </div>

            <!-- Description -->
            <div class="mt-6">
                <label class="block text-sm font-medium text-gray-700 mb-2" for="description">
                    Description
                </label>
                <div class="relative">
                    <i class="fas fa-align-left absolute left-3 top-3 text-gray-400"></i>
                    <textarea id="description" name="description" rows="4" required
                              class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                              placeholder="Enter event description"><?php echo $edit_event ? htmlspecialchars($edit_event['description']) : ''; ?></textarea>
                </div>
            </div>

            <!-- File Upload Section -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mt-6">
                <!-- Event Image Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Event Image</label>
                    <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-blue-500 transition-colors">
                        <input type="file" id="event_image" name="event_image" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div class="text-center">
                            <i class="fas fa-cloud-upload-alt text-4xl text-gray-400 mb-2"></i>
                            <p class="text-gray-500 text-sm">Click or drag image here</p>
                        </div>
                        <?php if ($edit_event && !empty($edit_event['image'])): ?>
                            <img src="<?php echo htmlspecialchars($edit_event['image']); ?>" alt="Current Image" class="mt-2 h-20 mx-auto">
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Event Banner Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Event Banner</label>
                    <div class="relative border-2 border-dashed border-gray-300 rounded-lg p-6 hover:border-blue-500 transition-colors">
                        <input type="file" id="event_banner" name="event_banner" accept="image/*" class="absolute inset-0 w-full h-full opacity-0 cursor-pointer">
                        <div class="text-center">
                            <i class="fas fa-image text-4xl text-gray-400 mb-2"></i>
                            <p class="text-gray-500 text-sm">Click or drag banner here</p>
                        </div>
                        <?php if ($edit_event && !empty($edit_event['banner'])): ?>
                            <img src="<?php echo htmlspecialchars($edit_event['banner']); ?>" alt="Current Banner" class="mt-2 h-20 mx-auto">
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="mt-6 flex justify-end space-x-4">
                <button type="submit"
                        class="bg-blue-600 text-white px-6 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center">
                    <i class="fas <?php echo $edit_event ? 'fa-save' : 'fa-plus-circle'; ?> mr-2"></i>
                    <?php echo $edit_event ? 'Update Event' : 'Create Event'; ?>
                </button>
            </div>
        </form>

       <!-- Events List -->
<div class="bg-white rounded-xl shadow-lg overflow-hidden">
    <!-- Header -->
    <div class="px-4 sm:px-6 py-4 border-b border-gray-200">
        <h2 class="text-lg sm:text-xl font-semibold text-gray-800">Event List</h2>
    </div>
    
    <!-- Table/Card Container -->
    <div class="block">
        <!-- Desktop/Tablet View (Table) -->
        <div class="hidden md:block overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Banner</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Event Details</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Date & Time</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Location</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Status</th>
                        <th class="px-4 sm:px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">Actions</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    <?php while ($event = mysqli_fetch_assoc($result)): ?>
                        <tr class="hover:bg-gray-50 transition-colors">
                            <td class="px-4 sm:px-6 py-4 whitespace-nowrap">
                                <?php if (!empty($event['banner'])): ?>
                                    <img src="<?php echo htmlspecialchars($event['banner']); ?>" 
                                         alt="Event Banner" 
                                         class="h-16 w-24 sm:h-20 sm:w-32 object-cover rounded-lg">
                                <?php else: ?>
                                    <div class="h-16 w-24 sm:h-20 sm:w-32 bg-gray-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-image text-gray-400 text-xl sm:text-2xl"></i>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-4 sm:px-6 py-4">
                                <div class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($event['name']); ?></div>
                                <div class="text-xs sm:text-sm text-gray-500">
                                    <i class="fas fa-users mr-1"></i>
                                    <?php echo $event['registrant_count']; ?> / <?php echo $event['max_participants']; ?> participants
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    <?php echo date('M d, Y', strtotime($event['date'])); ?>
                                </div>
                                <div class="text-xs sm:text-sm text-gray-500">
                                    <i class="far fa-clock mr-1"></i>
                                    <?php echo date('h:i A', strtotime($event['time'])); ?>
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-4">
                                <div class="text-sm text-gray-900">
                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                    <?php echo htmlspecialchars($event['location']); ?>
                                </div>
                            </td>
                            <td class="px-4 sm:px-6 py-4">
                                <?php
                                $status_colors = [
                                    'upcoming' => 'bg-yellow-100 text-yellow-800',
                                    'active' => 'bg-green-100 text-green-800',
                                    'completed' => 'bg-blue-100 text-blue-800',
                                    'canceled' => 'bg-red-100 text-red-800'
                                ];
                                $status_class = $status_colors[$event['status']] ?? 'bg-gray-100 text-gray-800';
                                ?>
                                <span class="px-2 sm:px-3 py-1 inline-flex text-xs leading-5 font-semibold rounded-full <?php echo $status_class; ?>">
                                    <?php echo ucfirst(htmlspecialchars($event['status'])); ?>
                                </span>
                            </td>
                            <td class="px-4 sm:px-6 py-4 text-sm font-medium">
                                <div class="flex space-x-3">
                                    <a href="event_management.php?edit=<?php echo $event['id']; ?>" 
                                       class="text-blue-600 hover:text-blue-900 transition-colors">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button onclick="deleteEvent(<?php echo $event['id']; ?>)"
                                            class="text-red-600 hover:text-red-900 transition-colors">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>

        <!-- Mobile View (Cards) -->
        <div class="md:hidden">
            <?php 
            mysqli_data_seek($result, 0); // Reset result pointer
            while ($event = mysqli_fetch_assoc($result)): 
            ?>
                <div class="p-4 border-b border-gray-200">
                    <div class="flex items-start space-x-4">
                        <!-- Event Banner -->
                        <div class="flex-shrink-0">
                            <?php if (!empty($event['banner'])): ?>
                                <img src="<?php echo htmlspecialchars($event['banner']); ?>" 
                                     alt="Event Banner" 
                                     class="h-20 w-24 object-cover rounded-lg">
                            <?php else: ?>
                                <div class="h-20 w-24 bg-gray-100 rounded-lg flex items-center justify-center">
                                    <i class="fas fa-image text-gray-400 text-2xl"></i>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Event Details -->
                        <div class="flex-1 min-w-0">
                            <div class="flex justify-between items-start">
                                <h3 class="text-sm font-medium text-gray-900"><?php echo htmlspecialchars($event['name']); ?></h3>
                                <span class="px-2 py-1 text-xs font-semibold rounded-full <?php echo $status_class; ?>">
                                    <?php echo ucfirst(htmlspecialchars($event['status'])); ?>
                                </span>
                            </div>

                            <div class="mt-2 space-y-1">
                                <div class="text-xs text-gray-500">
                                    <i class="fas fa-users mr-1"></i>
                                    <?php echo $event['registrant_count']; ?> / <?php echo $event['max_participants']; ?> participants
                                </div>
                                <div class="text-xs text-gray-500">
                                    <i class="far fa-calendar-alt mr-1"></i>
                                    <?php echo date('M d, Y', strtotime($event['date'])); ?>
                                    <i class="far fa-clock ml-2 mr-1"></i>
                                    <?php echo date('h:i A', strtotime($event['time'])); ?>
                                </div>
                                <div class="text-xs text-gray-500">
                                    <i class="fas fa-map-marker-alt mr-1"></i>
                                    <?php echo htmlspecialchars($event['location']); ?>
                                </div>
                            </div>

                            <!-- Actions -->
                            <div class="mt-3 flex space-x-4">
                                <a href="event_management.php?edit=<?php echo $event['id']; ?>" 
                                   class="text-blue-600 hover:text-blue-900 transition-colors text-sm">
                                    <i class="fas fa-edit mr-1"></i> Edit
                                </a>
                                <button onclick="deleteEvent(<?php echo $event['id']; ?>)"
                                        class="text-red-600 hover:text-red-900 transition-colors text-sm">
                                    <i class="fas fa-trash-alt mr-1"></i> Delete
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    </div>
</div>

    <script>
        let currentEventId = null;

        function toggleForm() {
            const form = document.getElementById('eventForm');
            form.classList.toggle('hidden');
        }

        function deleteEvent(eventId) {
            currentEventId = eventId;
            const modal = document.getElementById('deleteModal');
            modal.classList.remove('hidden');
            modal.classList.add('flex');
        }

        function closeDeleteModal() {
            const modal = document.getElementById('deleteModal');
            modal.classList.add('hidden');
            modal.classList.remove('flex');
            currentEventId = null;
        }

        function confirmDelete() {
            if (currentEventId) {
                window.location.href = `event_management.php?delete=${currentEventId}`;
            }
        }

        // File upload preview
        document.getElementById('event_image').addEventListener('change', function(e) {
            if (e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.createElement('img');
                    preview.src = e.target.result;
                    preview.className = 'mt-2 h-20 mx-auto';
                    const container = this.parentElement;
                    const existingPreview = container.querySelector('img');
                    if (existingPreview) {
                        container.removeChild(existingPreview);
                    }
                    container.appendChild(preview);
                }.bind(this);
                reader.readAsDataURL(e.target.files[0]);
            }
        });

        document.getElementById('event_banner').addEventListener('change', function(e) {
            if (e.target.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    const preview = document.createElement('img');
                    preview.src = e.target.result;
                    preview.className = 'mt-2 h-20 mx-auto';
                    const container = this.parentElement;
                    const existingPreview = container.querySelector('img');
                    if (existingPreview) {
                        container.removeChild(existingPreview);
                    }
                    container.appendChild(preview);
                }.bind(this);
                reader.readAsDataURL(e.target.files[0]);
            }
        });
    </script>
</body>
</html>
