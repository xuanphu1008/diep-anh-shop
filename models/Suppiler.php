<?php
class Suppiler {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getAllSuppilers() {
        $sql = "SELECT * FROM suppilers WHERE deleted_at IS NULL ORDER BY name ASC";
        return $this->db->fetchAll($sql);
    }

    public function getSuppilerById($id) {
        $sql = "SELECT * FROM suppilers WHERE id = ? AND deleted_at IS NULL";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    public function addSuppiler($data) {
        $sql = "INSERT INTO suppilers (name, email, phone, address, status) VALUES (?, ?, ?, ?, ?)";
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

    public function updateSuppiler($id, $data) {
        $sql = "UPDATE suppilers SET name = ?, email = ?, phone = ?, address = ?, status = ? WHERE id = ?";
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

    public function deleteSuppiler($id) {
        $sql = "UPDATE suppilers SET deleted_at = NOW() WHERE id = ?";
        if ($this->db->query($sql, [$id])) {
            return ['success' => true, 'message' => 'Xóa nhà cung cấp thành công'];
        }
        return ['success' => false, 'message' => 'Xóa nhà cung cấp thất bại'];
    }

    public function restoreSuppiler($id) {
        $sql = "UPDATE suppilers SET deleted_at = NULL WHERE id = ?";
        if ($this->db->query($sql, [$id])) {
            return ['success' => true, 'message' => 'Khôi phục nhà cung cấp thành công'];
        }
        return ['success' => false, 'message' => 'Khôi phục nhà cung cấp thất bại'];
    }
}
?>