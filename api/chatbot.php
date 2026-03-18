<?php
// api/chatbot.php - Chatbot tư vấn tự động nâng cao

header('Content-Type: application/json');
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/Database.php';
require_once __DIR__ . '/../models/Product.php';

$input = json_decode(file_get_contents('php://input'), true);
$rawMessage = isset($input['message']) ? trim($input['message']) : '';

if ($rawMessage === '') {
    echo json_encode([
        'success' => false,
        'message' => 'Vui lòng nhập nội dung câu hỏi'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$normalizedMessage = normalizeMessage($rawMessage);
$productModel = new Product();
$responseSegments = [];
$suggestions = [];

$faqIntents = [
    'greeting' => [
        'keywords' => ['xin chào', 'chào bạn', 'hello', 'hi', 'hey', 'good morning', 'good afternoon'],
        'response' => 'Xin chào! Tôi là trợ lý ảo của Diệp Anh Computer. Tôi luôn sẵn sàng hỗ trợ bạn tìm sản phẩm hoặc giải đáp thắc mắc.'
    ],
    'about_shop' => [
        'keywords' => ['giới thiệu', 'diệp anh', 'shop', 'cửa hàng', 'điệp anh'],
        'response' => 'Diệp Anh Computer chuyên laptop, PC gaming và linh kiện chính hãng với giá tốt, bảo hành dài và giao hàng toàn quốc.'
    ],
    'promotion' => [
        'keywords' => ['khuyến mãi', 'giảm giá', 'ưu đãi', 'mã giảm'],
        'response' => 'Shop đang có nhiều chương trình khuyến mãi. Đăng ký tài khoản để nhận mã giảm giá tới 10% và theo dõi banner trang chủ để cập nhật ưu đãi mới.'
    ],
    'payment' => [
        'keywords' => ['thanh toán', 'cod', 'vnpay', 'visa', 'mastercard'],
        'response' => 'Bạn có thể thanh toán COD khi nhận hàng hoặc thanh toán online qua VNPay (ATM, Visa, MasterCard). Tất cả đều an toàn và tiện lợi.'
    ],
    'shipping' => [
        'keywords' => ['giao hàng', 'ship', 'vận chuyển', 'phí ship'],
        'response' => 'Shop giao hàng toàn quốc, nội thành Hà Nội trong 24h. Đơn từ 5 triệu được miễn phí ship, các đơn khác phí chỉ từ 20k.'
    ],
    'warranty' => [
        'keywords' => ['bảo hành', 'đổi trả', 'bảo trì'],
        'response' => 'Mọi sản phẩm đều được bảo hành chính hãng 12-36 tháng và hỗ trợ đổi trả trong 7 ngày nếu lỗi từ nhà sản xuất.'
    ],
    'contact' => [
        'keywords' => ['liên hệ', 'số điện thoại', 'email', 'hotline', 'địa chỉ'],
        'response' => 'Bạn có thể liên hệ Hotline 0123.456.789 (8:00 - 22:00) hoặc email admin@diepanhshop.com. Địa chỉ cửa hàng tại Hà Nội, Việt Nam.'
    ],
    'support' => [
        'keywords' => ['tư vấn', 'hỗ trợ', 'giúp', 'cần hỗ trợ'],
        'response' => 'Tôi có thể giúp bạn tìm sản phẩm phù hợp, tư vấn cấu hình, hướng dẫn đặt hàng hay cung cấp thông tin bảo hành. Cho tôi biết nhu cầu cụ thể nhé!'
    ]
];

$productIntents = [
    'laptop_gaming' => [
        'keywords' => ['laptop gaming', 'gaming laptop', 'máy chơi game', 'rog', 'tuf gaming', 'laptop chơi game'],
        'category_id' => 1,
        'search_keyword' => 'laptop gaming',
        'response' => 'Laptop Gaming của Diệp Anh tập trung vào hiệu năng đồ họa mạnh, màn hình tần số quét cao và hệ thống tản nhiệt tốt.'
    ],
    'laptop_office' => [
        'keywords' => ['laptop văn phòng', 'laptop mỏng nhẹ', 'laptop cho sinh viên', 'laptop office'],
        'category_id' => 2,
        'search_keyword' => 'laptop văn phòng',
        'response' => 'Laptop văn phòng ưu tiên thiết kế mỏng nhẹ, pin lâu và bảo mật tốt. Tôi sẽ gợi ý một vài mẫu phù hợp.'
    ],
    'pc_gaming' => [
        'keywords' => ['pc gaming', 'máy tính bàn chơi game', 'pc đồ hoạ', 'build pc', 'pc mạnh'],
        'category_id' => 3,
        'search_keyword' => 'pc gaming',
        'response' => 'PC Gaming của shop có cấu hình đa dạng từ tầm trung đến cao cấp, dễ nâng cấp và tối ưu airflow.'
    ],
    'components' => [
        'keywords' => ['linh kiện', 'card đồ họa', 'gpu', 'vga', 'cpu', 'mainboard', 'ssd', 'ram'],
        'category_id' => 4,
        'search_keyword' => 'linh kiện máy tính',
        'response' => 'Tôi có thể gợi ý nhanh các linh kiện phổ biến như RAM, SSD, card đồ họa và CPU chính hãng.'
    ]
];

$faqMatch = matchIntent($normalizedMessage, $faqIntents);
$productIntent = matchIntent($normalizedMessage, $productIntents);

if ($faqMatch) {
    $responseSegments[] = $faqMatch['response'];
}

if ($productIntent) {
    $responseSegments[] = $productIntent['response'];
}

$budgetInfo = extractBudgetRange($normalizedMessage);
if ($budgetInfo['description']) {
    $responseSegments[] = $budgetInfo['description'];
}

$categoryId = $productIntent['category_id'] ?? null;
$searchKeyword = $productIntent['search_keyword'] ?? $rawMessage;
$productLimit = 4;
$products = [];

$shouldSearchProducts = $productIntent !== null || $budgetInfo['has_budget'] || containsSearchCue($normalizedMessage);

if ($shouldSearchProducts) {
    $products = $productModel->searchProducts(
        $searchKeyword,
        $categoryId,
        $budgetInfo['min'],
        $budgetInfo['max'],
        $productLimit
    );
}

if (empty($products)) {
    $products = $productModel->searchProducts($rawMessage, $categoryId, null, null, $productLimit);
}

if (empty($products) && ($categoryId || $shouldSearchProducts)) {
    $products = $productModel->getHotProducts($productLimit);
    if (!empty($products)) {
        $responseSegments[] = 'Tôi chưa tìm thấy đúng sản phẩm bạn nhắc tới, nhưng đây là các sản phẩm nổi bật bạn có thể cân nhắc:';
    }
}

if (!empty($products)) {
    if (!$productIntent && !$faqMatch) {
        $responseSegments[] = 'Tôi tìm thấy một số lựa chọn phù hợp, bạn tham khảo thêm nhé:';
    }
    $suggestions = formatProductsForChat($products);
} else {
    $responseSegments[] = 'Hiện tôi chưa tìm được sản phẩm khớp mô tả. Bạn thử cho tôi biết rõ hơn nhu cầu (loại sản phẩm, ngân sách, mục đích sử dụng) nhé!';
}

if (empty($responseSegments)) {
    $responseSegments[] = 'Tôi có thể giúp bạn tìm laptop, PC gaming, linh kiện, thông tin giao hàng và bảo hành. Bạn cần hỗ trợ vấn đề nào?';
}

$responseText = implode("\n\n", uniqueSegments($responseSegments));

echo json_encode([
    'success' => true,
    'message' => $responseText,
    'products' => $suggestions,
    'timestamp' => time()
], JSON_UNESCAPED_UNICODE);

/**
 * Helpers
 */
function normalizeMessage($message) {
    $message = mb_strtolower($message, 'UTF-8');
    $message = preg_replace('/\s+/', ' ', $message);
    return trim($message);
}

function matchIntent($message, $intents) {
    foreach ($intents as $intent => $config) {
        foreach ($config['keywords'] as $keyword) {
            if (mb_strpos($message, $keyword) !== false) {
                $config['intent'] = $intent;
                return $config;
            }
        }
    }
    return null;
}

function containsSearchCue($message) {
    $cues = ['gợi ý', 'goi y', 'tìm', 'tim', 'mua', 'muốn', 'muon', 'chọn', 'chon', 'cần', 'can', 'đề xuất', 'de xuat', 'phù hợp', 'phu hop', 'loại', 'loai'];
    foreach ($cues as $cue) {
        if (mb_strpos($message, $cue) !== false) {
            return true;
        }
    }
    return false;
}

function extractBudgetRange($message) {
    $result = [
        'min' => null,
        'max' => null,
        'has_budget' => false,
        'description' => ''
    ];

    if (preg_match('/(?:từ|tu)\s*(\d+(?:[.,]\d+)?)\s*(tr|triệu|trieu|m)?\s*(?:đến|den|-|tới|toi)\s*(\d+(?:[.,]\d+)?)/u', $message, $matches)) {
        $result['min'] = convertBudgetValue($matches[1], $matches[2] ?? '');
        $result['max'] = convertBudgetValue($matches[3], $matches[2] ?? '');
    } elseif (preg_match('/(?:dưới|duoi|tối đa|toi da|<=|<)\s*(\d+(?:[.,]\d+)?)/u', $message, $matches)) {
        $result['max'] = convertBudgetValue($matches[1], $matches[2] ?? '');
    } elseif (preg_match('/(?:trên|tren|hơn|hon|>=|>|\btu\b)\s*(\d+(?:[.,]\d+)?)/u', $message, $matches)) {
        $result['min'] = convertBudgetValue($matches[1], $matches[2] ?? '');
    } elseif (preg_match('/(\d+(?:[.,]\d+)?)(?:\s*)(tr|triệu|trieu|m)/u', $message, $matches)) {
        $value = convertBudgetValue($matches[1], $matches[2]);
        $margin = 3000000;
        $result['min'] = max(0, $value - $margin);
        $result['max'] = $value + $margin;
    }

    if ($result['min'] !== null || $result['max'] !== null) {
        $result['has_budget'] = true;
        $minText = $result['min'] ? formatCurrency($result['min']) : '0đ';
        $maxText = $result['max'] ? formatCurrency($result['max']) : 'không giới hạn';
        if ($result['min'] && $result['max']) {
            $result['description'] = "Tôi sẽ ưu tiên các sản phẩm trong tầm giá $minText - $maxText.";
        } elseif ($result['max']) {
            $result['description'] = "Tôi sẽ gợi ý các sản phẩm dưới $maxText.";
        } else {
            $result['description'] = "Tôi sẽ tìm các sản phẩm từ $minText trở lên.";
        }
    }

    return $result;
}

function convertBudgetValue($value, $unit) {
    $unit = trim(mb_strtolower($unit ?? '', 'UTF-8'));
    $number = (float)str_replace([',', '.'], '', $value);

    if (in_array($unit, ['tr', 'triệu', 'trieu', 'm', 'trđ', 'trd'], true)) {
        $number *= 1000000;
    } elseif (in_array($unit, ['k', 'ngàn', 'ngan'], true)) {
        $number *= 1000;
    } elseif ($number < 1000) {
        // nếu người dùng chỉ nhập số (ví dụ: 15) thì mặc định là triệu
        $number *= 1000000;
    }

    return (int)$number;
}

function formatCurrency($value) {
    return number_format($value, 0, ',', '.') . 'đ';
}

function formatProductsForChat($products) {
    $suggestions = [];
    foreach ($products as $product) {
        $price = $product['discount_price'] ?? $product['price'];
        $suggestions[] = [
            'id' => $product['id'],
            'name' => $product['name'],
            'price' => formatCurrency($price),
            'image' => resolveProductImage($product['image'] ?? ''),
            'url' => SITE_URL . '/product-detail.php?slug=' . $product['slug']
        ];
    }
    return $suggestions;
}
function resolveProductImage($imagePath) {
    $default = SITE_URL . '/assets/images/products/default.jpg';
    if (empty($imagePath)) {
        return $default;
    }

    if (filter_var($imagePath, FILTER_VALIDATE_URL)) {
        return $imagePath;
    }

    $normalized = str_replace('\\', '/', ltrim($imagePath, '/'));
    $basename = basename($normalized);
    $siteRoot = realpath(__DIR__ . '/..') ?: __DIR__ . '/..';

    $candidates = [
        [
            'path' => $siteRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized),
            'url' => SITE_URL . '/' . $normalized
        ],
        [
            'path' => rtrim(UPLOAD_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalized),
            'url' => rtrim(UPLOAD_URL, '/') . '/' . $normalized
        ],
        [
            'path' => rtrim(UPLOAD_PATH, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . 'products' . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $basename),
            'url' => rtrim(UPLOAD_URL, '/') . '/products/' . $basename
        ],
        [
            'path' => __DIR__ . '/../assets/images/products/' . str_replace('/', DIRECTORY_SEPARATOR, $basename),
            'url' => SITE_URL . '/assets/images/products/' . $basename
        ]
    ];

    foreach ($candidates as $candidate) {
        if (file_exists($candidate['path'])) {
            return $candidate['url'];
        }
    }

    return $default;
}

function uniqueSegments($segments) {
    return array_values(array_unique(array_filter($segments)));
}

?>