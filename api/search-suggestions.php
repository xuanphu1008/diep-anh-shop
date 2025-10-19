<?php
require_once '../config/config.php';
require_once '../includes/Database.php';
require_once '../models/Product.php';
require_once '../includes/functions.php';

header('Content-Type: application/json');

if (!isset($_GET['keyword']) || empty($_GET['keyword'])) {
    echo json_encode(['success' => false, 'message' => 'Từ khóa không hợp lệ']);
    exit;
}

$keyword = trim($_GET['keyword']);
$productModel = new Product();
$suggestions = $productModel->searchProducts($keyword, 5); // Giới hạn 5 kết quả

$results = [];
foreach ($suggestions as $product) {
    $results[] = [
        'id' => $product['id'],
        'name' => $product['name'],
        'price' => $product['price'],
        'image' => getProductImage($product['image']),
        'slug' => $product['slug'],
        'discount_price' => $product['discount_price']
    ];
}

echo json_encode([
    'success' => true,
    'data' => $results
]);