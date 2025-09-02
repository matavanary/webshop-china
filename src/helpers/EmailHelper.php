<?php
/**
 * Email Helper Class
 * Handles email sending functionality
 */

class EmailHelper {
    private $config;

    public function __construct() {
        $this->config = require __DIR__ . '/../../config/config.php';
    }

    /**
     * Send email using configured SMTP settings
     */
    public function send($to, $subject, $body, $isHtml = true) {
        $emailConfig = $this->config['email'];
        
        // Simple mail function implementation
        // In production, consider using PHPMailer or similar library
        
        $headers = [
            'From: ' . $emailConfig['from']['name'] . ' <' . $emailConfig['from']['address'] . '>',
            'Reply-To: ' . $emailConfig['reply_to']['address'],
        ];
        
        if ($isHtml) {
            $headers[] = 'Content-Type: text/html; charset=UTF-8';
            $headers[] = 'MIME-Version: 1.0';
        } else {
            $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        }
        
        $headerString = implode("\r\n", $headers);
        
        try {
            $result = mail($to, $subject, $body, $headerString);
            
            if ($result) {
                Utils::log('info', 'Email sent successfully', [
                    'to' => $to,
                    'subject' => $subject
                ]);
                return true;
            } else {
                Utils::log('error', 'Failed to send email', [
                    'to' => $to,
                    'subject' => $subject
                ]);
                return false;
            }
        } catch (Exception $e) {
            Utils::log('error', 'Email sending error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send welcome email to new user
     */
    public function sendWelcomeEmail($user) {
        $subject = 'ยินดีต้อนรับสู่ Chinese E-commerce Website';
        $body = $this->getWelcomeEmailTemplate($user);
        return $this->send($user['email'], $subject, $body);
    }

    /**
     * Send order confirmation email
     */
    public function sendOrderConfirmation($user, $order) {
        $subject = 'ยืนยันการสั่งซื้อ - คำสั่งซื้อ #' . $order['order_number'];
        $body = $this->getOrderConfirmationTemplate($user, $order);
        return $this->send($user['email'], $subject, $body);
    }

    /**
     * Send password reset email
     */
    public function sendPasswordReset($user, $resetToken) {
        $subject = 'รีเซ็ตรหัสผ่าน - Chinese E-commerce Website';
        $body = $this->getPasswordResetTemplate($user, $resetToken);
        return $this->send($user['email'], $subject, $body);
    }

    /**
     * Get welcome email template
     */
    private function getWelcomeEmailTemplate($user) {
        return "
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: #3b82f6; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>ยินดีต้อนรับสู่ Chinese E-commerce Website</h1>
            </div>
            <div class='content'>
                <h2>สวัสดีคุณ {$user['first_name']} {$user['last_name']}</h2>
                <p>ขอบคุณที่เข้าร่วมกับเรา! บัญชีของคุณได้ถูกสร้างเรียบร้อยแล้ว</p>
                <p><strong>ข้อมูลบัญชี:</strong></p>
                <ul>
                    <li>ชื่อผู้ใช้: {$user['username']}</li>
                    <li>อีเมล: {$user['email']}</li>
                </ul>
                <p>คุณสามารถเริ่มช้อปปิ้งสินค้าจากจีนคุณภาพดีได้ทันที</p>
                <p><a href='{$this->config['app']['base_url']}' style='background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>เริ่มช้อปปิ้ง</a></p>
            </div>
            <div class='footer'>
                <p>&copy; 2024 Chinese E-commerce Website. All rights reserved.</p>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Get order confirmation email template
     */
    private function getOrderConfirmationTemplate($user, $order) {
        return "
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: #dc2626; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .order-details { background: #f8f9fa; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>ยืนยันการสั่งซื้อ</h1>
            </div>
            <div class='content'>
                <h2>สวัสดีคุณ {$user['first_name']} {$user['last_name']}</h2>
                <p>ขอบคุณสำหรับการสั่งซื้อ! เราได้รับคำสั่งซื้อของคุณแล้ว</p>
                
                <div class='order-details'>
                    <h3>รายละเอียดคำสั่งซื้อ</h3>
                    <p><strong>หมายเลขคำสั่งซื้อ:</strong> {$order['order_number']}</p>
                    <p><strong>วันที่สั่งซื้อ:</strong> {$order['created_at']}</p>
                    <p><strong>ยอดรวม:</strong> ฿" . number_format($order['total_amount'], 2) . "</p>
                    <p><strong>สถานะ:</strong> {$order['status']}</p>
                </div>
                
                <p>เราจะแจ้งให้คุณทราบเมื่อสินค้าของคุณถูกจัดส่ง</p>
                <p><a href='{$this->config['app']['base_url']}/orders/{$order['id']}' style='background: #dc2626; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>ดูรายละเอียดคำสั่งซื้อ</a></p>
            </div>
            <div class='footer'>
                <p>&copy; 2024 Chinese E-commerce Website. All rights reserved.</p>
            </div>
        </body>
        </html>
        ";
    }

    /**
     * Get password reset email template
     */
    private function getPasswordResetTemplate($user, $resetToken) {
        $resetUrl = $this->config['app']['base_url'] . '/reset-password?token=' . $resetToken;
        
        return "
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .header { background: #3b82f6; color: white; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .warning { background: #fef2f2; border: 1px solid #fecaca; padding: 15px; border-radius: 5px; margin: 15px 0; }
                .footer { background: #f8f9fa; padding: 20px; text-align: center; font-size: 12px; color: #666; }
            </style>
        </head>
        <body>
            <div class='header'>
                <h1>รีเซ็ตรหัสผ่าน</h1>
            </div>
            <div class='content'>
                <h2>สวัสดีคุณ {$user['first_name']} {$user['last_name']}</h2>
                <p>เราได้รับคำขอรีเซ็ตรหัสผ่านสำหรับบัญชีของคุณ</p>
                
                <p>กรุณาคลิกปุ่มด้านล่างเพื่อรีเซ็ตรหัสผ่าน:</p>
                <p><a href='{$resetUrl}' style='background: #3b82f6; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;'>รีเซ็ตรหัสผ่าน</a></p>
                
                <div class='warning'>
                    <strong>หมายเหตุ:</strong> ลิงก์นี้จะใช้ได้เพียง 24 ชั่วโมง หากคุณไม่ได้ขอรีเซ็ตรหัสผ่าน กรุณาละเว้นอีเมลนี้
                </div>
                
                <p>หากปุ่มไม่ทำงาน กรุณาคัดลอกลิงก์นี้ไปวางในเบราว์เซอร์:</p>
                <p style='word-break: break-all; color: #3b82f6;'>{$resetUrl}</p>
            </div>
            <div class='footer'>
                <p>&copy; 2024 Chinese E-commerce Website. All rights reserved.</p>
            </div>
        </body>
        </html>
        ";
    }
}