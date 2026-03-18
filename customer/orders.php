<?php
// customer/orders.php - Lịch sử đơn hàng

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Order.php';

requireLogin();

$orderModel = new Order();
$userId = $_SESSION['user_id'];

// Lấy danh sách đơn hàng
$orders = $orderModel->getOrdersByUser($userId);

// Xử lý hủy đơn
if (isset($_GET['cancel']) && isset($_GET['id'])) {
    $orderId = (int)$_GET['id'];
    $result = $orderModel->cancelOrder($orderId);
    
    setFlashMessage($result['success'] ? 'success' : 'error', $result['message']);
    redirect('orders.php');
}

$pageTitle = 'Đơn hàng của tôi - ' . SITE_NAME;
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
        .order-card {
            background: #fff;
            border-radius: 10px;
            box-shadow: var(--shadow);
            margin-bottom: 20px;
            overflow: hidden;
        }
        .order-header {
            background: var(--light-color);
            padding: 15px 20px;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .order-body {
            padding: 20px;
        }
        .order-item {
            display: flex;
            gap: 15px;
            padding: 15px 0;
            border-bottom: 1px solid var(--border-color);
        }
        .order-item:last-child {
            border-bottom: none;
        }
        .order-item img {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border-radius: 5px;
        }
        .order-footer {
            background: var(--light-color);
            padding: 15px 20px;
            border-top: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .empty-orders {
            text-align: center;
            padding: 80px 30px;
            background: linear-gradient(135deg, var(--light-color) 0%, #ffffff 100%);
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(2, 40, 89, 0.08);
            max-width: 600px;
            margin: 40px auto;
        }
        .empty-orders i {
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
        .empty-orders h2 {
            font-size: 28px;
            color: var(--dark-color);
            margin-bottom: 15px;
            font-weight: 600;
        }
        .empty-orders p {
            font-size: 16px;
            color: var(--primary-dark);
            margin-bottom: 40px;
            line-height: 1.6;
        }
        .empty-orders .btn-shopping {
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
        .empty-orders .btn-shopping:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(50, 133, 166, 0.5);
            background: linear-gradient(135deg, var(--primary-dark) 0%, var(--primary-darkest) 100%);
        }
        .empty-orders .btn-shopping:active {
            transform: translateY(0);
        }
        .empty-orders .btn-shopping i {
            font-size: 20px;
            color: #fff;
            margin: 0;
            animation: none;
        }
        @media (max-width: 768px) {
            .empty-orders {
                padding: 60px 20px;
                margin: 20px auto;
            }
            .empty-orders i {
                font-size: 80px;
            }
            .empty-orders h2 {
                font-size: 24px;
            }
            .empty-orders .btn-shopping {
                padding: 16px 32px;
                font-size: 16px;
            }
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container" style="padding: 30px 0;">
        <h1 class="section-title"><i class="fas fa-shopping-bag"></i> Đơn hàng của tôi</h1>
        
        <?php if ($flash = getFlashMessage()): ?>
            <div class="alert alert-<?php echo $flash['type']; ?>">
                <?php echo htmlspecialchars($flash['message']); ?>
            </div>
        <?php endif; ?>
        
        <?php if (empty($orders)): ?>
            <div class="empty-orders">
                <i class="fas fa-shopping-bag"></i>
                <h2>Bạn chưa có đơn hàng nào</h2>
                <p>Hãy khám phá và mua sắm ngay!<br>Chúng tôi có nhiều sản phẩm hấp dẫn đang chờ bạn.</p>
                <a href="../products.php" class="btn-shopping">
                    <i class="fas fa-shopping-cart"></i>
                    <span>Mua sắm ngay</span>
                </a>
            </div>
        <?php else: ?>
            <?php foreach ($orders as $order): ?>
                <?php $details = $orderModel->getOrderDetails($order['id']); ?>
                <div class="order-card">
                    <div class="order-header">
                        <div>
                            <strong>Mã đơn hàng: <?php echo $order['order_code']; ?></strong>
                            <div style="font-size: 14px; color: #666; margin-top: 5px;">
                                <i class="fas fa-calendar"></i> <?php echo formatDate($order['created_at']); ?>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <span class="badge <?php echo getOrderStatusClass($order['order_status']); ?>">
                                <?php echo getOrderStatusText($order['order_status']); ?>
                            </span>
                            <div style="font-size: 14px; color: #666; margin-top: 5px;">
                                <?php echo getPaymentMethodText($order['payment_method']); ?>
                            </div>
                        </div>
                    </div>
                    
                    <div class="order-body">
                        <?php foreach ($details as $item): ?>
                        <div class="order-item">
                            <img src="<?php echo getProductImage($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>">
                            <div style="flex: 1;">
                                <h4 style="margin: 0 0 10px 0;"><?php echo htmlspecialchars($item['product_name']); ?></h4>
                                <div style="color: #666; font-size: 14px;">
                                    Số lượng: <?php echo $item['quantity']; ?> x <?php echo formatCurrency($item['product_price']); ?>
                                </div>
                            </div>
                            <div style="font-weight: bold; color: var(--danger-color);">
                                <?php echo formatCurrency($item['total']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div class="order-footer">
                        <div>
                            <strong>Tổng tiền: </strong>
                            <span style="font-size: 20px; color: var(--danger-color); font-weight: bold;">
                                <?php echo formatCurrency($order['total']); ?>
                            </span>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <?php if (in_array($order['order_status'], ['pending', 'confirmed'])): ?>
                            <a href="?cancel=1&id=<?php echo $order['id']; ?>" 
                               class="btn btn-danger btn-sm"
                               onclick="return confirm('Bạn có chắc muốn hủy đơn hàng này?')">
                                <i class="fas fa-times"></i> Hủy đơn
                            </a>
                            <?php endif; ?>
                            
                            <a href="order-detail.php?code=<?php echo $order['order_code']; ?>" 
                               class="btn btn-primary btn-sm">
                                <i class="fas fa-eye"></i> Xem chi tiết
                            </a>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>