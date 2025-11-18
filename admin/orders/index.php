<?php
// admin/orders/index.php - Quản lý đơn hàng

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Order.php';

requireStaff();

$orderModel = new Order();

// Xử lý cập nhật trạng thái
if (isset($_POST['update_status'])) {
    $orderId = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $result = $orderModel->updateOrderStatus($orderId, $status);
    setFlashMessage($result['success'] ? 'success' : 'error', $result['message']);
    redirect('index.php');
}

// Lọc theo trạng thái
$filterStatus = $_GET['status'] ?? null;
$orders = $orderModel->getAllOrders($filterStatus);
$pageTitle = 'Quản lý đơn hàng - Admin';
$activeMenu = 'orders';
include __DIR__ . '/../layout.php';
?>
            <div class="page-header">
                <h1><i class="fas fa-shopping-cart"></i> Quản lý đơn hàng</h1>
            </div>
            
            <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="filter-tabs">
                <a href="index.php" class="<?php echo !$filterStatus ? 'active' : ''; ?>">Tất cả (<?php echo $orderModel->countOrdersByStatus(); ?>)</a>
                <a href="?status=pending" class="<?php echo $filterStatus === 'pending' ? 'active' : ''; ?>">Chờ xác nhận (<?php echo $orderModel->countOrdersByStatus('pending'); ?>)</a>
                <a href="?status=confirmed" class="<?php echo $filterStatus === 'confirmed' ? 'active' : ''; ?>">Đã xác nhận (<?php echo $orderModel->countOrdersByStatus('confirmed'); ?>)</a>
                <a href="?status=shipping" class="<?php echo $filterStatus === 'shipping' ? 'active' : ''; ?>">Đang giao (<?php echo $orderModel->countOrdersByStatus('shipping'); ?>)</a>
                <a href="?status=delivered" class="<?php echo $filterStatus === 'delivered' ? 'active' : ''; ?>">Hoàn thành (<?php echo $orderModel->countOrdersByStatus('delivered'); ?>)</a>
                <a href="?status=cancelled" class="<?php echo $filterStatus === 'cancelled' ? 'active' : ''; ?>">Đã hủy (<?php echo $orderModel->countOrdersByStatus('cancelled'); ?>)</a>
            </div>
            
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>Mã đơn</th>
                            <th>Khách hàng</th>
                            <th>SĐT</th>
                            <th>Tổng tiền</th>
                            <th>Thanh toán</th>
                            <th>Trạng thái</th>
                            <th>Ngày đặt</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($orders as $order): ?>
                        <tr>
                            <td><strong><?php echo $order['order_code']; ?></strong></td>
                            <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                            <td><?php echo htmlspecialchars($order['customer_phone']); ?></td>
                            <td><strong style="color: #e74c3c;"><?php echo formatCurrency($order['total']); ?></strong></td>
                            <td><?php echo getPaymentMethodText($order['payment_method']); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <select name="status" class="form-control" onchange="if(confirm('Cập nhật trạng thái?')) this.form.submit()">
                                        <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                        <option value="confirmed" <?php echo $order['order_status'] === 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                        <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                                        <option value="shipping" <?php echo $order['order_status'] === 'shipping' ? 'selected' : ''; ?>>Đang giao hàng</option>
                                        <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Đã giao hàng</option>
                                        <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                    </select>
                                    <button type="submit" name="update_status" style="display: none;"></button>
                                </form>
                            </td>
                            <td><?php echo formatDate($order['created_at'], 'd/m/Y H:i'); ?></td>
                            <td>
                                <a href="detail.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-primary" title="Chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="print.php?id=<?php echo $order['id']; ?>" class="btn btn-sm btn-success" target="_blank" title="In đơn">
                                    <i class="fas fa-print"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>
    <script>
        // Xác nhận trước khi thay đổi trạng thái
        document.querySelectorAll('select[name="status"]').forEach(function(select) {
            select.addEventListener('change', function() {
                if (!confirm('Bạn có chắc chắn muốn thay đổi trạng thái đơn hàng?')) {
                    // Nếu không đồng ý, quay lại trạng thái ban đầu
                    this.value = this.getAttribute('data-original-value');
                }
            });
        });
        
        // Lưu giá trị ban đầu để so sánh khi thay đổi
        document.querySelectorAll('select[name="status"]').forEach(function(select) {
            select.setAttribute('data-original-value', select.value);
        });