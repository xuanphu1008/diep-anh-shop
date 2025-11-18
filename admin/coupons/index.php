<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Coupon.php';

requireStaff();

$couponModel = new Coupon();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'code' => strtoupper(sanitizeInput($_POST['code'])),
        'type' => $_POST['type'],
        'value' => (float)$_POST['value'],
        'min_order_value' => (float)($_POST['min_order_value'] ?? 0),
        'max_discount' => !empty($_POST['max_discount']) ? (float)$_POST['max_discount'] : null,
        'quantity' => (int)$_POST['quantity'],
        'start_date' => $_POST['start_date'] ?? null,
        'end_date' => $_POST['end_date'] ?? null,
        'status' => isset($_POST['status']) ? 1 : 0
    ];
    
    if (isset($_POST['id']) && $_POST['id'] > 0) {
        $couponModel->updateCoupon($_POST['id'], $data);
        setFlashMessage('success', 'Cập nhật mã giảm giá thành công');
    } else {
        $result = $couponModel->addCoupon($data);
        setFlashMessage($result['success'] ? 'success' : 'error', $result['message']);
    }
    redirect('index.php');
}

if (isset($_GET['delete'])) {
    $couponModel->deleteCoupon($_GET['delete']);
    setFlashMessage('success', 'Xóa mã giảm giá thành công');
    redirect('index.php');
}

$coupons = $couponModel->getAllCoupons();
$editCoupon = null;
if (isset($_GET['edit'])) {
    $editCoupon = $couponModel->getCouponById($_GET['edit']);
}
$pageTitle = 'Quản lý mã giảm giá - Admin';
$activeMenu = 'coupons';
include __DIR__ . '/../layout.php';
?>
            <div class="page-header">
                <h1><i class="fas fa-tags"></i> Quản lý mã giảm giá</h1>
            </div>
            
            <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="content-grid">
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>Mã</th>
                                <th>Loại</th>
                                <th>Giá trị</th>
                                <th>Số lượng</th>
                                <th>Đã dùng</th>
                                <th>Trạng thái</th>
                                <th>Hiệu lực</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($coupons as $coupon): ?>
                            <tr>
                                <td><strong><?php echo htmlspecialchars($coupon['code']); ?></strong></td>
                                <td><?php echo $coupon['type'] === 'percent' ? 'Phần trăm' : 'Cố định'; ?></td>
                                <td>
                                    <?php if ($coupon['type'] === 'percent'): ?>
                                        <?php echo $coupon['value']; ?>%
                                    <?php else: ?>
                                        <?php echo formatCurrency($coupon['value']); ?>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo $coupon['quantity']; ?></td>
                                <td><?php echo $coupon['used_quantity']; ?></td>
                                <td>
                                    <?php if ($coupon['status']): ?>
                                        <span class="badge badge-success">Kích hoạt</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Tắt</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($coupon['start_date'] && $coupon['end_date']): ?>
                                        <?php echo formatDate($coupon['start_date'], 'd/m/Y'); ?> - 
                                        <?php echo formatDate($coupon['end_date'], 'd/m/Y'); ?>
                                    <?php else: ?>
                                        Không giới hạn
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-danger"
                                       onclick="return confirm('Xóa mã giảm giá?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="background: #fff; padding: 30px; border-radius: 10px; height: fit-content;">
                    <h3><?php echo $editCoupon ? 'Sửa mã giảm giá' : 'Tạo mã giảm giá'; ?></h3>
                    <form method="POST">
                        <?php if ($editCoupon): ?>
                            <input type="hidden" name="id" value="<?php echo $editCoupon['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>Mã giảm giá *</label>
                            <input type="text" name="code" class="form-control" style="text-transform: uppercase;"
                                   value="<?php echo htmlspecialchars($editCoupon['code'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Loại *</label>
                            <select name="type" class="form-control" required>
                                <option value="percent" <?php echo ($editCoupon['type'] ?? '') === 'percent' ? 'selected' : ''; ?>>Phần trăm (%)</option>
                                <option value="fixed" <?php echo ($editCoupon['type'] ?? '') === 'fixed' ? 'selected' : ''; ?>>Cố định (đ)</option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Giá trị *</label>
                            <input type="number" name="value" class="form-control" step="0.01"
                                   value="<?php echo $editCoupon['value'] ?? ''; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Đơn hàng tối thiểu</label>
                            <input type="number" name="min_order_value" class="form-control" step="1000"
                                   value="<?php echo $editCoupon['min_order_value'] ?? 0; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Giảm tối đa (cho %)</label>
                            <input type="number" name="max_discount" class="form-control" step="1000"
                                   value="<?php echo $editCoupon['max_discount'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Số lượng *</label>
                            <input type="number" name="quantity" class="form-control"
                                   value="<?php echo $editCoupon['quantity'] ?? 1; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Ngày bắt đầu</label>
                            <input type="datetime-local" name="start_date" class="form-control"
                                   value="<?php echo $editCoupon['start_date'] ? date('Y-m-d\TH:i', strtotime($editCoupon['start_date'])) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Ngày kết thúc</label>
                            <input type="datetime-local" name="end_date" class="form-control"
                                   value="<?php echo $editCoupon['end_date'] ? date('Y-m-d\TH:i', strtotime($editCoupon['end_date'])) : ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="status" <?php echo ($editCoupon['status'] ?? 1) ? 'checked' : ''; ?>>
                                Kích hoạt
                            </label>
                        </div>
                        
                        <button type="submit" class="btn btn-success btn-block">
                            <i class="fas fa-save"></i> <?php echo $editCoupon ? 'Cập nhật' : 'Tạo mã'; ?>
                        </button>
                        <?php if ($editCoupon): ?>
                            <a href="index.php" class="btn btn-secondary btn-block">Hủy</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>