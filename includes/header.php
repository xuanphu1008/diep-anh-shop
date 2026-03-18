<?php
// includes/header.php - Header template

if (!isset($_SESSION)) {
    session_start();
}

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/functions.php';

$cartCount = getCartCount();
?>
<script>
    // Provide SITE_URL to client JS so API paths are absolute and consistent
    // Tự động detect port từ current origin
    var currentOrigin = window.location.origin;
    var configSiteUrl = '<?php echo rtrim(SITE_URL, "/"); ?>';
    
    // Nếu đang chạy trên port khác (như 8012), sử dụng origin hiện tại
    if (currentOrigin.includes(':8012') || currentOrigin.includes(':8080') || currentOrigin.includes(':3000')) {
        window.SITE_URL = currentOrigin + '/diep-anh-shop';
    } else {
        window.SITE_URL = configSiteUrl;
    }

    // Helper to build API URL
    function apiUrl(path) {
        path = path.replace(/^\//, '');
        return window.SITE_URL + '/' + path;
    }

    // Define critical functions IMMEDIATELY before page renders
    window.addToCart = function(productId, btnElement) {
        if(btnElement) {
            var originalText = btnElement.innerHTML;
            btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
            btnElement.disabled = true;
        }
        
        var quantityInput = document.getElementById('quantity');
        var quantity = 1;
        if (quantityInput && quantityInput.value) {
            quantity = parseInt(quantityInput.value) || 1;
        }
        var formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', parseInt(productId) || 0);
        formData.append('quantity', quantity);
        var fullApiPath = apiUrl('api/cart-handler.php');
        
        fetch(fullApiPath, {
            method: 'POST',
            body: formData
        })
        .then(function(r) {
            if (!r.ok) {
                throw new Error('Network response was not ok');
            }
            return r.json();
        })
        .then(function(d) {
            if(d.success) {
                window.showNotification('success', d.message);
                if (typeof window.updateCartCount === 'function') {
                    window.updateCartCount();
                }
            } else {
                window.showNotification('error', d.message || 'Có lỗi xảy ra');
            }
        })
        .catch(function(e) {
            console.error('Error:', e);
            window.showNotification('error', 'Có lỗi xảy ra khi thêm vào giỏ. Vui lòng thử lại!');
        })
        .finally(function() {
            if(btnElement) {
                btnElement.innerHTML = originalText;
                btnElement.disabled = false;
            }
        });
    };

    window.buyNow = function(productId) {
        var quantityInput = document.getElementById('quantity');
        var quantity = 1;
        if (quantityInput && quantityInput.value) {
            quantity = parseInt(quantityInput.value) || 1;
        }
        var formData = new FormData();
        formData.append('action', 'add');
        formData.append('product_id', parseInt(productId) || 0);
        formData.append('quantity', quantity);
        var fullApiPath = apiUrl('api/cart-handler.php');
        fetch(fullApiPath, {method:'POST', body:formData}).then(function(r){return r.json();}).then(function(d){if(d.success){window.location.href = apiUrl('customer/cart.php');}else{window.showNotification('error',d.message);}}).catch(function(e){console.error('Error:',e);window.showNotification('error','Có lỗi xảy ra!');});
    };

    window.removeFromCart = function(productId) {
        if (!confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')) {
            return;
        }
        
        var formData = new FormData();
        formData.append('action', 'remove');
        formData.append('product_id', productId);
        var fullApiPath = apiUrl('api/cart-handler.php');
        
        fetch(fullApiPath, {
            method: 'POST',
            body: formData
        })
        .then(function(r) {
            if (!r.ok) {
                throw new Error('Network response was not ok');
            }
            return r.json();
        })
        .then(function(d) {
            if(d.success) {
                window.showNotification('success', d.message);
                if (typeof window.updateCartCount === 'function') {
                    window.updateCartCount();
                }
                setTimeout(function() {
                    location.reload();
                }, 500);
            } else {
                window.showNotification('error', d.message || 'Có lỗi xảy ra');
            }
        })
        .catch(function(e) {
            console.error('Error:', e);
            window.showNotification('error', 'Có lỗi xảy ra khi xóa sản phẩm. Vui lòng thử lại!');
        });
    };

    window.showTab = function(evt, tabName) {
        try{var cs=document.querySelectorAll('.tab-content');for(var i=0;i<cs.length;i++)cs[i].classList.remove('active');var bs=document.querySelectorAll('.tab-btn');for(var i=0;i<bs.length;i++)bs[i].classList.remove('active');var t=document.getElementById(tabName);if(t)t.classList.add('active');if(evt&&evt.currentTarget)evt.currentTarget.classList.add('active');else if(evt&&evt.target){var b=evt.target.closest('.tab-btn');if(b)b.classList.add('active');}}catch(e){console.error('showTab error',e);}
    };

    window.changeImage = function(img) {
        var m=document.getElementById('mainImage');if(m)m.src=img.src;var ts=document.querySelectorAll('.thumbnail-images img');for(var i=0;i<ts.length;i++)ts[i].classList.remove('active');img.classList.add('active');
    };

    window.increaseQty = function() {
        var i=document.getElementById('quantity');var max=parseInt(i.max);if(parseInt(i.value)<max)i.value=parseInt(i.value)+1;
    };

    window.decreaseQty = function() {
        var i=document.getElementById('quantity');if(parseInt(i.value)>1)i.value=parseInt(i.value)-1;
    };

    window.updateCartCount = function() {
        var f = apiUrl('api/cart-handler.php') + '?action=count';
        fetch(f).then(function(r){return r.json();}).then(function(d){if(d.success){var e=document.querySelector('.cart-count');if(e)e.textContent=d.count;}}).catch(function(e){console.error('Error:',e);});
    };

    window.showNotification = function(type,msg) {
        var o=document.querySelector('.notification');if(o)o.remove();var n=document.createElement('div');n.className='notification notification-'+type;n.innerHTML='<div class="notification-content"><i class="fas fa-'+(type==='success'?'check-circle':type==='error'?'exclamation-circle':'info-circle')+'"></i><span>'+msg+'</span></div>';if(!document.querySelector('#notification-styles')){var s=document.createElement('style');s.id='notification-styles';s.textContent='.notification{position:fixed;top:80px;right:20px;min-width:300px;padding:15px 20px;border-radius:5px;box-shadow:0 4px 12px rgba(2,40,89,0.2);z-index:9999;animation:slideIn .3s}.notification-success{background:var(--success-color);color:#fff}.notification-error{background:var(--danger-color);color:#fff}@keyframes slideIn{from{transform:translateX(400px);opacity:0}to{transform:translateX(0);opacity:1}}';document.head.appendChild(s);}document.body.appendChild(n);setTimeout(function(){n.style.animation='slideOut .3s ease';setTimeout(function(){n.remove();},300);},3000);
    };

    window.selectRating = function(value) {
        var ratingValue = document.getElementById('ratingValue');
        if(ratingValue) ratingValue.value = value;
        var stars = document.querySelectorAll('.rating-star');
        for(var i = 0; i < stars.length; i++) {
            stars[i].style.color = (i + 1) <= value ? 'var(--warning-color)' : '#ccc';
        }
    };

    window.deleteRating = function(id) {
        if(confirm('Bạn chắc chắn muốn xóa đánh giá này?')) {
            var formData = new FormData();
            formData.append('action', 'delete');
            formData.append('rating_id', id);
            var fullApiPath = apiUrl('api/rating-handler.php');
            fetch(fullApiPath, {method:'POST', body:formData}).then(function(r){return r.json();}).then(function(d){if(d.success){alert('Đánh giá đã được xóa');location.reload();}else{alert(d.message);}}).catch(function(e){alert('Lỗi khi xóa đánh giá');});
        }
    };
</script>
<header>
    <div class="header-top">
        <div class="container">
            <div>
                <i class="fas fa-phone"></i> Hotline: 0123.456.789
                <i class="fas fa-envelope" style="margin-left: 20px;"></i> admin@diepanhshop.com
            </div>
            <div>
                <?php if (isLoggedIn()): ?>
                    <a href="<?php echo SITE_URL; ?>/customer/profile.php">
                        <i class="fas fa-user"></i> <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>
                    </a>
                    <?php if (isStaff()): ?>
                    <a href="<?php echo SITE_URL; ?>/admin/index.php" style="margin-left: 15px;">
                        <i class="fas fa-cog"></i> Quản trị
                    </a>
                    <?php endif; ?>
                    <a href="<?php echo SITE_URL; ?>/customer/logout.php" style="margin-left: 15px;">
                        <i class="fas fa-sign-out-alt"></i> Đăng xuất
                    </a>
                <?php else: ?>
                    <a href="<?php echo SITE_URL; ?>/customer/login.php">
                        <i class="fas fa-sign-in-alt"></i> Đăng nhập
                    </a>
                    <a href="<?php echo SITE_URL; ?>/customer/register.php" style="margin-left: 15px;">
                        <i class="fas fa-user-plus"></i> Đăng ký
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="header-main">
        <div class="container">
            <div class="logo">
                <a href="<?php echo SITE_URL; ?>">
                    <i class="fas fa-laptop"></i> Diệp Anh
                </a>
            </div>
            
            <div class="search-bar">
                <form action="<?php echo SITE_URL; ?>/products.php" method="GET">
                    <input type="text" name="search" placeholder="Tìm kiếm sản phẩm..." 
                           value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>">
                    <button type="submit">
                        <i class="fas fa-search"></i>
                    </button>
                </form>
            </div>
            
            <div class="header-actions">
                <a href="<?php echo SITE_URL; ?>/customer/cart.php" class="cart-icon">
                    <i class="fas fa-shopping-cart"></i>
                    <span class="cart-count"><?php echo $cartCount; ?></span>
                </a>
            </div>
        </div>
    </div>
    
    <nav>
        <div class="container">
            <ul>
                <li><a href="<?php echo SITE_URL; ?>"><i class="fas fa-home"></i> Trang chủ</a></li>
                <li><a href="<?php echo SITE_URL; ?>/products.php"><i class="fas fa-laptop"></i> Sản phẩm</a></li>
                <li><a href="<?php echo SITE_URL; ?>/products.php?filter=hot"><i class="fas fa-fire"></i> Hot</a></li>
                <li><a href="<?php echo SITE_URL; ?>/products.php?filter=discount"><i class="fas fa-tags"></i> Khuyến mãi</a></li>
                <li><a href="<?php echo SITE_URL; ?>/news.php"><i class="fas fa-newspaper"></i> Tin tức</a></li>
                <li><a href="<?php echo SITE_URL; ?>/contact.php"><i class="fas fa-phone"></i> Liên hệ</a></li>
            </ul>
        </div>
    </nav>
</header>