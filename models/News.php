<?php
require_once 'models/News.php';
class News {
    public $db;

    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getAllNews($limit = null) {
        $sql = "SELECT n.*, u.username as author_name 
                FROM news n 
                LEFT JOIN users u ON n.author_id = u.id 
                WHERE n.deleted_at IS NULL AND n.status = 1
                ORDER BY n.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            return $this->db->fetchAll($sql, [$limit]);
        }
        
        return $this->db->fetchAll($sql);
    }
    
    public function getNewsById($id) {
        $sql = "SELECT n.*, u.username as author_name 
                FROM news n 
                LEFT JOIN users u ON n.author_id = u.id 
                WHERE n.id = ? AND n.deleted_at IS NULL";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    public function getNewsBySlug($slug) {
        $sql = "SELECT n.*, u.username as author_name 
                FROM news n 
                LEFT JOIN users u ON n.author_id = u.id 
                WHERE n.slug = ? AND n.deleted_at IS NULL AND n.status = 1";
        
        // Tăng views
        $news = $this->db->fetchOne($sql, [$slug]);
        if ($news) {
            $this->db->query("UPDATE news SET views = views + 1 WHERE id = ?", [$news['id']]);
        }
        
        return $news;
    }
    
    public function addNews($data) {
        $sql = "INSERT INTO news (title, slug, content, image, author_id, status) 
                VALUES (?, ?, ?, ?, ?, ?)";
        $params = [
            $data['title'],
            $this->createSlug($data['title']),
            $data['content'],
            $data['image'] ?? '',
            $data['author_id'],
            $data['status'] ?? 1
        ];
        
        if ($this->db->query($sql, $params)) {
            return ['success' => true, 'message' => 'Thêm tin tức thành công'];
        }
        return ['success' => false, 'message' => 'Thêm tin tức thất bại'];
    }
    
    public function updateNews($id, $data) {
        $sql = "UPDATE news SET title = ?, slug = ?, content = ?, image = ?, status = ?, updated_at = NOW() 
                WHERE id = ?";
        $params = [
            $data['title'],
            $this->createSlug($data['title']),
            $data['content'],
            $data['image'] ?? '',
            $data['status'] ?? 1,
            $id
        ];
        
        if ($this->db->query($sql, $params)) {
            return ['success' => true, 'message' => 'Cập nhật tin tức thành công'];
        }
        return ['success' => false, 'message' => 'Cập nhật tin tức thất bại'];
    }
    
    public function deleteNews($id) {
        $sql = "UPDATE news SET deleted_at = NOW() WHERE id = ?";
        if ($this->db->query($sql, [$id])) {
            return ['success' => true, 'message' => 'Xóa tin tức thành công'];
        }
        return ['success' => false, 'message' => 'Xóa tin tức thất bại'];
    }
    
    public function restoreNews($id) {
        $sql = "UPDATE news SET deleted_at = NULL WHERE id = ?";
        if ($this->db->query($sql, [$id])) {
            return ['success' => true, 'message' => 'Khôi phục tin tức thành công'];
        }
        return ['success' => false, 'message' => 'Khôi phục tin tức thất bại'];
    }
    
    private function createSlug($string) {
        $string = mb_strtolower($string, 'UTF-8');
        $string = preg_replace('/[áàảãạăắằẳẵặâấầẩẫậ]/u', 'a', $string);
        $string = preg_replace('/[éèẻẽẹêếềểễệ]/u', 'e', $string);
        $string = preg_replace('/[íìỉĩị]/u', 'i', $string);
        $string = preg_replace('/[óòỏõọôốồổỗộơớờởỡợ]/u', 'o', $string);
        $string = preg_replace('/[úùủũụưứừửữự]/u', 'u', $string);
        $string = preg_replace('/[ýỳỷỹỵ]/u', 'y', $string);
        $string = preg_replace('/[đ]/u', 'd', $string);
        $string = preg_replace('/[^a-z0-9\s-]/', '', $string);
        $string = preg_replace('/[\s-]+/', '-', $string);
        return trim($string, '-') . '-' . time();
    }
}
?>