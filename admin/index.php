<?php
// admin/index.php - Admin Dashboard

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../models/Order.php';
require_once __DIR__ . '/../models/Product.php';
require_once __DIR__ . '/../models/User.php';

requireStaff();

$orderModel = new Order();
$productModel = new Product();
$userModel = new User();

// Thống kê tổng quan
$totalRevenue = $orderModel->getTotalRevenue();
$totalOrders = $orderModel->countOrdersByStatus();
$totalProducts = $productModel->countProducts();
$totalCustomers = count($userModel->getAllCustomers());
$pendingOrders = $orderModel->countOrdersByStatus('pending');

// Doanh thu theo tháng (năm hiện tại)
$monthlyRevenue = $orderModel->getMonthlyRevenue(date('Y'));

// Đơn hàng mới nhất
$recentOrders = $orderModel->getAllOrders(null, 10);

// Sản phẩm bán chạy
$topProducts = $orderModel->getTopSellingProducts(5);

$pageTitle = 'Dashboard - Admin';
$activeMenu = 'dashboard';
include __DIR__ . '/layout.php';
?>
            <div class="page-header">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 10px; margin-bottom: 14px;">
                <div class="stat-card" style="border-left: 4px solid var(--admin-primary);">
                    <div>
                        <div style="font-size: 10px; color: var(--admin-text-light); margin-bottom: 4px;">Tổng doanh thu</div>
                        <div style="font-size: 17px; font-weight: 600; color: var(--admin-text);">
                            <?php echo formatCurrency($totalRevenue); ?>
                        </div>
                    </div>
                    <i class="fas fa-dollar-sign" style="font-size: 30px; opacity: 0.15; color: var(--admin-primary); position: absolute; right: 8px; top: 50%; transform: translateY(-50%);"></i>
                </div>
                
                <div class="stat-card" style="border-left: 4px solid var(--admin-success);">
                    <div>
                        <div style="font-size: 10px; color: var(--admin-text-light); margin-bottom: 4px;">Tổng đơn hàng</div>
                        <div style="font-size: 17px; font-weight: 600; color: var(--admin-text);">
                            <?php echo $totalOrders; ?>
                        </div>
                    </div>
                    <i class="fas fa-shopping-cart" style="font-size: 30px; opacity: 0.15; color: var(--admin-success); position: absolute; right: 8px; top: 50%; transform: translateY(-50%);"></i>
                </div>
                
                <div class="stat-card" style="border-left: 4px solid var(--admin-warning);">
                    <div>
                        <div style="font-size: 10px; color: var(--admin-text-light); margin-bottom: 4px;">Tổng sản phẩm</div>
                        <div style="font-size: 17px; font-weight: 600; color: var(--admin-text);">
                            <?php echo $totalProducts; ?>
                        </div>
                    </div>
                    <i class="fas fa-box" style="font-size: 30px; opacity: 0.15; color: var(--admin-warning); position: absolute; right: 8px; top: 50%; transform: translateY(-50%);"></i>
                </div>
                
                <div class="stat-card" style="border-left: 4px solid var(--admin-info);">
                    <div>
                        <div style="font-size: 10px; color: var(--admin-text-light); margin-bottom: 4px;">Khách hàng</div>
                        <div style="font-size: 17px; font-weight: 600; color: var(--admin-text);">
                            <?php echo $totalCustomers; ?>
                        </div>
                    </div>
                    <i class="fas fa-users" style="font-size: 30px; opacity: 0.15; color: var(--admin-info); position: absolute; right: 8px; top: 50%; transform: translateY(-50%);"></i>
                </div>
            </div>
            
            <!-- Biểu đồ doanh thu -->
            <div class="chart-container">
                <h3 style="margin-bottom: 10px; font-size: 14px; font-weight: 600; color: var(--admin-text);">
                    <i class="fas fa-chart-line" style="color: var(--admin-primary);"></i> Doanh thu theo tháng (<?php echo date('Y'); ?>)
                </h3>
                <canvas id="revenueChart" width="400" height="100"></canvas>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 10px;">
                <!-- Đơn hàng mới nhất -->
                <div class="data-table">
                    <div style="padding: 12px; background: linear-gradient(135deg, var(--admin-primary-very-pale) 0%, var(--admin-primary-pale) 100%); border-bottom: 2px solid var(--admin-border);">
                        <h3 style="margin: 0; font-size: 13px; font-weight: 600; color: var(--admin-text);">
                            <i class="fas fa-shopping-bag" style="color: var(--admin-primary);"></i> Đơn hàng mới nhất
                        </h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Mã đơn</th>
                                <th>Khách hàng</th>
                                <th>Tổng tiền</th>
                                <th>Trạng thái</th>
                                <th>Ngày đặt</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentOrders as $order): ?>
                            <tr>
                                <td><strong><?php echo $order['order_code']; ?></strong></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo formatCurrency($order['total']); ?></td>
                                <td>
                                    <span class="badge <?php echo getOrderStatusClass($order['order_status']); ?>">
                                        <?php echo getOrderStatusText($order['order_status']); ?>
                                    </span>
                                </td>
                                <td><?php echo formatDate($order['created_at'], 'd/m/Y'); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Sản phẩm bán chạy -->
                <div class="data-table">
                    <div style="padding: 12px; background: linear-gradient(135deg, var(--admin-primary-very-pale) 0%, var(--admin-primary-pale) 100%); border-bottom: 2px solid var(--admin-border);">
                        <h3 style="margin: 0; font-size: 13px; font-weight: 600; color: var(--admin-text);">
                            <i class="fas fa-fire" style="color: var(--admin-primary);"></i> Top sản phẩm
                        </h3>
                    </div>
                    <table>
                        <thead>
                            <tr>
                                <th>Sản phẩm</th>
                                <th>Đã bán</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topProducts as $product): ?>
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <img src="<?php echo getProductImage($product['image']); ?>" 
                                             style="width: 40px; height: 40px; object-fit: cover; border-radius: 5px;">
                                        <div style="font-size: 12px;">
                                            <?php echo truncateText($product['product_name'], 30); ?>
                                        </div>
                                    </div>
                                </td>
                                <td><strong><?php echo $product['total_sold']; ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script>
        // Dữ liệu doanh thu theo tháng
        const monthlyData = <?php echo json_encode($monthlyRevenue); ?>;
        
        const months = ['T1', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7', 'T8', 'T9', 'T10', 'T11', 'T12'];
        const revenues = new Array(12).fill(0);
        
        monthlyData.forEach(item => {
            revenues[item.month - 1] = item.revenue;
        });
        
        // Vẽ biểu đồ
        const ctx = document.getElementById('revenueChart').getContext('2d');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Doanh thu (VNĐ)',
                    data: revenues,
                    backgroundColor: 'rgba(50, 133, 166, 0.8)',
                    borderColor: 'rgba(50, 133, 166, 1)',
                    borderRadius: 8,
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
                },
                plugins: {
                    legend: {
                        display: true,
                        position: 'top'
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                return 'Doanh thu: ' + context.parsed.y.toLocaleString('vi-VN') + 'đ';
                            }
                        }
                    }
                }
            }
        });
    </script>