<?php
// customer/reset-password.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/User.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';
$error = '';
$success = '';

$db = new Database();

// Kiểm tra token hợp lệ
$sql = "SELECT * FROM password_resets WHERE email = ? AND token = ? AND expires_at > NOW()";
$resetRequest = $db->fetchOne($sql, [$email, $token]);

if (!$resetRequest) {
    setFlashMessage('error', 'Liên kết đặt lại mật khẩu không hợp lệ hoặc đã hết hạn.');
    redirect('login.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    if (strlen($password) < 6) {
        $error = 'Mật khẩu phải có ít nhất 6 ký tự';
    } elseif ($password !== $confirm_password) {
        $error = 'Mật khẩu xác nhận không khớp';
    } else {
        // Cập nhật mật khẩu mới
        $userModel = new User();
        $user = $userModel->getUserByEmail($email);
        
        // Hash password mới và cập nhật (Bạn cần thêm hàm updatePasswordByEmail vào User model hoặc chạy query trực tiếp)
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
        $db->query("UPDATE users SET password = ? WHERE email = ?", [$hashedPassword, $email]);
        
        // Xóa token
        $db->query("DELETE FROM password_resets WHERE email = ?", [$email]);
        
        setFlashMessage('success', 'Đặt lại mật khẩu thành công. Vui lòng đăng nhập.');
        redirect('login.php');
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Đặt lại mật khẩu</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="container" style="padding: 50px 0;">
        <div style="max-width: 500px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2 class="text-center mb-30">Đặt lại mật khẩu mới</h2>
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label>Mật khẩu mới</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Xác nhận mật khẩu</label>
                    <input type="password" name="confirm_password" class="form-control" required>
                </div>
                <button type="submit" class="btn btn-success btn-block">Đổi mật khẩu</button>
            </form>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>