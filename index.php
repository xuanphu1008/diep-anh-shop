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
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .admin-layout {
            display: grid;
            grid-template-columns: 250px 1fr;
            min-height: 100vh;
        }
        .admin-sidebar {
            background: #2c3e50;
            color: #fff;
            padding: 20px 0;
        }
        .admin-sidebar h2 {
            padding: 0 20px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.1);
            margin-bottom: 20px;
        }
        .admin-menu {
            list-style: none;
        }
        .admin-menu li a {
            display: block;
            padding: 12px 20px;
            color: #ecf0f1;
            transition: all 0.3s;
        }
        .admin-menu li a:hover,
        .admin-menu li a.active {
            background: #34495e;
            border-left: 3px solid var(--primary-color);
        }
        .admin-content {
            padding: 30px;
            background: #ecf0f1;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .stat-card .icon {
            font-size: 48px;
            opacity: 0.3;
        }
        .stat-card.blue .icon { color: #3498db; }
        .stat-card.green .icon { color: #2ecc71; }
        .stat-card.orange .icon { color: #f39c12; }
        .stat-card.red .icon { color: #e74c3c; }
        .stat-card.purple .icon { color: #9b59b6; }
        .chart-container {
            background: #fff;
            padding: 25px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            margin-bottom: 30px;
        }
        .data-table {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .data-table table {
            width: 100%;
            border-collapse: collapse;
        }
        .data-table th {
            background: #34495e;
            color: #fff;
            padding: 15px;
            text-align: left;
        }
        .data-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        .data-table tr:hover {
            background: #f8f9fa;
        }
    </style>
</head>
<body>
    <div class="admin-layout">
        <!-- Sidebar -->
        <aside class="admin-sidebar">
            <h2><i class="fas fa-cog"></i> Admin Panel</h2>
            
            <ul class="admin-menu">
                <li><a href="index.php" class="active"><i class="fas fa-home"></i> Dashboard</a></li>
                <li><a href="products/index.php"><i class="fas fa-box"></i> Sản phẩm</a></li>
                <li><a href="categories/index.php"><i class="fas fa-list"></i> Danh mục</a></li>
                <li><a href="orders/index.php"><i class="fas fa-shopping-cart"></i> Đơn hàng</a></li>
                <li><a href="users/customers.php"><i class="fas fa-users"></i> Khách hàng</a></li>
                <li><a href="users/staff.php"><i class="fas fa-user-tie"></i> Nhân viên</a></li>
                <li><a href="coupons/index.php"><i class="fas fa-tags"></i> Mã giảm giá</a></li>
                <li><a href="news/index.php"><i class="fas fa-newspaper"></i> Tin tức</a></li>
                <li><a href="banners/index.php"><i class="fas fa-image"></i> Banner</a></li>
                <li><a href="contacts/index.php"><i class="fas fa-envelope"></i> Liên hệ</a></li>
                <li><a href="statistics/index.php"><i class="fas fa-chart-bar"></i> Thống kê</a></li>
                <li><hr style="border-color: rgba(255,255,255,0.1);"></li>
                <li><a href="../index.php"><i class="fas fa-home"></i> Về trang chủ</a></li>
                <li><a href="../customer/logout.php"><i class="fas fa-sign-out-alt"></i> Đăng xuất</a></li>
            </ul>
        </aside>
        
        <!-- Main Content -->
        <main class="admin-content">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px;">
                <h1><i class="fas fa-tachometer-alt"></i> Dashboard</h1>
                <div>
                    <i class="fas fa-dollar-sign icon"></i>
                </div>
                
                <div class="stat-card green">
                    <div>
                        <div style="font-size: 14px; color: #7f8c8d;">Tổng đơn hàng</div>
                        <div style="font-size: 28px; font-weight: bold; color: #2c3e50; margin-top: 5px;">
                            <?php echo $totalOrders; ?>
                        </div>
                    </div>
                    <i class="fas fa-shopping-cart icon"></i>
                </div>
                
                <div class="stat-card orange">
                    <div>
                        <div style="font-size: 14px; color: #7f8c8d;">Tổng sản phẩm</div>
                        <div style="font-size: 28px; font-weight: bold; color: #2c3e50; margin-top: 5px;">
                            <?php echo $totalProducts; ?>
                        </div>
                    </div>
                    <i class="fas fa-box icon"></i>
                </div>
                
                <div class="stat-card red">
                    <div>
                        <div style="font-size: 14px; color: #7f8c8d;">Khách hàng</div>
                        <div style="font-size: 28px; font-weight: bold; color: #2c3e50; margin-top: 5px;">
                            <?php echo $totalCustomers; ?>
                        </div>
                    </div>
                    <i class="fas fa-users icon"></i>
                </div>
                
                <div class="stat-card purple">
                    <div>
                        <div style="font-size: 14px; color: #7f8c8d;">Đơn chờ xử lý</div>
                        <div style="font-size: 28px; font-weight: bold; color: #2c3e50; margin-top: 5px;">
                            <?php echo $pendingOrders; ?>
                        </div>
                    </div>
                    <i class="fas fa-clock icon"></i>
                </div>
            </div>
            
            <!-- Biểu đồ doanh thu -->
            <div class="chart-container">
                <h3 style="margin-bottom: 20px;"><i class="fas fa-chart-line"></i> Doanh thu theo tháng (<?php echo date('Y'); ?>)</h3>
                <canvas id="revenueChart" width="400" height="100"></canvas>
            </div>
            
            <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 30px;">
                <!-- Đơn hàng mới nhất -->
                <div class="data-table">
                    <h3 style="padding: 20px; background: #34495e; color: #fff; margin: 0;">
                        <i class="fas fa-shopping-bag"></i> Đơn hàng mới nhất
                    </h3>
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
                    <h3 style="padding: 20px; background: #34495e; color: #fff; margin: 0;">
                        <i class="fas fa-fire"></i> Top sản phẩm
                    </h3>
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
        </main>
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
</body>
</html>
<i class="fas fa-user"></i> 
                    Xin chào, <strong><?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?></strong>
                </div>
            </div>
            
            <!-- Thống kê tổng quan -->
            <div class="stats-grid">
                <div class="stat-card blue">
                    <div>
                        <div style="font-size: 14px; color: #7f8c8d;">Tổng doanh thu</div>
                        <div style="font-size: 28px; font-weight: bold; color: #2c3e50; margin-top: 5px;">
                            <?php echo formatCurrency($totalRevenue); ?>
                        </div>
                    </div>
                    <i class