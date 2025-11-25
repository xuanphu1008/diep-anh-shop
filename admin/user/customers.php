<?php
// admin/users/customers.php - Quản lý khách hàng

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/User.php';

requireStaff();

$userModel = new User();

// Filters & pagination
$filters = [];
$filters['keyword'] = $_GET['q'] ?? '';
$filters['status'] = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filter customers (server-side for now)
$allCustomers = $userModel->getAllCustomers();
$filtered = array_filter($allCustomers, function($cust) use ($filters) {
    $ok = true;
    if ($filters['keyword']) {
        $kw = mb_strtolower($filters['keyword']);
        $ok = $ok && (
            mb_strpos(mb_strtolower($cust['username']), $kw) !== false ||
            mb_strpos(mb_strtolower($cust['full_name']), $kw) !== false ||
            mb_strpos(mb_strtolower($cust['email']), $kw) !== false ||
            mb_strpos(mb_strtolower($cust['phone']), $kw) !== false
        );
    }
    if ($filters['status'] !== '') {
        $ok = $ok && ((string)$cust['status'] === $filters['status']);
    }
    return $ok;
});
$total = count($filtered);
$customers = array_slice(array_values($filtered), $offset, $perPage);

$pageTitle = 'Quản lý khách hàng - Admin';
$activeMenu = 'customers';
include __DIR__ . '/../layout.php';
?>
            <div class="section-header d-flex justify-between align-center" style="margin-bottom: 20px;">
                <h1 class="section-title"><i class="fas fa-users"></i> Quản lý khách hàng</h1>
                <div class="d-flex gap-10">
                    <button id="exportCustomersBtn" class="btn btn-success">Xuất CSV</button>
                </div>
            </div>

            <div class="admin-toolbar d-flex justify-between mb-20">
                <form method="GET" style="display:flex; gap:10px; align-items:center;">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Tìm username, tên, email, SĐT..." class="form-control">
                    <select name="status" class="form-control">
                        <option value="">Tất cả</option>
                        <option value="1" <?php echo $filters['status'] === '1' ? 'selected' : ''; ?>>Hoạt động</option>
                        <option value="0" <?php echo $filters['status'] === '0' ? 'selected' : ''; ?>>Khóa</option>
                    </select>
                    <button class="btn btn-primary">Lọc</button>
                </form>
                <div class="d-flex gap-10">
                    <button id="bulkUnlockBtn" class="btn btn-success">Kích hoạt</button>
                    <button id="bulkLockBtn" class="btn btn-warning">Khóa</button>
                </div>
            </div>

            <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="data-table">
                <form id="bulkCustomersForm">
                <table>
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="selectAll"></th>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>Điện thoại</th>
                            <th>Ngày đăng ký</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                        <tr data-id="<?php echo $customer['id']; ?>">
                            <td><input type="checkbox" name="ids[]" value="<?php echo $customer['id']; ?>"></td>
                            <td><?php echo $customer['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($customer['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                            <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                            <td><?php echo formatDate($customer['created_at'], 'd/m/Y'); ?></td>
                            <td><?php echo $customer['status'] ? '<span class="badge badge-success">Hoạt động</span>' : '<span class="badge badge-secondary">Khóa</span>'; ?></td>
                            <td>
                                <a href="#" class="btn btn-sm btn-primary" title="Chi tiết"><i class="fas fa-eye"></i></a>
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
                document.querySelectorAll('#bulkCustomersForm tbody input[type=checkbox]').forEach(cb => cb.checked = this.checked);
            });
        }
        function getSelectedIds(){
            return Array.from(document.querySelectorAll('#bulkCustomersForm tbody input[type=checkbox]:checked')).map(i => i.value);
        }
        function doBulkAction(action){
            const ids = getSelectedIds();
            if (ids.length === 0) { alert('Chọn ít nhất một khách hàng'); return; }
            if (!confirm('Xác nhận thực hiện: ' + action + ' trên ' + ids.length + ' khách hàng?')) return;
            fetch('bulk-handler.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, ids })
            }).then(r => r.json()).then(data => {
                if (data.success) window.location.reload(); else alert(data.message || 'Lỗi');
            }).catch(()=> alert('Lỗi mạng'));
        }
        document.getElementById('bulkUnlockBtn')?.addEventListener('click', () => doBulkAction('bulk_unlock'));
        document.getElementById('bulkLockBtn')?.addEventListener('click', () => doBulkAction('bulk_lock'));
        document.getElementById('exportCustomersBtn')?.addEventListener('click', function(){
            const rows = Array.from(document.querySelectorAll('.data-table tbody tr'));
            let csv = 'ID,Username,Full Name,Email,Phone,Registered,Status\n';
            rows.forEach(r=>{
                const cols = r.querySelectorAll('td');
                if (!cols.length) return;
                const id = cols[1].innerText.trim();
                const user = cols[2].innerText.trim();
                const name = '"' + cols[3].innerText.trim().replace(/"/g,'""') + '"';
                const email = cols[4].innerText.trim();
                const phone = cols[5].innerText.trim();
                const reg = cols[6].innerText.trim();
                const status = cols[7].innerText.trim();
                csv += [id, user, name, email, phone, reg, status].join(',') + '\n';
            });
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'customers_export.csv';
            document.body.appendChild(link);
            link.click();
            link.remove();
        });
    });
    </script>

</body>
</html>