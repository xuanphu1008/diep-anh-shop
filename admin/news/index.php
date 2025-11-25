<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/News.php';

requireStaff();

$newsModel = new News();

// Filters & pagination
$filters = [];
$filters['keyword'] = $_GET['q'] ?? '';
$filters['status'] = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filter news (server-side)
$allNews = $newsModel->getAllNews();
$filtered = array_filter($allNews, function($news) use ($filters) {
    $ok = true;
    if ($filters['keyword']) {
        $kw = mb_strtolower($filters['keyword']);
        $ok = $ok && (
            mb_strpos(mb_strtolower($news['title']), $kw) !== false ||
            mb_strpos(mb_strtolower($news['content']), $kw) !== false
        );
    }
    if ($filters['status'] !== '') {
        $ok = $ok && ((string)$news['status'] === $filters['status']);
    }
    return $ok;
});
$total = count($filtered);
$newsList = array_slice(array_values($filtered), $offset, $perPage);

$pageTitle = 'Quản lý tin tức - Admin';
$activeMenu = 'news';
include __DIR__ . '/../layout.php';
?>
            <div class="section-header d-flex justify-between align-center" style="margin-bottom: 20px;">
                <h1 class="section-title"><i class="fas fa-newspaper"></i> Quản lý tin tức</h1>
                <div class="d-flex gap-10">
                    <a href="add.php" class="btn btn-primary"><i class="fas fa-plus"></i> Thêm tin tức</a>
                    <button id="exportNewsBtn" class="btn btn-success">Xuất CSV</button>
                </div>
            </div>

            <div class="admin-toolbar d-flex justify-between mb-20">
                <form method="GET" style="display:flex; gap:10px; align-items:center;">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Tìm tiêu đề, nội dung..." class="form-control">
                    <select name="status" class="form-control">
                        <option value="">Tất cả</option>
                        <option value="1" <?php echo $filters['status'] === '1' ? 'selected' : ''; ?>>Hiển thị</option>
                        <option value="0" <?php echo $filters['status'] === '0' ? 'selected' : ''; ?>>Ẩn</option>
                    </select>
                    <button class="btn btn-primary">Lọc</button>
                </form>
                <div class="d-flex gap-10">
                    <button id="bulkPublishBtn" class="btn btn-success">Hiển thị</button>
                    <button id="bulkHideBtn" class="btn btn-warning">Ẩn</button>
                    <button id="bulkDeleteBtn" class="btn btn-danger">Xóa</button>
                </div>
            </div>

            <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="data-table">
                <form id="bulkNewsForm">
                <table>
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="selectAll"></th>
                            <th>ID</th>
                            <th>Hình ảnh</th>
                            <th>Tiêu đề</th>
                            <th>Tác giả</th>
                            <th>Lượt xem</th>
                            <th>Ngày tạo</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($newsList as $news): ?>
                        <tr data-id="<?php echo $news['id']; ?>">
                            <td><input type="checkbox" name="ids[]" value="<?php echo $news['id']; ?>"></td>
                            <td><?php echo $news['id']; ?></td>
                            <td><img src="<?php echo getNewsImage($news['image']); ?>" style="width:60px;height:60px;object-fit:cover;border-radius:6px;"></td>
                            <td><strong><?php echo htmlspecialchars($news['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($news['author_name']); ?></td>
                            <td><?php echo $news['views']; ?></td>
                            <td><?php echo formatDate($news['created_at'], 'd/m/Y'); ?></td>
                            <td><?php echo $news['status'] ? '<span class="badge badge-success">Hiển thị</span>' : '<span class="badge badge-secondary">Ẩn</span>'; ?></td>
                            <td>
                                <a href="edit.php?id=<?php echo $news['id']; ?>" class="btn btn-sm btn-primary"><i class="fas fa-edit"></i></a>
                                <a href="?delete=<?php echo $news['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa tin tức?')"><i class="fas fa-trash"></i></a>
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
                document.querySelectorAll('#bulkNewsForm tbody input[type=checkbox]').forEach(cb => cb.checked = this.checked);
            });
        }
        function getSelectedIds(){
            return Array.from(document.querySelectorAll('#bulkNewsForm tbody input[type=checkbox]:checked')).map(i => i.value);
        }
        function doBulkAction(action){
            const ids = getSelectedIds();
            if (ids.length === 0) { alert('Chọn ít nhất một tin tức'); return; }
            if (!confirm('Xác nhận thực hiện: ' + action + ' trên ' + ids.length + ' tin tức?')) return;
            fetch('bulk-handler.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, ids })
            }).then(r => r.json()).then(data => {
                if (data.success) window.location.reload(); else alert(data.message || 'Lỗi');
            }).catch(()=> alert('Lỗi mạng'));
        }
        document.getElementById('bulkPublishBtn')?.addEventListener('click', () => doBulkAction('bulk_publish'));
        document.getElementById('bulkHideBtn')?.addEventListener('click', () => doBulkAction('bulk_hide'));
        document.getElementById('bulkDeleteBtn')?.addEventListener('click', () => doBulkAction('bulk_delete'));
        document.getElementById('exportNewsBtn')?.addEventListener('click', function(){
            const rows = Array.from(document.querySelectorAll('.data-table tbody tr'));
            let csv = 'ID,Title,Author,Views,Created,Status\n';
            rows.forEach(r=>{
                const cols = r.querySelectorAll('td');
                if (!cols.length) return;
                const id = cols[1].innerText.trim();
                const title = '"' + cols[3].innerText.trim().replace(/"/g,'""') + '"';
                const author = cols[4].innerText.trim();
                const views = cols[5].innerText.trim();
                const created = cols[6].innerText.trim();
                const status = cols[7].innerText.trim();
                csv += [id, title, author, views, created, status].join(',') + '\n';
            });
            const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
            const link = document.createElement('a');
            link.href = URL.createObjectURL(blob);
            link.download = 'news_export.csv';
            document.body.appendChild(link);
            link.click();
            link.remove();
        });
    });
    </script>

</body>
</html>