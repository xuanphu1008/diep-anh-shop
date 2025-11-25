<?php
// customer/forgot-password.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/mailer.php';
require_once __DIR__ . '/../models/User.php';

if (isLoggedIn()) redirect(SITE_URL);

$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    
    if (!validateEmail($email)) {
        $error = 'Email không hợp lệ';
    } else {
        $userModel = new User();
        $user = $userModel->getUserByEmail($email);
        
        if ($user) {
            // Tạo token
            $token = bin2hex(random_bytes(32));
            $expires = date('Y-m-d H:i:s', time() + 3600); // Hết hạn sau 1 giờ
            
            $db = new Database();
            // Xóa token cũ nếu có
            $db->query("DELETE FROM password_resets WHERE email = ?", [$email]);
            // Lưu token mới
            $sql = "INSERT INTO password_resets (email, token, expires_at) VALUES (?, ?, ?)";
            
            if ($db->query($sql, [$email, $token, $expires])) {
                // Gửi email
                $resetLink = SITE_URL . "/customer/reset-password.php?token=" . $token . "&email=" . urlencode($email);
                $mailer = new Mailer();
                // Lưu ý: Bạn cần viết thêm hàm sendResetPasswordEmail trong class Mailer hoặc dùng hàm gửi email cơ bản
                // Ở đây tôi giả lập gửi mail thành công
                // $mailer->sendEmail($email, "Đặt lại mật khẩu", "Bấm vào đây để đặt lại mật khẩu: <a href='$resetLink'>$resetLink</a>");
                
                $message = "Link đặt lại mật khẩu đã được gửi vào email của bạn (Demo Link: <a href='$resetLink'>Click vào đây để test</a>)";
            }
        } else {
            // Để bảo mật, vẫn báo thành công dù email không tồn tại
            $message = "Nếu email tồn tại, chúng tôi đã gửi hướng dẫn đặt lại mật khẩu.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Quên mật khẩu - <?php echo SITE_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    <div class="container" style="padding: 50px 0;">
        <div style="max-width: 500px; margin: 0 auto; background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
            <h2 class="text-center mb-30">Quên mật khẩu?</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php else: ?>
                <p class="mb-20 text-center">Nhập email của bạn để nhận liên kết đặt lại mật khẩu.</p>
                <form method="POST">
                    <div class="form-group">
                        <label>Email đăng ký</label>
                        <input type="email" name="email" class="form-control" required placeholder="nhap@email.com">
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Gửi yêu cầu</button>
                    <div class="text-center mt-20">
                        <a href="login.php">Quay lại đăng nhập</a>
                    </div>
                </form>
            <?php endif; ?>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>