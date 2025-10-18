<?php
// customer/login.php - Trang đăng nhập

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../includes/functions.php';

// Redirect nếu đã đăng nhập
if (isLoggedIn()) {
    redirect(SITE_URL . '/index.php');
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate CSRF token
    if (!validateCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Token không hợp lệ';
    } else {
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        if (empty($username) || empty($password)) {
            $error = 'Vui lòng nhập đầy đủ thông tin';
        } else {
            $userModel = new User();
            $result = $userModel->login($username, $password);
            
            if ($result['success']) {
                // Set remember me cookie
                if ($remember) {
                    setcookie('remember_user', $username, time() + (86400 * 30), '/');
                }
                
                // Redirect based on role
                if (isStaff()) {
                    redirect(SITE_URL . '/admin/index.php');
                } else {
                    redirect(SITE_URL . '/index.php');
                }
            } else {
                $error = $result['message'];
            }
        }
    }
}

$pageTitle = 'Đăng nhập - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .login-container {
            max-width: 450px;
            margin: 80px auto;
            padding: 40px;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 20px rgba(0,0,0,0.1);
        }
        .login-header {
            text-align: center;
            margin-bottom: 30px;
        }
        .login-header h1 {
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        .social-login {
            display: flex;
            gap: 10px;
            margin: 20px 0;
        }
        .social-btn {
            flex: 1;
            padding: 10px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            background: #fff;
            cursor: pointer;
            transition: all 0.3s;
        }
        .social-btn:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        .divider {
            text-align: center;
            margin: 20px 0;
            position: relative;
        }
        .divider::before {
            content: '';
            position: absolute;
            left: 0;
            top: 50%;
            width: 100%;
            height: 1px;
            background: var(--border-color);
        }
        .divider span {
            background: #fff;
            padding: 0 10px;
            position: relative;
            color: #666;
        }
        .remember-forgot {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container">
        <div class="login-container">
            <div class="login-header">
                <h1><i class="fas fa-sign-in-alt"></i> Đăng nhập</h1>
                <p>Đăng nhập để trải nghiệm mua sắm tốt hơn!</p>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-error">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" action="">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="username"><i class="fas fa-user"></i> Tên đăng nhập hoặc Email</label>
                    <input type="text" id="username" name="username" class="form-control" 
                           value="<?php echo htmlspecialchars($_COOKIE['remember_user'] ?? ''); ?>" 
                           required autofocus>
                </div>
                
                <div class="form-group">
                    <label for="password"><i class="fas fa-lock"></i> Mật khẩu</label>
                    <input type="password" id="password" name="password" class="form-control" required>
                </div>
                
                <div class="remember-forgot">
                    <label>
                        <input type="checkbox" name="remember" <?php echo isset($_COOKIE['remember_user']) ? 'checked' : ''; ?>>
                        Ghi nhớ đăng nhập
                    </label>
                    <a href="forgot-password.php" style="color: var(--primary-color);">Quên mật khẩu?</a>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-block btn-lg">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập
                    </button>
                </div>
            </form>
            
            <div class="divider">
                <span>Hoặc đăng nhập với</span>
            </div>
            
            <div class="social-login">
                <button class="social-btn" onclick="alert('Tính năng đang phát triển')">
                    <i class="fab fa-google" style="color: #DB4437;"></i> Google
                </button>
                <button class="social-btn" onclick="alert('Tính năng đang phát triển')">
                    <i class="fab fa-facebook" style="color: #4267B2;"></i> Facebook
                </button>
            </div>
            
            <div class="text-center" style="margin-top: 20px;">
                <p>Chưa có tài khoản? <a href="register.php" style="color: var(--primary-color); font-weight: bold;">Đăng ký ngay</a></p>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>