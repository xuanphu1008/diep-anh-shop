<?php
// admin/user/get-customer-detail.php - API lấy thông tin chi tiết khách hàng

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/User.php';
require_once __DIR__ . '/../../models/Order.php';

requireStaff();

header('Content-Type: application/json');

$customerId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if (!$customerId) {
    echo json_encode(['success' => false, 'message' => 'ID khách hàng không hợp lệ']);
    exit;
}

$userModel = new User();
$orderModel = new Order();

// Lấy thông tin khách hàng
$customer = $userModel->getUserById($customerId);

if (!$customer || $customer['role'] !== 'customer') {
    echo json_encode(['success' => false, 'message' => 'Khách hàng không tồn tại']);
    exit;
}

// Lấy lịch sử đơn hàng
$orders = $orderModel->getOrdersByUser($customerId);

// Tính tổng số đơn hàng và tổng giá trị
$totalOrders = count($orders);
$totalSpent = 0;
$orderStats = [
    'pending' => 0,
    'confirmed' => 0,
    'processing' => 0,
    'shipping' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

foreach ($orders as $order) {
    if ($order['payment_status'] === 'paid' && $order['order_status'] !== 'cancelled') {
        $totalSpent += $order['total'];
    }
    if (isset($orderStats[$order['order_status']])) {
        $orderStats[$order['order_status']]++;
    }
}

// Chuẩn bị dữ liệu trả về
$data = [
    'success' => true,
    'customer' => [
        'id' => $customer['id'],
        'username' => $customer['username'],
        'email' => $customer['email'],
        'full_name' => $customer['full_name'],
        'phone' => $customer['phone'],
        'address' => $customer['address'],
        'status' => $customer['status'],
        'created_at' => $customer['created_at'],
        'updated_at' => $customer['updated_at'] ?? null
    ],
    'statistics' => [
        'total_orders' => $totalOrders,
        'total_spent' => $totalSpent,
        'order_status' => $orderStats
    ],
    'recent_orders' => array_slice($orders, 0, 5) // 5 đơn hàng gần nhất
];

echo json_encode($data, JSON_UNESCAPED_UNICODE);
?>

