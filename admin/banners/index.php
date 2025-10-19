<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
requireStaff();

$pageTitle = 'Quản lý banner - Admin';
$activeMenu = 'banners';
include __DIR__ . '/../layout.php';
?>
    <h1><i class="fas fa-image"></i> Quản lý banner</h1>
    <div class="content-grid">
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tiêu đề</th>
                        <th>Hình ảnh</th>
                        <th>Liên kết</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Dữ liệu banner sẽ được hiển thị ở đây -->
                </tbody>
            </table>
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
