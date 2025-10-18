<?php
// models/Cart.php - Class quản lý giỏ hàng

require_once __DIR__ . '/../includes/Database.php';

class Cart {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Thêm sản phẩm vào giỏ hàng
    public function addToCart($userId, $productId, $quantity = 1) {
        // Kiểm tra sản phẩm đã có trong giỏ hàng chưa
        $existingItem = $this->getCartItem($userId, $productId);
        
        if ($existingItem) {
            // Cập nhật số lượng
            $newQuantity = $existingItem['quantity'] + $quantity;
            $sql = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
            $result = $this->db->query($sql, [$newQuantity, $userId, $productId]);
        } else {
            // Thêm mới
            $sql = "INSERT INTO cart (user_id, product_id, quantity) VALUES (?, ?, ?)";
            $result = $this->db->query($sql, [$userId, $productId, $quantity]);
        }
        
        if ($result) {
            return ['success' => true, 'message' => 'Đã thêm vào giỏ hàng'];
        }
        
        return ['success' => false, 'message' => 'Không thể thêm vào giỏ hàng'];
    }
    
    // Lấy giỏ hàng của user
    public function getCart($userId) {
        $sql = "SELECT c.*, 
                p.name, 
                p.slug,
                p.image, 
                p.price, 
                p.discount_price,
                p.quantity as stock,
                CASE 
                    WHEN p.discount_price IS NOT NULL THEN p.discount_price 
                    ELSE p.price 
                END as final_price,
                (c.quantity * CASE 
                    WHEN p.discount_price IS NOT NULL THEN p.discount_price 
                    ELSE p.price 
                END) as total_price
                FROM cart c
                INNER JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ? 
                AND p.is_active = 1 
                AND p.deleted_at IS NULL
                ORDER BY c.created_at DESC";
        
        return $this->db->fetchAll($sql, [$userId]);
    }
    
    // Lấy một item trong giỏ hàng
    public function getCartItem($userId, $productId) {
        $sql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
        return $this->db->fetchOne($sql, [$userId, $productId]);
    }
    
    // Cập nhật số lượng sản phẩm trong giỏ hàng
    public function updateQuantity($userId, $productId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeFromCart($userId, $productId);
        }
        
        $sql = "UPDATE cart SET quantity = ? WHERE user_id = ? AND product_id = ?";
        $result = $this->db->query($sql, [$quantity, $userId, $productId]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Cập nhật giỏ hàng thành công'];
        }
        
        return ['success' => false, 'message' => 'Cập nhật giỏ hàng thất bại'];
    }
    
    // Xóa sản phẩm khỏi giỏ hàng
    public function removeFromCart($userId, $productId) {
        $sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
        $result = $this->db->query($sql, [$userId, $productId]);
        
        if ($result) {
            return ['success' => true, 'message' => 'Đã xóa khỏi giỏ hàng'];
        }
        
        return ['success' => false, 'message' => 'Xóa khỏi giỏ hàng thất bại'];
    }
    
    // Xóa toàn bộ giỏ hàng
    public function clearCart($userId) {
        $sql = "DELETE FROM cart WHERE user_id = ?";
        return $this->db->query($sql, [$userId]);
    }
    
    // Đếm số lượng sản phẩm trong giỏ hàng
    public function countItems($userId) {
        $sql = "SELECT COUNT(*) as total FROM cart WHERE user_id = ?";
        $result = $this->db->fetchOne($sql, [$userId]);
        return $result['total'] ?? 0;
    }
    
    // Tính tổng tiền giỏ hàng
    public function getTotal($userId) {
        $sql = "SELECT SUM(c.quantity * CASE 
                    WHEN p.discount_price IS NOT NULL THEN p.discount_price 
                    ELSE p.price 
                END) as total
                FROM cart c
                INNER JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ? 
                AND p.is_active = 1 
                AND p.deleted_at IS NULL";
        
        $result = $this->db->fetchOne($sql, [$userId]);
        return $result['total'] ?? 0;
    }
    
    // Kiểm tra tồn kho cho giỏ hàng
    public function validateStock($userId) {
        $cartItems = $this->getCart($userId);
        $errors = [];
        
        foreach ($cartItems as $item) {
            if ($item['quantity'] > $item['stock']) {
                $errors[] = [
                    'product_id' => $item['product_id'],
                    'product_name' => $item['name'],
                    'requested' => $item['quantity'],
                    'available' => $item['stock']
                ];
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    // Lấy giỏ hàng với thông tin coupon
    public function getCartWithCoupon($userId, $couponCode = null) {
        $cartItems = $this->getCart($userId);
        $subtotal = $this->getTotal($userId);
        
        $result = [
            'items' => $cartItems,
            'subtotal' => $subtotal,
            'discount' => 0,
            'total' => $subtotal,
            'coupon' => null
        ];
        
        if ($couponCode) {
            require_once __DIR__ . '/Coupon.php';
            $couponModel = new Coupon();
            $couponResult = $couponModel->applyCoupon($couponCode, $subtotal);
            
            if ($couponResult['valid']) {
                $result['discount'] = $couponResult['discount'];
                $result['total'] = $subtotal - $couponResult['discount'];
                $result['coupon'] = $couponResult['coupon'];
            }
        }
        
        return $result;
    }
    
    // Chuyển giỏ hàng thành đơn hàng
    public function convertToOrder($userId, $orderData) {
        require_once __DIR__ . '/Order.php';
        $orderModel = new Order();
        
        $cartItems = $this->getCart($userId);
        
        if (empty($cartItems)) {
            return ['success' => false, 'message' => 'Giỏ hàng trống'];
        }
        
        // Kiểm tra tồn kho
        $stockValidation = $this->validateStock($userId);
        if (!$stockValidation['valid']) {
            return [
                'success' => false, 
                'message' => 'Một số sản phẩm không đủ hàng',
                'errors' => $stockValidation['errors']
            ];
        }
        
        // Tạo đơn hàng
        $orderData['user_id'] = $userId;
        $orderData['items'] = $cartItems;
        
        $result = $orderModel->createOrder($orderData);
        
        if ($result['success']) {
            // Xóa giỏ hàng sau khi đặt hàng thành công
            $this->clearCart($userId);
        }
        
        return $result;
    }
    
    // Lấy giỏ hàng cho session (khách chưa đăng nhập)
    public function getSessionCart() {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        $cart = [];
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            $sql = "SELECT id, name, slug, image, price, discount_price, quantity as stock
                    FROM products 
                    WHERE id = ? AND is_active = 1 AND deleted_at IS NULL";
            
            $product = $this->db->fetchOne($sql, [$productId]);
            
            if ($product) {
                $finalPrice = $product['discount_price'] ?? $product['price'];
                $cart[] = [
                    'product_id' => $product['id'],
                    'name' => $product['name'],
                    'slug' => $product['slug'],
                    'image' => $product['image'],
                    'price' => $product['price'],
                    'discount_price' => $product['discount_price'],
                    'final_price' => $finalPrice,
                    'quantity' => $quantity,
                    'stock' => $product['stock'],
                    'total_price' => $quantity * $finalPrice
                ];
            }
        }
        
        return $cart;
    }
    
    // Thêm vào giỏ hàng session
    public function addToSessionCart($productId, $quantity = 1) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
        
        return ['success' => true, 'message' => 'Đã thêm vào giỏ hàng'];
    }
    
    // Chuyển giỏ hàng session sang database khi đăng nhập
    public function mergeSessionCart($userId) {
        if (empty($_SESSION['cart'])) {
            return;
        }
        
        foreach ($_SESSION['cart'] as $productId => $quantity) {
            $this->addToCart($userId, $productId, $quantity);
        }
        
        // Xóa session cart
        unset($_SESSION['cart']);
    }
}
?>