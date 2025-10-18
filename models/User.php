<?php
// models/User.php - Class quản lý người dùng

require_once __DIR__ . '/../includes/Database.php';

class User {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }
    
    // Đăng ký người dùng mới
    public function register($data) {
        // Kiểm tra username đã tồn tại
        if ($this->getUserByUsername($data['username'])) {
            return ['success' => false, 'message' => 'Username đã tồn tại'];
        }
        
        // Kiểm tra email đã tồn tại
        if ($this->getUserByEmail($data['email'])) {
            return ['success' => false, 'message' => 'Email đã tồn tại'];
        }
        
        $sql = "INSERT INTO users (username, email, password, full_name, phone, address, role) 
                VALUES (?, ?, ?, ?, ?, ?, 'customer')";
        
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        $params = [
            $data['username'],
            $data['email'],
            $hashedPassword,
            $data['full_name'] ?? '',
            $data['phone'] ?? '',
            $data['address'] ?? ''
        ];
        
        if ($this->db->query($sql, $params)) {
            // Tạo mã giảm giá chào mừng
            $this->createWelcomeCoupon($data['email']);
            return ['success' => true, 'message' => 'Đăng ký thành công'];
        }
        
        return ['success' => false, 'message' => 'Đăng ký thất bại'];
    }
    
    // Đăng nhập
    public function login($username, $password) {
        $user = $this->getUserByUsername($username);
        
        if (!$user) {
            $user = $this->getUserByEmail($username);
        }
        
        if ($user && password_verify($password, $user['password'])) {
            if ($user['status'] == 0) {
                return ['success' => false, 'message' => 'Tài khoản đã bị khóa'];
            }
            
            // Lưu thông tin vào session
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['email'] = $user['email'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            
            return ['success' => true, 'message' => 'Đăng nhập thành công', 'user' => $user];
        }
        
        return ['success' => false, 'message' => 'Tên đăng nhập hoặc mật khẩu không đúng'];
    }
    
    // Đăng xuất
    public function logout() {
        session_destroy();
        return true;
    }
    
    // Lấy thông tin user theo username
    public function getUserByUsername($username) {
        $sql = "SELECT * FROM users WHERE username = ? AND status = 1";
        return $this->db->fetchOne($sql, [$username]);
    }
    
    // Lấy thông tin user theo email
    public function getUserByEmail($email) {
        $sql = "SELECT * FROM users WHERE email = ? AND status = 1";
        return $this->db->fetchOne($sql, [$email]);
    }
    
    // Lấy thông tin user theo ID
    public function getUserById($id) {
        $sql = "SELECT * FROM users WHERE id = ?";
        return $this->db->fetchOne($sql, [$id]);
    }
    
    // Cập nhật thông tin user
    public function updateUser($id, $data) {
        $sql = "UPDATE users SET 
                full_name = ?, 
                phone = ?, 
                address = ?,
                updated_at = NOW()
                WHERE id = ?";
        
        $params = [
            $data['full_name'] ?? '',
            $data['phone'] ?? '',
            $data['address'] ?? '',
            $id
        ];
        
        return $this->db->query($sql, $params);
    }
    
    // Đổi mật khẩu
    public function changePassword($userId, $oldPassword, $newPassword) {
        $user = $this->getUserById($userId);
        
        if (!$user || !password_verify($oldPassword, $user['password'])) {
            return ['success' => false, 'message' => 'Mật khẩu cũ không đúng'];
        }
        
        $sql = "UPDATE users SET password = ? WHERE id = ?";
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        if ($this->db->query($sql, [$hashedPassword, $userId])) {
            return ['success' => true, 'message' => 'Đổi mật khẩu thành công'];
        }
        
        return ['success' => false, 'message' => 'Đổi mật khẩu thất bại'];
    }
    
    // Kiểm tra đăng nhập
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    // Kiểm tra quyền admin
    public function isAdmin() {
        return isset($_SESSION['role']) && $_SESSION['role'] === 'admin';
    }
    
    // Kiểm tra quyền staff hoặc admin
    public function isStaff() {
        return isset($_SESSION['role']) && in_array($_SESSION['role'], ['admin', 'staff']);
    }
    
    // Tạo mã giảm giá chào mừng
    private function createWelcomeCoupon($email) {
        $couponCode = 'WELCOME' . strtoupper(substr(md5($email), 0, 6));
        
        $sql = "INSERT INTO coupons (code, type, value, min_order_value, quantity, start_date, end_date, status) 
                VALUES (?, 'percent', 10, 500000, 1, NOW(), DATE_ADD(NOW(), INTERVAL 30 DAY), 1)";
        
        $this->db->query($sql, [$couponCode]);
        
        // Gửi email thông báo (cần implement sendEmail function)
        return $couponCode;
    }
    
    // Lấy danh sách khách hàng
    public function getAllCustomers() {
        $sql = "SELECT id, username, email, full_name, phone, address, status, created_at 
                FROM users 
                WHERE role = 'customer' 
                ORDER BY created_at DESC";
        return $this->db->fetchAll($sql);
    }
    
    // Lấy danh sách nhân viên
    public function getAllStaff() {
        $sql = "SELECT id, username, email, full_name, phone, role, status, created_at 
                FROM users 
                WHERE role IN ('staff', 'admin') 
                ORDER BY created_at DESC";
        return $this->db->fetchAll($sql);
    }
    
    // Thêm/sửa nhân viên
    public function saveStaff($data, $id = null) {
        if ($id) {
            // Cập nhật
            $sql = "UPDATE users SET 
                    username = ?, 
                    email = ?, 
                    full_name = ?, 
                    phone = ?, 
                    role = ?,
                    status = ?
                    WHERE id = ?";
            
            $params = [
                $data['username'],
                $data['email'],
                $data['full_name'],
                $data['phone'],
                $data['role'],
                $data['status'],
                $id
            ];
        } else {
            // Thêm mới
            $sql = "INSERT INTO users (username, email, password, full_name, phone, role, status) 
                    VALUES (?, ?, ?, ?, ?, ?, ?)";
            
            $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
            
            $params = [
                $data['username'],
                $data['email'],
                $hashedPassword,
                $data['full_name'],
                $data['phone'],
                $data['role'],
                $data['status']
            ];
        }
        
        return $this->db->query($sql, $params);
    }
}
?>
