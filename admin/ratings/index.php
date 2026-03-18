<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Rating.php';

requireStaff();

$ratingModel = new Rating();

// Xử lý các hành động - chỉ xóa, không cần duyệt

if (isset($_GET['delete'])) {
    $ratingModel->deleteRating($_GET['delete']);
    setFlashMessage('success', 'Xóa đánh giá thành công');
    redirect('index.php');
}

// Lọc và tìm kiếm
$filters = [];
$filters['keyword'] = $_GET['q'] ?? '';
$filters['product_id'] = $_GET['product_id'] ?? '';
// Chỉ hiển thị đánh giá đã được hiển thị (status = 1)
$filters['status'] = '1';

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$total = $ratingModel->countAllRatings($filters);
$ratings = $ratingModel->getAllRatings($filters, $perPage, $offset);

$pageTitle = 'Quản lý đánh giá - Admin';
$activeMenu = 'ratings';
include __DIR__ . '/../layout.php';
?>
    <div class="section-header d-flex justify-between align-center" style="margin-bottom: 20px;">
        <h1 class="section-title"><i class="fas fa-star"></i> Quản lý đánh giá</h1>
        <button id="exportRatingsBtn" class="btn btn-success">Xuất CSV</button>
    </div>
    
    <div class="admin-toolbar d-flex justify-between mb-20">
        <form method="GET" style="display:flex; gap:10px; align-items:center;">
            <input type="text" name="q" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Tìm username, sản phẩm..." class="form-control">
            <button class="btn btn-primary">Tìm kiếm</button>
        </form>
    </div>
    
    <?php if ($flash = getFlashMessage()): ?>
        <div class="alert alert-<?php echo $flash['type']; ?>">
            <?php echo htmlspecialchars($flash['message']); ?>
        </div>
    <?php endif; ?>
    
    <div class="data-table">
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Sản phẩm</th>
                    <th>Người đánh giá</th>
                    <th>Sao</th>
                    <th>Nội dung</th>
                    <th>Ngày đánh giá</th>
                    <th>Thao tác</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($ratings as $rating): ?>
                <tr>
                    <td><?php echo $rating['id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($rating['product_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($rating['full_name'] ?: $rating['username']); ?></td>
                    <td>
                        <div style="color: var(--admin-warning);">
                            <?php for ($i = 1; $i <= 5; $i++): ?>
                                <i class="fas fa-star" style="opacity: <?php echo $i <= $rating['rating'] ? '1' : '0.3'; ?>"></i>
                            <?php endfor; ?>
                        </div>
                    </td>
                    <td><?php echo htmlspecialchars(substr($rating['content'], 0, 50)); ?><?php echo strlen($rating['content']) > 50 ? '...' : ''; ?></td>
                    <td><?php echo formatDate($rating['created_at'], 'd/m/Y H:i'); ?></td>
                    <td>
                        <a href="?delete=<?php echo $rating['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Bạn chắc chắn muốn xóa đánh giá này?')">
                            <i class="fas fa-trash"></i> Xóa
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
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
</body>
</html>

<script>
    var exportRatingsBtn = document.getElementById('exportRatingsBtn');
    if (exportRatingsBtn) {
        exportRatingsBtn.addEventListener('click', function() {
        const rows = document.querySelectorAll('table tbody tr');
        let csv = '"ID","Sản phẩm","Người đánh giá","Sao","Nội dung","Ngày đánh giá"\n';
        rows.forEach(row => {
            const cells = row.querySelectorAll('td');
            // Bỏ cột cuối cùng (Thao tác), chỉ lấy 6 cột đầu
            const values = [cells[0], cells[1], cells[2], cells[3], cells[4], cells[5]].map(c => '"' + (c.textContent || '').trim().replace(/"/g, '""') + '"');
            csv += values.join(',') + '\n';
        });
        const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
        const link = document.createElement('a');
        link.href = URL.createObjectURL(blob);
        link.download = 'ratings-' + new Date().toISOString().split('T')[0] + '.csv';
        link.click();
        });
    }
</script>
