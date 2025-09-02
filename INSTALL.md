# Chinese E-commerce Website Installation Guide

## Quick Start

1. **Prerequisites**
   - PHP 7.4+
   - MySQL 5.7+
   - Node.js 14+
   - Web server (Apache/Nginx)

2. **Installation Steps**
   ```bash
   # Clone repository
   git clone https://github.com/matavanary/webshop-china.git
   cd webshop-china
   
   # Setup database
   mysql -u root -p
   CREATE DATABASE webshop_china CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   mysql -u root -p webshop_china < database/schema.sql
   
   # Configure application
   cp config/config.sample.php config/config.php
   # Edit config/config.php with your settings
   
   # Install frontend dependencies
   npm install
   npm run build
   
   # Set permissions
   chmod 777 storage/ -R
   chmod 777 public/assets/uploads/ -R
   ```

3. **Web Server Setup**
   - Point document root to `public/` directory
   - Configure URL rewriting to route all requests to `public/index.php`

4. **Testing**
   - Open your website in browser
   - Check that homepage loads
   - Verify CSS and JS assets are loading

## Default Admin Account
- Username: admin
- Email: admin@webshop-china.com  
- Password: admin123 (change immediately!)

## Need Help?
See README.md for detailed documentation.