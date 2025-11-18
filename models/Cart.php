<?php
// models/Cart.php - Model quản lý giỏ hàng

require_once __DIR__ . '/../includes/Database.php';

class Cart {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    /**
     * Lấy chi tiết giỏ hàng (cho user đã đăng nhập)
     */
    public function getCartDetails($userId) {
        $sql = "SELECT c.*, p.name, p.slug, p.price, p.discount_price, 
                p.image, p.quantity as stock_quantity
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ? AND p.is_active = 1 AND p.deleted_at IS NULL";
        
        $items = $this->db->fetchAll($sql, [$userId]);
        
        $subtotal = 0;
        $formattedItems = [];
        
        foreach ($items as $item) {
            $finalPrice = $item['discount_price'] ?: $item['price'];
            $total = $finalPrice * $item['quantity'];
            
            $formattedItems[] = [
                'product_id' => $item['product_id'],
                'name' => $item['name'],
                'slug' => $item['slug'],
                'price' => $item['price'],
                'discount_price' => $item['discount_price'],
                'final_price' => $finalPrice,
                'image' => $item['image'],
                'quantity' => $item['quantity'],
                'stock_quantity' => $item['stock_quantity'],
                'total' => $total
            ];
            
            $subtotal += $total;
        }
        
        return [
            'items' => $formattedItems,
            'subtotal' => $subtotal,
            'item_count' => count($formattedItems)
        ];
    }
    
    /**
     * Lấy giỏ hàng từ session (user chưa đăng nhập)
     */
    public function getSessionCart() {
        return $_SESSION['cart'] ?? [];
    }

    /**
     * Lấy một item trong giỏ hàng
     */
    public function getCartItem($userId, $productId) {
        $sql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
        return $this->db->fetchOne($sql, [$userId, $productId]);
    }

    /**
     * Cập nhật số lượng sản phẩm (alias của updateCartItem)
     */
    public function updateQuantity($userId, $productId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeFromCart($userId, $productId);
        }
        
        $sql = "UPDATE cart SET quantity = ?, updated_at = NOW() 
                WHERE user_id = ? AND product_id = ?";
        
        if ($this->db->query($sql, [$quantity, $userId, $productId])) {
            return ['success' => true, 'message' => 'Cập nhật số lượng thành công'];
        } else {
            return ['success' => false, 'message' => 'Cập nhật số lượng thất bại'];
        }
    }

    /**
     * Đếm số lượng items trong giỏ
     */
    public function countCartItems($userId) {
        $sql = "SELECT SUM(quantity) as total FROM cart WHERE user_id = ?";
        $result = $this->db->fetchOne($sql, [$userId]);
        return $result ? (int)$result['total'] : 0;
    }
    
    /**
     * Tính tổng tiền giỏ hàng
     */
    public function calculateCartTotal($userId) {
        $sql = "SELECT SUM(c.quantity * COALESCE(p.discount_price, p.price)) as total 
                FROM cart c 
                JOIN products p ON c.product_id = p.id 
                WHERE c.user_id = ?";
        $result = $this->db->fetchOne($sql, [$userId]);
        return $result ? (float)$result['total'] : 0;
    }
    
    /**
     * Thêm sản phẩm vào giỏ
     */
    public function addToCart($userId, $productId, $quantity = 1) {
        // Kiểm tra sản phẩm đã có trong giỏ chưa
        $sql = "SELECT * FROM cart WHERE user_id = ? AND product_id = ?";
        $existing = $this->db->fetchOne($sql, [$userId, $productId]);
        
        if ($existing) {
            // Cập nhật số lượng
            $newQuantity = $existing['quantity'] + $quantity;
            $sql = "UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?";
            $result = $this->db->query($sql, [$newQuantity, $existing['id']]);
        } else {
            // Thêm mới
            $sql = "INSERT INTO cart (user_id, product_id, quantity, created_at) 
                    VALUES (?, ?, ?, NOW())";
            $result = $this->db->query($sql, [$userId, $productId, $quantity]);
        }
        
        if ($result) {
            return ['success' => true, 'message' => 'Đã thêm sản phẩm vào giỏ hàng'];
        } else {
            return ['success' => false, 'message' => 'Có lỗi xảy ra khi thêm vào giỏ hàng'];
        }
    }
    
    /**
     * Cập nhật số lượng sản phẩm
     */
    public function updateCartItem($userId, $productId, $quantity) {
        if ($quantity <= 0) {
            return $this->removeFromCart($userId, $productId);
        }
        
        $sql = "UPDATE cart SET quantity = ?, updated_at = NOW() 
                WHERE user_id = ? AND product_id = ?";
        return $this->db->query($sql, [$quantity, $userId, $productId]);
    }
    
    /**
     * Xóa sản phẩm khỏi giỏ
     */
    public function removeFromCart($userId, $productId) {
        $sql = "DELETE FROM cart WHERE user_id = ? AND product_id = ?";
        if ($this->db->query($sql, [$userId, $productId])) {
            return ['success' => true, 'message' => 'Đã xóa sản phẩm khỏi giỏ hàng'];
        } else {
            return ['success' => false, 'message' => 'Có lỗi xảy ra khi xóa sản phẩm'];
        }
    }
    
    /**
     * Xóa toàn bộ giỏ hàng
     */
    public function clearCart($userId) {
        $sql = "DELETE FROM cart WHERE user_id = ?";
        return $this->db->query($sql, [$userId]);
    }
    
    /**
     * Kiểm tra tồn kho trước khi checkout
     */
    public function validateCartStock($userId) {
        $sql = "SELECT c.product_id, c.quantity as cart_qty, 
                p.quantity as stock_qty, p.name
                FROM cart c
                JOIN products p ON c.product_id = p.id
                WHERE c.user_id = ?";
        
        $items = $this->db->fetchAll($sql, [$userId]);
        $errors = [];
        
        foreach ($items as $item) {
            if ($item['cart_qty'] > $item['stock_qty']) {
                $errors[] = $item['name'] . ' chỉ còn ' . $item['stock_qty'] . ' sản phẩm';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * Merge session cart vào database khi đăng nhập
     */
    public function mergeSessionCart($userId) {
        $sessionCart = $this->getSessionCart();
        
        if (empty($sessionCart)) {
            return;
        }
        
        foreach ($sessionCart as $productId => $quantity) {
            $this->addToCart($userId, $productId, $quantity);
        }
        
        // Xóa session cart
        unset($_SESSION['cart']);
    }
    
    /**
     * Thêm vào session cart (user chưa đăng nhập)
     */
    public function addToSessionCart($productId, $quantity = 1) {
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        if (isset($_SESSION['cart'][$productId])) {
            $_SESSION['cart'][$productId] += $quantity;
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
        
        return ['success' => true, 'message' => 'Đã thêm sản phẩm vào giỏ hàng'];
    }
    
    /**
     * Cập nhật session cart
     */
    public function updateSessionCart($productId, $quantity) {
        if ($quantity <= 0) {
            unset($_SESSION['cart'][$productId]);
        } else {
            $_SESSION['cart'][$productId] = $quantity;
        }
    }
    
    /**
     * Xóa khỏi session cart
     */
    public function removeFromSessionCart($productId) {
        unset($_SESSION['cart'][$productId]);
    }
    
    /**
     * Xóa toàn bộ session cart
     */
    public function clearSessionCart() {
        unset($_SESSION['cart']);
    }
    
    /**
     * Đếm session cart
     */
    public function countSessionCart() {
        $cart = $this->getSessionCart();
        return array_sum($cart);
    }
}