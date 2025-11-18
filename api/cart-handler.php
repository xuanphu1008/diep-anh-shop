<?php
// api/cart-handler.php - API xử lý giỏ hàng

header('Content-Type: application/json');

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

// Debug log
error_log("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
error_log("POST data: " . json_encode($_POST));
error_log("GET data: " . json_encode($_GET));

// Lấy action từ request
$action = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
} else {
    $action = $_GET['action'] ?? '';
}

// Nếu action vẫn rỗng, thử lấy từ input stream
if (empty($action)) {
    $input = file_get_contents('php://input');
    error_log("Input stream: " . $input);
    if (!empty($input)) {
        $data = json_decode($input, true);
        if ($data && isset($data['action'])) {
            $action = $data['action'];
        }
    }
}

error_log("Action: " . $action);

// Lấy các tham số khác
$productId = 0;
$quantity = 1;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $productId = (int)($_POST['product_id'] ?? 0);
    $quantity = (int)($_POST['quantity'] ?? 1);
} elseif ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $productId = (int)($_GET['product_id'] ?? 0);
    $quantity = (int)($_GET['quantity'] ?? 1);
}

// Nếu không lấy được từ POST/GET, thử từ input stream
if ($productId == 0 && !empty($input)) {
    $data = json_decode($input, true);
    if ($data) {
        $productId = (int)($data['product_id'] ?? 0);
        $quantity = (int)($data['quantity'] ?? 1);
    }
}

// Kiểm tra người dùng đăng nhập
$userId = $_SESSION['user_id'] ?? null;

// Nếu action rỗng, không làm gì và trả về lỗi nhưng không log
if (empty($action)) {
    error_log("ERROR: Action is empty!");
    error_log("POST: " . json_encode($_POST));
    error_log("GET: " . json_encode($_GET));
    error_log("Input stream: " . $input);
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing action parameter']);
    exit;
}

// Set error handler để catch lỗi PHP
set_error_handler(function($errno, $errstr, $errfile, $errline) {
    error_log("PHP Error [$errno]: $errstr in $errfile on line $errline");
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Lỗi PHP: ' . $errstr]);
    exit;
});

switch ($action) {
    case 'add':
        // productId và quantity đã được lấy ở trên

        if ($productId <= 0 || $quantity <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu không hợp lệ']);
            exit;
        }

        // Kiểm tra sản phẩm tồn tại
        $product = $productModel->getProductById($productId);
        if (!$product || $product['is_active'] == 0 || $product['deleted_at'] != null) {
            echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại hoặc không còn bán']);
            exit;
        }

        // --- Logic kiểm tra tồn kho ĐÃ SỬA ---
        $currentCartQuantity = 0;
        if ($userId) {
            // Lấy số lượng hiện có trong giỏ hàng DB
            $existingItem = $cartModel->getCartItem($userId, $productId);
            if ($existingItem) {
                $currentCartQuantity = $existingItem['quantity'];
            }
        } else {
            // Lấy số lượng hiện có trong giỏ hàng session
            if (!isset($_SESSION['cart'])) {
                 $_SESSION['cart'] = []; // Khởi tạo nếu chưa có
            }
            $currentCartQuantity = $_SESSION['cart'][$productId] ?? 0;
        }
        $finalQuantity = $currentCartQuantity + $quantity; // Số lượng cuối cùng sau khi thêm

        if ($product['quantity'] < $finalQuantity) {
             echo json_encode(['success' => false, 'message' => "Sản phẩm không đủ số lượng (Chỉ còn {$product['quantity']} trong kho)"]);
             exit;
        }
        // --- Kết thúc kiểm tra tồn kho ---

        // Thêm vào giỏ hàng
        if ($userId) {
            // User đã đăng nhập - lưu vào database
            $result = $cartModel->addToCart($userId, $productId, $quantity);
        } else {
            // User chưa đăng nhập - lưu vào session
            $result = $cartModel->addToSessionCart($productId, $quantity);
        }

        // Thêm số lượng giỏ hàng vào response để JS cập nhật icon ngay lập tức
        $newCartCount = $cartModel->countCartItems($userId); // Sửa tên method đúng
        $result['cart_count'] = $newCartCount; // Thêm count vào kết quả trả về

        echo json_encode($result);
        break;

    case 'update':
        // productId và quantity đã được lấy ở trên
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu sản phẩm không hợp lệ']);
            exit;
        }
        if ($quantity < 0) { // Số lượng không được âm, nếu là 0 thì nên là remove
             echo json_encode(['success' => false, 'message' => 'Số lượng không hợp lệ']);
             exit;
        }

         // Kiểm tra tồn kho trước khi cập nhật
         $product = $productModel->getProductById($productId);
         if (!$product) {
             echo json_encode(['success' => false, 'message' => 'Sản phẩm không tồn tại']);
             exit;
         }
         if ($product['quantity'] < $quantity) {
              echo json_encode(['success' => false, 'message' => "Sản phẩm không đủ số lượng (Chỉ còn {$product['quantity']} trong kho)"]);
              exit;
         }


        if ($userId) {
            // Dùng hàm updateQuantity đã có trong Cart.php
             $result = $cartModel->updateQuantity($userId, $productId, $quantity);
        } else {
            // Cập nhật session trực tiếp
            if (!isset($_SESSION['cart'])) {
                $_SESSION['cart'] = [];
            }
             if ($quantity > 0) {
                 $_SESSION['cart'][$productId] = $quantity;
                 $result = ['success' => true, 'message' => 'Cập nhật giỏ hàng thành công'];
             } else {
                 // Nếu quantity là 0, xóa khỏi session
                 unset($_SESSION['cart'][$productId]);
                 $result = ['success' => true, 'message' => 'Đã xóa khỏi giỏ hàng'];
             }
        }
        echo json_encode($result);
        break;

    case 'remove':
        // productId đã được lấy ở trên
        if ($productId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Dữ liệu sản phẩm không hợp lệ']);
            exit;
        }

        if ($userId) {
            $result = $cartModel->removeFromCart($userId, $productId);
        } else {
            // Xóa khỏi session trực tiếp
            if (isset($_SESSION['cart'][$productId])) {
                unset($_SESSION['cart'][$productId]);
                $result = ['success' => true, 'message' => 'Đã xóa khỏi giỏ hàng'];
            } else {
                $result = ['success' => false, 'message' => 'Sản phẩm không có trong giỏ hàng'];
            }
        }
        echo json_encode($result);
        break;

    case 'clear':
        // Xóa toàn bộ giỏ hàng
        if ($userId) {
            $cartModel->clearCart($userId);
        } else {
            $_SESSION['cart'] = []; // Xóa session cart
        }
        echo json_encode(['success' => true, 'message' => 'Đã xóa toàn bộ giỏ hàng']);
        break;

    case 'count':
        // Đếm số lượng sản phẩm trong giỏ (Dùng GET)
        $count = 0;
        if ($userId) {
            $count = $cartModel->countCartItems($userId); // Sửa tên method đúng
        } else {
             $cart = $_SESSION['cart'] ?? [];
             $count = array_sum($cart); // Đếm tổng số lượng item trong session
        }
        echo json_encode(['success' => true, 'count' => $count]);
        break;

    case 'get':
        // Lấy giỏ hàng (Dùng GET)
         if ($userId) {
             // Hàm getCartDetails cần được kiểm tra hoặc tạo trong Cart.php
             // Giả sử nó trả về đúng cấu trúc {items: [], subtotal: n, item_count: m}
             $cart = $cartModel->getCartDetails($userId); // Cần đảm bảo hàm này tồn tại và đúng
             if ($cart === null) { // Xử lý trường hợp getCartDetails trả về null nếu lỗi
                  $cart = ['items' => [], 'subtotal' => 0, 'item_count' => 0];
             }
         } else {
             // Lấy từ session và định dạng lại
             $items = [];
             $subtotal = 0;
             $sessionCart = $_SESSION['cart'] ?? [];
             foreach ($sessionCart as $pid => $qty) {
                  $product = $productModel->getProductById($pid);
                  if ($product) {
                      $finalPrice = getFinalPrice($product['price'], $product['discount_price']);
                      $items[] = [
                           'product_id' => $pid,
                           'name' => $product['name'],
                           'slug' => $product['slug'],
                           'image' => getProductImage($product['image']), // Dùng helper function
                           'price' => $product['price'],
                           'discount_price' => $product['discount_price'],
                           'final_price' => $finalPrice,
                           'quantity' => $qty,
                           'stock_quantity' => $product['quantity'], // Cần cột này trong DB và model
                           'total' => $finalPrice * $qty
                      ];
                      $subtotal += $finalPrice * $qty;
                  }
             }
             $cart = ['items' => $items, 'subtotal' => $subtotal, 'item_count' => count($items)];
         }
        echo json_encode(['success' => true, 'cart' => $cart]);
        break;

    default:
        echo json_encode([
            'success' => false, 
            'message' => "Action không hợp lệ: '$action'. Các action hợp lệ: add, update, remove, clear, count, get"
        ]);
        break;
}
?>