<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Product.php';
require_once __DIR__ . '/../../models/Category.php';
require_once __DIR__ . '/../../models/Supplier.php';

requireStaff();

$productModel = new Product();
$categoryModel = new Category();
$supplierModel = new Supplier();

// Lấy ID sản phẩm
$productId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$productId) {
    setFlashMessage('error', 'Không tìm thấy sản phẩm');
    redirect('index.php');
}

// Lấy thông tin sản phẩm hiện tại
$product = $productModel->getProductById($productId);

if (!$product) {
    setFlashMessage('error', 'Sản phẩm không tồn tại');
    redirect('index.php');
}

$categories = $categoryModel->getAllCategories();
$suppliers = $supplierModel->getAllSuppliers();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate input
    $name = trim($_POST['name'] ?? '');
    $category_id = (int)($_POST['category_id'] ?? 0);
    $supplier_id = (int)($_POST['supplier_id'] ?? 0);
    $price = (float)($_POST['price'] ?? 0);
    $discount_price = !empty($_POST['discount_price']) ? (float)$_POST['discount_price'] : null;
    $quantity = (int)($_POST['quantity'] ?? 0);
    $description = trim($_POST['description'] ?? '');
    $is_hot = isset($_POST['is_hot']) ? 1 : 0;
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    
    // Validate required fields
    if (empty($name)) {
        $errors[] = 'Tên sản phẩm là bắt buộc';
    }
    if ($category_id <= 0) {
        $errors[] = 'Vui lòng chọn danh mục';
    }
    if ($supplier_id <= 0) {
        $errors[] = 'Vui lòng chọn nhà cung cấp';
    }
    if ($price <= 0) {
        $errors[] = 'Giá sản phẩm phải lớn hơn 0';
    }
    if ($discount_price !== null && $discount_price >= $price) {
        $errors[] = 'Giá giảm phải nhỏ hơn giá gốc';
    }
    
    // Handle image upload - chỉ upload nếu có file mới
    $image = $product['image']; // Giữ ảnh cũ mặc định
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $uploadResult = uploadFile($_FILES['image'], 'products/');
        if ($uploadResult['success']) {
            $image = $uploadResult['filename'];
            // Có thể xóa ảnh cũ nếu muốn
        } else {
            $errors[] = $uploadResult['message'];
        }
    }
    
    // Handle specifications
    $specifications = null;
    if (isset($_POST['specs_key']) && is_array($_POST['specs_key'])) {
        $specs = [];
        foreach ($_POST['specs_key'] as $index => $key) {
            $key = trim($key);
            $value = trim($_POST['specs_value'][$index] ?? '');
            if (!empty($key) && !empty($value)) {
                $specs[$key] = $value;
            }
        }
        if (!empty($specs)) {
            $specifications = json_encode($specs, JSON_UNESCAPED_UNICODE);
        }
    }
    
    // If no errors, update product
    if (empty($errors)) {
        $data = [
            'name' => $name,
            'category_id' => $category_id,
            'supplier_id' => $supplier_id,
            'price' => $price,
            'discount_price' => $discount_price,
            'quantity' => $quantity,
            'description' => $description,
            'specifications' => $specifications,
            'image' => $image,
            'is_hot' => $is_hot,
            'is_active' => $is_active
        ];
        
        if ($productModel->updateProduct($productId, $data)) {
            setFlashMessage('success', 'Cập nhật sản phẩm thành công');
            redirect('index.php');
        } else {
            $errors[] = 'Cập nhật sản phẩm thất bại';
        }
    }
}

// Parse specifications nếu có
$specs = [];
if (!empty($product['specifications'])) {
    $specs = json_decode($product['specifications'], true) ?? [];
}

$pageTitle = 'Chỉnh sửa sản phẩm - Admin';
$activeMenu = 'products';
include __DIR__ . '/../layout.php';
?>
            <div class="page-header">
                <h1><i class="fas fa-edit"></i> Chỉnh sửa sản phẩm</h1>
                <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
            </div>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul><?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?></ul>
                </div>
            <?php endif; ?>
            
            <div style="background: #fff; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                <form method="POST" enctype="multipart/form-data">
                    <div class="form-group">
                        <label>Tên sản phẩm *</label>
                        <input type="text" name="name" class="form-control" value="<?php echo htmlspecialchars($product['name']); ?>" required>
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Danh mục *</label>
                            <select name="category_id" class="form-control" required>
                                <option value="">-- Chọn danh mục --</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['id']; ?>" <?php echo $product['category_id'] == $cat['id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
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
                    </div>
                    
                    <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px;">
                        <div class="form-group">
                            <label>Giá gốc *</label>
                            <input type="number" name="price" class="form-control" min="0" step="1000" value="<?php echo $product['price']; ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Giá giảm</label>
                            <input type="number" name="discount_price" class="form-control" min="0" step="1000" value="<?php echo $product['discount_price'] ?? ''; ?>">
                        </div>
                        
                        <div class="form-group">
                            <label>Số lượng *</label>
                            <input type="number" name="quantity" class="form-control" min="0" value="<?php echo $product['quantity']; ?>" required>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Mô tả</label>
                        <textarea name="description" class="form-control" rows="5"><?php echo htmlspecialchars($product['description'] ?? ''); ?></textarea>
                    </div>
                    
                    <div class="form-group">
                        <label>Thông số kỹ thuật</label>
                        <div id="specs-container">
                            <?php if (!empty($specs)): ?>
                                <?php foreach ($specs as $key => $value): ?>
                                <div style="display: grid; grid-template-columns: 1fr 1fr 50px; gap: 10px; margin-bottom: 10px;">
                                    <input type="text" name="specs_key[]" class="form-control" placeholder="Tên thông số" value="<?php echo htmlspecialchars($key); ?>">
                                    <input type="text" name="specs_value[]" class="form-control" placeholder="Giá trị" value="<?php echo htmlspecialchars($value); ?>">
                                    <button type="button" class="btn btn-danger" onclick="this.parentElement.remove()"><i class="fas fa-trash"></i></button>
                                </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 50px; gap: 10px; margin-bottom: 10px;">
                                <input type="text" name="specs_key[]" class="form-control" placeholder="Tên thông số">
                                <input type="text" name="specs_value[]" class="form-control" placeholder="Giá trị">
                                <button type="button" class="btn btn-primary" onclick="addSpec()"><i class="fas fa-plus"></i></button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label>Hình ảnh</label>
                        <div style="margin-bottom: 15px;">
                            <?php if ($product['image']): ?>
                            <img src="<?php echo getProductImage($product['image']); ?>" 
                                 class="product-image-preview" 
                                 style="width: 200px; height: 200px; object-fit: cover; border-radius: 8px; border: 2px solid #ddd; display: block;">
                            <small style="color: #666; display: block; margin-top: 5px;">Ảnh hiện tại</small>
                            <?php endif; ?>
                        </div>
                        <input type="file" name="image" class="form-control" accept="image/*">
                        <small style="color: #666;">Để trống nếu không muốn thay đổi ảnh</small>
                    </div>
                    
                    <div style="display: flex; gap: 20px; margin: 20px 0;">
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="is_hot" <?php echo $product['is_hot'] ? 'checked' : ''; ?>>
                            <span>Sản phẩm HOT</span>
                        </label>
                        <label style="display: flex; align-items: center; gap: 8px; cursor: pointer;">
                            <input type="checkbox" name="is_active" <?php echo $product['is_active'] ? 'checked' : ''; ?>>
                            <span>Hiển thị</span>
                        </label>
                    </div>
                    
                    <div style="display: flex; gap: 10px; margin-top: 30px;">
                        <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Cập nhật sản phẩm</button>
                        <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
                        <a href="detail.php?id=<?php echo $productId; ?>" class="btn btn-info"><i class="fas fa-eye"></i> Xem chi tiết</a>
                    </div>
                </form>
            </div>
        </main>
    </div>
    <style>
        .product-image-preview {
            width: 200px;
            height: 200px;
            object-fit: cover;
            border-radius: 8px;
            border: 2px solid #ddd;
            margin-bottom: 10px;
        }
        .form-group {
            margin-bottom: 20px;
        }
        .form-group label {
            display: block;
            margin-bottom: 8px;
            font-weight: 600;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }
        .form-control:focus {
            outline: none;
            border-color: #3498db;
            box-shadow: 0 0 0 3px rgba(52, 152, 219, 0.1);
        }
        .alert-error {
            background: #fee;
            border: 1px solid #fcc;
            color: #c33;
            padding: 15px;
            border-radius: 6px;
            margin-bottom: 20px;
        }
        .alert-error ul {
            margin: 0;
            padding-left: 20px;
        }
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .page-header h1 {
            margin: 0;
            color: #2c3e50;
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

        // Hiển thị ảnh preview khi chọn file mới
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.querySelector('input[name="image"]');
            if (input) {
                input.addEventListener('change', function(e) {
                    if (this.files && this.files[0]) {
                        const reader = new FileReader();
                        reader.onload = function(ev) {
                            // Tìm hoặc tạo preview element
                            let preview = document.querySelector('.product-image-preview');
                            if (!preview) {
                                preview = document.createElement('img');
                                preview.className = 'product-image-preview';
                                input.parentNode.insertBefore(preview, input);
                            }
                            preview.src = ev.target.result;
                            preview.style.display = 'block';
                        };
                        reader.readAsDataURL(this.files[0]);
                    }
                });
            }
        });
    </script>
</body>
</html>

