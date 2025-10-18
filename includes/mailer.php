<?php
// includes/mailer.php - Functions gửi email

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Cần cài đặt PHPMailer: composer require phpmailer/phpmailer

require_once __DIR__ . '/../vendor/autoload.php';

class Mailer {
    private $mail;
    
    public function __construct() {
        $this->mail = new PHPMailer(true);
        $this->configure();
    }
    
    // Cấu hình SMTP
    private function configure() {
        try {
            // Server settings
            $this->mail->isSMTP();
            $this->mail->Host = SMTP_HOST;
            $this->mail->SMTPAuth = true;
            $this->mail->Username = SMTP_USER;
            $this->mail->Password = SMTP_PASS;
            $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            $this->mail->Port = SMTP_PORT;
            $this->mail->CharSet = 'UTF-8';
            
            // Sender
            $this->mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
            
        } catch (Exception $e) {
            error_log("Mailer configuration error: " . $e->getMessage());
        }
    }
    
    // Gửi email chào mừng khi đăng ký
    public function sendWelcomeEmail($toEmail, $toName, $couponCode) {
        try {
            $this->mail->addAddress($toEmail, $toName);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Chào mừng đến với Diệp Anh Computer';
            
            $body = $this->getWelcomeEmailTemplate($toName, $couponCode);
            $this->mail->Body = $body;
            
            $this->mail->send();
            return ['success' => true, 'message' => 'Email đã được gửi'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $this->mail->ErrorInfo];
        }
    }
    
    // Gửi email xác nhận đơn hàng
    public function sendOrderConfirmation($orderData) {
        try {
            $this->mail->addAddress($orderData['customer_email'], $orderData['customer_name']);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Xác nhận đơn hàng #' . $orderData['order_code'];
            
            $body = $this->getOrderConfirmationTemplate($orderData);
            $this->mail->Body = $body;
            
            $this->mail->send();
            return ['success' => true, 'message' => 'Email xác nhận đã được gửi'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $this->mail->ErrorInfo];
        }
    }
    
    // Gửi email cập nhật trạng thái đơn hàng
    public function sendOrderStatusUpdate($orderData, $newStatus) {
        try {
            $this->mail->addAddress($orderData['customer_email'], $orderData['customer_name']);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Cập nhật đơn hàng #' . $orderData['order_code'];
            
            $body = $this->getOrderStatusUpdateTemplate($orderData, $newStatus);
            $this->mail->Body = $body;
            
            $this->mail->send();
            return ['success' => true, 'message' => 'Email cập nhật đã được gửi'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $this->mail->ErrorInfo];
        }
    }
    
    // Gửi email hóa đơn
    public function sendInvoice($orderData) {
        try {
            $this->mail->addAddress($orderData['customer_email'], $orderData['customer_name']);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Hóa đơn đơn hàng #' . $orderData['order_code'];
            
            $body = $this->getInvoiceTemplate($orderData);
            $this->mail->Body = $body;
            
            $this->mail->send();
            return ['success' => true, 'message' => 'Hóa đơn đã được gửi'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $this->mail->ErrorInfo];
        }
    }
    
    // Gửi email liên hệ
    public function sendContactReply($contactData, $replyMessage) {
        try {
            $this->mail->addAddress($contactData['email'], $contactData['name']);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Re: ' . $contactData['subject'];
            
            $body = $this->getContactReplyTemplate($contactData, $replyMessage);
            $this->mail->Body = $body;
            
            $this->mail->send();
            return ['success' => true, 'message' => 'Email phản hồi đã được gửi'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => $this->mail->ErrorInfo];
        }
    }
    
    // Template email chào mừng
    private function getWelcomeEmailTemplate($name, $couponCode) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
                .content { background: #f9fafb; padding: 30px; }
                .coupon { background: #fef3c7; border: 2px dashed #f59e0b; padding: 20px; margin: 20px 0; text-align: center; }
                .coupon-code { font-size: 24px; font-weight: bold; color: #dc2626; letter-spacing: 2px; }
                .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Chào mừng đến với Diệp Anh Computer!</h1>
                </div>
                <div class='content'>
                    <h2>Xin chào {$name},</h2>
                    <p>Cảm ơn bạn đã đăng ký tài khoản tại <strong>Diệp Anh Computer</strong>!</p>
                    <p>Chúng tôi rất vui khi bạn đã tin tưởng và lựa chọn chúng tôi.</p>
                    
                    <div class='coupon'>
                        <h3>🎁 Quà tặng chào mừng</h3>
                        <p>Mã giảm giá <strong>10%</strong> cho đơn hàng đầu tiên:</p>
                        <div class='coupon-code'>{$couponCode}</div>
                        <p style='margin-top: 10px; font-size: 14px;'>Áp dụng cho đơn hàng từ 500.000đ</p>
                        <p style='font-size: 14px;'>Có hiệu lực trong 30 ngày</p>
                    </div>
                    
                    <p>Hãy khám phá các sản phẩm máy tính chất lượng cao với giá cả hợp lý tại cửa hàng của chúng tôi!</p>
                    
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . SITE_URL . "' style='background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Mua sắm ngay</a>
                    </div>
                </div>
                <div class='footer'>
                    <p>Trân trọng,<br><strong>Diệp Anh Computer</strong></p>
                    <p>Email: " . ADMIN_EMAIL . " | Website: " . SITE_URL . "</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    // Template email xác nhận đơn hàng
    private function getOrderConfirmationTemplate($order) {
        $itemsHtml = '';
        foreach ($order['details'] as $item) {
            $itemsHtml .= "
                <tr>
                    <td style='padding: 10px; border-bottom: 1px solid #e5e7eb;'>{$item['product_name']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: center;'>{$item['quantity']}</td>
                    <td style='padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: right;'>" . number_format($item['product_price']) . "đ</td>
                    <td style='padding: 10px; border-bottom: 1px solid #e5e7eb; text-align: right;'>" . number_format($item['total']) . "đ</td>
                </tr>
            ";
        }
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #10b981; color: white; padding: 20px; text-align: center; }
                .content { background: #f9fafb; padding: 30px; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; background: white; }
                th { background: #f3f4f6; padding: 12px; text-align: left; }
                .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
                .info-box { background: white; padding: 20px; margin: 20px 0; border-left: 4px solid #2563eb; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>✅ Đơn hàng đã được xác nhận</h1>
                </div>
                <div class='content'>
                    <h2>Xin chào {$order['customer_name']},</h2>
                    <p>Cảm ơn bạn đã đặt hàng tại <strong>Diệp Anh Computer</strong>!</p>
                    <p>Đơn hàng của bạn đã được tiếp nhận và đang được xử lý.</p>
                    
                    <div class='info-box'>
                        <p><strong>Mã đơn hàng:</strong> {$order['order_code']}</p>
                        <p><strong>Ngày đặt:</strong> " . date('d/m/Y H:i', strtotime($order['created_at'])) . "</p>
                        <p><strong>Phương thức thanh toán:</strong> " . ($order['payment_method'] == 'vnpay' ? 'VNPay' : 'COD') . "</p>
                    </div>
                    
                    <h3>Chi tiết đơn hàng:</h3>
                    <table>
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th style='text-align: center;'>Số lượng</th>
                                <th style='text-align: right;'>Đơn giá</th>
                                <th style='text-align: right;'>Thành tiền</th>
                            </tr>
                        </thead>
                        <tbody>
                            {$itemsHtml}
                            <tr>
                                <td colspan='3' style='padding: 10px; text-align: right;'><strong>Tạm tính:</strong></td>
                                <td style='padding: 10px; text-align: right;'><strong>" . number_format($order['subtotal']) . "đ</strong></td>
                            </tr>
                            " . ($order['coupon_discount'] > 0 ? "
                            <tr>
                                <td colspan='3' style='padding: 10px; text-align: right;'>Giảm giá:</td>
                                <td style='padding: 10px; text-align: right; color: #dc2626;'>-" . number_format($order['coupon_discount']) . "đ</td>
                            </tr>
                            " : "") . "
                            <tr style='background: #fef3c7;'>
                                <td colspan='3' style='padding: 15px; text-align: right;'><strong>TỔNG CỘNG:</strong></td>
                                <td style='padding: 15px; text-align: right;'><strong style='font-size: 18px; color: #dc2626;'>" . number_format($order['total']) . "đ</strong></td>
                            </tr>
                        </tbody>
                    </table>
                    
                    <div class='info-box'>
                        <h4>Thông tin giao hàng:</h4>
                        <p><strong>Người nhận:</strong> {$order['customer_name']}</p>
                        <p><strong>Số điện thoại:</strong> {$order['customer_phone']}</p>
                        <p><strong>Địa chỉ:</strong> {$order['customer_address']}</p>
                    </div>
                    
                    <p>Chúng tôi sẽ liên hệ với bạn để xác nhận và giao hàng trong thời gian sớm nhất.</p>
                </div>
                <div class='footer'>
                    <p>Trân trọng,<br><strong>Diệp Anh Computer</strong></p>
                    <p>Email: " . ADMIN_EMAIL . " | Website: " . SITE_URL . "</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    // Template email cập nhật trạng thái
    private function getOrderStatusUpdateTemplate($order, $status) {
        $statusMessages = [
            'confirmed' => ['title' => 'Đơn hàng đã được xác nhận', 'message' => 'Đơn hàng của bạn đã được xác nhận và đang được chuẩn bị.', 'icon' => '✅'],
            'processing' => ['title' => 'Đơn hàng đang được xử lý', 'message' => 'Chúng tôi đang đóng gói sản phẩm của bạn.', 'icon' => '📦'],
            'shipping' => ['title' => 'Đơn hàng đang được giao', 'message' => 'Đơn hàng của bạn đang trên đường giao đến bạn.', 'icon' => '🚚'],
            'delivered' => ['title' => 'Đơn hàng đã giao thành công', 'message' => 'Đơn hàng đã được giao thành công. Cảm ơn bạn đã mua hàng!', 'icon' => '✨'],
            'cancelled' => ['title' => 'Đơn hàng đã bị hủy', 'message' => 'Đơn hàng của bạn đã bị hủy.', 'icon' => '❌']
        ];
        
        $statusInfo = $statusMessages[$status] ?? ['title' => 'Cập nhật đơn hàng', 'message' => '', 'icon' => '📋'];
        
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
                .content { background: #f9fafb; padding: 30px; }
                .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>{$statusInfo['icon']} {$statusInfo['title']}</h1>
                </div>
                <div class='content'>
                    <h2>Xin chào {$order['customer_name']},</h2>
                    <p>{$statusInfo['message']}</p>
                    <p><strong>Mã đơn hàng:</strong> {$order['order_code']}</p>
                    <div style='text-align: center; margin: 30px 0;'>
                        <a href='" . SITE_URL . "/customer/order-detail.php?code={$order['order_code']}' style='background: #2563eb; color: white; padding: 12px 30px; text-decoration: none; border-radius: 5px; display: inline-block;'>Xem chi tiết đơn hàng</a>
                    </div>
                </div>
                <div class='footer'>
                    <p>Trân trọng,<br><strong>Diệp Anh Computer</strong></p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
    
    // Template hóa đơn
    private function getInvoiceTemplate($order) {
        // Tương tự như OrderConfirmationTemplate nhưng có thêm thông tin chi tiết hơn
        return $this->getOrderConfirmationTemplate($order);
    }
    
    // Template phản hồi liên hệ
    private function getContactReplyTemplate($contact, $replyMessage) {
        return "
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset='UTF-8'>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background: #2563eb; color: white; padding: 20px; text-align: center; }
                .content { background: #f9fafb; padding: 30px; }
                .footer { text-align: center; padding: 20px; color: #6b7280; font-size: 14px; }
                .quote { background: white; border-left: 4px solid #e5e7eb; padding: 15px; margin: 20px 0; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h1>Phản hồi từ Diệp Anh Computer</h1>
                </div>
                <div class='content'>
                    <h2>Xin chào {$contact['name']},</h2>
                    <p>Cảm ơn bạn đã liên hệ với chúng tôi. Dưới đây là phản hồi của chúng tôi:</p>
                    
                    <div style='background: white; padding: 20px; margin: 20px 0;'>
                        {$replyMessage}
                    </div>
                    
                    <div class='quote'>
                        <p><strong>Tin nhắn gốc của bạn:</strong></p>
                        <p>{$contact['message']}</p>
                    </div>
                    
                    <p>Nếu bạn còn bất kỳ thắc mắc nào, vui lòng liên hệ lại với chúng tôi.</p>
                </div>
                <div class='footer'>
                    <p>Trân trọng,<br><strong>Diệp Anh Computer</strong></p>
                    <p>Email: " . ADMIN_EMAIL . " | Website: " . SITE_URL . "</p>
                </div>
            </div>
        </body>
        </html>
        ";
    }
}
?>