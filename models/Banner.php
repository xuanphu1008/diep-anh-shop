<?php
require_once 'models/Banner.php';
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
}
?>