<?php
// Start output buffering FIRST to catch any output
ob_start();

// Turn off error display to prevent HTML output
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    // Load config first (this will start session if needed)
    // api/ is one level down from root, so we need ../ to go up one level
    require_once dirname(__DIR__) . '/config/config.php';
    require_once dirname(__DIR__) . '/includes/Database.php';
    require_once dirname(__DIR__) . '/includes/functions.php';
    require_once dirname(__DIR__) . '/models/Rating.php';
    require_once dirname(__DIR__) . '/models/Product.php';
    
    // Clear any output that might have been generated
    ob_clean();
    
    // NOW set JSON header after session is started
    header('Content-Type: application/json; charset=utf-8');
    
} catch (Exception $e) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    exit();
} catch (Error $e) {
    ob_clean();
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'message' => 'Lỗi hệ thống: ' . $e->getMessage()]);
    exit();
}

try {
    // Check authentication without redirect
    if (!isLoggedIn()) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Vui lòng đăng nhập để đánh giá'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    if (!isCustomer()) {
        ob_clean();
        echo json_encode(['success' => false, 'message' => 'Chỉ khách hàng có thể đánh giá sản phẩm'], JSON_UNESCAPED_UNICODE);
        exit();
    }

    $ratingModel = new Rating();
    $productModel = new Product();
    $response = ['success' => false, 'message' => ''];

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        
        // Thêm đánh giá mới (từ widget)
        if ($action === 'submit_rating' || $action === 'add') {
            $product_id = (int)($_POST['product_id'] ?? 0);
            $rating = (int)($_POST['rating'] ?? 0);
            $content = isset($_POST['content']) ? sanitizeInput($_POST['content']) : '';
            
            if (!$product_id || !$rating || $rating < 1 || $rating > 5) {
                $response = ['success' => false, 'message' => 'Vui lòng chọn số sao đánh giá (1-5 sao)'];
            } else {
                // Cho phép đánh giá nhiều lần - luôn tạo mới
                $data = [
                    'product_id' => $product_id,
                    'user_id' => $_SESSION['user_id'],
                    'content' => $content,
                    'rating' => $rating,
                    'status' => 1 // Tự động hiển thị, không cần duyệt
                ];
                $result = $ratingModel->addRating($data);
                $response = $result;
                if ($result['success']) {
                    $response['message'] = 'Đánh giá thành công. Cảm ơn bạn đã đánh giá sản phẩm!';
                }
            }
        }
        
        // Cập nhật đánh giá
        elseif ($action === 'update') {
            $rating_id = (int)($_POST['rating_id'] ?? 0);
            $rating = (int)($_POST['rating'] ?? 0);
            $content = isset($_POST['content']) ? sanitizeInput($_POST['content']) : '';
            
            $existingRating = $ratingModel->getRatingById($rating_id);
            if (!$existingRating || $existingRating['user_id'] != $_SESSION['user_id']) {
                $response = ['success' => false, 'message' => 'Không có quyền cập nhật'];
            } elseif (!$rating || $rating < 1 || $rating > 5) {
                $response = ['success' => false, 'message' => 'Dữ liệu không hợp lệ'];
            } else {
                $data = [
                    'content' => $content,
                    'rating' => $rating,
                    'status' => 1 // Tự động hiển thị
                ];
                $ratingModel->updateRating($rating_id, $data);
                $response = ['success' => true, 'message' => 'Cập nhật đánh giá thành công'];
            }
        }
        
        // Xóa đánh giá
        elseif ($action === 'delete' || $action === 'delete_rating') {
            $rating_id = (int)($_POST['rating_id'] ?? 0);
            
            if (!$rating_id) {
                $response = ['success' => false, 'message' => 'ID đánh giá không hợp lệ'];
            } else {
                $existingRating = $ratingModel->getRatingById($rating_id);
                if (!$existingRating || $existingRating['user_id'] != $_SESSION['user_id']) {
                    $response = ['success' => false, 'message' => 'Không có quyền xóa đánh giá này'];
                } else {
                    $ratingModel->deleteRating($rating_id);
                    $response = ['success' => true, 'message' => 'Xóa đánh giá thành công'];
                }
            }
        } else {
            $response = ['success' => false, 'message' => 'Hành động không hợp lệ'];
        }
    } else {
        $response = ['success' => false, 'message' => 'Phương thức không được phép'];
    }

    // Clear any remaining output
    ob_clean();

    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit();
    
} catch (Exception $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi hệ thống: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
    exit();
} catch (Error $e) {
    ob_clean();
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Lỗi hệ thống: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ], JSON_UNESCAPED_UNICODE);
    exit();
}
