<?php
// api/coupon-handler.php - API xử lý mã giảm giá

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../models/Coupon.php';
require_once __DIR__ . '/../models/Cart.php';

$couponModel = new Coupon();
$cartModel = new Cart();

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

$userId = $_SESSION['user_id'] ?? null;

switch ($action) {
    case 'apply':
        // Áp dụng mã giảm giá
        if (!$userId) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
            exit;
        }
        
        $couponCode = strtoupper(trim($input['coupon_code'] ?? ''));
        
        if (empty($couponCode)) {
            echo json_encode(['success' => false, 'message' => 'Vui lòng nhập mã giảm giá']);
            exit;
        }
        
        // Lấy tổng giỏ hàng
        $cartDetails = $cartModel->getCartDetails($userId);
        $orderTotal = $cartDetails['subtotal'];
        
        if ($orderTotal <= 0) {
            echo json_encode(['success' => false, 'message' => 'Giỏ hàng trống']);
            exit;
        }
        
        // Kiểm tra và áp dụng coupon
        $result = $couponModel->applyCoupon($couponCode, $orderTotal);
        
        if ($result['success']) {
            // Lưu vào session
            $_SESSION['applied_coupon'] = [
                'coupon_id' => $result['coupon_id'],
                'coupon_code' => $result['coupon_code'],
                'discount' => $result['discount']
            ];
        }
        
        echo json_encode($result);
        break;
        
    case 'remove':
        // Xóa mã giảm giá
        unset($_SESSION['applied_coupon']);
        echo json_encode(['success' => true, 'message' => 'Đã xóa mã giảm giá']);
        break;
        
    case 'get':
        // Lấy mã đã áp dụng
        if (isset($_SESSION['applied_coupon'])) {
            echo json_encode([
                'success' => true,
                'coupon' => $_SESSION['applied_coupon']
            ]);
        } else {
            echo json_encode(['success' => false, 'message' => 'Chưa áp dụng mã giảm giá']);
        }
        break;
        
    case 'list':
        // Lấy danh sách mã giảm giá có sẵn
        $coupons = $couponModel->getActiveCoupons();
        echo json_encode(['success' => true, 'coupons' => $coupons]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}
?>