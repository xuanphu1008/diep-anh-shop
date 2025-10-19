<?php
require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';
require_once 'models/Contact.php';

$contactModel = new Contact();
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = [
        'name' => sanitizeInput($_POST['name'] ?? ''),
        'email' => sanitizeInput($_POST['email'] ?? ''),
        'phone' => sanitizeInput($_POST['phone'] ?? ''),
        'subject' => sanitizeInput($_POST['subject'] ?? ''),
        'message' => sanitizeInput($_POST['message'] ?? '')
    ];
    
    if (empty($data['name'])) $errors[] = 'Vui lòng nhập họ tên';
    if (empty($data['email'])) $errors[] = 'Vui lòng nhập email';
    if (empty($data['message'])) $errors[] = 'Vui lòng nhập nội dung';
    
    if (empty($errors)) {
        $result = $contactModel->addContact($data);
        if ($result['success']) {
            $success = $result['message'];
        } else {
            $errors[] = $result['message'];
        }
    }
}

$pageTitle = 'Liên hệ - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .contact-container {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 30px 0;
        }
        .contact-info {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        .contact-info-item {
            display: flex;
            gap: 15px;
            margin-bottom: 25px;
            padding-bottom: 25px;
            border-bottom: 1px solid var(--border-color);
        }
        .contact-info-item:last-child {
            border-bottom: none;
        }
        .contact-info-item i {
            font-size: 24px;
            color: var(--primary-color);
            width: 40px;
        }
        .contact-form {
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: var(--shadow);
        }
        @media (max-width: 768px) {
            .contact-container {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container contact-container">
        <!-- Thông tin liên hệ -->
        <div class="contact-info">
            <h2 style="margin-bottom: 30px;"><i class="fas fa-info-circle"></i> Thông tin liên hệ</h2>
            
            <div class="contact-info-item">
                <i class="fas fa-map-marker-alt"></i>
                <div>
                    <h4>Địa chỉ</h4>
                    <p>123 Đường ABC, Quận 1, Hà Nội, Việt Nam</p>
                </div>
            </div>
            
            <div class="contact-info-item">
                <i class="fas fa-phone"></i>
                <div>
                    <h4>Hotline</h4>
                    <p>0123.456.789 (8:00 - 22:00)</p>
                </div>
            </div>
            
            <div class="contact-info-item">
                <i class="fas fa-envelope"></i>
                <div>
                    <h4>Email</h4>
                    <p>admin@diepanhshop.com</p>
                </div>
            </div>
            
            <div class="contact-info-item">
                <i class="fas fa-clock"></i>
                <div>
                    <h4>Giờ làm việc</h4>
                    <p>Thứ 2 - Chủ nhật: 8:00 - 22:00</p>
                </div>
            </div>
            
            <h3 style="margin: 30px 0 20px;">Theo dõi chúng tôi</h3>
            <div class="social-links" style="font-size: 32px;">
                <a href="#"><i class="fab fa-facebook"></i></a>
                <a href="#"><i class="fab fa-youtube"></i></a>
                <a href="#"><i class="fab fa-instagram"></i></a>
                <a href="#"><i class="fab fa-tiktok"></i></a>
            </div>
            
            <h3 style="margin: 30px 0 20px;">Bản đồ</h3>
            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3724.096950912942!2d105.84117531533204!3d21.028511885995636!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3135ab9bd9861ca1%3A0xe7887f7b72ca17a9!2zSMOgIE7hu5lp!5e0!3m2!1svi!2s!4v1234567890123!5m2!1svi!2s" 
                    width="100%" height="300" style="border:0; border-radius: 10px;" allowfullscreen="" loading="lazy"></iframe>
        </div>
        
        <!-- Form liên hệ -->
        <div class="contact-form">
            <h2 style="margin-bottom: 20px;"><i class="fas fa-paper-plane"></i> Gửi tin nhắn</h2>
            
            <?php if (!empty($errors)): ?>
                <div class="alert alert-error">
                    <ul style="margin: 0; padding-left: 20px;">
                        <?php foreach ($errors as $error): ?>
                            <li><?php echo htmlspecialchars($error); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <?php echo htmlspecialchars($success); ?>
                </div>
            <?php endif; ?>
            
            <form method="POST">
                <div class="form-group">
                    <label>Họ và tên *</label>
                    <input type="text" name="name" class="form-control" required>
                </div>
                
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                    <div class="form-group">
                        <label>Email *</label>
                        <input type="email" name="email" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Số điện thoại</label>
                        <input type="text" name="phone" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label>Tiêu đề</label>
                    <input type="text" name="subject" class="form-control">
                </div>
                
                <div class="form-group">
                    <label>Nội dung *</label>
                    <textarea name="message" class="form-control" rows="6" required></textarea>
                </div>
                
                <button type="submit" class="btn btn-primary btn-block btn-lg">
                    <i class="fas fa-paper-plane"></i> Gửi tin nhắn
                </button>
            </form>
        </div>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>