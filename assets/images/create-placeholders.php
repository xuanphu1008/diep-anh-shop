<?php
// assets/images/create-placeholders.php - Tạo ảnh placeholder đơn giản

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
    'psu-corsair-1.jpg', 'psu-corsair-1-2.jpg',
    
    // Ảnh mặc định
    'default.jpg', 'placeholder.jpg'
];

$categoryImages = [
    'laptop-gaming.jpg',
    'laptop-van-phong.jpg', 
    'pc-gaming.jpg',
    'linh-kien-may-tinh.jpg',
    'default-category.jpg'
];

$bannerImages = [
    'banner-laptop-gaming.jpg',
    'banner-pc-gaming.jpg',
    'banner-linh-kien.jpg',
    'banner-laptop-vanphong.jpg',
    'banner-flash-sale.jpg',
    'default-banner.jpg'
];

$newsImages = [
    'news-laptop-gaming-2024.jpg',
    'news-build-pc-gaming.jpg',
    'news-intel-vs-amd.jpg',
    'news-rtx-40-series.jpg',
    'news-optimize-laptop-gaming.jpg',
    'default-news.jpg'
];

echo "🚀 Bắt đầu tạo ảnh placeholder...\n\n";

// Tạo ảnh sản phẩm
foreach ($productImages as $image) {
    createPlaceholder(__DIR__ . '/products/' . $image, 800, 600, $image);
    echo "✅ Tạo: products/$image\n";
}

// Tạo ảnh danh mục
foreach ($categoryImages as $image) {
    createPlaceholder(__DIR__ . '/categories/' . $image, 400, 300, $image);
    echo "✅ Tạo: categories/$image\n";
}

// Tạo ảnh banner
foreach ($bannerImages as $image) {
    createPlaceholder(__DIR__ . '/banners/' . $image, 1200, 400, $image);
    echo "✅ Tạo: banners/$image\n";
}

// Tạo ảnh tin tức
foreach ($newsImages as $image) {
    createPlaceholder(__DIR__ . '/news/' . $image, 800, 450, $image);
    echo "✅ Tạo: news/$image\n";
}

echo "\n🎉 Hoàn thành! Tổng cộng đã tạo " . 
     (count($productImages) + count($categoryImages) + count($bannerImages) + count($newsImages)) . 
     " ảnh placeholder.\n";

/**
 * Tạo ảnh placeholder đơn giản
 */
function createPlaceholder($filePath, $width, $height, $filename) {
    // Tạo ảnh
    $image = imagecreatetruecolor($width, $height);
    
    // Màu nền
    $bgColor = imagecolorallocate($image, 248, 249, 250);
    imagefill($image, 0, 0, $bgColor);
    
    // Màu border
    $borderColor = imagecolorallocate($image, 222, 226, 230);
    imagerectangle($image, 0, 0, $width-1, $height-1, $borderColor);
    
    // Màu text
    $textColor = imagecolorallocate($image, 108, 117, 125);
    
    // Tạo text từ filename
    $text = str_replace(['.jpg', '-'], ['', ' '], $filename);
    $text = ucwords($text);
    
    // Cắt text nếu quá dài
    if (strlen($text) > 25) {
        $text = substr($text, 0, 22) . '...';
    }
    
    // Tính vị trí text
    $fontSize = 5;
    $textWidth = strlen($text) * 10;
    $textHeight = 15;
    
    $x = ($width - $textWidth) / 2;
    $y = ($height - $textHeight) / 2;
    
    // Vẽ text
    imagestring($image, $fontSize, $x, $y, $text, $textColor);
    
    // Vẽ kích thước
    $sizeText = $width . 'x' . $height;
    $sizeX = ($width - strlen($sizeText) * 6) / 2;
    $sizeY = $y + 20;
    imagestring($image, 3, $sizeX, $sizeY, $sizeText, $textColor);
    
    // Lưu ảnh
    imagejpeg($image, $filePath, 85);
    imagedestroy($image);
}
?>
