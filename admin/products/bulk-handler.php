<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Product.php';

header('Content-Type: application/json');
requireStaff();

$productModel = new Product();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
    exit;
}

$action = $input['action'];
$ids = $input['ids'] ?? [];
if (!is_array($ids) || empty($ids)) {
    echo json_encode(['success' => false, 'message' => 'Không có sản phẩm để xử lý']);
    exit;
}

foreach ($ids as $id) {
    $id = intval($id);
    if ($action === 'bulk_delete') {
        $productModel->deleteProduct($id);
    } elseif ($action === 'bulk_activate') {
        $productModel->activateProduct($id);
    } elseif ($action === 'bulk_deactivate') {
        $productModel->deactivateProduct($id);
    }
}

echo json_encode(['success' => true, 'message' => 'Đã thực hiện hành động']);
