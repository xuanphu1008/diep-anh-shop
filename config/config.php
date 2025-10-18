<?php
// config/config.php - Cấu hình cơ bản

// Cấu hình database
define('DB_HOST', 'localhost');
define('DB_NAME', 'diep_anh_shop');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

// Cấu hình website
define('SITE_NAME', 'Diệp Anh Computer');
define('SITE_URL', 'http://localhost/diep-anh-shop');
define('ADMIN_EMAIL', 'admin@diepanhshop.com');

// Cấu hình phiên làm việc
define('SESSION_TIMEOUT', 3600); // 1 giờ

// Cấu hình upload
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Cấu hình VNPay
define('VNPAY_TMN_CODE', 'YOUR_TMN_CODE'); // Mã website của bạn tại VNPay
define('VNPAY_HASH_SECRET', 'YOUR_HASH_SECRET'); // Chuỗi bí mật
define('VNPAY_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html'); // URL thanh toán
define('VNPAY_RETURN_URL', SITE_URL . '/payment/vnpay_return.php'); // URL trả về

// Cấu hình email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_app_password');
define('SMTP_FROM_EMAIL', 'noreply@diepanhshop.com');
define('SMTP_FROM_NAME', 'Diệp Anh Computer');

// Timezone
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Error reporting (tắt trong production)
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Session
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>