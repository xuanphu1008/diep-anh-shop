<?php
// api/cart-handler.php - API xử lý giỏ hàng

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/Product.php';

$cartModel = new Cart();
$productModel = new Product();

// Lấy action từ request
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

// Kiểm tra người dùng đăng nhập
$userId = $_SESSION['user_id'] ?? null;

switch ($action) {
    case 'add':
        // Thêm sản phẩm vào giỏ hàng
        $productId = (int)($input['product_id'] ?? 0);
        $quantity = (int)($input['quantity'] ?? 1);
        
        if ($productId <= 0 || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }
        
        // Kiểm tra sản phẩm tồn tại
        $product = $productModel->getProductById($productId);
        if (!$product) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
            exit;
        }
        
        // Kiểm tra tồn kho
        if ($product['quantity'] < $quantity) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không đủ số lượng']);
            exit;
        }
        
        if ($userId) {
            // User đã đăng nhập - lưu vào database
            $result = $cartModel->addToCart($userId, $productId, $quantity);
        } else {
            // User chưa đăng nhập - lưu vào session
            $result = $cartModel->addToSessionCart($productId, $quantity);
        }
        
        echo json_encode($result);
        break;
        
    case 'update':
        // Cập nhật số lượng
        $productId = (int)($input['product_id'] ?? 0);
        $quantity = (int)($input['quantity'] ?? 1);
        
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }
        
        if ($userId) {
            $result = $cartModel->updateCartItem($userId, $productId, $quantity);
        } else {
            $result = $cartModel->updateSessionCart($productId, $quantity);
        }
        
        echo json_encode($result);
        break;
        
    case 'remove':
        // Xóa sản phẩm
        $productId = (int)($input['product_id'] ?? 0);
        
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }
        
        if ($userId) {
            $result = $cartModel->removeFromCart($userId, $productId);
        } else {
            $result = $cartModel->removeFromSessionCart($productId);
        }
        
        echo json_encode($result);
        break;
        
    case 'clear':
        // Xóa toàn bộ giỏ hàng
        if ($userId) {
            $cartModel->clearCart($userId);
        } else {
            $cartModel->clearSessionCart();
        }
        
        echo json_encode(['success' => true, 'message' => 'Đã xóa toàn bộ giỏ hàng']);
        break;
        
    case 'count':
        // Đếm số lượng sản phẩm trong giỏ
        if ($userId) {
            $count = $cartModel->countCartItems($userId);
        } else {
            $count = $cartModel->countSessionCartItems();
        }
        
        echo json_encode(['success' => true, 'count' => $count]);
        break;
        
    case 'get':
        // Lấy giỏ hàng
        if ($userId) {
            $cart = $cartModel->getCartDetails($userId);
        } else {
            $cart = ['items' => [], 'subtotal' => 0, 'item_count' => 0];
        }
        
        echo json_encode(['success' => true, 'cart' => $cart]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}
?>