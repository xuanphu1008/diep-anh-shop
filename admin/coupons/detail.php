<?php
// admin/coupons/detail.php - Chi tiết mã giảm giá

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Coupon.php';

requireStaff();

$couponModel = new Coupon();

// Lấy ID mã giảm giá
$couponId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$couponId) {
    setFlashMessage('error', 'Không tìm thấy mã giảm giá');
    redirect('index.php');
}

// Lấy thông tin mã giảm giá
$coupon = $couponModel->getCouponById($couponId);

if (!$coupon) {
    setFlashMessage('error', 'Mã giảm giá không tồn tại');
    redirect('index.php');
}

$pageTitle = 'Chi tiết mã giảm giá - Admin';
$activeMenu = 'coupons';
include __DIR__ . '/../layout.php';
?>

<div class="page-header d-flex justify-between align-center">
    <h1><i class="fas fa-tags"></i> Chi tiết mã giảm giá</h1>
    <div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
        <a href="?edit=<?php echo $coupon['id']; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Sửa mã giảm giá
        </a>
    </div>
</div>

<?php if ($flash = getFlashMessage()): ?>
    <div class="alert alert-<?php echo $flash['type']; ?>">
        <?php echo htmlspecialchars($flash['message']); ?>
    </div>
<?php endif; ?>

<div class="coupon-detail-container" style="max-width: 800px; margin: 20px auto;">
    <div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; border-bottom: 2px solid #3498db; padding-bottom: 10px;">
            <i class="fas fa-info-circle"></i> Thông tin mã giảm giá
        </h2>
        
        <table class="detail-table" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px; font-weight: bold; width: 30%;">ID:</td>
                <td style="padding: 10px;">#<?php echo $coupon['id']; ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Mã giảm giá:</td>
                <td style="padding: 10px;">
                    <strong style="font-size: 18px; color: #e74c3c; letter-spacing: 2px;">
                        <?php echo htmlspecialchars($coupon['code']); ?>
                    </strong>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Loại:</td>
                <td style="padding: 10px;">
                    <?php if ($coupon['type'] === 'percent'): ?>
                        <span class="badge badge-info">Phần trăm (%)</span>
                    <?php else: ?>
                        <span class="badge badge-info">Cố định (VNĐ)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Giá trị:</td>
                <td style="padding: 10px;">
                    <strong style="font-size: 16px; color: #27ae60;">
                        <?php if ($coupon['type'] === 'percent'): ?>
                            <?php echo $coupon['value']; ?>%
                        <?php else: ?>
                            <?php echo formatCurrency($coupon['value']); ?>
                        <?php endif; ?>
                    </strong>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Đơn hàng tối thiểu:</td>
                <td style="padding: 10px;">
                    <?php echo $coupon['min_order_value'] > 0 ? formatCurrency($coupon['min_order_value']) : 'Không giới hạn'; ?>
                </td>
            </tr>
            <?php if ($coupon['type'] === 'percent' && $coupon['max_discount']): ?>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Giảm tối đa:</td>
                <td style="padding: 10px;"><?php echo formatCurrency($coupon['max_discount']); ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Số lượng:</td>
                <td style="padding: 10px;">
                    <span style="color: <?php echo $coupon['used_quantity'] >= $coupon['quantity'] ? '#e74c3c' : '#27ae60'; ?>;">
                        <?php echo $coupon['used_quantity']; ?> / <?php echo $coupon['quantity']; ?> đã sử dụng
                    </span>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Thời gian hiệu lực:</td>
                <td style="padding: 10px;">
                    <?php if ($coupon['start_date'] && $coupon['end_date']): ?>
                        Từ <?php echo formatDate($coupon['start_date'], 'd/m/Y H:i'); ?> 
                        đến <?php echo formatDate($coupon['end_date'], 'd/m/Y H:i'); ?>
                    <?php else: ?>
                        <span style="color: #999;">Không giới hạn</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Trạng thái:</td>
                <td style="padding: 10px;">
                    <?php if ($coupon['status']): ?>
                        <span class="badge badge-success">Kích hoạt</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Tắt</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Ngày tạo:</td>
                <td style="padding: 10px;"><?php echo formatDate($coupon['created_at'], 'd/m/Y H:i:s'); ?></td>
            </tr>
        </table>

        <?php
        // Kiểm tra trạng thái mã giảm giá
        $now = time();
        $isExpired = false;
        $isActive = false;
        
        if ($coupon['start_date'] && strtotime($coupon['start_date']) > $now) {
            $statusMsg = 'Chưa bắt đầu';
            $statusClass = 'badge-warning';
        } elseif ($coupon['end_date'] && strtotime($coupon['end_date']) < $now) {
            $statusMsg = 'Đã hết hạn';
            $statusClass = 'badge-danger';
            $isExpired = true;
        } elseif ($coupon['used_quantity'] >= $coupon['quantity']) {
            $statusMsg = 'Đã hết lượt sử dụng';
            $statusClass = 'badge-danger';
        } elseif (!$coupon['status']) {
            $statusMsg = 'Đã tắt';
            $statusClass = 'badge-secondary';
        } else {
            $statusMsg = 'Đang hoạt động';
            $statusClass = 'badge-success';
            $isActive = true;
        }
        ?>

        <div style="margin-top: 20px; padding: 15px; background: #f8f9fa; border-radius: 6px; border-left: 4px solid #3498db;">
            <strong>Trạng thái hiện tại:</strong> 
            <span class="badge <?php echo $statusClass; ?>" style="margin-left: 10px;">
                <?php echo $statusMsg; ?>
            </span>
        </div>
    </div>
</div>

</main>
</div>
</body>
</html>

