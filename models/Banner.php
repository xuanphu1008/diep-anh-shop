<?php
class Banner {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getAllBanners() {
        $sql = "SELECT * FROM banners WHERE deleted_at IS NULL ORDER BY position ASC";
        return $this->db->fetchAll($sql);
    }
    
    public function getActiveBanners() {
        $sql = "SELECT * FROM banners WHERE deleted_at IS NULL AND status = 1 ORDER BY position ASC";
        return $this->db->fetchAll($sql);
    }
    
    public function addBanner($data) {
        $sql = "INSERT INTO banners (title, image, link, position, status) VALUES (?, ?, ?, ?, ?)";
        $params = [
            $data['title'] ?? '',
            $data['image'],
            $data['link'] ?? '',
            $data['position'] ?? 0,
            $data['status'] ?? 1
        ];
        
        if ($this->db->query($sql, $params)) {
            return ['success' => true, 'message' => 'Thêm banner thành công'];
        }
        return ['success' => false, 'message' => 'Thêm banner thất bại'];
    }
    
    public function updateBanner($id, $data) {
        $sql = "UPDATE banners SET title = ?, image = ?, link = ?, position = ?, status = ? WHERE id = ?";
        $params = [
            $data['title'] ?? '',
            $data['image'],
            $data['link'] ?? '',
            $data['position'] ?? 0,
            $data['status'] ?? 1,
            $id
        ];
        
        if ($this->db->query($sql, $params)) {
            return ['success' => true, 'message' => 'Cập nhật banner thành công'];
        }
        return ['success' => false, 'message' => 'Cập nhật banner thất bại'];
    }
    
    public function deleteBanner($id) {
        $sql = "UPDATE banners SET deleted_at = NOW() WHERE id = ?";
        if ($this->db->query($sql, [$id])) {
            return ['success' => true, 'message' => 'Xóa banner thành công'];
        }
        return ['success' => false, 'message' => 'Xóa banner thất bại'];
    }
    
    public function restoreBanner($id) {
        $sql = "UPDATE banners SET deleted_at = NULL WHERE id = ?";
        if ($this->db->query($sql, [$id])) {
            return ['success' => true, 'message' => 'Khôi phục banner thành công'];
        }
        return ['success' => false, 'message' => 'Khôi phục banner thất bại'];
    }

    // Admin helpers: count and paginated fetch with filters
    public function countAdminBanners($filters = []) {
        $where = "WHERE deleted_at IS NULL";
        $params = [];
        if (!empty($filters['keyword'])) {
            $where .= " AND title LIKE ?";
            $params[] = '%' . $filters['keyword'] . '%';
        }
        if ($filters['status'] !== '' && $filters['status'] !== null) {
            $where .= " AND status = ?";
            $params[] = (int)$filters['status'];
        }
        $sql = "SELECT COUNT(*) as cnt FROM banners " . $where;
        $row = $this->db->fetchOne($sql, $params);
        return $row ? (int)$row['cnt'] : 0;
    }

    public function getAdminBanners($filters = [], $limit = 20, $offset = 0) {
        $where = "WHERE deleted_at IS NULL";
        $params = [];
        if (!empty($filters['keyword'])) {
            $where .= " AND title LIKE ?";
            $params[] = '%' . $filters['keyword'] . '%';
        }
        if ($filters['status'] !== '' && $filters['status'] !== null) {
            $where .= " AND status = ?";
            $params[] = (int)$filters['status'];
        }
        $sql = "SELECT * FROM banners " . $where . " ORDER BY position ASC LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;
        return $this->db->fetchAll($sql, $params);
    }

    public function getBannerById($id) {
        $sql = "SELECT * FROM banners WHERE id = ? AND deleted_at IS NULL";
        return $this->db->fetchOne($sql, [$id]);
    }
}
?>