<?php
// checkout.php - Trang thanh tóan

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/models/Cart.php';
require_once __DIR__ . '/models/User.php';
require_once __DIR__ . '/models/Coupon.php';
require_once __DIR__ . '/models/Order.php';
require_once __DIR__ . '/includes/VNPay.php';

$cartModel = new Cart();
$userModel = new User();
$couponModel = new Coupon();
$orderModel = new Order();

$userId = $userModel->isLoggedIn() ? $_SESSION['user_id'] : null;
$cartDetails = $userId ? $cartModel->getCartDetails($userId) : null;

if (!$cartDetails || empty($cartDetails['items'])) {
    header('Location: cart.php');
    exit;
}

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
    if (empty($customerName)) $errors[] = 'Vui lÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â²ng nhÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â­p hÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â tÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªn';
    if (empty($customerPhone)) $errors[] = 'Vui lÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â²ng nhÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â­p sÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã‹Å“ ÃƒÆ’Ã¢â‚¬Å¾ÃƒÂ¢Ã¢â€šÂ¬Ã‹Å“iÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡n thoÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â¡i';
    if (empty($customerAddress)) $errors[] = 'Vui lÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â²ng nhÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â­p ÃƒÆ’Ã¢â‚¬Å¾ÃƒÂ¢Ã¢â€šÂ¬Ã‹Å“ÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¹a chÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â°';
    
    $stockValidation = $cartModel->validateCartStock($userId);
    if (!$stockValidation['valid']) {
        foreach ($stockValidation['errors'] as $error) {
            $errors[] = "SÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â£n phÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â©m {$error['product_name']} chÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â° cÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â²n {$error['available']} sÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â£n phÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â©m";
        }
    }
    
    if (empty($errors)) {
        $subtotal = $cartDetails['subtotal'];
        $couponDiscount = 0;
        $couponId = null;
        
        if (!empty($couponCode)) {
            $couponResult = $couponModel->applyCoupon($couponCode, $subtotal);
            if ($couponResult['success']) {
                $couponDiscount = $couponResult['discount'];
                $couponId = $couponResult['coupon_id'];
            }
        }
        
        $total = max(0, $subtotal - $couponDiscount); // Đảm bảo total không bị âm
        
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
            $cartModel->clearCart($userId);
            
            require_once __DIR__ . '/includes/mailer.php';
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

// Lấy thông tin user nếu đăng nhập
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
    <title>Thanh toÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡n - DiÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»ÃƒÂ¢Ã¢â€šÂ¬Ã‚Â¡p Anh Computer</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div class="checkout-page">
        <div class="container">
            <h1>Thanh toÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â¡n ÃƒÆ’Ã¢â‚¬Å¾ÃƒÂ¢Ã¢â€šÂ¬Ã‹Å“ÃƒÆ’Ã¢â‚¬Â Ãƒâ€šÃ‚Â¡n hÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â ng</h1>
            
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
                            <h2>ThÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â´ng tin ngÃƒÆ’Ã¢â‚¬Â Ãƒâ€šÃ‚Â°ÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Âi nhÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚ÂºÃƒâ€šÃ‚Â­n</h2>
                            
                            <div class="form-group">
                                <label>HÃƒÆ’Ã‚Â¡Ãƒâ€šÃ‚Â»Ãƒâ€šÃ‚Â vÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Â  tÃƒÆ’Ã†â€™Ãƒâ€šÃ‚Âªn <span class="required">*</span></label>
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
