<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Suppiler.php';

requireStaff();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$ids = $input['ids'] ?? [];

if (!is_array($ids) || !$action) {
    echo json_encode(['success' => false, 'message' => 'Thiếu dữ liệu']);
    exit;
}

$supplierModel = new Suppiler();
$success = true;
foreach ($ids as $id) {
    $id = (int)$id;
    if ($action === 'bulk_delete') {
        $result = $supplierModel->deleteSupplier($id);
        $success = $success && $result['success'];
    } elseif ($action === 'bulk_activate') {
        $result = $supplierModel->updateSupplier($id, ['status' => 1]);
        $success = $success && $result['success'];
    } elseif ($action === 'bulk_deactivate') {
        $result = $supplierModel->updateSupplier($id, ['status' => 0]);
        $success = $success && $result['success'];
    }
}
if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
