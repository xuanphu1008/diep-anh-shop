<?php
// customer/profile.php - Trang thông tin cá nhân

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/Rating.php';

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
                        <a href="#info" class="tab-link active" onclick="showTab(event,'info')">
                            <i class="fas fa-user"></i> Thông tin cá nhân
                        </a>
                    </li>
                    <li>
                        <a href="#password" class="tab-link" onclick="showTab(event,'password')">
                            <i class="fas fa-lock"></i> Đổi mật khẩu
                        </a>
                    </li>
                    <li>
                        <a href="#ratings" class="tab-link" onclick="showTab(event,'ratings')">
                            <i class="fas fa-star"></i> Đánh giá của tôi
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
                
                <!-- Tab đánh giá của tôi -->
                <div id="ratings" class="tab-content">
                    <h2>Đánh giá của tôi</h2>
                    <?php
                    $ratingModel = new Rating();
                    $db = new Database();
                    
                    // Lấy danh sách đánh giá của user
                    $sql = "SELECT r.id, r.rating, r.content, r.status, r.created_at,
                                   p.id as product_id, p.name as product_name, p.slug, p.image,
                                   p.price, p.discount_price
                            FROM comments r
                            INNER JOIN products p ON r.product_id = p.id
                            WHERE r.user_id = ?
                            ORDER BY r.created_at DESC";
                    
                    $userRatings = $db->query($sql, [$userId])->fetchAll();
                    ?>
                    
                    <?php if (empty($userRatings)): ?>
                        <div style="text-align: center; padding: 40px; background: var(--light-color); border-radius: 8px;">
                            <i class="fas fa-star" style="font-size: 48px; opacity: 0.2; display: block; margin-bottom: 15px;"></i>
                            <p style="color: #999; margin: 0;">Bạn chưa có đánh giá nào</p>
                        </div>
                    <?php else: ?>
                        <table style="width: 100%; border-collapse: collapse; margin-top: 20px;">
                            <thead>
                                <tr style="background: var(--light-color); border-bottom: 2px solid var(--border-color);">
                                    <th style="padding: 12px; text-align: left;">Sản phẩm</th>
                                    <th style="padding: 12px; text-align: center; width: 100px;">Đánh giá</th>
                                    <th style="padding: 12px; text-align: center; width: 120px;">Trạng thái</th>
                                    <th style="padding: 12px; text-align: left;">Nhận xét</th>
                                    <th style="padding: 12px; text-align: left; width: 150px;">Ngày đánh giá</th>
                                    <th style="padding: 12px; text-align: center; width: 80px;">Hành động</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($userRatings as $rating): ?>
                                <tr style="border-bottom: 1px solid var(--border-color);">
                                    <td style="padding: 12px;">
                                        <a href="../product-detail.php?slug=<?php echo $rating['slug']; ?>" style="color: var(--primary-color); text-decoration: none;">
                                            <?php echo htmlspecialchars(substr($rating['product_name'], 0, 40)); ?>
                                        </a>
                                    </td>
                                    <td style="padding: 12px; text-align: center; color: var(--warning-color);">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star" style="opacity: <?php echo $i <= $rating['rating'] ? '1' : '0.3'; ?>"></i>
                                        <?php endfor; ?>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <span style="display: inline-block; padding: 4px 10px; border-radius: 12px; font-size: 12px;
                                                     background: <?php echo $rating['status'] == 1 ? 'rgba(74, 155, 107, 0.12)' : 'rgba(212, 165, 116, 0.12)'; ?>;
                                                     color: <?php echo $rating['status'] == 1 ? '#2d6b4a' : '#a67a4f'; ?>;">
                                            <?php echo $rating['status'] == 1 ? 'Đã duyệt' : 'Chờ duyệt'; ?>
                                        </span>
                                    </td>
                                    <td style="padding: 12px;">
                                        <small style="color: #555;"><?php echo htmlspecialchars(substr($rating['content'] ?? '', 0, 50)); ?><?php echo strlen($rating['content'] ?? '') > 50 ? '...' : ''; ?></small>
                                    </td>
                                    <td style="padding: 12px;">
                                        <small style="color: #999;"><?php echo formatDate($rating['created_at'], 'd/m/Y'); ?></small>
                                    </td>
                                    <td style="padding: 12px; text-align: center;">
                                        <a href="javascript:deleteRating(<?php echo $rating['id']; ?>)" style="color: var(--danger-color); text-decoration: none; font-size: 14px;">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    <?php endif; ?>
                </div>
            </main>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    
    <script>
        function showTab(evt, tabName) {
            try {
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
                if (evt && evt.currentTarget) {
                    evt.currentTarget.classList.add('active');
                } else if (evt && evt.target) {
                    const link = evt.target.closest('.tab-link');
                    if (link) link.classList.add('active');
                }
            } catch (e) {
                console.error('showTab error', e);
            }
        }
        
        function deleteRating(id) {
            if (confirm('Bạn chắc chắn muốn xóa đánh giá này?')) {
                const formData = new FormData();
                formData.append('action', 'delete');
                formData.append('rating_id', id);
                
                fetch('../api/rating-handler.php', {
                    method: 'POST',
                    body: formData
                })
                .then(r => r.json())
                .then(d => {
                    if (d.success) {
                        alert('Đánh giá đã được xóa');
                        location.reload();
                    } else {
                        alert(d.message);
                    }
                });
            }
        }
    </script>
</body>
</html>