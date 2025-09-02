<?php
/**
 * User Model
 * 
 * Handles all user-related database operations
 */

namespace models;

class User {
    private $db;
    private $table = 'users';

    public function __construct($database) {
        $this->db = $database;
    }

    /**
     * Get user by ID
     */
    public function getById($id) {
        return $this->db->findOne($this->table, ['id' => $id, 'is_active' => 1]);
    }

    /**
     * Get user by email
     */
    public function getByEmail($email) {
        return $this->db->findOne($this->table, ['email' => $email, 'is_active' => 1]);
    }

    /**
     * Get user by username
     */
    public function getByUsername($username) {
        return $this->db->findOne($this->table, ['username' => $username, 'is_active' => 1]);
    }

    /**
     * Create new user
     */
    public function create($data) {
        // Hash password
        if (isset($data['password'])) {
            $data['password_hash'] = hashPassword($data['password']);
            unset($data['password']);
        }

        // Set default values
        $data['created_at'] = date('Y-m-d H:i:s');
        $data['updated_at'] = date('Y-m-d H:i:s');
        $data['role'] = $data['role'] ?? 'customer';
        $data['is_active'] = $data['is_active'] ?? 1;

        return $this->db->create($this->table, $data);
    }

    /**
     * Update user
     */
    public function update($id, $data) {
        // Hash password if provided
        if (isset($data['password'])) {
            $data['password_hash'] = hashPassword($data['password']);
            unset($data['password']);
        }

        $data['updated_at'] = date('Y-m-d H:i:s');
        return $this->db->update($this->table, $data, ['id' => $id]);
    }

    /**
     * Authenticate user
     */
    public function authenticate($emailOrUsername, $password) {
        // Try to find user by email or username
        $user = $this->getByEmail($emailOrUsername);
        if (!$user) {
            $user = $this->getByUsername($emailOrUsername);
        }

        if (!$user) {
            return false;
        }

        if (!verifyPassword($password, $user['password_hash'])) {
            return false;
        }

        // Update last login
        $this->update($user['id'], ['last_login' => date('Y-m-d H:i:s')]);

        return $user;
    }

    /**
     * Register new user
     */
    public function register($data) {
        $errors = $this->validate($data);
        if (!empty($errors)) {
            return ['success' => false, 'errors' => $errors];
        }

        try {
            $userId = $this->create($data);
            return ['success' => true, 'user_id' => $userId];
        } catch (\Exception $e) {
            return ['success' => false, 'errors' => ['general' => '注册失败，请稍后重试']];
        }
    }

    /**
     * Validate user data
     */
    public function validate($data, $isUpdate = false) {
        $errors = [];

        // Username validation
        if (!$isUpdate && empty($data['username'])) {
            $errors['username'] = '用户名不能为空';
        } elseif (!empty($data['username'])) {
            if (strlen($data['username']) < 3) {
                $errors['username'] = '用户名至少3个字符';
            } elseif ($this->usernameExists($data['username'], $data['id'] ?? null)) {
                $errors['username'] = '用户名已存在';
            }
        }

        // Email validation
        if (!$isUpdate && empty($data['email'])) {
            $errors['email'] = '邮箱不能为空';
        } elseif (!empty($data['email'])) {
            if (!isValidEmail($data['email'])) {
                $errors['email'] = '邮箱格式不正确';
            } elseif ($this->emailExists($data['email'], $data['id'] ?? null)) {
                $errors['email'] = '邮箱已被注册';
            }
        }

        // Password validation
        if (!$isUpdate && empty($data['password'])) {
            $errors['password'] = '密码不能为空';
        } elseif (!empty($data['password'])) {
            if (strlen($data['password']) < 8) {
                $errors['password'] = '密码至少8个字符';
            }
        }

        // Confirm password
        if (!empty($data['password']) && isset($data['confirm_password'])) {
            if ($data['password'] !== $data['confirm_password']) {
                $errors['confirm_password'] = '确认密码不匹配';
            }
        }

        // Name validation
        if (!$isUpdate && empty($data['first_name'])) {
            $errors['first_name'] = '名字不能为空';
        }

        if (!$isUpdate && empty($data['last_name'])) {
            $errors['last_name'] = '姓氏不能为空';
        }

        return $errors;
    }

    /**
     * Check if username exists
     */
    private function usernameExists($username, $excludeId = null) {
        if ($excludeId) {
            $sql = "SELECT id FROM {$this->table} WHERE username = ? AND id != ?";
            $result = $this->db->queryOne($sql, [$username, $excludeId]);
        } else {
            $result = $this->db->findOne($this->table, ['username' => $username]);
        }
        return !empty($result);
    }

    /**
     * Check if email exists
     */
    private function emailExists($email, $excludeId = null) {
        if ($excludeId) {
            $sql = "SELECT id FROM {$this->table} WHERE email = ? AND id != ?";
            $result = $this->db->queryOne($sql, [$email, $excludeId]);
        } else {
            $result = $this->db->findOne($this->table, ['email' => $email]);
        }
        return !empty($result);
    }

    /**
     * Get user orders
     */
    public function getOrders($userId, $page = 1, $perPage = 10) {
        $offset = ($page - 1) * $perPage;
        
        $orders = $this->db->find(
            'orders', 
            ['user_id' => $userId], 
            'created_at DESC', 
            "LIMIT {$perPage} OFFSET {$offset}"
        );

        $total = $this->db->getTotalCount('orders', ['user_id' => $userId]);

        return [
            'orders' => $orders,
            'total' => $total,
            'page' => $page,
            'per_page' => $perPage,
            'total_pages' => ceil($total / $perPage)
        ];
    }

    /**
     * Get user wishlist
     */
    public function getWishlist($userId) {
        $sql = "
            SELECT w.*, p.name, p.slug, p.price, p.sale_price, p.image_url 
            FROM wishlists w 
            JOIN products p ON w.product_id = p.id 
            WHERE w.user_id = ? AND p.is_active = 1 
            ORDER BY w.created_at DESC
        ";
        
        return $this->db->query($sql, [$userId]);
    }

    /**
     * Add to wishlist
     */
    public function addToWishlist($userId, $productId) {
        try {
            return $this->db->create('wishlists', [
                'user_id' => $userId,
                'product_id' => $productId,
                'created_at' => date('Y-m-d H:i:s')
            ]);
        } catch (\Exception $e) {
            // Item already in wishlist
            return false;
        }
    }

    /**
     * Remove from wishlist
     */
    public function removeFromWishlist($userId, $productId) {
        return $this->db->delete('wishlists', [
            'user_id' => $userId,
            'product_id' => $productId
        ]);
    }

    /**
     * Check if product is in wishlist
     */
    public function isInWishlist($userId, $productId) {
        $item = $this->db->findOne('wishlists', [
            'user_id' => $userId,
            'product_id' => $productId
        ]);
        return !empty($item);
    }

    /**
     * Get user addresses
     */
    public function getAddresses($userId) {
        return $this->db->find('user_addresses', ['user_id' => $userId], 'is_default DESC, created_at DESC');
    }

    /**
     * Add user address
     */
    public function addAddress($userId, $data) {
        $data['user_id'] = $userId;
        $data['created_at'] = date('Y-m-d H:i:s');
        
        // If this is set as default, remove default from other addresses
        if (!empty($data['is_default'])) {
            $this->db->update('user_addresses', ['is_default' => 0], ['user_id' => $userId]);
        }
        
        return $this->db->create('user_addresses', $data);
    }

    /**
     * Update address
     */
    public function updateAddress($addressId, $userId, $data) {
        // If this is set as default, remove default from other addresses
        if (!empty($data['is_default'])) {
            $this->db->update('user_addresses', ['is_default' => 0], ['user_id' => $userId]);
        }
        
        return $this->db->update('user_addresses', $data, [
            'id' => $addressId,
            'user_id' => $userId
        ]);
    }

    /**
     * Delete address
     */
    public function deleteAddress($addressId, $userId) {
        return $this->db->delete('user_addresses', [
            'id' => $addressId,
            'user_id' => $userId
        ]);
    }

    /**
     * Reset password
     */
    public function resetPassword($email) {
        $user = $this->getByEmail($email);
        if (!$user) {
            return false;
        }

        $token = generateRandomString(64);
        $expiry = date('Y-m-d H:i:s', strtotime('+1 hour'));

        // Store reset token
        $this->db->create('password_resets', [
            'email' => $email,
            'token' => $token,
            'expires_at' => $expiry,
            'created_at' => date('Y-m-d H:i:s')
        ]);

        // Send email (in a real app, you'd queue this)
        $resetUrl = "http://localhost/reset-password?token={$token}";
        $subject = "密码重置 - WebShop China";
        $message = "请点击以下链接重置您的密码：\n\n{$resetUrl}\n\n此链接将在1小时后过期。";
        
        return sendEmail($email, $subject, $message);
    }

    /**
     * Verify reset token
     */
    public function verifyResetToken($token) {
        $sql = "
            SELECT * FROM password_resets 
            WHERE token = ? AND expires_at > NOW() 
            ORDER BY created_at DESC 
            LIMIT 1
        ";
        
        return $this->db->queryOne($sql, [$token]);
    }

    /**
     * Update password with token
     */
    public function updatePasswordWithToken($token, $password) {
        $reset = $this->verifyResetToken($token);
        if (!$reset) {
            return false;
        }

        $user = $this->getByEmail($reset['email']);
        if (!$user) {
            return false;
        }

        // Update password
        $this->update($user['id'], ['password' => $password]);

        // Delete reset token
        $this->db->delete('password_resets', ['token' => $token]);

        return true;
    }
}