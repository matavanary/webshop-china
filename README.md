# Chinese E-commerce Website 🇨🇳🛒

A complete starter project for building a Chinese product e-commerce website using PHP + MySQL backend and HTML + TailwindCSS + JavaScript frontend.

## 🎯 Project Scope & Features

### Core Features
- **Product Management**: Complete product catalog with categories, variants, inventory tracking
- **User Management**: User registration, authentication, profiles, order history
- **Shopping Cart**: Add to cart, quantity management, persistent cart storage
- **Order Management**: Complete order processing workflow from cart to delivery
- **Payment Integration**: Ready for payment gateway integration (mock implementation included)
- **Shipping Integration**: Tracking numbers, shipment status, delivery confirmation
- **Admin Panel**: Admin user management with role-based permissions
- **Search & Filtering**: Product search and category-based filtering
- **Responsive Design**: Mobile-first design using TailwindCSS

### Technical Features
- **MVC Architecture**: Clean separation of concerns with Models, Views, Controllers
- **Database Schema**: Comprehensive database design for e-commerce operations
- **Security**: CSRF protection, password hashing, input sanitization
- **Logging**: Application logging to files and database
- **Caching**: Configuration ready for Redis/Memcached integration
- **API Ready**: RESTful API endpoints for AJAX operations
- **SEO Friendly**: Clean URLs, meta tags, sitemap ready

## 🚀 Quick Deployment Guide

### Prerequisites
- PHP 7.4 or higher
- MySQL 5.7 or higher
- Web server (Apache/Nginx)
- Node.js (for frontend build tools)
- Composer (optional, for future PHP dependencies)

### Step 1: Clone and Setup
```bash
git clone https://github.com/matavanary/webshop-china.git
cd webshop-china
```

### Step 2: Database Setup
```bash
# Create database
mysql -u root -p
CREATE DATABASE webshop_china CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
exit

# Import schema
mysql -u root -p webshop_china < database/schema.sql
```

### Step 3: Configuration
```bash
# Copy and configure settings
cp config/config.sample.php config/config.php

# Edit config/config.php with your database credentials and settings
nano config/config.php
```

### Step 4: Frontend Build
```bash
# Install dependencies
npm install

# Build CSS (development)
npm run dev

# Or build for production
npm run build
```

### Step 5: Web Server Configuration

#### Apache (.htaccess)
```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ /public/index.php [QSA,L]
```

#### Nginx
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/webshop-china/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php7.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

### Step 6: Permissions
```bash
chmod 755 public/
chmod 777 storage/ -R
chmod 777 public/assets/uploads/ -R
```

## 🔧 Development Guide

### Project Structure
```
webshop-china/
├── config/                 # Configuration files
│   ├── config.sample.php   # Sample configuration
│   └── config.php          # Your configuration (gitignored)
├── database/               # Database files
│   └── schema.sql          # Database structure
├── public/                 # Public web directory
│   ├── index.php           # Application entry point
│   └── assets/             # Static assets
│       ├── css/            # Stylesheets
│       ├── js/             # JavaScript files
│       └── images/         # Images
├── src/                    # Application source code
│   ├── controllers/        # Controller classes
│   ├── models/             # Model classes
│   └── helpers/            # Helper utilities
├── views/                  # View templates (to be created)
├── storage/                # Storage directory
│   ├── logs/               # Application logs
│   └── cache/              # Cache files
├── package.json            # Node.js dependencies
├── tailwind.config.js      # TailwindCSS configuration
└── README.md               # This file
```

### Adding New Features

#### 1. Creating a New Model
```php
<?php
// src/models/YourModel.php
require_once __DIR__ . '/BaseModel.php';

class YourModel extends BaseModel {
    protected $table = 'your_table';
    protected $fillable = ['field1', 'field2'];
    
    public function customMethod() {
        // Your custom logic
    }
}
```

#### 2. Creating a New Controller
```php
<?php
// src/controllers/YourController.php
require_once __DIR__ . '/BaseController.php';

class YourController extends BaseController {
    public function index() {
        $this->render('your/index', ['data' => $data]);
    }
}
```

#### 3. Adding Routes
Edit `public/index.php` to add new routes:
```php
$router->get('/your-route', [YourController::class, 'index']);
$router->post('/your-route', [YourController::class, 'store']);
```

### CSS Customization
The project uses TailwindCSS with custom components defined in `public/assets/css/app.css`:

```css
/* Add custom components */
@layer components {
  .your-component {
    @apply bg-blue-500 text-white p-4 rounded;
  }
}
```

Build CSS after changes:
```bash
npm run dev    # Development (with watch)
npm run build  # Production
```

## 🔌 Integration Guides

### Payment Gateway Integration

#### 1. Omise (Thailand)
```php
// In config/config.php
'payment' => [
    'omise' => [
        'enabled' => true,
        'public_key' => 'pkey_live_xxxxxxxxxx',
        'secret_key' => 'skey_live_xxxxxxxxxx',
    ]
]

// Implementation example
$charge = OmiseCharge::create([
    'amount' => $amount * 100, // Amount in satang
    'currency' => 'THB',
    'card' => $token
]);
```

#### 2. PromptPay
```php
// Generate PromptPay QR Code
$promptpay = new PromptPay();
$qr_code = $promptpay->generateQR($phone_number, $amount);
```

#### 3. 2C2P
```php
// 2C2P payment processing
$payment_data = [
    'merchant_id' => $config['payment']['2c2p']['merchant_id'],
    'amount' => $amount,
    'currency' => 'THB',
    'order_id' => $order_id
];
```

### TikTok Pixel Integration

#### 1. Frontend Implementation
```html
<!-- Add to your layout header -->
<script>
!function (w, d, t) {
  w.TiktokAnalyticsObject=t;var ttq=w[t]=w[t]||[];ttq.methods=["page","track","identify","instances","debug","on","off","once","ready","alias","group","enableCookie","disableCookie"],ttq.setAndDefer=function(t,e){t[e]=function(){t.push([e].concat(Array.prototype.slice.call(arguments,0)))}};for(var i=0;i<ttq.methods.length;i++)ttq.setAndDefer(ttq,ttq.methods[i]);ttq.instance=function(t){for(var e=ttq._i[t]||[],n=0;n<ttq.methods.length;n++)ttq.setAndDefer(e,ttq.methods[n]);return e},ttq.load=function(e,n){var i="https://analytics.tiktok.com/i18n/pixel/events.js";ttq._i=ttq._i||{},ttq._i[e]=[],ttq._i[e]._u=i,ttq._t=ttq._t||{},ttq._t[e]=+new Date,ttq._o=ttq._o||{},ttq._o[e]=n||{};var o=document.createElement("script");o.type="text/javascript",o.async=!0,o.src=i+"?sdkid="+e+"&lib="+t;var a=document.getElementsByTagName("script")[0];a.parentNode.insertBefore(o,a)};
  ttq.load('YOUR_PIXEL_ID');
  ttq.page();
}(window, document, 'ttq');
</script>
```

#### 2. Event Tracking
```php
// Track purchase event
echo "<script>
ttq.track('CompletePayment', {
    'content_id': '{$product_id}',
    'content_type': 'product',
    'value': {$order_total},
    'currency': 'THB'
});
</script>";
```

### LINE Integration

#### 1. LINE Login
```php
// LINE OAuth configuration
$line_config = [
    'channel_id' => $config['line']['channel_id'],
    'channel_secret' => $config['line']['channel_secret'],
    'redirect_uri' => 'https://yourdomain.com/auth/line/callback'
];
```

#### 2. LINE Notify
```php
// Send LINE notification
function sendLineNotify($message, $token) {
    $curl = curl_init();
    curl_setopt_array($curl, [
        CURLOPT_URL => "https://notify-api.line.me/api/notify",
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => http_build_query(['message' => $message]),
        CURLOPT_HTTPHEADER => [
            'Authorization: Bearer ' . $token,
            'Content-Type: application/x-www-form-urlencoded'
        ],
        CURLOPT_RETURNTRANSFER => true
    ]);
    
    $response = curl_exec($curl);
    curl_close($curl);
    return $response;
}
```

### SMTP Email Configuration

#### 1. Gmail SMTP
```php
// In config/config.php
'email' => [
    'driver' => 'smtp',
    'host' => 'smtp.gmail.com',
    'port' => 587,
    'username' => 'your-email@gmail.com',
    'password' => 'your-app-password', // Use App Password, not regular password
    'encryption' => 'tls'
]
```

#### 2. Send Email Function
```php
function sendEmail($to, $subject, $body) {
    $config = require 'config/config.php';
    $mail_config = $config['email'];
    
    $headers = [
        'From: ' . $mail_config['from']['address'],
        'Reply-To: ' . $mail_config['reply_to']['address'],
        'Content-Type: text/html; charset=UTF-8',
        'MIME-Version: 1.0'
    ];
    
    return mail($to, $subject, $body, implode("\r\n", $headers));
}
```

## 🎨 Customization Guide

### Theme Customization
1. **Colors**: Edit `tailwind.config.js` to change the color scheme
2. **Fonts**: Update Google Fonts imports in `public/assets/css/app.css`
3. **Layout**: Modify view templates in the `views/` directory
4. **Components**: Add custom TailwindCSS components in the CSS file

### Language Localization
1. Create language files in `config/languages/`
2. Implement translation function in helpers
3. Update view templates with translation calls

### Adding New Product Fields
1. Add columns to database schema
2. Update Product model's `$fillable` array
3. Modify admin forms and display templates

## 🔒 Security Best Practices

### Implemented Security Features
- **Password Hashing**: Using PHP's `password_hash()`
- **CSRF Protection**: Token-based CSRF protection
- **Input Sanitization**: All user inputs are sanitized
- **SQL Injection Prevention**: Using prepared statements
- **XSS Protection**: Output escaping in views
- **Session Security**: Secure session configuration

### Additional Security Recommendations
1. **SSL Certificate**: Always use HTTPS in production
2. **Environment Variables**: Store sensitive data in environment variables
3. **Regular Updates**: Keep PHP, MySQL, and dependencies updated
4. **Rate Limiting**: Implement API rate limiting
5. **Backup Strategy**: Regular database and file backups

## 📊 Monitoring & Analytics

### Application Logging
Logs are stored in:
- **File logs**: `storage/logs/YYYY-MM-DD.log`
- **Database logs**: `logs` table

### Error Monitoring
- Enable error logging in production
- Set up log rotation for file logs
- Monitor database performance

### Analytics Integration
- Google Analytics: Add tracking ID in config
- TikTok Pixel: Configure pixel ID for conversion tracking
- Facebook Pixel: Set up for social media marketing

## 🚀 Performance Optimization

### Caching Strategy
1. **Database Query Caching**: Implement Redis for query results
2. **File Caching**: Cache compiled views and configuration
3. **CDN**: Use CDN for static assets
4. **Image Optimization**: Implement image compression and WebP format

### Database Optimization
1. **Indexes**: Ensure proper indexing on frequently queried columns
2. **Query Optimization**: Use EXPLAIN to optimize slow queries
3. **Connection Pooling**: Configure connection pooling for high traffic

## 🔄 Backup & Recovery

### Database Backup
```bash
# Daily backup script
mysqldump -u username -p webshop_china > backup_$(date +%Y%m%d).sql
```

### File Backup
```bash
# Backup uploaded files
tar -czf files_backup_$(date +%Y%m%d).tar.gz public/assets/uploads/
```

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch: `git checkout -b feature/new-feature`
3. Commit changes: `git commit -am 'Add new feature'`
4. Push to branch: `git push origin feature/new-feature`
5. Submit a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

For support and questions:
- Create an issue on GitHub
- Email: support@webshop-china.com
- Documentation: [Project Wiki](https://github.com/matavanary/webshop-china/wiki)

## 🔮 Roadmap

### Upcoming Features
- [ ] Multi-language support
- [ ] Advanced inventory management
- [ ] Dropshipping integration
- [ ] Mobile app API
- [ ] Advanced analytics dashboard
- [ ] AI-powered product recommendations
- [ ] Bulk import/export tools
- [ ] Advanced SEO features

---

**Happy Coding! 🚀**

Made with ❤️ for the Thai e-commerce community