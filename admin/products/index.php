<?php
// admin/products/index.php - Danh sách sản phẩm

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../models/Category.php';

requireStaff();

$productModel = new Product();
$categoryModel = new Category();

// Xử lý xóa (single)
if (isset($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $productModel->deleteProduct($id);
    setFlashMessage('success', 'Xóa sản phẩm thành công');
    redirect('index.php');
}

// Xử lý khôi phục (single)
if (isset($_GET['restore'])) {
    $id = (int)$_GET['restore'];
    $productModel->restoreProduct($id);
    setFlashMessage('success', 'Khôi phục sản phẩm thành công');
    redirect('index.php');
}

// Filters & pagination
$filters = [];
$filters['keyword'] = $_GET['q'] ?? '';
$filters['category_id'] = $_GET['category_id'] ?? '';
$filters['status'] = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$total = $productModel->countAdminProducts($filters);
$products = $productModel->getAdminProducts($filters, $perPage, $offset);
$categories = $categoryModel->getAllCategories();
$pageTitle = 'Quản lý sản phẩm - Admin';
$activeMenu = 'products';
include __DIR__ . '/../layout.php';
?>
            <div class="section-header d-flex justify-between align-center" style="margin-bottom: 20px;">
                <h1 class="section-title"><i class="fas fa-box"></i> Quản lý sản phẩm</h1>
                <div class="d-flex gap-10">
                    <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm sản phẩm</a>
                    <button id="exportProductsBtn" class="btn btn-success">Xuất CSV</button>
                </div>
            </div>

            <div class="admin-toolbar d-flex justify-between mb-20">
                <div class="d-flex gap-10 align-center">
                    <form method="GET" id="filterForm" style="display:flex; gap:10px; align-items:center;">
                        <input type="text" name="q" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Tìm tên hoặc mã..." class="form-control">
                        <select name="category_id" class="form-control">
                            <option value="">Tất cả danh mục</option>
                            <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo $cat['id']; ?>" <?php echo $filters['category_id'] == $cat['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="status" class="form-control">
                            <option value="">Tất cả</option>
                            <option value="active" <?php echo $filters['status'] === 'active' ? 'selected' : ''; ?>>Đang bán</option>
                            <option value="inactive" <?php echo $filters['status'] === 'inactive' ? 'selected' : ''; ?>>Ngừng bán</option>
                        </select>
                        <button class="btn btn-primary">Lọc</button>
                    </form>
                </div>
                <div class="d-flex gap-10">
                    <button id="bulkActivateBtn" class="btn btn-success">Kích hoạt</button>
                    <button id="bulkDeactivateBtn" class="btn btn-warning">Ngừng bán</button>
                    <button id="bulkDeleteBtn" class="btn btn-danger">Xóa</button>
                </div>
            </div>
            
            <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="data-table">
                <form id="bulkProductsForm">
                <table>
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="selectAll"></th>
                            <th>ID</th>
                            <th>Hình</th>
                            <th>Tên</th>
                            <th>Danh mục</th>
                            <th>Giá</th>
                            <th>Số lượng</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($products as $product): ?>
                        <tr data-id="<?php echo $product['id']; ?>">
                            <td><input type="checkbox" name="ids[]" value="<?php echo $product['id']; ?>"></td>
                            <td><?php echo $product['id']; ?></td>
                            <td><img src="<?php echo getProductImage($product['image']); ?>" class="product-thumb" style="width:60px;height:60px;object-fit:cover;border-radius:6px;"></td>
                            <td>
                                <strong><?php echo htmlspecialchars($product['name']); ?></strong>
                                <?php if ($product['is_hot']): ?>
                                    <span class="badge badge-hot">HOT</span>
                                <?php endif; ?>
                            </td>
                            <td><?php echo htmlspecialchars($product['category_name']); ?></td>
                            <td><?php echo $product['discount_price'] ? ('<del>'.formatCurrency($product['price']).'</del><br><strong style="color:#e74c3c;">'.formatCurrency($product['discount_price']).'</strong>') : '<strong>'.formatCurrency($product['price']).'</strong>'; ?></td>
                            <td><span style="color: <?php echo $product['quantity'] > 0 ? '#27ae60' : '#e74c3c'; ?>;"><?php echo $product['quantity']; ?></span></td>
                            <td><?php echo $product['is_active'] ? '<span class="badge badge-success">Đang bán</span>' : '<span class="badge badge-secondary">Ngừng bán</span>'; ?></td>
                            <td>
                                <a href="edit.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-primary" title="Sửa"><i class="fas fa-edit"></i></a>
                                <a href="import.php?id=<?php echo $product['id']; ?>" class="btn btn-sm btn-success" title="Nhập hàng"><i class="fas fa-download"></i></a>
                                <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn có chắc muốn xóa?')" title="Xóa"><i class="fas fa-trash"></i></a>
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
        </main>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function(){
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', function(){
                document.querySelectorAll('#bulkProductsForm tbody input[type=checkbox]').forEach(cb => cb.checked = this.checked);
            });
        }

        function getSelectedIds(){
            return Array.from(document.querySelectorAll('#bulkProductsForm tbody input[type=checkbox]:checked')).map(i => i.value);
        }

        function doBulkAction(action){
            const ids = getSelectedIds();
            if (ids.length === 0) { alert('Chọn ít nhất một sản phẩm'); return; }
            if (!confirm('Xác nhận thực hiện: ' + action + ' trên ' + ids.length + ' sản phẩm?')) return;
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

        // Export current visible rows to CSV
        document.getElementById('exportProductsBtn')?.addEventListener('click', function(){
            const rows = Array.from(document.querySelectorAll('.data-table tbody tr'));
            let csv = 'ID,Name,Category,Price,Quantity,Status\n';
            rows.forEach(r=>{
                const cols = r.querySelectorAll('td');
                if (!cols.length) return;
                const id = cols[1].innerText.trim();
                const name = '"' + cols[3].innerText.trim().replace(/"/g,'""') + '"';
                const cat = cols[4].innerText.trim();
                const price = cols[5].innerText.trim().replace(/\n/g,' ');
                const qty = cols[6].innerText.trim();
                const status = cols[7].innerText.trim();
                csv += [id, name, cat, price, qty, status].join(',') + '\n';
            });
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'products_export.csv';
            document.body.appendChild(link);
            link.click();
            link.remove();
        });
    });
    </script>

</body>
</html>

<style>
.data-table {
    background: #fff;
    border-radius: 10px;
    box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    overflow-x: auto;
    margin-bottom: 30px;
}
.data-table table {
    width: 100%;
    min-width: 900px;
    border-collapse: collapse;
    table-layout: auto;
}
.data-table th, .data-table td {
    padding: 12px 15px;
    border-bottom: 1px solid #ecf0f1;
    white-space: nowrap;
    text-align: left;
}
.data-table th {
    background: #34495e;
    color: #fff;
    position: sticky;
    top: 0;
    z-index: 2;
}
.data-table tr:hover {
    background: #f8f9fa;
}
@media (max-width: 1100px) {
    .data-table table {
        min-width: 700px;
    }
}
</style>