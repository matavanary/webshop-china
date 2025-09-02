<?php
/**
 * User Model
 * Handles user-related database operations
 */

require_once __DIR__ . '/BaseModel.php';
require_once __DIR__ . '/../helpers/Utils.php';

class User extends BaseModel {
    protected $table = 'users';
    protected $fillable = [
        'username', 'email', 'password_hash', 'first_name', 'last_name',
        'phone', 'address', 'city', 'province', 'postal_code', 'country',
        'date_of_birth', 'gender', 'avatar_url', 'email_verified', 'phone_verified', 'status'
    ];

    /**
     * Create new user
     */
    public function createUser($data) {
        // Hash password
        if (isset($data['password'])) {
            $data['password_hash'] = Utils::hashPassword($data['password']);
            unset($data['password']);
        }

        // Generate unique username if not provided
        if (empty($data['username'])) {
            $data['username'] = $this->generateUsername($data['email']);
        }

        return $this->create($data);
    }

    /**
     * Authenticate user
     */
    public function authenticate($emailOrUsername, $password) {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (email = :login OR username = :login) 
                AND status = 'active' 
                LIMIT 1";
        
        $user = $this->db->fetchOne($sql, ['login' => $emailOrUsername]);
        
        if ($user && Utils::verifyPassword($password, $user['password_hash'])) {
            // Remove password hash from returned data
            unset($user['password_hash']);
            return $user;
        }
        
        return false;
    }

    /**
     * Get user by email
     */
    public function getByEmail($email) {
        $sql = "SELECT * FROM {$this->table} WHERE email = :email LIMIT 1";
        return $this->db->fetchOne($sql, ['email' => $email]);
    }

    /**
     * Get user by username
     */
    public function getByUsername($username) {
        $sql = "SELECT * FROM {$this->table} WHERE username = :username LIMIT 1";
        return $this->db->fetchOne($sql, ['username' => $username]);
    }

    /**
     * Check if email exists
     */
    public function emailExists($email, $excludeId = null) {
        $sql = "SELECT id FROM {$this->table} WHERE email = :email";
        $params = ['email' => $email];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        return (bool) $this->db->fetchOne($sql, $params);
    }

    /**
     * Check if username exists
     */
    public function usernameExists($username, $excludeId = null) {
        $sql = "SELECT id FROM {$this->table} WHERE username = :username";
        $params = ['username' => $username];
        
        if ($excludeId) {
            $sql .= " AND id != :exclude_id";
            $params['exclude_id'] = $excludeId;
        }
        
        return (bool) $this->db->fetchOne($sql, $params);
    }

    /**
     * Update password
     */
    public function updatePassword($userId, $newPassword) {
        $passwordHash = Utils::hashPassword($newPassword);
        return $this->update($userId, ['password_hash' => $passwordHash]);
    }

    /**
     * Verify email
     */
    public function verifyEmail($userId) {
        return $this->update($userId, ['email_verified' => 1]);
    }

    /**
     * Verify phone
     */
    public function verifyPhone($userId) {
        return $this->update($userId, ['phone_verified' => 1]);
    }

    /**
     * Update profile
     */
    public function updateProfile($userId, $data) {
        // Remove sensitive fields
        unset($data['password'], $data['password_hash'], $data['id']);
        
        return $this->update($userId, $data);
    }

    /**
     * Get user orders
     */
    public function getOrders($userId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT o.*, COUNT(oi.id) as item_count 
                FROM orders o 
                LEFT JOIN order_items oi ON o.id = oi.order_id 
                WHERE o.user_id = :user_id 
                GROUP BY o.id 
                ORDER BY o.created_at DESC 
                LIMIT :limit OFFSET :offset";
        
        $orders = $this->db->fetchAll($sql, [
            'user_id' => $userId,
            'limit' => $perPage,
            'offset' => $offset
        ]);

        // Get total count
        $countSql = "SELECT COUNT(*) as count FROM orders WHERE user_id = :user_id";
        $totalResult = $this->db->fetchOne($countSql, ['user_id' => $userId]);
        $totalRecords = $totalResult['count'];
        $totalPages = ceil($totalRecords / $perPage);

        return [
            'data' => $orders,
            'current_page' => $page,
            'per_page' => $perPage,
            'total_records' => $totalRecords,
            'total_pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ];
    }

    /**
     * Generate unique username
     */
    private function generateUsername($email) {
        $baseUsername = strtolower(explode('@', $email)[0]);
        $baseUsername = preg_replace('/[^a-z0-9]/', '', $baseUsername);
        
        $username = $baseUsername;
        $counter = 1;
        
        while ($this->usernameExists($username)) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        return $username;
    }

    /**
     * Get user statistics
     */
    public function getStatistics($userId) {
        $stats = [];
        
        // Total orders
        $orderSql = "SELECT COUNT(*) as count, SUM(total_amount) as total_spent 
                     FROM orders 
                     WHERE user_id = :user_id AND status != 'cancelled'";
        $orderStats = $this->db->fetchOne($orderSql, ['user_id' => $userId]);
        
        $stats['total_orders'] = $orderStats['count'] ?? 0;
        $stats['total_spent'] = $orderStats['total_spent'] ?? 0;
        
        // Recent order
        $recentOrderSql = "SELECT * FROM orders 
                           WHERE user_id = :user_id 
                           ORDER BY created_at DESC 
                           LIMIT 1";
        $stats['recent_order'] = $this->db->fetchOne($recentOrderSql, ['user_id' => $userId]);
        
        return $stats;
    }

    /**
     * Soft delete user (set status to inactive)
     */
    public function softDelete($userId) {
        return $this->update($userId, ['status' => 'inactive']);
    }

    /**
     * Reactivate user
     */
    public function reactivate($userId) {
        return $this->update($userId, ['status' => 'active']);
    }
}