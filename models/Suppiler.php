<?php
class Supplier {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getAllSuppliers() {
        $sql = "SELECT * FROM suppliers WHERE deleted_at IS NULL ORDER BY name ASC";
        return $this->db->fetchAll($sql);
    }
    
    public function getSupplierById($id) {
        $sql = "SELECT * FROM suppliers WHERE id = ? AND deleted_at IS NULL";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    public function addSupplier($data) {
        $sql = "INSERT INTO suppliers (name, email, phone, address, status) VALUES (?, ?, ?, ?, ?)";
        $params = [
            $data['name'],
            $data['email'] ?? '',
            $data['phone'] ?? '',
            $data['address'] ?? '',
            $data['status'] ?? 1
        ];
        
        if ($this->db->query($sql, $params)) {
            return ['success' => true, 'message' => 'Thêm nhà cung cấp thành công'];
        }
        return ['success' => false, 'message' => 'Thêm nhà cung cấp thất bại'];
    }
    
    public function updateSupplier($id, $data) {
        $sql = "UPDATE suppliers SET name = ?, email = ?, phone = ?, address = ?, status = ? WHERE id = ?";
        $params = [
            $data['name'],
            $data['email'] ?? '',
            $data['phone'] ?? '',
            $data['address'] ?? '',
            $data['status'] ?? 1,
            $id
        ];
        
        if ($this->db->query($sql, $params)) {
            return ['success' => true, 'message' => 'Cập nhật nhà cung cấp thành công'];
        }
        return ['success' => false, 'message' => 'Cập nhật nhà cung cấp thất bại'];
    }
    
    public function deleteSupplier($id) {
        $sql = "UPDATE suppliers SET deleted_at = NOW() WHERE id = ?";
        if ($this->db->query($sql, [$id])) {
            return ['success' => true, 'message' => 'Xóa nhà cung cấp thành công'];
        }
        return ['success' => false, 'message' => 'Xóa nhà cung cấp thất bại'];
    }
    
    public function restoreSupplier($id) {
        $sql = "UPDATE suppliers SET deleted_at = NULL WHERE id = ?";
        if ($this->db->query($sql, [$id])) {
            return ['success' => true, 'message' => 'Khôi phục nhà cung cấp thành công'];
        }
        return ['success' => false, 'message' => 'Khôi phục nhà cung cấp thất bại'];
    }
}
?>