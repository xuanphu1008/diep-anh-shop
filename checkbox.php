<?php
// checkout.php - Trang thanh toán

require_once 'config/config.php';
require_once 'models/Cart.php';
require_once 'models/User.php';
require_once 'models/Coupon.php';
require_once 'models/Order.php';
require_once 'includes/VNPay.php';

$cartModel = new Cart();
$userModel = new User();
$couponModel = new Coupon();
$orderModel = new Order();

// Kiểm tra giỏ hàng
$userId = $userModel->isLoggedIn() ? $_SESSION['user_id'] : null;
$cartDetails = $userId ? $cartModel->getCartDetails($userId) : null;

if (!$cartDetails || empty($cartDetails['items'])) {
    header('Location: cart.php');
    exit;
}

// Xử lý đặt hàng
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $customerName = $_POST['customer_name'] ?? '';
    $customerEmail = $_POST['customer_email'] ?? '';
    $customerPhone = $_POST['customer_phone'] ?? '';
    $customerAddress = $_POST['customer_address'] ?? '';
    $paymentMethod = $_POST['payment_method'] ?? 'cod';
    $note = $_POST['note'] ?? '';
    $couponCode = $_POST['coupon_code'] ?? '';
    
    // Validate
    $errors = [];
    if (empty($customerName)) $errors[] = 'Vui lòng nhập họ tên';
    if (empty($customerPhone)) $errors[] = 'Vui lòng nhập số điện thoại';
    if (empty($customerAddress)) $errors[] = 'Vui lòng nhập địa chỉ';
    
    // Kiểm tra tồn kho
    $stockValidation = $cartModel->validateCartStock($userId);
    if (!$stockValidation['valid']) {
        foreach ($stockValidation['errors'] as $error) {
            $errors[] = "Sản phẩm {$error['product_name']} chỉ còn {$error['available']} sản phẩm";
        }
    }
    
    if (empty($errors)) {
        $subtotal = $cartDetails['subtotal'];
        $couponDiscount = 0;
        $couponId = null;
        
        // Áp dụng mã giảm giá
        if (!empty($couponCode)) {
            $couponResult = $couponModel->applyCoupon($couponCode, $subtotal);
            if ($couponResult['success']) {
                $couponDiscount = $couponResult['discount'];
                $couponId = $couponResult['coupon_id'];
            }
        }
        
        $total = $subtotal - $couponDiscount;
        
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
            'note' => $note,
            'items' => []
        ];
        
        // Chuẩn bị items
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
            
            // Gửi email xác nhận
            require_once 'includes/mailer.php';
            $mailer = new Mailer();
            $orderInfo = $orderModel->getFullOrderInfo($result['order_id']);
            $mailer->sendOrderConfirmation($orderInfo);
            
            // Nếu thanh toán VNPay, redirect đến VNPay
            if ($paymentMethod === 'vnpay') {
                $vnpay = new VNPay();
                $paymentUrl = $vnpay->createPaymentUrl([
                    'order_code' => $result['order_code'],
                    'total' => $total,
                    'bank_code' => $_POST['bank_code'] ?? ''
                ]);
                header('Location: ' . $paymentUrl);
                exit;
            }
            
            // Redirect đến trang success
            header('Location: order-success.php?code=' . $result['order_code']);
            exit;
        } else {
            $errors[] = $result['message'];
        }
    }
}

// Lấy thông tin user nếu đã đăng nhập
$user = null;
if ($userId) {
    $user = $userModel->getUserById($userId);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Thanh toán - Diệp Anh Computer</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="checkout-page">
        <div class="container">
            <h1>Thanh toán đơn hàng</h1>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <?php foreach ($errors as $error): ?>
                        <p><?php echo $error; ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            
            <div class="checkout-content">
                <div class="checkout-form">
                    <form method="POST" action="">
                        <div class="form-section">
                            <h2>Thông tin người nhận</h2>
                            
                            <div class="form-group">
                                <label>Họ và tên <span class="required">*</span></label>
                                <input type="text" name="customer_name" 
                                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" 
                                       required>
                            </div>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label>Email</label>
                                    <input type="email" name="customer_email" 
                                           value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>">
                                </div>
                                
                                <div class="form-group">
                                    <label