<?php
// api/cart.php - API xử lý giỏ hàng

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Product.php';

$cartModel = new Cart();
$userModel = new User();
$productModel = new Product();

// Lấy dữ liệu từ request
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'add':
        // Thêm sản phẩm vào giỏ
        $productId = $input['product_id'] ?? 0;
        $quantity = $input['quantity'] ?? 1;
        
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
        
        if ($userModel->isLoggedIn()) {
            // User đã đăng nhập - lưu vào database
            $result = $cartModel->addToCart($_SESSION['user_id'], $productId, $quantity);
        } else {
            // User chưa đăng nhập - lưu vào session
            $result = $cartModel->addToSessionCart($productId, $quantity);
        }
        
        echo json_encode($result);
        break;
        
    case 'update':
        // Cập nhật số lượng
        $productId = $input['product_id'] ?? 0;
        $quantity = $input['quantity'] ?? 1;
        
        if ($userModel->isLoggedIn()) {
            $result = $cartModel->updateCartItem($_SESSION['user_id'], $productId, $quantity);
        } else {
            $result = $cartModel->updateSessionCart($productId, $quantity);
        }
        
        echo json_encode($result);
        break;
        
    case 'remove':
        // Xóa sản phẩm khỏi giỏ
        $productId = $input['product_id'] ?? 0;
        
        if ($userModel->isLoggedIn()) {
            $result = $cartModel->removeFromCart($_SESSION['user_id'], $productId);
        } else {
            $result = $cartModel->removeFromSessionCart($productId);
        }
        
        echo json_encode($result);
        break;
        
    case 'clear':
        // Xóa toàn bộ giỏ hàng
        if ($userModel->isLoggedIn()) {
            $cartModel->clearCart($_SESSION['user_id']);
        } else {
            $cartModel->clearSessionCart();
        }
        
        echo json_encode(['success' => true, 'message' => 'Đã xóa giỏ hàng']);
        break;
        
    case 'get':
        // Lấy giỏ hàng
        if ($userModel->isLoggedIn()) {
            $cartDetails = $cartModel->getCartDetails($_SESSION['user_id']);
        } else {
            $sessionCart = $cartModel->getSessionCart();
            $items = [];
            $subtotal = 0;
            
            foreach ($sessionCart as $productId => $quantity) {
                $product = $productModel->getProductById($productId);
                if ($product) {
                    $finalPrice = $product['discount_price'] ?? $product['price'];
                    $items[] = [
                        'product_id' => $productId,
                        'name' => $product['name'],
                        'slug' => $product['slug'],
                        'image' => $product['image'],
                        'price' => $product['price'],
                        'discount_price' => $product['discount_price'],
                        'final_price' => $finalPrice,
                        'quantity' => $quantity,
                        'stock_quantity' => $product['quantity'],
                        'total' => $finalPrice * $quantity
                    ];
                    $subtotal += $finalPrice * $quantity;
                }
            }
            
            $cartDetails = [
                'items' => $items,
                'subtotal' => $subtotal,
                'item_count' => count($items)
            ];
        }
        
        echo json_encode(['success' => true, 'data' => $cartDetails]);
        break;
        
    case 'count':
        // Đếm số lượng sản phẩm trong giỏ
        if ($userModel->isLoggedIn()) {
            $count = $cartModel->countCartItems($_SESSION['user_id']);
        } else {
            $count = $cartModel->countSessionCartItems();
        }
        
        echo json_encode(['success' => true, 'count' => $count]);
        break;
        
    default:
        echo json_encode(['success' => false, 'message' => 'Action không hợp lệ']);
        break;
}
?>