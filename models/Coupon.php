<?php
// models/Coupon.php - Class quản lý mã giảm giá

require_once __DIR__ . '/../includes/Database.php';

class Coupon {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Lấy tất cả mã giảm giá
    public function getAllCoupons() {
        $sql = "SELECT * FROM coupons WHERE deleted_at IS NULL ORDER BY created_at DESC";
        return $this->db->fetchAll($sql);
    }
    
    // Lấy mã giảm giá theo ID
    public function getCouponById($id) {
        $sql = "SELECT * FROM coupons WHERE id = ? AND deleted_at IS NULL";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    // Lấy mã giảm giá theo code
    public function getCouponByCode($code) {
        $sql = "SELECT * FROM coupons WHERE code = ? AND deleted_at IS NULL";
        return $this->db->fetchOne($sql, [$code]);
    }
    
    // Kiểm tra và áp dụng mã giảm giá
    public function applyCoupon($code, $orderTotal) {
        $coupon = $this->getCouponByCode($code);
        
        if (!$coupon) {
            return ['success' => false, 'message' => 'Mã giảm giá không tồn tại'];
        }
        
        // Kiểm tra trạng thái
        if ($coupon['status'] != 1) {
            return ['success' => false, 'message' => 'Mã giảm giá không khả dụng'];
        }
        
        // Kiểm tra số lượng
        if ($coupon['used_quantity'] >= $coupon['quantity']) {
            return ['success' => false, 'message' => 'Mã giảm giá đã hết lượt sử dụng'];
        }
        
        // Kiểm tra ngày bắt đầu
        if ($coupon['start_date'] && strtotime($coupon['start_date']) > time()) {
            return ['success' => false, 'message' => 'Mã giảm giá chưa có hiệu lực'];
        }
        
        // Kiểm tra ngày hết hạn
        if ($coupon['end_date'] && strtotime($coupon['end_date']) < time()) {
            return ['success' => false, 'message' => 'Mã giảm giá đã hết hạn'];
        }
        
        // Kiểm tra giá trị đơn hàng tối thiểu
        if ($orderTotal < $coupon['min_order_value']) {
            return [
                'success' => false, 
                'message' => 'Đơn hàng tối thiểu ' . number_format($coupon['min_order_value']) . 'đ'
            ];
        }
        
        // Tính giảm giá
        $discount = 0;
        if ($coupon['type'] == 'percent') {
            $discount = ($orderTotal * $coupon['value']) / 100;
            
            // Kiểm tra giảm tối đa
            if ($coupon['max_discount'] && $discount > $coupon['max_discount']) {
                $discount = $coupon['max_discount'];
            }
        } else {
            $discount = $coupon['value'];
        }
        
        return [
            'success' => true,
            'coupon_id' => $coupon['id'],
            'coupon_code' => $coupon['code'],
            'discount' => $discount,
            'message' => 'Áp dụng mã giảm giá thành công'
        ];
    }
    
    // Thêm mã giảm giá
    public function addCoupon($data) {
        // Kiểm tra code đã tồn tại
        if ($this->getCouponByCode($data['code'])) {
            return ['success' => false, 'message' => 'Mã giảm giá đã tồn tại'];
        }
        
        $sql = "INSERT INTO coupons (code, type, value, min_order_value, max_discount, 
                quantity, start_date, end_date, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            strtoupper($data['code']),
            $data['type'] ?? 'percent',
            $data['value'],
            $data['min_order_value'] ?? 0,
            $data['max_discount'] ?? null,
            $data['quantity'] ?? 1,
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['status'] ?? 1
        ];
        
        if ($this->db->query($sql, $params)) {
            return ['success' => true, 'message' => 'Thêm mã giảm giá thành công'];
        }
        
        return ['success' => false, 'message' => 'Thêm mã giảm giá thất bại'];
    }
    
    // Cập nhật mã giảm giá
    public function updateCoupon($id, $data) {
        $sql = "UPDATE coupons SET 
                code = ?,
                type = ?,
                value = ?,
                min_order_value = ?,
                max_discount = ?,
                quantity = ?,
                start_date = ?,
                end_date = ?,
                status = ?
                WHERE id = ?";
        
        $params = [
            strtoupper($data['code']),
            $data['type'] ?? 'percent',
            $data['value'],
            $data['min_order_value'] ?? 0,
            $data['max_discount'] ?? null,
            $data['quantity'] ?? 1,
            $data['start_date'] ?? null,
            $data['end_date'] ?? null,
            $data['status'] ?? 1,
            $id
        ];
        
        if ($this->db->query($sql, $params)) {
            return ['success' => true, 'message' => 'Cập nhật mã giảm giá thành công'];
        }
        
        return ['success' => false, 'message' => 'Cập nhật mã giảm giá thất bại'];
    }
    
    // Xóa mềm mã giảm giá
    public function deleteCoupon($id) {
        $sql = "UPDATE coupons SET deleted_at = NOW() WHERE id = ?";
        
        if ($this->db->query($sql, [$id])) {
            return ['success' => true, 'message' => 'Xóa mã giảm giá thành công'];
        }
        
        return ['success' => false, 'message' => 'Xóa mã giảm giá thất bại'];
    }
    
    // Khôi phục mã giảm giá
    public function restoreCoupon($id) {
        $sql = "UPDATE coupons SET deleted_at = NULL WHERE id = ?";
        
        if ($this->db->query($sql, [$id])) {
            return ['success' => true, 'message' => 'Khôi phục mã giảm giá thành công'];
        }
        
        return ['success' => false, 'message' => 'Khôi phục mã giảm giá thất bại'];
    }
    
    // Lấy mã giảm giá đang hoạt động
    public function getActiveCoupons() {
        $sql = "SELECT * FROM coupons 
                WHERE status = 1 
                AND deleted_at IS NULL
                AND (start_date IS NULL OR start_date <= NOW())
                AND (end_date IS NULL OR end_date >= NOW())
                AND used_quantity < quantity
                ORDER BY created_at DESC";
        
        return $this->db->fetchAll($sql);
    }
    
    // Tạo mã giảm giá ngẫu nhiên
    public function generateCouponCode($prefix = 'DA', $length = 6) {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $code = $prefix;
        
        for ($i = 0; $i < $length; $i++) {
            $code .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        // Kiểm tra trùng
        if ($this->getCouponByCode($code)) {
            return $this->generateCouponCode($prefix, $length);
        }
        
        return $code;
    }
}
?>