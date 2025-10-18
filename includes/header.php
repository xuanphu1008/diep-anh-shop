<?php
// includes/header.php - Header template

if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$cartCount = getCartCount();
?>
<header>
    <div class="header-top">
        <div class="container">
            <div>
                <i class="fas fa-phone"></i> Hotline: 0123.456.789
                <i class="fas fa-envelope" style="margin-left: 20px;"></i> admin@diepanhshop.com
            </div>
            <div>
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo SITE_URL; ?>/customer/profile.php">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>
                    </a>
                    <?php if (isStaff()): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/index.php" style="margin-left: 15px;">
                        <i class="fas fa-cog"></i> Quản trị
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo SITE_URL; ?>/customer/logout.php" style="margin-left: 15px;">
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                    </a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/customer/login.php">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập
                    </a>
                    <a href="<?php echo SITE_URL; ?>/customer/register.php" style="margin-left: 15px;">
                        <i class="fas fa-user-plus"></i> Đăng ký
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="header-main">
        <div class="container">
            <div class="logo">
                <a href="<?php echo SITE_URL; ?>">
                    <i class="fas fa-laptop"></i> Diệp Anh
                </a>
            </div>
            
            <div class="search-bar">
                <form action="<?php echo SITE_URL; ?>/products.php" method="GET">
                    <input type="text" name="search" placeholder="Tìm kiếm sản phẩm..." 
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <button type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            
            <div class="header-actions">
                <a href="<?php echo SITE_URL; ?>/customer/cart.php" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cartCount; ?></span>
                </a>
            </div>
        </div>
    </div>
    
    <nav>
        <div class="container">
            <ul>
                <li><a href="<?php echo SITE_URL; ?>"><i class="fas fa-home"></i> Trang chủ</a></li>
                <li><a href="<?php echo SITE_URL; ?>/products.php"><i class="fas fa-laptop"></i> Sản phẩm</a></li>
                <li><a href="<?php echo SITE_URL; ?>/products.php?filter=hot"><i class="fas fa-fire"></i> Hot</a></li>
                <li><a href="<?php echo SITE_URL; ?>/products.php?filter=discount"><i class="fas fa-tags"></i> Khuyến mãi</a></li>
                <li><a href="<?php echo SITE_URL; ?>/news.php"><i class="fas fa-newspaper"></i> Tin tức</a></li>
                <li><a href="<?php echo SITE_URL; ?>/contact.php"><i class="fas fa-phone"></i> Liên hệ</a></li>
            </ul>
        </div>
    </nav>
</header>