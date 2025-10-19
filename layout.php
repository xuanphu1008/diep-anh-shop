<?php
// admin/layout.php - Layout dùng chung cho admin
if (!isset($pageTitle)) $pageTitle = 'Admin';

// Determine if we're in a subdirectory of admin
$isSubDir = strpos($_SERVER['SCRIPT_NAME'], '/admin/') !== false && 
            substr_count($_SERVER['SCRIPT_NAME'], '/', strpos($_SERVER['SCRIPT_NAME'], '/admin/')) > 1;
$baseUrl = $isSubDir ? '../' : '';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="<?php echo $baseUrl; ?>../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-layout { display: grid; grid-template-columns: 250px 1fr; min-height: 100vh; }
        .admin-sidebar { background: #2c3e50; color: #fff; padding: 20px 0; }
        .admin-sidebar h2 { padding: 0 20px 20px; border-bottom: 1px solid rgba(255,255,255,0.1); margin-bottom: 20px; }
        .admin-menu { list-style: none; }
        .admin-menu li a { display: block; padding: 12px 20px; color: #ecf0f1; transition: all 0.3s; }
        .admin-menu li a:hover, .admin-menu li a.active { background: #34495e; border-left: 3px solid var(--primary-color); }
        .admin-content { padding: 30px; background: #ecf0f1; }
        @media (max-width: 900px) {
            .admin-layout { grid-template-columns: 1fr; }
            .admin-sidebar { position: static; width: 100%; }
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <h2><i class="fas fa-cog"></i> Admin Panel</h2>
        <ul class="admin-menu">
            <li><a href="<?php echo $baseUrl; ?>index.php" class="<?php if(isset($activeMenu) && $activeMenu=='dashboard') echo 'active';?>"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="<?php echo $baseUrl; ?>products/index.php" class="<?php if(isset($activeMenu) && $activeMenu=='products') echo 'active';?>"><i class="fas fa-box"></i> Sản phẩm</a></li>
            <li><a href="<?php echo $baseUrl; ?>categories/index.php" class="<?php if(isset($activeMenu) && $activeMenu=='categories') echo 'active';?>"><i class="fas fa-list"></i> Danh mục</a></li>
            <li><a href="<?php echo $baseUrl; ?>suppilers/index.php" class="<?php if(isset($activeMenu) && $activeMenu=='suppliers') echo 'active';?>"><i class="fas fa-truck"></i> Nhà cung cấp</a></li>
            <li><a href="<?php echo $baseUrl; ?>orders/index.php" class="<?php if(isset($activeMenu) && $activeMenu=='orders') echo 'active';?>"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
            <li><a href="<?php echo $baseUrl; ?>user/customers.php" class="<?php if(isset($activeMenu) && $activeMenu=='customers') echo 'active';?>"><i class="fas fa-users"></i> Khách hàng</a></li>
            <li><a href="<?php echo $baseUrl; ?>user/staff.php" class="<?php if(isset($activeMenu) && $activeMenu=='staff') echo 'active';?>"><i class="fas fa-user-tie"></i> Nhân viên</a></li>
            <li><a href="<?php echo $baseUrl; ?>coupons/index.php" class="<?php if(isset($activeMenu) && $activeMenu=='coupons') echo 'active';?>"><i class="fas fa-tags"></i> Mã giảm giá</a></li>
            <li><a href="<?php echo $baseUrl; ?>news/index.php" class="<?php if(isset($activeMenu) && $activeMenu=='news') echo 'active';?>"><i class="fas fa-newspaper"></i> Tin tức</a></li>
            <li><a href="<?php echo $baseUrl; ?>banners/index.php" class="<?php if(isset($activeMenu) && $activeMenu=='banners') echo 'active';?>"><i class="fas fa-image"></i> Banner</a></li>
            <li><a href="<?php echo $baseUrl; ?>contacts/index.php" class="<?php if(isset($activeMenu) && $activeMenu=='contacts') echo 'active';?>"><i class="fas fa-envelope"></i> Liên hệ</a></li>
            <li><a href="<?php echo $baseUrl; ?>statistics/index.php" class="<?php if(isset($activeMenu) && $activeMenu=='statistics') echo 'active';?>"><i class="fas fa-chart-bar"></i> Thống kê</a></li>
            <li><hr style="border-color: rgba(255,255,255,0.1);"></li>
            <li><a href="../index.php"><i class="fas fa-home"></i> Về trang chủ</a></li>
            <li><a href="../customer/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </aside>
    <!-- Main Content -->
    <main class="admin-content">
