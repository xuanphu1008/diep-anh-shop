<?php
// admin/layout.php - Layout dùng chung cho admin
if (!isset($pageTitle)) $pageTitle = 'Admin';

// Helper function to create admin URLs
function adminUrl($path = '') {
    // Remove leading slash if present
    $path = ltrim($path, '/');
    
    // Get current script directory
    $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
    
    // Find admin directory position
    $adminPos = strpos($scriptDir, '/admin');
    
    if ($adminPos !== false) {
        // We're in admin directory, calculate relative path
        $pathAfterAdmin = substr($scriptDir, $adminPos + 6); // +6 for '/admin'
        // Count depth: number of '/' in path after /admin
        $depth = $pathAfterAdmin ? substr_count($pathAfterAdmin, '/') : 0;
        $baseUrl = $depth > 0 ? str_repeat('../', $depth) : '';
    } else {
        // We're at admin root
        $baseUrl = '';
    }
    
    return $baseUrl . $path;
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="<?php 
        $cssBaseUrl = dirname($_SERVER['SCRIPT_NAME']);
        $adminPos = strpos($cssBaseUrl, '/admin');
        if ($adminPos !== false) {
            $pathAfterAdmin = substr($cssBaseUrl, $adminPos + 6);
            $depth = $pathAfterAdmin ? substr_count($pathAfterAdmin, '/') : 0;
            echo str_repeat('../', $depth + 1) . 'assets/css/style.css';
        } else {
            echo '../assets/css/style.css';
        }
    ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        /* Admin Color Scheme - Bảng màu xanh teal mới với contrast tốt */
        :root {
            --admin-primary: #3285A6;
            --admin-primary-light: #539DA6;
            --admin-primary-dark: #1B618C;
            --admin-primary-darker: #023059;
            --admin-primary-darkest: #022859;
            --admin-primary-pale: rgba(83, 157, 166, 0.2);
            --admin-primary-very-pale: rgba(83, 157, 166, 0.1);
            --admin-secondary: #6B9A7A;
            --admin-bg: #E8F0F5;
            --admin-card: #FFFFFF;
            --admin-border: #B8D0E0;
            --admin-text: #022859;
            --admin-text-light: #1B618C;
            --admin-success: #4A9B6B;
            --admin-warning: #D4A574;
            --admin-danger: #C85A5A;
            --admin-info: #3285A6;
        }
        
        * {
            box-sizing: border-box;
        }
        
        body {
            margin: 0;
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            background: var(--admin-bg);
            color: var(--admin-text);
        }
        
        .admin-layout { 
            display: grid; 
            grid-template-columns: 210px 1fr; 
            min-height: 100vh;
            background: var(--admin-bg);
        }
        
        /* Sidebar */
        .admin-sidebar { 
            background: linear-gradient(180deg, #F8FBFC 0%, #E8F2F7 100%);
            border-right: 2px solid var(--admin-primary-light);
            padding: 0;
            box-shadow: 2px 0 12px rgba(2, 40, 89, 0.15);
            position: sticky;
            top: 0;
            height: 100vh;
            overflow-y: auto;
        }
        
        .admin-sidebar h2 { 
            padding: 12px 14px;
            margin: 0;
            font-size: 15px;
            font-weight: 600;
            color: var(--admin-primary-darkest);
            border-bottom: 2px solid var(--admin-primary-light);
            background: linear-gradient(135deg, var(--admin-primary-light) 0%, var(--admin-primary) 100%);
            color: #FFFFFF;
        }
        
        .admin-sidebar h2 i {
            margin-right: 10px;
            color: #FFFFFF;
        }
        
        .admin-menu { 
            list-style: none; 
            margin: 0;
            padding: 10px 0;
        }
        
        .admin-menu li {
            margin: 0;
        }
        
        .admin-menu li a { 
            display: flex;
            align-items: center;
            gap: 8px;
            padding: 7px 14px;
            color: var(--admin-primary-darkest);
            transition: all 0.2s ease;
            text-decoration: none;
            font-size: 12px;
            font-weight: 500;
            border-left: 3px solid transparent;
        }
        
        .admin-menu li a i {
            width: 20px;
            text-align: center;
            color: var(--admin-primary);
            transition: all 0.2s ease;
        }
        
        .admin-menu li a:hover {
            background: rgba(83, 157, 166, 0.15);
            color: var(--admin-primary-darkest);
            border-left-color: var(--admin-primary);
        }
        
        .admin-menu li a:hover i {
            color: var(--admin-primary-dark);
        }
        
        .admin-menu li a.active {
            background: linear-gradient(90deg, var(--admin-primary-light) 0%, var(--admin-primary) 100%);
            color: #FFFFFF;
            border-left-color: var(--admin-primary-darkest);
            font-weight: 600;
        }
        
        .admin-menu li a.active i {
            color: #FFFFFF;
        }
        
        .admin-menu li hr {
            margin: 10px 0;
            border: none;
            border-top: 1px solid var(--admin-border);
        }
        
        /* Staff info */
        .admin-sidebar .staff-info {
            padding: 12px 16px;
            margin: 8px;
            background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-primary-dark) 100%);
            border-radius: 10px;
            color: #FFFFFF;
            font-size: 11px;
            border: 1px solid var(--admin-primary-darkest);
        }
        
        .admin-sidebar .staff-info strong {
            display: block;
            margin-top: 4px;
            font-size: 13px;
        }
        
        /* Main Content */
        .admin-content { 
            padding: 14px;
            background: var(--admin-bg);
            min-height: 100vh;
        }
        
        /* Cards */
        .card, .data-table, .stat-card, .chart-container {
            background: var(--admin-card);
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(2, 40, 89, 0.15);
            border: 1px solid var(--admin-border);
            overflow: hidden;
            transition: all 0.3s ease;
        }
        
        .card:hover, .stat-card:hover {
            box-shadow: 0 6px 20px rgba(2, 40, 89, 0.2);
            transform: translateY(-2px);
            border-color: var(--admin-primary);
        }
        
        /* Buttons */
        .btn {
            padding: 6px 12px;
            border-radius: 6px;
            border: none;
            font-size: 12px;
            font-weight: 500;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            text-decoration: none;
            box-shadow: 0 2px 4px rgba(0,0,0,0.08);
        }
        
        .btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.12);
        }
        
        .btn-primary {
            background: var(--admin-primary);
            color: #fff;
        }
        
        .btn-primary:hover {
            background: var(--admin-primary-dark);
            box-shadow: 0 4px 12px rgba(50, 133, 166, 0.3);
        }
        
        .btn-success {
            background: var(--admin-success);
            color: #fff;
        }
        
        .btn-success:hover {
            background: #3d7a55;
        }
        
        .btn-danger {
            background: var(--admin-danger);
            color: #fff;
        }
        
        .btn-danger:hover {
            background: #b04a4a;
        }
        
        .btn-warning {
            background: var(--admin-warning);
            color: #fff;
        }
        
        .btn-warning:hover {
            background: #c4945a;
        }
        
        .btn-secondary {
            background: #E8EAED;
            color: var(--admin-text);
        }
        
        .btn-secondary:hover {
            background: #DADCE0;
        }
        
        .btn-info {
            background: var(--admin-info);
            color: #fff;
        }
        
        .btn-info:hover {
            background: #4a7a8a;
        }
        
        .btn-sm {
            padding: 5px 10px;
            font-size: 11px;
            border-radius: 6px;
        }
        
        .btn-lg {
            padding: 12px 24px;
            font-size: 14px;
            border-radius: 10px;
        }
        
        /* Forms */
        .form-control, input[type="text"], input[type="email"], input[type="password"], 
        input[type="number"], select, textarea {
            width: 100%;
            padding: 7px 10px;
            border: 1px solid var(--admin-border);
            border-radius: 6px;
            font-size: 12px;
            transition: all 0.2s ease;
            background: var(--admin-card);
            color: var(--admin-text);
        }
        
        .form-control:focus, input:focus, select:focus, textarea:focus {
            outline: none;
            border-color: var(--admin-primary);
            box-shadow: 0 0 0 3px var(--admin-primary-very-pale);
        }
        
        .form-group {
            margin-bottom: 12px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: var(--admin-text);
            font-size: 12px;
        }
        
        /* Tables */
        .data-table {
            background: var(--admin-card);
            border-radius: 16px;
            overflow: hidden;
        }
        
        .data-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .data-table th {
            background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-primary-dark) 100%);
            padding: 9px;
            text-align: left;
            font-weight: 600;
            font-size: 11px;
            color: #FFFFFF;
            text-transform: uppercase;
            letter-spacing: 0.3px;
            border-bottom: 2px solid var(--admin-primary-darkest);
        }
        
        .data-table td {
            padding: 9px;
            border-bottom: 1px solid var(--admin-border);
            font-size: 12px;
        }
        
        .data-table tr:hover {
            background: rgba(83, 157, 166, 0.08);
        }
        
        .data-table tr:last-child td {
            border-bottom: none;
        }
        
        /* Badges */
        .badge {
            display: inline-block;
            padding: 6px 12px;
            border-radius: 8px;
            font-size: 12px;
            font-weight: 500;
        }
        
        .badge-success {
            background: rgba(74, 155, 107, 0.15);
            color: var(--admin-success);
        }
        
        .badge-danger {
            background: rgba(200, 90, 90, 0.15);
            color: var(--admin-danger);
        }
        
        .badge-warning {
            background: rgba(212, 165, 116, 0.15);
            color: var(--admin-warning);
        }
        
        .badge-info {
            background: var(--admin-primary-very-pale);
            color: var(--admin-primary-darkest);
        }
        
        .badge-secondary {
            background: rgba(107, 122, 138, 0.15);
            color: var(--admin-text-light);
        }
        
        .badge-primary {
            background: var(--admin-primary-pale);
            color: var(--admin-primary-darkest);
        }
        
        /* Alerts */
        .alert {
            padding: 16px 20px;
            border-radius: 12px;
            margin-bottom: 20px;
            border-left: 4px solid;
            font-size: 14px;
        }
        
        .alert-success {
            background: rgba(74, 155, 107, 0.12);
            border-color: var(--admin-success);
            color: #2d6b4a;
        }
        
        .alert-error, .alert-danger {
            background: rgba(200, 90, 90, 0.12);
            border-color: var(--admin-danger);
            color: #9d4545;
        }
        
        .alert-warning {
            background: rgba(212, 165, 116, 0.12);
            border-color: var(--admin-warning);
            color: #a67a4f;
        }
        
        .alert-info {
            background: var(--admin-primary-very-pale);
            border-color: var(--admin-primary-light);
            color: var(--admin-primary-darkest);
        }
        
        /* Page Header */
        .page-header, .section-header {
            margin-bottom: 14px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .page-header h1, .section-title {
            font-size: 19px;
            font-weight: 600;
            color: var(--admin-text);
            margin: 0;
        }
        
        .page-header h1 i, .section-title i {
            color: var(--admin-primary);
            margin-right: 10px;
        }
        
        /* Stat card icons */
        .stat-card i {
            color: var(--admin-primary) !important;
        }
        
        /* Toolbar */
        .admin-toolbar {
            background: var(--admin-card);
            padding: 10px;
            border-radius: 10px;
            margin-bottom: 10px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 8px;
            box-shadow: 0 4px 12px rgba(2, 40, 89, 0.15);
            border: 1px solid var(--admin-border);
        }
        
        /* Filter Tabs */
        .filter-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        
        .filter-tabs a {
            padding: 10px 20px;
            border-radius: 10px;
            background: var(--admin-card);
            color: var(--admin-text);
            text-decoration: none;
            font-size: 14px;
            font-weight: 500;
            transition: all 0.2s ease;
            border: 1px solid var(--admin-border);
        }
        
        .filter-tabs a:hover {
            background: var(--admin-primary-very-pale);
            border-color: var(--admin-primary-light);
        }
        
        .filter-tabs a.active {
            background: linear-gradient(135deg, var(--admin-primary) 0%, var(--admin-primary-dark) 100%);
            color: #fff;
            border-color: var(--admin-primary);
        }
        
        /* Pagination */
        .pagination {
            display: flex;
            gap: 8px;
            margin-top: 20px;
            justify-content: center;
            flex-wrap: wrap;
        }
        
        .pagination a {
            padding: 10px 16px;
            border-radius: 10px;
            background: var(--admin-card);
            color: var(--admin-text);
            text-decoration: none;
            border: 1px solid var(--admin-border);
            transition: all 0.2s ease;
            font-size: 14px;
        }
        
        .pagination a:hover {
            background: var(--admin-primary-very-pale);
            border-color: var(--admin-primary-light);
        }
        
        .pagination a.active {
            background: var(--admin-primary);
            color: #fff;
            border-color: var(--admin-primary);
        }
        
        /* Stat Cards */
        .stat-card {
            padding: 12px;
            border-radius: 10px;
            background: var(--admin-card);
            border: 1px solid var(--admin-border);
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            position: relative;
        }
        
        /* Utility Classes */
        .d-flex {
            display: flex;
        }
        
        .justify-between {
            justify-content: space-between;
        }
        
        .align-center {
            align-items: center;
        }
        
        .gap-10 {
            gap: 10px;
        }
        
        .mb-20 {
            margin-bottom: 20px;
        }
        
        .mt-20 {
            margin-top: 20px;
        }
        
        /* Responsive */
        @media (max-width: 900px) {
            .admin-layout { 
                grid-template-columns: 1fr;
            }
            .admin-sidebar { 
                position: static;
                height: auto;
            }
            .admin-content {
                padding: 20px;
            }
        }
        
        /* Scrollbar */
        .admin-sidebar::-webkit-scrollbar {
            width: 6px;
        }
        
        .admin-sidebar::-webkit-scrollbar-track {
            background: transparent;
        }
        
        .admin-sidebar::-webkit-scrollbar-thumb {
            background: var(--admin-border);
            border-radius: 3px;
        }
        
        .admin-sidebar::-webkit-scrollbar-thumb:hover {
            background: var(--admin-text-light);
        }
        
        /* Content Grid */
        .content-grid {
            display: grid;
            grid-template-columns: 1fr 350px;
            gap: 20px;
        }
        
        @media (max-width: 1200px) {
            .content-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* Section Header */
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 25px;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .section-title {
            font-size: 24px;
            font-weight: 600;
            color: var(--admin-text);
            margin: 0;
        }
        
        .section-title i {
            color: var(--admin-primary);
            margin-right: 10px;
        }
        
        /* Quantity Input */
        .quantity-input {
            display: flex;
            align-items: center;
            gap: 5px;
        }
        
        .quantity-input button {
            width: 32px;
            height: 32px;
            border: 1px solid var(--admin-border);
            background: var(--admin-card);
            border-radius: 8px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s ease;
        }
        
        .quantity-input button:hover {
            background: var(--admin-primary);
            color: #fff;
            border-color: var(--admin-primary);
        }
        
        .quantity-input input {
            width: 60px;
            text-align: center;
            padding: 6px;
            border: 1px solid var(--admin-border);
            border-radius: 8px;
        }
        
        /* Product Thumb */
        .product-thumb {
            border-radius: 10px;
            border: 1px solid var(--admin-border);
        }
        
        /* Checkbox */
        input[type="checkbox"] {
            width: 18px;
            height: 18px;
            cursor: pointer;
            accent-color: var(--admin-primary);
        }
        
        /* Select */
        select.form-control {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/20000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%234A90E2' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 12px center;
            padding-right: 40px;
            appearance: none;
        }
        
        /* Input File */
        input[type="file"] {
            padding: 8px;
        }
        
        /* Textarea */
        textarea.form-control {
            resize: vertical;
            min-height: 100px;
        }
        
        /* Loading States */
        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none !important;
        }
        
        /* Focus States */
        *:focus-visible {
            outline: 2px solid var(--admin-primary);
            outline-offset: 2px;
        }
        
        /* Smooth Transitions */
        * {
            transition: background-color 0.2s ease, border-color 0.2s ease, color 0.2s ease;
        }
    </style>
</head>
<body>
<div class="admin-layout">
    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <h2><i class="fas fa-cog"></i> Admin Panel</h2>
        <?php if (isStaffOnly()): ?>
        <div class="staff-info">
            <i class="fas fa-user-tie"></i> Nhân viên: <strong><?php echo htmlspecialchars($_SESSION['username'] ?? ''); ?></strong>
        </div>
        <?php endif; ?>
        <ul class="admin-menu">
            <li><a href="<?php echo adminUrl('index.php'); ?>" class="<?php if(isset($activeMenu) && $activeMenu=='dashboard') echo 'active';?>"><i class="fas fa-home"></i> Dashboard</a></li>
            <li><a href="<?php echo adminUrl('products/index.php'); ?>" class="<?php if(isset($activeMenu) && $activeMenu=='products') echo 'active';?>"><i class="fas fa-box"></i> Sản phẩm</a></li>
            <li><a href="<?php echo adminUrl('categories/index.php'); ?>" class="<?php if(isset($activeMenu) && $activeMenu=='categories') echo 'active';?>"><i class="fas fa-list"></i> Danh mục</a></li>
            <li><a href="<?php echo adminUrl('suppliers/index.php'); ?>" class="<?php if(isset($activeMenu) && $activeMenu=='suppliers') echo 'active';?>"><i class="fas fa-truck"></i> Nhà cung cấp</a></li>
            <li><a href="<?php echo adminUrl('orders/index.php'); ?>" class="<?php if(isset($activeMenu) && $activeMenu=='orders') echo 'active';?>"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
            <li><a href="<?php echo adminUrl('ratings/index.php'); ?>" class="<?php if(isset($activeMenu) && $activeMenu=='ratings') echo 'active';?>"><i class="fas fa-star"></i> Đánh giá</a></li>
            <li><a href="<?php echo adminUrl('user/customers.php'); ?>" class="<?php if(isset($activeMenu) && $activeMenu=='customers') echo 'active';?>"><i class="fas fa-users"></i> Khách hàng</a></li>
            <?php if (isAdmin()): ?>
            <li><a href="<?php echo adminUrl('user/staff.php'); ?>" class="<?php if(isset($activeMenu) && $activeMenu=='staff') echo 'active';?>"><i class="fas fa-user-tie"></i> Nhân viên</a></li>
            <?php endif; ?>
            <li><a href="<?php echo adminUrl('coupons/index.php'); ?>" class="<?php if(isset($activeMenu) && $activeMenu=='coupons') echo 'active';?>"><i class="fas fa-tags"></i> Mã giảm giá</a></li>
            <li><a href="<?php echo adminUrl('news/index.php'); ?>" class="<?php if(isset($activeMenu) && $activeMenu=='news') echo 'active';?>"><i class="fas fa-newspaper"></i> Tin tức</a></li>
            <li><a href="<?php echo adminUrl('banners/index.php'); ?>" class="<?php if(isset($activeMenu) && $activeMenu=='banners') echo 'active';?>"><i class="fas fa-image"></i> Banner</a></li>
            <li><a href="<?php echo adminUrl('contacts/index.php'); ?>" class="<?php if(isset($activeMenu) && $activeMenu=='contacts') echo 'active';?>"><i class="fas fa-envelope"></i> Liên hệ</a></li>
            <li><a href="<?php echo adminUrl('statistics/index.php'); ?>" class="<?php if(isset($activeMenu) && $activeMenu=='statistics') echo 'active';?>"><i class="fas fa-chart-bar"></i> Thống kê</a></li>
            <li><hr></li>
            <li><a href="<?php echo SITE_URL; ?>/index.php"><i class="fas fa-home"></i> Về trang chủ</a></li>
            <li><a href="<?php echo SITE_URL; ?>/customer/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
        </ul>
    </aside>
    <!-- Main Content -->
    <main class="admin-content">
