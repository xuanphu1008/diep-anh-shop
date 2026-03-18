<?php
// api/cart-handler.php - API xử lý giỏ hàng

// CORS headers - Cho phép cross-origin requests
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Access-Control-Allow-Credentials: true');
header('Content-Type: application/json');

// Xử lý preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Khởi động session trước khi include config
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

try {
    require_once __DIR__ . '/../config/config.php';
    require_once __DIR__ . '/../includes/Database.php';
    require_once __DIR__ . '/../includes/functions.php';
    require_once __DIR__ . '/../models/Cart.php';
    require_once __DIR__ . '/../models/Product.php';

    $cartModel = new Cart();
    $productModel = new Product();
} catch (Exception $e) {
    error_log("Error loading files: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi khởi tạo: ' . $e->getMessage()]);
    exit;
} catch (Error $e) {
    error_log("Error loading files: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi khởi tạo: ' . $e->getMessage()]);
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? null;
$userId = $_SESSION['user_id'] ?? null;
$method = $_SERVER['REQUEST_METHOD'];

if (!$action) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Hành động không hợp lệ']);
    exit;
}

// Các hành động liên quan đến giỏ hàng
switch ($action) {
    case 'get':
        // Lấy giỏ hàng từ session hoặc database
        $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
        echo json_encode(['success' => true, 'cart' => $cart]);
        break;

    case 'add':
        // Thêm sản phẩm vào giỏ hàng
        $productId = intval($_POST['product_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);
        
        if ($productId <= 0 || $quantity <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        $product = $productModel->getProductById($productId);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không tìm thấy']);
            exit;
        }

        // Kiểm tra sản phẩm có đang bán và còn hàng không
        if (!$product['is_active'] || $product['deleted_at'] !== null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không khả dụng']);
            exit;
        }

        // Nếu user đã đăng nhập, lưu vào database
        if ($userId) {
            // Kiểm tra số lượng hiện tại trong giỏ hàng
            $existingItem = $cartModel->getCartItem($userId, $productId);
            $currentQuantity = ($existingItem && isset($existingItem['quantity'])) ? (int)$existingItem['quantity'] : 0;
            $newTotalQuantity = $currentQuantity + $quantity;
            
            // Kiểm tra tồn kho với tổng số lượng mới (chỉ kiểm tra nếu tồn kho > 0)
            if ($product['quantity'] > 0 && $product['quantity'] < $newTotalQuantity) {
                http_response_code(400);
                $available = max(0, $product['quantity'] - $currentQuantity);
                if ($available <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Sản phẩm đã hết hàng. Bạn đã có ' . $currentQuantity . ' sản phẩm trong giỏ hàng']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Số lượng sản phẩm không đủ. Bạn có thể thêm tối đa ' . $available . ' sản phẩm nữa']);
                }
                exit;
            }
            
            // Kiểm tra nếu tồn kho = 0
            if ($product['quantity'] <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Sản phẩm đã hết hàng']);
                exit;
            }
            
            $result = $cartModel->addToCart($userId, $productId, $quantity);
            if ($result['success']) {
                $cartCount = $cartModel->countCartItems($userId);
                echo json_encode(['success' => true, 'message' => $result['message'], 'cart_count' => $cartCount]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $result['message']]);
            }
        } else {
            // Chưa đăng nhập, lưu vào session
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            // Kiểm tra số lượng hiện tại trong session cart
            $currentQuantity = 0;
            foreach ($_SESSION['cart'] as $item) {
                if (isset($item['id']) && (int)$item['id'] === (int)$productId) {
                    $currentQuantity = isset($item['quantity']) ? (int)$item['quantity'] : 0;
                    break;
                }
            }
            
            $newTotalQuantity = $currentQuantity + $quantity;
            
            // Kiểm tra tồn kho với tổng số lượng mới (chỉ kiểm tra nếu tồn kho > 0)
            if ($product['quantity'] > 0 && $product['quantity'] < $newTotalQuantity) {
                http_response_code(400);
                $available = max(0, $product['quantity'] - $currentQuantity);
                if ($available <= 0) {
                    echo json_encode(['success' => false, 'message' => 'Sản phẩm đã hết hàng. Bạn đã có ' . $currentQuantity . ' sản phẩm trong giỏ hàng']);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Số lượng sản phẩm không đủ. Bạn có thể thêm tối đa ' . $available . ' sản phẩm nữa']);
                }
                exit;
            }
            
            // Kiểm tra nếu tồn kho = 0
            if ($product['quantity'] <= 0) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Sản phẩm đã hết hàng']);
                exit;
            }

            $found = false;
            foreach ($_SESSION['cart'] as &$item) {
                if (isset($item['id']) && (int)$item['id'] === (int)$productId) {
                    $item['quantity'] = (int)($item['quantity'] ?? 0) + $quantity;
                    $found = true;
                    break;
                }
            }

            if (!$found) {
                $_SESSION['cart'][] = [
                    'id' => $productId,
                    'name' => $product['name'] ?? '',
                    'price' => $product['price'] ?? 0,
                    'image' => $product['image'] ?? '',
                    'quantity' => $quantity
                ];
            }

            $cartCount = 0;
            foreach ($_SESSION['cart'] as $item) {
                $cartCount += $item['quantity'] ?? 1;
            }
            echo json_encode(['success' => true, 'message' => 'Thêm vào giỏ hàng thành công', 'cart_count' => $cartCount]);
        }
        break;

    case 'remove':
        // Xóa sản phẩm khỏi giỏ hàng
        $productId = intval($_POST['product_id'] ?? 0);
        
        if ($productId <= 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        // Nếu user đã đăng nhập, xóa từ database
        if ($userId) {
            $result = $cartModel->removeFromCart($userId, $productId);
            if ($result['success']) {
                $cartCount = $cartModel->countCartItems($userId);
                echo json_encode(['success' => true, 'message' => $result['message'], 'cart_count' => $cartCount]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $result['message']]);
            }
        } else {
            // Chưa đăng nhập, xóa từ session
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], function($item) use ($productId) {
                return !isset($item['id']) || (int)$item['id'] !== (int)$productId;
            }));

            $cartCount = 0;
            foreach ($_SESSION['cart'] as $item) {
                $cartCount += $item['quantity'] ?? 1;
            }
            echo json_encode(['success' => true, 'message' => 'Xóa khỏi giỏ hàng thành công', 'cart_count' => $cartCount]);
        }
        break;

    case 'update':
        // Cập nhật số lượng sản phẩm
        $productId = intval($_POST['product_id'] ?? 0);
        $quantity = intval($_POST['quantity'] ?? 1);
        
        // Log để debug
        error_log("Update cart - productId: $productId, quantity: $quantity, userId: " . ($userId ?? 'null'));
        
        if ($productId <= 0 || $quantity < 0) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        // Kiểm tra tồn kho
        $product = $productModel->getProductById($productId);
        if (!$product) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không tìm thấy']);
            exit;
        }

        // Kiểm tra sản phẩm có đang bán không
        if (!$product['is_active'] || $product['deleted_at'] !== null) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không khả dụng']);
            exit;
        }

        // Nếu user đã đăng nhập, cập nhật database
        if ($userId) {
            // Kiểm tra sản phẩm có trong giỏ hàng không
            $existingItem = $cartModel->getCartItem($userId, $productId);
            
            if ($quantity === 0) {
                $result = $cartModel->removeFromCart($userId, $productId);
            } else if (!$existingItem) {
                // Nếu không có trong giỏ và quantity > 0, thêm mới
                // Kiểm tra tồn kho trước khi thêm
                if ($product['quantity'] < $quantity) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Số lượng sản phẩm không đủ. Chỉ còn ' . $product['quantity'] . ' sản phẩm']);
                    exit;
                }
                $result = $cartModel->addToCart($userId, $productId, $quantity);
            } else {
                // Cập nhật số lượng - kiểm tra tồn kho với số lượng mới
                if ($product['quantity'] < $quantity) {
                    http_response_code(400);
                    echo json_encode(['success' => false, 'message' => 'Số lượng sản phẩm không đủ. Chỉ còn ' . $product['quantity'] . ' sản phẩm']);
                    exit;
                }
                $result = $cartModel->updateQuantity($userId, $productId, $quantity);
            }
            
            if ($result['success']) {
                $cartCount = $cartModel->countCartItems($userId);
                echo json_encode(['success' => true, 'message' => $result['message'], 'cart_count' => $cartCount]);
            } else {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => $result['message'] ?? 'Cập nhật số lượng thất bại']);
            }
        } else {
            // Chưa đăng nhập, cập nhật session
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }

            // Kiểm tra tồn kho trước khi cập nhật
            if ($quantity > 0 && $product['quantity'] < $quantity) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Số lượng sản phẩm không đủ. Chỉ còn ' . $product['quantity'] . ' sản phẩm']);
                exit;
            }

            if ($quantity === 0) {
                $_SESSION['cart'] = array_values(array_filter($_SESSION['cart'], function($item) use ($productId) {
                    return !isset($item['id']) || (int)$item['id'] !== (int)$productId;
                }));
            } else {
                $found = false;
                foreach ($_SESSION['cart'] as &$item) {
                    if (isset($item['id']) && (int)$item['id'] === (int)$productId) {
                        $item['quantity'] = (int)$quantity;
                        $found = true;
                        break;
                    }
                }
                if (!$found) {
                    $_SESSION['cart'][] = [
                        'id' => $productId,
                        'name' => $product['name'] ?? '',
                        'price' => $product['price'] ?? 0,
                        'image' => $product['image'] ?? '',
                        'quantity' => $quantity
                    ];
                }
            }

            $cartCount = 0;
            foreach ($_SESSION['cart'] as $item) {
                $cartCount += $item['quantity'] ?? 1;
            }
            echo json_encode(['success' => true, 'message' => 'Cập nhật thành công', 'cart_count' => $cartCount]);
        }
        break;

    case 'clear':
        // Xóa toàn bộ giỏ hàng
        if ($userId) {
            $result = $cartModel->clearCart($userId);
            if (is_array($result) && isset($result['message'])) {
                echo json_encode(['success' => true, 'message' => $result['message']]);
            } else {
                echo json_encode(['success' => true, 'message' => 'Giỏ hàng đã được làm trống']);
            }
        } else {
            $_SESSION['cart'] = [];
            echo json_encode(['success' => true, 'message' => 'Giỏ hàng đã được làm trống']);
        }
        break;

    case 'count':
        // Trả về số lượng sản phẩm trong giỏ hàng
        if ($userId) {
            $count = $cartModel->countCartItems($userId);
            echo json_encode(['success' => true, 'count' => $count]);
        } else {
            $cart = isset($_SESSION['cart']) ? $_SESSION['cart'] : [];
            $count = 0;
            foreach ($cart as $item) {
                $count += $item['quantity'] ?? 1;
            }
            echo json_encode(['success' => true, 'count' => $count]);
        }
        break;

    default:
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Hành động không được hỗ trợ']);
}
?>
