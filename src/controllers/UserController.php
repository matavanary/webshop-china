<?php
/**
 * User Controller
 * 
 * Handles user-related HTTP requests like login, registration, profile management
 */

namespace controllers;

use models\User;

class UserController {
    private $userModel;

    public function __construct($database) {
        $this->userModel = new User($database);
    }

    /**
     * Display login page
     */
    public function loginPage() {
        if (isLoggedIn()) {
            redirect('/');
            return;
        }

        $GLOBALS['pageTitle'] = '用户登录';
        return;
    }

    /**
     * Handle login form submission
     */
    public function login() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return ['error' => 'Method not allowed'];
        }

        if (isLoggedIn()) {
            return ['success' => true, 'redirect' => '/'];
        }

        $emailOrUsername = sanitize($_POST['email_or_username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        // Validation
        if (empty($emailOrUsername)) {
            return ['error' => '请输入邮箱或用户名'];
        }

        if (empty($password)) {
            return ['error' => '请输入密码'];
        }

        // Authenticate user
        $user = $this->userModel->authenticate($emailOrUsername, $password);
        
        if (!$user) {
            return ['error' => '邮箱/用户名或密码错误'];
        }

        // Set session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];

        // Set remember me cookie
        if ($remember) {
            $token = generateRandomString(64);
            setcookie('remember_token', $token, time() + (86400 * 30), '/', '', false, true);
            
            // Store token in database (you'd need a remember_tokens table)
            global $db;
            $db->create('remember_tokens', [
                'user_id' => $user['id'],
                'token' => hash('sha256', $token),
                'expires_at' => date('Y-m-d H:i:s', time() + (86400 * 30)),
                'created_at' => date('Y-m-d H:i:s')
            ]);
        }

        $redirectUrl = $_POST['redirect'] ?? '/';
        
        if ($_SERVER['HTTP_ACCEPT'] === 'application/json') {
            header('Content-Type: application/json');
            return json_encode(['success' => true, 'redirect' => $redirectUrl]);
        }

        redirect($redirectUrl);
    }

    /**
     * Display registration page
     */
    public function registerPage() {
        if (isLoggedIn()) {
            redirect('/');
            return;
        }

        $GLOBALS['pageTitle'] = '用户注册';
        return;
    }

    /**
     * Handle registration form submission
     */
    public function register() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return ['error' => 'Method not allowed'];
        }

        if (isLoggedIn()) {
            return ['success' => true, 'redirect' => '/'];
        }

        $data = [
            'username' => sanitize($_POST['username'] ?? ''),
            'email' => sanitize($_POST['email'] ?? ''),
            'password' => $_POST['password'] ?? '',
            'confirm_password' => $_POST['confirm_password'] ?? '',
            'first_name' => sanitize($_POST['first_name'] ?? ''),
            'last_name' => sanitize($_POST['last_name'] ?? ''),
            'phone' => sanitize($_POST['phone'] ?? ''),
        ];

        // Register user
        $result = $this->userModel->register($data);

        if (!$result['success']) {
            if ($_SERVER['HTTP_ACCEPT'] === 'application/json') {
                header('Content-Type: application/json');
                return json_encode($result);
            }
            return $result;
        }

        // Auto login after successful registration
        $user = $this->userModel->getById($result['user_id']);
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['user_email'] = $user['email'];

        if ($_SERVER['HTTP_ACCEPT'] === 'application/json') {
            header('Content-Type: application/json');
            return json_encode(['success' => true, 'redirect' => '/']);
        }

        redirect('/');
    }

    /**
     * Handle logout
     */
    public function logout() {
        // Clear remember token
        if (isset($_COOKIE['remember_token'])) {
            global $db;
            $tokenHash = hash('sha256', $_COOKIE['remember_token']);
            $db->delete('remember_tokens', ['token' => $tokenHash]);
            
            setcookie('remember_token', '', time() - 3600, '/', '', false, true);
        }

        // Clear session
        session_destroy();
        
        redirect('/');
    }

    /**
     * Display user profile page
     */
    public function profile() {
        requireLogin();

        $user = $this->userModel->getById($_SESSION['user_id']);
        if (!$user) {
            redirect('/login');
            return;
        }

        $GLOBALS['pageTitle'] = '个人资料';
        $GLOBALS['user'] = $user;
        
        return $user;
    }

    /**
     * Update user profile
     */
    public function updateProfile() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return ['error' => 'Method not allowed'];
        }

        requireLogin();

        $data = [
            'id' => $_SESSION['user_id'],
            'first_name' => sanitize($_POST['first_name'] ?? ''),
            'last_name' => sanitize($_POST['last_name'] ?? ''),
            'phone' => sanitize($_POST['phone'] ?? ''),
        ];

        // Validate
        $errors = $this->userModel->validate($data, true);
        if (!empty($errors)) {
            return ['error' => 'Validation failed', 'errors' => $errors];
        }

        // Update user
        $success = $this->userModel->update($_SESSION['user_id'], $data);

        if ($success) {
            return ['success' => true, 'message' => '资料更新成功'];
        } else {
            return ['error' => '更新失败，请稍后重试'];
        }
    }

    /**
     * Change password
     */
    public function changePassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return ['error' => 'Method not allowed'];
        }

        requireLogin();

        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        // Validation
        if (empty($currentPassword)) {
            return ['error' => '请输入当前密码'];
        }

        if (empty($newPassword)) {
            return ['error' => '请输入新密码'];
        }

        if (strlen($newPassword) < 8) {
            return ['error' => '新密码至少8个字符'];
        }

        if ($newPassword !== $confirmPassword) {
            return ['error' => '确认密码不匹配'];
        }

        // Verify current password
        $user = $this->userModel->getById($_SESSION['user_id']);
        if (!verifyPassword($currentPassword, $user['password_hash'])) {
            return ['error' => '当前密码错误'];
        }

        // Update password
        $success = $this->userModel->update($_SESSION['user_id'], [
            'password' => $newPassword
        ]);

        if ($success) {
            return ['success' => true, 'message' => '密码修改成功'];
        } else {
            return ['error' => '密码修改失败，请稍后重试'];
        }
    }

    /**
     * Display user orders
     */
    public function orders() {
        requireLogin();

        $page = (int)($_GET['page'] ?? 1);
        $result = $this->userModel->getOrders($_SESSION['user_id'], $page);

        $GLOBALS['pageTitle'] = '我的订单';
        $GLOBALS['orders'] = $result['orders'];
        $GLOBALS['pagination'] = getPaginationInfo(
            $result['total'], 
            $page, 
            $result['per_page']
        );

        return $result;
    }

    /**
     * Display user wishlist
     */
    public function wishlist() {
        requireLogin();

        $wishlist = $this->userModel->getWishlist($_SESSION['user_id']);

        $GLOBALS['pageTitle'] = '我的收藏';
        $GLOBALS['wishlist'] = $wishlist;

        return $wishlist;
    }

    /**
     * Add product to wishlist
     */
    public function addToWishlist() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return ['error' => 'Method not allowed'];
        }

        requireLogin();

        $productId = $_POST['product_id'] ?? null;

        if (!$productId) {
            return ['error' => 'Product ID required'];
        }

        $success = $this->userModel->addToWishlist($_SESSION['user_id'], $productId);

        header('Content-Type: application/json');
        if ($success) {
            return json_encode(['success' => true, 'message' => '已添加到收藏']);
        } else {
            return json_encode(['error' => '商品已在收藏列表中']);
        }
    }

    /**
     * Remove product from wishlist
     */
    public function removeFromWishlist() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            http_response_code(405);
            return ['error' => 'Method not allowed'];
        }

        requireLogin();

        $productId = $_POST['product_id'] ?? null;

        if (!$productId) {
            return ['error' => 'Product ID required'];
        }

        $success = $this->userModel->removeFromWishlist($_SESSION['user_id'], $productId);

        header('Content-Type: application/json');
        return json_encode(['success' => $success, 'message' => '已从收藏中移除']);
    }

    /**
     * Forgot password
     */
    public function forgotPassword() {
        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $GLOBALS['pageTitle'] = '忘记密码';
            return;
        }

        $email = sanitize($_POST['email'] ?? '');

        if (empty($email)) {
            return ['error' => '请输入邮箱地址'];
        }

        if (!isValidEmail($email)) {
            return ['error' => '邮箱格式不正确'];
        }

        $success = $this->userModel->resetPassword($email);

        if ($success) {
            return ['success' => true, 'message' => '重置链接已发送到您的邮箱'];
        } else {
            return ['error' => '邮箱地址不存在'];
        }
    }

    /**
     * Reset password with token
     */
    public function resetPassword() {
        $token = $_GET['token'] ?? $_POST['token'] ?? '';

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            if (empty($token)) {
                redirect('/');
                return;
            }

            $reset = $this->userModel->verifyResetToken($token);
            if (!$reset) {
                $GLOBALS['pageTitle'] = '重置链接无效';
                $GLOBALS['error'] = '重置链接无效或已过期';
                return;
            }

            $GLOBALS['pageTitle'] = '重置密码';
            $GLOBALS['token'] = $token;
            return;
        }

        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';

        if (empty($password)) {
            return ['error' => '请输入新密码'];
        }

        if (strlen($password) < 8) {
            return ['error' => '密码至少8个字符'];
        }

        if ($password !== $confirmPassword) {
            return ['error' => '确认密码不匹配'];
        }

        $success = $this->userModel->updatePasswordWithToken($token, $password);

        if ($success) {
            return ['success' => true, 'message' => '密码重置成功，请登录'];
        } else {
            return ['error' => '重置失败，链接可能已过期'];
        }
    }

    /**
     * Check if email is available
     */
    public function checkEmailAvailability() {
        $email = $_GET['email'] ?? '';
        
        if (!isValidEmail($email)) {
            header('Content-Type: application/json');
            return json_encode(['available' => false, 'message' => '邮箱格式不正确']);
        }

        $user = $this->userModel->getByEmail($email);
        $available = !$user;

        header('Content-Type: application/json');
        return json_encode([
            'available' => $available, 
            'message' => $available ? '邮箱可用' : '邮箱已被注册'
        ]);
    }

    /**
     * Check if username is available
     */
    public function checkUsernameAvailability() {
        $username = $_GET['username'] ?? '';
        
        if (strlen($username) < 3) {
            header('Content-Type: application/json');
            return json_encode(['available' => false, 'message' => '用户名至少3个字符']);
        }

        $user = $this->userModel->getByUsername($username);
        $available = !$user;

        header('Content-Type: application/json');
        return json_encode([
            'available' => $available, 
            'message' => $available ? '用户名可用' : '用户名已存在'
        ]);
    }
}