<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Suppiler.php';

requireStaff();

$supplierModel = new Suppiler();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitizeInput($_POST['name']),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'address' => sanitizeInput($_POST['address'] ?? ''),
        'status' => isset($_POST['status']) ? 1 : 0
    ];
    
    if (isset($_POST['id']) && $_POST['id'] > 0) {
        $supplierModel->updateSupplier($_POST['id'], $data);
        setFlashMessage('success', 'Cập nhật nhà cung cấp thành công');
    } else {
        $supplierModel->addSupplier($data);
        setFlashMessage('success', 'Thêm nhà cung cấp thành công');
    }
    redirect('index.php');
}

if (isset($_GET['delete'])) {
    $supplierModel->deleteSupplier($_GET['delete']);
    setFlashMessage('success', 'Xóa nhà cung cấp thành công');
    redirect('index.php');
}

$suppliers = $supplierModel->getAllSuppliers();
$editSupplier = null;
if (isset($_GET['edit'])) {
    $editSupplier = $supplierModel->getSupplierById($_GET['edit']);
}
$pageTitle = 'Quản lý nhà cung cấp - Admin';
$activeMenu = 'suppliers';
include __DIR__ . '/../layout.php';
?>
            <h1><i class="fas fa-truck"></i> Quản lý nhà cung cấp</h1>
            
            <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="content-grid">
                <div class="data-table">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Tên nhà cung cấp</th>
                                <th>Email</th>
                                <th>Điện thoại</th>
                                <th>Địa chỉ</th>
                                <th>Thao tác</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($suppliers as $sup): ?>
                            <tr>
                                <td><?php echo $sup['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($sup['name']); ?></strong></td>
                                <td><?php echo htmlspecialchars($sup['email']); ?></td>
                                <td><?php echo htmlspecialchars($sup['phone']); ?></td>
                                <td><?php echo htmlspecialchars($sup['address']); ?></td>
                                <td>
                                    <a href="?edit=<?php echo $sup['id']; ?>" class="btn btn-sm btn-primary">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="?delete=<?php echo $sup['id']; ?>" class="btn btn-sm btn-danger"
                                       onclick="return confirm('Xóa nhà cung cấp?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <div style="background: #fff; padding: 30px; border-radius: 10px; height: fit-content;">
                    <h3><?php echo $editSupplier ? 'Sửa nhà cung cấp' : 'Thêm nhà cung cấp'; ?></h3>
                    <form method="POST">
                        <?php if ($editSupplier): ?>
                            <input type="hidden" name="id" value="<?php echo $editSupplier['id']; ?>">
                        <?php endif; ?>
                        
                        <div class="form-group">
                            <label>Tên nhà cung cấp *</label>
                            <input type="text" name="name" class="form-control" 
                                   value="<?php echo htmlspecialchars($editSupplier['name'] ?? ''); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Email</label>
                            <input type="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($editSupplier['email'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Điện thoại</label>
                            <input type="text" name="phone" class="form-control" 
                                   value="<?php echo htmlspecialchars($editSupplier['phone'] ?? ''); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Địa chỉ</label>
                            <textarea name="address" class="form-control" rows="3"><?php echo htmlspecialchars($editSupplier['address'] ?? ''); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="status" <?php echo ($editSupplier['status'] ?? 1) ? 'checked' : ''; ?>>
                                Hoạt động
                            </label>
                        </div>
                        
                        <div style="display: flex; gap: 10px;">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-save"></i> <?php echo $editSupplier ? 'Cập nhật' : 'Thêm mới'; ?>
                            </button>
                            <?php if ($editSupplier): ?>
                                <a href="index.php" class="btn btn-secondary">Hủy</a>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>
</body>
</html>