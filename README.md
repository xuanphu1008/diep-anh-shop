# 🖥️ DIỆP ANH COMPUTER - WEBSITE BÁN MÁY TÍNH

Website quản lý và bán hàng máy tính chuyên nghiệp với đầy đủ tính năng từ khách hàng đến quản trị viên.

## 📋 Mục lục
- [Tính năng](#tính-năng)
- [Công nghệ sử dụng](#công-nghệ-sử-dụng)
- [Yêu cầu hệ thống](#yêu-cầu-hệ-thống)
- [Hướng dẫn cài đặt](#hướng-dẫn-cài-đặt)
- [Cấu trúc thư mục](#cấu-trúc-thư-mục)
- [Tài khoản mặc định](#tài-khoản-mặc-định)
- [API Documentation](#api-documentation)

## ✨ Tính năng

### Dành cho Khách hàng
- ✅ Đăng ký/Đăng nhập (nhận mã giảm giá chào mừng)
- ✅ Xem sản phẩm (Hot, Bán chạy, Giảm giá, Mới)
- ✅ Tìm kiếm và lọc sản phẩm
- ✅ Xem chi tiết sản phẩm
- ✅ Giỏ hàng (Thêm/Xóa/Sửa)
- ✅ Đặt hàng
- ✅ Thanh toán VNPay (ATM, Visa, MasterCard)
- ✅ Thanh toán COD
- ✅ Nhập mã giảm giá
- ✅ Xem lịch sử đơn hàng
- ✅ Xem trạng thái đơn hàng
- ✅ Nhận hóa đơn qua email
- ✅ Đổi mật khẩu
- ✅ Bình luận và đánh giá sản phẩm
- ✅ Chatbot tư vấn tự động
- ✅ Xem tin tức
- ✅ Liên hệ với shop (Email, Google Maps)

### Dành cho Admin & Staff
- ✅ Dashboard thống kê
- ✅ Quản lý sản phẩm (CRUD, khôi phục)
- ✅ Nhập hàng từ nhà cung cấp
- ✅ Ngừng kinh doanh sản phẩm
- ✅ Giảm giá sản phẩm
- ✅ Quản lý danh mục sản phẩm
- ✅ Quản lý nhà cung cấp
- ✅ Quản lý mã giảm giá
- ✅ Quản lý đơn hàng
- ✅ Duyệt trạng thái đơn hàng
- ✅ In đơn hàng
- ✅ Xem chi tiết đơn hàng
- ✅ Quản lý khách hàng
- ✅ Quản lý nhân viên
- ✅ Quản lý tin tức (CRUD)
- ✅ Quản lý banner
- ✅ Quản lý liên hệ
- ✅ Thống kê doanh thu theo tháng
- ✅ Biểu đồ doanh thu
- ✅ Thống kê sản phẩm bán chạy

## 💻 Công nghệ sử dụng

- **Backend:** PHP 7.4+ (Pure PHP, OOP)
- **Database:** MySQL 5.7+
- **Frontend:** HTML5, CSS3, JavaScript (Vanilla JS)
- **Icons:** Font Awesome 6.4.0
- **Payment Gateway:** VNPay
- **Email:** PHPMailer
- **Architecture:** MVC Pattern

## 📦 Yêu cầu hệ thống

- PHP >= 7.4
- MySQL >= 5.7
- Apache/Nginx Web Server
- Composer (cho PHPMailer)
- Extension PHP: mysqli, pdo, mbstring, json

## 🚀 Hướng dẫn cài đặt

### Bước 1: Clone hoặc tải project

```bash
git clone https://github.com/yourusername/diep-anh-shop.git
cd diep-anh-shop
```

### Bước 2: Cài đặt PHPMailer

```bash
composer require phpmailer/phpmailer
```

### Bước 3: Tạo database

```bash
mysql -u root -p
```

Sau đó import file SQL:

```sql
source database/diep_anh_shop.sql
```

Hoặc sử dụng phpMyAdmin để import file `database/diep_anh_shop.sql`

### Bước 4: Cấu hình

Mở file `config/config.php` và điều chỉnh thông tin:

```php
// Database
define('DB_HOST', 'localhost');
define('DB_NAME', 'diep_anh_shop');
define('DB_USER', 'root');
define('DB_PASS', '');

// Website
define('SITE_URL', 'http://localhost/diep-anh-shop');

// VNPay (Đăng ký tại vnpay.vn để lấy thông tin)
define('VNPAY_TMN_CODE', 'YOUR_TMN_CODE');
define('VNPAY_HASH_SECRET', 'YOUR_HASH_SECRET');

// Email SMTP
define('SMTP_USER', 'your_email@gmail.com');
define('SMTP_PASS', 'your_app_password');
```

### Bước 5: Tạo thư mục uploads

```bash
mkdir uploads
mkdir uploads/products
mkdir uploads/news
mkdir uploads/banners
chmod -R 755 uploads
```

### Bước 6: Chạy website

Truy cập: `http://localhost/diep-anh-shop`

## 📁 Cấu trúc thư mục

```
diep-anh-shop/
├── config/
│   └── config.php              # Cấu hình hệ thống
├── includes/
│   ├── Database.php            # Class kết nối database
│   ├── functions.php           # Helper functions
│   ├── VNPay.php              # VNPay integration
│   ├── mailer.php             # Email functions
│   ├── header.php             # Header template
│   └── footer.php             # Footer template
├── models/
│   ├── User.php               # Model người dùng
│   ├── Product.php            # Model sản phẩm
│   ├── Cart.php               # Model giỏ hàng
│   ├── Order.php              # Model đơn hàng
│   ├── Coupon.php             # Model mã giảm giá
│   ├── Category.php           # Model danh mục
│   ├── Supplier.php           # Model nhà cung cấp
│   ├── News.php               # Model tin tức
│   ├── Banner.php             # Model banner
│   ├── Contact.php            # Model liên hệ
│   └── Comment.php            # Model bình luận
├── customer/
│   ├── register.php           # Đăng ký
│   ├── login.php              # Đăng nhập
│   ├── logout.php             # Đăng xuất
│   ├── profile.php            # Thông tin cá nhân
│   ├── cart.php               # Giỏ hàng
│   ├── checkout.php           # Thanh toán
│   └── orders.php             # Lịch sử đơn hàng
├── admin/
│   ├── index.php              # Dashboard
│   ├── products/              # Quản lý sản phẩm
│   ├── orders/                # Quản lý đơn hàng
│   ├── users/                 # Quản lý người dùng
│   ├── categories/            # Quản lý danh mục
│   ├── suppliers/             # Quản lý NCC
│   ├── coupons/               # Quản lý mã giảm giá
│   ├── news/                  # Quản lý tin tức
│   ├── banners/               # Quản lý banner
│   ├── contacts/              # Quản lý liên hệ
│   └── statistics/            # Thống kê
├── api/
│   ├── cart-handler.php       # API giỏ hàng
│   ├── chatbot.php            # API chatbot
│   └── coupon-handler.php     # API mã giảm giá
├── assets/
│   ├── css/
│   │   └── style.css          # Main stylesheet
│   ├── js/
│   │   ├── main.js            # Main JavaScript
│   │   ├── cart.js            # Cart functions
│   │   └── chatbot.js         # Chatbot functions
│   └── images/
├── uploads/                    # Thư mục upload
│   ├── products/
│   ├── news/
│   └── banners/
├── index.php                   # Trang chủ
├── products.php                # Danh sách sản phẩm
├── product-detail.php          # Chi tiết sản phẩm
├── news.php                    # Tin tức
├── contact.php                 # Liên hệ
└── README.md
```

## 👤 Tài khoản mặc định

### Admin
- Username: `admin`
- Password: `password` (Hash: `$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi`)

### Staff
- Username: `staff`
- Password: `password`

**⚠️ LƯU Ý:** Đổi mật khẩu ngay sau khi cài đặt!

## 📡 API Documentation

### Cart API (`api/cart-handler.php`)

#### Thêm sản phẩm
```javascript
POST /api/cart-handler.php
{
  "action": "add",
  "product_id": 1,
  "quantity": 2
}
```

#### Cập nhật số lượng
```javascript
POST /api/cart-handler.php
{
  "action": "update",
  "product_id": 1,
  "quantity": 3
}
```

#### Xóa sản phẩm
```javascript
POST /api/cart-handler.php
{
  "action": "remove",
  "product_id": 1
}
```

#### Lấy số lượng giỏ hàng
```javascript
GET /api/cart-handler.php?action=count
```

### Chatbot API (`api/chatbot.php`)

```javascript
POST /api/chatbot.php
{
  "message": "laptop gaming giá rẻ"
}

Response:
{
  "success": true,
  "message": "Tôi tìm thấy 5 sản phẩm...",
  "products": [...]
}
```

## 🔒 Bảo mật

- ✅ CSRF Token protection
- ✅ Password hashing (bcrypt)
- ✅ SQL Injection prevention (PDO)
- ✅ XSS protection (htmlspecialchars)
- ✅ Input validation & sanitization
- ✅ Session security

## 📝 Ghi chú

1. **VNPay**: Cần đăng ký tài khoản test/production tại [vnpay.vn](https://vnpay.vn)
2. **Email**: Sử dụng App Password cho Gmail
3. **Upload**: Đảm bảo thư mục `uploads/` có quyền ghi (755)
4. **Production**: 
   - Tắt error reporting trong `config.php`
   - Đổi tất cả mật khẩu mặc định
   - Bật HTTPS
   - Cấu hình firewall

## 🐛 Báo lỗi

Nếu gặp lỗi, vui lòng tạo issue tại [GitHub Issues](https://github.com/yourusername/diep-anh-shop/issues)

## 📞 Liên hệ

- Email: admin@diepanhshop.com
- Website: https://diepanhshop.com

## 📄 License

MIT License - Copyright (c) 2025 Diệp Anh Computer

---

**Phát triển bởi:** Diệp Anh Team
**Version:** 1.0.0
**Last Updated:** 2025