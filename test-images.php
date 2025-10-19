<?php
// test-images.php - Test hiển thị ảnh sản phẩm

require_once 'config/config.php';
require_once 'includes/functions.php';

echo "<h1>🧪 Test hiển thị ảnh sản phẩm</h1>";

// Test ảnh sản phẩm
echo "<h2>📸 Test ảnh sản phẩm</h2>";
$testImages = [
    'laptop-gaming-1.jpg',
    'laptop-gaming-2.jpg', 
    'laptop-vanphong-1.jpg',
    'card-dohoa-1.jpg',
    'ram-ddr4-1.jpg',
    'ssd-nvme-1.jpg',
    'cpu-intel-1.jpg',
    'mainboard-1.jpg',
    'psu-corsair-1.jpg'
];

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;'>";
foreach ($testImages as $image) {
    $imageUrl = getProductImage($image);
    echo "<div style='border: 1px solid #ddd; padding: 10px; text-align: center;'>";
    echo "<h4>" . htmlspecialchars($image) . "</h4>";
    echo "<img src='$imageUrl' alt='$image' style='width: 150px; height: 100px; object-fit: cover; border: 1px solid #ccc;'>";
    echo "<br><small>URL: $imageUrl</small>";
    echo "</div>";
}
echo "</div>";

// Test ảnh danh mục
echo "<h2>📂 Test ảnh danh mục</h2>";
$categoryImages = [
    'laptop-gaming.jpg',
    'laptop-van-phong.jpg',
    'pc-gaming.jpg',
    'linh-kien-may-tinh.jpg'
];

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;'>";
foreach ($categoryImages as $image) {
    $imageUrl = getCategoryImage($image);
    echo "<div style='border: 1px solid #ddd; padding: 10px; text-align: center;'>";
    echo "<h4>" . htmlspecialchars($image) . "</h4>";
    echo "<img src='$imageUrl' alt='$image' style='width: 150px; height: 100px; object-fit: cover; border: 1px solid #ccc;'>";
    echo "<br><small>URL: $imageUrl</small>";
    echo "</div>";
}
echo "</div>";

// Test ảnh banner
echo "<h2>🎯 Test ảnh banner</h2>";
$bannerImages = [
    'banner-laptop-gaming.jpg',
    'banner-pc-gaming.jpg',
    'banner-linh-kien.jpg'
];

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;'>";
foreach ($bannerImages as $image) {
    $imageUrl = getBannerImage($image);
    echo "<div style='border: 1px solid #ddd; padding: 10px; text-align: center;'>";
    echo "<h4>" . htmlspecialchars($image) . "</h4>";
    echo "<img src='$imageUrl' alt='$image' style='width: 150px; height: 50px; object-fit: cover; border: 1px solid #ccc;'>";
    echo "<br><small>URL: $imageUrl</small>";
    echo "</div>";
}
echo "</div>";

// Test ảnh tin tức
echo "<h2>📰 Test ảnh tin tức</h2>";
$newsImages = [
    'news-laptop-gaming-2024.jpg',
    'news-build-pc-gaming.jpg',
    'news-intel-vs-amd.jpg'
];

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;'>";
foreach ($newsImages as $image) {
    $imageUrl = getNewsImage($image);
    echo "<div style='border: 1px solid #ddd; padding: 10px; text-align: center;'>";
    echo "<h4>" . htmlspecialchars($image) . "</h4>";
    echo "<img src='$imageUrl' alt='$image' style='width: 150px; height: 100px; object-fit: cover; border: 1px solid #ccc;'>";
    echo "<br><small>URL: $imageUrl</small>";
    echo "</div>";
}
echo "</div>";

// Test ảnh mặc định
echo "<h2>🖼️ Test ảnh mặc định</h2>";
$defaultImages = [
    'default.jpg',
    'default-category.jpg',
    'default-banner.jpg',
    'default-news.jpg'
];

echo "<div style='display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin: 20px 0;'>";
foreach ($defaultImages as $image) {
    $imageUrl = getProductImage($image);
    echo "<div style='border: 1px solid #ddd; padding: 10px; text-align: center;'>";
    echo "<h4>" . htmlspecialchars($image) . "</h4>";
    echo "<img src='$imageUrl' alt='$image' style='width: 150px; height: 100px; object-fit: cover; border: 1px solid #ccc;'>";
    echo "<br><small>URL: $imageUrl</small>";
    echo "</div>";
}
echo "</div>";

echo "<h2>🔧 Debug thông tin</h2>";
echo "<p><strong>SITE_URL:</strong> " . SITE_URL . "</p>";
echo "<p><strong>UPLOAD_URL:</strong> " . UPLOAD_URL . "</p>";
echo "<p><strong>Thư mục assets/images/products:</strong> " . (is_dir('assets/images/products') ? '✅ Tồn tại' : '❌ Không tồn tại') . "</p>";
echo "<p><strong>Thư mục assets/images/categories:</strong> " . (is_dir('assets/images/categories') ? '✅ Tồn tại' : '❌ Không tồn tại') . "</p>";
echo "<p><strong>Thư mục assets/images/banners:</strong> " . (is_dir('assets/images/banners') ? '✅ Tồn tại' : '❌ Không tồn tại') . "</p>";
echo "<p><strong>Thư mục assets/images/news:</strong> " . (is_dir('assets/images/news') ? '✅ Tồn tại' : '❌ Không tồn tại') . "</p>";

// Kiểm tra một số file cụ thể
$testFiles = [
    'assets/images/products/laptop-gaming-1.jpg',
    'assets/images/products/default.jpg',
    'assets/images/categories/laptop-gaming.jpg',
    'assets/images/banners/banner-laptop-gaming.jpg'
];

echo "<h3>📁 Kiểm tra file cụ thể:</h3>";
foreach ($testFiles as $file) {
    echo "<p><strong>$file:</strong> " . (file_exists($file) ? '✅ Tồn tại' : '❌ Không tồn tại') . "</p>";
}
?>
