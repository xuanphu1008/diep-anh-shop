<?php
class Rating {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Thêm đánh giá mới
    public function addRating($data) {
        $sql = "INSERT INTO comments (product_id, user_id, content, rating, status) 
                VALUES (?, ?, ?, ?, ?)";
        $params = [
            $data['product_id'],
            $data['user_id'],
            $data['content'] ?? '',
            (int)$data['rating'],
            $data['status'] ?? 1
        ];
        
        if ($this->db->query($sql, $params)) {
            return ['success' => true, 'message' => 'Đánh giá thành công'];
        }
        return ['success' => false, 'message' => 'Đánh giá thất bại'];
    }
    
    // Cập nhật đánh giá
    public function updateRating($id, $data) {
        $sql = "UPDATE comments SET content = ?, rating = ?, status = ? WHERE id = ?";
        $params = [
            $data['content'] ?? '',
            (int)$data['rating'],
            $data['status'] ?? 1,
            $id
        ];
        
        return $this->db->query($sql, $params);
    }
    
    // Xóa đánh giá
    public function deleteRating($id) {
        $sql = "DELETE FROM comments WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    // Lấy đánh giá theo ID
    public function getRatingById($id) {
        $sql = "SELECT c.*, u.username, u.full_name, p.name as product_name 
                FROM comments c
                JOIN users u ON c.user_id = u.id
                JOIN products p ON c.product_id = p.id
                WHERE c.id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    // Lấy tất cả đánh giá của sản phẩm (chỉ những đánh giá được duyệt)
    public function getRatingsByProduct($product_id, $limit = 10, $offset = 0) {
        $sql = "SELECT c.*, u.username, u.full_name 
                FROM comments c
                JOIN users u ON c.user_id = u.id
                WHERE c.product_id = ? AND c.status = 1
                ORDER BY c.created_at DESC
                LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [$product_id, (int)$limit, (int)$offset]);
    }
    
    // Đếm đánh giá theo sản phẩm
    public function countRatingsByProduct($product_id) {
        $sql = "SELECT COUNT(*) as total FROM comments WHERE product_id = ? AND status = 1";
        $result = $this->db->fetchOne($sql, [$product_id]);
        return $result['total'] ?? 0;
    }
    
    // Lấy điểm đánh giá trung bình
    public function getAverageRating($product_id) {
        $sql = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings 
                FROM comments 
                WHERE product_id = ? AND status = 1";
        return $this->db->fetchOne($sql, [$product_id]);
    }
    
    // Lấy phân bố đánh giá (số lượng theo từng mức sao)
    public function getRatingDistribution($product_id) {
        $sql = "SELECT rating, COUNT(*) as count 
                FROM comments 
                WHERE product_id = ? AND status = 1
                GROUP BY rating 
                ORDER BY rating DESC";
        return $this->db->fetchAll($sql, [$product_id]);
    }
    
    // Kiểm tra user đã đánh giá sản phẩm này chưa
    public function hasUserRated($product_id, $user_id) {
        $sql = "SELECT id FROM comments WHERE product_id = ? AND user_id = ?";
        return $this->db->fetchOne($sql, [$product_id, $user_id]) ? true : false;
    }
    
    // Lấy đánh giá của user cho sản phẩm
    public function getUserRating($product_id, $user_id) {
        $sql = "SELECT * FROM comments WHERE product_id = ? AND user_id = ?";
        return $this->db->fetchOne($sql, [$product_id, $user_id]);
    }
    
    // Lấy tất cả đánh giá chưa duyệt (admin)
    public function getUnverifiedRatings($limit = 20, $offset = 0) {
        $sql = "SELECT c.*, u.username, u.full_name, p.name as product_name 
                FROM comments c
                JOIN users u ON c.user_id = u.id
                JOIN products p ON c.product_id = p.id
                WHERE c.status = 0
                ORDER BY c.created_at DESC
                LIMIT ? OFFSET ?";
        return $this->db->fetchAll($sql, [(int)$limit, (int)$offset]);
    }
    
    // Đếm đánh giá chưa duyệt
    public function countUnverifiedRatings() {
        $sql = "SELECT COUNT(*) as total FROM comments WHERE status = 0";
        $result = $this->db->fetchOne($sql);
        return $result['total'] ?? 0;
    }
    
    // Duyệt đánh giá
    public function approveRating($id) {
        $sql = "UPDATE comments SET status = 1 WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    // Từ chối đánh giá
    public function rejectRating($id) {
        $sql = "UPDATE comments SET status = 0 WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    // Lấy tất cả đánh giá (cho admin)
    public function getAllRatings($filters = [], $limit = 20, $offset = 0) {
        $where = "WHERE 1=1";
        $params = [];
        
        if (!empty($filters['product_id'])) {
            $where .= " AND c.product_id = ?";
            $params[] = $filters['product_id'];
        }
        
        if (!empty($filters['status']) || $filters['status'] === '0') {
            $where .= " AND c.status = ?";
            $params[] = (int)$filters['status'];
        }
        
        if (!empty($filters['keyword'])) {
            $where .= " AND (u.username LIKE ? OR p.name LIKE ? OR c.content LIKE ?)";
            $kw = '%' . $filters['keyword'] . '%';
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }
        
        $sql = "SELECT c.*, u.username, u.full_name, p.name as product_name 
                FROM comments c
                JOIN users u ON c.user_id = u.id
                JOIN products p ON c.product_id = p.id
                $where
                ORDER BY c.created_at DESC
                LIMIT ? OFFSET ?";
        
        $params[] = (int)$limit;
        $params[] = (int)$offset;
        
        return $this->db->fetchAll($sql, $params);
    }
    
    // Đếm tất cả đánh giá
    public function countAllRatings($filters = []) {
        $where = "WHERE 1=1";
        $params = [];
        
        if (!empty($filters['product_id'])) {
            $where .= " AND c.product_id = ?";
            $params[] = $filters['product_id'];
        }
        
        if (!empty($filters['status']) || $filters['status'] === '0') {
            $where .= " AND c.status = ?";
            $params[] = (int)$filters['status'];
        }
        
        if (!empty($filters['keyword'])) {
            $where .= " AND (u.username LIKE ? OR p.name LIKE ? OR c.content LIKE ?)";
            $kw = '%' . $filters['keyword'] . '%';
            $params[] = $kw;
            $params[] = $kw;
            $params[] = $kw;
        }
        
        $sql = "SELECT COUNT(*) as total FROM comments c
                JOIN users u ON c.user_id = u.id
                JOIN products p ON c.product_id = p.id
                $where";
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['total'] ?? 0;
    }
}
?>
