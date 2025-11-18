<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Order.php';
require_once __DIR__ . '/../../models/Product.php';

requireStaff();

$orderModel = new Order();
$productModel = new Product();

// Thống kê tổng quan
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('m');

$totalRevenue = $orderModel->getTotalRevenue();
$monthlyRevenue = $orderModel->getMonthlyRevenue($year);
$dailyRevenue = $orderModel->getDailyRevenue($month, $year);
$topProducts = $orderModel->getTopSellingProducts(10);
$pageTitle = 'Thống kê - Admin';
$activeMenu = 'statistics';
include __DIR__ . '/../layout.php';
?>
            <div class="section-header">
                <h1 class="section-title"><i class="fas fa-chart-bar"></i> Thống kê & Báo cáo</h1>
            </div>
            
            <div class="card">
                <form method="GET" style="display: flex; gap: 15px; align-items: end; flex-wrap: wrap;">
                    <div class="form-group" style="margin: 0;">
                        <label>Năm</label>
                        <select name="year" class="form-control">
                            <?php for ($y = date('Y'); $y >= 2020; $y--): ?>
                                <option value="<?php echo $y; ?>" <?php echo $y == $year ? 'selected' : ''; ?>><?php echo $y; ?></option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group" style="margin: 0;">
                        <label>Tháng</label>
                        <select name="month" class="form-control">
                            <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?php echo str_pad($m, 2, '0', STR_PAD_LEFT); ?>" 
                                        <?php echo $m == $month ? 'selected' : ''; ?>>
                                    Tháng <?php echo $m; ?>
                                </option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-filter"></i> Lọc
                    </button>
                    <a href="export.php?year=<?php echo $year; ?>&month=<?php echo $month; ?>" class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Xuất Excel
                    </a>
                </form>
            </div>
            
            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; margin-bottom: 30px;">
                <div class="stat-card blue">
                    <div>
                        <div style="font-size: 14px; color: #7f8c8d;">Tổng doanh thu</div>
                        <div style="font-size: 28px; font-weight: bold; color: #2c3e50; margin-top: 5px;">
                            <?php echo formatCurrency($totalRevenue); ?>
                        </div>
                    </div>
                    <i class="fas fa-dollar-sign icon"></i>
                </div>
                <div class="stat-card green">
                    <div>
                        <div style="font-size: 14px; color: #7f8c8d;">Doanh thu tháng <?php echo $month; ?></div>
                        <div style="font-size: 28px; font-weight: bold; color: #2c3e50; margin-top: 5px;">
                            <?php 
                            $monthTotal = 0;
                            foreach ($dailyRevenue as $day) {
                                $monthTotal += $day['revenue'];
                            }
                            echo formatCurrency($monthTotal);
                            ?>
                        </div>
                    </div>
                    <i class="fas fa-calendar icon"></i>
                </div>
                <div class="stat-card orange">
                    <div>
                        <div style="font-size: 14px; color: #7f8c8d;">Số đơn tháng <?php echo $month; ?></div>
                        <div style="font-size: 28px; font-weight: bold; color: #2c3e50; margin-top: 5px;">
                            <?php 
                            $monthOrders = 0;
                            foreach ($dailyRevenue as $day) {
                                $monthOrders += $day['order_count'];
                            }
                            echo $monthOrders;
                            ?>
                        </div>
                    </div>
                    <i class="fas fa-shopping-cart icon"></i>
                </div>
            </div>
            
            <div class="chart-container">
                <h3><i class="fas fa-chart-line"></i> Doanh thu theo tháng năm <?php echo $year; ?></h3>
                <canvas id="monthlyChart" width="400" height="100"></canvas>
            </div>
            
            <div class="chart-container">
                <h3><i class="fas fa-chart-bar"></i> Doanh thu theo ngày tháng <?php echo $month; ?>/<?php echo $year; ?></h3>
                <canvas id="dailyChart" width="400" height="100"></canvas>
            </div>
            
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Sản phẩm</th>
                            <th>Đã bán</th>
                            <th>Doanh thu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($topProducts as $index => $product): ?>
                        <tr>
                            <td><strong><?php echo $index + 1; ?></strong></td>
                            <td>
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <img src="<?php echo getProductImage($product['image']); ?>" 
                                         style="width: 50px; height: 50px; object-fit: cover; border-radius: 5px;">
                                    <div><?php echo htmlspecialchars($product['product_name']); ?></div>
                                </div>
                            </td>
                            <td><strong><?php echo $product['total_sold']; ?></strong></td>
                            <td><strong style="color: #e74c3c;"><?php echo formatCurrency($product['total_revenue']); ?></strong></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Biểu đồ theo tháng
    const monthlyData = <?php echo json_encode($monthlyRevenue); ?>;
    const months = ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'];
    const monthlyRevenues = new Array(12).fill(0);
    monthlyData.forEach(item => {
        monthlyRevenues[item.month - 1] = item.revenue;
    });
    new Chart(document.getElementById('monthlyChart'), {
        type: 'bar',
        data: {
            labels: months,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: monthlyRevenues,
                backgroundColor: 'rgba(52, 152, 219, 0.8)',
                borderColor: 'rgba(52, 152, 219, 1)',
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('vi-VN') + 'đ';
                        }
                    }
                }
            }
        }
    });
    // Biểu đồ theo ngày
    const dailyData = <?php echo json_encode($dailyRevenue); ?>;
    const dates = dailyData.map(d => new Date(d.date).getDate());
    const dailyRevenues = dailyData.map(d => d.revenue);
    new Chart(document.getElementById('dailyChart'), {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Doanh thu (VNĐ)',
                data: dailyRevenues,
                backgroundColor: 'rgba(46, 204, 113, 0.2)',
                borderColor: 'rgba(46, 204, 113, 1)',
                borderWidth: 2,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            return value.toLocaleString('vi-VN') + 'đ';
                        }
                    }
                }
            }
        }
    });
</script>