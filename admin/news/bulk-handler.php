<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/News.php';

requireStaff();
header('Content-Type: application/json');

$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';
$ids = $input['ids'] ?? [];

if (!is_array($ids) || !$action) {
    echo json_encode(['success' => false, 'message' => 'Thiếu dữ liệu']);
    exit;
}

$newsModel = new News();
$success = true;
foreach ($ids as $id) {
    $id = (int)$id;
    if ($action === 'bulk_delete') {
        $newsModel->deleteNews($id);
        $success = $success && true;
    } elseif ($action === 'bulk_publish') {
        $newsModel->updateNews($id, ['status' => 1]);
        $success = $success && true;
    } elseif ($action === 'bulk_hide') {
        $newsModel->updateNews($id, ['status' => 0]);
        $success = $success && true;
    }
}
if ($success) {
    echo json_encode(['success' => true]);
} else {
    echo json_encode(['success' => false, 'message' => 'Có lỗi xảy ra']);
}
