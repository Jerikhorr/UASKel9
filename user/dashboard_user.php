<?php
session_start();
require_once('../includes/config.php');
require_once('../includes/db_connect.php');
require_once('../includes/functions.php');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'user') {
    header("Location: ../user/login.php");
    exit();
}

$conn = getDBConnection();

$query_events = "SELECT 
    e.*, 
    COALESCE(r.registrant_count, 0) as current_participants
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
    CASE 
        WHEN e.status = 'active' THEN 1
        WHEN e.status = 'upcoming' THEN 2
        ELSE 3 
    END,
    e.date ASC";

$result_events = mysqli_query($conn, $query_events);

$user_id = $_SESSION['user_id'];
$registered_events_query = "SELECT event_id FROM registrations WHERE user_id = '$user_id'";
$result_registered_events = mysqli_query($conn, $registered_events_query);
$registered_events = [];
if ($result_registered_events) {
    while ($row = mysqli_fetch_assoc($result_registered_events)) {
        $registered_events[] = $row['event_id'];
    }
}

mysqli_close($conn);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Dashboard - Events</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
            min-height: 100vh;
        }

        .event-card {
            position: relative;
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.05);
            border-radius: 0.5rem;
            padding: 0.5rem;
            height: auto;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .event-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.1);
        }

        .banner-image {
            height: 120px;
            object-fit: cover;
            width: 100%;
            transition: transform 0.3s ease;
        }

        .event-card:hover .banner-image {
            transform: scale(1.05);
        }

        .registered-badge {
            position: absolute;
            top: 8px;
            left: 8px;
            background: linear-gradient(135deg, #22c55e, #16a34a);
            color: white;
            padding: 4px 8px;
            border-radius: 9999px;
            font-size: 0.75rem;
            font-weight: 600;
            box-shadow: 0 2px 4px -1px rgba(0, 0, 0, 0.1);
            z-index: 10;
        }

        .status-badge {
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
            transition: background-color 0.3s ease;
        }

        .participants-badge {
            background: #f3f4f6;
            padding: 0.25rem 0.5rem;
            border-radius: 9999px;
            font-size: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 0.25rem;
        }

        .event-info {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            color: #6b7280;
            margin-bottom: 0.25rem;
        }

        .line-clamp-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            text-overflow: ellipsis;
        }
    </style>
</head>
<body class="bg-gray-50">
    <?php include '../includes/navbar_user.php'; ?>

    <div class="container mx-auto px-4 py-8">
        <?php if (isset($_SESSION['message'])): ?>
            <div class="message bg-green-100 border-l-4 border-green-500 text-green-700">
                <div class="flex items-center">
                    <i class="fas fa-check-circle mr-3"></i>
                    <?php echo $_SESSION['message']; unset($_SESSION['message']); ?>
                </div>
            </div>
        <?php elseif (isset($_SESSION['error'])): ?>
            <div class="message bg-red-100 border-l-4 border-red-500 text-red-700">
                <div class="flex items-center">
                    <i class="fas fa-exclamation-circle mr-3"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                </div>
            </div>
        <?php endif; ?>

        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
            <?php if (mysqli_num_rows($result_events) > 0): ?>
                <?php while($event = mysqli_fetch_assoc($result_events)): 
                    $is_registered = in_array($event['id'], $registered_events);
                    $status = htmlspecialchars($event['status']);

                    switch ($status) {
                        case 'upcoming':
                            $status_class = 'bg-blue-100 text-blue-800';
                            $status_icon = 'fa-clock';
                            break;
                        case 'active':
                            $status_class = 'bg-green-100 text-green-800';
                            $status_icon = 'fa-check-circle';
                            break;
                        case 'canceled':
                            $status_class = 'bg-red-100 text-red-800';
                            $status_icon = 'fa-times-circle';
                            break;
                        case 'completed':
                            $status_class = 'bg-gray-100 text-gray-800';
                            $status_icon = 'fa-flag-checkered';
                            break;
                        default:
                            $status_class = 'bg-gray-100 text-gray-800';
                            $status_icon = 'fa-info-circle';
                    }
                ?>
                    <div class="event-card rounded-xl">
                        <?php if($is_registered): ?>
                            <div class="registered-badge">
                                <i class="fas fa-check-circle mr-1"></i> Registered
                            </div>
                        <?php endif; ?>

                        <a href="event_details.php?id=<?php echo $event['id']; ?>" class="block">
                            <?php if($event['banner']): ?>
                                <div class="overflow-hidden">
                                    <img src="<?php echo htmlspecialchars($event['banner']); ?>" 
                                        class="banner-image" 
                                        alt="Event banner">
                                </div>
                            <?php endif; ?>
                            
                            <div class="p-6">
                                <h2 class="text-xl font-bold mb-3 text-gray-900">
                                    <?php echo htmlspecialchars($event['name']); ?>
                                </h2>
                                
                                <p class="text-gray-600 mb-4 line-clamp-2">
                                    <?php echo htmlspecialchars(substr($event['description'], 0, 100)) . '...'; ?>
                                </p>
                                
                                <div class="event-info">
                                    <i class="fas fa-calendar-alt text-blue-500"></i>
                                    <span><?php echo date('F d, Y', strtotime($event['date'])); ?>
                                    at <?php echo date('h:i A', strtotime($event['time'])); ?></span>
                                </div>
                                
                                <div class="event-info">
                                    <i class="fas fa-map-marker-alt text-red-500"></i>
                                    <span><?php echo htmlspecialchars($event['location']); ?></span>
                                </div>

                                <div class="mt-6 flex items-center justify-between">
                                    <span class="status-badge <?php echo $status_class; ?>">
                                        <i class="fas <?php echo $status_icon; ?>"></i>
                                        <?php echo ucfirst($status); ?>
                                    </span>
                                    
                                    <div class="participants-badge">
                                        <i class="fas fa-users text-gray-500"></i>
                                        <span><?php echo $event['current_participants']; ?>/<?php echo $event['max_participants']; ?></span>
                                    </div>
                                </div>
                            </div>
                        </a>
                    </div>
                <?php endwhile; ?>
            <?php else: ?>
                <div class="col-span-full">
                    <div class="bg-yellow-50 border-l-4 border-yellow-400 p-6 rounded-lg">
                        <div class="flex items-center">
                            <i class="fas fa-exclamation-circle text-yellow-400 text-2xl mr-4"></i>
                            <div>
                                <h3 class="text-lg font-medium text-yellow-800">No Events Available</h3>
                                <p class="text-yellow-700">Check back later for upcoming events!</p>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

</body>
</html>
