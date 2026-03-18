<?php
// admin/suppliers/detail.php - Chi tiết nhà cung cấp

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Supplier.php';

requireStaff();

$supplierModel = new Supplier();

// Lấy ID nhà cung cấp
$supplierId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$supplierId) {
    setFlashMessage('error', 'Không tìm thấy nhà cung cấp');
    redirect('index.php');
}

// Lấy thông tin nhà cung cấp
$supplier = $supplierModel->getSupplierById($supplierId);

if (!$supplier) {
    setFlashMessage('error', 'Nhà cung cấp không tồn tại');
    redirect('index.php');
}

// Đếm số sản phẩm từ nhà cung cấp này
$db = new Database();
$productCount = $db->fetchOne("SELECT COUNT(*) as total FROM products WHERE supplier_id = ? AND deleted_at IS NULL", [$supplierId])['total'] ?? 0;

$pageTitle = 'Chi tiết nhà cung cấp - Admin';
$activeMenu = 'suppliers';
include __DIR__ . '/../layout.php';
?>

<div class="page-header d-flex justify-between align-center">
    <h1><i class="fas fa-truck"></i> Chi tiết nhà cung cấp</h1>
    <div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
        <a href="?edit=<?php echo $supplier['id']; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Sửa nhà cung cấp
        </a>
    </div>
</div>

<?php if ($flash = getFlashMessage()): ?>
    <div class="alert alert-<?php echo $flash['type']; ?>">
        <?php echo htmlspecialchars($flash['message']); ?>
    </div>
<?php endif; ?>

<div class="supplier-detail-container" style="max-width: 800px; margin: 20px auto;">
    <div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; border-bottom: 2px solid #3498db; padding-bottom: 10px;">
            <i class="fas fa-info-circle"></i> Thông tin nhà cung cấp
        </h2>
        
        <table class="detail-table" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px; font-weight: bold; width: 30%;">ID:</td>
                <td style="padding: 10px;">#<?php echo $supplier['id']; ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Tên nhà cung cấp:</td>
                <td style="padding: 10px;"><strong><?php echo htmlspecialchars($supplier['name']); ?></strong></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Email:</td>
                <td style="padding: 10px;">
                    <?php if ($supplier['email']): ?>
                        <a href="mailto:<?php echo htmlspecialchars($supplier['email']); ?>" style="color: #3498db;">
                            <?php echo htmlspecialchars($supplier['email']); ?>
                        </a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Số điện thoại:</td>
                <td style="padding: 10px;">
                    <?php if ($supplier['phone']): ?>
                        <a href="tel:<?php echo htmlspecialchars($supplier['phone']); ?>" style="color: #3498db;">
                            <?php echo htmlspecialchars($supplier['phone']); ?>
                        </a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Địa chỉ:</td>
                <td style="padding: 10px;"><?php echo $supplier['address'] ? nl2br(htmlspecialchars($supplier['address'])) : 'N/A'; ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Trạng thái:</td>
                <td style="padding: 10px;">
                    <?php if ($supplier['status']): ?>
                        <span class="badge badge-success">Hoạt động</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Ngừng hoạt động</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Số sản phẩm:</td>
                <td style="padding: 10px;">
                    <a href="../products/index.php?supplier_id=<?php echo $supplier['id']; ?>" style="color: #3498db; font-weight: bold;">
                        <?php echo $productCount; ?> sản phẩm
                    </a>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Ngày tạo:</td>
                <td style="padding: 10px;"><?php echo formatDate($supplier['created_at'], 'd/m/Y H:i:s'); ?></td>
            </tr>
        </table>
    </div>
</div>

</main>
</div>
</body>
</html>

