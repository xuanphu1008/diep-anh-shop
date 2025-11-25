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

// Filters & pagination
$filters = [];
$filters['keyword'] = $_GET['q'] ?? '';
$filters['status'] = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$allCoupons = $couponModel->getAllCoupons();
$filtered = array_filter($allCoupons, function($c) use ($filters) {
    $ok = true;
    if ($filters['keyword']) {
        $kw = mb_strtolower($filters['keyword']);
        $ok = $ok && mb_strpos(mb_strtolower($c['code']), $kw) !== false;
    }
    if ($filters['status'] !== '') {
        $ok = $ok && ((string)$c['status'] === $filters['status']);
    }
    return $ok;
});
$total = count($filtered);
$coupons = array_slice(array_values($filtered), $offset, $perPage);

$editCoupon = null;
if (isset($_GET['edit'])) {
    $editCoupon = $couponModel->getCouponById($_GET['edit']);
}
$pageTitle = 'Quản lý mã giảm giá - Admin';
$activeMenu = 'coupons';
include __DIR__ . '/../layout.php';
?>
            <div class="section-header d-flex justify-between align-center" style="margin-bottom: 20px;">
                <h1 class="section-title"><i class="fas fa-tags"></i> Quản lý mã giảm giá</h1>
                <div class="d-flex gap-10">
                    <button id="exportCouponsBtn" class="btn btn-success">Xuất CSV</button>
                </div>
            </div>

            <div class="admin-toolbar d-flex justify-between mb-20">
                <form method="GET" style="display:flex; gap:10px; align-items:center;">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Tìm mã..." class="form-control">
                    <select name="status" class="form-control">
                        <option value="">Tất cả</option>
                        <option value="1" <?php echo $filters['status'] === '1' ? 'selected' : ''; ?>>Kích hoạt</option>
                        <option value="0" <?php echo $filters['status'] === '0' ? 'selected' : ''; ?>>Tắt</option>
                    </select>
                    <button class="btn btn-primary">Lọc</button>
                </form>
                <div class="d-flex gap-10">
                    <button id="bulkActivateBtn" class="btn btn-success">Kích hoạt</button>
                    <button id="bulkDeactivateBtn" class="btn btn-warning">Tắt</button>
                    <button id="bulkDeleteBtn" class="btn btn-danger">Xóa</button>
                </div>
            </div>

            <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="content-grid">
                <div class="data-table">
                    <form id="bulkCouponsForm">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:40px;"><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
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
                            <tr data-id="<?php echo $coupon['id']; ?>">
                                <td><input type="checkbox" name="ids[]" value="<?php echo $coupon['id']; ?>"></td>
                                <td><?php echo $coupon['id']; ?></td>
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
                                <td><?php echo $coupon['status'] ? '<span class="badge badge-success">Kích hoạt</span>' : '<span class="badge badge-secondary">Tắt</span>'; ?></td>
                                <td>
                                    <?php if ($coupon['start_date'] && $coupon['end_date']): ?>
                                        <?php echo formatDate($coupon['start_date'], 'd/m'); ?> - <?php echo formatDate($coupon['end_date'], 'd/m'); ?>
                                    <?php else: ?>
                                        Không giới hạn
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                    <a href="?delete=<?php echo $coupon['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa mã?')"><i class="fas fa-trash"></i></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </form>

                    <!-- Pagination -->
                    <?php if ($total > $perPage): ?>
                    <div class="pagination mt-20">
                        <?php
                        $pages = ceil($total / $perPage);
                        for ($p = 1; $p <= $pages; $p++):
                            $qs = $_GET; $qs['page'] = $p; $link = '?'.http_build_query($qs);
                        ?>
                            <a href="<?php echo $link; ?>" class="<?php echo $p == $page ? 'active' : ''; ?>"><?php echo $p; ?></a>
                        <?php endfor; ?>
                    </div>
                    <?php endif; ?>
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
                            <a href="index.php" class="btn btn-secondary btn-block" style="margin-top:10px;">Hủy</a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function(){
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', function(){
                document.querySelectorAll('#bulkCouponsForm tbody input[type=checkbox]').forEach(cb => cb.checked = this.checked);
            });
        }
        function getSelectedIds(){
            return Array.from(document.querySelectorAll('#bulkCouponsForm tbody input[type=checkbox]:checked')).map(i => i.value);
        }
        function doBulkAction(action){
            const ids = getSelectedIds();
            if (ids.length === 0) { alert('Chọn ít nhất một mã'); return; }
            if (!confirm('Xác nhận thực hiện: ' + action + ' trên ' + ids.length + ' mã?')) return;
            fetch('bulk-handler.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, ids })
            }).then(r => r.json()).then(data => {
                if (data.success) window.location.reload(); else alert(data.message || 'Lỗi');
            }).catch(()=> alert('Lỗi mạng'));
        }
        document.getElementById('bulkActivateBtn')?.addEventListener('click', () => doBulkAction('bulk_activate'));
        document.getElementById('bulkDeactivateBtn')?.addEventListener('click', () => doBulkAction('bulk_deactivate'));
        document.getElementById('bulkDeleteBtn')?.addEventListener('click', () => doBulkAction('bulk_delete'));
        document.getElementById('exportCouponsBtn')?.addEventListener('click', function(){
            const rows = Array.from(document.querySelectorAll('.data-table tbody tr'));
            let csv = 'ID,Code,Type,Value,Qty,Used,Status\n';
            rows.forEach(r=>{
                const cols = r.querySelectorAll('td');
                if (!cols.length) return;
                const id = cols[1].innerText.trim();
                const code = cols[2].innerText.trim();
                const type = cols[3].innerText.trim();
                const value = cols[4].innerText.trim();
                const qty = cols[5].innerText.trim();
                const used = cols[6].innerText.trim();
                const status = cols[7].innerText.trim();
                csv += [id, code, type, value, qty, used, status].join(',') + '\n';
            });
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'coupons_export.csv';
            document.body.appendChild(link);
            link.click();
            link.remove();
        });
    });
    </script>

</body>
</html>