<?php
// includes/functions.php - Helper functions (UPGRADED VERSION)

// ============================================
// AUTHENTICATION & AUTHORIZATION
// ============================================

/**
 * Kiểm tra người dùng đã đăng nhập chưa
 */
function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

/**
 * Kiểm tra quyền admin
 */
function isAdmin() {
    return isLoggedIn() && isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
}

/**
 * Kiểm tra quyền staff hoặc admin
 */
function isStaff() {
    return isLoggedIn() && isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'staff']);
}

/**
 * Yêu cầu đăng nhập
 */
function requireLogin() {
    if (!isLoggedIn()) {
        setFlashMessage('error', 'Vui lòng đăng nhập để tiếp tục');
        redirect(SITE_URL . '/customer/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    }
}

/**
 * Yêu cầu quyền admin
 */
function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        setFlashMessage('error', 'Bạn không có quyền truy cập trang này');
        redirect(SITE_URL . '/index.php');
    }
}

/**
 * Yêu cầu quyền staff
 */
function requireStaff() {
    requireLogin();
    if (!isStaff()) {
        setFlashMessage('error', 'Bạn không có quyền truy cập trang này');
        redirect(SITE_URL . '/index.php');
    }
}

/**
 * Lấy thông tin user hiện tại
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'username' => $_SESSION['username'] ?? '',
        'email' => $_SESSION['email'] ?? '',
        'full_name' => $_SESSION['full_name'] ?? '',
        'role' => $_SESSION['role'] ?? 'customer'
    ];
}

// ============================================
// URL & REDIRECT
// ============================================

/**
 * Redirect đến URL
 */
function redirect($url) {
    if (!headers_sent()) {
        header("Location: $url");
        exit();
    } else {
        echo "<script>window.location.href='$url';</script>";
        exit();
    }
}

/**
 * Lấy URL hiện tại
 */
function getCurrentUrl() {
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    return $protocol . "://" . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
}

/**
 * Tạo URL với query string
 */
function buildUrl($baseUrl, $params = []) {
    if (empty($params)) {
        return $baseUrl;
    }
    
    $query = http_build_query($params);
    $separator = strpos($baseUrl, '?') !== false ? '&' : '?';
    
    return $baseUrl . $separator . $query;
}

// ============================================
// FLASH MESSAGES
// ============================================

/**
 * Set flash message
 */
function setFlashMessage($type, $message) {
    $_SESSION['flash_message'] = [
        'type' => $type, // success, error, warning, info
        'message' => $message,
        'time' => time()
    ];
}

/**
 * Get flash message
 */
function getFlashMessage() {
    if (isset($_SESSION['flash_message'])) {
        $message = $_SESSION['flash_message'];
        unset($_SESSION['flash_message']);
        return $message;
    }
    return null;
}

/**
 * Check có flash message không
 */
function hasFlashMessage() {
    return isset($_SESSION['flash_message']);
}

// ============================================
// INPUT VALIDATION & SANITIZATION
// ============================================

/**
 * Sanitize input
 */
function sanitizeInput($data) {
    if (is_array($data)) {
        return array_map('sanitizeInput', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Validate email
 */
function validateEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * Validate phone (Vietnamese format)
 */
function validatePhone($phone) {
    // Xóa khoảng trắng và ký tự đặc biệt
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Kiểm tra độ dài 10-11 số
    if (strlen($phone) < 10 || strlen($phone) > 11) {
        return false;
    }
    
    // Kiểm tra đầu số hợp lệ
    $validPrefixes = ['03', '05', '07', '08', '09', '01'];
    $prefix = substr($phone, 0, 2);
    
    return in_array($prefix, $validPrefixes);
}

/**
 * Validate URL
 */
function validateUrl($url) {
    return filter_var($url, FILTER_VALIDATE_URL) !== false;
}

/**
 * Clean filename
 */
function cleanFilename($filename) {
    // Xóa ký tự đặc biệt
    $filename = preg_replace('/[^a-zA-Z0-9._-]/', '_', $filename);
    // Xóa nhiều underscore liên tiếp
    $filename = preg_replace('/_+/', '_', $filename);
    return $filename;
}

// ============================================
// FILE UPLOAD
// ============================================

/**
 * Upload file
 */
function uploadFile($file, $targetDir = 'products/') {
    if (!isset($file) || $file['error'] !== UPLOAD_ERR_OK) {
        return ['success' => false, 'message' => 'Không có file được upload hoặc có lỗi xảy ra'];
    }
    
    // Kiểm tra kích thước
    if ($file['size'] > MAX_FILE_SIZE) {
        return ['success' => false, 'message' => 'File quá lớn. Tối đa ' . (MAX_FILE_SIZE / 1024 / 1024) . 'MB'];
    }
    
    // Kiểm tra extension
    $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($extension, ALLOWED_EXTENSIONS)) {
        return ['success' => false, 'message' => 'Định dạng file không được phép. Chỉ chấp nhận: ' . implode(', ', ALLOWED_EXTENSIONS)];
    }
    
    // Tạo tên file mới
    $filename = uniqid() . '_' . time() . '.' . $extension;
    $targetPath = UPLOAD_PATH . $targetDir;
    
    // Tạo thư mục nếu chưa tồn tại
    if (!is_dir($targetPath)) {
        mkdir($targetPath, 0755, true);
    }
    
    $targetFile = $targetPath . $filename;
    
    // Validate file type thêm bằng getimagesize
    if (in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
        if (!getimagesize($file['tmp_name'])) {
            return ['success' => false, 'message' => 'File không phải là hình ảnh hợp lệ'];
        }
    }
    
    // Di chuyển file
    if (move_uploaded_file($file['tmp_name'], $targetFile)) {
        return [
            'success' => true,
            'filename' => $targetDir . $filename,
            'path' => $targetFile
        ];
    }
    
    return ['success' => false, 'message' => 'Upload file thất bại'];
}

/**
 * Upload nhiều file
 */
function uploadMultipleFiles($files, $targetDir = 'products/') {
    $uploadedFiles = [];
    $errors = [];
    
    if (!isset($files['name']) || !is_array($files['name'])) {
        return ['success' => false, 'files' => [], 'errors' => ['Không có file nào']];
    }
    
    for ($i = 0; $i < count($files['name']); $i++) {
        if ($files['error'][$i] === UPLOAD_ERR_NO_FILE) {
            continue;
        }
        
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
            $errors[] = $files['name'][$i] . ': ' . $result['message'];
        }
    }
    
    return [
        'success' => count($uploadedFiles) > 0,
        'files' => $uploadedFiles,
        'errors' => $errors
    ];
}

/**
 * Xóa file
 */
function deleteFile($filename) {
    if (empty($filename)) {
        return false;
    }
    
    $filepath = UPLOAD_PATH . $filename;
    
    if (file_exists($filepath) && is_file($filepath)) {
        return unlink($filepath);
    }
    
    return false;
}

/**
 * Resize image
 */
function resizeImage($source, $destination, $maxWidth = 800, $maxHeight = 800) {
    $imageInfo = getimagesize($source);
    if (!$imageInfo) {
        return false;
    }
    
    list($width, $height, $type) = $imageInfo;
    
    // Tính toán kích thước mới
    $ratio = min($maxWidth / $width, $maxHeight / $height);
    $newWidth = (int)($width * $ratio);
    $newHeight = (int)($height * $ratio);
    
    // Tạo image từ source
    switch ($type) {
        case IMAGETYPE_JPEG:
            $srcImage = imagecreatefromjpeg($source);
            break;
        case IMAGETYPE_PNG:
            $srcImage = imagecreatefrompng($source);
            break;
        case IMAGETYPE_GIF:
            $srcImage = imagecreatefromgif($source);
            break;
        default:
            return false;
    }
    
    // Tạo image mới
    $dstImage = imagecreatetruecolor($newWidth, $newHeight);
    
    // Preserve transparency
    if ($type == IMAGETYPE_PNG || $type == IMAGETYPE_GIF) {
        imagealphablending($dstImage, false);
        imagesavealpha($dstImage, true);
    }
    
    // Resize
    imagecopyresampled($dstImage, $srcImage, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);
    
    // Save
    $result = false;
    switch ($type) {
        case IMAGETYPE_JPEG:
            $result = imagejpeg($dstImage, $destination, 90);
            break;
        case IMAGETYPE_PNG:
            $result = imagepng($dstImage, $destination, 9);
            break;
        case IMAGETYPE_GIF:
            $result = imagegif($dstImage, $destination);
            break;
    }
    
    imagedestroy($srcImage);
    imagedestroy($dstImage);
    
    return $result;
}

// ============================================
// FORMATTING
// ============================================

/**
 * Format currency
 */
function formatCurrency($amount) {
    return number_format($amount, 0, ',', '.') . 'đ';
}

/**
 * Format date
 */
function formatDate($date, $format = 'd/m/Y H:i') {
    if (empty($date) || $date === '0000-00-00 00:00:00') {
        return '';
    }
    
    $timestamp = is_numeric($date) ? $date : strtotime($date);
    return date($format, $timestamp);
}

/**
 * Time ago
 */
function timeAgo($datetime) {
    if (empty($datetime)) {
        return '';
    }
    
    $timestamp = is_numeric($datetime) ? $datetime : strtotime($datetime);
    $difference = time() - $timestamp;
    
    if ($difference < 0) {
        return 'vừa xong';
    }
    
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
    
    return 'vừa xong';
}

/**
 * Truncate text
 */
function truncateText($text, $length = 100, $suffix = '...') {
    $text = strip_tags($text);
    
    if (mb_strlen($text, 'UTF-8') <= $length) {
        return $text;
    }
    
    return mb_substr($text, 0, $length, 'UTF-8') . $suffix;
}

/**
 * Format number
 */
function formatNumber($number, $decimals = 0) {
    return number_format($number, $decimals, ',', '.');
}

/**
 * Format file size
 */
function formatFileSize($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    
    return round($bytes, 2) . ' ' . $units[$pow];
}

// ============================================
// ORDER HELPERS
// ============================================

/**
 * Get order status text
 */
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

/**
 * Get order status class
 */
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

/**
 * Get payment status text
 */
function getPaymentStatusText($status) {
    $statuses = [
        'pending' => 'Chờ thanh toán',
        'paid' => 'Đã thanh toán',
        'failed' => 'Thanh toán thất bại',
        'refunded' => 'Đã hoàn tiền'
    ];
    
    return $statuses[$status] ?? $status;
}

/**
 * Get payment method text
 */
function getPaymentMethodText($method) {
    $methods = [
        'cod' => 'Thanh toán khi nhận hàng (COD)',
        'vnpay' => 'Thanh toán VNPay',
        'bank_transfer' => 'Chuyển khoản ngân hàng',
        'momo' => 'Ví MoMo'
    ];
    
    return $methods[$method] ?? $method;
}

// ============================================
// PRODUCT HELPERS
// ============================================

/**
 * Check sản phẩm còn hàng
 */
function isInStock($quantity) {
    return $quantity > 0;
}

/**
 * Calculate discount percentage
 */
function calculateDiscountPercent($originalPrice, $discountPrice) {
    if ($originalPrice <= 0 || $discountPrice >= $originalPrice) {
        return 0;
    }
    
    return round((($originalPrice - $discountPrice) / $originalPrice) * 100);
}

/**
 * Get final price
 */
function getFinalPrice($price, $discountPrice = null) {
    if ($discountPrice && $discountPrice > 0 && $discountPrice < $price) {
        return $discountPrice;
    }
    return $price;
}

/**
 * Get product image URL
 */
function getProductImage($image, $default = 'default.jpg') {
    if (empty($image)) {
        return SITE_URL . '/assets/images/products/' . $default;
    }
    
    // Nếu ảnh bắt đầu bằng http (URL đầy đủ)
    if (strpos($image, 'http') === 0) {
        return $image;
    }
    
    // Nếu ảnh có đường dẫn uploads/
    if (strpos($image, 'uploads/') === 0) {
        return UPLOAD_URL . $image;
    }
    
    // Nếu ảnh chỉ có tên file
    return SITE_URL . '/assets/images/products/' . $image;
}

/**
 * Get product images array
 */
function getProductImages($imagesJson, $default = 'default.jpg') {
    if (empty($imagesJson)) {
        return [getProductImage($default)];
    }
    
    $images = json_decode($imagesJson, true);
    if (!is_array($images) || empty($images)) {
        return [getProductImage($default)];
    }
    
    $result = [];
    foreach ($images as $image) {
        $result[] = getProductImage($image, $default);
    }
    
    return $result;
}

/**
 * Get category image URL
 */
function getCategoryImage($image, $default = 'default-category.jpg') {
    if (empty($image)) {
        return SITE_URL . '/assets/images/categories/' . $default;
    }
    
    if (strpos($image, 'http') === 0) {
        return $image;
    }
    
    if (strpos($image, 'uploads/') === 0) {
        return UPLOAD_URL . $image;
    }
    
    return SITE_URL . '/assets/images/categories/' . $image;
}

/**
 * Get banner image URL
 */
function getBannerImage($image, $default = 'default-banner.jpg') {
    if (empty($image)) {
        return SITE_URL . '/assets/images/banners/' . $default;
    }
    
    if (strpos($image, 'http') === 0) {
        return $image;
    }
    
    if (strpos($image, 'uploads/') === 0) {
        return UPLOAD_URL . $image;
    }
    
    return SITE_URL . '/assets/images/banners/' . $image;
}

/**
 * Get news image URL
 */
function getNewsImage($image, $default = 'default-news.jpg') {
    if (empty($image)) {
        return SITE_URL . '/assets/images/news/' . $default;
    }
    
    if (strpos($image, 'http') === 0) {
        return $image;
    }
    
    if (strpos($image, 'uploads/') === 0) {
        return UPLOAD_URL . $image;
    }
    
    return SITE_URL . '/assets/images/news/' . $image;
}

/**
 * Generate responsive image HTML
 */
function generateImageHTML($src, $alt = '', $class = '', $attributes = []) {
    $alt = htmlspecialchars($alt);
    $class = htmlspecialchars($class);
    
    $attrString = '';
    foreach ($attributes as $key => $value) {
        $attrString .= ' ' . htmlspecialchars($key) . '="' . htmlspecialchars($value) . '"';
    }
    
    return '<img src="' . htmlspecialchars($src) . '" alt="' . $alt . '" class="' . $class . '"' . $attrString . '>';
}

/**
 * Generate lazy loading image HTML
 */
function generateLazyImageHTML($src, $alt = '', $class = '', $placeholder = '') {
    $attributes = [
        'loading' => 'lazy',
        'data-src' => $src
    ];
    
    if ($placeholder) {
        $attributes['src'] = $placeholder;
    }
    
    return generateImageHTML($src, $alt, $class, $attributes);
}

/**
 * Format specifications
 */
function formatSpecifications($specsJson) {
    if (empty($specsJson)) {
        return '';
    }
    
    $specs = json_decode($specsJson, true);
    if (!$specs || !is_array($specs)) {
        return '';
    }
    
    $html = '<ul class="specifications-list">';
    foreach ($specs as $key => $value) {
        $html .= '<li><strong>' . htmlspecialchars($key) . ':</strong> ' . htmlspecialchars($value) . '</li>';
    }
    $html .= '</ul>';
    
    return $html;
}

// ============================================
// CART HELPERS
// ============================================

/**
 * Get cart count
 */
// Sửa lại hàm getCartCount() trong includes/functions.php (line 737)

function getCartCount() {
    if (isLoggedIn()) {
        try {
            require_once __DIR__ . '/../models/Cart.php';
            $cart = new Cart();
            
            // Kiểm tra xem method có tồn tại không
            if (method_exists($cart, 'countCartItems')) {
                return $cart->countCartItems($_SESSION['user_id']);
            } else {
                // Fallback: Đếm trực tiếp từ database
                $db = new Database();
                $sql = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
                $result = $db->fetchOne($sql, [$_SESSION['user_id']]);
                return $result ? (int)$result['total'] : 0;
            }
        } catch (Exception $e) {
            error_log("Cart count error: " . $e->getMessage());
            return 0;
        }
    } else {
        $cart = $_SESSION['cart'] ?? [];
        return array_sum($cart);
    }
}

/**
 * Get cart total
 */
function getCartTotal() {
    if (isLoggedIn()) {
        require_once __DIR__ . '/../models/Cart.php';
        $cart = new Cart();
        return $cart->calculateCartTotal($_SESSION['user_id']);
    }
    return 0;
}

// ============================================
// PAGINATION
// ============================================

/**
 * Paginate
 */
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
        'has_next' => $currentPage < $totalPages,
        'prev_page' => $currentPage - 1,
        'next_page' => $currentPage + 1
    ];
}

/**
 * Render pagination
 */
function renderPagination($pagination, $baseUrl = '') {
    if ($pagination['total_pages'] <= 1) {
        return '';
    }
    
    $html = '<div class="pagination">';
    
    // Previous
    if ($pagination['has_prev']) {
        $html .= '<a href="' . buildUrl($baseUrl, ['page' => $pagination['prev_page']]) . '"><i class="fas fa-chevron-left"></i> Trước</a>';
    }
    
    // Pages
    $start = max(1, $pagination['current_page'] - 2);
    $end = min($pagination['total_pages'], $pagination['current_page'] + 2);
    
    if ($start > 1) {
        $html .= '<a href="' . buildUrl($baseUrl, ['page' => 1]) . '">1</a>';
        if ($start > 2) {
            $html .= '<span>...</span>';
        }
    }
    
    for ($i = $start; $i <= $end; $i++) {
        if ($i == $pagination['current_page']) {
            $html .= '<span class="active">' . $i . '</span>';
        } else {
            $html .= '<a href="' . buildUrl($baseUrl, ['page' => $i]) . '">' . $i . '</a>';
        }
    }
    
    if ($end < $pagination['total_pages']) {
        if ($end < $pagination['total_pages'] - 1) {
            $html .= '<span>...</span>';
        }
        $html .= '<a href="' . buildUrl($baseUrl, ['page' => $pagination['total_pages']]) . '">' . $pagination['total_pages'] . '</a>';
    }
    
    // Next
    if ($pagination['has_next']) {
        $html .= '<a href="' . buildUrl($baseUrl, ['page' => $pagination['next_page']]) . '">Sau <i class="fas fa-chevron-right"></i></a>';
    }
    
    $html .= '</div>';
    
    return $html;
}

// ============================================
// BREADCRUMB
// ============================================

/**
 * Render breadcrumb
 */
function renderBreadcrumb($items) {
    if (empty($items)) {
        return '';
    }
    
    $html = '<nav class="breadcrumb"><div class="container">';
    $html .= '<a href="' . SITE_URL . '"><i class="fas fa-home"></i> Trang chủ</a>';
    
    $lastIndex = count($items) - 1;
    foreach ($items as $index => $item) {
        $html .= '<span class="separator">/</span>';
        
        if ($index === $lastIndex || empty($item['url'])) {
            $html .= '<span class="current">' . htmlspecialchars($item['text']) . '</span>';
        } else {
            $html .= '<a href="' . htmlspecialchars($item['url']) . '">' . htmlspecialchars($item['text']) . '</a>';
        }
    }
    
    $html .= '</div></nav>';
    
    return $html;
}

// ============================================
// RATING & REVIEWS
// ============================================

/**
 * Render stars rating
 */
function renderStars($rating, $maxStars = 5) {
    $rating = max(0, min($maxStars, $rating));
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

// ============================================
// SECURITY
// ============================================

/**
 * Generate CSRF token
 */
function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Validate CSRF token
 */
function validateCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Get client IP
 */
function getClientIP() {
    $headers = [
        'HTTP_CLIENT_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_X_FORWARDED',
        'HTTP_FORWARDED_FOR',
        'HTTP_FORWARDED',
        'REMOTE_ADDR'
    ];
    
    foreach ($headers as $header) {
        if (isset($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            return trim($ips[0]);
        }
    }
    
    return 'UNKNOWN';
}

/**
 * Generate random string
 */
function generateRandomString($length = 10) {
    return bin2hex(random_bytes($length / 2));
}

// ============================================
// UTILITIES
// ============================================

/**
 * Debug dump
 */
function dd($data) {
    echo '<pre>';
    var_dump($data);
    echo '</pre>';
    die();
}

/**
 * Check mobile device
 */
function isMobile() {
    return preg_match("/(android|avantgo|blackberry|bolt|boost|cricket|docomo|fone|hiptop|mini|mobi|palm|phone|pie|tablet|up\.browser|up\.link|webos|wos)/i", $_SERVER["HTTP_USER_AGENT"]);
}

/**
 * Array get (lấy giá trị từ array an toàn)
 */
function array_get($array, $key, $default = null) {
    if (!is_array($array)) {
        return $default;
    }
    
    if (isset($array[$key])) {
        return $array[$key];
    }
    
    return $default;
}

/**
 * Slug generator
 */
function createSlug($string) {
    $string = mb_strtolower($string, 'UTF-8');
    
    // Vietnamese characters
    $vietnamese = [
        'á' => 'a', 'à' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
        'ă' => 'a', 'ắ' => 'a', 'ằ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
        'â' => 'a', 'ấ' => 'a', 'ầ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
        'é' => 'e', 'è' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e',
        'ê' => 'e', 'ế' => 'e', 'ề' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
        'í' => 'i', 'ì' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
        'ó' => 'o', 'ò' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o',
        'ô' => 'o', 'ố' => 'o', 'ồ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o',
        'ơ' => 'o', 'ớ' => 'o', 'ờ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
        'ú' => 'u', 'ù' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u',
        'ư' => 'u', 'ứ' => 'u', 'ừ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
        'ý' => 'y', 'ỳ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
        'đ' => 'd'
    ];
    
    $string = strtr($string, $vietnamese);
    
    // Remove special characters
    $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
    
    // Replace spaces and multiple dashes with single dash
    $string = preg_replace('/[\s-]+/', '-', $string);
    
    return trim($string, '-');
}

/**
 * Log activity
 */
function logActivity($action, $details = '', $userId = null) {
    if ($userId === null && isLoggedIn()) {
        $userId = $_SESSION['user_id'];
    }
    
    $db = new Database();
    $sql = "INSERT INTO activity_logs (user_id, action, details, ip_address, user_agent, created_at) 
            VALUES (?, ?, ?, ?, ?, NOW())";
    
    try {
        $db->query($sql, [
            $userId,
            $action,
            $details,
            getClientIP(),
            $_SERVER['HTTP_USER_AGENT'] ?? ''
        ]);
    } catch (Exception $e) {
        error_log("Log activity error: " . $e->getMessage());
    }
}

/**
 * Send email notification to admin
 */
function notifyAdmin($subject, $message) {
    // Sử dụng mailer nếu đã cấu hình
    try {
        require_once __DIR__ . '/mailer.php';
        $mailer = new Mailer();
        return $mailer->sendWelcomeEmail(ADMIN_EMAIL, 'Admin', $message);
    } catch (Exception $e) {
        error_log("Admin notification error: " . $e->getMessage());
        return false;
    }
}

/**
 * Generate order code
 */
function generateOrderCode() {
    return 'DA' . date('YmdHis') . rand(1000, 9999);
}

/**
 * Generate coupon code
 */
function generateCouponCode($prefix = 'DA', $length = 6) {
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = $prefix;
    
    for ($i = 0; $i < $length; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }
    
    return $code;
}

/**
 * Check if current page is admin
 */
function isAdminPage() {
    return strpos($_SERVER['REQUEST_URI'], '/admin/') !== false;
}

/**
 * Get product stock status
 */
function getStockStatus($quantity) {
    if ($quantity <= 0) {
        return ['text' => 'Hết hàng', 'class' => 'danger'];
    } elseif ($quantity < 10) {
        return ['text' => 'Sắp hết', 'class' => 'warning'];
    } else {
        return ['text' => 'Còn hàng', 'class' => 'success'];
    }
}

/**
 * Calculate shipping fee
 */
function calculateShippingFee($total, $address = '') {
    // Miễn phí ship cho đơn từ 5 triệu
    if ($total >= 5000000) {
        return 0;
    }
    
    // Phí ship cơ bản
    $fee = 30000;
    
    // Có thể thêm logic tính theo địa chỉ ở đây
    // Ví dụ: ship xa hơn thì phí cao hơn
    
    return $fee;
}

/**
 * Format Vietnamese address
 */
function formatAddress($address) {
    if (empty($address)) {
        return '';
    }
    
    // Chuẩn hóa địa chỉ Việt Nam
    $address = trim($address);
    $address = preg_replace('/\s+/', ' ', $address);
    
    return $address;
}

/**
 * Validate Vietnamese ID card
 */
function validateIDCard($id) {
    // CMND cũ: 9 hoặc 12 số
    // CCCD mới: 12 số
    $id = preg_replace('/[^0-9]/', '', $id);
    
    return in_array(strlen($id), [9, 12]);
}

/**
 * Get age from birthday
 */
function getAge($birthday) {
    if (empty($birthday)) {
        return null;
    }
    
    $birthDate = new DateTime($birthday);
    $today = new DateTime('today');
    
    return $birthDate->diff($today)->y;
}

/**
 * Check if date is valid
 */
function isValidDate($date, $format = 'Y-m-d') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

/**
 * Get days between dates
 */
function getDaysBetween($date1, $date2) {
    $datetime1 = new DateTime($date1);
    $datetime2 = new DateTime($date2);
    $interval = $datetime1->diff($datetime2);
    
    return abs($interval->days);
}

/**
 * Get month name in Vietnamese
 */
function getVietnameseMonth($month) {
    $months = [
        1 => 'Tháng Một', 2 => 'Tháng Hai', 3 => 'Tháng Ba',
        4 => 'Tháng Tư', 5 => 'Tháng Năm', 6 => 'Tháng Sáu',
        7 => 'Tháng Bảy', 8 => 'Tháng Tám', 9 => 'Tháng Chín',
        10 => 'Tháng Mười', 11 => 'Tháng Mười Một', 12 => 'Tháng Mười Hai'
    ];
    
    return $months[(int)$month] ?? '';
}

/**
 * Get day of week in Vietnamese
 */
function getVietnameseDayOfWeek($date) {
    $days = [
        'Sunday' => 'Chủ Nhật',
        'Monday' => 'Thứ Hai',
        'Tuesday' => 'Thứ Ba',
        'Wednesday' => 'Thứ Tư',
        'Thursday' => 'Thứ Năm',
        'Friday' => 'Thứ Sáu',
        'Saturday' => 'Thứ Bảy'
    ];
    
    $dayName = date('l', strtotime($date));
    return $days[$dayName] ?? '';
}

/**
 * Format phone number Vietnamese style
 */
function formatPhoneNumber($phone) {
    // Xóa ký tự không phải số
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    // Format: 0123.456.789 hoặc 0123.456.7890
    if (strlen($phone) == 10) {
        return substr($phone, 0, 4) . '.' . substr($phone, 4, 3) . '.' . substr($phone, 7);
    } elseif (strlen($phone) == 11) {
        return substr($phone, 0, 4) . '.' . substr($phone, 4, 3) . '.' . substr($phone, 7);
    }
    
    return $phone;
}

/**
 * Mask sensitive data
 */
function maskEmail($email) {
    $parts = explode('@', $email);
    if (count($parts) != 2) {
        return $email;
    }
    
    $name = $parts[0];
    $domain = $parts[1];
    
    $nameLength = strlen($name);
    if ($nameLength <= 2) {
        $masked = str_repeat('*', $nameLength);
    } else {
        $masked = $name[0] . str_repeat('*', $nameLength - 2) . $name[$nameLength - 1];
    }
    
    return $masked . '@' . $domain;
}

function maskPhone($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    $length = strlen($phone);
    
    if ($length < 4) {
        return str_repeat('*', $length);
    }
    
    return substr($phone, 0, 3) . str_repeat('*', $length - 6) . substr($phone, -3);
}

/**
 * Generate QR code URL
 */
function generateQRCode($data, $size = 200) {
    return "https://api.qrserver.com/v1/create-qr-code/?size={$size}x{$size}&data=" . urlencode($data);
}

/**
 * Check strong password
 */
function isStrongPassword($password) {
    // Ít nhất 8 ký tự, có chữ hoa, chữ thường, số
    return strlen($password) >= 8 
        && preg_match('/[A-Z]/', $password) 
        && preg_match('/[a-z]/', $password) 
        && preg_match('/[0-9]/', $password);
}

/**
 * Clean session cart (merge with DB when login)
 */
function mergeSessionCart($userId) {
    if (!isset($_SESSION['cart']) || empty($_SESSION['cart'])) {
        return;
    }
    
    require_once __DIR__ . '/../models/Cart.php';
    $cartModel = new Cart();
    
    foreach ($_SESSION['cart'] as $productId => $quantity) {
        $cartModel->addToCart($userId, $productId, $quantity);
    }
    
    unset($_SESSION['cart']);
}

/**
 * Get browser name
 */
function getBrowserName() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    if (strpos($userAgent, 'Firefox') !== false) return 'Firefox';
    if (strpos($userAgent, 'Chrome') !== false) return 'Chrome';
    if (strpos($userAgent, 'Safari') !== false) return 'Safari';
    if (strpos($userAgent, 'Edge') !== false) return 'Edge';
    if (strpos($userAgent, 'Opera') !== false) return 'Opera';
    if (strpos($userAgent, 'MSIE') !== false || strpos($userAgent, 'Trident') !== false) return 'IE';
    
    return 'Unknown';
}

/**
 * Get operating system
 */
function getOS() {
    $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
    
    if (preg_match('/windows/i', $userAgent)) return 'Windows';
    if (preg_match('/macintosh|mac os x/i', $userAgent)) return 'Mac';
    if (preg_match('/linux/i', $userAgent)) return 'Linux';
    if (preg_match('/android/i', $userAgent)) return 'Android';
    if (preg_match('/iphone|ipad|ipod/i', $userAgent)) return 'iOS';
    
    return 'Unknown';
}

/**
 * Clean old sessions
 */
function cleanOldSessions() {
    $sessionPath = session_save_path();
    if (empty($sessionPath)) {
        $sessionPath = sys_get_temp_dir();
    }
    
    $files = glob($sessionPath . '/sess_*');
    if ($files === false) return;
    
    $now = time();
    $timeout = SESSION_TIMEOUT ?? 3600;
    
    foreach ($files as $file) {
        if (is_file($file)) {
            if ($now - filemtime($file) >= $timeout) {
                @unlink($file);
            }
        }
    }
}

/**
 * Generate meta tags for SEO
 */
function generateMetaTags($title, $description = '', $keywords = '', $image = '') {
    $siteName = SITE_NAME;
    $siteUrl = SITE_URL;
    
    $html = "<title>$title</title>\n";
    $html .= "<meta name='description' content='$description'>\n";
    
    if ($keywords) {
        $html .= "<meta name='keywords' content='$keywords'>\n";
    }
    
    // Open Graph
    $html .= "<meta property='og:title' content='$title'>\n";
    $html .= "<meta property='og:description' content='$description'>\n";
    $html .= "<meta property='og:type' content='website'>\n";
    $html .= "<meta property='og:url' content='$siteUrl'>\n";
    $html .= "<meta property='og:site_name' content='$siteName'>\n";
    
    if ($image) {
        $html .= "<meta property='og:image' content='$image'>\n";
    }
    
    // Twitter Card
    $html .= "<meta name='twitter:card' content='summary_large_image'>\n";
    $html .= "<meta name='twitter:title' content='$title'>\n";
    $html .= "<meta name='twitter:description' content='$description'>\n";
    
    if ($image) {
        $html .= "<meta name='twitter:image' content='$image'>\n";
    }
    
    return $html;
}

/**
 * Convert number to words (Vietnamese)
 */
function numberToWords($number) {
    $units = ['', 'một', 'hai', 'ba', 'bốn', 'năm', 'sáu', 'bảy', 'tám', 'chín'];
    $teens = ['mười', 'mười một', 'mười hai', 'mười ba', 'mười bốn', 'mười lăm', 'mười sáu', 'mười bảy', 'mười tám', 'mười chín'];
    $tens = ['', '', 'hai mươi', 'ba mươi', 'bốn mươi', 'năm mươi', 'sáu mươi', 'bảy mươi', 'tám mươi', 'chín mươi'];
    
    if ($number == 0) return 'không';
    
    // Implement full conversion logic here
    // This is a simplified version
    return number_format($number, 0, ',', '.');
}

/**
 * Rate limiter - Simple implementation
 */
function checkRateLimit($action, $limit = 10, $period = 60) {
    $key = 'rate_limit_' . $action . '_' . getClientIP();
    
    if (!isset($_SESSION[$key])) {
        $_SESSION[$key] = ['count' => 0, 'start' => time()];
    }
    
    $data = $_SESSION[$key];
    
    // Reset if period expired
    if (time() - $data['start'] > $period) {
        $_SESSION[$key] = ['count' => 1, 'start' => time()];
        return true;
    }
    
    // Check limit
    if ($data['count'] >= $limit) {
        return false;
    }
    
    // Increment
    $_SESSION[$key]['count']++;
    return true;
}

/**
 * Debounce function (for preventing multiple rapid submissions)
 */
function debounceAction($action, $delay = 3) {
    $key = 'debounce_' . $action;
    
    if (isset($_SESSION[$key])) {
        $lastTime = $_SESSION[$key];
        if (time() - $lastTime < $delay) {
            return false;
        }
    }
    
    $_SESSION[$key] = time();
    return true;
}

/**
 * Get config value
 */
function getConfig($key, $default = null) {
    static $config = null;
    
    if ($config === null) {
        $config = [];
        // Load from database or config file if needed
    }
    
    return $config[$key] ?? $default;
}

/**
 * Set config value
 */
function setConfig($key, $value) {
    // Save to database or config file
    return true;
}

/**
 * Check if development mode
 */
function isDevelopment() {
    return defined('ENVIRONMENT') && ENVIRONMENT === 'development';
}

/**
 * Check if production mode
 */
function isProduction() {
    return defined('ENVIRONMENT') && ENVIRONMENT === 'production';
}

/**
 * Log error to file
 */
function logError($message, $context = []) {
    $logFile = __DIR__ . '/../logs/error_' . date('Y-m-d') . '.log';
    $logDir = dirname($logFile);
    
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $timestamp = date('Y-m-d H:i:s');
    $contextStr = !empty($context) ? json_encode($context) : '';
    $logMessage = "[$timestamp] $message $contextStr\n";
    
    error_log($logMessage, 3, $logFile);
}

/**
 * Get or set cache
 */
function cache($key, $value = null, $ttl = 3600) {
    $cacheKey = 'cache_' . md5($key);
    
    // Get cache
    if ($value === null) {
        if (isset($_SESSION[$cacheKey])) {
            $cached = $_SESSION[$cacheKey];
            if ($cached['expires'] > time()) {
                return $cached['value'];
            }
            unset($_SESSION[$cacheKey]);
        }
        return null;
    }
    
    // Set cache
    $_SESSION[$cacheKey] = [
        'value' => $value,
        'expires' => time() + $ttl
    ];
    
    return $value;
}

/**
 * Clear all cache
 */
function clearCache() {
    foreach ($_SESSION as $key => $value) {
        if (strpos($key, 'cache_') === 0) {
            unset($_SESSION[$key]);
        }
    }
}

/**
 * Array to CSV
 */
function arrayToCSV($array, $filename = 'export.csv') {
    if (empty($array)) {
        return false;
    }
    
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    
    // Add BOM for UTF-8
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    
    // Headers
    fputcsv($output, array_keys($array[0]));
    
    // Data
    foreach ($array as $row) {
        fputcsv($output, $row);
    }
    
    fclose($output);
    exit;
}

/**
 * Convert array to XML
 */
function arrayToXML($array, $rootElement = 'root', $xml = null) {
    if ($xml === null) {
        $xml = new SimpleXMLElement("<$rootElement/>");
    }
    
    foreach ($array as $key => $value) {
        if (is_array($value)) {
            arrayToXML($value, $key, $xml->addChild($key));
        } else {
            $xml->addChild($key, htmlspecialchars($value));
        }
    }
    
    return $xml->asXML();
}

/**
 * Check maintenance mode
 */
function isMaintenanceMode() {
    $maintenanceFile = __DIR__ . '/../maintenance.flag';
    return file_exists($maintenanceFile);
}

/**
 * Enable maintenance mode
 */
function enableMaintenanceMode($message = 'Website đang bảo trì') {
    $maintenanceFile = __DIR__ . '/../maintenance.flag';
    file_put_contents($maintenanceFile, $message);
}

/**
 * Disable maintenance mode
 */
function disableMaintenanceMode() {
    $maintenanceFile = __DIR__ . '/../maintenance.flag';
    if (file_exists($maintenanceFile)) {
        unlink($maintenanceFile);
    }
}

// ============================================
// AUTO-EXECUTE ON INCLUDE
// ============================================

// Khởi tạo session nếu chưa có
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Clean old sessions (chạy ngẫu nhiên 1% requests)
if (rand(1, 100) === 1) {
    cleanOldSessions();
}

// Set timezone
if (!ini_get('date.timezone')) {
    date_default_timezone_set('Asia/Ho_Chi_Minh');
}