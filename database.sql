-- Database: diep_anh_shop
CREATE DATABASE IF NOT EXISTS diep_anh_shop CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE diep_anh_shop;

-- Bảng người dùng
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    full_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    role ENUM('customer', 'staff', 'admin') DEFAULT 'customer',
    status TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Bảng danh mục sản phẩm
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    status TINYINT DEFAULT 1,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng nhà cung cấp
CREATE TABLE suppliers (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    status TINYINT DEFAULT 1,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng sản phẩm
CREATE TABLE products (
    id INT PRIMARY KEY AUTO_INCREMENT,
    category_id INT,
    supplier_id INT,
    name VARCHAR(200) NOT NULL,
    slug VARCHAR(200) UNIQUE NOT NULL,
    description TEXT,
    specifications JSON,
    price DECIMAL(15,2) NOT NULL,
    discount_price DECIMAL(15,2),
    quantity INT DEFAULT 0,
    sold_quantity INT DEFAULT 0,
    image VARCHAR(255),
    images JSON,
    is_hot TINYINT DEFAULT 0,
    is_active TINYINT DEFAULT 1,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id)
);

-- Bảng nhập hàng
CREATE TABLE product_imports (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT,
    supplier_id INT,
    quantity INT NOT NULL,
    import_price DECIMAL(15,2) NOT NULL,
    total_price DECIMAL(15,2) NOT NULL,
    note TEXT,
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (supplier_id) REFERENCES suppliers(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);

-- Bảng mã giảm giá
CREATE TABLE coupons (
    id INT PRIMARY KEY AUTO_INCREMENT,
    code VARCHAR(50) UNIQUE NOT NULL,
    type ENUM('percent', 'fixed') DEFAULT 'percent',
    value DECIMAL(10,2) NOT NULL,
    min_order_value DECIMAL(15,2) DEFAULT 0,
    max_discount DECIMAL(15,2),
    quantity INT DEFAULT 1,
    used_quantity INT DEFAULT 0,
    start_date DATETIME,
    end_date DATETIME,
    status TINYINT DEFAULT 1,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng đơn hàng
CREATE TABLE orders (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    order_code VARCHAR(50) UNIQUE NOT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_email VARCHAR(100),
    customer_phone VARCHAR(20) NOT NULL,
    customer_address TEXT NOT NULL,
    coupon_id INT,
    coupon_discount DECIMAL(15,2) DEFAULT 0,
    subtotal DECIMAL(15,2) NOT NULL,
    total DECIMAL(15,2) NOT NULL,
    payment_method ENUM('cod', 'vnpay') DEFAULT 'cod',
    payment_status ENUM('pending', 'paid', 'failed') DEFAULT 'pending',
    order_status ENUM('pending', 'confirmed', 'processing', 'shipping', 'delivered', 'cancelled') DEFAULT 'pending',
    note TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (coupon_id) REFERENCES coupons(id)
);

-- Bảng chi tiết đơn hàng
CREATE TABLE order_details (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    product_id INT,
    product_name VARCHAR(200),
    product_price DECIMAL(15,2),
    quantity INT NOT NULL,
    total DECIMAL(15,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id),
    FOREIGN KEY (product_id) REFERENCES products(id)
);

-- Bảng giỏ hàng
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    product_id INT,
    quantity INT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (product_id) REFERENCES products(id),
    UNIQUE KEY unique_cart_item (user_id, product_id)
);

-- Bảng bình luận
CREATE TABLE comments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    product_id INT,
    user_id INT,
    content TEXT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    status TINYINT DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id),
    FOREIGN KEY (user_id) REFERENCES users(id)
);

-- Bảng tin tức
CREATE TABLE news (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(255) NOT NULL,
    slug VARCHAR(255) UNIQUE NOT NULL,
    content TEXT NOT NULL,
    image VARCHAR(255),
    author_id INT,
    views INT DEFAULT 0,
    status TINYINT DEFAULT 1,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (author_id) REFERENCES users(id)
);

-- Bảng banner
CREATE TABLE banners (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200),
    image VARCHAR(255) NOT NULL,
    link VARCHAR(255),
    position INT DEFAULT 0,
    status TINYINT DEFAULT 1,
    deleted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng liên hệ
CREATE TABLE contacts (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20),
    subject VARCHAR(200),
    message TEXT NOT NULL,
    status ENUM('pending', 'processing', 'resolved') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Bảng thanh toán VNPay
CREATE TABLE vnpay_transactions (
    id INT PRIMARY KEY AUTO_INCREMENT,
    order_id INT,
    transaction_no VARCHAR(50),
    bank_code VARCHAR(20),
    amount DECIMAL(15,2),
    order_info TEXT,
    transaction_status VARCHAR(20),
    response_code VARCHAR(10),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id)
);

-- Thêm dữ liệu mẫu
INSERT INTO users (username, email, password, full_name, role) VALUES
('admin', 'admin@diepanhshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrator', 'admin'),
('staff', 'staff@diepanhshop.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nhân viên', 'staff');

INSERT INTO categories (name, slug, description) VALUES
('Laptop Gaming', 'laptop-gaming', 'Laptop chuyên game hiệu năng cao'),
('Laptop Văn phòng', 'laptop-van-phong', 'Laptop phù hợp công việc văn phòng'),
('PC Gaming', 'pc-gaming', 'Máy tính để bàn chuyên game'),
('Linh kiện máy tính', 'linh-kien-may-tinh', 'Linh kiện nâng cấp máy tính');

INSERT INTO suppliers (name, email, phone, address) VALUES
('ASUS Việt Nam', 'contact@asus.vn', '0123456789', 'Hà Nội'),
('Dell Việt Nam', 'contact@dell.vn', '0123456790', 'TP.HCM'),
('MSI Việt Nam', 'contact@msi.vn', '0123456791', 'Hà Nội');

-- Dữ liệu mẫu sản phẩm
INSERT INTO products (category_id, supplier_id, name, slug, description, specifications, price, discount_price, quantity, sold_quantity, image, images, is_hot, is_active) VALUES
-- Laptop Gaming
(1, 1, 'ASUS ROG Strix G15 G513IH-HN015T', 'asus-rog-strix-g15-g513ih', 
'Laptop gaming ASUS ROG Strix G15 với hiệu năng mạnh mẽ, phù hợp cho game thủ và đồ họa chuyên nghiệp.',
'{"CPU": "AMD Ryzen 7 4800H", "RAM": "16GB DDR4", "Storage": "512GB SSD", "GPU": "NVIDIA GeForce RTX 3050 4GB", "Display": "15.6 inch FHD 144Hz", "OS": "Windows 11 Home"}',
25990000, 23990000, 15, 8, 'laptop-gaming-1.jpg', 
'["laptop-gaming-1.jpg", "laptop-gaming-1-2.jpg", "laptop-gaming-1-3.jpg"]', 1, 1),

(1, 3, 'MSI Gaming GF63 Thin 11SC-664VN', 'msi-gaming-gf63-thin-11sc',
'Laptop gaming MSI GF63 Thin với thiết kế mỏng nhẹ, hiệu năng ổn định cho gaming và làm việc.',
'{"CPU": "Intel Core i5-11400H", "RAM": "8GB DDR4", "Storage": "512GB SSD", "GPU": "NVIDIA GeForce GTX 1650 4GB", "Display": "15.6 inch FHD 60Hz", "OS": "Windows 11 Home"}',
18990000, 17990000, 20, 12, 'laptop-gaming-2.jpg',
'["laptop-gaming-2.jpg", "laptop-gaming-2-2.jpg"]', 0, 1),

(1, 1, 'ASUS TUF Gaming A15 FA506ICB-HN144T', 'asus-tuf-gaming-a15-fa506icb',
'Laptop gaming ASUS TUF với độ bền cao, thiết kế quân đội, phù hợp cho game thủ chuyên nghiệp.',
'{"CPU": "AMD Ryzen 5 4600H", "RAM": "8GB DDR4", "Storage": "512GB SSD", "GPU": "NVIDIA GeForce RTX 3050 4GB", "Display": "15.6 inch FHD 144Hz", "OS": "Windows 11 Home"}',
22990000, 21990000, 18, 5, 'laptop-gaming-3.jpg',
'["laptop-gaming-3.jpg", "laptop-gaming-3-2.jpg", "laptop-gaming-3-3.jpg"]', 1, 1),

-- Laptop Văn phòng
(2, 2, 'Dell Inspiron 15 3511 i5-1135G7', 'dell-inspiron-15-3511-i5',
'Laptop văn phòng Dell Inspiron 15 với hiệu năng ổn định, phù hợp cho công việc văn phòng và học tập.',
'{"CPU": "Intel Core i5-1135G7", "RAM": "8GB DDR4", "Storage": "512GB SSD", "GPU": "Intel Iris Xe Graphics", "Display": "15.6 inch FHD", "OS": "Windows 11 Home"}',
15990000, 14990000, 25, 15, 'laptop-vanphong-1.jpg',
'["laptop-vanphong-1.jpg", "laptop-vanphong-1-2.jpg"]', 0, 1),

(2, 1, 'ASUS VivoBook S15 S533EA-BQ011T', 'asus-vivobook-s15-s533ea',
'Laptop văn phòng ASUS VivoBook S15 với thiết kế sang trọng, hiệu năng tốt cho công việc hàng ngày.',
'{"CPU": "Intel Core i5-1135G7", "RAM": "8GB DDR4", "Storage": "512GB SSD", "GPU": "Intel Iris Xe Graphics", "Display": "15.6 inch FHD", "OS": "Windows 11 Home"}',
16990000, 15990000, 22, 10, 'laptop-vanphong-2.jpg',
'["laptop-vanphong-2.jpg", "laptop-vanphong-2-2.jpg", "laptop-vanphong-2-3.jpg"]', 1, 1),

(2, 2, 'Dell Latitude 3520 i5-1135G7', 'dell-latitude-3520-i5',
'Laptop doanh nhân Dell Latitude với độ bền cao, bảo mật tốt, phù hợp cho môi trường công ty.',
'{"CPU": "Intel Core i5-1135G7", "RAM": "8GB DDR4", "Storage": "256GB SSD", "GPU": "Intel Iris Xe Graphics", "Display": "15.6 inch FHD", "OS": "Windows 11 Pro"}',
18990000, 17990000, 12, 3, 'laptop-vanphong-3.jpg',
'["laptop-vanphong-3.jpg", "laptop-vanphong-3-2.jpg"]', 0, 1),

-- PC Gaming
(3, 1, 'PC Gaming ASUS ROG Strix G10DK-R5G1650', 'pc-gaming-asus-rog-strix-g10dk',
'PC Gaming ASUS ROG Strix với hiệu năng mạnh mẽ, thiết kế gaming chuyên nghiệp.',
'{"CPU": "AMD Ryzen 5 3600", "RAM": "16GB DDR4", "Storage": "512GB SSD + 1TB HDD", "GPU": "NVIDIA GeForce GTX 1650 4GB", "PSU": "500W 80+ Bronze", "Case": "ASUS ROG Strix"}',
18990000, 17990000, 8, 4, 'pc-gaming-1.jpg',
'["pc-gaming-1.jpg", "pc-gaming-1-2.jpg", "pc-gaming-1-3.jpg"]', 1, 1),

(3, 3, 'PC Gaming MSI Infinite S3 11SI-001VN', 'pc-gaming-msi-infinite-s3',
'PC Gaming MSI Infinite S3 với thiết kế compact, hiệu năng ổn định cho gaming.',
'{"CPU": "Intel Core i5-11400F", "RAM": "8GB DDR4", "Storage": "512GB SSD", "GPU": "NVIDIA GeForce GTX 1650 4GB", "PSU": "400W 80+ Bronze", "Case": "MSI Infinite S3"}',
15990000, 14990000, 10, 6, 'pc-gaming-2.jpg',
'["pc-gaming-2.jpg", "pc-gaming-2-2.jpg"]', 0, 1),

(3, 1, 'PC Gaming ASUS ROG Strix G15CE-R5G3060', 'pc-gaming-asus-rog-strix-g15ce',
'PC Gaming cao cấp với RTX 3060, hiệu năng vượt trội cho gaming 4K và streaming.',
'{"CPU": "AMD Ryzen 5 5600G", "RAM": "16GB DDR4", "Storage": "1TB SSD", "GPU": "NVIDIA GeForce RTX 3060 12GB", "PSU": "650W 80+ Gold", "Case": "ASUS ROG Strix Helios"}',
28990000, 26990000, 5, 2, 'pc-gaming-3.jpg',
'["pc-gaming-3.jpg", "pc-gaming-3-2.jpg", "pc-gaming-3-3.jpg"]', 1, 1),

-- Linh kiện máy tính
(4, 1, 'Card đồ họa ASUS ROG Strix RTX 3060 12GB', 'card-dohoa-asus-rog-strix-rtx3060',
'Card đồ họa gaming cao cấp với hiệu năng ray tracing, phù hợp cho gaming 1440p và 4K.',
'{"GPU": "NVIDIA GeForce RTX 3060", "VRAM": "12GB GDDR6", "Base Clock": "1320 MHz", "Boost Clock": "1882 MHz", "Interface": "PCIe 4.0 x16", "Power": "200W", "Connectors": "3x DisplayPort 1.4a, 1x HDMI 2.1"}',
8990000, 8490000, 15, 8, 'card-dohoa-1.jpg',
'["card-dohoa-1.jpg", "card-dohoa-1-2.jpg", "card-dohoa-1-3.jpg"]', 1, 1),

(4, 1, 'RAM DDR4 Corsair Vengeance LPX 16GB (2x8GB) 3200MHz', 'ram-ddr4-corsair-vengeance-16gb',
'Bộ nhớ RAM DDR4 hiệu năng cao với thiết kế low profile, phù hợp cho gaming và overclocking.',
'{"Capacity": "16GB (2x8GB)", "Type": "DDR4", "Speed": "3200MHz", "Timing": "CL16", "Voltage": "1.35V", "Form Factor": "288-pin DIMM", "Heat Spreader": "Aluminum"}',
2490000, 2290000, 30, 20, 'ram-ddr4-1.jpg',
'["ram-ddr4-1.jpg", "ram-ddr4-1-2.jpg"]', 0, 1),

(4, 1, 'SSD Samsung 970 EVO Plus 1TB NVMe M.2', 'ssd-samsung-970-evo-plus-1tb',
'Ổ cứng SSD NVMe tốc độ cao với hiệu năng vượt trội, phù hợp cho gaming và đồ họa.',
'{"Capacity": "1TB", "Interface": "PCIe 3.0 x4 NVMe", "Sequential Read": "3500 MB/s", "Sequential Write": "3300 MB/s", "Random Read": "620K IOPS", "Random Write": "560K IOPS", "Form Factor": "M.2 2280"}',
3290000, 2990000, 25, 12, 'ssd-nvme-1.jpg',
'["ssd-nvme-1.jpg", "ssd-nvme-1-2.jpg", "ssd-nvme-1-3.jpg"]', 1, 1),

(4, 1, 'CPU Intel Core i5-12400F 6 Core 12 Thread', 'cpu-intel-core-i5-12400f',
'Bộ xử lý Intel thế hệ 12 với hiệu năng mạnh mẽ, phù hợp cho gaming và đa nhiệm.',
'{"Cores": "6", "Threads": "12", "Base Clock": "2.5 GHz", "Max Turbo": "4.4 GHz", "Cache": "18MB L3", "TDP": "65W", "Socket": "LGA 1700", "Memory Support": "DDR4-3200, DDR5-4800"}',
4990000, 4790000, 20, 15, 'cpu-intel-1.jpg',
'["cpu-intel-1.jpg", "cpu-intel-1-2.jpg"]', 0, 1),

(4, 1, 'Mainboard ASUS ROG Strix B550-F Gaming WiFi', 'mainboard-asus-rog-strix-b550f',
'Bo mạch chủ gaming cao cấp với WiFi 6, phù hợp cho build PC gaming hiệu năng cao.',
'{"Socket": "AM4", "Chipset": "AMD B550", "Memory": "4x DDR4-3200", "PCIe": "1x PCIe 4.0 x16, 1x PCIe 3.0 x16", "Storage": "2x M.2, 6x SATA", "USB": "USB 3.2 Gen 2", "Network": "WiFi 6, Gigabit LAN", "Audio": "ROG SupremeFX"}',
3990000, 3790000, 18, 7, 'mainboard-1.jpg',
'["mainboard-1.jpg", "mainboard-1-2.jpg", "mainboard-1-3.jpg"]', 1, 1),

(4, 1, 'PSU Corsair RM750x 750W 80+ Gold Modular', 'psu-corsair-rm750x-750w',
'Nguồn máy tính 80+ Gold với thiết kế modular, hiệu suất cao và ổn định.',
'{"Wattage": "750W", "Efficiency": "80+ Gold", "Modular": "Fully Modular", "PCIe": "4x 6+2 pin", "SATA": "8x SATA", "Molex": "4x Molex", "Fan": "140mm Fluid Dynamic Bearing", "Warranty": "10 years"}',
2990000, 2790000, 12, 5, 'psu-corsair-1.jpg',
'["psu-corsair-1.jpg", "psu-corsair-1-2.jpg"]', 0, 1);

-- Dữ liệu mẫu cho bảng users (thêm khách hàng)
INSERT INTO users (username, email, password, full_name, phone, address, role, status) VALUES
('customer1', 'nguyenvanan@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn An', '0901234567', '123 Đường ABC, Quận 1, TP.HCM', 'customer', 1),
('customer2', 'tranthibinh@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Thị Bình', '0901234568', '456 Đường XYZ, Quận 2, TP.HCM', 'customer', 1),
('customer3', 'levanminh@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lê Văn Minh', '0901234569', '789 Đường DEF, Quận 3, TP.HCM', 'customer', 1),
('customer4', 'phamthithu@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Phạm Thị Thu', '0901234570', '321 Đường GHI, Quận 4, TP.HCM', 'customer', 1),
('customer5', 'hoangvanlong@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Hoàng Văn Long', '0901234571', '654 Đường JKL, Quận 5, TP.HCM', 'customer', 1);

-- Dữ liệu mẫu cho bảng product_imports
INSERT INTO product_imports (product_id, supplier_id, quantity, import_price, total_price, note, created_by) VALUES
(1, 1, 20, 22000000, 440000000, 'Nhập hàng lần 1 - ASUS ROG Strix G15', 1),
(2, 3, 25, 16000000, 400000000, 'Nhập hàng lần 1 - MSI Gaming GF63', 1),
(3, 1, 15, 20000000, 300000000, 'Nhập hàng lần 1 - ASUS TUF Gaming A15', 1),
(4, 2, 30, 13000000, 390000000, 'Nhập hàng lần 1 - Dell Inspiron 15', 1),
(5, 1, 25, 14000000, 350000000, 'Nhập hàng lần 1 - ASUS VivoBook S15', 1),
(6, 2, 20, 16000000, 320000000, 'Nhập hàng lần 1 - Dell Latitude 3520', 1),
(7, 1, 10, 15000000, 150000000, 'Nhập hàng lần 1 - PC Gaming ASUS ROG', 1),
(8, 3, 12, 13000000, 156000000, 'Nhập hàng lần 1 - PC Gaming MSI Infinite', 1),
(9, 1, 8, 25000000, 200000000, 'Nhập hàng lần 1 - PC Gaming ASUS ROG RTX 3060', 1),
(10, 1, 20, 7500000, 150000000, 'Nhập hàng lần 1 - Card đồ họa RTX 3060', 1);

-- Dữ liệu mẫu cho bảng coupons
INSERT INTO coupons (code, type, value, min_order_value, max_discount, quantity, used_quantity, start_date, end_date, status) VALUES
('WELCOME10', 'percent', 10.00, 1000000, 500000, 100, 15, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 1),
('GAMING20', 'percent', 20.00, 5000000, 2000000, 50, 8, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 1),
('LAPTOP15', 'percent', 15.00, 3000000, 1500000, 30, 5, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 1),
('FIXED100K', 'fixed', 100000, 2000000, 100000, 200, 25, '2024-01-01 00:00:00', '2024-12-31 23:59:59', 1),
('SUMMER2024', 'percent', 25.00, 10000000, 5000000, 20, 3, '2024-06-01 00:00:00', '2024-08-31 23:59:59', 1);

-- Dữ liệu mẫu cho bảng orders
INSERT INTO orders (user_id, order_code, customer_name, customer_email, customer_phone, customer_address, coupon_id, coupon_discount, subtotal, total, payment_method, payment_status, order_status, note) VALUES
(3, 'ORD001', 'Nguyễn Văn An', 'nguyenvanan@gmail.com', '0901234567', '123 Đường ABC, Quận 1, TP.HCM', 1, 2399000, 23990000, 21591000, 'cod', 'paid', 'delivered', 'Giao hàng vào buổi chiều'),
(4, 'ORD002', 'Trần Thị Bình', 'tranthibinh@gmail.com', '0901234568', '456 Đường XYZ, Quận 2, TP.HCM', 2, 3598000, 17990000, 14392000, 'vnpay', 'paid', 'shipping', 'Giao hàng nhanh'),
(5, 'ORD003', 'Lê Văn Minh', 'levanminh@gmail.com', '0901234569', '789 Đường DEF, Quận 3, TP.HCM', NULL, 0, 14990000, 14990000, 'cod', 'pending', 'confirmed', 'Khách hàng yêu cầu kiểm tra trước khi thanh toán'),
(6, 'ORD004', 'Phạm Thị Thu', 'phamthithu@gmail.com', '0901234570', '321 Đường GHI, Quận 4, TP.HCM', 4, 100000, 15990000, 15890000, 'vnpay', 'paid', 'processing', 'Đơn hàng đang chuẩn bị'),
(7, 'ORD005', 'Hoàng Văn Long', 'hoangvanlong@gmail.com', '0901234571', '654 Đường JKL, Quận 5, TP.HCM', 1, 1799000, 17990000, 16191000, 'cod', 'pending', 'pending', 'Chờ xác nhận từ khách hàng');

-- Dữ liệu mẫu cho bảng order_details
INSERT INTO order_details (order_id, product_id, product_name, product_price, quantity, total) VALUES
(1, 1, 'ASUS ROG Strix G15 G513IH-HN015T', 23990000, 1, 23990000),
(2, 2, 'MSI Gaming GF63 Thin 11SC-664VN', 17990000, 1, 17990000),
(3, 4, 'Dell Inspiron 15 3511 i5-1135G7', 14990000, 1, 14990000),
(4, 5, 'ASUS VivoBook S15 S533EA-BQ011T', 15990000, 1, 15990000),
(5, 2, 'MSI Gaming GF63 Thin 11SC-664VN', 17990000, 1, 17990000),
(1, 10, 'Card đồ họa ASUS ROG Strix RTX 3060 12GB', 8490000, 1, 8490000),
(2, 11, 'RAM DDR4 Corsair Vengeance LPX 16GB (2x8GB) 3200MHz', 2290000, 2, 4580000),
(3, 12, 'SSD Samsung 970 EVO Plus 1TB NVMe M.2', 2990000, 1, 2990000);

-- Dữ liệu mẫu cho bảng cart
INSERT INTO cart (user_id, product_id, quantity) VALUES
(3, 3, 1),
(3, 10, 1),
(4, 5, 2),
(4, 11, 1),
(5, 6, 1),
(5, 12, 1),
(6, 7, 1),
(6, 13, 1),
(7, 8, 1),
(7, 14, 1);

-- Dữ liệu mẫu cho bảng comments
INSERT INTO comments (product_id, user_id, content, rating, status) VALUES
(1, 3, 'Laptop gaming rất tốt, chạy game mượt mà, thiết kế đẹp. Rất hài lòng với sản phẩm!', 5, 1),
(1, 4, 'Hiệu năng tốt, giá cả hợp lý. Tuy nhiên pin hơi nhanh hết khi chơi game.', 4, 1),
(2, 5, 'Laptop mỏng nhẹ, phù hợp cho công việc và gaming nhẹ. Thiết kế đẹp.', 4, 1),
(4, 6, 'Laptop văn phòng tốt, hiệu năng ổn định. Phù hợp cho công việc hàng ngày.', 5, 1),
(5, 7, 'ASUS VivoBook thiết kế đẹp, hiệu năng tốt. Rất hài lòng với sản phẩm.', 5, 1),
(10, 3, 'Card đồ họa RTX 3060 hiệu năng tốt, chạy game 1440p mượt mà.', 5, 1),
(11, 4, 'RAM Corsair chất lượng tốt, tương thích tốt với mainboard.', 4, 1),
(12, 5, 'SSD Samsung tốc độ cao, khởi động máy nhanh hơn nhiều.', 5, 1);

-- Dữ liệu mẫu cho bảng news
INSERT INTO news (title, slug, content, image, author_id, views, status) VALUES
('Xu hướng laptop gaming 2024: Những điều cần biết', 'xu-huong-laptop-gaming-2024', 
'Laptop gaming đang trở thành xu hướng mạnh mẽ trong năm 2024 với những cải tiến về hiệu năng, thiết kế và công nghệ. Bài viết này sẽ phân tích những xu hướng mới nhất trong thế giới laptop gaming.',
'news-laptop-gaming-2024.jpg', 1, 1250, 1),

('Hướng dẫn build PC gaming giá rẻ dưới 20 triệu', 'huong-dan-build-pc-gaming-gia-re',
'Bài viết hướng dẫn chi tiết cách build PC gaming với ngân sách dưới 20 triệu đồng, bao gồm lựa chọn linh kiện phù hợp và tối ưu hóa hiệu năng.',
'news-build-pc-gaming.jpg', 1, 2100, 1),

('So sánh Intel vs AMD: Lựa chọn nào phù hợp?', 'so-sanh-intel-vs-amd',
'Phân tích chi tiết về ưu nhược điểm của bộ xử lý Intel và AMD, giúp người dùng lựa chọn phù hợp với nhu cầu sử dụng.',
'news-intel-vs-amd.jpg', 2, 1800, 1),

('Công nghệ RTX 40 series: Bước tiến mới trong gaming', 'cong-nghe-rtx-40-series',
'Khám phá những tính năng mới của dòng card đồ họa RTX 40 series và tác động của chúng đến trải nghiệm gaming.',
'news-rtx-40-series.jpg', 1, 950, 1),

('Tips tối ưu hóa hiệu năng laptop gaming', 'tips-toi-uu-hieu-nang-laptop-gaming',
'Những mẹo và thủ thuật giúp tối ưu hóa hiệu năng laptop gaming, kéo dài tuổi thọ và cải thiện trải nghiệm chơi game.',
'news-optimize-laptop-gaming.jpg', 2, 1600, 1);

-- Dữ liệu mẫu cho bảng banners
INSERT INTO banners (title, image, link, position, status) VALUES
('Khuyến mãi laptop gaming', 'banner-laptop-gaming.jpg', '/products?category=laptop-gaming', 1, 1),
('PC Gaming cao cấp', 'banner-pc-gaming.jpg', '/products?category=pc-gaming', 2, 1),
('Linh kiện máy tính', 'banner-linh-kien.jpg', '/products?category=linh-kien-may-tinh', 3, 1),
('Laptop văn phòng', 'banner-laptop-vanphong.jpg', '/products?category=laptop-van-phong', 4, 1),
('Flash Sale 50%', 'banner-flash-sale.jpg', '/products?sale=1', 5, 1);

-- Dữ liệu mẫu cho bảng contacts
INSERT INTO contacts (name, email, phone, subject, message, status) VALUES
('Nguyễn Văn A', 'nguyenvana@gmail.com', '0901234567', 'Hỏi về sản phẩm laptop gaming', 'Tôi muốn hỏi về laptop gaming ASUS ROG Strix G15, có còn hàng không?', 'resolved'),
('Trần Thị B', 'tranthib@gmail.com', '0901234568', 'Tư vấn build PC', 'Tôi muốn tư vấn build PC gaming với ngân sách 25 triệu, có thể tư vấn không?', 'processing'),
('Lê Văn C', 'levanc@gmail.com', '0901234569', 'Bảo hành sản phẩm', 'Laptop tôi mua bị lỗi màn hình, cần bảo hành như thế nào?', 'pending'),
('Phạm Thị D', 'phamthid@gmail.com', '0901234570', 'Đổi trả sản phẩm', 'Tôi muốn đổi laptop đã mua vì không phù hợp, có thể đổi không?', 'resolved'),
('Hoàng Văn E', 'hoangvane@gmail.com', '0901234571', 'Hỏi về chính sách giao hàng', 'Shop có giao hàng đến tỉnh không? Phí ship bao nhiêu?', 'resolved');

-- Dữ liệu mẫu cho bảng vnpay_transactions
INSERT INTO vnpay_transactions (order_id, transaction_no, bank_code, amount, order_info, transaction_status, response_code) VALUES
(2, 'VNPAY123456789', 'NCB', 14392000, 'Thanh toan don hang ORD002', 'success', '00'),
(4, 'VNPAY987654321', 'VCB', 15890000, 'Thanh toan don hang ORD004', 'success', '00');