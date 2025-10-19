<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Category.php';

header('Content-Type: application/json');
requireStaff();

$categoryModel = new Category();

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || !isset($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Yêu cầu không hợp lệ']);
    exit;
}

$action = $input['action'];
if ($action === 'bulk_delete') {
    $ids = $input['ids'] ?? [];
    if (!is_array($ids) || empty($ids)) {
        echo json_encode(['success' => false, 'message' => 'Không có danh mục để xóa']);
        exit;
    }
    foreach ($ids as $id) {
        $categoryModel->deleteCategory(intval($id));
    }
    echo json_encode(['success' => true, 'message' => 'Đã xóa']);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Hành động không hỗ trợ']);
