<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/User.php';

requireStaff();

$userModel = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['edit'])) {
    $data = [
        'username' => sanitizeInput($_POST['username']),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'role' => 'staff',
        'status' => isset($_POST['status']) ? 1 : 0
    ];
    
    if (isset($_POST['password']) && !empty($_POST['password'])) {
        $data['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
    }
    
    if (isset($_POST['id']) && $_POST['id'] > 0) {
        unset($data['role']);
        unset($data['username']);
        $db = new Database();
        $sets = implode(', ', array_map(fn($k) => "$k = ?", array_keys($data)));
        $db->query("UPDATE users SET $sets WHERE id = ? AND role = 'staff'", [...array_values($data), $_POST['id']]);
        setFlashMessage('success', 'Cập nhật nhân viên thành công');
    } else {
        if (empty($_POST['password'])) {
            setFlashMessage('error', 'Mật khẩu là bắt buộc');
            redirect('staff.php');
        }
        $data['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
        $db = new Database();
        $keys = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $db->query("INSERT INTO users ($keys) VALUES ($placeholders)", array_values($data));
        setFlashMessage('success', 'Thêm nhân viên thành công');
    }
    redirect('staff.php');
}

if (isset($_GET['delete'])) {
    $db = new Database();
    $db->query("DELETE FROM users WHERE id = ? AND role = 'staff'", [$_GET['delete']]);
    setFlashMessage('success', 'Xóa nhân viên thành công');
    redirect('staff.php');
}

$filters = [];
$filters['keyword'] = $_GET['q'] ?? '';
$filters['status'] = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$db = new Database();
$allStaff = $db->fetchAll("SELECT * FROM users WHERE role = 'staff' ORDER BY id DESC");
$filtered = array_filter($allStaff, function($u) use ($filters) {
    $ok = true;
    if ($filters['keyword']) {
        $kw = mb_strtolower($filters['keyword']);
        $uname = mb_strtolower($u['username'] ?? '');
        $uemail = mb_strtolower($u['email'] ?? '');
        $uphone = mb_strtolower($u['phone'] ?? '');
        $ok = $ok && (mb_strpos($uname, $kw) !== false || mb_strpos($uemail, $kw) !== false || mb_strpos($uphone, $kw) !== false);
    }
    if ($filters['status'] !== '') $ok = $ok && ((string)$u['status'] === $filters['status']);
    return $ok;
});
$total = count($filtered);
$staff = array_slice(array_values($filtered), $offset, $perPage);

$pageTitle = 'Quản lý nhân viên - Admin';
$activeMenu = 'staff';
include __DIR__ . '/../layout.php';
?>
    <div class="section-header d-flex justify-between align-center" style="margin-bottom: 20px;">
        <h1 class="section-title"><i class="fas fa-user-tie"></i> Quản lý nhân viên</h1>
        <button id="exportStaffBtn" class="btn btn-success">Xuất CSV</button>
    </div>
    <div class="admin-toolbar d-flex justify-between mb-20">
        <form method="GET" style="display:flex; gap:10px; align-items:center;">
            <input type="text" name="q" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Tìm username, email..." class="form-control">
            <select name="status" class="form-control">
                <option value="">Tất cả</option>
                <option value="1" <?php echo $filters['status'] === '1' ? 'selected' : ''; ?>>Kích hoạt</option>
                <option value="0" <?php echo $filters['status'] === '0' ? 'selected' : ''; ?>>Vô hiệu hóa</option>
            </select>
            <button class="btn btn-primary">Lọc</button>
        </form>
        <div class="d-flex gap-10">
            <button id="bulkActivateBtn" class="btn btn-success">Kích hoạt</button>
            <button id="bulkDeactivateBtn" class="btn btn-warning">Vô hiệu hóa</button>
            <button id="bulkDeleteBtn" class="btn btn-danger">Xóa</button>
        </div>
    </div>
    
    <div class="content-grid">
        <div class="data-table">
            <form id="bulkStaffForm">
            <table>
                <thead>
                    <tr>
                        <th style="width:40px;"><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Điện thoại</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($staff as $s): ?>
                    <tr data-id="<?php echo $s['id']; ?>">
                        <td><input type="checkbox" name="ids[]" value="<?php echo $s['id']; ?>"></td>
                        <td><?php echo $s['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($s['username'] ?? ''); ?></strong></td>
                        <td><?php echo htmlspecialchars($s['email'] ?? ''); ?></td>
                        <td><?php echo htmlspecialchars($s['phone'] ?? ''); ?></td>
                        <td><span class="badge badge-<?php echo $s['status'] ? 'success' : 'secondary'; ?>"><?php echo $s['status'] ? 'Kích hoạt' : 'Vô hiệu hóa'; ?></span></td>
                        <td>
                            <a href="?edit=<?php echo $s['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                            <a href="?delete=<?php echo $s['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa nhân viên?')"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            </form>
            <?php if ($total > $perPage): ?>
            <div class="pagination mt-20">
                <?php for ($p = 1; $p <= ceil($total / $perPage); $p++): $qs = $_GET; $qs['page'] = $p; ?>
                <a href="?<?php echo http_build_query($qs); ?>" class="<?php echo $p == $page ? 'active' : ''; ?>"><?php echo $p; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </div>
        <div style="background: #fff; padding: 30px; border-radius: 10px; height: fit-content;">
            <h3>Thêm/Sửa nhân viên</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Tên nhân viên *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="form-group">
                    <label>Điện thoại</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="form-group">
                    <label>Vai trò</label>
                    <select name="role" class="form-control">
                        <option value="admin">Admin</option>
                        <option value="staff">Nhân viên</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                    <a href="staff.php" class="btn btn-secondary">Hủy</a>
                </div>
            </form>
        </div>
    </div>
</main>
</div>
</body>
</html>

<script>
    document.getElementById('selectAll').addEventListener('change', function() {
        document.querySelectorAll('#bulkStaffForm input[name="ids[]"]').forEach(cb => cb.checked = this.checked);
    });
    
    document.getElementById('bulkActivateBtn').addEventListener('click', function(e) {
        e.preventDefault();
        const ids = Array.from(document.querySelectorAll('#bulkStaffForm input[name="ids[]"]:checked')).map(cb => cb.value);
        if (ids.length === 0) return alert('Chọn ít nhất 1 nhân viên');
        fetch('staff-bulk-handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'bulk_activate', ids})
        }).then(r => r.json()).then(d => {
            if (d.success) location.reload();
            else alert(d.message);
        });
    });
    
    document.getElementById('bulkDeactivateBtn').addEventListener('click', function(e) {
        e.preventDefault();
        const ids = Array.from(document.querySelectorAll('#bulkStaffForm input[name="ids[]"]:checked')).map(cb => cb.value);
        if (ids.length === 0) return alert('Chọn ít nhất 1 nhân viên');
        fetch('staff-bulk-handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'bulk_deactivate', ids})
        }).then(r => r.json()).then(d => {
            if (d.success) location.reload();
            else alert(d.message);
        });
    });
    
    document.getElementById('bulkDeleteBtn').addEventListener('click', function(e) {
        e.preventDefault();
        const ids = Array.from(document.querySelectorAll('#bulkStaffForm input[name="ids[]"]:checked')).map(cb => cb.value);
        if (ids.length === 0) return alert('Chọn ít nhất 1 nhân viên');
        if (!confirm('Xóa ' + ids.length + ' nhân viên?')) return;
        fetch('staff-bulk-handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'bulk_delete', ids})
        }).then(r => r.json()).then(d => {
            if (d.success) location.reload();
            else alert(d.message);
        });
    });
    
    document.getElementById('exportStaffBtn').addEventListener('click', function() {
        const rows = document.querySelectorAll('#bulkStaffForm table tbody tr');
        let csv = '"ID","Username","Email","SĐT","Trạng thái"\n';
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const values = [cells[1], cells[2], cells[3], cells[4], cells[5]].map(c => '"' + (c.textContent || '').trim().replace(/"/g, '""') + '"');
            csv += values.join(',') + '\n';
        });
        const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'staff-' + new Date().toISOString().split('T')[0] + '.csv';
        link.click();
    });
</script>
