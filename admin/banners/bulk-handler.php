<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Banner.php';

requireStaff();

header('Content-Type: application/json');

$data = json_decode(file_get_contents('php://input'), true);
$action = $data['action'] ?? '';
$ids = $data['ids'] ?? [];

if (!$ids || !is_array($ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$bannerModel = new Banner();

try {
    switch ($action) {
        case 'bulk_show':
            foreach ($ids as $id) {
                $bannerModel->updateBanner($id, ['status' => 1]);
            }
            echo json_encode(['success' => true, 'message' => 'Hiển thị thành công']);
            break;
            
        case 'bulk_hide':
            foreach ($ids as $id) {
                $bannerModel->updateBanner($id, ['status' => 0]);
            }
            echo json_encode(['success' => true, 'message' => 'Ẩn thành công']);
            break;
            
        case 'bulk_delete':
            foreach ($ids as $id) {
                $bannerModel->deleteBanner($id);
            }
            echo json_encode(['success' => true, 'message' => 'Xóa thành công']);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Invalid action']);
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
}
