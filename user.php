<?php
// FILE: User.php
// Model for handling all database operations related to users.

class UserModel {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function get_user_by_username($username) {
        $sql = "SELECT * FROM users WHERE username = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$username]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    public function get_role_name($role_id) {
        $sql = "SELECT role_name FROM roles WHERE role_id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([$role_id]);
        return $stmt->fetchColumn();
    }
    
    /**
     * Create a new user
     * 
     * @param array $data User data including fullname, username, password, department, role_id
     * @return bool Success or failure
     */
    public function create_user($data) {
        try {
            $sql = "INSERT INTO users (fullname, username, password, department, role_id, created_at) 
                    VALUES (?, ?, ?, ?, ?, CURRENT_TIMESTAMP)";
            $stmt = $this->pdo->prepare($sql);
            
            // Hash the password before storing
            $hashedPassword = password_hash($data['password'], PASSWORD_BCRYPT);
            
            return $stmt->execute([
                $data['fullname'],
                $data['username'],
                $hashedPassword,
                $data['department'],
                $data['role_id']
            ]);
        } catch (PDOException $e) {
            error_log("Error creating user: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Update a user's password hash
     * 
     * @param int $user_id User ID
     * @param string $hash New password hash
     * @return bool Success or failure
     */
    public function update_password_hash($user_id, $hash) {
        try {
            $sql = "UPDATE users SET password = ? WHERE user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$hash, $user_id]);
        } catch (PDOException $e) {
            error_log("Error updating password hash: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * List all users with their roles
     * 
     * @return array Array of users with role information
     */
    public function list_users() {
        try {
            $sql = "SELECT u.*, r.role_name 
                    FROM users u 
                    LEFT JOIN roles r ON u.role_id = r.role_id 
                    ORDER BY u.fullname";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute();
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            error_log("Error listing users: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Deactivate a user
     * 
     * @param int $user_id User ID
     * @return bool Success or failure
     */
    public function deactivate_user($user_id) {
        try {
            $sql = "UPDATE users SET is_active = 0 WHERE user_id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            error_log("Error deactivating user: " . $e->getMessage());
            return false;
        }
    }
}
