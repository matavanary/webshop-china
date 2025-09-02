<?php
/**
 * Configuration File for WebShop China
 * 
 * Copy this file to config.php and update the values according to your environment
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
        'options' => [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]
    ],

    // Application Configuration
    'app' => [
        'name' => 'WebShop China',
        'url' => 'http://localhost',
        'timezone' => 'Asia/Shanghai',
        'locale' => 'zh_CN',
        'debug' => true,
        'env' => 'development', // development, production, testing
    ],

    // Security Configuration
    'security' => [
        'secret_key' => 'your-secret-key-change-this-in-production',
        'session_lifetime' => 3600, // seconds
        'password_min_length' => 8,
        'max_login_attempts' => 5,
        'lockout_duration' => 900, // seconds
    ],

    // Email Configuration
    'mail' => [
        'driver' => 'smtp', // smtp, sendmail, mail
        'host' => 'smtp.gmail.com',
        'port' => 587,
        'username' => 'your-email@gmail.com',
        'password' => 'your-app-password',
        'encryption' => 'tls', // tls, ssl
        'from' => [
            'address' => 'noreply@webshop-china.com',
            'name' => 'WebShop China'
        ]
    ],

    // File Upload Configuration
    'upload' => [
        'max_size' => 5242880, // 5MB in bytes
        'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'webp'],
        'upload_path' => '/public/assets/images/uploads/',
        'create_thumbnails' => true,
        'thumbnail_sizes' => [
            'thumb' => [150, 150],
            'medium' => [300, 300],
            'large' => [800, 600]
        ]
    ],

    // Cache Configuration
    'cache' => [
        'driver' => 'file', // file, redis, memcached
        'default_ttl' => 3600, // seconds
        'file_path' => '/tmp/cache/',
        'redis' => [
            'host' => '127.0.0.1',
            'port' => 6379,
            'database' => 0
        ]
    ],

    // Payment Gateway Configuration
    'payment' => [
        'default_gateway' => 'alipay',
        'currency' => 'CNY',
        'gateways' => [
            'alipay' => [
                'app_id' => 'your-alipay-app-id',
                'private_key' => 'your-alipay-private-key',
                'public_key' => 'alipay-public-key',
                'sandbox' => true
            ],
            'wechat' => [
                'app_id' => 'your-wechat-app-id',
                'mch_id' => 'your-merchant-id',
                'key' => 'your-wechat-key',
                'sandbox' => true
            ],
            'stripe' => [
                'public_key' => 'pk_test_your-stripe-public-key',
                'secret_key' => 'sk_test_your-stripe-secret-key',
                'webhook_secret' => 'whsec_your-webhook-secret'
            ]
        ]
    ],

    // Shipping Configuration
    'shipping' => [
        'default_method' => 'standard',
        'free_shipping_threshold' => 200.00,
        'methods' => [
            'standard' => [
                'name' => 'Standard Shipping',
                'cost' => 10.00,
                'delivery_days' => '3-5'
            ],
            'express' => [
                'name' => 'Express Shipping',
                'cost' => 25.00,
                'delivery_days' => '1-2'
            ],
            'overnight' => [
                'name' => 'Overnight Shipping',
                'cost' => 50.00,
                'delivery_days' => '1'
            ]
        ],
        'carriers' => [
            'sf_express' => [
                'api_key' => 'your-sf-express-api-key',
                'api_secret' => 'your-sf-express-secret'
            ],
            'ems' => [
                'api_key' => 'your-ems-api-key'
            ]
        ]
    ],

    // Analytics Configuration
    'analytics' => [
        'google_analytics_id' => 'GA-XXXXXXXXX',
        'facebook_pixel_id' => 'your-facebook-pixel-id',
        'enable_tracking' => true
    ],

    // Social Media Configuration
    'social' => [
        'facebook_app_id' => 'your-facebook-app-id',
        'facebook_app_secret' => 'your-facebook-app-secret',
        'wechat_app_id' => 'your-wechat-app-id',
        'wechat_app_secret' => 'your-wechat-app-secret'
    ],

    // API Configuration
    'api' => [
        'rate_limit' => 1000, // requests per hour
        'version' => 'v1',
        'authentication' => 'jwt', // jwt, oauth2, api_key
        'jwt_secret' => 'your-jwt-secret-key',
        'jwt_expiry' => 3600 // seconds
    ],

    // Logging Configuration
    'logging' => [
        'level' => 'info', // debug, info, notice, warning, error, critical, alert, emergency
        'log_file' => '/logs/app.log',
        'max_files' => 30,
        'max_size' => '10MB'
    ],

    // SEO Configuration
    'seo' => [
        'meta_title_suffix' => ' - WebShop China',
        'meta_description_default' => 'Shop the latest products at WebShop China. Fast shipping, great prices, and excellent customer service.',
        'robots' => 'index,follow',
        'sitemap_url' => '/sitemap.xml'
    ],

    // Third-party Services
    'services' => [
        'cdn_url' => 'https://cdn.webshop-china.com',
        'image_optimization' => [
            'enabled' => true,
            'service' => 'cloudinary', // cloudinary, imagekit, tinypng
            'api_key' => 'your-service-api-key'
        ],
        'search' => [
            'provider' => 'elasticsearch', // elasticsearch, algolia, database
            'elasticsearch' => [
                'host' => 'localhost:9200',
                'index' => 'webshop_products'
            ],
            'algolia' => [
                'app_id' => 'your-algolia-app-id',
                'api_key' => 'your-algolia-api-key',
                'index' => 'products'
            ]
        ]
    ]
];