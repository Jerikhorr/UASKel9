<?php
require_once '../includes/db_connect.php';

class User {
    private $conn;
    private $userTable = 'users';
    private $adminTable = 'admin';

    public $id;
    public $name;
    public $email;
    public $password;
    public $is_admin;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $table = $this->is_admin ? $this->adminTable : $this->userTable;
        $query = "INSERT INTO " . $table . " (name, email, password) VALUES (?, ?, ?)";
        $stmt = $this->conn->prepare($query);

        $this->name = sanitizeInput($this->name);
        $this->email = sanitizeInput($this->email);
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);

        $stmt->bind_param("sss", $this->name, $this->email, $this->password);

        if ($stmt->execute()) {
            return true;
        }
        error_log("SQL Error: " . $stmt->error); // Log error if execution fails
        return false;
    }

    public function read($id) {
        $query = "SELECT * FROM " . $this->userTable . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            $this->id = $row['id'];
            $this->name = $row['name'];
            $this->email = $row['email'];
            $this->is_admin = $row['is_admin'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->userTable . " SET name=?, email=? WHERE id=?";
        $stmt = $this->conn->prepare($query);

        $this->name = sanitizeInput($this->name);
        $this->email = sanitizeInput($this->email);

        $stmt->bind_param("ssi", $this->name, $this->email, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        error_log("SQL Error: " . $stmt->error);
        return false;
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->userTable . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            return true;
        }
        error_log("SQL Error: " . $stmt->error);
        return false;
    }

    public function authenticate($email, $password) {
        // Check in user table
        $query = "SELECT id, password, is_admin FROM " . $this->userTable . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->is_admin = $row['is_admin'];
                return true;
            }
        }

        // Check in admin table if not found in user table
        $query = "SELECT id, password, 1 as is_admin FROM " . $this->adminTable . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->is_admin = true;
                return true;
            }
        }
        return false;
    }
}
?>
