<?php
// api/chatbot.php - Chatbot API cho tư vấn tự động

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../models/Product.php';

// Lấy message từ request
$input = json_decode(file_get_contents('php://input'), true);
$message = isset($input['message']) ? strtolower(trim($input['message'])) : '';

if (empty($message)) {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng nhập nội dung câu hỏi'
    ]);
    exit;
}

// Initialize
$productModel = new Product();
$response = '';
$suggestions = [];

// Từ khóa và câu trả lời
$keywords = [
    // Chào hỏi
    'xin chào' => 'Xin chào! Tôi là trợ lý ảo của Diệp Anh Computer. Tôi có thể giúp gì cho bạn?',
    'hello' => 'Hello! Chào mừng bạn đến với Diệp Anh Computer. Bạn cần hỗ trợ gì?',
    'hi' => 'Hi! Tôi có thể giúp bạn tìm laptop, PC gaming hoặc linh kiện máy tính. Bạn cần gì?',
    
    // Giới thiệu shop
    'giới thiệu' => 'Diệp Anh Computer là cửa hàng chuyên cung cấp laptop, PC gaming và linh kiện máy tính chính hãng. Chúng tôi cam kết sản phẩm chất lượng, giá tốt và bảo hành uy tín.',
    'shop' => 'Diệp Anh Computer - Địa chỉ tin cậy cho mọi nhu cầu về máy tính của bạn!',
    
    // Sản phẩm
    'laptop' => 'Chúng tôi có nhiều dòng laptop: Gaming, Văn phòng, Đồ họa. Bạn quan tâm dòng nào?',
    'gaming' => 'Laptop Gaming và PC Gaming của chúng tôi có cấu hình mạnh mẽ, phù hợp cho game thủ. Giá từ 15 triệu.',
    'văn phòng' => 'Laptop văn phòng nhẹ, pin trâu, giá từ 10 triệu đồng.',
    'pc' => 'PC Gaming của shop có nhiều cấu hình từ phổ thông đến cao cấp. Bạn có ngân sách bao nhiêu?',
    'linh kiện' => 'Chúng tôi có đầy đủ linh kiện: RAM, SSD, VGA, CPU, Mainboard... Bạn cần linh kiện gì?',
    
    // Giá cả
    'giá' => 'Sản phẩm của chúng tôi có nhiều mức giá phù hợp với mọi túi tiền. Bạn có thể cho tôi biết ngân sách của bạn?',
    'rẻ' => 'Chúng tôi có nhiều sản phẩm giá tốt, thường xuyên có chương trình khuyến mãi. Hãy xem mục "Sản phẩm giảm giá"!',
    'khuyến mãi' => 'Hiện tại shop đang có nhiều chương trình khuyến mãi hấp dẫn. Đăng ký tài khoản để nhận mã giảm giá 10%!',
    'giảm giá' => 'Mục "Sản phẩm giảm giá" luôn được cập nhật. Đừng bỏ lỡ!',
    
    // Thanh toán
    'thanh toán' => 'Shop hỗ trợ thanh toán COD và thanh toán online qua VNPay (ATM, Visa, MasterCard).',
    'cod' => 'Bạn có thể chọn thanh toán khi nhận hàng (COD) khi đặt hàng.',
    'vnpay' => 'Chúng tôi hỗ trợ thanh toán VNPay qua thẻ ATM, Visa, MasterCard rất tiện lợi.',
    
    // Giao hàng
    'giao hàng' => 'Shop giao hàng toàn quốc, nội thành Hà Nội trong 24h. Miễn phí ship cho đơn từ 5 triệu.',
    'ship' => 'Phí ship từ 20k - 50k tùy khu vực. Miễn phí ship cho đơn từ 5 triệu đồng.',
    
    // Bảo hành
    'bảo hành' => 'Tất cả sản phẩm được bảo hành chính hãng từ 12-36 tháng tùy sản phẩm.',
    'đổi trả' => 'Shop hỗ trợ đổi trả trong 7 ngày nếu sản phẩm lỗi từ nhà sản xuất.',
    
    // Liên hệ
    'liên hệ' => 'Bạn có thể liên hệ qua Hotline: 0123.456.789 hoặc Email: admin@diepanhshop.com',
    'số điện thoại' => 'Hotline: 0123.456.789 (8:00 - 22:00 hàng ngày)',
    'email' => 'Email: admin@diepanhshop.com',
    'địa chỉ' => 'Địa chỉ: Hà Nội, Việt Nam. Xem chi tiết tại trang Liên hệ.',
    
    // Hỗ trợ
    'tư vấn' => 'Tôi sẵn sàng tư vấn cho bạn! Bạn cần tìm laptop hay PC gaming? Ngân sách bao nhiêu?',
    'giúp' => 'Tôi có thể giúp bạn: Tìm sản phẩm, Tư vấn cấu hình, Hướng dẫn đặt hàng, Chính sách bảo hành.',
    'hỗ trợ' => 'Tôi luôn sẵn sàng hỗ trợ bạn! Bạn cần giúp đỡ về vấn đề gì?'
];

// Tìm kiếm từ khóa trong message
$found = false;
foreach ($keywords as $keyword => $reply) {
    if (strpos($message, $keyword) !== false) {
        $response = $reply;
        $found = true;
        break;
    }
}

// Tìm kiếm sản phẩm nếu không match keywords
if (!$found) {
    // Tìm sản phẩm theo tên
    $products = $productModel->searchProducts($message, null, null, null);
    
    if (!empty($products)) {
        $response = "Tôi tìm thấy " . count($products) . " sản phẩm phù hợp:\n\n";
        
        $count = 0;
        foreach ($products as $product) {
            if ($count >= 3) break; // Chỉ hiển thị 3 sản phẩm đầu
            
            $price = $product['discount_price'] ?? $product['price'];
            $suggestions[] = [
                'id' => $product['id'],
                'name' => $product['name'],
                'price' => number_format($price) . 'đ',
                'image' => UPLOAD_URL . $product['image'],
                'url' => SITE_URL . '/product-detail.php?slug=' . $product['slug']
            ];
            
            $count++;
        }
        
        $response .= "Bạn có thể xem chi tiết các sản phẩm bên dưới.";
    } else {
        // Câu trả lời mặc định
        $response = "Xin lỗi, tôi chưa hiểu câu hỏi của bạn. Bạn có thể hỏi tôi về:\n" .
                   "• Sản phẩm laptop, PC gaming, linh kiện\n" .
                   "• Giá cả và khuyến mãi\n" .
                   "• Thanh toán và giao hàng\n" .
                   "• Bảo hành và đổi trả\n" .
                   "Hoặc liên hệ Hotline: 0123.456.789 để được hỗ trợ trực tiếp.";
    }
}

// Trả về response
echo json_encode([
    'success' => true,
    'message' => $response,
    'products' => $suggestions,
    'timestamp' => time()
], JSON_UNESCAPED_UNICODE);
?>