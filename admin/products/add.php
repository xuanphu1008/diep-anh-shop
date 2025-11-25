<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../models/Category.php';
require_once __DIR__ . '/../../models/Suppiler.php';

requireStaff();

$productModel = new Product();
$categoryModel = new Category();
$supplierModel = new Suppiler();

$categories = $categoryModel->getAllCategories();
$suppliers = $supplierModel->getAllSuppliers();
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // ...existing code...
}
$pageTitle = 'Thêm sản phẩm - Admin';
$activeMenu = 'products';
include __DIR__ . '/../layout.php';
?>
            <h1><i class="fas fa-plus"></i> Thêm sản phẩm mới</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul><?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?></ul>
                </div>
            <?php endif; ?>
            
            <div style="background: #fff; padding: 30px; border-radius: 10px;">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Tên sản phẩm *</label>
                        <input type="text" name="name" class="form-control" required>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Danh mục *</label>
                            <select name="category_id" class="form-control" required>
                                <option value="">-- Chọn danh mục --</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label>Nhà cung cấp *</label>
                            <select name="supplier_id" class="form-control" required>
                                <option value="">-- Chọn nhà cung cấp --</option>
                                <?php foreach ($suppliers as $sup): ?>
                                <option value="<?php echo $sup['id']; ?>"><?php echo htmlspecialchars($sup['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Giá gốc *</label>
                            <input type="number" name="price" class="form-control" min="0" step="1000" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Giá giảm</label>
                            <input type="number" name="discount_price" class="form-control" min="0" step="1000">
                        </div>
                        
                        <div class="form-group">
                            <label>Số lượng *</label>
                            <input type="number" name="quantity" class="form-control" min="0" value="0" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea name="description" class="form-control" rows="5"></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Thông số kỹ thuật</label>
                        <div id="specs-container">
                            <div style="display: grid; grid-template-columns: 1fr 1fr 50px; gap: 10px; margin-bottom: 10px;">
                                <input type="text" name="specs_key[]" class="form-control" placeholder="Tên thông số">
                                <input type="text" name="specs_value[]" class="form-control" placeholder="Giá trị">
                                <button type="button" class="btn btn-primary" onclick="addSpec()"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Hình ảnh *</label>
                        <input type="file" name="image" class="form-control" accept="image/*" required>
                    </div>
                    
                    <div style="display: flex; gap: 20px; margin: 20px 0;">
                        <label><input type="checkbox" name="is_hot"> Sản phẩm HOT</label>
                        <label><input type="checkbox" name="is_active" checked> Hiển thị</label>
                    </div>
                    
                    <div style="display: flex; gap: 10px;">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Lưu sản phẩm</button>
                        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <style>
        .product-image-preview {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 8px;
            border: 1px solid #eee;
            margin-bottom: 10px;
            display: none;
        }
    </style>
    <script>
        function addSpec() {
            const container = document.getElementById('specs-container');
            const div = document.createElement('div');
            div.style.cssText = 'display: grid; grid-template-columns: 1fr 1fr 50px; gap: 10px; margin-bottom: 10px;';
            div.innerHTML = `
                <input type="text" name="specs_key[]" class="form-control" placeholder="Tên thông số">
                <input type="text" name="specs_value[]" class="form-control" placeholder="Giá trị">
                <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
            `;
            container.appendChild(div);
        }

        // Hiển thị ảnh preview khi chọn file
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.querySelector('input[name="image"]');
            const preview = document.createElement('img');
            preview.className = 'product-image-preview';
            input.parentNode.insertBefore(preview, input);
            input.addEventListener('change', function(e) {
                if (this.files && this.files[0]) {
                    const reader = new FileReader();
                    reader.onload = function(ev) {
                        preview.src = ev.target.result;
                        preview.style.display = 'block';
                    };
                    reader.readAsDataURL(this.files[0]);
                } else {
                    preview.style.display = 'none';
                }
            });
        });
    </script>
</body>
</html>