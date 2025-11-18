<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/News.php';

requireStaff();

$newsModel = new News();
$pageTitle = 'Thêm tin tức - Admin';
$activeMenu = 'news';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'title' => sanitizeInput($_POST['title']),
        'content' => $_POST['content'],
        'author_id' => $_SESSION['user_id'],
        'status' => isset($_POST['status']) ? 1 : 0
    ];
    
    if (isset($_FILES['image']) && $_FILES['image']['error'] === 0) {
        $upload = uploadFile($_FILES['image'], 'news/');
        if ($upload['success']) {
            $data['image'] = $upload['filename'];
        }
    }
    
    $newsModel->addNews($data);
    setFlashMessage('success', 'Thêm tin tức thành công');
    redirect('index.php');
}

include __DIR__ . '/../layout.php';
?>
            <div class="page-header">
                <h1><i class="fas fa-newspaper"></i> Thêm tin tức mới</h1>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
            </div>

            <div class="card" style="max-width: 800px;">
                <div class="card-header">
                    <h3>Thêm tin tức mới</h3>
                </div>
                <div class="card-body">
                    <form method="POST" enctype="multipart/form-data">
                        <div class="form-group">
                            <label>Tiêu đề *</label>
                            <input type="text" name="title" class="form-control" required placeholder="Nhập tiêu đề tin tức">
                        </div>

                        <div class="form-group">
                            <label>Hình ảnh</label>
                            <input type="file" name="image" class="form-control" accept="image/*">
                            <small class="text-muted">Định dạng: JPG, PNG, GIF (tối đa 5MB)</small>
                        </div>

                        <div class="form-group">
                            <label>Nội dung *</label>
                            <textarea name="content" class="form-control" rows="10" required placeholder="Nhập nội dung tin tức"></textarea>
                        </div>

                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="status" checked>
                                Hiển thị
                            </label>
                        </div>

                        <div class="d-flex gap-10 mt-20">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> Thêm tin tức
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
