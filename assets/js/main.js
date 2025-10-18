// assets/js/main.js - Main JavaScript file

// Banner Slider
let currentSlide = 0;
let slideInterval;

function initBannerSlider() {
    const bannerSlider = document.querySelector('.banner-slider');
    if (!bannerSlider) return;
    
    const slides = bannerSlider.querySelectorAll('.banner-item');
    const totalSlides = slides.length;
    
    if (totalSlides <= 1) return;
    
    // Tạo dots
    const dotsContainer = document.createElement('div');
    dotsContainer.className = 'slider-dots';
    for (let i = 0; i < totalSlides; i++) {
        const dot = document.createElement('span');
        dot.className = i === 0 ? 'dot active' : 'dot';
        dot.onclick = () => goToSlide(i);
        dotsContainer.appendChild(dot);
    }
    bannerSlider.appendChild(dotsContainer);
    
    // Tạo prev/next buttons
    const prevBtn = document.createElement('button');
    prevBtn.className = 'slider-btn prev';
    prevBtn.innerHTML = '<i class="fas fa-chevron-left"></i>';
    prevBtn.onclick = previousSlide;
    
    const nextBtn = document.createElement('button');
    nextBtn.className = 'slider-btn next';
    nextBtn.innerHTML = '<i class="fas fa-chevron-right"></i>';
    nextBtn.onclick = nextSlide;
    
    bannerSlider.appendChild(prevBtn);
    bannerSlider.appendChild(nextBtn);
    
    // Auto slide
    startAutoSlide();
    
    // Pause on hover
    bannerSlider.addEventListener('mouseenter', stopAutoSlide);
    bannerSlider.addEventListener('mouseleave', startAutoSlide);
    
    // Add CSS
    addSliderStyles();
}

function goToSlide(n) {
    const slides = document.querySelectorAll('.banner-item');
    const dots = document.querySelectorAll('.slider-dots .dot');
    
    slides.forEach(slide => slide.classList.remove('active'));
    dots.forEach(dot => dot.classList.remove('active'));
    
    currentSlide = n;
    if (currentSlide >= slides.length) currentSlide = 0;
    if (currentSlide < 0) currentSlide = slides.length - 1;
    
    slides[currentSlide].classList.add('active');
    dots[currentSlide].classList.add('active');
    
    // Transform để slide
    document.querySelector('.banner-slider').style.transform = `translateX(-${currentSlide * 100}%)`;
}

function nextSlide() {
    goToSlide(currentSlide + 1);
}

function previousSlide() {
    goToSlide(currentSlide - 1);
}

function startAutoSlide() {
    slideInterval = setInterval(nextSlide, 5000);
}

function stopAutoSlide() {
    clearInterval(slideInterval);
}

function addSliderStyles() {
    if (document.querySelector('#slider-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'slider-styles';
    style.textContent = `
        .banner-slider {
            position: relative;
            overflow: hidden;
            display: flex;
            transition: transform 0.5s ease;
        }
        .banner-item {
            min-width: 100%;
            transition: opacity 0.5s ease;
        }
        .slider-btn {
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            background: rgba(0,0,0,0.5);
            color: white;
            border: none;
            padding: 15px 20px;
            cursor: pointer;
            z-index: 10;
            transition: all 0.3s;
        }
        .slider-btn:hover {
            background: rgba(0,0,0,0.8);
        }
        .slider-btn.prev {
            left: 20px;
        }
        .slider-btn.next {
            right: 20px;
        }
        .slider-dots {
            position: absolute;
            bottom: 20px;
            left: 50%;
            transform: translateX(-50%);
            display: flex;
            gap: 10px;
            z-index: 10;
        }
        .slider-dots .dot {
            width: 12px;
            height: 12px;
            border-radius: 50%;
            background: rgba(255,255,255,0.5);
            cursor: pointer;
            transition: all 0.3s;
        }
        .slider-dots .dot.active,
        .slider-dots .dot:hover {
            background: white;
            transform: scale(1.2);
        }
    `;
    document.head.appendChild(style);
}

// Mobile Menu Toggle
function initMobileMenu() {
    const menuToggle = document.createElement('button');
    menuToggle.className = 'mobile-menu-toggle';
    menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
    menuToggle.onclick = toggleMobileMenu;
    
    const header = document.querySelector('header .container');
    if (header) {
        header.insertBefore(menuToggle, header.firstChild);
    }
    
    addMobileMenuStyles();
}

function toggleMobileMenu() {
    const nav = document.querySelector('nav');
    nav.classList.toggle('mobile-active');
}

function addMobileMenuStyles() {
    if (document.querySelector('#mobile-menu-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'mobile-menu-styles';
    style.textContent = `
        .mobile-menu-toggle {
            display: none;
            background: none;
            border: none;
            font-size: 24px;
            color: var(--primary-color);
            cursor: pointer;
        }
        @media (max-width: 768px) {
            .mobile-menu-toggle {
                display: block;
            }
            nav {
                display: none;
            }
            nav.mobile-active {
                display: block;
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            }
            nav.mobile-active ul {
                flex-direction: column;
            }
        }
    `;
    document.head.appendChild(style);
}

// Search functionality
function initSearch() {
    const searchForm = document.querySelector('.search-bar form');
    if (searchForm) {
        searchForm.addEventListener('submit', function(e) {
            const searchInput = this.querySelector('input');
            if (!searchInput.value.trim()) {
                e.preventDefault();
                showNotification('warning', 'Vui lòng nhập từ khóa tìm kiếm');
            }
        });
    }
}

// Lazy loading images
function initLazyLoading() {
    const images = document.querySelectorAll('img[data-src]');
    
    const imageObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                const img = entry.target;
                img.src = img.dataset.src;
                img.removeAttribute('data-src');
                observer.unobserve(img);
            }
        });
    });
    
    images.forEach(img => imageObserver.observe(img));
}

// Scroll to top button
function initScrollToTop() {
    const scrollBtn = document.createElement('button');
    scrollBtn.id = 'scroll-to-top';
    scrollBtn.innerHTML = '<i class="fas fa-arrow-up"></i>';
    scrollBtn.onclick = () => {
        window.scrollTo({ top: 0, behavior: 'smooth' });
    };
    
    document.body.appendChild(scrollBtn);
    
    window.addEventListener('scroll', () => {
        if (window.pageYOffset > 300) {
            scrollBtn.classList.add('show');
        } else {
            scrollBtn.classList.remove('show');
        }
    });
    
    addScrollToTopStyles();
}

function addScrollToTopStyles() {
    if (document.querySelector('#scroll-to-top-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'scroll-to-top-styles';
    style.textContent = `
        #scroll-to-top {
            position: fixed;
            bottom: 100px;
            right: 30px;
            width: 50px;
            height: 50px;
            background: var(--primary-color);
            color: white;
            border: none;
            border-radius: 50%;
            cursor: pointer;
            opacity: 0;
            visibility: hidden;
            transition: all 0.3s;
            z-index: 998;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }
        #scroll-to-top.show {
            opacity: 1;
            visibility: visible;
        }
        #scroll-to-top:hover {
            background: #0052a3;
            transform: translateY(-5px);
        }
    `;
    document.head.appendChild(style);
}

// Countdown timer for flash sales
function initCountdown() {
    const countdowns = document.querySelectorAll('.countdown-timer');
    
    countdowns.forEach(countdown => {
        const endTime = new Date(countdown.dataset.endtime).getTime();
        
        const timer = setInterval(() => {
            const now = new Date().getTime();
            const distance = endTime - now;
            
            if (distance < 0) {
                clearInterval(timer);
                countdown.innerHTML = 'Đã kết thúc';
                return;
            }
            
            const days = Math.floor(distance / (1000 * 60 * 60 * 24));
            const hours = Math.floor((distance % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
            const minutes = Math.floor((distance % (1000 * 60 * 60)) / (1000 * 60));
            const seconds = Math.floor((distance % (1000 * 60)) / 1000);
            
            countdown.innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
        }, 1000);
    });
}

// Product image zoom
function initProductZoom() {
    const mainImage = document.getElementById('mainImage');
    if (!mainImage) return;
    
    mainImage.addEventListener('mousemove', function(e) {
        const rect = this.getBoundingClientRect();
        const x = e.clientX - rect.left;
        const y = e.clientY - rect.top;
        
        const xPercent = (x / rect.width) * 100;
        const yPercent = (y / rect.height) * 100;
        
        this.style.transformOrigin = `${xPercent}% ${yPercent}%`;
        this.style.transform = 'scale(1.5)';
    });
    
    mainImage.addEventListener('mouseleave', function() {
        this.style.transform = 'scale(1)';
    });
}

// Form validation
function validateForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return true;
    
    const inputs = form.querySelectorAll('[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!input.value.trim()) {
            isValid = false;
            input.classList.add('error');
            showInputError(input, 'Trường này không được để trống');
        } else {
            input.classList.remove('error');
            removeInputError(input);
        }
    });
    
    return isValid;
}

function showInputError(input, message) {
    removeInputError(input);
    
    const error = document.createElement('span');
    error.className = 'input-error';
    error.textContent = message;
    error.style.color = 'red';
    error.style.fontSize = '12px';
    error.style.marginTop = '5px';
    error.style.display = 'block';
    
    input.parentNode.appendChild(error);
}

function removeInputError(input) {
    const error = input.parentNode.querySelector('.input-error');
    if (error) {
        error.remove();
    }
}

// Wishlist functionality
function toggleWishlist(productId) {
    fetch('api/wishlist-handler.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'toggle',
            product_id: productId
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', data.message);
            const btn = document.querySelector(`[data-product-id="${productId}"] .wishlist-btn`);
            if (btn) {
                btn.classList.toggle('active');
            }
        } else {
            showNotification('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'Có lỗi xảy ra');
    });
}

// Compare products
let compareList = [];

function addToCompare(productId) {
    if (compareList.includes(productId)) {
        showNotification('info', 'Sản phẩm đã có trong danh sách so sánh');
        return;
    }
    
    if (compareList.length >= 4) {
        showNotification('warning', 'Chỉ có thể so sánh tối đa 4 sản phẩm');
        return;
    }
    
    compareList.push(productId);
    showNotification('success', 'Đã thêm vào danh sách so sánh');
    updateCompareCounter();
}

function updateCompareCounter() {
    const counter = document.getElementById('compare-counter');
    if (counter) {
        counter.textContent = compareList.length;
        counter.style.display = compareList.length > 0 ? 'block' : 'none';
    }
}

// Quick view product
function quickView(productId) {
    fetch(`api/product-quick-view.php?id=${productId}`)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showQuickViewModal(data.product);
        } else {
            showNotification('error', 'Không thể tải thông tin sản phẩm');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'Có lỗi xảy ra');
    });
}

function showQuickViewModal(product) {
    const modalHTML = `
        <div class="modal-overlay" onclick="closeModal()">
            <div class="modal-content" onclick="event.stopPropagation()">
                <button class="modal-close" onclick="closeModal()">
                    <i class="fas fa-times"></i>
                </button>
                <div class="quick-view-content">
                    <div class="quick-view-image">
                        <img src="${product.image}" alt="${product.name}">
                    </div>
                    <div class="quick-view-info">
                        <h2>${product.name}</h2>
                        <div class="price-section">
                            <span class="current-price">${formatCurrency(product.price)}</span>
                        </div>
                        <p>${product.description}</p>
                        <button class="btn btn-primary" onclick="addToCart(${product.id})">
                            Thêm vào giỏ hàng
                        </button>
                        <a href="product-detail.php?slug=${product.slug}" class="btn btn-secondary">
                            Xem chi tiết
                        </a>
                    </div>
                </div>
            </div>
        </div>
    `;
    
    document.body.insertAdjacentHTML('beforeend', modalHTML);
    document.body.style.overflow = 'hidden';
    
    addModalStyles();
}

function closeModal() {
    const modal = document.querySelector('.modal-overlay');
    if (modal) {
        modal.remove();
        document.body.style.overflow = '';
    }
}

function addModalStyles() {
    if (document.querySelector('#modal-styles')) return;
    
    const style = document.createElement('style');
    style.id = 'modal-styles';
    style.textContent = `
        .modal-overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0,0,0,0.7);
            display: flex;
            align-items: center;
            justify-content: center;
            z-index: 10000;
        }
        .modal-content {
            background: white;
            border-radius: 10px;
            max-width: 900px;
            width: 90%;
            max-height: 90vh;
            overflow-y: auto;
            position: relative;
        }
        .modal-close {
            position: absolute;
            top: 20px;
            right: 20px;
            background: none;
            border: none;
            font-size: 24px;
            cursor: pointer;
            z-index: 1;
        }
        .quick-view-content {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            padding: 30px;
        }
        @media (max-width: 768px) {
            .quick-view-content {
                grid-template-columns: 1fr;
            }
        }
    `;
    document.head.appendChild(style);
}

// Price range slider
function initPriceRangeSlider() {
    const slider = document.getElementById('price-range-slider');
    if (!slider) return;
    
    const minPrice = parseInt(slider.dataset.min);
    const maxPrice = parseInt(slider.dataset.max);
    
    // Sử dụng thư viện noUiSlider hoặc tự implement
    // Code implementation tùy thuộc vào thư viện
}

// Auto-save form data to localStorage
function autoSaveForm(formId) {
    const form = document.getElementById(formId);
    if (!form) return;
    
    // Load saved data
    const savedData = localStorage.getItem(formId);
    if (savedData) {
        const data = JSON.parse(savedData);
        Object.keys(data).forEach(key => {
            const input = form.querySelector(`[name="${key}"]`);
            if (input) {
                input.value = data[key];
            }
        });
    }
    
    // Save on input
    form.querySelectorAll('input, textarea, select').forEach(input => {
        input.addEventListener('input', () => {
            const formData = new FormData(form);
            const data = {};
            formData.forEach((value, key) => {
                data[key] = value;
            });
            localStorage.setItem(formId, JSON.stringify(data));
        });
    });
    
    // Clear on submit
    form.addEventListener('submit', () => {
        localStorage.removeItem(formId);
    });
}

// Copy to clipboard
function copyToClipboard(text) {
    navigator.clipboard.writeText(text).then(() => {
        showNotification('success', 'Đã sao chép vào clipboard');
    }).catch(err => {
        console.error('Error copying text: ', err);
        showNotification('error', 'Không thể sao chép');
    });
}

// Share product
function shareProduct(url, title) {
    if (navigator.share) {
        navigator.share({
            title: title,
            url: url
        }).then(() => {
            console.log('Shared successfully');
        }).catch(err => {
            console.error('Error sharing:', err);
        });
    } else {
        // Fallback - copy link
        copyToClipboard(url);
    }
}

// Print functionality
function printPage() {
    window.print();
}

// Initialize all functions when DOM is ready
document.addEventListener('DOMContentLoaded', function() {
    initBannerSlider();
    initMobileMenu();
    initSearch();
    initLazyLoading();
    initScrollToTop();
    initCountdown();
    initProductZoom();
    
    // Close flash messages
    const closeButtons = document.querySelectorAll('.alert .close-btn');
    closeButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            this.parentElement.style.display = 'none';
        });
    });
    
    // Auto-hide flash messages after 5 seconds
    const alerts = document.querySelectorAll('.alert');
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.style.display = 'none';
            }, 300);
        }, 5000);
    });
});

// Utility functions
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function throttle(func, limit) {
    let inThrottle;
    return function() {
        const args = arguments;
        const context = this;
        if (!inThrottle) {
            func.apply(context, args);
            inThrottle = true;
            setTimeout(() => inThrottle = false, limit);
        }
    };
}

// Export functions for global use
window.diepanhShop = {
    addToCart,
    buyNow,
    removeFromCart,
    toggleWishlist,
    addToCompare,
    quickView,
    shareProduct,
    copyToClipboard,
    showNotification
};