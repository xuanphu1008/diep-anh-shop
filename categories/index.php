<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Category.php';

requireStaff();

$categoryModel = new Category();

// Xử lý bulk delete từ toolbar
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_delete' && !empty($_POST['ids'])) {
    $ids = array_map('intval', $_POST['ids']);
    foreach ($ids as $delId) {
        $categoryModel->deleteCategory($delId);
    }
    setFlashMessage('success', 'Đã xóa các danh mục đã chọn');
    redirect('index.php');
}

// Xử lý thêm/sửa
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitizeInput($_POST['name']),
        'description' => sanitizeInput($_POST['description'] ?? ''),
        'status' => isset($_POST['status']) ? 1 : 0,
        'parent_id' => isset($_POST['parent_id']) && $_POST['parent_id'] !== '' ? intval($_POST['parent_id']) : null
    ];
    
    if (isset($_POST['id']) && $_POST['id'] > 0) {
        // Sửa
        $categoryModel->updateCategory($_POST['id'], $data);
        setFlashMessage('success', 'Cập nhật danh mục thành công');
    } else {
        // Thêm mới
        $categoryModel->addCategory($data);
        setFlashMessage('success', 'Thêm danh mục thành công');
    }
    redirect('index.php');
}

// Xử lý xóa
if (isset($_GET['delete'])) {
    $categoryModel->deleteCategory($_GET['delete']);
    setFlashMessage('success', 'Xóa danh mục thành công');
    redirect('index.php');
}

// Lấy danh sách
$categories = $categoryModel->getAllCategories();
$editCategory = null;
if (isset($_GET['edit'])) {
    $editCategory = $categoryModel->getCategoryById($_GET['edit']);
}
$allForDropdown = $categoryModel->getCategoriesForDropdown(true);
$pageTitle = 'Quản lý danh mục - Admin';
$activeMenu = 'categories';
include __DIR__ . '/../layout.php';
?>
            <div class="section-header">
                <h1 class="section-title"><i class="fas fa-list"></i> Quản lý danh mục</h1>
                <div>
                    <a href="?" class="btn btn-primary">Làm mới</a>
                </div>
            </div>

            <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>

            <div class="content-grid container" style="display: grid; grid-template-columns: 1fr 380px; gap: 30px;">
                <!-- Danh sách danh mục -->
                <div class="data-table admin-list">
                    <div class="admin-toolbar d-flex justify-between mb-20">
                        <div class="d-flex gap-10 align-center">
                            <input type="text" id="searchInput" class="form-control" placeholder="Tìm tên danh mục...">
                            <select id="statusFilter" class="form-control">
                                <option value="">Tất cả</option>
                                <option value="1">Hiển thị</option>
                                <option value="0">Ẩn</option>
                            </select>
                        </div>
                        <div class="d-flex gap-10">
                            <button id="exportBtn" class="btn btn-success">Xuất CSV</button>
                            <button id="bulkDeleteBtn" class="btn btn-danger">Xóa đã chọn</button>
                        </div>
                    </div>

                    <form id="bulkForm" method="POST">
                        <input type="hidden" name="action" value="bulk_delete">
                    <table class="admin-table" id="categoriesTable">
                        <thead>
                            <tr>
                                <th style="width:40px;"><input type="checkbox" id="selectAll"></th>
                                <th>ID</th>
                                <th>Tên danh mục</th>
                                <th>Slug</th>
                                <th>Số sản phẩm</th>
                                <th>Trạng thái</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($categories as $cat): ?>
                            <tr data-name="<?php echo htmlspecialchars(strtolower($cat['name'])); ?>" data-status="<?php echo $cat['status']; ?>">
                                <td><input type="checkbox" name="ids[]" value="<?php echo $cat['id']; ?>"></td>
                                <td><?php echo $cat['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($cat['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($cat['slug']); ?></td>
                                <td><a href="../products/index.php?category_id=<?php echo $cat['id']; ?>" class="text-primary"><?php echo $categoryModel->countProducts($cat['id']); ?></a></td>
                                <td>
                                    <?php if ($cat['status']): ?>
                                        <span class="badge badge-success">Hiển thị</span>
                                    <?php else: ?>
                                        <span class="badge badge-secondary">Ẩn</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <a href="?edit=<?php echo $cat['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $cat['id']; ?>" 
                                       class="btn btn-sm btn-danger"
                                       onclick="return confirm('Bạn có chắc muốn xóa?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                    </form>
                </div>
                <!-- Form thêm/sửa -->
                <div class="card-form shadow rounded-lg">
                    <h3><?php echo $editCategory ? 'Sửa danh mục' : 'Thêm danh mục mới'; ?></h3>
                    <form method="POST">
                        <?php if ($editCategory): ?>
                            <input type="hidden" name="id" value="<?php echo $editCategory['id']; ?>">
                        <?php endif; ?>
                        <div class="form-group">
                            <label>Tên danh mục *</label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?php echo htmlspecialchars($editCategory['name'] ?? ''); ?>" required>
                        </div>
                        <div class="form-group">
                            <label>Mô tả</label>
                            <textarea name="description" class="form-control" rows="4"><?php echo htmlspecialchars($editCategory['description'] ?? ''); ?></textarea>
                        </div>
                        <div class="form-group">
                            <label>Danh mục cha</label>
                            <select name="parent_id" class="form-control">
                                <option value="">-- Không có --</option>
                                <?php foreach ($allForDropdown as $d):
                                    if ($editCategory && $d['id'] == $editCategory['id']) continue; // không cho chọn chính nó
                                ?>
                                <option value="<?php echo $d['id']; ?>" <?php echo ($editCategory && $editCategory['parent_id'] == $d['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($d['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="status" <?php echo ($editCategory['status'] ?? 1) ? 'checked' : ''; ?> >
                                Hiển thị
                            </label>
                        </div>
                        <div class="d-flex gap-10 mt-20">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> <?php echo $editCategory ? 'Cập nhật' : 'Thêm mới'; ?>
                            </button>
                            <?php if ($editCategory): ?>
                                <a href="index.php" class="btn btn-secondary">Hủy</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
<script>
// Select all checkbox
const selectAllEl = document.getElementById('selectAll');
if (selectAllEl) {
    selectAllEl.addEventListener('change', function(){
        const checked = this.checked;
        document.querySelectorAll('#categoriesTable tbody input[type=checkbox]').forEach(cb => cb.checked = checked);
    });
}

const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
if (bulkDeleteBtn) {
    bulkDeleteBtn.addEventListener('click', function(){
        const checked = Array.from(document.querySelectorAll('#categoriesTable tbody input[type=checkbox]:checked'));
            if (checked.length === 0) {
                alert('Vui lòng chọn ít nhất một danh mục để xóa');
                return;
            }
            if (!confirm('Xác nhận xóa các danh mục đã chọn?')) return;
            const ids = checked.map(cb => cb.value);
            fetch('bulk-handler.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'bulk_delete', ids })
            }).then(r => r.json()).then(data => {
                if (data.success) {
                    alert('Đã xóa thành công');
                    window.location.reload();
                } else {
                    alert('Lỗi: ' + (data.message || 'Không thể xóa'));
                }
            }).catch(err => { alert('Lỗi mạng'); });
    });
}

// Search & filter
const searchInput = document.getElementById('searchInput');
const statusFilter = document.getElementById('statusFilter');
function applyFilters(){
    const q = searchInput ? searchInput.value.trim().toLowerCase() : '';
    const status = statusFilter ? statusFilter.value : '';
    document.querySelectorAll('#categoriesTable tbody tr').forEach(row => {
        const name = row.getAttribute('data-name') || '';
        const st = row.getAttribute('data-status');
        const matches = (q === '' || name.indexOf(q) !== -1) && (status === '' || status === st);
        row.style.display = matches ? '' : 'none';
    });
}
if (searchInput) searchInput.addEventListener('input', applyFilters);
if (statusFilter) statusFilter.addEventListener('change', applyFilters);

// Export CSV
const exportBtn = document.getElementById('exportBtn');
if (exportBtn) {
    exportBtn.addEventListener('click', function(){
        const rows = Array.from(document.querySelectorAll('#categoriesTable tbody tr')).filter(r => r.style.display !== 'none');
        let csv = 'ID,Name,Slug,Products,Status\n';
        rows.forEach(r => {
            const cols = r.querySelectorAll('td');
            const values = [cols[1].innerText, '"' + cols[2].innerText.replace(/"/g, '""') + '"', cols[3].innerText, cols[4].innerText];
            csv += values.join(',') + '\n';
        });
        const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = 'categories_export.csv';
        document.body.appendChild(a);
        a.click();
        a.remove();
        URL.revokeObjectURL(url);
    });
}
</script>