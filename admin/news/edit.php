<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/News.php';

requireStaff();

$newsModel = new News();
$pageTitle = 'Sửa tin tức - Admin';
$activeMenu = 'news';

if (!isset($_GET['id'])) {
    redirect('index.php');
}

$news = $newsModel->getNewsById($_GET['id']);
if (!$news) {
    setFlashMessage('error', 'Tin tức không tồn tại');
    redirect('index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => sanitizeInput($_POST['title']),
        'content' => $_POST['content'],
        'status' => isset($_POST['status']) ? 1 : 0
    ];
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload = uploadFile($_FILES['image'], 'news/');
        if ($upload['success']) {
            $data['image'] = $upload['filename'];
        }
    }
    
    $newsModel->updateNews($_GET['id'], $data);
    setFlashMessage('success', 'Cập nhật tin tức thành công');
    redirect('index.php');
}

include __DIR__ . '/../layout.php';
?>
            <div class="page-header">
                <h1><i class="fas fa-newspaper"></i> Sửa tin tức</h1>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
            </div>

            <div class="card" style="max-width: 800px;">
                <div class="card-header">
                    <h3>Sửa tin tức: <?php echo htmlspecialchars($news['title']); ?></h3>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Tiêu đề *</label>
                            <input type="text" name="title" class="form-control" required 
                                   value="<?php echo htmlspecialchars($news['title']); ?>">
                        </div>

                        <div class="form-group">
                            <label>Hình ảnh</label>
                            <?php if ($news['image']): ?>
                                <div style="margin-bottom: 10px;">
                                    <img src="<?php echo getNewsImage($news['image']); ?>" 
                                         style="max-width: 200px; height: auto; border-radius: 5px;">
                                </div>
                            <?php endif; ?>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">Định dạng: JPG, PNG, GIF (tối đa 5MB)</small>
                        </div>

                        <div class="form-group">
                            <label>Nội dung *</label>
                            <textarea name="content" class="form-control" rows="10" required><?php echo htmlspecialchars($news['content']); ?></textarea>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="status" <?php echo $news['status'] ? 'checked' : ''; ?>>
                                Hiển thị
                            </label>
                        </div>

                        <div class="d-flex gap-10 mt-20">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Cập nhật
                            </button>
                            <a href="index.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Hủy
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
