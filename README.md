# WebShop China - PHP E-commerce Starter Project

一个现代化的PHP电商系统启动项目，采用PHP + MySQL + TailwindCSS + JavaScript技术栈构建，专为中国市场设计。

## 🚀 项目特色

- **现代化技术栈**: PHP 8+, MySQL 8.0, TailwindCSS 3.x, JavaScript ES6+
- **响应式设计**: 完全适配桌面端、平板和移动设备
- **模块化架构**: 清晰的MVC结构，易于扩展和维护
- **安全可靠**: 内置安全防护机制，包括CSRF保护、SQL注入防护
- **国际化支持**: 专为中国用户优化，支持中文界面和本地化功能
- **高性能**: 优化的数据库查询和前端资源加载
- **开发友好**: 完整的开发工具链和详细的文档

## 📁 项目结构

```
webshop-china/
├── README.md                 # 项目说明文档
├── package.json             # Node.js依赖和脚本
├── tailwind.config.js       # TailwindCSS配置
├── .gitignore              # Git忽略规则
├── config/                 # 配置文件目录
│   └── config.sample.php   # 配置文件模板
├── database/               # 数据库相关文件
│   └── schema.sql          # 数据库结构文件
├── public/                 # 公共访问目录
│   ├── index.php          # 应用入口文件
│   └── assets/            # 静态资源
│       ├── css/           # 样式文件
│       ├── js/            # JavaScript文件
│       └── images/        # 图片资源
└── src/                   # 源代码目录
    ├── controllers/       # 控制器
    ├── models/           # 数据模型
    └── helpers/          # 辅助类和函数
```

## 🛠️ 快速开始

### 环境要求

- **PHP**: 8.0 或更高版本
- **MySQL**: 8.0 或更高版本
- **Node.js**: 16.0 或更高版本
- **Composer**: 2.0 或更高版本（可选）

### 安装步骤

1. **克隆项目**
   ```bash
   git clone https://github.com/matavanary/webshop-china.git
   cd webshop-china
   ```

2. **安装前端依赖**
   ```bash
   npm install
   ```

3. **配置数据库**
   ```bash
   # 创建数据库
   mysql -u root -p -e "CREATE DATABASE webshop_china CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
   
   # 导入数据库结构
   mysql -u root -p webshop_china < database/schema.sql
   ```

4. **配置应用**
   ```bash
   # 复制配置文件
   cp config/config.sample.php config/config.php
   
   # 编辑配置文件，填入数据库连接信息
   nano config/config.php
   ```

5. **构建前端资源**
   ```bash
   npm run build
   ```

6. **启动开发服务器**
   ```bash
   npm run dev
   ```

7. **访问应用**
   
   打开浏览器访问 `http://localhost:8000`

### 默认账户

- **管理员账户**: admin@webshop-china.com / admin123

## 🎯 核心功能

### 用户功能
- [x] 用户注册和登录
- [x] 用户资料管理
- [x] 密码重置
- [x] 收藏夹管理
- [x] 订单历史查询

### 商品功能
- [x] 商品展示和分类
- [x] 商品搜索和筛选
- [x] 商品详情页面
- [x] 商品评价系统
- [x] 相关商品推荐

### 购物功能
- [x] 购物车管理
- [x] 订单创建和管理
- [x] 多种支付方式
- [x] 订单状态跟踪
- [x] 优惠券系统

### 管理功能
- [x] 商品管理
- [x] 订单管理
- [x] 用户管理
- [x] 系统设置
- [x] 数据统计

## 🔧 开发指南

### 数据库设计

系统包含以下核心数据表：

- **users**: 用户信息
- **products**: 商品信息
- **categories**: 商品分类
- **orders**: 订单信息
- **order_items**: 订单商品明细
- **payments**: 支付记录
- **shipments**: 物流信息
- **coupons**: 优惠券
- **cart**: 购物车
- **wishlists**: 收藏夹
- **product_reviews**: 商品评价
- **system_logs**: 系统日志

### API 接口

系统提供RESTful API接口：

```php
// 商品相关
GET /api/products              # 获取商品列表
GET /api/products/{id}         # 获取商品详情
GET /api/products/search       # 搜索商品
GET /api/products/featured     # 获取推荐商品

// 用户相关
POST /api/users/login          # 用户登录
POST /api/users/register       # 用户注册
GET /api/users/profile         # 获取用户资料
PUT /api/users/profile         # 更新用户资料

// 购物车
GET /api/cart                  # 获取购物车
POST /api/cart/add             # 添加到购物车
PUT /api/cart/update           # 更新购物车
DELETE /api/cart/remove        # 删除购物车商品

// 订单相关
POST /api/orders               # 创建订单
GET /api/orders                # 获取订单列表
GET /api/orders/{id}           # 获取订单详情
PUT /api/orders/{id}/status    # 更新订单状态
```

### 扩展开发

#### 添加新的支付方式

1. 在 `config/config.php` 中添加支付网关配置
2. 创建支付处理类 `src/helpers/PaymentGateway.php`
3. 实现支付接口方法
4. 在订单流程中集成新支付方式

#### 添加新的物流服务

1. 在配置文件中添加物流商配置
2. 创建物流服务类 `src/helpers/ShippingProvider.php`
3. 实现物流接口方法
4. 在发货流程中集成新物流服务

#### 自定义主题

1. 修改 `tailwind.config.js` 中的颜色和样式配置
2. 更新 `public/assets/css/style.css` 中的自定义样式
3. 修改模板文件中的HTML结构
4. 重新构建前端资源

## 🚀 部署指南

### 开发环境部署

使用内置PHP服务器：
```bash
npm run serve
```

### 生产环境部署

#### Apache 服务器

1. **配置虚拟主机**
   ```apache
   <VirtualHost *:80>
       ServerName webshop-china.com
       DocumentRoot /var/www/webshop-china/public
       
       <Directory /var/www/webshop-china/public>
           AllowOverride All
           Require all granted
       </Directory>
   </VirtualHost>
   ```

2. **设置文件权限**
   ```bash
   chown -R www-data:www-data /var/www/webshop-china
   chmod -R 755 /var/www/webshop-china
   chmod -R 777 /var/www/webshop-china/logs
   chmod -R 777 /var/www/webshop-china/public/assets/images/uploads
   ```

#### Nginx 服务器

```nginx
server {
    listen 80;
    server_name webshop-china.com;
    root /var/www/webshop-china/public;
    index index.php;
    
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }
    
    location ~ /\.ht {
        deny all;
    }
}
```

#### Docker 部署

```dockerfile
FROM php:8.1-apache

# Install dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo pdo_mysql mbstring exif pcntl bcmath gd

# Enable Apache mod_rewrite
RUN a2enmod rewrite

# Copy application code
COPY . /var/www/html

# Set permissions
RUN chown -R www-data:www-data /var/www/html
RUN chmod -R 755 /var/www/html

EXPOSE 80
```

### 环境配置

#### 生产环境配置

修改 `config/config.php`:

```php
'app' => [
    'debug' => false,
    'env' => 'production',
],

'database' => [
    'host' => 'your-production-db-host',
    'username' => 'your-production-db-user',
    'password' => 'your-production-db-password',
],

'security' => [
    'secret_key' => 'your-production-secret-key',
],
```

#### SSL 证书配置

使用 Let's Encrypt：
```bash
certbot --apache -d webshop-china.com
```

## 🔗 第三方集成

### 支付网关集成

#### 支付宝

```php
'payment' => [
    'gateways' => [
        'alipay' => [
            'app_id' => 'your-alipay-app-id',
            'private_key' => 'your-private-key',
            'public_key' => 'alipay-public-key',
            'sandbox' => false
        ]
    ]
]
```

#### 微信支付

```php
'wechat' => [
    'app_id' => 'your-wechat-app-id',
    'mch_id' => 'your-merchant-id',
    'key' => 'your-wechat-key',
    'sandbox' => false
]
```

### 物流服务集成

#### 顺丰快递

```php
'shipping' => [
    'carriers' => [
        'sf_express' => [
            'api_key' => 'your-sf-api-key',
            'api_secret' => 'your-sf-secret'
        ]
    ]
]
```

#### 中国邮政EMS

```php
'ems' => [
    'api_key' => 'your-ems-api-key'
]
```

### 短信服务集成

#### 阿里云短信

```php
'sms' => [
    'provider' => 'aliyun',
    'access_key' => 'your-access-key',
    'access_secret' => 'your-access-secret',
    'sign_name' => 'your-sms-signature'
]
```

## 📊 性能优化

### 数据库优化

1. **索引优化**
   ```sql
   CREATE INDEX idx_products_category ON products(category_id);
   CREATE INDEX idx_products_active ON products(is_active);
   CREATE INDEX idx_orders_user ON orders(user_id);
   ```

2. **查询优化**
   - 使用预编译语句
   - 避免N+1查询问题
   - 合理使用JOIN查询

### 缓存策略

1. **Redis 缓存**
   ```php
   'cache' => [
       'driver' => 'redis',
       'redis' => [
           'host' => '127.0.0.1',
           'port' => 6379,
           'database' => 0
       ]
   ]
   ```

2. **文件缓存**
   ```php
   'cache' => [
       'driver' => 'file',
       'file_path' => '/tmp/cache/'
   ]
   ```

### 前端优化

1. **资源压缩**
   ```bash
   npm run build  # 自动压缩CSS和JS
   ```

2. **图片优化**
   - 使用WebP格式
   - 实现图片懒加载
   - 设置合适的图片尺寸

## 🔒 安全措施

### 数据安全

- 所有用户输入都经过过滤和验证
- 使用预编译SQL语句防止SQL注入
- 密码使用bcrypt加密存储
- 实现CSRF令牌保护

### 访问控制

- 基于角色的权限控制
- 会话安全管理
- API访问频率限制
- 文件上传安全检查

### 数据备份

```bash
# 定期备份数据库
mysqldump -u username -p webshop_china > backup_$(date +%Y%m%d).sql

# 备份上传文件
tar -czf uploads_backup_$(date +%Y%m%d).tar.gz public/assets/images/uploads/
```

## 🧪 测试

### 单元测试

```bash
# 运行测试（待实现）
npm run test
```

### 性能测试

使用Apache Bench进行性能测试：
```bash
ab -n 1000 -c 10 http://localhost:8000/
```

## 📚 扩展功能

### 计划中的功能

- [ ] 多语言支持
- [ ] 移动端APP API
- [ ] 商家入驻系统
- [ ] 积分和等级系统
- [ ] 社交登录集成
- [ ] 商品推荐算法
- [ ] 实时聊天客服
- [ ] 数据分析仪表板

### 插件系统

系统支持插件扩展，可以方便地添加新功能：

```php
// 创建插件
class MyPlugin {
    public function init() {
        // 插件初始化代码
    }
    
    public function activate() {
        // 插件激活代码
    }
}
```

## 🤝 贡献指南

欢迎提交问题和功能请求！

1. Fork 项目
2. 创建功能分支 (`git checkout -b feature/AmazingFeature`)
3. 提交更改 (`git commit -m 'Add some AmazingFeature'`)
4. 推送到分支 (`git push origin feature/AmazingFeature`)
5. 创建 Pull Request

## 📝 许可证

本项目基于 MIT 许可证开源，详情请查看 [LICENSE](LICENSE) 文件。

## 💬 支持与帮助

- **文档**: 查看项目Wiki获取详细文档
- **问题反馈**: 通过GitHub Issues报告问题
- **讨论交流**: 加入项目讨论组
- **邮件支持**: info@webshop-china.com

## 🎉 鸣谢

感谢以下开源项目：

- [TailwindCSS](https://tailwindcss.com/) - 实用优先的CSS框架
- [PHP](https://php.net/) - 流行的服务器端脚本语言
- [MySQL](https://mysql.com/) - 世界上最流行的开源数据库

---

**WebShop China** - 让电商开发更简单！ 🚀