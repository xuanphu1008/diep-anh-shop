<?php
// assets/images/example-usage.php - Ví dụ sử dụng ảnh sản phẩm

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/functions.php';

// Dữ liệu sản phẩm mẫu
$products = [
    [
        'id' => 1,
        'name' => 'ASUS ROG Strix G15 Gaming',
        'image' => 'laptop-gaming-1.jpg',
        'images' => '["laptop-gaming-1.jpg", "laptop-gaming-1-2.jpg", "laptop-gaming-1-3.jpg"]',
        'price' => 25990000,
        'discount_price' => 23990000
    ],
    [
        'id' => 2,
        'name' => 'MSI Gaming GF63 Thin',
        'image' => 'laptop-gaming-2.jpg',
        'images' => '["laptop-gaming-2.jpg", "laptop-gaming-2-2.jpg"]',
        'price' => 18990000,
        'discount_price' => 17990000
    ]
];

$categories = [
    [
        'id' => 1,
        'name' => 'Laptop Gaming',
        'image' => 'laptop-gaming.jpg'
    ],
    [
        'id' => 2,
        'name' => 'Laptop Văn phòng',
        'image' => 'laptop-vanphong.jpg'
    ]
];

$banners = [
    [
        'id' => 1,
        'title' => 'Khuyến mãi laptop gaming',
        'image' => 'banner-laptop-gaming.jpg',
        'link' => '/products?category=laptop-gaming'
    ]
];

$news = [
    [
        'id' => 1,
        'title' => 'Xu hướng laptop gaming 2024',
        'image' => 'news-laptop-gaming-2024.jpg'
    ]
];
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ví dụ hiển thị ảnh sản phẩm</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 1200px; margin: 0 auto; }
        .section { margin: 40px 0; }
        .section h2 { color: #333; border-bottom: 2px solid #007bff; padding-bottom: 10px; }
        .products-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 20px; }
        .product-card { border: 1px solid #ddd; border-radius: 8px; overflow: hidden; }
        .product-image { width: 100%; height: 200px; object-fit: cover; }
        .product-info { padding: 15px; }
        .product-name { font-weight: bold; margin-bottom: 10px; }
        .product-price { color: #e74c3c; font-size: 18px; }
        .product-discount { color: #95a5a6; text-decoration: line-through; margin-right: 10px; }
        .categories-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; }
        .category-card { text-align: center; padding: 20px; border: 1px solid #ddd; border-radius: 8px; }
        .category-image { width: 100%; height: 150px; object-fit: cover; border-radius: 4px; }
        .banner { width: 100%; height: 300px; object-fit: cover; border-radius: 8px; }
        .news-item { display: flex; gap: 20px; margin-bottom: 20px; padding: 15px; border: 1px solid #ddd; border-radius: 8px; }
        .news-image { width: 200px; height: 150px; object-fit: cover; border-radius: 4px; }
        .news-content { flex: 1; }
        .code-example { background: #f8f9fa; padding: 15px; border-radius: 4px; margin: 10px 0; }
        .code-example pre { margin: 0; }
    </style>
</head>
<body>
    <div class="container">
        <h1>📸 Ví dụ hiển thị ảnh sản phẩm</h1>
        
        <!-- Sản phẩm -->
        <div class="section">
            <h2>🛍️ Sản phẩm</h2>
            <div class="products-grid">
                <?php foreach ($products as $product): ?>
                <div class="product-card">
                    <img src="<?php echo getProductImage($product['image']); ?>" 
                         alt="<?php echo htmlspecialchars($product['name']); ?>" 
                         class="product-image">
                    <div class="product-info">
                        <div class="product-name"><?php echo htmlspecialchars($product['name']); ?></div>
                        <div class="product-price">
                            <?php if ($product['discount_price']): ?>
                                <span class="product-discount"><?php echo formatCurrency($product['price']); ?></span>
                                <?php echo formatCurrency($product['discount_price']); ?>
                            <?php else: ?>
                                <?php echo formatCurrency($product['price']); ?>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="code-example">
                <h3>Code hiển thị ảnh sản phẩm:</h3>
                <pre><code>&lt;img src="&lt;?php echo getProductImage($product['image']); ?&gt;" 
     alt="&lt;?php echo htmlspecialchars($product['name']); ?&gt;" 
     class="product-image"&gt;</code></pre>
            </div>
        </div>
        
        <!-- Danh mục -->
        <div class="section">
            <h2>📂 Danh mục</h2>
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                <div class="category-card">
                    <img src="<?php echo getCategoryImage($category['image']); ?>" 
                         alt="<?php echo htmlspecialchars($category['name']); ?>" 
                         class="category-image">
                    <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                </div>
                <?php endforeach; ?>
            </div>
            
            <div class="code-example">
                <h3>Code hiển thị ảnh danh mục:</h3>
                <pre><code>&lt;img src="&lt;?php echo getCategoryImage($category['image']); ?&gt;" 
     alt="&lt;?php echo htmlspecialchars($category['name']); ?&gt;" 
     class="category-image"&gt;</code></pre>
            </div>
        </div>
        
        <!-- Banner -->
        <div class="section">
            <h2>🎯 Banner</h2>
            <?php foreach ($banners as $banner): ?>
            <img src="<?php echo getBannerImage($banner['image']); ?>" 
                 alt="<?php echo htmlspecialchars($banner['title']); ?>" 
                 class="banner">
            <?php endforeach; ?>
            
            <div class="code-example">
                <h3>Code hiển thị ảnh banner:</h3>
                <pre><code>&lt;img src="&lt;?php echo getBannerImage($banner['image']); ?&gt;" 
     alt="&lt;?php echo htmlspecialchars($banner['title']); ?&gt;" 
     class="banner"&gt;</code></pre>
            </div>
        </div>
        
        <!-- Tin tức -->
        <div class="section">
            <h2>📰 Tin tức</h2>
            <?php foreach ($news as $article): ?>
            <div class="news-item">
                <img src="<?php echo getNewsImage($article['image']); ?>" 
                     alt="<?php echo htmlspecialchars($article['title']); ?>" 
                     class="news-image">
                <div class="news-content">
                    <h3><?php echo htmlspecialchars($article['title']); ?></h3>
                    <p>Nội dung bài viết...</p>
                </div>
            </div>
            <?php endforeach; ?>
            
            <div class="code-example">
                <h3>Code hiển thị ảnh tin tức:</h3>
                <pre><code>&lt;img src="&lt;?php echo getNewsImage($news['image']); ?&gt;" 
     alt="&lt;?php echo htmlspecialchars($news['title']); ?&gt;" 
     class="news-image"&gt;</code></pre>
            </div>
        </div>
        
        <!-- Lazy Loading -->
        <div class="section">
            <h2>⚡ Lazy Loading</h2>
            <div class="code-example">
                <h3>Code lazy loading:</h3>
                <pre><code>&lt;?php echo generateLazyImageHTML(
    getProductImage($product['image']), 
    $product['name'], 
    'product-image',
    'assets/images/placeholder.jpg'
); ?&gt;</code></pre>
            </div>
        </div>
        
        <!-- Responsive Images -->
        <div class="section">
            <h2>📱 Responsive Images</h2>
            <div class="code-example">
                <h3>Code responsive image:</h3>
                <pre><code>&lt;?php echo generateImageHTML(
    getProductImage($product['image']),
    $product['name'],
    'product-image',
    [
        'width' => '300',
        'height' => '200',
        'loading' => 'lazy'
    ]
); ?&gt;</code></pre>
            </div>
        </div>
    </div>
</body>
</html>
