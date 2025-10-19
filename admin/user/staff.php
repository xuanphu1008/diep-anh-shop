<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
requireStaff();

$pageTitle = 'Quản lý nhân viên - Admin';
$activeMenu = 'staff';
include __DIR__ . '/../layout.php';
?>
    <h1><i class="fas fa-user-tie"></i> Quản lý nhân viên</h1>
    <div class="content-grid">
        <div class="data-table">
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Tên nhân viên</th>
                        <th>Email</th>
                        <th>Điện thoại</th>
                        <th>Vai trò</th>
                        <th>Thao tác</th>
                    </tr>
                </thead>
                <tbody>
                    <!-- Dữ liệu nhân viên sẽ được hiển thị ở đây -->
                </tbody>
            </table>
        </div>
        <div style="background: #fff; padding: 30px; border-radius: 10px; height: fit-content;">
            <h3>Thêm/Sửa nhân viên</h3>
            <form method="POST">
                <div class="form-group">
                    <label>Tên nhân viên *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" name="email" class="form-control">
                </div>
                <div class="form-group">
                    <label>Điện thoại</label>
                    <input type="text" name="phone" class="form-control">
                </div>
                <div class="form-group">
                    <label>Vai trò</label>
                    <select name="role" class="form-control">
                        <option value="admin">Admin</option>
                        <option value="staff">Nhân viên</option>
                    </select>
                </div>
                <div style="display: flex; gap: 10px;">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> Lưu
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
