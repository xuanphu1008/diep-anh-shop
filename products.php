<?php
// products.php - Trang danh sách sản phẩm

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';
require_once 'models/Product.php';
require_once 'models/Category.php';

$productModel = new Product();
$categoryModel = new Category();

// Lấy parameters
$categorySlug = $_GET['category'] ?? '';
$filter = $_GET['filter'] ?? ''; // hot, bestselling, discount, new
$search = $_GET['search'] ?? '';
$minPrice = $_GET['min_price'] ?? null;
$maxPrice = $_GET['max_price'] ?? null;
$sort = $_GET['sort'] ?? ''; // price_asc, price_desc, name_asc, name_desc
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 12;

// Lấy sản phẩm
$products = [];
$category = null;
$pageTitle = 'Sản phẩm';

if ($categorySlug) {
    $category = $categoryModel->getCategoryBySlug($categorySlug);
    if ($category) {
        $products = $productModel->getProductsByCategory($category['id']);
        $pageTitle = $category['name'];
    }
} elseif ($search) {
    $products = $productModel->searchProducts($search, null, $minPrice, $maxPrice);
    $pageTitle = 'Tìm kiếm: ' . $search;
} elseif ($filter) {
    switch ($filter) {
        case 'hot':
            $products = $productModel->getHotProducts(100);
            $pageTitle = 'Sản phẩm Hot';
            break;
        case 'bestselling':
            $products = $productModel->getBestSellingProducts(100);
            $pageTitle = 'Sản phẩm bán chạy';
            break;
        case 'discount':
            $products = $productModel->getDiscountedProducts(100);
            $pageTitle = 'Sản phẩm giảm giá';
            break;
        case 'new':
            $products = $productModel->getNewProducts(100);
            $pageTitle = 'Sản phẩm mới';
            break;
    }
} else {
    $products = $productModel->getAllProducts();
}

// Sắp xếp
if ($sort) {
    usort($products, function($a, $b) use ($sort) {
        $priceA = $a['discount_price'] ?? $a['price'];
        $priceB = $b['discount_price'] ?? $b['price'];
        
        switch ($sort) {
            case 'price_asc':
                return $priceA - $priceB;
            case 'price_desc':
                return $priceB - $priceA;
            case 'name_asc':
                return strcmp($a['name'], $b['name']);
            case 'name_desc':
                return strcmp($b['name'], $a['name']);
            default:
                return 0;
        }
    });
}

// Pagination
$totalProducts = count($products);
$pagination = paginate($totalProducts, $perPage, $page);
$products = array_slice($products, $pagination['offset'], $perPage);

$categories = $categoryModel->getAllCategories();
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle . ' - ' . SITE_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .products-page {
            display: grid;
            grid-template-columns: 250px 1fr;
            gap: 30px;
            padding: 30px 0;
        }
        .sidebar {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            height: fit-content;
        }
        .sidebar h3 {
            margin-bottom: 15px;
            color: var(--primary-color);
            border-bottom: 2px solid var(--primary-color);
            padding-bottom: 10px;
        }
        .category-list {
            list-style: none;
        }
        .category-list li {
            margin-bottom: 10px;
        }
        .category-list a {
            display: block;
            padding: 8px 10px;
            border-radius: 5px;
            transition: all 0.3s;
        }
        .category-list a:hover,
        .category-list a.active {
            background: var(--primary-color);
            color: #fff;
        }
        .filter-section {
            margin-top: 20px;
        }
        .price-filter {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        .price-filter input {
            width: 100%;
            padding: 5px;
            border: 1px solid var(--border-color);
            border-radius: 5px;
        }
        .toolbar {
            background: #fff;
            padding: 15px;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .view-options {
            display: flex;
            gap: 10px;
        }
        .view-btn {
            padding: 8px 15px;
            border: 1px solid var(--border-color);
            background: #fff;
            cursor: pointer;
            border-radius: 5px;
        }
        .view-btn.active {
            background: var(--primary-color);
            color: #fff;
        }
        @media (max-width: 768px) {
            .products-page {
                grid-template-columns: 1fr;
            }
            .sidebar {
                display: none;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <?php
    $breadcrumb = [
        ['text' => 'Sản phẩm', 'url' => 'products.php']
    ];
    if ($category) {
        $breadcrumb[] = ['text' => $category['name'], 'url' => ''];
    }
    echo renderBreadcrumb($breadcrumb);
    ?>
    
    <div class="container">
        <div class="products-page">
            <!-- Sidebar -->
            <aside class="sidebar">
                <h3><i class="fas fa-list"></i> Danh mục</h3>
                <ul class="category-list">
                    <li>
                        <a href="products.php" <?php echo empty($categorySlug) ? 'class="active"' : ''; ?>>
                            <i class="fas fa-th"></i> Tất cả sản phẩm
                        </a>
                    </li>
                    <?php foreach ($categories as $cat): ?>
                    <li>
                        <a href="products.php?category=<?php echo $cat['slug']; ?>" 
                           <?php echo $categorySlug === $cat['slug'] ? 'class="active"' : ''; ?>>
                            <?php echo htmlspecialchars($cat['name']); ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
                
                <div class="filter-section">
                    <h3><i class="fas fa-filter"></i> Bộ lọc</h3>
                    <form method="GET" action="products.php">
                        <?php if ($categorySlug): ?>
                            <input type="hidden" name="category" value="<?php echo $categorySlug; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>Khoảng giá</label>
                            <div class="price-filter">
                                <input type="number" name="min_price" placeholder="Từ" value="<?php echo $minPrice; ?>">
                                <span>-</span>
                                <input type="number" name="max_price" placeholder="Đến" value="<?php echo $maxPrice; ?>">
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-primary btn-block btn-sm">
                            <i class="fas fa-search"></i> Lọc
                        </button>
                    </form>
                </div>
                
                <div class="filter-section">
                    <h3><i class="fas fa-tags"></i> Nhanh</h3>
                    <ul class="category-list">
                        <li><a href="products.php?filter=hot"><i class="fas fa-fire"></i> Sản phẩm Hot</a></li>
                        <li><a href="products.php?filter=bestselling"><i class="fas fa-star"></i> Bán chạy</a></li>
                        <li><a href="products.php?filter=discount"><i class="fas fa-percent"></i> Giảm giá</a></li>
                        <li><a href="products.php?filter=new"><i class="fas fa-plus"></i> Mới nhất</a></li>
                    </ul>
                </div>
            </aside>
            
            <!-- Main Content -->
            <main>
                <div class="toolbar">
                    <div>
                        <strong><?php echo $totalProducts; ?></strong> sản phẩm
                    </div>
                    
                    <div style="display: flex; gap: 15px; align-items: center;">
                        <select onchange="window.location.href=this.value" style="padding: 8px; border-radius: 5px; border: 1px solid var(--border-color);">
                            <option value="">Sắp xếp</option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_asc'])); ?>" 
                                    <?php echo $sort === 'price_asc' ? 'selected' : ''; ?>>Giá tăng dần</option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'price_desc'])); ?>" 
                                    <?php echo $sort === 'price_desc' ? 'selected' : ''; ?>>Giá giảm dần</option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name_asc'])); ?>" 
                                    <?php echo $sort === 'name_asc' ? 'selected' : ''; ?>>Tên A-Z</option>
                            <option value="?<?php echo http_build_query(array_merge($_GET, ['sort' => 'name_desc'])); ?>" 
                                    <?php echo $sort === 'name_desc' ? 'selected' : ''; ?>>Tên Z-A</option>
                        </select>
                    </div>
                </div>
                
                <?php if (empty($products)): ?>
                    <div style="text-align: center; padding: 50px;">
                        <i class="fas fa-inbox" style="font-size: 64px; color: #ccc;"></i>
                        <h3>Không tìm thấy sản phẩm nào</h3>
                    </div>
                <?php else: ?>
                    <div class="products-grid">
                        <?php foreach ($products as $product): ?>
                        <div class="product-card">
                            <?php if ($product['discount_price']): ?>
                            <span class="badge badge-sale">
                                -<?php echo calculateDiscountPercent($product['price'], $product['discount_price']); ?>%
                            </span>
                            <?php endif; ?>
                            
                            <?php if ($product['is_hot']): ?>
                            <span class="badge badge-hot" style="left: auto; right: 10px;">HOT</span>
                            <?php endif; ?>
                            
                            <a href="product-detail.php?slug=<?php echo $product['slug']; ?>">
                                <img src="<?php echo getProductImage($product['image']); ?>" 
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
                                        <span class="price-old"><?php echo formatCurrency($product['price']); ?></span>
                                        <span class="price-new"><?php echo formatCurrency($product['discount_price']); ?></span>
                                    <?php else: ?>
                                        <span class="price-new"><?php echo formatCurrency($product['price']); ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="product-actions">
                                    <button class="btn btn-cart" onclick="addToCart(<?php echo $product['id']; ?>)">
                                        <i class="fas fa-shopping-cart"></i> Thêm vào giỏ
                                    </button>
                                </div>
                                
                                <?php if ($product['quantity'] <= 0): ?>
                                <div class="out-of-stock">Hết hàng</div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <!-- Pagination -->
                    <?php if ($pagination['total_pages'] > 1): ?>
                    <div class="pagination">
                        <?php if ($pagination['has_prev']): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="fas fa-chevron-left"></i> Trước
                            </a>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $pagination['total_pages']; $i++): ?>
                            <?php if ($i == $page): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif; ?>
                        <?php endfor; ?>
                        
                        <?php if ($pagination['has_next']): ?>
                            <a href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                Sau <i class="fas fa-chevron-right"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
    <script src="assets/js/cart.js"></script>
</body>
</html>