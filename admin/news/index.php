<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/News.php';

requireStaff();

$newsModel = new News();

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
    
    if (isset($_POST['id']) && $_POST['id'] > 0) {
        $newsModel->updateNews($_POST['id'], $data);
        setFlashMessage('success', 'Cập nhật tin tức thành công');
    } else {
        $newsModel->addNews($data);
        setFlashMessage('success', 'Thêm tin tức thành công');
    }
    redirect('index.php');
}

if (isset($_GET['delete'])) {
    $newsModel->deleteNews($_GET['delete']);
    setFlashMessage('success', 'Xóa tin tức thành công');
    redirect('index.php');
}

$newsList = $newsModel->getAllNews();
$pageTitle = 'Quản lý tin tức - Admin';
$activeMenu = 'news';
include __DIR__ . '/../layout.php';
?>
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h1><i class="fas fa-newspaper"></i> Quản lý tin tức</h1>
                <a href="add.php" class="btn btn-primary">
                    <i class="fas fa-plus"></i> Thêm tin tức
                </a>
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
                        <tr>
                            <td><?php echo $news['id']; ?></td>
                            <td>
                                <img src="<?php echo getNewsImage($news['image']); ?>" 
                                     style="width: 80px; height: 60px; object-fit: cover; border-radius: 5px;">
                            </td>
                            <td><strong><?php echo htmlspecialchars($news['title']); ?></strong></td>
                            <td><?php echo htmlspecialchars($news['author_name']); ?></td>
                            <td><?php echo $news['views']; ?></td>
                            <td><?php echo formatDate($news['created_at'], 'd/m/Y'); ?></td>
                            <td>
                                <?php if ($news['status']): ?>
                                    <span class="badge badge-success">Hiển thị</span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Ẩn</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="edit.php?id=<?php echo $news['id']; ?>" class="btn btn-sm btn-primary">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="?delete=<?php echo $news['id']; ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('Xóa tin tức này?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
</body>
</html>