<?php
// admin/products/detail.php - Chi tiết sản phẩm

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../models/Category.php';

requireStaff();

$productModel = new Product();
$categoryModel = new Category();

// Lấy ID sản phẩm
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$productId) {
    setFlashMessage('error', 'Không tìm thấy sản phẩm');
    redirect('index.php');
}

// Lấy thông tin sản phẩm
$product = $productModel->getProductById($productId);

if (!$product) {
    setFlashMessage('error', 'Sản phẩm không tồn tại');
    redirect('index.php');
}

$pageTitle = 'Chi tiết sản phẩm - Admin';
$activeMenu = 'products';
include __DIR__ . '/../layout.php';
?>

<div class="page-header d-flex justify-between align-center">
    <h1><i class="fas fa-box"></i> Chi tiết sản phẩm</h1>
    <div>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Quay lại
        </a>
        <a href="edit.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
            <i class="fas fa-edit"></i> Sửa sản phẩm
        </a>
    </div>
</div>

<?php if ($flash = getFlashMessage()): ?>
    <div class="alert alert-<?php echo $flash['type']; ?>">
        <?php echo htmlspecialchars($flash['message']); ?>
    </div>
<?php endif; ?>

<div class="product-detail-container" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
    <!-- Thông tin cơ bản -->
    <div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; border-bottom: 2px solid #3498db; padding-bottom: 10px;">
            <i class="fas fa-info-circle"></i> Thông tin cơ bản
        </h2>
        
        <table class="detail-table" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px; font-weight: bold; width: 40%;">ID:</td>
                <td style="padding: 10px;">#<?php echo $product['id']; ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Tên sản phẩm:</td>
                <td style="padding: 10px;"><strong><?php echo htmlspecialchars($product['name']); ?></strong></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Slug:</td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($product['slug']); ?></td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Danh mục:</td>
                <td style="padding: 10px;">
                    <?php if ($product['category_id']): ?>
                        <a href="../categories/index.php?edit=<?php echo $product['category_id']; ?>" style="color: #3498db;">
                            <?php echo htmlspecialchars($product['category_name'] ?? 'N/A'); ?>
                        </a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Nhà cung cấp:</td>
                <td style="padding: 10px;">
                    <?php if ($product['supplier_id']): ?>
                        <a href="../suppliers/index.php?edit=<?php echo $product['supplier_id']; ?>" style="color: #3498db;">
                            <?php echo htmlspecialchars($product['supplier_name'] ?? 'N/A'); ?>
                        </a>
                    <?php else: ?>
                        N/A
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Trạng thái:</td>
                <td style="padding: 10px;">
                    <?php if ($product['is_active']): ?>
                        <span class="badge badge-success">Đang bán</span>
                    <?php else: ?>
                        <span class="badge badge-secondary">Ngừng bán</span>
                    <?php endif; ?>
                    <?php if ($product['is_hot']): ?>
                        <span class="badge badge-hot" style="margin-left: 10px;">HOT</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Ngày tạo:</td>
                <td style="padding: 10px;"><?php echo formatDate($product['created_at'], 'd/m/Y H:i:s'); ?></td>
            </tr>
            <?php if ($product['updated_at'] && $product['updated_at'] !== $product['created_at']): ?>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Cập nhật lần cuối:</td>
                <td style="padding: 10px;"><?php echo formatDate($product['updated_at'], 'd/m/Y H:i:s'); ?></td>
            </tr>
            <?php endif; ?>
        </table>
    </div>

    <!-- Hình ảnh và giá -->
    <div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
        <h2 style="margin-top: 0; border-bottom: 2px solid #e74c3c; padding-bottom: 10px;">
            <i class="fas fa-image"></i> Hình ảnh & Giá
        </h2>
        
        <div style="text-align: center; margin-bottom: 20px;">
            <img src="<?php echo getProductImage($product['image']); ?>" 
                 alt="<?php echo htmlspecialchars($product['name']); ?>"
                 style="max-width: 100%; max-height: 300px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.1);">
        </div>

        <table class="detail-table" style="width: 100%; border-collapse: collapse;">
            <tr>
                <td style="padding: 10px; font-weight: bold; width: 40%;">Giá gốc:</td>
                <td style="padding: 10px;"><strong><?php echo formatCurrency($product['price']); ?></strong></td>
            </tr>
            <?php if ($product['discount_price']): ?>
            <tr>
                <td style="padding: 10px; font-weight: bold; color: #27ae60;">Giá khuyến mãi:</td>
                <td style="padding: 10px;">
                    <strong style="color: #e74c3c; font-size: 18px;"><?php echo formatCurrency($product['discount_price']); ?></strong>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Tiết kiệm:</td>
                <td style="padding: 10px; color: #27ae60;">
                    <?php 
                    $saving = $product['price'] - $product['discount_price'];
                    $savingPercent = round(($saving / $product['price']) * 100);
                    echo formatCurrency($saving) . " ({$savingPercent}%)";
                    ?>
                </td>
            </tr>
            <?php endif; ?>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Số lượng tồn kho:</td>
                <td style="padding: 10px;">
                    <span style="color: <?php echo $product['quantity'] > 0 ? '#27ae60' : '#e74c3c'; ?>; font-weight: bold;">
                        <?php echo $product['quantity']; ?>
                    </span>
                </td>
            </tr>
            <tr>
                <td style="padding: 10px; font-weight: bold;">Đã bán:</td>
                <td style="padding: 10px;"><?php echo $product['sold_quantity'] ?? 0; ?></td>
            </tr>
        </table>
    </div>
</div>

<!-- Mô tả và thông số kỹ thuật -->
<div class="card" style="background: white; padding: 20px; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1); margin-top: 20px;">
    <h2 style="margin-top: 0; border-bottom: 2px solid #27ae60; padding-bottom: 10px;">
        <i class="fas fa-file-alt"></i> Mô tả sản phẩm
    </h2>
    
    <div style="padding: 15px; background: #f8f9fa; border-radius: 6px; line-height: 1.8;">
        <?php echo $product['description'] ? nl2br(htmlspecialchars($product['description'])) : '<em style="color: #999;">Chưa có mô tả</em>'; ?>
    </div>

    <?php if ($product['specifications']): ?>
    <h3 style="margin-top: 30px; border-bottom: 1px solid #dee2e6; padding-bottom: 10px;">
        <i class="fas fa-cog"></i> Thông số kỹ thuật
    </h3>
    <div style="padding: 15px; background: #f8f9fa; border-radius: 6px;">
        <?php
        $specs = is_string($product['specifications']) ? json_decode($product['specifications'], true) : $product['specifications'];
        if ($specs && is_array($specs)):
        ?>
        <table style="width: 100%; border-collapse: collapse;">
            <?php foreach ($specs as $key => $value): ?>
            <tr style="border-bottom: 1px solid #dee2e6;">
                <td style="padding: 10px; font-weight: bold; width: 30%;"><?php echo htmlspecialchars($key); ?>:</td>
                <td style="padding: 10px;"><?php echo htmlspecialchars($value); ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <?php else: ?>
        <pre style="white-space: pre-wrap; word-wrap: break-word;"><?php echo htmlspecialchars($product['specifications']); ?></pre>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<style>
@media (max-width: 768px) {
    .product-detail-container {
        grid-template-columns: 1fr !important;
    }
}
</style>

</main>
</div>
</body>
</html>

