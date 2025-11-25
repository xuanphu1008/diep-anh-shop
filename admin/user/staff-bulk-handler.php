<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/User.php';

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

$userModel = new User();

try {
    $db = new Database();
    
    switch ($action) {
        case 'bulk_activate':
            foreach ($ids as $id) {
                $db->query("UPDATE users SET status = 1 WHERE id = ?", [$id]);
            }
            echo json_encode(['success' => true, 'message' => 'Kích hoạt thành công']);
            break;
            
        case 'bulk_deactivate':
            foreach ($ids as $id) {
                $db->query("UPDATE users SET status = 0 WHERE id = ? AND role = 'staff'", [$id]);
            }
            echo json_encode(['success' => true, 'message' => 'Vô hiệu hóa thành công']);
            break;
            
        case 'bulk_delete':
            foreach ($ids as $id) {
                $db->query("DELETE FROM users WHERE id = ? AND role = 'staff'", [$id]);
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
