<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Contact.php';

requireStaff();

$contactModel = new Contact();

if (isset($_POST['update_status'])) {
    $contactModel->updateContactStatus($_POST['contact_id'], $_POST['status']);
    setFlashMessage('success', 'Cập nhật trạng thái thành công');
    redirect('index.php');
}

if (isset($_GET['delete'])) {
    $contactModel->deleteContact($_GET['delete']);
    setFlashMessage('success', 'Xóa liên hệ thành công');
    redirect('index.php');
}

$filters = [];
$filters['keyword'] = $_GET['q'] ?? '';
$filters['status'] = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

if (method_exists($contactModel, 'getAdminContacts')) {
    $total = $contactModel->countAdminContacts($filters);
    $contacts = $contactModel->getAdminContacts($filters, $perPage, $offset);
} else {
    $allContacts = $contactModel->getAllContacts();
    $filtered = array_filter($allContacts, function($c) use ($filters) {
        $ok = true;
        if ($filters['keyword']) {
            $kw = mb_strtolower($filters['keyword']);
            $ok = $ok && (mb_strpos(mb_strtolower($c['name']), $kw) !== false || mb_strpos(mb_strtolower($c['email']), $kw) !== false);
        }
        if ($filters['status'] !== '') $ok = $ok && ($c['status'] === $filters['status']);
        return $ok;
    });
    $total = count($filtered);
    $contacts = array_slice(array_values($filtered), $offset, $perPage);
}

$pageTitle = 'Quản lý liên hệ - Admin';
$activeMenu = 'contacts';
include __DIR__ . '/../layout.php';
?>
            <div class="section-header d-flex justify-between align-center" style="margin-bottom: 20px;">
                <h1 class="section-title"><i class="fas fa-envelope"></i> Quản lý liên hệ</h1>
                <button id="exportContactsBtn" class="btn btn-success">Xuất CSV</button>
            </div>
            
            <div class="admin-toolbar d-flex justify-between mb-20">
                <form method="GET" style="display:flex; gap:10px; align-items:center;">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Tìm tên, email..." class="form-control">
                    <select name="status" class="form-control">
                        <option value="">Tất cả</option>
                        <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                        <option value="processing" <?php echo $filters['status'] === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                        <option value="resolved" <?php echo $filters['status'] === 'resolved' ? 'selected' : ''; ?>>Đã xử lý</option>
                    </select>
                    <button class="btn btn-primary">Lọc</button>
                </form>
                <div class="d-flex gap-10">
                    <button id="bulkMarkReadBtn" class="btn btn-success">Đánh dấu đã đọc</button>
                    <button id="bulkDeleteBtn" class="btn btn-danger">Xóa</button>
                </div>
            </div>
            
            <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="data-table">
                <form id="bulkContactsForm">
                <table>
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="selectAll"></th>
                            <th>ID</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>SĐT</th>
                            <th>Tiêu đề</th>
                            <th>Trạng thái</th>
                            <th>Ngày gửi</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contacts as $contact): ?>
                        <tr data-id="<?php echo $contact['id']; ?>">
                            <td><input type="checkbox" name="ids[]" value="<?php echo $contact['id']; ?>"></td>
                            <td><?php echo $contact['id']; ?></td>
                            <td><?php echo htmlspecialchars($contact['name']); ?></td>
                            <td><?php echo htmlspecialchars($contact['email']); ?></td>
                            <td><?php echo htmlspecialchars($contact['phone']); ?></td>
                            <td><?php echo htmlspecialchars($contact['subject']); ?></td>
                            <td><span class="badge badge-<?php echo $contact['status'] === 'resolved' ? 'success' : ($contact['status'] === 'processing' ? 'warning' : 'secondary'); ?>"><?php echo ['pending' => 'Chờ xử lý', 'processing' => 'Đang xử lý', 'resolved' => 'Đã xử lý'][$contact['status']] ?? $contact['status']; ?></span></td>
                            <td><?php echo formatDate($contact['created_at'], 'd/m/Y H:i'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="viewMessage(<?php echo $contact['id']; ?>, '<?php echo htmlspecialchars($contact['message'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="?delete=<?php echo $contact['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa liên hệ này?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </form>
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
        document.getElementById('selectAll').addEventListener('change', function() {
            document.querySelectorAll('#bulkContactsForm input[name="ids[]"]').forEach(cb => cb.checked = this.checked);
        });
        
        document.getElementById('bulkMarkReadBtn').addEventListener('click', function(e) {
            e.preventDefault();
            const ids = Array.from(document.querySelectorAll('#bulkContactsForm input[name="ids[]"]:checked')).map(cb => cb.value);
            if (ids.length === 0) return alert('Chọn ít nhất 1 liên hệ');
            fetch('bulk-handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'bulk_mark_read', ids})
            }).then(r => r.json()).then(d => {
                if (d.success) location.reload();
                else alert(d.message);
            });
        });
        
        document.getElementById('bulkDeleteBtn').addEventListener('click', function(e) {
            e.preventDefault();
            const ids = Array.from(document.querySelectorAll('#bulkContactsForm input[name="ids[]"]:checked')).map(cb => cb.value);
            if (ids.length === 0) return alert('Chọn ít nhất 1 liên hệ');
            if (!confirm('Xóa ' + ids.length + ' liên hệ?')) return;
            fetch('bulk-handler.php', {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({action: 'bulk_delete', ids})
            }).then(r => r.json()).then(d => {
                if (d.success) location.reload();
                else alert(d.message);
            });
        });
        
        document.getElementById('exportContactsBtn').addEventListener('click', function() {
            const rows = document.querySelectorAll('#bulkContactsForm table tbody tr');
            let csv = '"ID","Họ tên","Email","SĐT","Tiêu đề","Trạng thái","Ngày gửi"\n';
            rows.forEach(row => {
                const cells = row.querySelectorAll('td');
                const values = [cells[1], cells[2], cells[3], cells[4], cells[5], cells[6], cells[7]].map(c => '"' + (c.textContent || '').trim().replace(/"/g, '""') + '"');
                csv += values.join(',') + '\n';
            });
            const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'contacts-' + new Date().toISOString().split('T')[0] + '.csv';
            link.click();
        });
        
        function viewMessage(id, message) {
            alert('Tin nhắn #' + id + ':\n\n' + message);
        }
    </script>
</body>
</html>