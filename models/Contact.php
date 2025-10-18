<?php
class Contact {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function getAllContacts($status = null) {
        $sql = "SELECT * FROM contacts";
        $params = [];
        
        if ($status) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }
        
        $sql .= " ORDER BY created_at DESC";
        return $this->db->fetchAll($sql, $params);
    }
    
    public function getContactById($id) {
        $sql = "SELECT * FROM contacts WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    public function addContact($data) {
        $sql = "INSERT INTO contacts (name, email, phone, subject, message, status) 
                VALUES (?, ?, ?, ?, ?, 'pending')";
        $params = [
            $data['name'],
            $data['email'],
            $data['phone'] ?? '',
            $data['subject'] ?? '',
            $data['message']
        ];
        
        if ($this->db->query($sql, $params)) {
            return ['success' => true, 'message' => 'Gửi liên hệ thành công. Chúng tôi sẽ phản hồi sớm!'];
        }
        return ['success' => false, 'message' => 'Gửi liên hệ thất bại'];
    }
    
    public function updateContactStatus($id, $status) {
        $validStatuses = ['pending', 'processing', 'resolved'];
        
        if (!in_array($status, $validStatuses)) {
            return ['success' => false, 'message' => 'Trạng thái không hợp lệ'];
        }
        
        $sql = "UPDATE contacts SET status = ? WHERE id = ?";
        if ($this->db->query($sql, [$status, $id])) {
            return ['success' => true, 'message' => 'Cập nhật trạng thái thành công'];
        }
        return ['success' => false, 'message' => 'Cập nhật trạng thái thất bại'];
    }
    
    public function deleteContact($id) {
        $sql = "DELETE FROM contacts WHERE id = ?";
        if ($this->db->query($sql, [$id])) {
            return ['success' => true, 'message' => 'Xóa liên hệ thành công'];
        }
        return ['success' => false, 'message' => 'Xóa liên hệ thất bại'];
    }
    
    public function countByStatus($status = null) {
        $sql = "SELECT COUNT(*) as total FROM contacts";
        $params = [];
        
        if ($status) {
            $sql .= " WHERE status = ?";
            $params[] = $status;
        }
        
        $result = $this->db->fetchOne($sql, $params);
        return $result['total'] ?? 0;
    }
}
?>