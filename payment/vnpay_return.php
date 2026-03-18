<?php
// payment/vnpay_return.php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/VNPay.php';
require_once __DIR__ . '/../models/Order.php';

$vnpay = new VNPay();
$verify = $vnpay->verifyReturnUrl($_GET);

$orderModel = new Order();
$pageTitle = 'Kết quả thanh toán';

// Lấy thông tin đơn hàng từ DB để kiểm tra
$orderCode = $_GET['vnp_TxnRef'] ?? '';
$order = $orderModel->getOrderByCode($orderCode);

if (!$order) {
    $message = "Đơn hàng không tồn tại";
    $icon = "times-circle";
    $color = "danger";
} else {
    if ($verify['valid']) {
        if ($_GET['vnp_ResponseCode'] == '00') {
            // Thanh toán thành công
            $message = "Giao dịch thành công! Cảm ơn bạn đã mua hàng.";
            $icon = "check-circle";
            $color = "success";
            
            // Cập nhật trạng thái đơn hàng nếu chưa cập nhật
            if ($order['payment_status'] != 'paid') {
                $db = new Database();
                $db->query("UPDATE orders SET payment_status = 'paid', order_status = 'processing' WHERE id = ?", [$order['id']]);
                
                // Lưu log giao dịch
                $vnpay->saveTransaction([
                    'order_id' => $order['id'],
                    'transaction_no' => $_GET['vnp_TransactionNo'],
                    'bank_code' => $_GET['vnp_BankCode'],
                    'amount' => $_GET['vnp_Amount'] / 100,
                    'order_info' => $_GET['vnp_OrderInfo'],
                    'response_code' => $_GET['vnp_ResponseCode'],
                    'transaction_status' => 'success'
                ]);
                
                // Gửi email xác nhận (nếu cần)
                // ...
            }
        } else {
            // Thanh toán thất bại / Hủy
            $message = "Giao dịch không thành công hoặc bị hủy.";
            $icon = "times-circle";
            $color = "danger";
            
            // Cập nhật trạng thái failed
             $db = new Database();
             $db->query("UPDATE orders SET payment_status = 'failed' WHERE id = ?", [$order['id']]);
        }
    } else {
        $message = "Chữ ký không hợp lệ!";
        $icon = "exclamation-triangle";
        $color = "warning";
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Kết quả thanh toán</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>
    
    <div class="container" style="padding: 80px 0; text-align: center;">
        <div style="max-width: 600px; margin: 0 auto; background: #fff; padding: 40px; border-radius: 10px; box-shadow: var(--shadow);">
            <i class="fas fa-<?php echo $icon; ?> text-<?php echo $color; ?>" style="font-size: 80px; margin-bottom: 20px;"></i>
            <h2 class="text-<?php echo $color; ?>"><?php echo $message; ?></h2>
            
            <?php if ($order): ?>
            <div style="margin: 30px 0; text-align: left; background: #f9f9f9; padding: 20px; border-radius: 5px;">
                <p><strong>Mã đơn hàng:</strong> <?php echo $order['order_code']; ?></p>
                <p><strong>Số tiền:</strong> <?php echo formatCurrency($order['total']); ?></p>
                <p><strong>Ngân hàng:</strong> <?php echo $_GET['vnp_BankCode'] ?? 'N/A'; ?></p>
                <p><strong>Thời gian:</strong> <?php echo date('H:i:s d/m/Y'); ?></p>
            </div>
            <?php endif; ?>
            
            <div style="margin-top: 30px;">
                <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">Về trang chủ</a>
                <?php if($order): ?>
                <a href="<?php echo SITE_URL; ?>/customer/order-detail.php?code=<?php echo $order['order_code']; ?>" class="btn btn-secondary">Xem đơn hàng</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>