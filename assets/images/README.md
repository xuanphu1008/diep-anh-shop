# 📸 Hướng dẫn sử dụng ảnh sản phẩm

## Cấu trúc thư mục

```
assets/images/
├── products/          # Ảnh sản phẩm
│   ├── default.jpg   # Ảnh mặc định
│   ├── laptop-gaming-1.jpg
│   ├── laptop-gaming-2.jpg
│   └── ...
├── categories/        # Ảnh danh mục
│   ├── default-category.jpg
│   ├── laptop-gaming.jpg
│   └── ...
├── banners/          # Ảnh banner
│   ├── default-banner.jpg
│   ├── banner-laptop-gaming.jpg
│   └── ...
└── news/            # Ảnh tin tức
    ├── default-news.jpg
    ├── news-laptop-gaming-2024.jpg
    └── ...
```

## Cách sử dụng trong PHP

### 1. Hiển thị ảnh sản phẩm đơn

```php
// Lấy URL ảnh sản phẩm
$imageUrl = getProductImage($product['image']);

// Hiển thị ảnh
echo '<img src="' . $imageUrl . '" alt="' . $product['name'] . '">';

// Hoặc sử dụng helper function
echo generateImageHTML($imageUrl, $product['name'], 'product-image');
```

### 2. Hiển thị nhiều ảnh sản phẩm

```php
// Lấy mảng ảnh
$images = getProductImages($product['images']);

// Hiển thị gallery
foreach ($images as $image) {
    echo '<img src="' . $image . '" alt="' . $product['name'] . '">';
}
```

### 3. Hiển thị ảnh danh mục

```php
$categoryImage = getCategoryImage($category['image']);
echo '<img src="' . $categoryImage . '" alt="' . $category['name'] . '">';
```

### 4. Hiển thị ảnh banner

```php
$bannerImage = getBannerImage($banner['image']);
echo '<img src="' . $bannerImage . '" alt="' . $banner['title'] . '">';
```

### 5. Hiển thị ảnh tin tức

```php
$newsImage = getNewsImage($news['image']);
echo '<img src="' . $newsImage . '" alt="' . $news['title'] . '">';
```

## Lazy Loading

```php
// Sử dụng lazy loading
echo generateLazyImageHTML(
    getProductImage($product['image']), 
    $product['name'], 
    'product-image',
    'assets/images/placeholder.jpg'
);
```

## Responsive Images

```php
// Tạo responsive image
echo generateImageHTML(
    getProductImage($product['image']),
    $product['name'],
    'product-image',
    [
        'width' => '300',
        'height' => '200',
        'loading' => 'lazy'
    ]
);
```

## Quy tắc đặt tên file

- **Sản phẩm**: `product-name-1.jpg`, `product-name-2.jpg`
- **Danh mục**: `category-name.jpg`
- **Banner**: `banner-name.jpg`
- **Tin tức**: `news-title.jpg`

## Kích thước ảnh khuyến nghị

- **Sản phẩm**: 800x600px (tỷ lệ 4:3)
- **Danh mục**: 400x300px
- **Banner**: 1200x400px (tỷ lệ 3:1)
- **Tin tức**: 800x450px (tỷ lệ 16:9)

## Định dạng hỗ trợ

- JPG/JPEG (khuyến nghị)
- PNG (cho ảnh có trong suốt)
- WebP (hiệu suất tốt nhất)
- GIF (cho ảnh động)

## Tối ưu hóa

1. **Nén ảnh**: Sử dụng tools như TinyPNG, ImageOptim
2. **Lazy Loading**: Sử dụng `loading="lazy"`
3. **Responsive**: Sử dụng CSS `max-width: 100%`
4. **Alt text**: Luôn có alt text mô tả ảnh
