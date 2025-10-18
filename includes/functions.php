<?php
// includes/functions.php - Helper functions

// Kiểm tra đăng nhập
function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

// Kiểm tra quyền admin
function isAdmin() {
    return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

// Kiểm tra quyền staff hoặc admin
function isStaff() {
    return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'staff']);
}

// Redirect
function redirect($url) {
    header("Location: $url");
    exit();
}

// Flash message
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message
    ];
}

function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

// Sanitize input
function sanitizeInput($data) {
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data);
    return $data;
}

// Validate email
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Validate phone
function validatePhone($phone) {
    return preg_match('/^[0-9]{10,11}$/', $phone);
}

// Upload file
function uploadFile($file, $targetDir = 'uploads/products/') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Không có file được upload'];
    }
    
    // Kiểm tra kích thước
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File quá lớn. Tối đa 5MB'];
    }
    
    // Kiểm tra extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Định dạng file không được phép'];
    }
    
    // Tạo tên file mới
    $newFileName = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = UPLOAD_PATH . $targetDir;
    
    // Tạo thư mục nếu chưa tồn tại
    if (!is_dir($targetPath)) {
        mkdir($targetPath, 0755, true);
    }
    
    $targetFile = $targetPath . $newFileName;
    
    // Di chuyển file
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return [
            'success' => true,
            'filename' => $targetDir . $newFileName,
            'path' => $targetFile
        ];
    }
    
    return ['success' => false, 'message' => 'Upload file thất bại'];
}

// Upload multiple files
function uploadMultipleFiles($files, $targetDir = 'uploads/products/') {
    $uploadedFiles = [];
    $errors = [];
    
    for ($i = 0; $i < count($files['name']); $i++) {
        $file = [
            'name' => $files['name'][$i],
            'type' => $files['type'][$i],
            'tmp_name' => $files['tmp_name'][$i],
            'error' => $files['error'][$i],
            'size' => $files['size'][$i]
        ];
        
        $result = uploadFile($file, $targetDir);
        
        if ($result['success']) {
            $uploadedFiles[] = $result['filename'];
        } else {
            $errors[] = $result['message'];
        }
    }
    
    return [
        'success' => count($uploadedFiles) > 0,
        'files' => $uploadedFiles,
        'errors' => $errors
    ];
}

// Delete file
function deleteFile($filename) {
    $filepath = UPLOAD_PATH . $filename;
    if (file_exists($filepath)) {
        return unlink($filepath);
    }
    return false;
}

// Format currency
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . 'đ';
}

// Format date
function formatDate($date, $format = 'd/m/Y H:i') {
    return date($format, strtotime($date));
}

// Time ago
function timeAgo($datetime) {
    $timestamp = strtotime($datetime);
    $difference = time() - $timestamp;
    
    $periods = [
        'năm' => 31536000,
        'tháng' => 2592000,
        'tuần' => 604800,
        'ngày' => 86400,
        'giờ' => 3600,
        'phút' => 60,
        'giây' => 1
    ];
    
    foreach ($periods as $key => $value) {
        $result = floor($difference / $value);
        if ($result >= 1) {
            return $result . ' ' . $key . ' trước';
        }
    }
    
    return 'Vừa xong';
}

// Pagination
function paginate($totalItems, $itemsPerPage, $currentPage) {
    $totalPages = ceil($totalItems / $itemsPerPage);
    $currentPage = max(1, min($currentPage, $totalPages));
    $offset = ($currentPage - 1) * $itemsPerPage;
    
    return [
        'total_items' => $totalItems,
        'items_per_page' => $itemsPerPage,
        'current_page' => $currentPage,
        'total_pages' => $totalPages,
        'offset' => $offset,
        'has_prev' => $currentPage > 1,
        'has_next' => $currentPage < $totalPages
    ];
}

// Generate random string
function generateRandomString($length = 10) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $randomString = '';
    
    for ($i = 0; $i < $length; $i++) {
        $randomString .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $randomString;
}

// CSRF Token
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

// Get client IP
function getClientIP() {
    $ipaddress = '';
    
    if (isset($_SERVER['HTTP_CLIENT_IP'])) {
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_X_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    } else if (isset($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    } else if (isset($_SERVER['HTTP_FORWARDED'])) {
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    } else if (isset($_SERVER['REMOTE_ADDR'])) {
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    } else {
        $ipaddress = 'UNKNOWN';
    }
    
    return $ipaddress;
}

// Truncate text
function truncateText($text, $length = 100, $suffix = '...') {
    if (mb_strlen($text) > $length) {
        return mb_substr($text, 0, $length) . $suffix;
    }
    return $text;
}

// Get order status text
function getOrderStatusText($status) {
    $statuses = [
        'pending' => 'Chờ xác nhận',
        'confirmed' => 'Đã xác nhận',
        'processing' => 'Đang xử lý',
        'shipping' => 'Đang giao hàng',
        'delivered' => 'Đã giao hàng',
        'cancelled' => 'Đã hủy'
    ];
    
    return $statuses[$status] ?? $status;
}

// Get order status class
function getOrderStatusClass($status) {
    $classes = [
        'pending' => 'badge-warning',
        'confirmed' => 'badge-info',
        'processing' => 'badge-primary',
        'shipping' => 'badge-primary',
        'delivered' => 'badge-success',
        'cancelled' => 'badge-danger'
    ];
    
    return $classes[$status] ?? 'badge-secondary';
}

// Get payment status text
function getPaymentStatusText($status) {
    $statuses = [
        'pending' => 'Chờ thanh toán',
        'paid' => 'Đã thanh toán',
        'failed' => 'Thanh toán thất bại'
    ];
    
    return $statuses[$status] ?? $status;
}

// Get payment method text
function getPaymentMethodText($method) {
    $methods = [
        'cod' => 'Thanh toán khi nhận hàng',
        'vnpay' => 'Thanh toán VNPay'
    ];
    
    return $methods[$method] ?? $method;
}

// Check if product is in stock
function isInStock($quantity) {
    return $quantity > 0;
}

// Calculate discount percentage
function calculateDiscountPercent($originalPrice, $discountPrice) {
    if ($originalPrice <= 0) return 0;
    return round((($originalPrice - $discountPrice) / $originalPrice) * 100);
}

// Get final price (considering discount)
function getFinalPrice($price, $discountPrice = null) {
    if ($discountPrice && $discountPrice > 0) {
        return $discountPrice;
    }
    return $price;
}

// Format product specifications (JSON to HTML)
function formatSpecifications($specsJson) {
    if (!$specsJson) return '';
    
    $specs = json_decode($specsJson, true);
    if (!$specs) return '';
    
    $html = '<ul class="specifications-list">';
    foreach ($specs as $key => $value) {
        $html .= '<li><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars($value) . '</li>';
    }
    $html .= '</ul>';
    
    return $html;
}

// Debug function
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

// Log activity
function logActivity($userId, $action, $details = '') {
    $db = new Database();
    $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address, created_at) 
            VALUES (?, ?, ?, ?, NOW())";
    
    return $db->query($sql, [$userId, $action, $details, getClientIP()]);
}

// Check permission
function hasPermission($requiredRole) {
    if (!isLoggedIn()) {
        return false;
    }
    
    $userRole = $_SESSION['role'] ?? 'customer';
    
    if ($requiredRole === 'admin') {
        return $userRole === 'admin';
    }
    
    if ($requiredRole === 'staff') {
        return in_array($userRole, ['admin', 'staff']);
    }
    
    return true;
}

// Require login
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Vui lòng đăng nhập để tiếp tục');
        redirect(SITE_URL . '/customer/login.php');
    }
}

// Require admin
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setFlashMessage('error', 'Bạn không có quyền truy cập');
        redirect(SITE_URL . '/index.php');
    }
}

// Require staff
function requireStaff() {
    requireLogin();
    if (!isStaff()) {
        setFlashMessage('error', 'Bạn không có quyền truy cập');
        redirect(SITE_URL . '/index.php');
    }
}

// Get cart count
function getCartCount() {
    if (isLoggedIn()) {
        require_once __DIR__ . '/../models/Cart.php';
        $cart = new Cart();
        return $cart->countCartItems($_SESSION['user_id']);
    } else {
        $cart = $_SESSION['cart'] ?? [];
        return array_sum($cart);
    }
}

// Render stars rating
function renderStars($rating, $maxStars = 5) {
    $html = '<div class="stars-rating">';
    
    for ($i = 1; $i <= $maxStars; $i++) {
        if ($i <= floor($rating)) {
            $html .= '<i class="fas fa-star"></i>';
        } elseif ($i - 0.5 <= $rating) {
            $html .= '<i class="fas fa-star-half-alt"></i>';
        } else {
            $html .= '<i class="far fa-star"></i>';
        }
    }
    
    $html .= '</div>';
    return $html;
}

// Generate breadcrumb
function renderBreadcrumb($items) {
    $html = '<nav class="breadcrumb">';
    $html .= '<a href="' . SITE_URL . '">Trang chủ</a>';
    
    $lastIndex = count($items) - 1;
    foreach ($items as $index => $item) {
        if ($index === $lastIndex) {
            $html .= '<span class="separator">/</span>';
            $html .= '<span class="current">' . htmlspecialchars($item['text']) . '</span>';
        } else {
            $html .= '<span class="separator">/</span>';
            $html .= '<a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['text']) . '</a>';
        }
    }
    
    $html .= '</nav>';
    return $html;
}

// Meta tags for SEO
function renderMetaTags($title, $description = '', $keywords = '', $image = '') {
    $siteUrl = SITE_URL;
    $siteName = SITE_NAME;
    
    echo "<title>$title</title>\n";
    echo "<meta name='description' content='$description'>\n";
    echo "<meta name='keywords' content='$keywords'>\n";
    
    // Open Graph
    echo "<meta property='og:title' content='$title'>\n";
    echo "<meta property='og:description' content='$description'>\n";
    echo "<meta property='og:type' content='website'>\n";
    echo "<meta property='og:url' content='$siteUrl'>\n";
    echo "<meta property='og:site_name' content='$siteName'>\n";
    
    if ($image) {
        echo "<meta property='og:image' content='$image'>\n";
    }
    
    // Twitter Card
    echo "<meta name='twitter:card' content='summary_large_image'>\n";
    echo "<meta name='twitter:title' content='$title'>\n";
    echo "<meta name='twitter:description' content='$description'>\n";
    
    if ($image) {
        echo "<meta name='twitter:image' content='$image'>\n";
    }
}

// Send notification email to admin
function sendAdminNotification($subject, $message) {
    require_once __DIR__ . '/mailer.php';
    $mailer = new Mailer();
    
    try {
        $mailer->mail->addAddress(ADMIN_EMAIL);
        $mailer->mail->Subject = '[Diệp Anh] ' . $subject;
        $mailer->mail->Body = $message;
        $mailer->mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Admin notification error: " . $e->getMessage());
        return false;
    }
}

// Clean old sessions
function cleanOldSessions() {
    $sessionPath = session_save_path();
    if (empty($sessionPath)) {
        $sessionPath = sys_get_temp_dir();
    }
    
    $files = glob($sessionPath . '/sess_*');
    if ($files === false) return;
    
    $now = time();
    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= SESSION_TIMEOUT) {
                @unlink($file);
            }
        }
    }
}

// Validate Vietnamese phone number
function isValidVietnamesePhone($phone) {
    // Remove all non-numeric characters
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Check length (10 or 11 digits)
    if (strlen($phone) < 10 || strlen($phone) > 11) {
        return false;
    }
    
    // Check if starts with valid prefixes
    $validPrefixes = ['03', '05', '07', '08', '09', '01'];
    $prefix = substr($phone, 0, 2);
    
    return in_array($prefix, $validPrefixes);
}

// Generate QR Code for order
function generateOrderQR($orderCode) {
    // Sử dụng API tạo QR code miễn phí
    $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=200x200&data=" . urlencode($orderCode);
    return $qrUrl;
}

// Check if user agent is mobile
function isMobile() {
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}

// Get user's cart total
function getCartTotal($userId = null) {
    require_once __DIR__ . '/../models/Cart.php';
    $cart = new Cart();
    
    if ($userId) {
        return $cart->calculateCartTotal($userId);
    } else {
        return 0;
    }
}
?>