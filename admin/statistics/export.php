<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Order.php';
require_once __DIR__ . '/../../models/Product.php';

requireStaff();

$orderModel = new Order();
$productModel = new Product();

// Lấy tham số
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

// Lấy dữ liệu thống kê
$totalRevenue = $orderModel->getTotalRevenue();
$monthlyRevenue = $orderModel->getMonthlyRevenue($year);
$dailyRevenue = $orderModel->getDailyRevenue($month, $year);
$topProducts = $orderModel->getTopSellingProducts(10);

// Tính tổng doanh thu tháng
$monthTotal = 0;
$monthOrders = 0;
foreach ($dailyRevenue as $day) {
    $monthTotal += $day['revenue'];
    $monthOrders += $day['order_count'];
}

// Thiết lập header để xuất Excel
header('Content-Type: application/vnd.ms-excel; charset=utf-8');
header('Content-Disposition: attachment; filename="thong-ke-bao-cao-' . $year . '-' . $month . '.xls"');
header('Pragma: no-cache');
header('Expires: 0');

// Bắt đầu xuất Excel
echo '<html xmlns:x="urn:schemas-microsoft-com:office:excel">';
echo '<head>';
echo '<meta http-equiv="Content-Type" content="text/html; charset=utf-8">';
echo '<style>';
echo 'table { border-collapse: collapse; width: 100%; }';
echo 'th, td { border: 1px solid #000; padding: 8px; text-align: left; }';
echo 'th { background-color: #4CAF50; color: white; font-weight: bold; }';
echo '.header { background-color: #2196F3; color: white; font-weight: bold; text-align: center; }';
echo '.total { background-color: #FFC107; font-weight: bold; }';
echo '</style>';
echo '</head>';
echo '<body>';

// Tiêu đề báo cáo
echo '<table>';
echo '<tr><td colspan="4" class="header" style="font-size: 18px; padding: 15px;">BÁO CÁO THỐNG KÊ DOANH THU</td></tr>';
echo '<tr><td colspan="4" style="padding: 10px;"><strong>Năm:</strong> ' . $year . ' | <strong>Tháng:</strong> ' . $month . '</td></tr>';
echo '<tr><td colspan="4" style="padding: 10px;"><strong>Ngày xuất:</strong> ' . date('d/m/Y H:i:s') . '</td></tr>';
echo '</table>';

echo '<br>';

// Tổng quan
echo '<table>';
echo '<tr><td colspan="4" class="header">TỔNG QUAN</td></tr>';
echo '<tr><th>Chỉ tiêu</th><th>Giá trị</th></tr>';
echo '<tr><td>Tổng doanh thu (tất cả thời gian)</td><td>' . number_format($totalRevenue, 0, ',', '.') . ' đ</td></tr>';
echo '<tr><td>Doanh thu tháng ' . $month . '/' . $year . '</td><td>' . number_format($monthTotal, 0, ',', '.') . ' đ</td></tr>';
echo '<tr><td>Số đơn hàng tháng ' . $month . '/' . $year . '</td><td>' . $monthOrders . ' đơn</td></tr>';
echo '</table>';

echo '<br>';

// Doanh thu theo tháng trong năm
echo '<table>';
echo '<tr><td colspan="3" class="header">DOANH THU THEO THÁNG NĂM ' . $year . '</td></tr>';
echo '<tr><th>Tháng</th><th>Doanh thu (VNĐ)</th><th>Số đơn hàng</th></tr>';

$monthlyData = [];
foreach ($monthlyRevenue as $item) {
    $monthlyData[$item['month']] = $item;
}

for ($m = 1; $m <= 12; $m++) {
    $revenue = isset($monthlyData[$m]) ? $monthlyData[$m]['revenue'] : 0;
    $orderCount = isset($monthlyData[$m]) ? $monthlyData[$m]['order_count'] : 0;
    echo '<tr>';
    echo '<td>Tháng ' . $m . '</td>';
    echo '<td>' . number_format($revenue, 0, ',', '.') . ' đ</td>';
    echo '<td>' . $orderCount . '</td>';
    echo '</tr>';
}
echo '</table>';

echo '<br>';

// Doanh thu theo ngày trong tháng
echo '<table>';
echo '<tr><td colspan="3" class="header">DOANH THU THEO NGÀY THÁNG ' . $month . '/' . $year . '</td></tr>';
echo '<tr><th>Ngày</th><th>Doanh thu (VNĐ)</th><th>Số đơn hàng</th></tr>';

if (empty($dailyRevenue)) {
    echo '<tr><td colspan="3" style="text-align: center;">Không có dữ liệu</td></tr>';
} else {
    foreach ($dailyRevenue as $day) {
        $date = new DateTime($day['date']);
        echo '<tr>';
        echo '<td>' . $date->format('d/m/Y') . '</td>';
        echo '<td>' . number_format($day['revenue'], 0, ',', '.') . ' đ</td>';
        echo '<td>' . $day['order_count'] . '</td>';
        echo '</tr>';
    }
}
echo '</table>';

echo '<br>';

// Top sản phẩm bán chạy
echo '<table>';
echo '<tr><td colspan="4" class="header">TOP 10 SẢN PHẨM BÁN CHẠY</td></tr>';
echo '<tr><th>STT</th><th>Tên sản phẩm</th><th>Số lượng đã bán</th><th>Doanh thu (VNĐ)</th></tr>';

if (empty($topProducts)) {
    echo '<tr><td colspan="4" style="text-align: center;">Không có dữ liệu</td></tr>';
} else {
    foreach ($topProducts as $index => $product) {
        echo '<tr>';
        echo '<td>' . ($index + 1) . '</td>';
        echo '<td>' . htmlspecialchars($product['product_name']) . '</td>';
        echo '<td>' . number_format($product['total_sold'], 0, ',', '.') . '</td>';
        echo '<td>' . number_format($product['total_revenue'], 0, ',', '.') . ' đ</td>';
        echo '</tr>';
    }
}
echo '</table>';

echo '</body>';
echo '</html>';
exit;
?>

