<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Order.php';

requireStaff();

$orderModel = new Order();

// Lấy ID đơn hàng
$orderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$orderId) {
    die('Không tìm thấy đơn hàng');
}

// Lấy thông tin đơn hàng đầy đủ
$order = $orderModel->getFullOrderInfo($orderId);

if (!$order) {
    die('Đơn hàng không tồn tại');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>In đơn hàng #<?php echo htmlspecialchars($order['order_code']); ?></title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', 'DejaVu Sans', sans-serif;
            font-size: 12px;
            color: #333;
            background: #fff;
            padding: 20px;
        }
        
        @media print {
            body {
                padding: 0;
            }
            
            .no-print {
                display: none !important;
            }
            
            @page {
                margin: 1cm;
            }
        }
        
        .print-container {
            max-width: 800px;
            margin: 0 auto;
            background: #fff;
        }
        
        .header {
            text-align: center;
            border-bottom: 3px solid #333;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 10px;
            color: #333;
        }
        
        .header p {
            font-size: 12px;
            color: #666;
        }
        
        .order-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        
        .info-box {
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
        }
        
        .info-box h3 {
            font-size: 14px;
            margin-bottom: 10px;
            border-bottom: 1px solid #ddd;
            padding-bottom: 5px;
            color: #333;
        }
        
        .info-box p {
            margin: 5px 0;
            font-size: 12px;
        }
        
        .info-box strong {
            display: inline-block;
            width: 120px;
        }
        
        .products-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        .products-table th {
            background: #f5f5f5;
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
            font-size: 12px;
            font-weight: bold;
        }
        
        .products-table td {
            padding: 10px;
            border: 1px solid #ddd;
            font-size: 12px;
        }
        
        .products-table .text-right {
            text-align: right;
        }
        
        .products-table .text-center {
            text-align: center;
        }
        
        .total-section {
            margin-top: 20px;
            display: flex;
            justify-content: flex-end;
        }
        
        .total-box {
            width: 300px;
            border: 1px solid #ddd;
            padding: 15px;
        }
        
        .total-row {
            display: flex;
            justify-content: space-between;
            margin: 8px 0;
            font-size: 12px;
        }
        
        .total-row.final {
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 10px;
            font-size: 16px;
            font-weight: bold;
        }
        
        .footer {
            margin-top: 40px;
            text-align: center;
            border-top: 1px solid #ddd;
            padding-top: 20px;
            font-size: 11px;
            color: #666;
        }
        
        .status-badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 3px;
            font-size: 11px;
            font-weight: bold;
        }
        
        .status-pending { background: #fff3cd; color: #856404; }
        .status-confirmed { background: #d1ecf1; color: #0c5460; }
        .status-processing { background: #cfe2ff; color: #084298; }
        .status-shipping { background: #d4edda; color: #155724; }
        .status-delivered { background: #d4edda; color: #155724; }
        .status-cancelled { background: #f8d7da; color: #721c24; }
        
        .print-buttons {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .btn {
            display: inline-block;
            padding: 10px 20px;
            margin: 0 10px;
            background: #3498db;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            cursor: pointer;
            border: none;
            font-size: 14px;
        }
        
        .btn:hover {
            background: #2980b9;
        }
        
        .btn-print {
            background: #27ae60;
        }
        
        .btn-print:hover {
            background: #229954;
        }
    </style>
</head>
<body>
    <div class="print-container">
        <div class="no-print print-buttons">
            <button onclick="window.print()" class="btn btn-print">🖨️ In đơn hàng</button>
            <button onclick="window.close()" class="btn">✕ Đóng</button>
        </div>
        
        <div class="header">
            <h1>HÓA ĐƠN BÁN HÀNG</h1>
            <p>Mã đơn hàng: <strong><?php echo htmlspecialchars($order['order_code']); ?></strong></p>
            <p>Ngày đặt: <?php echo formatDate($order['created_at'], 'd/m/Y H:i:s'); ?></p>
        </div>
        
        <div class="order-info">
            <div class="info-box">
                <h3>Thông tin khách hàng</h3>
                <p><strong>Họ tên:</strong> <?php echo htmlspecialchars($order['customer_name']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['customer_email']); ?></p>
                <p><strong>Điện thoại:</strong> <?php echo htmlspecialchars($order['customer_phone']); ?></p>
                <p><strong>Địa chỉ:</strong> <?php echo nl2br(htmlspecialchars($order['customer_address'])); ?></p>
            </div>
            
            <div class="info-box">
                <h3>Thông tin đơn hàng</h3>
                <p><strong>Mã đơn:</strong> <?php echo htmlspecialchars($order['order_code']); ?></p>
                <p><strong>Trạng thái:</strong> 
                    <span class="status-badge status-<?php echo $order['order_status']; ?>">
                        <?php echo getOrderStatusText($order['order_status']); ?>
                    </span>
                </p>
                <p><strong>Thanh toán:</strong> <?php echo getPaymentMethodText($order['payment_method']); ?></p>
                <p><strong>Trạng thái TT:</strong> 
                    <span class="status-badge status-<?php echo $order['payment_status']; ?>">
                        <?php echo getPaymentStatusText($order['payment_status']); ?>
                    </span>
                </p>
                <?php if ($order['note']): ?>
                <p><strong>Ghi chú:</strong> <?php echo nl2br(htmlspecialchars($order['note'])); ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <table class="products-table">
            <thead>
                <tr>
                    <th style="width: 50px;">STT</th>
                    <th>Sản phẩm</th>
                    <th class="text-center" style="width: 100px;">Số lượng</th>
                    <th class="text-right" style="width: 120px;">Đơn giá</th>
                    <th class="text-right" style="width: 120px;">Thành tiền</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $stt = 1;
                foreach ($order['details'] as $item): 
                ?>
                <tr>
                    <td class="text-center"><?php echo $stt++; ?></td>
                    <td><?php echo htmlspecialchars($item['product_name']); ?></td>
                    <td class="text-center"><?php echo $item['quantity']; ?></td>
                    <td class="text-right"><?php echo formatCurrency($item['product_price']); ?></td>
                    <td class="text-right"><?php echo formatCurrency($item['total']); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        
        <div class="total-section">
            <div class="total-box">
                <div class="total-row">
                    <span>Tạm tính:</span>
                    <span><?php echo formatCurrency($order['subtotal']); ?></span>
                </div>
                <?php if ($order['coupon_discount'] > 0): ?>
                <div class="total-row" style="color: #27ae60;">
                    <span>Giảm giá:</span>
                    <span>-<?php echo formatCurrency($order['coupon_discount']); ?></span>
                </div>
                <?php endif; ?>
                <div class="total-row final">
                    <span>TỔNG CỘNG:</span>
                    <span><?php echo formatCurrency($order['total']); ?></span>
                </div>
            </div>
        </div>
        
        <div class="footer">
            <p>Cảm ơn quý khách đã mua hàng!</p>
            <p>Mọi thắc mắc vui lòng liên hệ: Hotline: 1900xxxx | Email: support@example.com</p>
        </div>
    </div>
    
    <script>
        // Auto print when page loads (optional)
        // window.onload = function() {
        //     window.print();
        // }
    </script>
</body>
</html>

