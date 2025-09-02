<?php
/**
 * Utility Helper Functions
 * Common utility functions used throughout the application
 */

class Utils {
    
    /**
     * Sanitize input data
     */
    public static function sanitize($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitize'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }

    /**
     * Validate email address
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    /**
     * Generate random string
     */
    public static function generateRandomString($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    /**
     * Generate order number
     */
    public static function generateOrderNumber() {
        return 'ORD' . date('Ymd') . self::generateRandomString(6);
    }

    /**
     * Format price for display
     */
    public static function formatPrice($price, $currency = 'THB') {
        switch ($currency) {
            case 'THB':
                return '฿' . number_format($price, 2);
            case 'USD':
                return '$' . number_format($price, 2);
            case 'CNY':
                return '¥' . number_format($price, 2);
            default:
                return $currency . ' ' . number_format($price, 2);
        }
    }

    /**
     * Calculate discount percentage
     */
    public static function calculateDiscountPercentage($originalPrice, $salePrice) {
        if ($originalPrice <= 0) return 0;
        return round((($originalPrice - $salePrice) / $originalPrice) * 100);
    }

    /**
     * Generate slug from string
     */
    public static function generateSlug($string) {
        $string = strtolower($string);
        $string = preg_replace('/[^a-z0-9-]/', '-', $string);
        $string = preg_replace('/-+/', '-', $string);
        return trim($string, '-');
    }

    /**
     * Validate phone number (Thai format)
     */
    public static function validateThaiPhone($phone) {
        $phone = preg_replace('/[^0-9]/', '', $phone);
        return preg_match('/^(0[689]|06|02)\d{7,8}$/', $phone);
    }

    /**
     * Format file size
     */
    public static function formatBytes($size, $precision = 2) {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        for ($i = 0; $size > 1024 && $i < count($units) - 1; $i++) {
            $size /= 1024;
        }
        return round($size, $precision) . ' ' . $units[$i];
    }

    /**
     * Check if request is AJAX
     */
    public static function isAjax() {
        return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
               strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
    }

    /**
     * Get client IP address
     */
    public static function getClientIP() {
        if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            return $_SERVER['HTTP_CF_CONNECTING_IP'];
        } elseif (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            return $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            return $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
        }
    }

    /**
     * Log activity
     */
    public static function log($level, $message, $context = []) {
        $config = require __DIR__ . '/../../config/config.php';
        
        if (!$config['logging']['enabled']) {
            return;
        }

        // Log to file
        if ($config['logging']['channels']['file']['enabled']) {
            $logDir = $config['logging']['channels']['file']['path'];
            if (!is_dir($logDir)) {
                mkdir($logDir, 0755, true);
            }
            
            $logFile = $logDir . date('Y-m-d') . '.log';
            $timestamp = date('Y-m-d H:i:s');
            $logEntry = "[{$timestamp}] {$level}: {$message}" . PHP_EOL;
            if (!empty($context)) {
                $logEntry .= "Context: " . json_encode($context) . PHP_EOL;
            }
            
            file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
        }

        // Log to database
        if ($config['logging']['channels']['database']['enabled']) {
            try {
                $db = Database::getInstance();
                $db->insert('logs', [
                    'level' => $level,
                    'message' => $message,
                    'context' => json_encode($context),
                    'ip_address' => self::getClientIP(),
                    'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null,
                    'url' => $_SERVER['REQUEST_URI'] ?? null,
                    'method' => $_SERVER['REQUEST_METHOD'] ?? null,
                ]);
            } catch (Exception $e) {
                // Fallback to file logging if database fails
                error_log("Failed to log to database: " . $e->getMessage());
            }
        }
    }

    /**
     * Redirect helper
     */
    public static function redirect($url, $statusCode = 302) {
        header("Location: {$url}", true, $statusCode);
        exit;
    }

    /**
     * JSON response helper
     */
    public static function jsonResponse($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json');
        echo json_encode($data);
        exit;
    }

    /**
     * CSRF token generation and validation
     */
    public static function generateCSRFToken() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        $token = bin2hex(random_bytes(32));
        $_SESSION['csrf_token'] = $token;
        return $token;
    }

    public static function validateCSRFToken($token) {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Password hashing and verification
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }

    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
}