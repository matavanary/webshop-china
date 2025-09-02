<?php
/**
 * Utility Functions for WebShop China
 * 
 * Common helper functions used throughout the application
 */

/**
 * Sanitize input data
 */
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

/**
 * Validate email address
 */
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Generate secure password hash
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_DEFAULT);
}

/**
 * Verify password against hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Generate random string
 */
function generateRandomString($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = generateRandomString(32);
    }
    return $_SESSION['csrf_token'];
}

/**
 * Verify CSRF token
 */
function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Format price with currency
 */
function formatPrice($price, $currency = 'CNY') {
    switch ($currency) {
        case 'CNY':
            return '￥' . number_format($price, 2);
        case 'USD':
            return '$' . number_format($price, 2);
        default:
            return number_format($price, 2) . ' ' . $currency;
    }
}

/**
 * Format date for display
 */
function formatDate($date, $format = 'Y-m-d H:i:s') {
    if (is_string($date)) {
        $date = new DateTime($date);
    }
    return $date->format($format);
}

/**
 * Get time ago string
 */
function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) return '刚刚';
    if ($time < 3600) return floor($time/60) . '分钟前';
    if ($time < 86400) return floor($time/3600) . '小时前';
    if ($time < 2592000) return floor($time/86400) . '天前';
    if ($time < 31104000) return floor($time/2592000) . '个月前';
    return floor($time/31104000) . '年前';
}

/**
 * Redirect to URL
 */
function redirect($url, $statusCode = 302) {
    header("Location: {$url}", true, $statusCode);
    exit;
}

/**
 * Check if user is logged in
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Check if user is admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['user_role']) && $_SESSION['user_role'] === 'admin';
}

/**
 * Require login
 */
function requireLogin() {
    if (!isLoggedIn()) {
        redirect('/login');
    }
}

/**
 * Require admin access
 */
function requireAdmin() {
    if (!isAdmin()) {
        redirect('/');
    }
}

/**
 * Generate SEO-friendly slug
 */
function generateSlug($string) {
    // Replace Chinese characters and special chars
    $string = preg_replace('/[\x{4e00}-\x{9fff}]+/u', '', $string);
    $string = strtolower(trim($string));
    $string = preg_replace('/[^a-z0-9-]/', '-', $string);
    $string = preg_replace('/-+/', '-', $string);
    return trim($string, '-');
}

/**
 * Upload file
 */
function uploadFile($file, $uploadDir = '/public/assets/images/uploads/', $allowedTypes = ['jpg', 'jpeg', 'png', 'gif']) {
    if (!isset($file['error']) || is_array($file['error'])) {
        throw new \Exception('Invalid file parameters');
    }

    switch ($file['error']) {
        case UPLOAD_ERR_OK:
            break;
        case UPLOAD_ERR_NO_FILE:
            throw new \Exception('No file sent');
        case UPLOAD_ERR_INI_SIZE:
        case UPLOAD_ERR_FORM_SIZE:
            throw new \Exception('Exceeded filesize limit');
        default:
            throw new \Exception('Unknown upload error');
    }

    if ($file['size'] > 5242880) { // 5MB
        throw new \Exception('Exceeded filesize limit');
    }

    $finfo = new \finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($file['tmp_name']);
    $extension = array_search($mimeType, [
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
    ]);

    if (!$extension || !in_array($extension, $allowedTypes)) {
        throw new \Exception('Invalid file format');
    }

    $uploadPath = ROOT_PATH . $uploadDir;
    if (!is_dir($uploadPath)) {
        mkdir($uploadPath, 0755, true);
    }

    $filename = sprintf('%s.%s', sha1_file($file['tmp_name']), $extension);
    $destination = $uploadPath . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destination)) {
        throw new \Exception('Failed to move uploaded file');
    }

    return $uploadDir . $filename;
}

/**
 * Send email
 */
function sendEmail($to, $subject, $message, $headers = []) {
    $defaultHeaders = [
        'MIME-Version' => '1.0',
        'Content-type' => 'text/html; charset=UTF-8',
        'From' => 'noreply@webshop-china.com',
        'Reply-To' => 'noreply@webshop-china.com',
        'X-Mailer' => 'PHP/' . phpversion()
    ];

    $headers = array_merge($defaultHeaders, $headers);
    $headerString = '';
    foreach ($headers as $key => $value) {
        $headerString .= "{$key}: {$value}\r\n";
    }

    return mail($to, $subject, $message, $headerString);
}

/**
 * Log message
 */
function logMessage($level, $message, $context = []) {
    $logFile = ROOT_PATH . '/logs/app.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }

    $timestamp = date('Y-m-d H:i:s');
    $contextStr = empty($context) ? '' : ' ' . json_encode($context);
    $logEntry = "[{$timestamp}] {$level}: {$message}{$contextStr}" . PHP_EOL;

    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

/**
 * Get setting value
 */
function getSetting($key, $default = null) {
    global $db;
    if (!$db) return $default;

    $setting = $db->findOne('settings', ['setting_key' => $key]);
    if (!$setting) return $default;

    switch ($setting['setting_type']) {
        case 'boolean':
            return filter_var($setting['setting_value'], FILTER_VALIDATE_BOOLEAN);
        case 'integer':
            return (int)$setting['setting_value'];
        case 'json':
            return json_decode($setting['setting_value'], true);
        default:
            return $setting['setting_value'];
    }
}

/**
 * Set setting value
 */
function setSetting($key, $value, $type = 'string') {
    global $db;
    if (!$db) return false;

    if ($type === 'json') {
        $value = json_encode($value);
    } elseif ($type === 'boolean') {
        $value = $value ? 'true' : 'false';
    }

    $existing = $db->findOne('settings', ['setting_key' => $key]);
    
    if ($existing) {
        return $db->update('settings', [
            'setting_value' => $value,
            'setting_type' => $type
        ], ['setting_key' => $key]);
    } else {
        return $db->create('settings', [
            'setting_key' => $key,
            'setting_value' => $value,
            'setting_type' => $type
        ]);
    }
}

/**
 * Calculate pagination info
 */
function getPaginationInfo($totalItems, $currentPage, $itemsPerPage) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($totalPages, $currentPage));
    
    return [
        'total_items' => $totalItems,
        'total_pages' => $totalPages,
        'current_page' => $currentPage,
        'items_per_page' => $itemsPerPage,
        'has_previous' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages,
        'previous_page' => max(1, $currentPage - 1),
        'next_page' => min($totalPages, $currentPage + 1)
    ];
}

/**
 * Generate pagination HTML
 */
function renderPagination($paginationInfo, $baseUrl = '') {
    if ($paginationInfo['total_pages'] <= 1) {
        return '';
    }

    $html = '<nav class="flex justify-center mt-8"><div class="flex space-x-2">';
    
    // Previous button
    if ($paginationInfo['has_previous']) {
        $html .= '<a href="' . $baseUrl . '?page=' . $paginationInfo['previous_page'] . '" class="px-3 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50">上一页</a>';
    }
    
    // Page numbers
    $start = max(1, $paginationInfo['current_page'] - 2);
    $end = min($paginationInfo['total_pages'], $paginationInfo['current_page'] + 2);
    
    for ($i = $start; $i <= $end; $i++) {
        $activeClass = $i === $paginationInfo['current_page'] ? 'bg-blue-500 text-white' : 'bg-white text-gray-700 hover:bg-gray-50';
        $html .= '<a href="' . $baseUrl . '?page=' . $i . '" class="px-3 py-2 border border-gray-300 rounded-md ' . $activeClass . '">' . $i . '</a>';
    }
    
    // Next button
    if ($paginationInfo['has_next']) {
        $html .= '<a href="' . $baseUrl . '?page=' . $paginationInfo['next_page'] . '" class="px-3 py-2 bg-white border border-gray-300 rounded-md hover:bg-gray-50">下一页</a>';
    }
    
    $html .= '</div></nav>';
    return $html;
}

/**
 * Generate breadcrumb HTML
 */
function renderBreadcrumb($items) {
    if (empty($items)) return '';
    
    $html = '<nav class="breadcrumb flex items-center space-x-2 text-sm text-gray-600 mb-6">';
    
    foreach ($items as $index => $item) {
        if ($index > 0) {
            $html .= '<span class="breadcrumb-separator">/</span>';
        }
        
        if (isset($item['url']) && $index < count($items) - 1) {
            $html .= '<a href="' . $item['url'] . '" class="breadcrumb-item hover:text-blue-600">' . $item['title'] . '</a>';
        } else {
            $html .= '<span class="text-gray-900">' . $item['title'] . '</span>';
        }
    }
    
    $html .= '</nav>';
    return $html;
}