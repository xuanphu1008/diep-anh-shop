<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Supplier.php';

requireStaff();

$supplierModel = new Supplier();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitizeInput($_POST['name']),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'address' => sanitizeInput($_POST['address'] ?? ''),
        'status' => isset($_POST['status']) ? 1 : 0
    ];
    
    if (isset($_POST['id']) && $_POST['id'] > 0) {
        $supplierModel->updateSupplier($_POST['id'], $data);
        setFlashMessage('success', 'Cập nhật nhà cung cấp thành công');
    } else {
        $supplierModel->addSupplier($data);
        setFlashMessage('success', 'Thêm nhà cung cấp thành công');
    }
    redirect('index.php');
}

if (isset($_GET['delete'])) {
    $supplierModel->deleteSupplier($_GET['delete']);
    setFlashMessage('success', 'Xóa nhà cung cấp thành công');
    redirect('index.php');
}

// Filters & pagination
$filters = [];
$filters['keyword'] = $_GET['q'] ?? '';
$filters['status'] = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get suppliers with filters
$total = $supplierModel->countAdminSuppliers($filters);
$suppliers = $supplierModel->getAdminSuppliers($filters, $perPage, $offset);

$editSupplier = null;
if (isset($_GET['edit'])) {
    $editSupplier = $supplierModel->getSupplierById($_GET['edit']);
}
$pageTitle = 'Quản lý nhà cung cấp - Admin';
$activeMenu = 'suppliers';
include __DIR__ . '/../layout.php';
?>

            <h1><i class="fas fa-truck"></i> Quản lý nhà cung cấp</h1>
            <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <div class="admin-toolbar d-flex justify-between mb-20">
                <form method="GET" id="filterForm" style="display:flex; gap:10px; align-items:center;">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Tìm tên, email, điện thoại..." class="form-control">
                    <select name="status" class="form-control">
                        <option value="">Tất cả</option>
                        <option value="1" <?php echo $filters['status'] === '1' ? 'selected' : ''; ?>>Hoạt động</option>
                        <option value="0" <?php echo $filters['status'] === '0' ? 'selected' : ''; ?>>Ngừng hoạt động</option>
                    </select>
                    <button class="btn btn-primary">Lọc</button>
                </form>
                <div class="d-flex gap-10">
                    <button id="bulkActivateBtn" class="btn btn-success">Kích hoạt</button>
                    <button id="bulkDeactivateBtn" class="btn btn-warning">Ngừng hoạt động</button>
                    <button id="bulkDeleteBtn" class="btn btn-danger">Xóa</button>
                    <button id="exportSuppliersBtn" class="btn btn-info">Xuất CSV</button>
                </div>
            </div>

            <div class="content-grid">
                <div class="data-table">
                    <form id="bulkSuppliersForm">
                    <table>
                        <thead>
                            <tr>
                                <th style="width:40px;"><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
                                <th>Tên nhà cung cấp</th>
                                <th>Email</th>
                                <th>Điện thoại</th>
                                <th>Địa chỉ</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suppliers as $sup): ?>
                            <tr data-id="<?php echo $sup['id']; ?>">
                                <td><input type="checkbox" name="ids[]" value="<?php echo $sup['id']; ?>"></td>
                                <td><?php echo $sup['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($sup['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($sup['email']); ?></td>
                                <td><?php echo htmlspecialchars($sup['phone']); ?></td>
                                <td><?php echo htmlspecialchars($sup['address']); ?></td>
                                <td><?php echo $sup['status'] ? '<span class="badge badge-success">Hoạt động</span>' : '<span class="badge badge-secondary">Ngừng hoạt động</span>'; ?></td>
                                <td>
                                    <a href="?edit=<?php echo $sup['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                    <a href="?delete=<?php echo $sup['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa nhà cung cấp?')"><i class="fas fa-trash"></i></a>
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
                    <h3><?php echo $editSupplier ? 'Sửa nhà cung cấp' : 'Thêm nhà cung cấp'; ?></h3>
                    <form method="POST">
                        <?php if ($editSupplier): ?>
                            <input type="hidden" name="id" value="<?php echo $editSupplier['id']; ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label>Tên nhà cung cấp *</label>
                            <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($editSupplier['name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" value="<?php echo htmlspecialchars($editSupplier['email'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Điện thoại</label>
                            <input type="text" name="phone" class="form-control" value="<?php echo htmlspecialchars($editSupplier['phone'] ?? ''); ?>">
                        </div>
                        <div class="form-group">
                            <label>Địa chỉ</label>
                            <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($editSupplier['address'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="status" <?php echo ($editSupplier['status'] ?? 1) ? 'checked' : ''; ?>> Hoạt động
                            </label>
                        </div>
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> <?php echo $editSupplier ? 'Cập nhật' : 'Thêm mới'; ?></button>
                            <?php if ($editSupplier): ?>
                                <a href="index.php" class="btn btn-secondary">Hủy</a>
                            <?php endif; ?>
                        </div>
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
                document.querySelectorAll('#bulkSuppliersForm tbody input[type=checkbox]').forEach(cb => cb.checked = this.checked);
            });
        }
        function getSelectedIds(){
            return Array.from(document.querySelectorAll('#bulkSuppliersForm tbody input[type=checkbox]:checked')).map(i => i.value);
        }
        function doBulkAction(action){
            const ids = getSelectedIds();
            if (ids.length === 0) { alert('Chọn ít nhất một nhà cung cấp'); return; }
            if (!confirm('Xác nhận thực hiện: ' + action + ' trên ' + ids.length + ' nhà cung cấp?')) return;
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
        document.getElementById('exportSuppliersBtn')?.addEventListener('click', function(){
            const rows = Array.from(document.querySelectorAll('.data-table tbody tr'));
            let csv = 'ID,Name,Email,Phone,Address,Status\n';
            rows.forEach(r=>{
                const cols = r.querySelectorAll('td');
                if (!cols.length) return;
                const id = cols[1].innerText.trim();
                const name = '"' + cols[2].innerText.trim().replace(/"/g,'""') + '"';
                const email = cols[3].innerText.trim();
                const phone = cols[4].innerText.trim();
                const address = cols[5].innerText.trim();
                const status = cols[6].innerText.trim();
                csv += [id, name, email, phone, address, status].join(',') + '\n';
            });
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'suppliers_export.csv';
            document.body.appendChild(link);
            link.click();
            link.remove();
        });
    });
    </script>
</body>
</html>