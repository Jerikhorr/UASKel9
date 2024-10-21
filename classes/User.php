<?php
class User {
    private $conn;
    private $table = 'users'; // Default table for user
    public $id;
    public $name;
    public $email;
    public $password;
    public $is_admin = 0; // Default to user role

    public function __construct($db) {
        $this->conn = $db;
    }

    public function setTable($table) {
        $this->table = $table; // Method to set the table name
    }

    public function authenticate($email, $password) {
        $query = "SELECT id, password, is_admin FROM " . $this->table . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows == 1) {
            $stmt->bind_result($this->id, $hashedPassword, $this->is_admin);
            $stmt->fetch();

            // Verify the password
            if (password_verify($password, $hashedPassword)) {
                return true; // Authentication successful
            }
        }
        return false; // Authentication failed
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " (name, email, password, is_admin) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("sssi", $this->name, $this->email, $this->password, $this->is_admin);

        return $stmt->execute();
    }

    // Other methods (e.g., update, delete) can be added here
}
?>
