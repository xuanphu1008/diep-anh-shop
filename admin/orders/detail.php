<?php
// admin/orders/detail.php - Chi tiết đơn hàng

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Order.php';

requireStaff();

$orderModel = new Order();

// Lấy ID đơn hàng
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$orderId) {
    setFlashMessage('error', 'Không tìm thấy đơn hàng');
    redirect('index.php');
}

// Lấy thông tin đơn hàng đầy đủ
$order = $orderModel->getFullOrderInfo($orderId);

if (!$order) {
    setFlashMessage('error', 'Đơn hàng không tồn tại');
    redirect('index.php');
}

$pageTitle = 'Chi tiết đơn hàng #' . $order['order_code'] . ' - Admin';
$activeMenu = 'orders';
include __DIR__ . '/../layout.php';
?>

<div class="page-header d-flex justify-between align-center">
    <h1><i class="fas fa-shopping-cart"></i> Chi tiết đơn hàng</h1>
    <div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
        <a href="print.php?id=<?php echo $order['id']; ?>" class="btn btn-success" target="_blank">
            <i class="fas fa-print"></i> In đơn hàng
        </a>
    </div>
</div>

<?php if ($flash = getFlashMessage()): ?>
    <div class="alert alert-<?php echo $flash['type']; ?>">
        <?php echo htmlspecialchars($flash['message']); ?>
    </div>
<?php endif; ?>

<div class="order-detail-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
    <!-- Thông tin đơn hàng -->
    <div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; border-bottom: 2px solid #3498db; padding-bottom: 10px;">
            <i class="fas fa-info-circle"></i> Thông tin đơn hàng
        </h2>
        
        <table class="detail-table" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px; font-weight: bold; width: 40%;">Mã đơn hàng:</td>
                <td style="padding: 10px;"><strong style="color: #3498db;"><?php echo htmlspecialchars($order['order_code']); ?></strong></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Trạng thái đơn hàng:</td>
                <td style="padding: 10px;">
                    <span class="badge <?php echo getOrderStatusClass($order['order_status']); ?>">
                        <?php echo getOrderStatusText($order['order_status']); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Trạng thái thanh toán:</td>
                <td style="padding: 10px;">
                    <span class="badge <?php echo $order['payment_status'] === 'paid' ? 'badge-success' : ($order['payment_status'] === 'failed' ? 'badge-danger' : 'badge-warning'); ?>">
                        <?php echo getPaymentStatusText($order['payment_status']); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Phương thức thanh toán:</td>
                <td style="padding: 10px;"><?php echo getPaymentMethodText($order['payment_method']); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Ngày đặt hàng:</td>
                <td style="padding: 10px;"><?php echo formatDate($order['created_at'], 'd/m/Y H:i:s'); ?></td>
            </tr>
            <?php if ($order['updated_at'] && $order['updated_at'] !== $order['created_at']): ?>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Cập nhật lần cuối:</td>
                <td style="padding: 10px;"><?php echo formatDate($order['updated_at'], 'd/m/Y H:i:s'); ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($order['note']): ?>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Ghi chú:</td>
                <td style="padding: 10px;"><?php echo nl2br(htmlspecialchars($order['note'])); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Thông tin khách hàng -->
    <div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; border-bottom: 2px solid #e74c3c; padding-bottom: 10px;">
            <i class="fas fa-user"></i> Thông tin khách hàng
        </h2>
        
        <table class="detail-table" style="width: 100%; border-collapse: collapse;">
            <?php if ($order['user_id']): ?>
            <tr>
                <td style="padding: 10px; font-weight: bold; width: 40%;">ID khách hàng:</td>
                <td style="padding: 10px;">
                    <a href="../user/customers.php?q=<?php echo urlencode($order['user_id']); ?>" style="color: #3498db;">
                        #<?php echo $order['user_id']; ?>
                    </a>
                </td>
            </tr>
            <?php if ($order['username']): ?>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Username:</td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($order['username']); ?></td>
            </tr>
            <?php endif; ?>
            <?php endif; ?>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Họ tên:</td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($order['customer_name']); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Email:</td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($order['customer_email']); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Số điện thoại:</td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($order['customer_phone']); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Địa chỉ giao hàng:</td>
                <td style="padding: 10px;"><?php echo nl2br(htmlspecialchars($order['customer_address'])); ?></td>
            </tr>
        </table>
    </div>
</div>

<!-- Chi tiết sản phẩm -->
<div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 20px;">
    <h2 style="margin-top: 0; border-bottom: 2px solid #27ae60; padding-bottom: 10px;">
        <i class="fas fa-box"></i> Chi tiết sản phẩm
    </h2>
    
    <div class="data-table">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">STT</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Sản phẩm</th>
                    <th style="padding: 12px; text-align: center; border-bottom: 2px solid #dee2e6;">Đơn giá</th>
                    <th style="padding: 12px; text-align: center; border-bottom: 2px solid #dee2e6;">Số lượng</th>
                    <th style="padding: 12px; text-align: right; border-bottom: 2px solid #dee2e6;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $stt = 1;
                foreach ($order['details'] as $item): 
                ?>
                <tr style="border-bottom: 1px solid #dee2e6;">
                    <td style="padding: 12px;"><?php echo $stt++; ?></td>
                    <td style="padding: 12px;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <?php if ($item['image']): ?>
                            <img src="<?php echo getProductImage($item['image']); ?>" 
                                 alt="<?php echo htmlspecialchars($item['product_name']); ?>"
                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 6px;">
                            <?php endif; ?>
                            <div>
                                <strong><?php echo htmlspecialchars($item['product_name']); ?></strong>
                                <?php if ($item['product_id']): ?>
                                <br>
                                <small style="color: #666;">
                                    <a href="../../product-detail.php?id=<?php echo $item['product_id']; ?>" target="_blank" style="color: #3498db;">
                                        Xem sản phẩm
                                    </a>
                                </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </td>
                    <td style="padding: 12px; text-align: center;"><?php echo formatCurrency($item['product_price']); ?></td>
                    <td style="padding: 12px; text-align: center;"><?php echo $item['quantity']; ?></td>
                    <td style="padding: 12px; text-align: right; font-weight: bold; color: #e74c3c;">
                        <?php echo formatCurrency($item['total']); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Tổng thanh toán -->
<div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 20px;">
    <div style="display: flex; justify-content: flex-end;">
        <div style="width: 400px;">
            <table style="width: 100%; border-collapse: collapse;">
                <tr>
                    <td style="padding: 10px; font-weight: bold; text-align: right;">Tạm tính:</td>
                    <td style="padding: 10px; text-align: right;"><?php echo formatCurrency($order['subtotal']); ?></td>
                </tr>
                <?php if ($order['coupon_discount'] > 0): ?>
                <tr>
                    <td style="padding: 10px; font-weight: bold; text-align: right; color: #27ae60;">Giảm giá:</td>
                    <td style="padding: 10px; text-align: right; color: #27ae60;">
                        -<?php echo formatCurrency($order['coupon_discount']); ?>
                    </td>
                </tr>
                <?php endif; ?>
                <tr style="border-top: 2px solid #dee2e6;">
                    <td style="padding: 15px; font-weight: bold; font-size: 18px; text-align: right;">Tổng cộng:</td>
                    <td style="padding: 15px; font-weight: bold; font-size: 18px; text-align: right; color: #e74c3c;">
                        <?php echo formatCurrency($order['total']); ?>
                    </td>
                </tr>
            </table>
        </div>
    </div>
</div>

<style>
@media (max-width: 768px) {
    .order-detail-container {
        grid-template-columns: 1fr !important;
    }
}
</style>

</main>
</div>
</body>
</html>

