<?php
// customer/profile.php - Trang thông tin cá nhân

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/User.php';

requireLogin();

$userModel = new User();
$userId = $_SESSION['user_id'];
$user = $userModel->getUserById($userId);

$errors = [];
$success = '';

// Xử lý cập nhật thông tin
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $data = [
        'full_name' => sanitizeInput($_POST['full_name'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'address' => sanitizeInput($_POST['address'] ?? '')
    ];
    
    if (empty($data['full_name'])) {
        $errors[] = 'Vui lòng nhập họ tên';
    }
    
    if (empty($errors)) {
        if ($userModel->updateUser($userId, $data)) {
            $_SESSION['full_name'] = $data['full_name'];
            $success = 'Cập nhật thông tin thành công';
            $user = $userModel->getUserById($userId);
        } else {
            $errors[] = 'Cập nhật thất bại';
        }
    }
}

// Xử lý đổi mật khẩu
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $oldPassword = $_POST['old_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';
    
    if (empty($oldPassword) || empty($newPassword) || empty($confirmPassword)) {
        $errors[] = 'Vui lòng nhập đầy đủ thông tin';
    } elseif ($newPassword !== $confirmPassword) {
        $errors[] = 'Mật khẩu mới không khớp';
    } elseif (strlen($newPassword) < 6) {
        $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự';
    } else {
        $result = $userModel->changePassword($userId, $oldPassword, $newPassword);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
    }
}

$pageTitle = 'Thông tin cá nhân - ' . SITE_NAME;
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
        .profile-container {
            padding: 30px 0;
        }
        .profile-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
        }
        .profile-sidebar {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            height: fit-content;
        }
        .profile-menu {
            list-style: none;
        }
        .profile-menu li {
            margin-bottom: 10px;
        }
        .profile-menu a {
            display: block;
            padding: 10px 15px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .profile-menu a:hover,
        .profile-menu a.active {
            background: var(--primary-color);
            color: #fff;
        }
        .profile-content {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        @media (max-width: 768px) {
            .profile-layout {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container profile-container">
        <h1 class="section-title"><i class="fas fa-user"></i> Tài khoản của tôi</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <div class="profile-layout">
            <!-- Sidebar -->
            <aside class="profile-sidebar">
                <div style="text-align: center; margin-bottom: 20px;">
                    <div style="width: 100px; height: 100px; background: var(--primary-color); color: #fff; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto; font-size: 40px;">
                        <?php echo strtoupper(substr($user['full_name'] ?? $user['username'], 0, 1)); ?>
                    </div>
                    <h3 style="margin-top: 10px;"><?php echo htmlspecialchars($user['full_name'] ?? $user['username']); ?></h3>
                </div>
                
                <ul class="profile-menu">
                    <li>
                        <a href="#info" class="tab-link active" onclick="showTab('info')">
                            <i class="fas fa-user"></i> Thông tin cá nhân
                        </a>
                    </li>
                    <li>
                        <a href="#password" class="tab-link" onclick="showTab('password')">
                            <i class="fas fa-lock"></i> Đổi mật khẩu
                        </a>
                    </li>
                    <li>
                        <a href="orders.php">
                            <i class="fas fa-shopping-bag"></i> Đơn hàng của tôi
                        </a>
                    </li>
                    <li>
                        <a href="logout.php">
                            <i class="fas fa-sign-out-alt"></i> Đăng xuất
                        </a>
                    </li>
                </ul>
            </aside>
            
            <!-- Content -->
            <main class="profile-content">
                <!-- Tab thông tin cá nhân -->
                <div id="info" class="tab-content active">
                    <h2>Thông tin cá nhân</h2>
                    <form method="POST" style="margin-top: 20px;">
                        <div class="form-group">
                            <label>Tên đăng nhập</label>
                            <input type="text" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" class="form-control" value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                        </div>
                        
                        <div class="form-group">
                            <label>Họ và tên *</label>
                            <input type="text" name="full_name" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Số điện thoại</label>
                            <input type="text" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Địa chỉ</label>
                            <textarea name="address" class="form-control"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Cập nhật thông tin
                        </button>
                    </form>
                </div>
                
                <!-- Tab đổi mật khẩu -->
                <div id="password" class="tab-content">
                    <h2>Đổi mật khẩu</h2>
                    <form method="POST" style="margin-top: 20px;">
                        <div class="form-group">
                            <label>Mật khẩu cũ *</label>
                            <input type="password" name="old_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Mật khẩu mới *</label>
                            <input type="password" name="new_password" class="form-control" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Xác nhận mật khẩu mới *</label>
                            <input type="password" name="confirm_password" class="form-control" required>
                        </div>
                        
                        <button type="submit" name="change_password" class="btn btn-primary">
                            <i class="fas fa-key"></i> Đổi mật khẩu
                        </button>
                    </form>
                </div>
            </main>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    
    <script>
        function showTab(tabName) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Remove active from all links
            document.querySelectorAll('.tab-link').forEach(link => {
                link.classList.remove('active');
            });
            
            // Show selected tab
            document.getElementById(tabName).classList.add('active');
            
            // Add active to clicked link
            event.target.classList.add('active');
        }
    </script>
</body>
</html>