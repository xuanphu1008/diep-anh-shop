<?php
// customer/logout.php - Đăng xuất

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

// Hủy session
session_start();
session_unset();
session_destroy();

// Xóa cookie remember
if (isset($_COOKIE['remember_user'])) {
    setcookie('remember_user', '', time() - 3600, '/');
}

// Redirect về trang chủ
setFlashMessage('success', 'Đăng xuất thành công');
redirect(SITE_URL . '/index.php');
?>