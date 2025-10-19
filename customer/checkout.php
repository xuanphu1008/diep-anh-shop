<?php
// customer/checkout.php - Trang thanh toán

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Cart.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Coupon.php';
require_once __DIR__ . '/../includes/VNPay.php';
require_once __DIR__ . '/../models/User.php';

requireLogin();

$cartModel = new Cart();
$orderModel = new Order();
$couponModel = new Coupon();

$userId = $_SESSION['user_id'];
$cartDetails = $cartModel->getCartDetails($userId);

// Redirect nếu giỏ hàng trống
if (empty($cartDetails['items'])) {
    setFlashMessage('warning', 'Giỏ hàng trống');
    redirect(SITE_URL . '/products.php');
}

// Kiểm tra tồn kho
$stockCheck = $cartModel->validateCartStock($userId);
if (!$stockCheck['valid']) {
    setFlashMessage('error', 'Một số sản phẩm không đủ số lượng');
    redirect(SITE_URL . '/customer/cart.php');
}

$user = (new User())->getUserById($userId);
$errors = [];
$couponDiscount = 0;
$couponId = null;

// Xử lý áp dụng coupon
if (isset($_SESSION['applied_coupon'])) {
    $couponDiscount = $_SESSION['applied_coupon']['discount'];
    $couponId = $_SESSION['applied_coupon']['coupon_id'];
}

$subtotal = $cartDetails['subtotal'];
$total = $subtotal - $couponDiscount;

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = sanitizeInput($_POST['customer_name'] ?? '');
    $customerEmail = sanitizeInput($_POST['customer_email'] ?? '');
    $customerPhone = sanitizeInput($_POST['customer_phone'] ?? '');
    $customerAddress = sanitizeInput($_POST['customer_address'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'cod';
    $note = sanitizeInput($_POST['note'] ?? '');
    
    // Validation
    if (empty($customerName)) $errors[] = 'Vui lòng nhập họ tên';
    if (empty($customerPhone)) $errors[] = 'Vui lòng nhập số điện thoại';
    if (empty($customerAddress)) $errors[] = 'Vui lòng nhập địa chỉ';
    
    if (empty($errors)) {
        // Tạo đơn hàng
        $orderData = [
            'user_id' => $userId,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'customer_phone' => $customerPhone,
            'customer_address' => $customerAddress,
            'coupon_id' => $couponId,
            'coupon_discount' => $couponDiscount,
            'subtotal' => $subtotal,
            'total' => $total,
            'payment_method' => $paymentMethod,
            'payment_status' => $paymentMethod === 'vnpay' ? 'pending' : 'pending',
            'note' => $note,
            'items' => []
        ];
        
        // Thêm chi tiết sản phẩm
        foreach ($cartDetails['items'] as $item) {
            $orderData['items'][] = [
                'product_id' => $item['product_id'],
                'product_name' => $item['name'],
                'product_price' => $item['final_price'],
                'quantity' => $item['quantity'],
                'total' => $item['total']
            ];
        }
        
        $result = $orderModel->createOrder($orderData);
        
        if ($result['success']) {
            // Xóa giỏ hàng
            $cartModel->clearCart($userId);
            
            // Xóa coupon đã áp dụng
            unset($_SESSION['applied_coupon']);
            
            if ($paymentMethod === 'vnpay') {
                // Chuyển sang VNPay
                $vnpay = new VNPay();
                $vnpayData = [
                    'order_code' => $result['order_code'],
                    'total' => $total,
                    'bank_code' => $_POST['bank_code'] ?? ''
                ];
                
                $paymentUrl = $vnpay->createPaymentUrl($vnpayData);
                redirect($paymentUrl);
            } else {
                // COD - Hoàn tất
                setFlashMessage('success', 'Đặt hàng thành công! Mã đơn hàng: ' . $result['order_code']);
                redirect(SITE_URL . '/customer/orders.php');
            }
        } else {
            $errors[] = $result['message'];
        }
    }
}

$pageTitle = 'Thanh toán - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container" style="padding: 30px 0;">
        <h1 class="section-title"><i class="fas fa-credit-card"></i> Thanh toán</h1>
        
        <?php if (!empty($errors)): ?>
            <div class="alert alert-error">
                <ul style="margin: 0; padding-left: 20px;">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
        
        <form method="POST" style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px;">
            <!-- Thông tin giao hàng -->
            <div style="background: #fff; padding: 30px; border-radius: 10px; box-shadow: var(--shadow);">
                <h2 style="margin-bottom: 20px;"><i class="fas fa-shipping-fast"></i> Thông tin giao hàng</h2>
                
                <div class="form-group">
                    <label>Họ và tên *</label>
                    <input type="text" name="customer_name" class="form-control" 
                           value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" name="customer_email" class="form-control" 
                               value="<?php echo htmlspecialchars($user['email']); ?>">
                    </div>
                    
                    <div class="form-group">
                        <label>Số điện thoại *</label>
                        <input type="text" name="customer_phone" class="form-control" 
                               value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Địa chỉ giao hàng *</label>
                    <textarea name="customer_address" class="form-control" rows="3" required><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-group">
                    <label>Ghi chú đơn hàng</label>
                    <textarea name="note" class="form-control" rows="3" placeholder="Ghi chú về đơn hàng, ví dụ: thời gian hay chỉ dẫn địa điểm giao hàng chi tiết hơn"></textarea>
                </div>
                
                <h2 style="margin: 30px 0 20px;"><i class="fas fa-credit-card"></i> Phương thức thanh toán</h2>
                
                <div style="border: 1px solid var(--border-color); border-radius: 10px; overflow: hidden;">
                    <label style="display: block; padding: 15px; cursor: pointer; border-bottom: 1px solid var(--border-color);">
                        <input type="radio" name="payment_method" value="cod" checked>
                        <strong style="margin-left: 10px;">Thanh toán khi nhận hàng (COD)</strong>
                        <p style="margin: 10px 0 0 30px; color: #666; font-size: 14px;">Thanh toán bằng tiền mặt khi nhận hàng</p>
                    </label>
                    
                    <label style="display: block; padding: 15px; cursor: pointer;" onclick="document.getElementById('vnpay-options').style.display='block'">
                        <input type="radio" name="payment_method" value="vnpay">
                        <strong style="margin-left: 10px;">Thanh toán qua VNPay</strong>
                        <p style="margin: 10px 0 0 30px; color: #666; font-size: 14px;">Thanh toán qua thẻ ATM, Visa, MasterCard</p>
                    </label>
                    
                    <div id="vnpay-options" style="display: none; padding: 15px; background: var(--light-color);">
                        <label>Chọn ngân hàng (Optional):</label>
                        <select name="bank_code" class="form-control">
                            <option value="">-- Chọn ngân hàng --</option>
                            <option value="VNPAYQR">Thanh toán qua QR Code</option>
                            <option value="VNBANK">ATM/Tài khoản nội địa</option>
                            <option value="INTCARD">Thẻ quốc tế</option>
                            <option value="VIETCOMBANK">Vietcombank</option>
                            <option value="VIETINBANK">VietinBank</option>
                            <option value="BIDV">BIDV</option>
                            <option value="AGRIBANK">Agribank</option>
                        </select>
                    </div>
                </div>
            </div>
            
            <!-- Đơn hàng -->
            <div>
                <div style="background: #fff; padding: 20px; border-radius: 10px; box-shadow: var(--shadow);">
                    <h3 style="margin-bottom: 20px;">Đơn hàng của bạn</h3>
                    
                    <div style="max-height: 300px; overflow-y: auto; margin-bottom: 20px;">
                        <?php foreach ($cartDetails['items'] as $item): ?>
                        <div style="display: flex; gap: 10px; margin-bottom: 15px; padding-bottom: 15px; border-bottom: 1px solid var(--border-color);">
                            <img src="<?php echo getProductImage($item['image']); ?>" 
                                 style="width: 60px; height: 60px; object-fit: cover; border-radius: 5px;">
                            <div style="flex: 1;">
                                <div style="font-size: 14px;"><?php echo htmlspecialchars($item['name']); ?></div>
                                <div style="color: #666; font-size: 12px;">SL: <?php echo $item['quantity']; ?></div>
                            </div>
                            <div style="font-weight: bold; color: var(--danger-color);">
                                <?php echo formatCurrency($item['total']); ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <div style="border-top: 2px solid var(--border-color); padding-top: 15px;">
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Tạm tính:</span>
                            <span><?php echo formatCurrency($subtotal); ?></span>
                        </div>
                        
                        <?php if ($couponDiscount > 0): ?>
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px; color: var(--success-color);">
                            <span>Giảm giá:</span>
                            <span>-<?php echo formatCurrency($couponDiscount); ?></span>
                        </div>
                        <?php endif; ?>
                        
                        <div style="display: flex; justify-content: space-between; margin-bottom: 10px;">
                            <span>Phí vận chuyển:</span>
                            <span>Miễn phí</span>
                        </div>
                        
                        <div style="display: flex; justify-content: space-between; font-size: 20px; font-weight: bold; color: var(--primary-color); margin-top: 15px; padding-top: 15px; border-top: 2px solid var(--border-color);">
                            <span>Tổng cộng:</span>
                            <span><?php echo formatCurrency($total); ?></span>
                        </div>
                    </div>
                    
                    <button type="submit" class="btn btn-success btn-block btn-lg" style="margin-top: 20px;">
                        <i class="fas fa-check-circle"></i> Đặt hàng
                    </button>
                    
                    <a href="cart.php" class="btn btn-secondary btn-block" style="margin-top: 10px;">
                        <i class="fas fa-arrow-left"></i> Quay lại giỏ hàng
                    </a>
                </div>
            </div>
        </form>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>