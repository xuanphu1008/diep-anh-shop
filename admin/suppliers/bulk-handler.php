<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Supplier.php';

requireStaff();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$ids = $input['ids'] ?? [];

if (!is_array($ids) || !$action) {
    echo json_encode(['success' => false, 'message' => 'ThiÃƒÂ¡Ã‚ÂºÃ‚Â¿u dÃƒÂ¡Ã‚Â»Ã‚Â¯ liÃƒÂ¡Ã‚Â»Ã¢â‚¬Â¡u']);
    exit;
}

$supplierModel = new Supplier(); // Ãƒâ€žÃ‚ÂÃƒÆ’Ã†â€™ SÃƒÂ¡Ã‚Â»Ã‚Â¬A: tÃƒÂ¡Ã‚Â»Ã‚Â« Suppliers() thÃƒÆ’Ã‚Â nh Supplier()
$success = true;
foreach ($ids as $id) {
    $id = (int)$id;
    if ($action === 'bulk_delete') {
        // Chỉ admin mới được xóa
        if (!isAdmin()) {
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa nhà cung cấp']);
            exit;
        }
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
    echo json_encode(['success' => false, 'message' => 'CÃƒÆ’Ã‚Â³ lÃƒÂ¡Ã‚Â»Ã¢â‚¬â€i xÃƒÂ¡Ã‚ÂºÃ‚Â£y ra']);
}