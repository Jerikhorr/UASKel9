<?php
require_once '../includes/db_connect.php';

class Registration {
    private $conn;
    private $table = 'registrations';

    public $id;
    public $user_id;
    public $event_id;
    public $registration_date;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function register() {
        $query = "INSERT INTO " . $this->table . " SET user_id=?, event_id=?, registration_date=NOW()";
        $stmt = $this->conn->prepare($query);

        $stmt->bind_param("ii", $this->user_id, $this->event_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function cancelRegistration() {
        $query = "DELETE FROM " . $this->table . " WHERE user_id = ? AND event_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $this->user_id, $this->event_id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getRegisteredEvents($user_id) {
        $query = "SELECT e.* FROM events e INNER JOIN " . $this->table . " r ON e.id = r.event_id WHERE r.user_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result;
    }

    public function getRegistrantsForEvent($event_id) {
        $query = "SELECT u.* FROM users u INNER JOIN " . $this->table . " r ON u.id = r.user_id WHERE r.event_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result;
    }

    public function isUserRegistered($user_id, $event_id) {
        $query = "SELECT * FROM " . $this->table . " WHERE user_id = ? AND event_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("ii", $user_id, $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        return $result->num_rows > 0;
    }

    public function getRegistrationCount($event_id) {
        $query = "SELECT COUNT(*) as count FROM " . $this->table . " WHERE event_id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $event_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $row = $result->fetch_assoc();
        return $row['count'];
    }
}
?>