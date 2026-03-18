<?php
// Đảm bảo không có output trước header
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Contact.php';

// Xóa tất cả output buffer và set header
while (ob_get_level()) {
    ob_end_clean();
}
header('Content-Type: application/json; charset=utf-8');

// Chỉ chấp nhận POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

// Kiểm tra quyền
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập']);
    exit;
}

if (!isStaff()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Bạn không có quyền thực hiện hành động này']);
    exit;
}

// Lấy và parse JSON input
$rawInput = file_get_contents('php://input');

// Kiểm tra input có rỗng không
if (empty($rawInput)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không được để trống']);
    exit;
}

$data = json_decode($rawInput, true);

// Kiểm tra JSON decode có thành công không
if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    $errorMsg = json_last_error_msg();
    // Tránh lỗi khi json_last_error_msg() trả về null
    if (empty($errorMsg)) {
        $errorMsg = 'JSON parse error code: ' . json_last_error();
    }
    echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ: ' . $errorMsg]);
    exit;
}

$action = $data['action'] ?? '';
$ids = $data['ids'] ?? [];

// Kiểm tra action có hợp lệ không
if (empty($action)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Thiếu action']);
    exit;
}

// Kiểm tra ids có hợp lệ không
if (empty($ids) || !is_array($ids)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Vui lòng chọn ít nhất 1 liên hệ']);
    exit;
}

$contactModel = new Contact();

try {
    switch ($action) {
        case 'bulk_mark_read':
            $successCount = 0;
            $failedCount = 0;
            $errors = [];
            
            foreach ($ids as $id) {
                $id = (int)$id; // Validate ID
                if ($id > 0) {
                    $result = $contactModel->updateContactStatus($id, 'resolved');
                    if ($result && is_array($result) && isset($result['success']) && $result['success']) {
                        $successCount++;
                    } else {
                        $failedCount++;
                        $errors[] = "ID {$id}: " . (is_array($result) && isset($result['message']) ? $result['message'] : 'Cập nhật thất bại');
                    }
                } else {
                    $failedCount++;
                    $errors[] = "ID không hợp lệ: {$id}";
                }
            }
            
            if ($successCount > 0) {
                $message = "Đánh dấu đã đọc thành công {$successCount} liên hệ";
                if ($failedCount > 0) {
                    $message .= ". {$failedCount} liên hệ thất bại.";
                }
                echo json_encode(['success' => true, 'message' => $message, 'success_count' => $successCount, 'failed_count' => $failedCount]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Không thể đánh dấu đã đọc. ' . implode('; ', $errors), 'errors' => $errors]);
            }
            break;
            
        case 'bulk_delete':
            // Chỉ admin mới được xóa
            if (!isAdmin()) {
                http_response_code(403);
                echo json_encode(['success' => false, 'message' => 'Bạn không có quyền xóa liên hệ']);
                exit;
            }
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
