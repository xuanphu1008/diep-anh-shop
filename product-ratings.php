<?php
// product-ratings.php - Xem tất cả đánh giá của một sản phẩm

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/Database.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/models/Product.php';
require_once __DIR__ . '/models/Rating.php';

$product_id = (int)($_GET['id'] ?? 0);
$page = (int)($_GET['page'] ?? 1);
$sort = $_GET['sort'] ?? 'newest'; // newest, oldest, highest, lowest
$limit = 10;
$offset = ($page - 1) * $limit;

if (!$product_id) {
    redirect('products.php');
}

$productModel = new Product();
$ratingModel = new Rating();

$product = $productModel->getProductById($product_id);
if (!$product) {
    redirect('products.php');
}

// Lấy thống kê đánh giá
$avgRating = $ratingModel->getAverageRating($product_id);
$distribution = $ratingModel->getRatingDistribution($product_id);

// Lấy danh sách đánh giá
$filters = ['product_id' => $product_id, 'status' => 1];
$totalRatings = $ratingModel->countAllRatings($filters);

$orderBy = match($sort) {
    'oldest' => 'r.created_at ASC',
    'highest' => 'r.rating DESC',
    'lowest' => 'r.rating ASC',
    default => 'r.created_at DESC' // newest
};

$db = new Database();
$sql = "SELECT r.id, r.rating, r.content, r.created_at, 
               u.id as user_id, u.username, u.full_name, u.avatar,
               p.id as product_id, p.name as product_name
        FROM comments r
        INNER JOIN users u ON r.user_id = u.id
        INNER JOIN products p ON r.product_id = p.id
        WHERE r.product_id = ? AND r.status = 1
        ORDER BY " . $orderBy . "
        LIMIT ? OFFSET ?";

$ratings = $db->query($sql, [$product_id, $limit, $offset])->fetchAll();

$totalPages = ceil($totalRatings / $limit);

$pageTitle = 'Đánh giá - ' . htmlspecialchars($product['name']) . ' - ' . SITE_NAME;
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
        .ratings-container {
            display: grid;
            grid-template-columns: 300px 1fr;
            gap: 30px;
            padding: 30px 0;
        }
        
        .ratings-sidebar {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            height: fit-content;
        }
        
        .rating-summary {
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .rating-summary .big-rating {
            font-size: 48px;
            font-weight: bold;
            color: #ffc107;
        }
        
        .rating-summary .total-count {
            color: #666;
            margin-top: 5px;
        }
        
        .rating-distribution {
            margin-bottom: 20px;
        }
        
        .rating-distribution .dist-item {
            display: flex;
            align-items: center;
            gap: 8px;
            margin-bottom: 8px;
            font-size: 14px;
        }
        
        .rating-distribution .dist-star {
            width: 40px;
            text-align: right;
            color: #ffc107;
        }
        
        .rating-distribution .dist-bar {
            flex: 1;
            height: 8px;
            background: #e0e0e0;
            border-radius: 4px;
            overflow: hidden;
        }
        
        .rating-distribution .dist-bar-fill {
            height: 100%;
            background: #ffc107;
            border-radius: 4px;
        }
        
        .rating-distribution .dist-count {
            width: 30px;
            text-align: right;
            color: #999;
        }
        
        .ratings-list {
            background: #fff;
            border-radius: 10px;
        }
        
        .ratings-header {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .sort-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        
        .sort-btn {
            padding: 8px 15px;
            background: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 20px;
            cursor: pointer;
            font-size: 14px;
            transition: all 0.3s;
        }
        
        .sort-btn:hover {
            background: #e0e0e0;
        }
        
        .sort-btn.active {
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
        }
        
        .rating-item {
            padding: 20px;
            border-bottom: 1px solid #e0e0e0;
            display: grid;
            grid-template-columns: 50px 1fr;
            gap: 15px;
        }
        
        .rating-item:last-child {
            border-bottom: none;
        }
        
        .rating-avatar {
            width: 50px;
            height: 50px;
            border-radius: 50%;
            background: #e0e0e0;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 20px;
            overflow: hidden;
        }
        
        .rating-avatar img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .rating-content {
            flex: 1;
        }
        
        .rating-header-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .rating-name {
            font-weight: 600;
            color: #333;
        }
        
        .rating-stars {
            color: #ffc107;
            font-size: 14px;
        }
        
        .rating-date {
            color: #999;
            font-size: 13px;
        }
        
        .rating-text {
            color: #555;
            line-height: 1.6;
            margin: 10px 0;
        }
        
        .rating-verified {
            display: inline-block;
            background: #e8f5e9;
            color: #2e7d32;
            padding: 4px 10px;
            border-radius: 12px;
            font-size: 12px;
            margin-top: 8px;
        }
        
        .empty-state {
            text-align: center;
            padding: 40px 20px;
            color: #999;
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            padding: 20px;
            border-top: 1px solid #e0e0e0;
        }
        
        .pagination a, .pagination span {
            padding: 8px 12px;
            background: #f0f0f0;
            border: 1px solid #ddd;
            border-radius: 4px;
            text-decoration: none;
            color: #333;
        }
        
        .pagination a:hover {
            background: var(--primary-color);
            color: #fff;
        }
        
        .pagination .active {
            background: var(--primary-color);
            color: #fff;
            border-color: var(--primary-color);
        }
        
        @media (max-width: 768px) {
            .ratings-container {
                grid-template-columns: 1fr;
            }
            
            .ratings-header {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .sort-options {
                width: 100%;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>
    
    <?php
    $breadcrumb = [
        ['text' => 'SÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â£n phÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â©m', 'url' => 'products.php'],
        ['text' => htmlspecialchars($product['name']), 'url' => 'product-detail.php?slug=' . $product['slug']],
        ['text' => 'TÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¥t cÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â£ ÃƒÆ’Ã¢â‚¬Å¾ÃƒÂ¢Ã¢â€šÂ¬Ã‹Å“ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡nh giÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡', 'url' => '']
    ];
    echo renderBreadcrumb($breadcrumb);
    ?>
    
    <div class="container">
        <div class="ratings-container">
            <!-- Sidebar thÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã‹Å“ng kÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âª -->
            <div class="ratings-sidebar">
                <div class="rating-summary">
                    <div class="big-rating"><?php echo number_format($avgRating['avg_rating'] ?? 0, 1); ?></div>
                    <div style="color: #ffc107; margin: 5px 0;">
                        <?php for ($i = 1; $i <= 5; $i++): ?>
                            <i class="fas fa-star" style="opacity: <?php echo $i <= round($avgRating['avg_rating'] ?? 0) ? '1' : '0.3'; ?>"></i>
                        <?php endfor; ?>
                    </div>
                    <div class="total-count"><?php echo ($avgRating['total_ratings'] ?? 0) . ' ÃƒÆ’Ã¢â‚¬Å¾ÃƒÂ¢Ã¢â€šÂ¬Ã‹Å“ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡nh giÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡'; ?></div>
                </div>
                
                <div class="rating-distribution">
                    <?php for ($star = 5; $star >= 1; $star--):
                        $dist = array_filter($distribution, fn($d) => $d['rating'] == $star);
                        $dist = reset($dist);
                        $count = $dist['count'] ?? 0;
                        $percent = ($avgRating['total_ratings'] ?? 0) > 0 ? ($count / ($avgRating['total_ratings'] ?? 1) * 100) : 0;
                    ?>
                    <div class="dist-item">
                        <span class="dist-star">
                            <?php echo $star; ?> <i class="fas fa-star"></i>
                        </span>
                        <div class="dist-bar">
                            <div class="dist-bar-fill" style="width: <?php echo $percent; ?>%"></div>
                        </div>
                        <span class="dist-count"><?php echo (int)$count; ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
                
                <a href="product-detail.php?slug=<?php echo $product['slug']; ?>" class="btn btn-primary" style="width: 100%; text-align: center;">
                    ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â Ãƒâ€šÃ‚Â Quay lÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¡i sÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â£n phÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â©m
                </a>
            </div>
            
            <!-- Danh sÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ch ÃƒÆ’Ã¢â‚¬Å¾ÃƒÂ¢Ã¢â€šÂ¬Ã‹Å“ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡nh giÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ -->
            <div class="ratings-list">
                <div class="ratings-header">
                    <h3>ÃƒÆ’Ã¢â‚¬Å¾Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡nh giÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ sÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â£n phÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â©m (<?php echo $totalRatings; ?>)</h3>
                    <div class="sort-options">
                        <a href="?id=<?php echo $product_id; ?>&sort=newest" class="sort-btn <?php echo $sort === 'newest' ? 'active' : ''; ?>">MÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã‚Âºi nhÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¥t</a>
                        <a href="?id=<?php echo $product_id; ?>&sort=oldest" class="sort-btn <?php echo $sort === 'oldest' ? 'active' : ''; ?>">CÃƒÆ’Ã¢â‚¬Â¦Ãƒâ€šÃ‚Â© nhÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¥t</a>
                        <a href="?id=<?php echo $product_id; ?>&sort=highest" class="sort-btn <?php echo $sort === 'highest' ? 'active' : ''; ?>">Cao nhÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¥t</a>
                        <a href="?id=<?php echo $product_id; ?>&sort=lowest" class="sort-btn <?php echo $sort === 'lowest' ? 'active' : ''; ?>">ThÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¥p nhÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¥t</a>
                    </div>
                </div>
                
                <?php if (empty($ratings)): ?>
                    <div class="empty-state">
                        <i class="fas fa-star" style="font-size: 40px; opacity: 0.3; display: block; margin-bottom: 10px;"></i>
                        <p>ChÃƒÆ’Ã¢â‚¬Â Ãƒâ€šÃ‚Â°a cÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â³ ÃƒÆ’Ã¢â‚¬Å¾ÃƒÂ¢Ã¢â€šÂ¬Ã‹Å“ÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡nh giÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡ nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â o cho sÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â£n phÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â©m nÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â y</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($ratings as $rating): ?>
                    <div class="rating-item">
                        <div class="rating-avatar">
                            <?php if ($rating['avatar']): ?>
                                <img src="<?php echo $rating['avatar']; ?>" alt="<?php echo htmlspecialchars($rating['full_name'] ?? $rating['username']); ?>">
                            <?php else: ?>
                                <i class="fas fa-user"></i>
                            <?php endif; ?>
                        </div>
                        
                        <div class="rating-content">
                            <div class="rating-header-info">
                                <span class="rating-name"><?php echo htmlspecialchars($rating['full_name'] ?? $rating['username']); ?></span>
                                <div>
                                    <span class="rating-stars">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star" style="opacity: <?php echo $i <= $rating['rating'] ? '1' : '0.3'; ?>"></i>
                                        <?php endfor; ?>
                                    </span>
                                </div>
                                <span class="rating-date"><?php echo formatDate($rating['created_at'], 'd/m/Y H:i'); ?></span>
                            </div>
                            
                            <?php if ($rating['content']): ?>
                            <div class="rating-text">
                                <?php echo nl2br(htmlspecialchars($rating['content'])); ?>
                            </div>
                            <?php endif; ?>
                            
                            <div class="rating-verified">
                                <i class="fas fa-check-circle"></i> ÃƒÆ’Ã¢â‚¬Å¾Ãƒâ€šÃ‚ÂÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â£ xÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡c minh mua hÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â ng
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if ($totalPages > 1): ?>
                <div class="pagination">
                    <?php if ($page > 1): ?>
                        <a href="?id=<?php echo $product_id; ?>&page=1&sort=<?php echo $sort; ?>">ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â« ÃƒÆ’Ã¢â‚¬Å¾Ãƒâ€šÃ‚ÂÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â§u</a>
                        <a href="?id=<?php echo $product_id; ?>&page=<?php echo $page - 1; ?>&sort=<?php echo $sort; ?>">ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Â¹ TrÃƒÆ’Ã¢â‚¬Â Ãƒâ€šÃ‚Â°ÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã‚Âºc</a>
                    <?php endif; ?>
                    
                    <?php 
                    $startPage = max(1, $page - 2);
                    $endPage = min($totalPages, $page + 2);
                    
                    for ($p = $startPage; $p <= $endPage; $p++):
                    ?>
                        <?php if ($p === $page): ?>
                            <span class="active"><?php echo $p; ?></span>
                        <?php else: ?>
                            <a href="?id=<?php echo $product_id; ?>&page=<?php echo $p; ?>&sort=<?php echo $sort; ?>"><?php echo $p; ?></a>
                        <?php endif; ?>
                    <?php endfor; ?>
                    
                    <?php if ($page < $totalPages): ?>
                        <a href="?id=<?php echo $product_id; ?>&page=<?php echo $page + 1; ?>&sort=<?php echo $sort; ?>">TiÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¿p ÃƒÆ’Ã‚Â¢ÃƒÂ¢Ã¢â‚¬Å¡Ã‚Â¬Ãƒâ€šÃ‚Âº</a>
                        <a href="?id=<?php echo $product_id; ?>&page=<?php echo $totalPages; ?>&sort=<?php echo $sort; ?>">CuÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã‹Å“i ÃƒÆ’Ã¢â‚¬Å¡Ãƒâ€šÃ‚Â»</a>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/includes/footer.php'; ?>
</body>
</html>

