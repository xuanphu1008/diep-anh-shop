<?php
// news.php - Trang tin tức

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';
require_once 'models/News.php';

$newsModel = new News();
$newsList = $newsModel->getAllNews();

$pageTitle = 'Tin tức - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <?php include 'includes/header.php'; ?>
    
    <div class="container" style="padding: 30px 0;">
        <h1 class="section-title"><i class="fas fa-newspaper"></i> Tin tức công nghệ</h1>
        
        <div class="news-grid">
            <?php foreach ($newsList as $news): ?>
            <div class="news-card">
                <a href="news-detail.php?slug=<?php echo $news['slug']; ?>">
                    <img src="<?php echo getNewsImage($news['image']); ?>" 
                         alt="<?php echo htmlspecialchars($news['title']); ?>">
                </a>
                <div class="news-info">
                    <h3>
                        <a href="news-detail.php?slug=<?php echo $news['slug']; ?>">
                            <?php echo htmlspecialchars($news['title']); ?>
                        </a>
                    </h3>
                    <p class="news-meta">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($news['author_name'] ?? 'Admin'); ?>
                        <i class="fas fa-calendar"></i> <?php echo formatDate($news['created_at'], 'd/m/Y'); ?>
                        <i class="fas fa-eye"></i> <?php echo $news['views']; ?> lượt xem
                    </p>
                    <p><?php echo truncateText(strip_tags($news['content']), 150); ?></p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($newsList)): ?>
        <div style="text-align: center; padding: 50px; background: #fff; border-radius: 10px;">
            <i class="fas fa-newspaper" style="font-size: 64px; color: #ccc;"></i>
            <h3>Chưa có tin tức nào</h3>
        </div>
        <?php endif; ?>
    </div>
    
    <?php include 'includes/footer.php'; ?>
</body>
</html>
