<?php
// product-detail.php - Trang chi tiết sản phẩm

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';
require_once 'models/Product.php';
require_once 'models/Comment.php';

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    redirect('products.php');
}

$productModel = new Product();
$commentModel = new Comment();

$product = $productModel->getProductBySlug($slug);

if (!$product) {
    redirect('products.php');
}

// Lấy sản phẩm liên quan
$relatedProducts = $productModel->getSuggestedProducts($product['id'], $product['category_id'], 4);

// Lấy bình luận
$comments = $commentModel->getCommentsByProduct($product['id']);
$rating = $commentModel->getAverageRating($product['id']);

// Xử lý thêm bình luận
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_comment'])) {
    requireLogin();
    
    $commentData = [
        'product_id' => $product['id'],
        'user_id' => $_SESSION['user_id'],
        'content' => sanitizeInput($_POST['content']),
        'rating' => (int)$_POST['rating']
    ];
    
    $result = $commentModel->addComment($commentData);
    setFlashMessage($result['success'] ? 'success' : 'error', $result['message']);
    redirect('product-detail.php?slug=' . $slug);
}

$pageTitle = $product['name'] . ' - ' . SITE_NAME;
$finalPrice = getFinalPrice($product['price'], $product['discount_price']);
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .product-detail {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 40px;
            padding: 30px 0;
        }
        .product-images {
            position: sticky;
            top: 100px;
            height: fit-content;
        }
        .main-image {
            width: 100%;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 15px;
        }
        .thumbnail-images {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }
        .thumbnail-images img {
            width: 100%;
            border-radius: 5px;
            cursor: pointer;
            border: 2px solid transparent;
            transition: all 0.3s;
        }
        .thumbnail-images img:hover,
        .thumbnail-images img.active {
            border-color: var(--primary-color);
        }
        .product-info-section {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        .product-title {
            font-size: 28px;
            margin-bottom: 15px;
            color: var(--dark-color);
        }
        .product-meta {
            display: flex;
            gap: 20px;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid var(--border-color);
        }
        .price-section {
            background: var(--light-color);
            padding: 20px;
            border-radius: 10px;
            margin: 20px 0;
        }
        .current-price {
            font-size: 32px;
            color: var(--danger-color);
            font-weight: bold;
        }
        .old-price {
            font-size: 20px;
            color: #999;
            text-decoration: line-through;
            margin-left: 10px;
        }
        .save-price {
            display: inline-block;
            background: var(--danger-color);
            color: #fff;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 14px;
            margin-left: 10px;
        }
        .quantity-selector {
            display: flex;
            align-items: center;
            gap: 15px;
            margin: 20px 0;
        }
        .quantity-input {
            display: flex;
            align-items: center;
            border: 1px solid var(--border-color);
            border-radius: 5px;
            overflow: hidden;
        }
        .quantity-input button {
            padding: 10px 15px;
            border: none;
            background: var(--light-color);
            cursor: pointer;
        }
        .quantity-input input {
            width: 60px;
            text-align: center;
            border: none;
            padding: 10px;
        }
        .action-buttons {
            display: flex;
            gap: 15px;
            margin: 20px 0;
        }
        .specifications {
            margin: 30px 0;
        }
        .spec-table {
            width: 100%;
            border-collapse: collapse;
        }
        .spec-table tr {
            border-bottom: 1px solid var(--border-color);
        }
        .spec-table td {
            padding: 12px;
        }
        .spec-table td:first-child {
            font-weight: 500;
            width: 30%;
            background: var(--light-color);
        }
        .tabs {
            margin: 30px 0;
        }
        .tab-buttons {
            display: flex;
            gap: 10px;
            border-bottom: 2px solid var(--border-color);
        }
        .tab-btn {
            padding: 15px 30px;
            background: none;
            border: none;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
            margin-bottom: -2px;
        }
        .tab-btn.active {
            color: var(--primary-color);
            border-bottom-color: var(--primary-color);
        }
        .tab-content {
            display: none;
            padding: 20px 0;
        }
        .tab-content.active {
            display: block;
        }
        .comment-item {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        .comment-header {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
        }
        @media (max-width: 768px) {
            .product-detail {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <?php
    $breadcrumb = [
        ['text' => 'Sản phẩm', 'url' => 'products.php'],
        ['text' => $product['category_name'], 'url' => 'products.php?category=' . $product['category_id']],
        ['text' => $product['name'], 'url' => '']
    ];
    echo renderBreadcrumb($breadcrumb);
    ?>
    
    <div class="container">
        <div class="product-detail">
            <!-- Hình ảnh sản phẩm -->
            <div class="product-images">
                <img src="<?php echo getProductImage($product['image']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                     class="main-image" id="mainImage">
                
                <div class="thumbnail-images">
                    <img src="<?php echo getProductImage($product['image']); ?>" 
                         class="active" onclick="changeImage(this)">
                    <?php 
                    if ($product['images']) {
                        $images = json_decode($product['images'], true);
                        if ($images) {
                            foreach ($images as $img) {
                                echo '<img src="' . getProductImage($img) . '" onclick="changeImage(this)">';
                            }
                        }
                    }
                    ?>
                </div>
            </div>
            
            <!-- Thông tin sản phẩm -->
            <div>
                <div class="product-info-section">
                    <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                    
                    <div class="product-meta">
                        <div>
                            <?php echo renderStars($rating['avg_rating']); ?>
                            <span style="color: #666;">(<?php echo $rating['total_reviews']; ?> đánh giá)</span>
                        </div>
                        <div>
                            <i class="fas fa-check-circle" style="color: var(--success-color);"></i>
                            Đã bán: <strong><?php echo $product['sold_quantity']; ?></strong>
                        </div>
                        <div>
                            <i class="fas fa-box"></i>
                            Kho: <strong style="color: <?php echo $product['quantity'] > 0 ? 'var(--success-color)' : 'var(--danger-color)'; ?>">
                                <?php echo $product['quantity'] > 0 ? $product['quantity'] . ' sản phẩm' : 'Hết hàng'; ?>
                            </strong>
                        </div>
                    </div>
                    
                    <div class="price-section">
                        <span class="current-price"><?php echo formatCurrency($finalPrice); ?></span>
                        <?php if ($product['discount_price']): ?>
                            <span class="old-price"><?php echo formatCurrency($product['price']); ?></span>
                            <span class="save-price">
                                Tiết kiệm <?php echo formatCurrency($product['price'] - $product['discount_price']); ?>
                            </span>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($product['quantity'] > 0): ?>
                    <div class="quantity-selector">
                        <strong>Số lượng:</strong>
                        <div class="quantity-input">
                            <button onclick="decreaseQty()"><i class="fas fa-minus"></i></button>
                            <input type="number" id="quantity" value="1" min="1" max="<?php echo $product['quantity']; ?>">
                            <button onclick="increaseQty()"><i class="fas fa-plus"></i></button>
                        </div>
                        <span style="color: #666;">Còn <?php echo $product['quantity']; ?> sản phẩm</span>
                    </div>
                    
                    <div class="action-buttons">
                        <button class="btn btn-cart" style="flex: 1;" onclick="addToCart(<?php echo $product['id']; ?>)">
                            <i class="fas fa-shopping-cart"></i> Thêm vào giỏ hàng
                        </button>
                        <button class="btn btn-buy" style="flex: 1;" onclick="buyNow(<?php echo $product['id']; ?>)">
                            <i class="fas fa-bolt"></i> Mua ngay
                        </button>
                    </div>
                    <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="fas fa-exclamation-triangle"></i> Sản phẩm hiện đã hết hàng
                    </div>
                    <?php endif; ?>
                    
                    <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                        <p><i class="fas fa-shield-alt" style="color: var(--success-color);"></i> Bảo hành chính hãng 12-36 tháng</p>
                        <p><i class="fas fa-truck" style="color: var(--primary-color);"></i> Miễn phí vận chuyển cho đơn từ 5 triệu</p>
                        <p><i class="fas fa-undo" style="color: var(--info-color);"></i> Đổi trả trong 7 ngày nếu lỗi nhà sản xuất</p>
                    </div>
                </div>
                
                <!-- Tabs -->
                <div class="tabs">
                    <div class="tab-buttons">
                        <button class="tab-btn active" onclick="showTab('description')">Mô tả</button>
                        <button class="tab-btn" onclick="showTab('specifications')">Thông số kỹ thuật</button>
                        <button class="tab-btn" onclick="showTab('reviews')">Đánh giá (<?php echo $rating['total_reviews']; ?>)</button>
                    </div>
                    
                    <div id="description" class="tab-content active">
                        <div class="product-info-section">
                            <?php echo nl2br(htmlspecialchars($product['description'])); ?>
                        </div>
                    </div>
                    
                    <div id="specifications" class="tab-content">
                        <div class="product-info-section">
                            <?php
                            if ($product['specifications']) {
                                $specs = json_decode($product['specifications'], true);
                                if ($specs) {
                                    echo '<table class="spec-table">';
                                    foreach ($specs as $key => $value) {
                                        echo '<tr>';
                                        echo '<td>' . htmlspecialchars($key) . '</td>';
                                        echo '<td>' . htmlspecialchars($value) . '</td>';
                                        echo '</tr>';
                                    }
                                    echo '</table>';
                                } else {
                                    echo '<p>Chưa có thông số kỹ thuật</p>';
                                }
                            } else {
                                echo '<p>Chưa có thông số kỹ thuật</p>';
                            }
                            ?>
                        </div>
                    </div>
                    
                    <div id="reviews" class="tab-content">
                        <div class="product-info-section">
                            <h3>Đánh giá sản phẩm</h3>
                            
                            <?php if (isLoggedIn()): ?>
                            <form method="POST" style="margin: 20px 0; padding: 20px; background: var(--light-color); border-radius: 5px;">
                                <div class="form-group">
                                    <label>Đánh giá của bạn</label>
                                    <div>
                                        <input type="radio" name="rating" value="5" required> ⭐⭐⭐⭐⭐
                                        <input type="radio" name="rating" value="4"> ⭐⭐⭐⭐
                                        <input type="radio" name="rating" value="3"> ⭐⭐⭐
                                        <input type="radio" name="rating" value="2"> ⭐⭐
                                        <input type="radio" name="rating" value="1"> ⭐
                                    </div>
                                </div>
                                <div class="form-group">
                                    <textarea name="content" class="form-control" placeholder="Nhận xét của bạn..." required></textarea>
                                </div>
                                <button type="submit" name="add_comment" class="btn btn-primary">Gửi đánh giá</button>
                            </form>
                            <?php else: ?>
                            <p><a href="customer/login.php">Đăng nhập</a> để đánh giá sản phẩm</p>
                            <?php endif; ?>
                            
                            <div class="comments-list">
                                <?php foreach ($comments as $comment): ?>
                                <div class="comment-item">
                                    <div class="comment-header">
                                        <div>
                                            <strong><?php echo htmlspecialchars($comment['full_name'] ?? $comment['username']); ?></strong>
                                            <?php echo renderStars($comment['rating'], 5); ?>
                                        </div>
                                        <span style="color: #666; font-size: 14px;"><?php echo timeAgo($comment['created_at']); ?></span>
                                    </div>
                                    <p><?php echo htmlspecialchars($comment['content']); ?></p>
                                </div>
                                <?php endforeach; ?>
                                
                                <?php if (empty($comments)): ?>
                                <p style="text-align: center; color: #666; padding: 20px;">Chưa có đánh giá nào</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sản phẩm liên quan -->
        <?php if (!empty($relatedProducts)): ?>
        <section class="products-section">
            <h2 class="section-title">Sản phẩm liên quan</h2>
            <div class="products-grid">
                <?php foreach ($relatedProducts as $rp): ?>
                <div class="product-card">
                    <a href="product-detail.php?slug=<?php echo $rp['slug']; ?>">
                        <img src="<?php echo getProductImage($rp['image']); ?>" alt="<?php echo htmlspecialchars($rp['name']); ?>">
                    </a>
                    <div class="product-info">
                        <h3 class="product-name">
                            <a href="product-detail.php?slug=<?php echo $rp['slug']; ?>">
                                <?php echo htmlspecialchars($rp['name']); ?>
                            </a>
                        </h3>
                        <div class="product-price">
                            <span class="price-new"><?php echo formatCurrency(getFinalPrice($rp['price'], $rp['discount_price'])); ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    
    <script>
        function changeImage(img) {
            document.getElementById('mainImage').src = img.src;
            document.querySelectorAll('.thumbnail-images img').forEach(i => i.classList.remove('active'));
            img.classList.add('active');
        }
        
        function showTab(tabName) {
            document.querySelectorAll('.tab-content').forEach(tab => tab.classList.remove('active'));
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            
            document.getElementById(tabName).classList.add('active');
            event.target.classList.add('active');
        }
        
        function increaseQty() {
            const input = document.getElementById('quantity');
            const max = parseInt(input.max);
            if (parseInt(input.value) < max) {
                input.value = parseInt(input.value) + 1;
            }
        }
        
        function decreaseQty() {
            const input = document.getElementById('quantity');
            if (parseInt(input.value) > 1) {
                input.value = parseInt(input.value) - 1;
            }
        }
    </script>
    <script src="assets/js/cart.js?v=<?php echo time(); ?>"></script>
</body>
</html>