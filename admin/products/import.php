<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../models/Supplier.php';

requireStaff();

$productModel = new Product();
$supplierModel = new Supplier();

$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$productId) {
    setFlashMessage('error', 'Không tìm thấy sản phẩm');
    redirect('index.php');
}

$product = $productModel->getProductById($productId);

if (!$product) {
    setFlashMessage('error', 'Sản phẩm không tồn tại');
    redirect('index.php');
}

$suppliers = $supplierModel->getAllSuppliers();
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $supplier_id = (int)($_POST['supplier_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 0);
    $import_price = (float)($_POST['import_price'] ?? 0);
    $note = trim($_POST['note'] ?? '');
    
    // Validate
    if ($supplier_id <= 0) {
        $errors[] = 'Vui lòng chọn nhà cung cấp';
    }
    if ($quantity <= 0) {
        $errors[] = 'Số lượng nhập phải lớn hơn 0';
    }
    if ($import_price <= 0) {
        $errors[] = 'Giá nhập phải lớn hơn 0';
    }
    
    if (empty($errors)) {
        $data = [
            'product_id' => $productId,
            'supplier_id' => $supplier_id,
            'quantity' => $quantity,
            'import_price' => $import_price,
            'note' => $note,
            'created_by' => $_SESSION['user_id']
        ];
        
        $result = $productModel->importProduct($data);
        
        if ($result['success']) {
            setFlashMessage('success', 'Nhập hàng thành công');
            redirect('detail.php?id=' . $productId);
        } else {
            $errors[] = $result['message'] ?? 'Nhập hàng thất bại';
        }
    }
}

$pageTitle = 'Nhập hàng - ' . htmlspecialchars($product['name']);
$activeMenu = 'products';
include __DIR__ . '/../layout.php';
?>

<div class="page-header d-flex justify-between align-center">
    <h1><i class="fas fa-download"></i> Nhập hàng</h1>
    <a href="detail.php?id=<?php echo $productId; ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left"></i> Quay lại
    </a>
</div>

<?php if ($flash = getFlashMessage()): ?>
    <div class="alert alert-<?php echo $flash['type']; ?>">
        <?php echo htmlspecialchars($flash['message']); ?>
    </div>
<?php endif; ?>

<?php if (!empty($errors)): ?>
    <div class="alert alert-error">
        <ul>
            <?php foreach ($errors as $error): ?>
                <li><?php echo htmlspecialchars($error); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php endif; ?>

<div style="background: #fff; padding: 30px; border-radius: 10px; margin-top: 20px;">
    <!-- Thông tin sản phẩm -->
    <div style="background: #f8f9fa; padding: 20px; border-radius: 8px; margin-bottom: 30px;">
        <h3 style="margin-top: 0;"><i class="fas fa-box"></i> Thông tin sản phẩm</h3>
        <div style="display: grid; grid-template-columns: auto 1fr; gap: 15px; align-items: center;">
            <div>
                <img src="<?php echo getProductImage($product['image']); ?>" 
                     alt="<?php echo htmlspecialchars($product['name']); ?>"
                     style="width: 100px; height: 100px; object-fit: cover; border-radius: 8px;">
            </div>
            <div>
                <h4 style="margin: 0 0 10px 0;"><?php echo htmlspecialchars($product['name']); ?></h4>
                <p style="margin: 5px 0; color: #666;">
                    <strong>Mã SP:</strong> #<?php echo $product['id']; ?>
                </p>
                <p style="margin: 5px 0; color: #666;">
                    <strong>Số lượng hiện tại:</strong> 
                    <span style="color: <?php echo $product['quantity'] > 0 ? '#27ae60' : '#e74c3c'; ?>; font-weight: bold;">
                        <?php echo $product['quantity']; ?>
                    </span>
                </p>
            </div>
        </div>
    </div>
    
    <!-- Form nhập hàng -->
    <form method="POST">
        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
            <div class="form-group">
                <label>Nhà cung cấp *</label>
                <select name="supplier_id" class="form-control" required>
                    <option value="">-- Chọn nhà cung cấp --</option>
                    <?php foreach ($suppliers as $sup): ?>
                        <option value="<?php echo $sup['id']; ?>" <?php echo $product['supplier_id'] == $sup['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($sup['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label>Số lượng nhập *</label>
                <input type="number" name="quantity" class="form-control" min="1" step="1" required placeholder="Nhập số lượng">
            </div>
        </div>
        
        <div class="form-group">
            <label>Giá nhập (VNĐ) *</label>
            <input type="number" name="import_price" class="form-control" min="0" step="1000" required placeholder="Nhập giá nhập">
        </div>
        
        <div class="form-group">
            <label>Ghi chú</label>
            <textarea name="note" class="form-control" rows="3" placeholder="Ghi chú về lần nhập hàng này..."></textarea>
        </div>
        
        <div style="display: flex; gap: 10px; margin-top: 20px;">
            <button type="submit" class="btn btn-success">
                <i class="fas fa-save"></i> Xác nhận nhập hàng
            </button>
            <a href="detail.php?id=<?php echo $productId; ?>" class="btn btn-secondary">
                <i class="fas fa-times"></i> Hủy
            </a>
        </div>
    </form>
</div>

<!-- Lịch sử nhập hàng -->
<?php
$importHistory = $productModel->getImportHistory($productId);
if (!empty($importHistory)):
?>
<div style="background: #fff; padding: 30px; border-radius: 10px; margin-top: 20px;">
    <h3><i class="fas fa-history"></i> Lịch sử nhập hàng</h3>
    <div class="data-table">
        <table style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8f9fa;">
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Ngày nhập</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Nhà cung cấp</th>
                    <th style="padding: 12px; text-align: center; border-bottom: 2px solid #dee2e6;">Số lượng</th>
                    <th style="padding: 12px; text-align: right; border-bottom: 2px solid #dee2e6;">Giá nhập</th>
                    <th style="padding: 12px; text-align: right; border-bottom: 2px solid #dee2e6;">Thành tiền</th>
                    <th style="padding: 12px; text-align: left; border-bottom: 2px solid #dee2e6;">Ghi chú</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($importHistory as $import): ?>
                <tr style="border-bottom: 1px solid #dee2e6;">
                    <td style="padding: 12px;"><?php echo formatDate($import['created_at'], 'd/m/Y H:i'); ?></td>
                    <td style="padding: 12px;"><?php echo htmlspecialchars($import['supplier_name'] ?? 'N/A'); ?></td>
                    <td style="padding: 12px; text-align: center;"><?php echo number_format($import['quantity']); ?></td>
                    <td style="padding: 12px; text-align: right;"><?php echo formatCurrency($import['import_price']); ?></td>
                    <td style="padding: 12px; text-align: right; font-weight: bold; color: #27ae60;">
                        <?php echo formatCurrency($import['total_price']); ?>
                    </td>
                    <td style="padding: 12px;"><?php echo htmlspecialchars($import['note'] ?? ''); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

</main>
</div>
</body>
</html>

