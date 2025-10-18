<?php
class Comment {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getCommentsByProduct($productId) {
        $sql = "SELECT c.*, u.username, u.full_name 
                FROM comments c 
                LEFT JOIN users u ON c.user_id = u.id 
                WHERE c.product_id = ? AND c.status = 1
                ORDER BY c.created_at DESC";
        return $this->db->fetchAll($sql, [$productId]);
    }
    
    public function addComment($data) {
        // Kiểm tra user đã mua sản phẩm chưa
        if (!$this->hasUserPurchased($data['user_id'], $data['product_id'])) {
            return ['success' => false, 'message' => 'Bạn cần mua sản phẩm trước khi đánh giá'];
        }
        
        $sql = "INSERT INTO comments (product_id, user_id, content, rating, status) 
                VALUES (?, ?, ?, ?, 1)";
        $params = [
            $data['product_id'],
            $data['user_id'],
            $data['content'],
            $data['rating'] ?? 5
        ];
        
        if ($this->db->query($sql, $params)) {
            return ['success' => true, 'message' => 'Đánh giá thành công'];
        }
        return ['success' => false, 'message' => 'Đánh giá thất bại'];
    }
    
    public function deleteComment($id) {
        $sql = "DELETE FROM comments WHERE id = ?";
        if ($this->db->query($sql, [$id])) {
            return ['success' => true, 'message' => 'Xóa bình luận thành công'];
        }
        return ['success' => false, 'message' => 'Xóa bình luận thất bại'];
    }
    
    public function getAverageRating($productId) {
        $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_reviews 
                FROM comments 
                WHERE product_id = ? AND status = 1";
        $result = $this->db->fetchOne($sql, [$productId]);
        
        return [
            'avg_rating' => round($result['avg_rating'] ?? 0, 1),
            'total_reviews' => $result['total_reviews'] ?? 0
        ];
    }
    
    private function hasUserPurchased($userId, $productId) {
        $sql = "SELECT COUNT(*) as total 
                FROM order_details od
                INNER JOIN orders o ON od.order_id = o.id
                WHERE o.user_id = ? 
                AND od.product_id = ? 
                AND o.order_status = 'delivered'";
        
        $result = $this->db->fetchOne($sql, [$userId, $productId]);
        return ($result['total'] ?? 0) > 0;
    }
}
?>
