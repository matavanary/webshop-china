<?php
/**
 * Entry Point for Chinese E-commerce Website
 * Routes requests to appropriate controllers
 */

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set timezone
date_default_timezone_set('Asia/Bangkok');

// Start session
session_start();

// Include configuration and helpers
require_once __DIR__ . '/../config/config.sample.php';
require_once __DIR__ . '/../src/helpers/Database.php';
require_once __DIR__ . '/../src/helpers/Utils.php';

// Include controllers
require_once __DIR__ . '/../src/controllers/HomeController.php';
require_once __DIR__ . '/../src/controllers/ProductController.php';

// Simple router
class Router {
    private $routes = [];
    
    public function get($path, $callback) {
        $this->routes['GET'][$path] = $callback;
    }
    
    public function post($path, $callback) {
        $this->routes['POST'][$path] = $callback;
    }
    
    public function dispatch() {
        $method = $_SERVER['REQUEST_METHOD'];
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        
        // Remove trailing slash except for root
        if ($path !== '/' && substr($path, -1) === '/') {
            $path = substr($path, 0, -1);
        }
        
        // Check for exact match first
        if (isset($this->routes[$method][$path])) {
            $callback = $this->routes[$method][$path];
            $this->executeCallback($callback);
            return;
        }
        
        // Check for dynamic routes
        foreach ($this->routes[$method] ?? [] as $route => $callback) {
            $pattern = $this->convertToRegex($route);
            if (preg_match($pattern, $path, $matches)) {
                array_shift($matches); // Remove full match
                $this->executeCallback($callback, $matches);
                return;
            }
        }
        
        // 404 - Route not found
        http_response_code(404);
        $controller = new HomeController();
        $controller->notFound();
    }
    
    private function convertToRegex($route) {
        $pattern = str_replace('/', '\/', $route);
        $pattern = preg_replace('/\{([^}]+)\}/', '([^\/]+)', $pattern);
        return '/^' . $pattern . '$/';
    }
    
    private function executeCallback($callback, $params = []) {
        if (is_array($callback)) {
            $controller = new $callback[0]();
            $method = $callback[1];
            call_user_func_array([$controller, $method], $params);
        } else {
            call_user_func_array($callback, $params);
        }
    }
}

// Initialize router
$router = new Router();

// Define routes
$router->get('/', [HomeController::class, 'index']);
$router->get('/about', [HomeController::class, 'about']);
$router->get('/contact', [HomeController::class, 'contact']);
$router->post('/contact', [HomeController::class, 'contact']);
$router->get('/privacy', [HomeController::class, 'privacy']);
$router->get('/terms', [HomeController::class, 'terms']);
$router->get('/faq', [HomeController::class, 'faq']);
$router->get('/search', [HomeController::class, 'search']);

// Product routes
$router->get('/products', [ProductController::class, 'index']);
$router->get('/product/{slug}', [ProductController::class, 'show']);
$router->get('/category/{slug}', [ProductController::class, 'category']);

// AJAX routes
$router->get('/api/product/{id}/quick-view', [ProductController::class, 'quickView']);
$router->get('/api/product/{id}/variants', [ProductController::class, 'getVariants']);
$router->post('/api/product/check-stock', [ProductController::class, 'checkStock']);

// Dispatch the request
try {
    $router->dispatch();
} catch (Exception $e) {
    Utils::log('error', 'Application error: ' . $e->getMessage());
    
    http_response_code(500);
    $controller = new HomeController();
    
    if (Utils::isAjax()) {
        Utils::jsonResponse(['error' => 'Internal server error'], 500);
    } else {
        echo '<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Server Error - Chinese E-commerce Website</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-md text-center">
            <h1 class="text-3xl font-bold text-red-600 mb-4">เกิดข้อผิดพลาด</h1>
            <p class="text-gray-600 mb-6">ขออภัย เกิดข้อผิดพลาดในระบบ กรุณาลองใหม่อีกครั้ง</p>
            <a href="/" class="bg-blue-500 text-white px-6 py-2 rounded hover:bg-blue-600 transition-colors">กลับหน้าหลัก</a>
        </div>
    </div>
</body>
</html>';
    }
}
?>