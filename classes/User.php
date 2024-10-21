<?php
require_once '../includes/db_connect.php';

class User {
    private $conn;
    private $table;

    public $id;
    public $name;
    public $email;
    public $password;
    public $is_admin;
    public $role; // Untuk menyimpan role

    public function __construct($db) {
        $this->conn = $db;
    }

    public function setTable($role) {
        // Tentukan tabel berdasarkan role
        if ($role === 'admin') {
            $this->table = 'admin'; // Ganti tabel menjadi admin jika peran adalah admin
        } else {
            $this->table = 'users'; // Default ke users
        }
    }

    public function create($role) {
        $this->setTable($role); // Set table berdasarkan role

        // Query untuk menyimpan data pengguna
        $query = "INSERT INTO " . $this->table . " (name, email, password, is_admin) VALUES (?, ?, ?, ?)";
        $stmt = $this->conn->prepare($query);

        // Sanitasi input
        $this->name = sanitizeInput($this->name);
        $this->email = sanitizeInput($this->email);
        $this->password = password_hash($this->password, PASSWORD_BCRYPT);
        $this->is_admin = $role === 'admin' ? 1 : 0; // 1 untuk admin, 0 untuk user

        // Debugging: Lihat nilai yang akan dimasukkan
        var_dump($this->name, $this->email, $this->password, $this->is_admin);

        // Bind parameter dan eksekusi
        $stmt->bind_param("ssii", $this->name, $this->email, $this->password, $this->is_admin);

        if ($stmt->execute()) {
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
        $query = "UPDATE " . $this->table . " SET name=?, email=? WHERE id=?";
        $stmt = $this->conn->prepare($query);

        // Sanitasi input
        $this->name = sanitizeInput($this->name);
        $this->email = sanitizeInput($this->email);

        $stmt->bind_param("ssi", $this->name, $this->email, $this->id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function delete($id) {
        $query = "DELETE FROM " . $this->table . " WHERE id = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("i", $id);

        if ($stmt->execute()) {
            return true;
        }
        return false;
    }

    public function authenticate($email, $password) {
        // Check in the admin table
        $query = "SELECT id, password, is_admin FROM admin WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Debugging: Check if the admin query was successful
        if ($result) {
            var_dump($result->num_rows); // Show number of rows returned
        }

        // If found in admin table
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->is_admin = $row['is_admin'];
                return 'admin'; // Return a string indicating admin role
            }
        }

        // Now check in the users table
        $query = "SELECT id, password, is_admin FROM users WHERE email = ?";
        $stmt = $this->conn->prepare($query);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();

        // Debugging: Check if the user query was successful
        if ($result) {
            var_dump($result->num_rows); // Show number of rows returned
        }

        // If found in users table
        if ($result->num_rows == 1) {
            $row = $result->fetch_assoc();
            if (password_verify($password, $row['password'])) {
                $this->id = $row['id'];
                $this->is_admin = $row['is_admin'];
                return 'user'; // Return a string indicating user role
            }
        }

        return false; // Login failed
    }
}
?>
