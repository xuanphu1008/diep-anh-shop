<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Banner.php';

requireStaff();

$bannerModel = new Banner();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['edit'])) {
    $data = [
        'title' => sanitizeInput($_POST['title']),
        'link' => sanitizeInput($_POST['link'] ?? ''),
        'status' => isset($_POST['status']) ? 1 : 0
    ];
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload = uploadFile($_FILES['image'], 'banners/');
        if ($upload['success']) $data['image'] = $upload['filename'];
    }
    if (isset($_POST['id']) && $_POST['id'] > 0) {
        $bannerModel->updateBanner($_POST['id'], $data);
        setFlashMessage('success', 'Cập nhật banner thành công');
    } else {
        $bannerModel->addBanner($data);
        setFlashMessage('success', 'Thêm banner thành công');
    }
    redirect('index.php');
}

if (isset($_GET['delete'])) {
    $bannerModel->deleteBanner($_GET['delete']);
    setFlashMessage('success', 'Xóa banner thành công');
    redirect('index.php');
}

$filters = [];
$filters['keyword'] = $_GET['q'] ?? '';
$filters['status'] = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

if (method_exists($bannerModel, 'getAdminBanners')) {
    $total = $bannerModel->countAdminBanners($filters);
    $banners = $bannerModel->getAdminBanners($filters, $perPage, $offset);
} else {
    $allBanners = $bannerModel->getAllBanners();
    $filtered = array_filter($allBanners, function($b) use ($filters) {
        $ok = true;
        if ($filters['keyword']) {
            $kw = mb_strtolower($filters['keyword']);
            $ok = $ok && mb_strpos(mb_strtolower($b['title']), $kw) !== false;
        }
        if ($filters['status'] !== '') $ok = $ok && ((string)$b['status'] === $filters['status']);
        return $ok;
    });
    $total = count($filtered);
    $banners = array_slice(array_values($filtered), $offset, $perPage);
}

$editBanner = null;
if (isset($_GET['edit'])) $editBanner = $bannerModel->getBannerById($_GET['edit']);

$pageTitle = 'Quản lý banner - Admin';
$activeMenu = 'banners';
include __DIR__ . '/../layout.php';
?>
    <div class="section-header d-flex justify-between align-center" style="margin-bottom: 20px;">
        <h1 class="section-title"><i class="fas fa-image"></i> Quản lý banner</h1>
        <button id="exportBannersBtn" class="btn btn-success">Xuất CSV</button>
    </div>
    <div class="admin-toolbar d-flex justify-between mb-20">
        <form method="GET" style="display:flex; gap:10px; align-items:center;">
            <input type="text" name="q" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Tìm tiêu đề..." class="form-control">
            <select name="status" class="form-control">
                <option value="">Tất cả</option>
                <option value="1" <?php echo $filters['status'] === '1' ? 'selected' : ''; ?>>Hiển thị</option>
                <option value="0" <?php echo $filters['status'] === '0' ? 'selected' : ''; ?>>Ẩn</option>
            </select>
            <button class="btn btn-primary">Lọc</button>
        </form>
        <div class="d-flex gap-10">
            <button id="bulkShowBtn" class="btn btn-success">Hiển thị</button>
            <button id="bulkHideBtn" class="btn btn-warning">Ẩn</button>
            <button id="bulkDeleteBtn" class="btn btn-danger">Xóa</button>
        </div>
    </div>
    <?php if ($flash = getFlashMessage()): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">{{ $flash['message'] }}</div>
    <?php endif; ?>
    <div class="content-grid">
        <div class="data-table">
            <form id="bulkBannersForm">
            <table>
                <thead>
                    <tr>
                        <th style="width:40px;"><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>Tiêu đề</th>
                        <th>Hình ảnh</th>
                        <th>Liên kết</th>
                        <th>Trạng thái</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($banners as $b): ?>
                    <tr data-id="<?php echo $b['id']; ?>">
                        <td><input type="checkbox" name="ids[]" value="<?php echo $b['id']; ?>"></td>
                        <td><?php echo $b['id']; ?></td>
                        <td><strong><?php echo htmlspecialchars($b['title']); ?></strong></td>
                        <td><img src="<?php echo $b['image'] ? 'uploads/banners/' . $b['image'] : 'assets/images/placeholder.jpg'; ?>" style="width:80px;height:60px;object-fit:cover;border-radius:6px;"></td>
                        <td><?php echo htmlspecialchars($b['link']); ?></td>
                        <td><?php echo $b['status'] ? '<span class="badge badge-success">Hiển thị</span>' : '<span class="badge badge-secondary">Ẩn</span>'; ?></td>
                        <td>
                            <a href="?edit=<?php echo $b['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                            <a href="?delete=<?php echo $b['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa banner?')"><i class="fas fa-trash"></i></a>
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
            <h3>Thêm/Sửa banner</h3>
            <form method="POST" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Tiêu đề *</label>
                    <input type="text" name="title" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Hình ảnh</label>
                    <input type="file" name="image" class="form-control">
                </div>
                <div class="form-group">
                    <label>Liên kết</label>
                    <input type="text" name="link" class="form-control">
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Lưu
                    </button>
                    <a href="index.php" class="btn btn-secondary">Hủy</a>
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
        document.querySelectorAll('#bulkBannersForm input[name="ids[]"]').forEach(cb => cb.checked = this.checked);
    });
    
    document.getElementById('bulkShowBtn').addEventListener('click', function(e) {
        e.preventDefault();
        const ids = Array.from(document.querySelectorAll('#bulkBannersForm input[name="ids[]"]:checked')).map(cb => cb.value);
        if (ids.length === 0) return alert('Chọn ít nhất 1 banner');
        fetch('bulk-handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'bulk_show', ids})
        }).then(r => r.json()).then(d => {
            if (d.success) location.reload();
            else alert(d.message);
        });
    });
    
    document.getElementById('bulkHideBtn').addEventListener('click', function(e) {
        e.preventDefault();
        const ids = Array.from(document.querySelectorAll('#bulkBannersForm input[name="ids[]"]:checked')).map(cb => cb.value);
        if (ids.length === 0) return alert('Chọn ít nhất 1 banner');
        fetch('bulk-handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'bulk_hide', ids})
        }).then(r => r.json()).then(d => {
            if (d.success) location.reload();
            else alert(d.message);
        });
    });
    
    document.getElementById('bulkDeleteBtn').addEventListener('click', function(e) {
        e.preventDefault();
        const ids = Array.from(document.querySelectorAll('#bulkBannersForm input[name="ids[]"]:checked')).map(cb => cb.value);
        if (ids.length === 0) return alert('Chọn ít nhất 1 banner');
        if (!confirm('Xóa ' + ids.length + ' banner?')) return;
        fetch('bulk-handler.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/json'},
            body: JSON.stringify({action: 'bulk_delete', ids})
        }).then(r => r.json()).then(d => {
            if (d.success) location.reload();
            else alert(d.message);
        });
    });
    
    document.getElementById('exportBannersBtn').addEventListener('click', function() {
        const rows = document.querySelectorAll('#bulkBannersForm table tbody tr');
        let csv = '"ID","Tiêu đề","Liên kết","Trạng thái"\n';
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            const values = [cells[1], cells[2], cells[4], cells[5]].map(c => '"' + (c.textContent || '').trim().replace(/"/g, '""') + '"');
            csv += values.join(',') + '\n';
        });
        const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'banners-' + new Date().toISOString().split('T')[0] + '.csv';
        link.click();
    });
</script>
