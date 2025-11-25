<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/User.php';

requireStaff();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$ids = $input['ids'] ?? [];

if (!is_array($ids) || !$action) {
    echo json_encode(['success' => false, 'message' => 'Thiếu dữ liệu']);
    exit;
}

$userModel = new User();
$success = true;
foreach ($ids as $id) {
    $id = (int)$id;
    if ($action === 'bulk_lock') {
        $userModel->lockCustomer($id);
        $success = $success && true;
    } elseif ($action === 'bulk_unlock') {
        $userModel->unlockCustomer($id);
        $success = $success && true;
    }
}
if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
?>