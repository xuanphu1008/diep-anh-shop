<?php
// models/Product.php - Class quản lý sản phẩm

require_once __DIR__ . '/../includes/Database.php';

class Product {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Lấy tất cả sản phẩm
    public function getAllProducts($limit = null, $offset = 0) {
        $sql = "SELECT p.*, c.name as category_name, s.name as supplier_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN suppliers s ON p.supplier_id = s.id 
                WHERE p.deleted_at IS NULL AND p.is_active = 1
                ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ? OFFSET ?";
            return $this->db->fetchAll($sql, [$limit, $offset]);
        }
        
        return $this->db->fetchAll($sql);
    }

    // Lấy sản phẩm cho admin với lọc, phân trang và sắp xếp
    public function getAdminProducts($filters = [], $limit = 20, $offset = 0, $sort = 'p.created_at DESC') {
        $sql = "SELECT p.*, c.name as category_name, s.name as supplier_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN suppliers s ON p.supplier_id = s.id 
                WHERE p.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['keyword'])) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.slug LIKE ? )";
            $kw = '%' . $filters['keyword'] . '%';
            $params[] = $kw; $params[] = $kw; $params[] = $kw;
        }

        if (isset($filters['category_id']) && $filters['category_id'] !== '') {
            $sql .= " AND p.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            if ($filters['status'] === 'active') $sql .= " AND p.is_active = 1";
            if ($filters['status'] === 'inactive') $sql .= " AND p.is_active = 0";
        }

        $sql .= " ORDER BY " . $sort . " LIMIT ? OFFSET ?";
        $params[] = (int)$limit;
        $params[] = (int)$offset;

        return $this->db->fetchAll($sql, $params);
    }

    // Đếm sản phẩm cho admin (với cùng filters)
    public function countAdminProducts($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM products p WHERE p.deleted_at IS NULL";
        $params = [];

        if (!empty($filters['keyword'])) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ? OR p.slug LIKE ? )";
            $kw = '%' . $filters['keyword'] . '%';
            $params[] = $kw; $params[] = $kw; $params[] = $kw;
        }

        if (isset($filters['category_id']) && $filters['category_id'] !== '') {
            $sql .= " AND p.category_id = ?";
            $params[] = (int)$filters['category_id'];
        }

        if (isset($filters['status']) && $filters['status'] !== '') {
            if ($filters['status'] === 'active') $sql .= " AND p.is_active = 1";
            if ($filters['status'] === 'inactive') $sql .= " AND p.is_active = 0";
        }

        $res = $this->db->fetchOne($sql, $params);
        return $res['total'] ?? 0;
    }
    
    // Lấy sản phẩm theo ID
    public function getProductById($id) {
        $sql = "SELECT p.*, c.name as category_name, s.name as supplier_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN suppliers s ON p.supplier_id = s.id 
                WHERE p.id = ? AND p.deleted_at IS NULL";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    // Lấy sản phẩm theo slug
    public function getProductBySlug($slug) {
        $sql = "SELECT p.*, c.name as category_name, s.name as supplier_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                LEFT JOIN suppliers s ON p.supplier_id = s.id 
                WHERE p.slug = ? AND p.deleted_at IS NULL AND p.is_active = 1";
        return $this->db->fetchOne($sql, [$slug]);
    }
    
    // Lấy sản phẩm theo danh mục
    public function getProductsByCategory($categoryId, $limit = null) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.category_id = ? AND p.deleted_at IS NULL AND p.is_active = 1
                ORDER BY p.created_at DESC";
        
        if ($limit) {
            $sql .= " LIMIT ?";
            return $this->db->fetchAll($sql, [$categoryId, $limit]);
        }
        
        return $this->db->fetchAll($sql, [$categoryId]);
    }
    
    // Lấy sản phẩm hot
    public function getHotProducts($limit = 8) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.is_hot = 1 AND p.deleted_at IS NULL AND p.is_active = 1
                ORDER BY p.created_at DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    // Lấy sản phẩm bán chạy
    public function getBestSellingProducts($limit = 8) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.deleted_at IS NULL AND p.is_active = 1
                ORDER BY p.sold_quantity DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    // Lấy sản phẩm mới
    public function getNewProducts($limit = 8) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.deleted_at IS NULL AND p.is_active = 1
                ORDER BY p.created_at DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }
    
    // Tìm kiếm sản phẩm
    public function searchProducts($keyword, $categoryId = null, $minPrice = null, $maxPrice = null, $limit = null) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.deleted_at IS NULL AND p.is_active = 1
                AND (p.name LIKE ? OR p.description LIKE ?)";
        
        $params = ["%$keyword%", "%$keyword%"];
        
        if ($categoryId) {
            $sql .= " AND p.category_id = ?";
            $params[] = $categoryId;
        }
        
        if ($minPrice !== null) {
            $sql .= " AND p.price >= ?";
            $params[] = $minPrice;
        }
        
        if ($maxPrice !== null) {
            $sql .= " AND p.price <= ?";
            $params[] = $maxPrice;
        }
        
        $sql .= " ORDER BY 
                CASE 
                    WHEN p.name LIKE ? THEN 1
                    WHEN p.name LIKE ? THEN 2
                    ELSE 3
                END,
                p.sold_quantity DESC,
                p.created_at DESC";
        
        $params[] = "$keyword%";  // Bắt đầu bằng keyword
        $params[] = "%$keyword%";  // Chứa keyword
        
        if ($limit !== null) {
            $sql .= " LIMIT ?";
            $params[] = $limit;
        }
        
        return $this->db->fetchAll($sql, $params);
    }
    
    // Gợi ý sản phẩm (sản phẩm cùng danh mục)
    public function getSuggestedProducts($productId, $categoryId, $limit = 4) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.category_id = ? 
                AND p.id != ? 
                AND p.deleted_at IS NULL 
                AND p.is_active = 1
                ORDER BY RAND() 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$categoryId, $productId, $limit]);
    }
    
    // Thêm sản phẩm
    public function addProduct($data) {
        $sql = "INSERT INTO products (category_id, supplier_id, name, slug, description, 
                specifications, price, discount_price, quantity, image, images, is_hot, is_active) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $params = [
            $data['category_id'],
            $data['supplier_id'],
            $data['name'],
            $this->createSlug($data['name']),
            $data['description'] ?? '',
            $data['specifications'] ?? null,
            $data['price'],
            $data['discount_price'] ?? null,
            $data['quantity'] ?? 0,
            $data['image'] ?? '',
            $data['images'] ?? null,
            $data['is_hot'] ?? 0,
            $data['is_active'] ?? 1
        ];
        
        if ($this->db->query($sql, $params)) {
            return ['success' => true, 'id' => $this->db->lastInsertId()];
        }
        
        return ['success' => false, 'message' => 'Thêm sản phẩm thất bại'];
    }
    
    // Cập nhật sản phẩm
    public function updateProduct($id, $data) {
        $sql = "UPDATE products SET 
                category_id = ?, 
                supplier_id = ?, 
                name = ?, 
                slug = ?, 
                description = ?, 
                specifications = ?,
                price = ?, 
                discount_price = ?, 
                quantity = ?, 
                image = ?, 
                images = ?,
                is_hot = ?, 
                is_active = ?,
                updated_at = NOW()
                WHERE id = ?";
        
        $params = [
            $data['category_id'],
            $data['supplier_id'],
            $data['name'],
            $this->createSlug($data['name']),
            $data['description'] ?? '',
            $data['specifications'] ?? null,
            $data['price'],
            $data['discount_price'] ?? null,
            $data['quantity'] ?? 0,
            $data['image'] ?? '',
            $data['images'] ?? null,
            $data['is_hot'] ?? 0,
            $data['is_active'] ?? 1,
            $id
        ];
        
        return $this->db->query($sql, $params);
    }
    
    // Xóa mềm sản phẩm
    public function deleteProduct($id) {
        $sql = "UPDATE products SET deleted_at = NOW() WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    // Khôi phục sản phẩm
    public function restoreProduct($id) {
        $sql = "UPDATE products SET deleted_at = NULL WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    // Ngừng kinh doanh sản phẩm
    public function deactivateProduct($id) {
        $sql = "UPDATE products SET is_active = 0 WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    // Kích hoạt lại sản phẩm
    public function activateProduct($id) {
        $sql = "UPDATE products SET is_active = 1 WHERE id = ?";
        return $this->db->query($sql, [$id]);
    }
    
    // Cập nhật số lượng sản phẩm
    public function updateQuantity($id, $quantity) {
        $sql = "UPDATE products SET quantity = quantity + ? WHERE id = ?";
        return $this->db->query($sql, [$quantity, $id]);
    }
    
    // Giảm số lượng khi bán
    public function decreaseQuantity($id, $quantity) {
        $sql = "UPDATE products SET 
                quantity = quantity - ?, 
                sold_quantity = sold_quantity + ? 
                WHERE id = ? AND quantity >= ?";
        return $this->db->query($sql, [$quantity, $quantity, $id, $quantity]);
    }
    
    // Nhập hàng
    public function importProduct($data) {
        $this->db->beginTransaction();
        
        try {
            // Thêm vào bảng nhập hàng
            $sql = "INSERT INTO product_imports (product_id, supplier_id, quantity, import_price, total_price, note, created_by) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $params = [
                $data['product_id'],
                $data['supplier_id'],
                $data['quantity'],
                $data['import_price'],
                $data['quantity'] * $data['import_price'],
                $data['note'] ?? '',
                $data['created_by']
            ];
            
            $this->db->query($sql, $params);
            
            // Cập nhật số lượng sản phẩm
            $this->updateQuantity($data['product_id'], $data['quantity']);
            
            $this->db->commit();
            return ['success' => true, 'message' => 'Nhập hàng thành công'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => 'Nhập hàng thất bại: ' . $e->getMessage()];
        }
    }
    
    // Lấy lịch sử nhập hàng
    public function getImportHistory($productId = null) {
        $sql = "SELECT pi.*, p.name as product_name, s.name as supplier_name, u.username as created_by_name 
                FROM product_imports pi 
                LEFT JOIN products p ON pi.product_id = p.id 
                LEFT JOIN suppliers s ON pi.supplier_id = s.id 
                LEFT JOIN users u ON pi.created_by = u.id";
        
        if ($productId) {
            $sql .= " WHERE pi.product_id = ?";
            return $this->db->fetchAll($sql, [$productId]);
        }
        
        $sql .= " ORDER BY pi.created_at DESC";
        return $this->db->fetchAll($sql);
    }
    
    // Kiểm tra sản phẩm còn hàng
    public function checkStock($id) {
        $product = $this->getProductById($id);
        return $product && $product['quantity'] > 0;
    }
    
    // Tạo slug từ tên
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
        $string = trim($string, '-');
        
        return $string . '-' . time();
    }
    
    // Đếm tổng số sản phẩm
    public function countProducts() {
        $sql = "SELECT COUNT(*) as total FROM products WHERE deleted_at IS NULL";
        $result = $this->db->fetchOne($sql);
        return $result['total'] ?? 0;
    }
    
    // Lấy sản phẩm giảm giá
    public function getDiscountedProducts($limit = 8) {
        $sql = "SELECT p.*, c.name as category_name 
                FROM products p 
                LEFT JOIN categories c ON p.category_id = c.id 
                WHERE p.discount_price IS NOT NULL 
                AND p.discount_price > 0 
                AND p.deleted_at IS NULL 
                AND p.is_active = 1
                ORDER BY (p.price - p.discount_price) DESC 
                LIMIT ?";
        return $this->db->fetchAll($sql, [$limit]);
    }
}
?>