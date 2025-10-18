<?php
// models/Order.php - Class quản lý đơn hàng

require_once __DIR__ . '/../includes/Database.php';

class Order {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Tạo đơn hàng mới
    public function createOrder($data) {
        $this->db->beginTransaction();
        
        try {
            // Tạo mã đơn hàng
            $orderCode = 'DA' . date('YmdHis') . rand(1000, 9999);
            
            // Thêm đơn hàng
            $sql = "INSERT INTO orders (user_id, order_code, customer_name, customer_email, 
                    customer_phone, customer_address, coupon_id, coupon_discount, subtotal, 
                    total, payment_method, payment_status, order_status, note) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $data['user_id'] ?? null,
                $orderCode,
                $data['customer_name'],
                $data['customer_email'] ?? '',
                $data['customer_phone'],
                $data['customer_address'],
                $data['coupon_id'] ?? null,
                $data['coupon_discount'] ?? 0,
                $data['subtotal'],
                $data['total'],
                $data['payment_method'] ?? 'cod',
                $data['payment_status'] ?? 'pending',
                'pending',
                $data['note'] ?? ''
            ];
            
            $this->db->query($sql, $params);
            $orderId = $this->db->lastInsertId();
            
            // Thêm chi tiết đơn hàng
            foreach ($data['items'] as $item) {
                $sqlDetail = "INSERT INTO order_details (order_id, product_id, product_name, 
                             product_price, quantity, total) 
                             VALUES (?, ?, ?, ?, ?, ?)";
                
                $this->db->query($sqlDetail, [
                    $orderId,
                    $item['product_id'],
                    $item['product_name'],
                    $item['product_price'],
                    $item['quantity'],
                    $item['total']
                ]);
                
                // Giảm số lượng sản phẩm
                $sqlUpdate = "UPDATE products SET quantity = quantity - ?, sold_quantity = sold_quantity + ? 
                             WHERE id = ?";
                $this->db->query($sqlUpdate, [$item['quantity'], $item['quantity'], $item['product_id']]);
            }
            
            // Cập nhật mã giảm giá đã sử dụng
            if (!empty($data['coupon_id'])) {
                $sqlCoupon = "UPDATE coupons SET used_quantity = used_quantity + 1 WHERE id = ?";
                $this->db->query($sqlCoupon, [$data['coupon_id']]);
            }
            
            $this->db->commit();
            
            return [
                'success' => true, 
                'message' => 'Đặt hàng thành công',
                'order_id' => $orderId,
                'order_code' => $orderCode
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Đặt hàng thất bại: ' . $e->getMessage()];
        }
    }
    
    // Lấy đơn hàng theo ID
    public function getOrderById($id) {
        $sql = "SELECT o.*, u.username, u.email as user_email 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    // Lấy đơn hàng theo mã đơn
    public function getOrderByCode($orderCode) {
        $sql = "SELECT o.*, u.username 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE o.order_code = ?";
        return $this->db->fetchOne($sql, [$orderCode]);
    }
    
    // Lấy chi tiết đơn hàng
    public function getOrderDetails($orderId) {
        $sql = "SELECT od.*, p.image, p.slug 
                FROM order_details od 
                LEFT JOIN products p ON od.product_id = p.id 
                WHERE od.order_id = ?";
        return $this->db->fetchAll($sql, [$orderId]);
    }
    
    // Lấy lịch sử đơn hàng của khách
    public function getOrdersByUser($userId) {
        $sql = "SELECT * FROM orders 
                WHERE user_id = ? 
                ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, [$userId]);
    }
    
    // Lấy tất cả đơn hàng (admin)
    public function getAllOrders($status = null, $limit = null, $offset = 0) {
        $sql = "SELECT o.*, u.username 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id";
        
        $params = [];
        
        if ($status) {
            $sql .= " WHERE o.order_status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            $params[] = $limit;
            $params[] = $offset;
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    // Cập nhật trạng thái đơn hàng
    public function updateOrderStatus($orderId, $status) {
        $validStatuses = ['pending', 'confirmed', 'processing', 'shipping', 'delivered', 'cancelled'];
        
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'Trạng thái không hợp lệ'];
        }
        
        $sql = "UPDATE orders SET order_status = ?, updated_at = NOW() WHERE id = ?";
        
        if ($this->db->query($sql, [$status, $orderId])) {
            return ['success' => true, 'message' => 'Cập nhật trạng thái thành công'];
        }
        
        return ['success' => false, 'message' => 'Cập nhật trạng thái thất bại'];
    }
    
    // Cập nhật trạng thái thanh toán
    public function updatePaymentStatus($orderId, $status) {
        $validStatuses = ['pending', 'paid', 'failed'];
        
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'Trạng thái không hợp lệ'];
        }
        
        $sql = "UPDATE orders SET payment_status = ?, updated_at = NOW() WHERE id = ?";
        
        if ($this->db->query($sql, [$status, $orderId])) {
            return ['success' => true, 'message' => 'Cập nhật trạng thái thanh toán thành công'];
        }
        
        return ['success' => false, 'message' => 'Cập nhật trạng thái thanh toán thất bại'];
    }
    
    // Hủy đơn hàng
    public function cancelOrder($orderId) {
        $this->db->beginTransaction();
        
        try {
            $order = $this->getOrderById($orderId);
            
            if (!$order) {
                throw new Exception('Đơn hàng không tồn tại');
            }
            
            if (!in_array($order['order_status'], ['pending', 'confirmed'])) {
                throw new Exception('Không thể hủy đơn hàng đã xử lý');
            }
            
            // Cập nhật trạng thái
            $sql = "UPDATE orders SET order_status = 'cancelled', updated_at = NOW() WHERE id = ?";
            $this->db->query($sql, [$orderId]);
            
            // Hoàn lại số lượng sản phẩm
            $details = $this->getOrderDetails($orderId);
            foreach ($details as $item) {
                $sqlUpdate = "UPDATE products SET quantity = quantity + ?, sold_quantity = sold_quantity - ? 
                             WHERE id = ?";
                $this->db->query($sqlUpdate, [$item['quantity'], $item['quantity'], $item['product_id']]);
            }
            
            // Hoàn lại mã giảm giá
            if ($order['coupon_id']) {
                $sqlCoupon = "UPDATE coupons SET used_quantity = used_quantity - 1 WHERE id = ?";
                $this->db->query($sqlCoupon, [$order['coupon_id']]);
            }
            
            $this->db->commit();
            return ['success' => true, 'message' => 'Hủy đơn hàng thành công'];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    // Đếm đơn hàng theo trạng thái
    public function countOrdersByStatus($status = null) {
        $sql = "SELECT COUNT(*) as total FROM orders";
        $params = [];
        
        if ($status) {
            $sql .= " WHERE order_status = ?";
            $params[] = $status;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['total'] ?? 0;
    }
    
    // Thống kê doanh thu theo tháng
    public function getMonthlyRevenue($year = null) {
        if (!$year) {
            $year = date('Y');
        }
        
        $sql = "SELECT 
                MONTH(created_at) as month,
                SUM(total) as revenue,
                COUNT(*) as order_count
                FROM orders 
                WHERE YEAR(created_at) = ? 
                AND payment_status = 'paid'
                AND order_status != 'cancelled'
                GROUP BY MONTH(created_at)
                ORDER BY month ASC";
        
        return $this->db->fetchAll($sql, [$year]);
    }
    
    // Thống kê doanh thu theo ngày
    public function getDailyRevenue($month = null, $year = null) {
        if (!$month) $month = date('m');
        if (!$year) $year = date('Y');
        
        $sql = "SELECT 
                DATE(created_at) as date,
                SUM(total) as revenue,
                COUNT(*) as order_count
                FROM orders 
                WHERE MONTH(created_at) = ? 
                AND YEAR(created_at) = ?
                AND payment_status = 'paid'
                AND order_status != 'cancelled'
                GROUP BY DATE(created_at)
                ORDER BY date ASC";
        
        return $this->db->fetchAll($sql, [$month, $year]);
    }
    
    // Tổng doanh thu
    public function getTotalRevenue($startDate = null, $endDate = null) {
        $sql = "SELECT SUM(total) as total_revenue 
                FROM orders 
                WHERE payment_status = 'paid'
                AND order_status != 'cancelled'";
        
        $params = [];
        
        if ($startDate && $endDate) {
            $sql .= " AND created_at BETWEEN ? AND ?";
            $params = [$startDate, $endDate];
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['total_revenue'] ?? 0;
    }
    
    // Sản phẩm bán chạy
    public function getTopSellingProducts($limit = 10) {
        $sql = "SELECT 
                od.product_id,
                od.product_name,
                SUM(od.quantity) as total_sold,
                SUM(od.total) as total_revenue,
                p.image
                FROM order_details od
                LEFT JOIN products p ON od.product_id = p.id
                INNER JOIN orders o ON od.order_id = o.id
                WHERE o.order_status != 'cancelled'
                GROUP BY od.product_id, od.product_name, p.image
                ORDER BY total_sold DESC
                LIMIT ?";
        
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    // Tìm kiếm đơn hàng
    public function searchOrders($keyword, $status = null) {
        $sql = "SELECT o.*, u.username 
                FROM orders o 
                LEFT JOIN users u ON o.user_id = u.id 
                WHERE (o.order_code LIKE ? 
                OR o.customer_name LIKE ? 
                OR o.customer_phone LIKE ? 
                OR o.customer_email LIKE ?)";
        
        $params = ["%$keyword%", "%$keyword%", "%$keyword%", "%$keyword%"];
        
        if ($status) {
            $sql .= " AND o.order_status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY o.created_at DESC";
        
        return $this->db->fetchAll($sql, $params);
    }
    
    // Lấy thông tin đơn hàng đầy đủ (cho in hóa đơn)
    public function getFullOrderInfo($orderId) {
        $order = $this->getOrderById($orderId);
        if (!$order) {
            return null;
        }
        
        $order['details'] = $this->getOrderDetails($orderId);
        
        return $order;
    }
}
?>