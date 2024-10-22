<?php
// Handle cancellation
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['cancel_registration'])) {
    $event_id = $_POST['event_id'];
    
    if ($registration->cancelRegistration($_SESSION['user_id'], $event_id)) {
        $success_message = "Event registration has been cancelled successfully.";
    } else {
        $error_message = "Failed to cancel registration. Please try again.";
    }
}

// Fetch user's registered events
$registered_events = $registration->getUserRegisteredEvents($_SESSION['user_id']);
?>

<div class="space-y-6">
    <div class="flex justify-between items-center">
        <h1 class="text-3xl font-bold text-gray-900">My Registered Events</h1>
    </div>

    <?php if (isset($success_message)) : ?>
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?php echo $success_message; ?></span>
        </div>
    <?php endif; ?>

    <?php if (isset($error_message)) : ?>
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
            <span class="block sm:inline"><?php echo $error_message; ?></span>
        </div>
    <?php endif; ?>

    <?php if ($registered_events->num_rows > 0) : ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php while ($event = $registered_events->fetch_assoc()) : ?>
                <div class="bg-white shadow-md rounded-lg overflow-hidden">
                    <?php if (!empty($event['banner_image'])) : ?>
                        <img src="<?php echo htmlspecialchars($event['banner_image']); ?>" 
                             alt="<?php echo htmlspecialchars($event['name']); ?>" 
                             class="w-full h-48 object-cover">
                    <?php endif; ?>
                    <div class="p-6">
                        <h2 class="text-xl font-semibold mb-2"><?php echo htmlspecialchars($event['name']); ?></h2>
                        <p class="text-gray-600 mb-4"><?php echo htmlspecialchars($event['description']); ?></p>
                        <div class="space-y-2 mb-4">
                            <p class="text-sm text-gray-500">
                                <strong>Date:</strong> <?php echo htmlspecialchars($event['date']); ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                <strong>Time:</strong> <?php echo htmlspecialchars($event['time']); ?>
                            </p>
                            <p class="text-sm text-gray-500">
                                <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?>
                            </p>
                        </div>
                        
                        <!-- Cancel Registration Button with Confirmation Dialog -->
                        <div x-data="{ showModal: false }">
                            <button @click="showModal = true" 
                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded w-full">
                                Cancel Registration
                            </button>

                            <!-- Modal -->
                            <div x-show="showModal" 
                                 class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center">
                                <div class="bg-white p-6 rounded-lg shadow-lg max-w-sm mx-4">
                                    <h3 class="text-lg font-bold mb-4">Confirm Cancellation</h3>
                                    <p class="mb-4">Are you sure you want to cancel your registration for this event?</p>
                                    <div class="flex justify-end space-x-3">
                                        <button @click="showModal = false" 
                                                class="bg-gray-300 hover:bg-gray-400 text-black font-bold py-2 px-4 rounded">
                                            No, Keep
                                        </button>
                                        <form method="POST" class="inline">
                                            <input type="hidden" name="event_id" value="<?php echo $event['id']; ?>">
                                            <button type="submit" 
                                                    name="cancel_registration" 
                                                    class="bg-red-500 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                                Yes, Cancel
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endwhile; ?>
        </div>
    <?php else : ?>
        <div class="bg-white shadow-md rounded-lg p-6 text-center">
            <p class="text-gray-600">You haven't registered for any events yet.</p>
            <a href="?page=browse_events" class="inline-block mt-4 bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                Browse Available Events
            </a>
        </div>
    <?php endif; ?>
</div>