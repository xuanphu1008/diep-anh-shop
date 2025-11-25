<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Contact.php';

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

$contactModel = new Contact();

try {
    switch ($action) {
        case 'bulk_mark_read':
            foreach ($ids as $id) {
                $contactModel->updateContactStatus($id, 'resolved');
            }
            echo json_encode(['success' => true, 'message' => 'Đánh dấu đã đọc thành công']);
            break;
            
        case 'bulk_delete':
            foreach ($ids as $id) {
                $contactModel->deleteContact($id);
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
