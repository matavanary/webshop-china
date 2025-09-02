<?php
/**
 * Configuration file for Chinese E-commerce Website
 * Copy this file to config.php and update the values accordingly
 */

return [
    // Database Configuration
    'database' => [
        'host' => 'localhost',
        'port' => 3306,
        'database' => 'webshop_china',
        'username' => 'your_db_username',
        'password' => 'your_db_password',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],

    // Application Configuration
    'app' => [
        'name' => 'Chinese E-commerce Website',
        'version' => '1.0.0',
        'environment' => 'development', // development, staging, production
        'debug' => true,
        'timezone' => 'Asia/Bangkok',
        'locale' => 'th',
        'currency' => 'THB',
        'base_url' => 'http://localhost:8000',
        'admin_url' => 'http://localhost:8000/admin',
    ],

    // Security Configuration
    'security' => [
        'secret_key' => 'your-secret-key-change-this-in-production',
        'jwt_secret' => 'your-jwt-secret-key-change-this',
        'password_min_length' => 8,
        'session_lifetime' => 3600, // 1 hour in seconds
        'csrf_token_name' => '_token',
        'encryption_method' => 'AES-256-CBC',
    ],

    // Payment Gateway Configuration
    'payment' => [
        // Mock Payment for testing
        'mock' => [
            'enabled' => true,
            'success_rate' => 0.9, // 90% success rate for testing
        ],
        
        // Example: Omise (popular in Thailand)
        'omise' => [
            'enabled' => false,
            'public_key' => 'pkey_test_xxxxxxxxxx',
            'secret_key' => 'skey_test_xxxxxxxxxx',
            'webhook_endpoint' => '/webhooks/omise',
        ],
        
        // Example: PromptPay
        'promptpay' => [
            'enabled' => false,
            'merchant_id' => 'your_merchant_id',
            'api_key' => 'your_api_key',
        ],
        
        // Example: 2C2P
        '2c2p' => [
            'enabled' => false,
            'merchant_id' => 'your_merchant_id',
            'secret_key' => 'your_secret_key',
            'api_url' => 'https://sandbox-pgw.2c2p.com', // Use production URL for live
        ]
    ],

    // Shipping Configuration
    'shipping' => [
        // Mock shipping for testing
        'mock' => [
            'enabled' => true,
            'providers' => ['Thailand Post', 'Kerry Express', 'J&T Express'],
        ],
        
        // Thailand Post
        'thailand_post' => [
            'enabled' => false,
            'api_key' => 'your_api_key',
            'username' => 'your_username',
            'password' => 'your_password',
        ],
        
        // Kerry Express
        'kerry' => [
            'enabled' => false,
            'api_key' => 'your_api_key',
            'merchant_code' => 'your_merchant_code',
        ],
    ],

    // Email Configuration (SMTP)
    'email' => [
        'driver' => 'smtp', // smtp, sendmail, mail
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'your_email@gmail.com',
        'password' => 'your_app_password',
        'encryption' => 'tls', // tls, ssl
        'from' => [
            'address' => 'noreply@webshop-china.com',
            'name' => 'Chinese E-commerce Website'
        ],
        'reply_to' => [
            'address' => 'support@webshop-china.com',
            'name' => 'Support Team'
        ]
    ],

    // LINE Integration
    'line' => [
        'enabled' => false,
        'channel_id' => 'your_line_channel_id',
        'channel_secret' => 'your_line_channel_secret',
        'channel_access_token' => 'your_line_channel_access_token',
        'webhook_url' => '/webhooks/line',
        'notify' => [
            'enabled' => false,
            'token' => 'your_line_notify_token',
        ]
    ],

    // TikTok Pixel Integration
    'tiktok_pixel' => [
        'enabled' => false,
        'pixel_id' => 'your_tiktok_pixel_id',
        'access_token' => 'your_tiktok_access_token',
        'events' => [
            'page_view' => true,
            'view_content' => true,
            'add_to_cart' => true,
            'initiate_checkout' => true,
            'complete_payment' => true,
        ]
    ],

    // Google Analytics
    'google_analytics' => [
        'enabled' => false,
        'tracking_id' => 'GA-XXXXXXXXX-X',
        'gtag_id' => 'G-XXXXXXXXXX',
    ],

    // Facebook Pixel
    'facebook_pixel' => [
        'enabled' => false,
        'pixel_id' => 'your_facebook_pixel_id',
    ],

    // File Upload Configuration
    'upload' => [
        'max_file_size' => 5242880, // 5MB in bytes
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'upload_path' => 'public/assets/uploads/',
        'image_quality' => 85,
        'thumbnails' => [
            'small' => [150, 150],
            'medium' => [300, 300],
            'large' => [800, 600],
        ]
    ],

    // Cache Configuration
    'cache' => [
        'driver' => 'file', // file, redis, memcached
        'prefix' => 'webshop_',
        'ttl' => 3600, // 1 hour
        'path' => 'storage/cache/',
        
        // Redis configuration (if using redis driver)
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'password' => null,
            'database' => 0,
        ]
    ],

    // Session Configuration
    'session' => [
        'driver' => 'file', // file, database, redis
        'lifetime' => 120, // minutes
        'path' => 'storage/sessions/',
        'cookie_name' => 'webshop_session',
        'cookie_path' => '/',
        'cookie_domain' => null,
        'cookie_secure' => false, // Set to true for HTTPS
        'cookie_httponly' => true,
    ],

    // Logging Configuration
    'logging' => [
        'enabled' => true,
        'level' => 'info', // debug, info, warning, error, critical
        'channels' => [
            'file' => [
                'enabled' => true,
                'path' => 'storage/logs/',
                'max_files' => 30,
            ],
            'database' => [
                'enabled' => true,
                'table' => 'logs',
            ]
        ]
    ],

    // API Configuration
    'api' => [
        'rate_limiting' => [
            'enabled' => true,
            'requests_per_minute' => 60,
            'requests_per_hour' => 1000,
        ],
        'cors' => [
            'enabled' => true,
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
        ]
    ],

    // Pagination
    'pagination' => [
        'products_per_page' => 24,
        'orders_per_page' => 20,
        'users_per_page' => 50,
    ],

    // China Import Configuration
    'china_import' => [
        'exchange_rate_api' => [
            'enabled' => false,
            'provider' => 'exchangerate-api', // or 'fixer', 'currencylayer'
            'api_key' => 'your_exchange_rate_api_key',
            'base_currency' => 'CNY',
            'target_currency' => 'THB',
            'update_frequency' => 'daily', // hourly, daily, weekly
        ],
        'markup_percentage' => 30, // Default markup for imported products
        'default_shipping_days' => 14, // Default shipping time from China
    ]
];