<?php
// index.php - Trang chủ website

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'models/Product.php';
require_once 'models/Category.php';
require_once 'models/News.php';
require_once 'models/Banner.php';

$productModel = new Product();
$categoryModel = new Category();
$newsModel = new News();
$bannerModel = new Banner();

// Lấy dữ liệu
$banners = $bannerModel->getActiveBanners();
$categories = $categoryModel->getAllCategories();
$hotProducts = $productModel->getHotProducts(8);
$bestSelling = $productModel->getBestSellingProducts(8);
$newProducts = $productModel->getNewProducts(8);
$discountedProducts = $productModel->getDiscountedProducts(4);
$latestNews = $newsModel->getAllNews(3);

$pageTitle = 'Trang chủ - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Banner Slider -->
    <section class="banner-section">
        <div class="banner-slider">
            <?php foreach ($banners as $banner): ?>
            <div class="banner-item">
                <a href="<?php echo htmlspecialchars($banner['link']); ?>">
                    <img src="<?php echo UPLOAD_URL . htmlspecialchars($banner['image']); ?>" 
                         alt="<?php echo htmlspecialchars($banner['title']); ?>">
                </a>
            </div>
            <?php endforeach; ?>
        </div>
    </section>

    <!-- Categories -->
    <section class="categories-section">
        <div class="container">
            <h2 class="section-title">Danh mục sản phẩm</h2>
            <div class="categories-grid">
                <?php foreach ($categories as $category): ?>
                <a href="products.php?category=<?php echo $category['slug']; ?>" class="category-card">
                    <i class="fas fa-laptop"></i>
                    <h3><?php echo htmlspecialchars($category['name']); ?></h3>
                    <p><?php echo $productModel->getProductsByCategory($category['id'], 1)[0] ? 
                        count($productModel->getProductsByCategory($category['id'])) : 0; ?> sản phẩm</p>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Hot Products -->
    <section class="products-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-fire"></i> Sản phẩm Hot</h2>
                <a href="products.php?filter=hot" class="view-all">Xem tất cả <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="products-grid">
                <?php foreach ($hotProducts as $product): ?>
                <div class="product-card">
                    <?php if ($product['discount_price']): ?>
                    <span class="badge badge-sale">
                        -<?php echo round((($product['price'] - $product['discount_price']) / $product['price']) * 100); ?>%
                    </span>
                    <?php endif; ?>
                    
                    <a href="product-detail.php?slug=<?php echo $product['slug']; ?>">
                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image">
                    </a>
                    
                    <div class="product-info">
                        <h3 class="product-name">
                            <a href="product-detail.php?slug=<?php echo $product['slug']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                        </h3>
                        
                        <div class="product-price">
                            <?php if ($product['discount_price']): ?>
                                <span class="price-old"><?php echo number_format($product['price']); ?>đ</span>
                                <span class="price-new"><?php echo number_format($product['discount_price']); ?>đ</span>
                            <?php else: ?>
                                <span class="price-new"><?php echo number_format($product['price']); ?>đ</span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-actions">
                            <button class="btn btn-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                                <i class="fas fa-shopping-cart"></i> Thêm vào giỏ
                            </button>
                            <button class="btn btn-buy" onclick="buyNow(<?php echo $product['id']; ?>)">
                                Mua ngay
                            </button>
                        </div>
                        
                        <?php if ($product['quantity'] <= 0): ?>
                        <div class="out-of-stock">Hết hàng</div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Best Selling Products -->
    <section class="products-section bg-light">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-star"></i> Sản phẩm bán chạy</h2>
                <a href="products.php?filter=bestselling" class="view-all">Xem tất cả <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="products-grid">
                <?php foreach ($bestSelling as $product): ?>
                <!-- Tương tự như Hot Products -->
                <div class="product-card">
                    <a href="product-detail.php?slug=<?php echo $product['slug']; ?>">
                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>" 
                             class="product-image">
                    </a>
                    <div class="product-info">
                        <h3 class="product-name">
                            <a href="product-detail.php?slug=<?php echo $product['slug']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                        </h3>
                        <div class="product-price">
                            <?php if ($product['discount_price']): ?>
                                <span class="price-old"><?php echo number_format($product['price']); ?>đ</span>
                                <span class="price-new"><?php echo number_format($product['discount_price']); ?>đ</span>
                            <?php else: ?>
                                <span class="price-new"><?php echo number_format($product['price']); ?>đ</span>
                            <?php endif; ?>
                        </div>
                        <div class="sold-count">
                            <i class="fas fa-check-circle"></i> Đã bán: <?php echo $product['sold_quantity']; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Discount Products -->
    <?php if (!empty($discountedProducts)): ?>
    <section class="products-section discount-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-tags"></i> Sản phẩm giảm giá</h2>
                <a href="products.php?filter=discount" class="view-all">Xem tất cả <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="products-grid">
                <?php foreach ($discountedProducts as $product): ?>
                <div class="product-card">
                    <span class="badge badge-sale">
                        -<?php echo round((($product['price'] - $product['discount_price']) / $product['price']) * 100); ?>%
                    </span>
                    <a href="product-detail.php?slug=<?php echo $product['slug']; ?>">
                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($product['image']); ?>" 
                             alt="<?php echo htmlspecialchars($product['name']); ?>">
                    </a>
                    <div class="product-info">
                        <h3 class="product-name">
                            <a href="product-detail.php?slug=<?php echo $product['slug']; ?>">
                                <?php echo htmlspecialchars($product['name']); ?>
                            </a>
                        </h3>
                        <div class="product-price">
                            <span class="price-old"><?php echo number_format($product['price']); ?>đ</span>
                            <span class="price-new"><?php echo number_format($product['discount_price']); ?>đ</span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- News Section -->
    <section class="news-section">
        <div class="container">
            <div class="section-header">
                <h2 class="section-title"><i class="fas fa-newspaper"></i> Tin tức mới nhất</h2>
                <a href="news.php" class="view-all">Xem tất cả <i class="fas fa-arrow-right"></i></a>
            </div>
            <div class="news-grid">
                <?php foreach ($latestNews as $news): ?>
                <div class="news-card">
                    <a href="news-detail.php?slug=<?php echo $news['slug']; ?>">
                        <img src="<?php echo UPLOAD_URL . htmlspecialchars($news['image']); ?>" 
                             alt="<?php echo htmlspecialchars($news['title']); ?>">
                    </a>
                    <div class="news-info">
                        <h3>
                            <a href="news-detail.php?slug=<?php echo $news['slug']; ?>">
                                <?php echo htmlspecialchars($news['title']); ?>
                            </a>
                        </h3>
                        <p class="news-meta">
                            <i class="fas fa-user"></i> <?php echo htmlspecialchars($news['author_name']); ?>
                            <i class="fas fa-calendar"></i> <?php echo date('d/m/Y', strtotime($news['created_at'])); ?>
                            <i class="fas fa-eye"></i> <?php echo $news['views']; ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>

    <!-- Chatbot Button -->
    <div class="chatbot-button" onclick="toggleChatbot()">
        <i class="fas fa-comments"></i>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script src="assets/js/main.js"></script>
    <script src="assets/js/cart.js"></script>
    <script src="assets/js/chatbot.js"></script>
</body>
</html>