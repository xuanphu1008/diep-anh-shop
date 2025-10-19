<?php
// news-detail.php - Trang chi tiết tin tức

require_once 'config/config.php';
require_once 'includes/Database.php';
require_once 'includes/functions.php';
require_once 'models/News.php';

$slug = $_GET['slug'] ?? '';

if (empty($slug)) {
    setFlashMessage('error', 'Không tìm thấy tin tức');
    redirect(SITE_URL . '/news.php');
}

$newsModel = new News();
$news = $newsModel->getNewsBySlug($slug);

if (!$news) {
    setFlashMessage('error', 'Tin tức không tồn tại hoặc đã bị xóa');
    redirect(SITE_URL . '/news.php');
}

// Lấy tin tức liên quan
$relatedNews = $newsModel->getRelatedNews($news['id'], 3);
$popularNews = $newsModel->getPopularNews(5);

$pageTitle = htmlspecialchars($news['title']) . ' - ' . SITE_NAME;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $pageTitle; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Meta tags for SEO -->
    <meta name="description" content="<?php echo htmlspecialchars(truncateText(strip_tags($news['content']), 160)); ?>">
    <meta name="keywords" content="tin tức, công nghệ, laptop, máy tính, gaming">
    <meta property="og:title" content="<?php echo htmlspecialchars($news['title']); ?>">
    <meta property="og:description" content="<?php echo htmlspecialchars(truncateText(strip_tags($news['content']), 160)); ?>">
    <meta property="og:image" content="<?php echo getNewsImage($news['image']); ?>">
    <meta property="og:type" content="article">
    <meta property="og:url" content="<?php echo SITE_URL . '/news-detail.php?slug=' . $news['slug']; ?>">
    
    <style>
        .news-detail-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .news-detail {
            display: grid;
            grid-template-columns: 1fr 300px;
            gap: 40px;
            margin-top: 20px;
        }
        
        .news-content {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .news-header {
            padding: 30px;
            border-bottom: 1px solid #eee;
        }
        
        .news-title {
            font-size: 28px;
            font-weight: bold;
            color: #2c3e50;
            margin-bottom: 15px;
            line-height: 1.4;
        }
        
        .news-meta {
            display: flex;
            align-items: center;
            gap: 20px;
            color: #7f8c8d;
            font-size: 14px;
            margin-bottom: 20px;
        }
        
        .news-meta i {
            margin-right: 5px;
            color: var(--primary-color);
        }
        
        .news-image {
            width: 100%;
            height: 400px;
            object-fit: cover;
            border-radius: 8px;
        }
        
        .news-body {
            padding: 30px;
        }
        
        .news-content-text {
            font-size: 16px;
            line-height: 1.8;
            color: #2c3e50;
            text-align: justify;
        }
        
        .news-content-text h1,
        .news-content-text h2,
        .news-content-text h3 {
            color: #2c3e50;
            margin: 25px 0 15px 0;
        }
        
        .news-content-text p {
            margin-bottom: 20px;
        }
        
        .news-content-text img {
            max-width: 100%;
            height: auto;
            border-radius: 8px;
            margin: 20px 0;
        }
        
        .news-content-text blockquote {
            border-left: 4px solid var(--primary-color);
            padding-left: 20px;
            margin: 20px 0;
            font-style: italic;
            color: #7f8c8d;
        }
        
        .news-content-text ul,
        .news-content-text ol {
            margin: 15px 0;
            padding-left: 30px;
        }
        
        .news-content-text li {
            margin-bottom: 8px;
        }
        
        .news-sidebar {
            display: flex;
            flex-direction: column;
            gap: 30px;
        }
        
        .sidebar-widget {
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .widget-title {
            background: var(--primary-color);
            color: #fff;
            padding: 15px 20px;
            margin: 0;
            font-size: 16px;
            font-weight: bold;
        }
        
        .widget-content {
            padding: 20px;
        }
        
        .related-news-item {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .related-news-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .related-news-image {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .related-news-content h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .related-news-content h4 a {
            color: #2c3e50;
            text-decoration: none;
        }
        
        .related-news-content h4 a:hover {
            color: var(--primary-color);
        }
        
        .related-news-meta {
            font-size: 12px;
            color: #7f8c8d;
        }
        
        .popular-news-item {
            display: flex;
            gap: 15px;
            margin-bottom: 15px;
            padding-bottom: 15px;
            border-bottom: 1px solid #eee;
        }
        
        .popular-news-item:last-child {
            margin-bottom: 0;
            padding-bottom: 0;
            border-bottom: none;
        }
        
        .popular-news-image {
            width: 80px;
            height: 60px;
            object-fit: cover;
            border-radius: 5px;
        }
        
        .popular-news-content h4 {
            margin: 0 0 5px 0;
            font-size: 14px;
            line-height: 1.4;
        }
        
        .popular-news-content h4 a {
            color: #2c3e50;
            text-decoration: none;
        }
        
        .popular-news-content h4 a:hover {
            color: var(--primary-color);
        }
        
        .popular-news-meta {
            font-size: 12px;
            color: #7f8c8d;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .news-actions {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 20px 30px;
            border-top: 1px solid #eee;
            background: #f8f9fa;
        }
        
        .news-actions .btn {
            padding: 10px 20px;
            border-radius: 5px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-back {
            background: #6c757d;
            color: #fff;
        }
        
        .btn-back:hover {
            background: #5a6268;
            color: #fff;
        }
        
        .btn-share {
            background: var(--primary-color);
            color: #fff;
        }
        
        .btn-share:hover {
            background: #0056b3;
            color: #fff;
        }
        
        .breadcrumb {
            background: #f8f9fa;
            padding: 15px 0;
            margin-bottom: 20px;
        }
        
        .breadcrumb .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 20px;
        }
        
        .breadcrumb a {
            color: var(--primary-color);
            text-decoration: none;
        }
        
        .breadcrumb a:hover {
            text-decoration: underline;
        }
        
        .breadcrumb .separator {
            margin: 0 10px;
            color: #6c757d;
        }
        
        .breadcrumb .current {
            color: #6c757d;
        }
        
        @media (max-width: 768px) {
            .news-detail {
                grid-template-columns: 1fr;
                gap: 20px;
            }
            
            .news-title {
                font-size: 24px;
            }
            
            .news-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
            }
            
            .news-image {
                height: 250px;
            }
        }
    </style>
</head>
<body>
    <!-- Header -->
    <?php include 'includes/header.php'; ?>

    <!-- Breadcrumb -->
    <div class="breadcrumb">
        <div class="container">
            <a href="<?php echo SITE_URL; ?>"><i class="fas fa-home"></i> Trang chủ</a>
            <span class="separator">/</span>
            <a href="news.php">Tin tức</a>
            <span class="separator">/</span>
            <span class="current"><?php echo htmlspecialchars($news['title']); ?></span>
        </div>
    </div>

    <div class="news-detail-container">
        <div class="news-detail">
            <!-- Nội dung chính -->
            <div class="news-content">
                <div class="news-header">
                    <h1 class="news-title"><?php echo htmlspecialchars($news['title']); ?></h1>
                    
                    <div class="news-meta">
                        <span><i class="fas fa-user"></i> <?php echo htmlspecialchars($news['author_name']); ?></span>
                        <span><i class="fas fa-calendar"></i> <?php echo formatDate($news['created_at'], 'd/m/Y H:i'); ?></span>
                        <span><i class="fas fa-eye"></i> <?php echo number_format($news['views']); ?> lượt xem</span>
                    </div>
                    
                    <?php if ($news['image']): ?>
                    <img src="<?php echo getNewsImage($news['image']); ?>" 
                         alt="<?php echo htmlspecialchars($news['title']); ?>" 
                         class="news-image">
                    <?php endif; ?>
                </div>
                
                <div class="news-body">
                    <div class="news-content-text">
                        <?php echo nl2br(htmlspecialchars($news['content'])); ?>
                    </div>
                </div>
                
                <div class="news-actions">
                    <a href="news.php" class="btn btn-back">
                        <i class="fas fa-arrow-left"></i> Quay lại
                    </a>
                    <button class="btn btn-share" onclick="shareNews()">
                        <i class="fas fa-share"></i> Chia sẻ
                    </button>
                </div>
            </div>
            
            <!-- Sidebar -->
            <div class="news-sidebar">
                <!-- Tin tức liên quan -->
                <?php if (!empty($relatedNews)): ?>
                <div class="sidebar-widget">
                    <h3 class="widget-title">
                        <i class="fas fa-newspaper"></i> Tin tức liên quan
                    </h3>
                    <div class="widget-content">
                        <?php foreach ($relatedNews as $related): ?>
                        <div class="related-news-item">
                            <a href="news-detail.php?slug=<?php echo $related['slug']; ?>">
                                <img src="<?php echo getNewsImage($related['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($related['title']); ?>" 
                                     class="related-news-image">
                            </a>
                            <div class="related-news-content">
                                <h4>
                                    <a href="news-detail.php?slug=<?php echo $related['slug']; ?>">
                                        <?php echo htmlspecialchars($related['title']); ?>
                                    </a>
                                </h4>
                                <div class="related-news-meta">
                                    <i class="fas fa-calendar"></i> <?php echo formatDate($related['created_at'], 'd/m/Y'); ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Tin tức phổ biến -->
                <?php if (!empty($popularNews)): ?>
                <div class="sidebar-widget">
                    <h3 class="widget-title">
                        <i class="fas fa-fire"></i> Tin tức phổ biến
                    </h3>
                    <div class="widget-content">
                        <?php foreach ($popularNews as $popular): ?>
                        <div class="popular-news-item">
                            <a href="news-detail.php?slug=<?php echo $popular['slug']; ?>">
                                <img src="<?php echo getNewsImage($popular['image']); ?>" 
                                     alt="<?php echo htmlspecialchars($popular['title']); ?>" 
                                     class="popular-news-image">
                            </a>
                            <div class="popular-news-content">
                                <h4>
                                    <a href="news-detail.php?slug=<?php echo $popular['slug']; ?>">
                                        <?php echo htmlspecialchars($popular['title']); ?>
                                    </a>
                                </h4>
                                <div class="popular-news-meta">
                                    <span><i class="fas fa-eye"></i> <?php echo number_format($popular['views']); ?></span>
                                    <span><i class="fas fa-calendar"></i> <?php echo formatDate($popular['created_at'], 'd/m/Y'); ?></span>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <?php include 'includes/footer.php'; ?>

    <script>
        function shareNews() {
            if (navigator.share) {
                navigator.share({
                    title: '<?php echo addslashes($news['title']); ?>',
                    text: '<?php echo addslashes(truncateText(strip_tags($news['content']), 100)); ?>',
                    url: window.location.href
                });
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(window.location.href).then(function() {
                    alert('Đã sao chép link vào clipboard!');
                });
            }
        }
        
        // Smooth scroll for anchor links
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function (e) {
                e.preventDefault();
                document.querySelector(this.getAttribute('href')).scrollIntoView({
                    behavior: 'smooth'
                });
            });
        });
    </script>
</body>
</html>
