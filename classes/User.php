<?php
require_once '../includes/db_connect.php';

class User {
    private $conn;
    private $table = 'users';

    public $id;
    public $name;
    public $email;
    public $password;
    public $is_admin;

    public function __construct($db) {
        $this->conn = $db;
    }

    public function create() {
        $query = "INSERT INTO " . $this->table . " SET name=?, email=?, password=?, is_admin=?";
        $stmt = $this->conn->prepare($query);

        $this->name = sanitizeInput($this->name);
        $this->email = sanitizeInput($this->email);
        $this->password = password_hash($this->password, PASSWORD_BCRYPT, ['cost' => HASH_COST]);
        $this->is_admin = $this->is_admin ? 1 : 0;

        $stmt->bind_param("sssi", $this->name, $this->email, $this->password, $this->is_admin);

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
            $this->email = $row['email'];
            $this->is_admin = $row['is_admin'];
            return true;
        }
        return false;
    }

    public function update() {
        $query = "UPDATE " . $this->table . " SET name=?, email=? WHERE id=?";
        $stmt = $this->conn->prepare($query);

        $this->name = sanitizeInput($this->name);
        $this->email = sanitizeInput($this->email);

        $stmt->bind_param("ssi", $this->name, $this->email, $this->id);

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

    public function authenticate($email, $password) {
        $query = "SELECT id, password, is_admin FROM " . $this->table . " WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        if($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if(password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->is_admin = $row['is_admin'];
                return true;
            }
        }
        return false;
    }
}
?>