<?php
// customer/cart.php - Trang giỏ hàng

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/Product.php';

$cartModel = new Cart();
$productModel = new Product();

$userId = $_SESSION['user_id'] ?? null;

// Lấy giỏ hàng
if ($userId) {
    $cartDetails = $cartModel->getCartDetails($userId);
} else {
    // Giỏ hàng session cho khách chưa đăng nhập
    $sessionCart = $_SESSION['cart'] ?? [];
    $items = [];
    $subtotal = 0;
    
    // Xử lý cả 2 format: array với key là productId hoặc array các object
    foreach ($sessionCart as $key => $value) {
        $productId = 0;
        $quantity = 0;
        
        // Format mới: array các object với key 'id'
        if (is_array($value) && isset($value['id'])) {
            $productId = $value['id'];
            $quantity = $value['quantity'] ?? 1;
        } 
        // Format cũ: key là productId, value là quantity
        else if (is_numeric($key) && is_numeric($value)) {
            $productId = (int)$key;
            $quantity = (int)$value;
        }
        
        if ($productId > 0 && $quantity > 0) {
            $product = $productModel->getProductById($productId);
            if ($product && $product['is_active'] && $product['deleted_at'] === null) {
                $finalPrice = getFinalPrice($product['price'], $product['discount_price']);
                $items[] = [
                    'product_id' => $product['id'],
                    'name' => $product['name'],
                    'slug' => $product['slug'],
                    'price' => $product['price'],
                    'discount_price' => $product['discount_price'],
                    'final_price' => $finalPrice,
                    'image' => $product['image'],
                    'quantity' => $quantity,
                    'stock_quantity' => $product['quantity'],
                    'total' => $finalPrice * $quantity
                ];
                $subtotal += $finalPrice * $quantity;
            }
        }
    }
    
    $cartDetails = [
        'items' => $items,
        'subtotal' => $subtotal,
        'item_count' => count($items)
    ];
}

$pageTitle = 'Giỏ hàng - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .cart-container {
            padding: 30px 0;
        }
        .cart-table {
            background: #fff;
            border-radius: 10px;
            box-shadow: var(--shadow);
            overflow: hidden;
            margin-bottom: 20px;
        }
        .cart-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .cart-table th {
            background: var(--primary-color);
            color: #fff;
            padding: 15px;
            text-align: left;
        }
        .cart-table td {
            padding: 15px;
            border-bottom: 1px solid var(--border-color);
        }
        .cart-item-image {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
        }
        .cart-summary {
            background: #fff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            padding: 10px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .summary-row.total {
            font-size: 20px;
            font-weight: bold;
            color: var(--primary-color);
            border-bottom: none;
            margin-top: 10px;
        }
        .empty-cart {
            text-align: center;
            padding: 80px 30px;
            background: linear-gradient(135deg, var(--light-color) 0%, #ffffff 100%);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(2, 40, 89, 0.08);
            max-width: 600px;
            margin: 40px auto;
        }
        .empty-cart i {
            font-size: 120px;
            color: var(--border-color);
            margin-bottom: 30px;
            display: block;
            animation: float 3s ease-in-out infinite;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0px); }
            50% { transform: translateY(-10px); }
        }
        .empty-cart h2 {
            font-size: 28px;
            color: var(--dark-color);
            margin-bottom: 15px;
            font-weight: 600;
        }
        .empty-cart p {
            font-size: 16px;
            color: var(--primary-dark);
            margin-bottom: 40px;
            line-height: 1.6;
        }
        .empty-cart .btn-shopping {
            display: inline-flex;
            align-items: center;
            gap: 12px;
            padding: 18px 40px;
            font-size: 18px;
            font-weight: 600;
            color: #fff;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-dark) 100%);
            border: none;
            border-radius: 50px;
            box-shadow: 0 6px 20px rgba(50, 133, 166, 0.4);
            text-decoration: none;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .empty-cart .btn-shopping:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(50, 133, 166, 0.5);
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-darkest) 100%);
        }
        .empty-cart .btn-shopping:active {
            transform: translateY(0);
        }
        .empty-cart .btn-shopping i {
            font-size: 20px;
            color: #fff;
            margin: 0;
            animation: none;
        }
        @media (max-width: 768px) {
            .empty-cart {
                padding: 60px 20px;
                margin: 20px auto;
            }
            .empty-cart i {
                font-size: 80px;
            }
            .empty-cart h2 {
                font-size: 24px;
            }
            .empty-cart .btn-shopping {
                padding: 16px 32px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <?php
    $breadcrumb = [
        ['text' => 'Giỏ hàng', 'url' => '']
    ];
    echo renderBreadcrumb($breadcrumb);
    ?>
    
    <div class="container cart-container">
        <h1 class="section-title"><i class="fas fa-shopping-cart"></i> Giỏ hàng của bạn</h1>
        
        <?php if ($flash = getFlashMessage()): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($cartDetails['items'])): ?>
            <div class="empty-cart">
                <i class="fas fa-shopping-cart"></i>
                <h2>Giỏ hàng trống</h2>
                <p>Bạn chưa có sản phẩm nào trong giỏ hàng.<br>Hãy khám phá và mua sắm ngay!</p>
                <a href="../products.php" class="btn-shopping">
                    <i class="fas fa-shopping-bag"></i>
                    <span>Mua sắm ngay</span>
                </a>
            </div>
        <?php else: ?>
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
                <!-- Bảng sản phẩm -->
                <div>
                    <div class="cart-table">
                        <table>
                            <thead>
                                <tr>
                                    <th>Sản phẩm</th>
                                    <th>Đơn giá</th>
                                    <th>Số lượng</th>
                                    <th>Thành tiền</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($cartDetails['items'] as $item): ?>
                                <tr class="cart-item" data-product-id="<?php echo $item['product_id']; ?>" 
                                    data-price="<?php echo $item['final_price']; ?>">
                                    <td>
                                        <div style="display: flex; gap: 15px; align-items: center;">
                                            <img src="<?php echo getProductImage($item['image']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                 class="cart-item-image">
                                            <div>
                                                <a href="../product-detail.php?slug=<?php echo $item['slug']; ?>" 
                                                   style="color: var(--dark-color); font-weight: 500;">
                                                    <?php echo htmlspecialchars($item['name']); ?>
                                                </a>
                                                <?php if ($item['quantity'] > $item['stock_quantity']): ?>
                                                <p style="color: var(--danger-color); font-size: 12px; margin-top: 5px;">
                                                    <i class="fas fa-exclamation-triangle"></i> 
                                                    Chỉ còn <?php echo $item['stock_quantity']; ?> sản phẩm
                                                </p>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <?php if ($item['discount_price']): ?>
                                            <span style="text-decoration: line-through; color: #999; font-size: 14px;">
                                                <?php echo formatCurrency($item['price']); ?>
                                            </span><br>
                                            <span style="color: var(--danger-color); font-weight: bold;">
                                                <?php echo formatCurrency($item['discount_price']); ?>
                                            </span>
                                        <?php else: ?>
                                            <span style="font-weight: bold;">
                                                <?php echo formatCurrency($item['price']); ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="quantity-input">
                                            <button onclick="decreaseCartQty(<?php echo $item['product_id']; ?>)">
                                                <i class="fas fa-minus"></i>
                                            </button>
                                            <input type="number" value="<?php echo $item['quantity']; ?>" 
                                                   min="1" max="<?php echo $item['stock_quantity']; ?>"
                                                   onchange="updateCartQuantity(<?php echo $item['product_id']; ?>, this.value)">
                                            <button onclick="increaseCartQty(<?php echo $item['product_id']; ?>)">
                                                <i class="fas fa-plus"></i>
                                            </button>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="item-total" style="font-weight: bold; color: var(--danger-color);">
                                            <?php echo formatCurrency($item['total']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <button class="btn btn-danger btn-sm btn-remove-cart" 
                                                data-product-id="<?php echo $item['product_id']; ?>"
                                                onclick="removeFromCart(<?php echo $item['product_id']; ?>)">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <div style="display: flex; justify-content: space-between;">
                        <a href="../products.php" class="btn btn-secondary">
                            <i class="fas fa-arrow-left"></i> Tiếp tục mua sắm
                        </a>
                        <button class="btn btn-danger" id="clear-cart-btn" onclick="clearCart()">
                            <i class="fas fa-trash"></i> Xóa toàn bộ giỏ hàng
                        </button>
                    </div>
                </div>
                
                <!-- Tổng tiền -->
                <div>
                    <div class="cart-summary">
                        <h3 style="margin-bottom: 20px;">Tóm tắt đơn hàng</h3>
                        
                        <div class="summary-row">
                            <span>Tạm tính:</span>
                            <span id="subtotal"><?php echo formatCurrency($cartDetails['subtotal']); ?></span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Giảm giá:</span>
                            <span id="discount" data-value="0">0đ</span>
                        </div>
                        
                        <div class="summary-row">
                            <span>Phí vận chuyển:</span>
                            <span>Miễn phí</span>
                        </div>
                        
                        <div class="summary-row total">
                            <span>Tổng cộng:</span>
                            <span id="total"><?php echo formatCurrency($cartDetails['subtotal']); ?></span>
                        </div>
                        
                        <div style="margin: 20px 0;">
                            <label style="display: block; margin-bottom: 5px; font-weight: 500;">Mã giảm giá:</label>
                            <div style="display: flex; gap: 10px;">
                                <input type="text" id="coupon_code" class="form-control" placeholder="Nhập mã giảm giá">
                                <button class="btn btn-primary" id="apply-coupon-btn" onclick="applyCoupon()">
                                    Áp dụng
                                </button>
                            </div>
                        </div>
                        
                        <?php if ($userId): ?>
                            <a href="checkout.php" class="btn btn-success btn-block btn-lg">
                                <i class="fas fa-credit-card"></i> Thanh toán
                            </a>
                        <?php else: ?>
                            <a href="login.php?redirect=checkout" class="btn btn-success btn-block btn-lg">
                                <i class="fas fa-sign-in-alt"></i> Đăng nhập để thanh toán
                            </a>
                        <?php endif; ?>
                        
                        <div style="margin-top: 20px; padding-top: 20px; border-top: 1px solid var(--border-color);">
                            <h4 style="margin-bottom: 10px;">Chính sách mua hàng</h4>
                            <ul style="list-style: none; padding: 0; font-size: 14px; color: #666;">
                                <li style="margin-bottom: 8px;">
                                    <i class="fas fa-check" style="color: var(--success-color);"></i>
                                    Miễn phí vận chuyển cho đơn từ 5 triệu
                                </li>
                                <li style="margin-bottom: 8px;">
                                    <i class="fas fa-check" style="color: var(--success-color);"></i>
                                    Đổi trả trong 7 ngày nếu lỗi
                                </li>
                                <li style="margin-bottom: 8px;">
                                    <i class="fas fa-check" style="color: var(--success-color);"></i>
                                    Bảo hành chính hãng
                                </li>
                                <li>
                                    <i class="fas fa-check" style="color: var(--success-color);"></i>
                                    Hỗ trợ 24/7
                                </li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
    <script src="../assets/js/cart.js?v=<?php echo time(); ?>"></script>
</body>
</html>