<?php
/**
 * WebShop China - Main Entry Point
 * 
 * This file serves as the main entry point for the webshop application.
 * It handles routing, authentication, and page rendering.
 */

// Start session
session_start();

// Set error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Define constants
define('ROOT_PATH', dirname(__DIR__));
define('PUBLIC_PATH', __DIR__);
define('SRC_PATH', ROOT_PATH . '/src');
define('CONFIG_PATH', ROOT_PATH . '/config');

// Autoloader for classes
spl_autoload_register(function ($class) {
    $file = SRC_PATH . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
});

// Load configuration
$configFile = CONFIG_PATH . '/config.php';
if (!file_exists($configFile)) {
    $configFile = CONFIG_PATH . '/config.sample.php';
}
$config = require $configFile;

// Include helper functions
require_once SRC_PATH . '/helpers/functions.php';

// Initialize database connection
require_once SRC_PATH . '/helpers/Database.php';
$db = new helpers\Database($config['database']);

// Simple routing
$request_uri = $_SERVER['REQUEST_URI'];
$path = parse_url($request_uri, PHP_URL_PATH);
$path = rtrim($path, '/');

// Route handling
switch ($path) {
    case '':
    case '/':
        $page = 'home';
        break;
    case '/products':
        $page = 'products';
        break;
    case '/product':
        $page = 'product-detail';
        break;
    case '/cart':
        $page = 'cart';
        break;
    case '/login':
        $page = 'login';
        break;
    case '/register':
        $page = 'register';
        break;
    case '/admin':
        $page = 'admin';
        break;
    default:
        $page = '404';
        break;
}

// Include the appropriate page
$pageFile = __DIR__ . "/pages/{$page}.php";
if (file_exists($pageFile)) {
    include $pageFile;
} else {
    include __DIR__ . '/pages/404.php';
}
?>

<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle ?? 'WebShop China - 您值得信赖的网购商城'; ?></title>
    <meta name="description" content="<?php echo $pageDescription ?? '在线购物商城，提供优质商品和服务'; ?>">
    
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: {
                            50: '#eff6ff',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- Custom CSS -->
    <link rel="stylesheet" href="/assets/css/style.css">
    
    <!-- Favicon -->
    <link rel="icon" type="image/x-icon" href="/assets/images/favicon.ico">
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm border-b">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between h-16">
                <div class="flex items-center">
                    <!-- Logo -->
                    <a href="/" class="flex items-center">
                        <img class="h-8 w-auto" src="/assets/images/logo.png" alt="WebShop China" onerror="this.style.display='none'">
                        <span class="ml-2 text-xl font-bold text-gray-900">WebShop China</span>
                    </a>
                    
                    <!-- Main Navigation -->
                    <div class="hidden md:ml-10 md:flex md:space-x-8">
                        <a href="/" class="text-gray-900 hover:text-primary-600 px-3 py-2 text-sm font-medium">首页</a>
                        <a href="/products" class="text-gray-500 hover:text-primary-600 px-3 py-2 text-sm font-medium">商品</a>
                        <a href="/categories" class="text-gray-500 hover:text-primary-600 px-3 py-2 text-sm font-medium">分类</a>
                        <a href="/about" class="text-gray-500 hover:text-primary-600 px-3 py-2 text-sm font-medium">关于我们</a>
                    </div>
                </div>
                
                <!-- Search Bar -->
                <div class="flex-1 flex items-center justify-center px-2 lg:ml-6 lg:justify-end">
                    <div class="max-w-lg w-full lg:max-w-xs">
                        <div class="relative">
                            <input type="text" placeholder="搜索商品..." class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-primary-500 focus:border-primary-500 sm:text-sm">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                                </svg>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- User Menu -->
                <div class="flex items-center space-x-4">
                    <!-- Cart -->
                    <a href="/cart" class="text-gray-500 hover:text-primary-600 relative">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4m0 0L7 13m0 0l-1.5 9M7 13h10m-10 0v9a2 2 0 002 2h6a2 2 0 002-2V13m-10 0h10m-2-10a2 2 0 00-2-2H9a2 2 0 00-2 2v6"></path>
                        </svg>
                        <span class="absolute -top-2 -right-2 bg-primary-600 text-white rounded-full text-xs w-5 h-5 flex items-center justify-center" id="cart-count">0</span>
                    </a>
                    
                    <!-- User Account -->
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <div class="relative">
                            <button class="flex items-center text-sm text-gray-500 hover:text-primary-600">
                                <span><?php echo htmlspecialchars($_SESSION['username'] ?? '用户'); ?></span>
                                <svg class="ml-1 h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                                </svg>
                            </button>
                        </div>
                    <?php else: ?>
                        <a href="/login" class="text-gray-500 hover:text-primary-600 text-sm font-medium">登录</a>
                        <a href="/register" class="bg-primary-600 text-white px-4 py-2 rounded-md text-sm font-medium hover:bg-primary-700">注册</a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <main class="min-h-screen">
        <?php
        // Include page content based on route
        switch ($page) {
            case 'home':
                ?>
                <!-- Hero Section -->
                <div class="bg-gradient-to-r from-primary-600 to-primary-700">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
                        <div class="text-center">
                            <h1 class="text-4xl md:text-6xl font-bold text-white mb-6">
                                欢迎来到 WebShop China
                            </h1>
                            <p class="text-xl text-primary-100 mb-8 max-w-2xl mx-auto">
                                发现优质商品，享受便捷购物体验。我们为您提供最新的产品和最优的服务。
                            </p>
                            <div class="space-y-4 sm:space-y-0 sm:space-x-4 sm:flex sm:justify-center">
                                <a href="/products" class="bg-white text-primary-600 px-8 py-3 rounded-md font-medium hover:bg-gray-50 transition-colors inline-block">
                                    开始购物
                                </a>
                                <a href="/categories" class="border border-primary-200 text-white px-8 py-3 rounded-md font-medium hover:bg-primary-500 transition-colors inline-block">
                                    浏览分类
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Featured Products -->
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                    <h2 class="text-3xl font-bold text-gray-900 mb-8 text-center">热门商品</h2>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <?php for ($i = 1; $i <= 8; $i++): ?>
                        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow border">
                            <div class="aspect-w-1 aspect-h-1 bg-gray-200 rounded-t-lg">
                                <img src="/assets/images/product-placeholder.jpg" alt="商品 <?php echo $i; ?>" class="w-full h-48 object-cover rounded-t-lg" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTgiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIiBmaWxsPSIjOTk5Ij7llYblk4E8L3RleHQ+PC9zdmc+'">
                            </div>
                            <div class="p-4">
                                <h3 class="text-lg font-medium text-gray-900 mb-2">示例商品 <?php echo $i; ?></h3>
                                <p class="text-sm text-gray-600 mb-3">这是一个示例商品的描述，展示商品的特色和优势。</p>
                                <div class="flex items-center justify-between">
                                    <span class="text-lg font-bold text-primary-600">￥<?php echo number_format(rand(99, 999), 2); ?></span>
                                    <button class="bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700 transition-colors text-sm">
                                        加入购物车
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>

                <!-- Features Section -->
                <div class="bg-white">
                    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16">
                        <div class="text-center mb-12">
                            <h2 class="text-3xl font-bold text-gray-900 mb-4">为什么选择我们</h2>
                            <p class="text-lg text-gray-600">我们致力于为您提供最好的购物体验</p>
                        </div>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                            <div class="text-center">
                                <div class="bg-primary-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 mb-2">快速配送</h3>
                                <p class="text-gray-600">全国范围内快速配送，让您尽快收到心仪的商品。</p>
                            </div>
                            <div class="text-center">
                                <div class="bg-primary-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 mb-2">品质保证</h3>
                                <p class="text-gray-600">所有商品均经过严格质检，确保您购买到高品质产品。</p>
                            </div>
                            <div class="text-center">
                                <div class="bg-primary-100 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-4">
                                    <svg class="w-8 h-8 text-primary-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8h2a2 2 0 012 2v6a2 2 0 01-2 2h-2v4l-4-4H9a2 2 0 01-2-2v-6a2 2 0 012-2h8V8z"></path>
                                    </svg>
                                </div>
                                <h3 class="text-xl font-semibold text-gray-900 mb-2">客服支持</h3>
                                <p class="text-gray-600">专业客服团队为您提供全天候支持和服务。</p>
                            </div>
                        </div>
                    </div>
                </div>
                <?php
                break;
                
            case 'products':
                ?>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                    <h1 class="text-3xl font-bold text-gray-900 mb-8">所有商品</h1>
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-6">
                        <?php for ($i = 1; $i <= 12; $i++): ?>
                        <div class="bg-white rounded-lg shadow-sm hover:shadow-md transition-shadow border">
                            <div class="aspect-w-1 aspect-h-1 bg-gray-200 rounded-t-lg">
                                <img src="/assets/images/product-placeholder.jpg" alt="商品 <?php echo $i; ?>" class="w-full h-48 object-cover rounded-t-lg" onerror="this.src='data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iMjAwIiBoZWlnaHQ9IjIwMCIgeG1sbnM9Imh0dHA6Ly93d3cudzMub3JnLzIwMDAvc3ZnIj48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSIjZGRkIi8+PHRleHQgeD0iNTAlIiB5PSI1MCUiIGZvbnQtc2l6ZT0iMTgiIHRleHQtYW5jaG9yPSJtaWRkbGUiIGR5PSIuM2VtIiBmaWxsPSIjOTk5Ij7llYblk4E8L3RleHQ+PC9zdmc+'">
                            </div>
                            <div class="p-4">
                                <h3 class="text-lg font-medium text-gray-900 mb-2">商品 <?php echo $i; ?></h3>
                                <p class="text-sm text-gray-600 mb-3">商品描述信息</p>
                                <div class="flex items-center justify-between">
                                    <span class="text-lg font-bold text-primary-600">￥<?php echo number_format(rand(99, 999), 2); ?></span>
                                    <button class="bg-primary-600 text-white px-4 py-2 rounded-md hover:bg-primary-700 transition-colors text-sm">
                                        查看详情
                                    </button>
                                </div>
                            </div>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php
                break;
                
            default:
                ?>
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-16 text-center">
                    <h1 class="text-4xl font-bold text-gray-900 mb-4">页面未找到</h1>
                    <p class="text-lg text-gray-600 mb-8">抱歉，您访问的页面不存在。</p>
                    <a href="/" class="bg-primary-600 text-white px-6 py-3 rounded-md hover:bg-primary-700 transition-colors">
                        返回首页
                    </a>
                </div>
                <?php
                break;
        }
        ?>
    </main>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-12">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <h3 class="text-lg font-semibold mb-4">WebShop China</h3>
                    <p class="text-gray-400">您值得信赖的在线购物平台，为您提供优质商品和服务。</p>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">快速链接</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="/" class="hover:text-white">首页</a></li>
                        <li><a href="/products" class="hover:text-white">商品</a></li>
                        <li><a href="/categories" class="hover:text-white">分类</a></li>
                        <li><a href="/about" class="hover:text-white">关于我们</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">客户服务</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li><a href="/help" class="hover:text-white">帮助中心</a></li>
                        <li><a href="/contact" class="hover:text-white">联系我们</a></li>
                        <li><a href="/returns" class="hover:text-white">退换货政策</a></li>
                        <li><a href="/shipping" class="hover:text-white">配送信息</a></li>
                    </ul>
                </div>
                <div>
                    <h3 class="text-lg font-semibold mb-4">联系信息</h3>
                    <div class="text-gray-400 space-y-2">
                        <p>电话: +86 400-123-4567</p>
                        <p>邮箱: info@webshop-china.com</p>
                        <p>地址: 中国北京市朝阳区</p>
                    </div>
                </div>
            </div>
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2024 WebShop China. 保留所有权利。</p>
            </div>
        </div>
    </footer>

    <!-- JavaScript -->
    <script src="/assets/js/app.js"></script>
</body>
</html>