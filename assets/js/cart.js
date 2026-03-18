// Hàm loading cho nút bấm
function setBtnLoading(btn, isLoading) {
    if (isLoading) {
        btn.dataset.originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
        btn.disabled = true;
    } else {
        btn.innerHTML = btn.dataset.originalText;
        btn.disabled = false;
    }
}

// Thêm sản phẩm vào giỏ hàng
window.addToCart = function(productId, btnElement) {
    if(btnElement) setBtnLoading(btnElement, true);

    var quantityInput = document.getElementById('quantity');
    var quantity = 1;
    if (quantityInput && quantityInput.value) {
        quantity = parseInt(quantityInput.value) || 1;
    }

    var fullApiPath = (typeof apiUrl === 'function') ? apiUrl('api/cart-handler.php') : (window.location.pathname.includes('/customer/') || window.location.pathname.includes('/admin/') ? '../api/cart-handler.php' : 'api/cart-handler.php');

    var formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', parseInt(productId) || 0);
    formData.append('quantity', quantity);

    console.log('addToCart called with productId:', productId);
    console.log('Full API path:', fullApiPath);
    console.log('FormData:', {action: 'add', product_id: parseInt(productId), quantity: quantity});

    fetch(fullApiPath, {
        method: 'POST',
        body: formData
    })
    .then(function(response) {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(function(data) {
        console.log('Response data:', data);
        if (data.success) {
            showNotification('success', data.message);
            updateCartCount();
        } else {
            showNotification('error', data.message);
        }
    })
    .catch(function(error) {
        console.error('Error:', error);
        showNotification('error', 'Có lỗi xảy ra khi thêm vào giỏ. Vui lòng thử lại!');
    })
    .finally(function() {
        if(btnElement) setBtnLoading(btnElement, false);
    });
};

// Mua ngay
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

    var fullApiPath = (typeof apiUrl === 'function') ? apiUrl('api/cart-handler.php') : (window.location.pathname.includes('/customer/') || window.location.pathname.includes('/admin/') ? '../api/cart-handler.php' : 'api/cart-handler.php');

    fetch(fullApiPath, {
        method: 'POST',
        body: formData
    })
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            window.location.href = 'customer/cart.php';
        } else {
            showNotification('error', data.message);
        }
    })
    .catch(function(error) {
        console.error('Error:', error);
        showNotification('error', 'Có lỗi xảy ra khi mua ngay. Vui lòng thử lại!');
    });
};

// --- Cập nhật các hàm khác nếu chúng cũng dùng JSON POST ---

// Cập nhật số lượng trong giỏ hàng (Trang cart.php)
function updateCartQuantity(productId, quantity) {
    if (quantity < 1) {
        if (confirm('Bạn muốn xóa sản phẩm này khỏi giỏ hàng?')) {
            removeFromCart(productId); // Gọi hàm xóa nếu số lượng < 1
        } else {
            // Nếu người dùng không muốn xóa, khôi phục lại giá trị input (cần cách lấy giá trị cũ)
            // Hoặc đơn giản là reload trang để lấy lại giá trị đúng
            location.reload();
        }
        return;
    }

    const formData = new FormData();
    formData.append('action', 'update');
    formData.append('product_id', productId);
    formData.append('quantity', parseInt(quantity));

    // Xác định đường dẫn API đúng (xử lý cả trang con)
    const fullApiPath = (typeof apiUrl === 'function') ? apiUrl('api/cart-handler.php') : (window.location.pathname.includes('/customer/') || window.location.pathname.includes('/admin/') ? '../api/cart-handler.php' : 'api/cart-handler.php');

    fetch(fullApiPath, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        // Luôn parse JSON trước, sau đó kiểm tra success
        return response.json().then(data => {
            if (!response.ok || !data.success) {
                throw new Error(data.message || 'Network response was not ok');
            }
            return data;
        }).catch(err => {
            // Nếu không parse được JSON, throw error gốc
            if (err instanceof Error && err.message) {
                throw err;
            }
            throw new Error('Network response was not ok');
        });
    })
    .then(data => {
        if (data.success) {
            if (typeof showNotification === 'function') {
                showNotification('success', data.message || 'Cập nhật số lượng thành công');
            } else if (typeof window.showNotification === 'function') {
                window.showNotification('success', data.message || 'Cập nhật số lượng thành công');
            }
            // Cập nhật thành công, reload lại trang để hiển thị đúng
            setTimeout(() => {
                location.reload();
            }, 500);
        } else {
            const errorMsg = data.message || 'Cập nhật số lượng thất bại';
            if (typeof showNotification === 'function') {
                showNotification('error', errorMsg);
            } else if (typeof window.showNotification === 'function') {
                window.showNotification('error', errorMsg);
            }
            // Reload để khôi phục giá trị cũ
            setTimeout(() => {
                location.reload();
            }, 1000);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        const errorMsg = error.message || 'Có lỗi xảy ra khi cập nhật số lượng. Vui lòng thử lại!';
        if (typeof showNotification === 'function') {
            showNotification('error', errorMsg);
        } else if (typeof window.showNotification === 'function') {
            window.showNotification('error', errorMsg);
        }
        // Reload để khôi phục giá trị cũ
        setTimeout(() => {
            location.reload();
        }, 1000);
    });
}

// Xóa sản phẩm khỏi giỏ hàng (Trang cart.php)
window.removeFromCart = function(productId) {
    if (!confirm('Bạn có chắc muốn xóa sản phẩm này khỏi giỏ hàng?')) {
        return;
    }
    
    var formData = new FormData();
    formData.append('action', 'remove');
    formData.append('product_id', productId);

    var fullApiPath = (typeof apiUrl === 'function') ? apiUrl('api/cart-handler.php') : (window.location.pathname.includes('/customer/') || window.location.pathname.includes('/admin/') ? '../api/cart-handler.php' : 'api/cart-handler.php');

    fetch(fullApiPath, {
        method: 'POST',
        body: formData
    })
    .then(function(response) {
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(function(data) {
        if (data.success) {
            if (typeof showNotification === 'function') {
                showNotification('success', data.message);
            } else if (typeof window.showNotification === 'function') {
                window.showNotification('success', data.message);
            }
            if (typeof updateCartCount === 'function') {
                updateCartCount();
            } else if (typeof window.updateCartCount === 'function') {
                window.updateCartCount();
            }
            setTimeout(function() {
                location.reload();
            }, 500);
        } else {
            if (typeof showNotification === 'function') {
                showNotification('error', data.message || 'Có lỗi xảy ra');
            } else if (typeof window.showNotification === 'function') {
                window.showNotification('error', data.message || 'Có lỗi xảy ra');
            }
        }
    })
    .catch(function(error) {
        console.error('Error:', error);
        if (typeof showNotification === 'function') {
            showNotification('error', 'Có lỗi xảy ra khi xóa sản phẩm. Vui lòng thử lại!');
        } else if (typeof window.showNotification === 'function') {
            window.showNotification('error', 'Có lỗi xảy ra khi xóa sản phẩm. Vui lòng thử lại!');
        }
    });
};


// --- Các hàm còn lại (updateCartCount, applyCoupon, showNotification, ...) giữ nguyên ---
// ... (Các hàm khác không thay đổi) ...

// Cập nhật số lượng giỏ hàng trong header
window.updateCartCount = function() {
    var fullApiPath = (typeof apiUrl === 'function') ? (apiUrl('api/cart-handler.php') + '?action=count') : ((window.location.pathname.includes('/customer/') || window.location.pathname.includes('/admin')) ? '../api/cart-handler.php?action=count' : 'api/cart-handler.php?action=count');

    fetch(fullApiPath)
    .then(function(response) { return response.json(); })
    .then(function(data) {
        if (data.success) {
            var cartCountElement = document.querySelector('.cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = data.count;
            }
        }
    })
    .catch(function(error) {
        console.error('Error fetching cart count:', error);
    });
};
// ... (Phần còn lại của file cart.js)

// Áp dụng mã giảm giá
function applyCoupon() {
    const couponCode = document.getElementById('coupon_code').value.trim();
    
    if (!couponCode) {
        showNotification('warning', 'Vui lòng nhập mã giảm giá');
        return;
    }
    
    const fullApiPath = (typeof apiUrl === 'function') ? apiUrl('api/coupon-handler.php') : (window.location.pathname.includes('/customer/') || window.location.pathname.includes('/admin/') ? '../api/coupon-handler.php' : 'api/coupon-handler.php');
    
    fetch(fullApiPath, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'apply',
            coupon_code: couponCode
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', data.message);
            location.reload();
        } else {
            showNotification('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'Có lỗi xảy ra. Vui lòng thử lại!');
    });
}

// Hiển thị thông báo
window.showNotification = function(type, message) {
    var oldNotif = document.querySelector('.notification');
    if (oldNotif) {
        oldNotif.remove();
    }
    
    var notification = document.createElement('div');
    notification.className = 'notification notification-' + type;
    notification.innerHTML = '<div class="notification-content"><i class="fas fa-' + getNotificationIcon(type) + '"></i><span>' + message + '</span></div>';
    
    if (!document.querySelector('#notification-styles')) {
        var style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = '.notification{position:fixed;top:80px;right:20px;min-width:300px;padding:15px 20px;border-radius:5px;box-shadow:0 4px 12px rgba(0,0,0,0.2);z-index:9999;animation:slideIn 0.3s ease}.notification-content{display:flex;align-items:center;gap:10px}.notification-content i{font-size:20px}.notification-success{background:#28a745;color:#fff}.notification-error{background:#dc3545;color:#fff}.notification-warning{background:#ffc107;color:#333}.notification-info{background:#17a2b8;color:#fff}@keyframes slideIn{from{transform:translateX(400px);opacity:0}to{transform:translateX(0);opacity:1}}@keyframes slideOut{from{transform:translateX(0);opacity:1}to{transform:translateX(400px);opacity:0}}';
        document.head.appendChild(style);
    }
    
    document.body.appendChild(notification);
    
    setTimeout(function() {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(function() {
            notification.remove();
        }, 300);
    }, 3000);
};

function getNotificationIcon(type) {
    var icons = {
        'success': 'check-circle',
        'error': 'exclamation-circle',
        'warning': 'exclamation-triangle',
        'info': 'info-circle'
    };
    return icons[type] || 'info-circle';
}

// Cập nhật tổng tiền trong trang giỏ hàng
function updateCartTotal() {
    let subtotal = 0;
    
    document.querySelectorAll('.cart-item').forEach(item => {
        const price = parseFloat(item.dataset.price);
        const quantity = parseInt(item.querySelector('.quantity-input input').value);
        const itemTotal = price * quantity;
        
        item.querySelector('.item-total').textContent = formatCurrency(itemTotal);
        subtotal += itemTotal;
    });
    
    document.getElementById('subtotal').textContent = formatCurrency(subtotal);
    
    // Tính discount nếu có
        var discountEl = document.getElementById('discount');
        var discount = 0;
        if (discountEl && discountEl.dataset && discountEl.dataset.value) {
            discount = parseFloat(discountEl.dataset.value) || 0;
        }
    const total = Math.max(0, subtotal - discount); // Đảm bảo total không bị âm
    
    document.getElementById('total').textContent = formatCurrency(total);
}

// Format tiền tệ
function formatCurrency(amount) {
    return new Intl.NumberFormat('vi-VN', {
        style: 'currency',
        currency: 'VND'
    }).format(amount);
}

// Xóa toàn bộ giỏ hàng
function clearCart() {
    if (!confirm('Bạn có chắc muốn xóa toàn bộ giỏ hàng?')) {
        return;
    }
    
    const fullApiPath = (typeof apiUrl === 'function') ? apiUrl('api/cart-handler.php') : (window.location.pathname.includes('/customer/') || window.location.pathname.includes('/admin/') ? '../api/cart-handler.php' : 'api/cart-handler.php');
    
    fetch(fullApiPath, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({
            action: 'clear'
        })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', 'Đã xóa toàn bộ giỏ hàng');
            setTimeout(() => {
                location.reload();
            }, 1000);
        } else {
            showNotification('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'Có lỗi xảy ra. Vui lòng thử lại!');
    });
}

// Event listeners khi trang load
document.addEventListener('DOMContentLoaded', function() {
    // Cập nhật số lượng giỏ hàng
    updateCartCount();
    
    // Xử lý thay đổi số lượng trong trang giỏ hàng
    const quantityInputs = document.querySelectorAll('.cart-item .quantity-input input');
    quantityInputs.forEach(input => {
        input.addEventListener('change', function() {
            const productId = this.closest('.cart-item').dataset.productId;
            const quantity = parseInt(this.value);
            updateCartQuantity(productId, quantity);
        });
    });
    
    // Nút xóa sản phẩm
    const removeButtons = document.querySelectorAll('.btn-remove-cart');
    removeButtons.forEach(button => {
        button.addEventListener('click', function() {
            const productId = this.dataset.productId;
            removeFromCart(productId);
        });
    });
    
    // Nút áp dụng coupon
    const applyCouponBtn = document.getElementById('apply-coupon-btn');
    if (applyCouponBtn) {
        applyCouponBtn.addEventListener('click', applyCoupon);
    }
    
    // Nút xóa toàn bộ giỏ hàng
    const clearCartBtn = document.getElementById('clear-cart-btn');
    if (clearCartBtn) {
        clearCartBtn.addEventListener('click', clearCart);
    }
});

// Xử lý tăng giảm số lượng
function increaseCartQty(productId) {
    const input = document.querySelector(`[data-product-id="${productId}"] .quantity-input input`);
    const max = parseInt(input.max);
    if (parseInt(input.value) < max) {
        input.value = parseInt(input.value) + 1;
        updateCartQuantity(productId, input.value);
    }
}

function decreaseCartQty(productId) {
    const input = document.querySelector(`[data-product-id="${productId}"] .quantity-input input`);
    if (parseInt(input.value) > 1) {
        input.value = parseInt(input.value) - 1;
        updateCartQuantity(productId, input.value);
    }
}