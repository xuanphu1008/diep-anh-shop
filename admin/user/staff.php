<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/User.php';

requireAdmin(); // Chỉ admin mới được quản lý nhân viên

$userModel = new User();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = new Database();
    
    // Check if editing
    $isEdit = isset($_POST['edit']) && isset($_POST['id']) && $_POST['id'] > 0;
    $id = $isEdit ? (int)$_POST['id'] : 0;
    
    // Validate username
    $username = sanitizeInput($_POST['username'] ?? '');
    if (empty($username)) {
        setFlashMessage('error', 'Username là bắt buộc');
        redirect('staff.php' . ($isEdit ? '?edit=' . $id : ''));
    }
    
    // Check if username already exists (for new staff)
    if (!$isEdit) {
        $existing = $db->fetchOne("SELECT id FROM users WHERE username = ?", [$username]);
        if ($existing) {
            setFlashMessage('error', 'Username đã tồn tại');
            redirect('staff.php');
        }
    }
    
    // Validate password
    if (!$isEdit && empty($_POST['password'])) {
        setFlashMessage('error', 'Mật khẩu là bắt buộc');
        redirect('staff.php');
    }
    
    // Prepare data
    $data = [
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'status' => isset($_POST['status']) && $_POST['status'] == '1' ? 1 : 0
    ];
    
    // Kiểm tra không được khóa chính mình
    if ($isEdit && $id == $_SESSION['user_id'] && $data['status'] == 0) {
        setFlashMessage('error', 'Không thể khóa chính mình');
        redirect('staff.php?edit=' . $id);
    }
    
    // Handle password
    if (isset($_POST['password']) && !empty($_POST['password'])) {
        $data['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
    }
    
    if ($isEdit) {
        // Update existing staff
        $sets = [];
        $params = [];
        foreach ($data as $key => $value) {
            $sets[] = "$key = ?";
            $params[] = $value;
        }
        $params[] = $id;
        $sql = "UPDATE users SET " . implode(', ', $sets) . " WHERE id = ? AND role = 'staff'";
        $db->query($sql, $params);
        setFlashMessage('success', 'Cập nhật nhân viên thành công');
    } else {
        // Insert new staff
        $data['username'] = $username;
        $data['role'] = 'staff';
        $data['password'] = password_hash($_POST['password'], PASSWORD_BCRYPT);
        
        $keys = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        $sql = "INSERT INTO users ($keys) VALUES ($placeholders)";
        $db->query($sql, array_values($data));
        setFlashMessage('success', 'Thêm nhân viên thành công');
    }
    
    redirect('staff.php');
}

// Xử lý xóa nhân viên
if (isset($_GET['delete']) && isset($_GET['confirm']) && $_GET['confirm'] === 'yes') {
    $id = (int)$_GET['delete'];
    $db = new Database();
    
    // Kiểm tra không được xóa chính mình
    if ($id == $_SESSION['user_id']) {
        setFlashMessage('error', 'Không thể xóa chính mình');
        redirect('staff.php');
    }
    
    $db->query("DELETE FROM users WHERE id = ? AND role = 'staff'", [$id]);
    setFlashMessage('success', 'Xóa nhân viên thành công');
    redirect('staff.php');
}

// Xử lý toggle status (khóa/mở khóa)
if (isset($_GET['toggle_status']) && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $newStatus = (int)$_GET['toggle_status'];
    
    // Kiểm tra không được khóa chính mình
    if ($id == $_SESSION['user_id'] && $newStatus == 0) {
        setFlashMessage('error', 'Không thể khóa chính mình');
        redirect('staff.php');
    }
    
    $db = new Database();
    $db->query("UPDATE users SET status = ? WHERE id = ? AND role = 'staff'", [$newStatus, $id]);
    setFlashMessage('success', $newStatus ? 'Mở khóa nhân viên thành công' : 'Khóa nhân viên thành công');
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
    
    <?php if ($flash = getFlashMessage()): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
    <?php endif; ?>
    
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
                        <td>
                            <span class="badge badge-<?php echo $s['status'] ? 'success' : 'secondary'; ?>">
                                <?php echo $s['status'] ? 'Kích hoạt' : 'Vô hiệu hóa'; ?>
                            </span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 5px;">
                                <a href="staff.php?edit=<?php echo $s['id']; ?><?php 
                                    $queryParams = $_GET;
                                    unset($queryParams['edit']);
                                    if (!empty($queryParams)) {
                                        echo '&' . http_build_query($queryParams);
                                    }
                                ?>" 
                                   class="btn btn-sm btn-primary" 
                                   title="Sửa"
                                   style="text-decoration: none; display: inline-block;">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <button type="button" 
                                        class="btn btn-sm <?php echo $s['status'] ? 'btn-warning' : 'btn-success'; ?>" 
                                        onclick="toggleStaffStatus(<?php echo $s['id']; ?>, <?php echo $s['status'] ? 0 : 1; ?>)"
                                        title="<?php echo $s['status'] ? 'Khóa' : 'Mở khóa'; ?>">
                                    <i class="fas fa-<?php echo $s['status'] ? 'lock' : 'unlock'; ?>"></i>
                                </button>
                                <button type="button" 
                                        class="btn btn-sm btn-danger" 
                                        onclick="deleteStaff(<?php echo $s['id']; ?>, '<?php echo htmlspecialchars($s['username']); ?>')"
                                        title="Xóa">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <form id="bulkStaffForm" style="display: none;">
                <!-- Hidden form for bulk actions -->
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
            <h3><?php echo isset($_GET['edit']) ? 'Sửa nhân viên' : 'Thêm nhân viên'; ?></h3>
            <?php
            $editId = isset($_GET['edit']) ? (int)$_GET['edit'] : 0;
            $editStaff = null;
            if ($editId > 0) {
                $db = new Database();
                $editStaff = $db->fetchOne("SELECT * FROM users WHERE id = ? AND role = 'staff'", [$editId]);
            }
            ?>
            <form method="POST">
                <?php if ($editId > 0): ?>
                    <input type="hidden" name="id" value="<?php echo $editId; ?>">
                    <input type="hidden" name="edit" value="1">
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Username *</label>
                    <input type="text" name="username" class="form-control" 
                           value="<?php echo htmlspecialchars($editStaff['username'] ?? ''); ?>" 
                           <?php echo $editId > 0 ? 'readonly' : 'required'; ?>>
                    <?php if ($editId > 0): ?>
                        <small style="color: #666;">Username không thể thay đổi</small>
                    <?php endif; ?>
                </div>
                
                <?php if ($editId == 0): ?>
                <div class="form-group">
                    <label>Mật khẩu *</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <?php else: ?>
                <div class="form-group">
                    <label>Mật khẩu mới (để trống nếu không đổi)</label>
                    <input type="password" name="password" class="form-control">
                </div>
                <?php endif; ?>
                
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control" 
                           value="<?php echo htmlspecialchars($editStaff['email'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Điện thoại</label>
                    <input type="text" name="phone" class="form-control" 
                           value="<?php echo htmlspecialchars($editStaff['phone'] ?? ''); ?>">
                </div>
                
                <div class="form-group">
                    <label>Trạng thái</label>
                    <label style="display: flex; align-items: center; gap: 10px; cursor: pointer;">
                        <input type="checkbox" name="status" value="1" 
                               <?php echo ($editStaff['status'] ?? 1) ? 'checked' : ''; ?>>
                        <span>Kích hoạt (bỏ chọn để khóa tài khoản)</span>
                    </label>
                    <?php if ($editId > 0 && $editStaff && $editStaff['id'] == $_SESSION['user_id']): ?>
                        <small style="color: #e74c3c; display: block; margin-top: 5px;">
                            <i class="fas fa-exclamation-triangle"></i> Bạn không thể khóa chính mình
                        </small>
                    <?php endif; ?>
                </div>
                
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> <?php echo $editId > 0 ? 'Cập nhật' : 'Thêm mới'; ?>
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
    // Select all checkbox
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function() {
            document.querySelectorAll('table tbody input[name="ids[]"]').forEach(cb => cb.checked = this.checked);
        });
    }
    
    document.getElementById('bulkActivateBtn').addEventListener('click', function(e) {
        e.preventDefault();
        const ids = Array.from(document.querySelectorAll('table tbody input[name="ids[]"]:checked')).map(cb => cb.value);
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
        const ids = Array.from(document.querySelectorAll('table tbody input[name="ids[]"]:checked')).map(cb => cb.value);
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
        const ids = Array.from(document.querySelectorAll('table tbody input[name="ids[]"]:checked')).map(cb => cb.value);
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
        const rows = document.querySelectorAll('table tbody tr');
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
    
    // Toggle status nhân viên
    function toggleStaffStatus(id, newStatus) {
        const action = newStatus ? 'mở khóa' : 'khóa';
        if (confirm('Bạn có chắc chắn muốn ' + action + ' nhân viên này?')) {
            window.location.href = '?toggle_status=' + newStatus + '&id=' + id;
        }
    }
    
    // Xóa nhân viên
    function deleteStaff(id, username) {
        if (confirm('Bạn có chắc chắn muốn xóa nhân viên "' + username + '"?\n\nHành động này không thể hoàn tác!')) {
            window.location.href = '?delete=' + id + '&confirm=yes';
        }
    }
</script>
