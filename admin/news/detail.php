<?php
// admin/news/detail.php - Chi tiết tin tức

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/News.php';

requireStaff();

$newsModel = new News();

// Lấy ID tin tức
$newsId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$newsId) {
    setFlashMessage('error', 'Không tìm thấy tin tức');
    redirect('index.php');
}

// Lấy thông tin tin tức
$news = $newsModel->getNewsById($newsId);

if (!$news) {
    setFlashMessage('error', 'Tin tức không tồn tại');
    redirect('index.php');
}

$pageTitle = 'Chi tiết tin tức - Admin';
$activeMenu = 'news';
include __DIR__ . '/../layout.php';
?>

<div class="page-header d-flex justify-between align-center">
    <h1><i class="fas fa-newspaper"></i> Chi tiết tin tức</h1>
    <div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
        <a href="edit.php?id=<?php echo $news['id']; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Sửa tin tức
        </a>
    </div>
</div>

<?php if ($flash = getFlashMessage()): ?>
    <div class="alert alert-<?php echo $flash['type']; ?>">
        <?php echo htmlspecialchars($flash['message']); ?>
    </div>
<?php endif; ?>

<div class="news-detail-container" style="max-width: 900px; margin: 20px auto;">
    <!-- Thông tin cơ bản -->
    <div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px;">
        <h2 style="margin-top: 0; border-bottom: 2px solid #3498db; padding-bottom: 10px;">
            <i class="fas fa-info-circle"></i> Thông tin cơ bản
        </h2>
        
        <table class="detail-table" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px; font-weight: bold; width: 30%;">ID:</td>
                <td style="padding: 10px;">#<?php echo $news['id']; ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Tiêu đề:</td>
                <td style="padding: 10px;"><strong><?php echo htmlspecialchars($news['title']); ?></strong></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Slug:</td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($news['slug']); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Tác giả:</td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($news['author_name'] ?? 'N/A'); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Lượt xem:</td>
                <td style="padding: 10px;">
                    <span style="color: #3498db; font-weight: bold;">
                        <i class="fas fa-eye"></i> <?php echo number_format($news['views']); ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Trạng thái:</td>
                <td style="padding: 10px;">
                    <?php if ($news['status']): ?>
                        <span class="badge badge-success">Hiển thị</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Ẩn</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Ngày tạo:</td>
                <td style="padding: 10px;"><?php echo formatDate($news['created_at'], 'd/m/Y H:i:s'); ?></td>
            </tr>
            <?php if ($news['updated_at'] && $news['updated_at'] !== $news['created_at']): ?>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Cập nhật lần cuối:</td>
                <td style="padding: 10px;"><?php echo formatDate($news['updated_at'], 'd/m/Y H:i:s'); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Hình ảnh -->
    <?php if ($news['image']): ?>
    <div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-bottom: 20px; text-align: center;">
        <img src="<?php echo getNewsImage($news['image']); ?>" 
             alt="<?php echo htmlspecialchars($news['title']); ?>"
             style="max-width: 100%; max-height: 500px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
    </div>
    <?php endif; ?>

    <!-- Nội dung -->
    <div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; border-bottom: 2px solid #27ae60; padding-bottom: 10px;">
            <i class="fas fa-file-alt"></i> Nội dung
        </h2>
        
        <div style="padding: 20px; background: #f8f9fa; border-radius: 6px; line-height: 1.8;">
            <?php echo nl2br(htmlspecialchars($news['content'])); ?>
        </div>
    </div>
</div>

</main>
</div>
</body>
</html>

