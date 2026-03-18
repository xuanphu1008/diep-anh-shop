<?php
// admin/orders/index.php - Quản lý đơn hàng

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Order.php';

requireStaff();

$orderModel = new Order();

// Search and filter
$filterStatus = $_GET['status'] ?? null;
$keyword = $_GET['q'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Xử lý cập nhật trạng thái
if (isset($_POST['update_status']) && isset($_POST['order_id']) && isset($_POST['status'])) {
    $orderId = (int)$_POST['order_id'];
    $status = $_POST['status'];
    $result = $orderModel->updateOrderStatus($orderId, $status);
    setFlashMessage($result['success'] ? 'success' : 'error', $result['message']);
    
    // Giữ nguyên các tham số filter và search khi redirect
    $redirectUrl = 'index.php';
    $queryParams = [];
    if ($filterStatus) {
        $queryParams['status'] = $filterStatus;
    }
    if ($keyword) {
        $queryParams['q'] = $keyword;
    }
    if (!empty($queryParams)) {
        $redirectUrl .= '?' . http_build_query($queryParams);
    }
    redirect($redirectUrl);
}

// Get all orders and apply filters
$allOrders = $orderModel->getAllOrders($filterStatus);
$filtered = array_filter($allOrders, function($o) use ($keyword) {
    if (!$keyword) return true;
    $kw = mb_strtolower($keyword);
    return mb_strpos(mb_strtolower($o['order_code']), $kw) !== false || 
           mb_strpos(mb_strtolower($o['customer_name']), $kw) !== false || 
           mb_strpos(mb_strtolower($o['customer_email']), $kw) !== false || 
           mb_strpos(mb_strtolower($o['customer_phone']), $kw) !== false;
});
$total = count($filtered);
$orders = array_slice(array_values($filtered), $offset, $perPage);
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
            
            <div class="admin-toolbar d-flex justify-between mb-20">
                <form method="GET" style="display:flex; gap:10px; align-items:center;">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($keyword); ?>" placeholder="Tìm mã đơn, khách hàng..." class="form-control">
                    <?php if ($filterStatus): ?>
                    <input type="hidden" name="status" value="<?php echo htmlspecialchars($filterStatus); ?>">
                    <?php endif; ?>
                    <button class="btn btn-primary">Tìm kiếm</button>
                </form>
                <button id="exportOrdersBtn" class="btn btn-success">Xuất CSV</button>
            </div>
            
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
                            <td><strong style="color: var(--admin-primary);"><?php echo formatCurrency($order['total']); ?></strong></td>
                            <td><?php echo getPaymentMethodText($order['payment_method']); ?></td>
                            <td>
                                <form method="POST" style="display: inline-flex; gap: 5px; align-items: center;" class="status-update-form">
                                    <input type="hidden" name="order_id" value="<?php echo $order['id']; ?>">
                                    <input type="hidden" name="update_status" value="1">
                                    <select name="status" class="form-control status-select" data-original-value="<?php echo htmlspecialchars($order['order_status']); ?>" style="min-width: 150px;">
                                        <option value="pending" <?php echo $order['order_status'] === 'pending' ? 'selected' : ''; ?>>Chờ xác nhận</option>
                                        <option value="confirmed" <?php echo $order['order_status'] === 'confirmed' ? 'selected' : ''; ?>>Đã xác nhận</option>
                                        <option value="processing" <?php echo $order['order_status'] === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                                        <option value="shipping" <?php echo $order['order_status'] === 'shipping' ? 'selected' : ''; ?>>Đang giao hàng</option>
                                        <option value="delivered" <?php echo $order['order_status'] === 'delivered' ? 'selected' : ''; ?>>Đã giao hàng</option>
                                        <option value="cancelled" <?php echo $order['order_status'] === 'cancelled' ? 'selected' : ''; ?>>Đã hủy</option>
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-primary update-status-btn" title="Cập nhật trạng thái" style="white-space: nowrap; padding: 6px 12px; margin-left: 5px;">
                                        <i class="fas fa-save"></i> Cập nhật
                                    </button>
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
            
            <?php if ($total > $perPage): ?>
            <div class="pagination mt-20">
                <?php for ($p = 1; $p <= ceil($total / $perPage); $p++): $qs = $_GET; $qs['page'] = $p; ?>
                <a href="?<?php echo http_build_query($qs); ?>" class="<?php echo $p == $page ? 'active' : ''; ?>"><?php echo $p; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>
    <script>
        // Đợi DOM load xong
        document.addEventListener('DOMContentLoaded', function() {
            // Xử lý hiển thị nút cập nhật khi thay đổi trạng thái
            document.querySelectorAll('select.status-select').forEach(function(select) {
                const form = select.closest('form');
                const updateBtn = form.querySelector('.update-status-btn');
                const originalValue = select.getAttribute('data-original-value');
                
                // Xử lý khi thay đổi giá trị - có thể thêm highlight nếu cần
            select.addEventListener('change', function() {
                    // Có thể thêm visual feedback nếu cần
                    if (this.value !== originalValue) {
                        this.style.borderColor = 'var(--admin-primary)';
                    } else {
                        this.style.borderColor = '';
                    }
                });
                
                // Xác nhận trước khi submit form
                form.addEventListener('submit', function(e) {
                    const currentValue = select.value;
                    
                    // Nếu giá trị không thay đổi, không submit
                    if (currentValue === originalValue) {
                        e.preventDefault();
                        alert('Vui lòng chọn trạng thái khác để cập nhật!');
                        return false;
                    }
                    
                    // Xác nhận trước khi submit
                    if (!confirm('Bạn có chắc chắn muốn cập nhật trạng thái đơn hàng từ "' + 
                        getStatusText(originalValue) + '" sang "' + getStatusText(currentValue) + '"?')) {
                        e.preventDefault();
                        // Quay lại trạng thái ban đầu
                        select.value = originalValue;
                        select.style.borderColor = '';
                        return false;
                }
            });
        });
        
            // Hàm lấy text của trạng thái
            function getStatusText(status) {
                const statuses = {
                    'pending': 'Chờ xác nhận',
                    'confirmed': 'Đã xác nhận',
                    'processing': 'Đang xử lý',
                    'shipping': 'Đang giao hàng',
                    'delivered': 'Đã giao hàng',
                    'cancelled': 'Đã hủy'
                };
                return statuses[status] || status;
            }
            
            // Export CSV
            const exportBtn = document.getElementById('exportOrdersBtn');
            if (exportBtn) {
                exportBtn.addEventListener('click', function() {
            const rows = document.querySelectorAll('table tbody tr');
            let csv = '"Mã đơn","Khách hàng","SĐT","Tổng tiền","Thanh toán","Trạng thái","Ngày đặt"\n';
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const values = [cells[0], cells[1], cells[2], cells[3], cells[4], cells[5], cells[6]].map(c => '"' + (c.textContent || '').trim().replace(/"/g, '""') + '"');
                csv += values.join(',') + '\n';
            });
            const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'orders-' + new Date().toISOString().split('T')[0] + '.csv';
            link.click();
        });
            }
        });
    </script>
</body>
</html>