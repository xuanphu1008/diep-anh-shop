<?php
// models/Category.php - Class quản lý danh mục

require_once __DIR__ . '/../includes/Database.php';

class Category {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Lấy tất cả danh mục
    public function getAllCategories() {
        $sql = "SELECT * FROM categories WHERE deleted_at IS NULL ORDER BY name ASC";
        return $this->db->fetchAll($sql);
    }
    
    // Lấy danh mục theo ID
    public function getCategoryById($id) {
        $sql = "SELECT * FROM categories WHERE id = ? AND deleted_at IS NULL";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    // Lấy danh mục theo slug
    public function getCategoryBySlug($slug) {
        $sql = "SELECT * FROM categories WHERE slug = ? AND deleted_at IS NULL AND status = 1";
        return $this->db->fetchOne($sql, [$slug]);
    }
    
    // Thêm danh mục
    public function addCategory($data) {
        $sql = "INSERT INTO categories (name, slug, description, status, parent_id) VALUES (?, ?, ?, ?, ?)";
        $params = [
            $data['name'],
            $this->createSlug($data['name']),
            $data['description'] ?? '',
            $data['status'] ?? 1,
            $data['parent_id'] ?? null
        ];
        
        if ($this->db->query($sql, $params)) {
            return ['success' => true, 'message' => 'Thêm danh mục thành công'];
        }
        return ['success' => false, 'message' => 'Thêm danh mục thất bại'];
    }
    
    // Cập nhật danh mục
    public function updateCategory($id, $data) {
        $sql = "UPDATE categories SET name = ?, slug = ?, description = ?, status = ?, parent_id = ? WHERE id = ?";
        $params = [
            $data['name'],
            $this->createSlug($data['name']),
            $data['description'] ?? '',
            $data['status'] ?? 1,
            $data['parent_id'] ?? null,
            $id
        ];
        
        if ($this->db->query($sql, $params)) {
            return ['success' => true, 'message' => 'Cập nhật danh mục thành công'];
        }
        return ['success' => false, 'message' => 'Cập nhật danh mục thất bại'];
    }

    // Lấy danh sách dùng cho dropdown (bao gồm cả ẩn nếu cần)
    public function getCategoriesForDropdown($includeDisabled = false) {
        $sql = "SELECT id, name, parent_id FROM categories WHERE deleted_at IS NULL";
        if (!$includeDisabled) {
            $sql .= " AND status = 1";
        }
        $sql .= " ORDER BY name ASC";
        return $this->db->fetchAll($sql);
    }
    
    // Xóa mềm danh mục
    public function deleteCategory($id) {
        $sql = "UPDATE categories SET deleted_at = NOW() WHERE id = ?";
        if ($this->db->query($sql, [$id])) {
            return ['success' => true, 'message' => 'Xóa danh mục thành công'];
        }
        return ['success' => false, 'message' => 'Xóa danh mục thất bại'];
    }
    
    // Khôi phục danh mục
    public function restoreCategory($id) {
        $sql = "UPDATE categories SET deleted_at = NULL WHERE id = ?";
        if ($this->db->query($sql, [$id])) {
            return ['success' => true, 'message' => 'Khôi phục danh mục thành công'];
        }
        return ['success' => false, 'message' => 'Khôi phục danh mục thất bại'];
    }
    
    // Đếm sản phẩm trong danh mục
    public function countProducts($categoryId) {
        $sql = "SELECT COUNT(*) as total FROM products 
                WHERE category_id = ? AND deleted_at IS NULL AND is_active = 1";
        $result = $this->db->fetchOne($sql, [$categoryId]);
        return $result['total'] ?? 0;
    }
    
    // Tạo slug
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
        return trim($string, '-');
    }
}
?>