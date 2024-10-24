<?php
// cancel_registration.php
session_start();
require_once '../config/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $event_id = $_POST['event_id'];
    $user_id = $_SESSION['user_id'];

    // Delete the registration record
    $sql = "DELETE FROM registrations WHERE event_id = ? AND user_id = ?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, "ii", $event_id, $user_id);

    if (mysqli_stmt_execute($stmt)) {
        $_SESSION['message'] = "You have successfully cancelled your registration.";
    } else {
        $_SESSION['error'] = "Error cancelling your registration.";
    }

    header("Location: my_events.php");
    exit;
}
?>
