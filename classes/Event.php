<?php
require_once '../includes/db_connect.php';

class Event {
    private $conn;
    private $table = 'events';

    public $id;
    public $name;
    public $description;
    public $date;
    public $time;
    public $location;
    public $max_participants;
    public $banner_image;
    public $status;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " SET name=?, description=?, date=?, time=?, location=?, max_participants=?, banner_image=?, status=?";
        $stmt = $this->conn->prepare($query);

        $this->name = sanitizeInput($this->name);
        $this->description = sanitizeInput($this->description);
        $this->location = sanitizeInput($this->location);
        $this->banner_image = sanitizeInput($this->banner_image);
        $this->status = sanitizeInput($this->status);

        $stmt->bind_param("sssssisss", $this->name, $this->description, $this->date, $this->time, $this->location, $this->max_participants, $this->banner_image, $this->status);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function read($id) {
        $query = "SELECT * FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->description = $row['description'];
            $this->date = $row['date'];
            $this->time = $row['time'];
            $this->location = $row['location'];
            $this->max_participants = $row['max_participants'];
            $this->banner_image = $row['banner_image'];
            $this->status = $row['status'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table . " SET name=?, description=?, date=?, time=?, location=?, max_participants=?, banner_image=?, status=? WHERE id=?";
        $stmt = $this->conn->prepare($query);

        $this->name = sanitizeInput($this->name);
        $this->description = sanitizeInput($this->description);
        $this->location = sanitizeInput($this->location);
        $this->banner_image = sanitizeInput($this->banner_image);
        $this->status = sanitizeInput($this->status);

        $stmt->bind_param("sssssisssi", $this->name, $this->description, $this->date, $this->time, $this->location, $this->max_participants, $this->banner_image, $this->status, $this->id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);

        if($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function getAllEvents() {
        $query = "SELECT * FROM " . $this->table . " ORDER BY date ASC";
        $result = $this->conn->query($query);
        return $result;
    }

    public function getAvailableEvents() {
        $query = "SELECT * FROM " . $this->table . " WHERE status = 'open' AND date >= CURDATE() ORDER BY date ASC";
        $result = $this->conn->query($query);
        return $result;
    }
}
?>