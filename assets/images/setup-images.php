<?php
// assets/images/setup-images.php - Script tạo cấu trúc ảnh sản phẩm

require_once __DIR__ . '/../../config/config.php';

// Danh sách ảnh sản phẩm cần tạo
$productImages = [
    // Laptop Gaming
    'laptop-gaming-1.jpg', 'laptop-gaming-1-2.jpg', 'laptop-gaming-1-3.jpg',
    'laptop-gaming-2.jpg', 'laptop-gaming-2-2.jpg',
    'laptop-gaming-3.jpg', 'laptop-gaming-3-2.jpg', 'laptop-gaming-3-3.jpg',
    
    // Laptop Văn phòng
    'laptop-vanphong-1.jpg', 'laptop-vanphong-1-2.jpg',
    'laptop-vanphong-2.jpg', 'laptop-vanphong-2-2.jpg', 'laptop-vanphong-2-3.jpg',
    'laptop-vanphong-3.jpg', 'laptop-vanphong-3-2.jpg',
    
    // PC Gaming
    'pc-gaming-1.jpg', 'pc-gaming-1-2.jpg', 'pc-gaming-1-3.jpg',
    'pc-gaming-2.jpg', 'pc-gaming-2-2.jpg',
    'pc-gaming-3.jpg', 'pc-gaming-3-2.jpg', 'pc-gaming-3-3.jpg',
    
    // Linh kiện
    'card-dohoa-1.jpg', 'card-dohoa-1-2.jpg', 'card-dohoa-1-3.jpg',
    'ram-ddr4-1.jpg', 'ram-ddr4-1-2.jpg',
    'ssd-nvme-1.jpg', 'ssd-nvme-1-2.jpg', 'ssd-nvme-1-3.jpg',
    'cpu-intel-1.jpg', 'cpu-intel-1-2.jpg',
    'mainboard-1.jpg', 'mainboard-1-2.jpg', 'mainboard-1-3.jpg',
    'psu-corsair-1.jpg', 'psu-corsair-1-2.jpg'
];

$categoryImages = [
    'laptop-gaming.jpg',
    'laptop-van-phong.jpg',
    'pc-gaming.jpg',
    'linh-kien-may-tinh.jpg'
];

$bannerImages = [
    'banner-laptop-gaming.jpg',
    'banner-pc-gaming.jpg',
    'banner-linh-kien.jpg',
    'banner-laptop-vanphong.jpg',
    'banner-flash-sale.jpg'
];

$newsImages = [
    'news-laptop-gaming-2024.jpg',
    'news-build-pc-gaming.jpg',
    'news-intel-vs-amd.jpg',
    'news-rtx-40-series.jpg',
    'news-optimize-laptop-gaming.jpg'
];

$defaultImages = [
    'default.jpg',
    'default-category.jpg',
    'default-banner.jpg',
    'default-news.jpg',
    'placeholder.jpg'
];

// Tạo thư mục nếu chưa có
$directories = [
    'products',
    'categories', 
    'banners',
    'news'
];

foreach ($directories as $dir) {
    $path = __DIR__ . '/' . $dir;
    if (!is_dir($path)) {
        mkdir($path, 0755, true);
        echo "✅ Tạo thư mục: $dir\n";
    }
}

// Tạo file placeholder cho sản phẩm
foreach ($productImages as $image) {
    $filePath = __DIR__ . '/products/' . $image;
    if (!file_exists($filePath)) {
        createPlaceholderImage($filePath, 800, 600, $image);
        echo "✅ Tạo ảnh sản phẩm: $image\n";
    }
}

// Tạo file placeholder cho danh mục
foreach ($categoryImages as $image) {
    $filePath = __DIR__ . '/categories/' . $image;
    if (!file_exists($filePath)) {
        createPlaceholderImage($filePath, 400, 300, $image);
        echo "✅ Tạo ảnh danh mục: $image\n";
    }
}

// Tạo file placeholder cho banner
foreach ($bannerImages as $image) {
    $filePath = __DIR__ . '/banners/' . $image;
    if (!file_exists($filePath)) {
        createPlaceholderImage($filePath, 1200, 400, $image);
        echo "✅ Tạo ảnh banner: $image\n";
    }
}

// Tạo file placeholder cho tin tức
foreach ($newsImages as $image) {
    $filePath = __DIR__ . '/news/' . $image;
    if (!file_exists($filePath)) {
        createPlaceholderImage($filePath, 800, 450, $image);
        echo "✅ Tạo ảnh tin tức: $image\n";
    }
}

// Tạo ảnh mặc định
foreach ($defaultImages as $image) {
    $filePath = __DIR__ . '/products/' . $image;
    if (!file_exists($filePath)) {
        createPlaceholderImage($filePath, 800, 600, $image);
        echo "✅ Tạo ảnh mặc định: $image\n";
    }
}

echo "\n🎉 Hoàn thành tạo cấu trúc ảnh!\n";
echo "📁 Tổng số file đã tạo: " . (count($productImages) + count($categoryImages) + count($bannerImages) + count($newsImages) + count($defaultImages)) . "\n";

/**
 * Tạo ảnh placeholder
 */
function createPlaceholderImage($filePath, $width, $height, $text) {
    // Tạo ảnh với màu nền
    $image = imagecreatetruecolor($width, $height);
    
    // Màu nền gradient
    $bgColor1 = imagecolorallocate($image, 240, 240, 240);
    $bgColor2 = imagecolorallocate($image, 220, 220, 220);
    
    // Tạo gradient
    for ($i = 0; $i < $height; $i++) {
        $ratio = $i / $height;
        $r = 240 - (240 - 220) * $ratio;
        $g = 240 - (240 - 220) * $ratio;
        $b = 240 - (240 - 220) * $ratio;
        $color = imagecolorallocate($image, $r, $g, $b);
        imageline($image, 0, $i, $width, $i, $color);
    }
    
    // Màu chữ
    $textColor = imagecolorallocate($image, 100, 100, 100);
    $borderColor = imagecolorallocate($image, 200, 200, 200);
    
    // Vẽ border
    imagerectangle($image, 0, 0, $width-1, $height-1, $borderColor);
    
    // Vẽ text
    $fontSize = min($width, $height) / 20;
    $text = str_replace(['.jpg', '-'], ['', ' '], $text);
    $text = ucwords($text);
    
    // Tính toán vị trí text
    $bbox = imagettfbbox($fontSize, 0, __DIR__ . '/arial.ttf', $text);
    $textWidth = $bbox[4] - $bbox[0];
    $textHeight = $bbox[1] - $bbox[5];
    
    $x = ($width - $textWidth) / 2;
    $y = ($height + $textHeight) / 2;
    
    // Vẽ text (nếu có font)
    if (file_exists(__DIR__ . '/arial.ttf')) {
        imagettftext($image, $fontSize, 0, $x, $y, $textColor, __DIR__ . '/arial.ttf', $text);
    } else {
        // Sử dụng font mặc định
        imagestring($image, 5, $x, $y, $text, $textColor);
    }
    
    // Lưu ảnh
    imagejpeg($image, $filePath, 90);
    imagedestroy($image);
}

/**
 * Tạo ảnh placeholder đơn giản (không cần font)
 */
function createSimplePlaceholder($filePath, $width, $height, $text) {
    $image = imagecreatetruecolor($width, $height);
    
    // Màu nền
    $bgColor = imagecolorallocate($image, 245, 245, 245);
    imagefill($image, 0, 0, $bgColor);
    
    // Màu border
    $borderColor = imagecolorallocate($image, 200, 200, 200);
    imagerectangle($image, 0, 0, $width-1, $height-1, $borderColor);
    
    // Màu text
    $textColor = imagecolorallocate($image, 150, 150, 150);
    
    // Vẽ text đơn giản
    $fontSize = 5;
    $text = str_replace(['.jpg', '-'], ['', ' '], $text);
    $text = strtoupper($text);
    
    // Cắt text nếu quá dài
    if (strlen($text) > 20) {
        $text = substr($text, 0, 17) . '...';
    }
    
    $x = ($width - strlen($text) * 10) / 2;
    $y = $height / 2;
    
    imagestring($image, $fontSize, $x, $y, $text, $textColor);
    
    // Lưu ảnh
    imagejpeg($image, $filePath, 90);
    imagedestroy($image);
}
?>
