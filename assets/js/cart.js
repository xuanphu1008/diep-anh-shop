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

// Cập nhật hàm addToCart để có loading
window.addToCart = function(productId, btnElement = null) {
    // Nếu gọi từ nút, truyền 'this' vào onclick: onclick="addToCart(1, this)"
    // Nếu không truyền, tự tìm nút (nếu có thể) hoặc bỏ qua hiệu ứng
    if(btnElement) setBtnLoading(btnElement, true);

    const quantityInput = document.getElementById('quantity');
    let quantity = 1;
    if (quantityInput && quantityInput.value) {
        quantity = parseInt(quantityInput.value) || 1;
    }

    // ... (Giữ nguyên phần xác định đường dẫn API) ...
    const apiPath = window.location.pathname.includes('/customer/') || 
                     window.location.pathname.includes('/admin/') 
                     ? '../api/cart-handler.php' 
                     : 'api/cart-handler.php';

    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', parseInt(productId) || 0);
    formData.append('quantity', quantity);

    fetch(apiPath, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', data.message);
            updateCartCount();
        } else {
            showNotification('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'Lỗi kết nối!');
    })
    .finally(() => {
        if(btnElement) setBtnLoading(btnElement, false);
    });
};

// Thêm sản phẩm vào giỏ hàng
window.addToCart = function(productId) {
    const quantityInput = document.getElementById('quantity');
    let quantity = 1;
    if (quantityInput && quantityInput.value) {
        quantity = parseInt(quantityInput.value) || 1;
    }

    // Xác định đường dẫn API đúng (xử lý cả trang con)
    const apiPath = window.location.pathname.includes('/customer/') || 
                     window.location.pathname.includes('/admin/') 
                     ? '../api/cart-handler.php' 
                     : 'api/cart-handler.php';

    // Tạo FormData
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', parseInt(productId) || 0);
    formData.append('quantity', quantity);

    console.log('addToCart called with productId:', productId);
    console.log('API path:', apiPath);
    console.log('FormData:', {action: 'add', product_id: parseInt(productId), quantity: quantity});

    // Đảm bảo URL có .php
    const fullApiPath = apiPath.endsWith('.php') ? apiPath : apiPath + '.php';
    console.log('Full API path:', fullApiPath);

    fetch(fullApiPath, {
        method: 'POST',
        body: formData
    })
    .then(response => {
        console.log('Response status:', response.status);
        if (!response.ok) {
            throw new Error('Network response was not ok');
        }
        return response.json();
    })
    .then(data => {
        console.log('Response data:', data);
        if (data.success) {
            showNotification('success', data.message);
            updateCartCount();
        } else {
            showNotification('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'Có lỗi xảy ra khi thêm vào giỏ. Vui lòng thử lại!');
    });
};

// Mua ngay
window.buyNow = function(productId) {
    const quantityInput = document.getElementById('quantity');
    let quantity = 1;
    if (quantityInput && quantityInput.value) {
        quantity = parseInt(quantityInput.value) || 1;
    }

    // Tạo FormData
    const formData = new FormData();
    formData.append('action', 'add'); // Hành động vẫn là 'add' để thêm vào giỏ trước khi chuyển trang
    formData.append('product_id', parseInt(productId) || 0);
    formData.append('quantity', quantity);

    // Xác định đường dẫn API đúng (xử lý cả trang con)
    const apiPath = window.location.pathname.includes('/customer/') || 
                     window.location.pathname.includes('/admin/') 
                     ? '../api/cart-handler.php' 
                     : 'api/cart-handler.php';
    
    // Đảm bảo URL có .php
    const fullApiPath = apiPath.endsWith('.php') ? apiPath : apiPath + '.php';

    fetch(fullApiPath, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Thêm thành công, chuyển đến trang giỏ hàng
            window.location.href = 'customer/cart.php';
        } else {
            showNotification('error', data.message);
        }
    })
    .catch(error => {
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
    const apiPath = window.location.pathname.includes('/customer/') || 
                     window.location.pathname.includes('/admin/') 
                     ? '../api/cart-handler.php' 
                     : 'api/cart-handler.php';
    
    // Đảm bảo URL có .php
    const fullApiPath = apiPath.endsWith('.php') ? apiPath : apiPath + '.php';

    fetch(fullApiPath, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Cập nhật thành công, reload lại trang để hiển thị đúng
            location.reload();
        } else {
            showNotification('error', data.message);
            // Có thể cần khôi phục giá trị input về giá trị cũ nếu cập nhật thất bại
            location.reload(); // Reload tạm thời
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'Có lỗi xảy ra khi cập nhật số lượng. Vui lòng thử lại!');
        location.reload(); // Reload tạm thời
    });
}

// Xóa sản phẩm khỏi giỏ hàng (Trang cart.php)
window.removeFromCart = function(productId) {
    // Không cần confirm nữa vì đã có trong hàm updateCartQuantity hoặc gọi trực tiếp từ nút xóa
    // if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
    //     return;
    // }

    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('product_id', productId);

    // Xác định đường dẫn API đúng (xử lý cả trang con)
    const apiPath = window.location.pathname.includes('/customer/') || 
                     window.location.pathname.includes('/admin/') 
                     ? '../api/cart-handler.php' 
                     : 'api/cart-handler.php';
    
    // Đảm bảo URL có .php
    const fullApiPath = apiPath.endsWith('.php') ? apiPath : apiPath + '.php';

    fetch(fullApiPath, {
        method: 'POST',
        body: formData
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            showNotification('success', data.message);
            location.reload(); // Reload lại trang giỏ hàng
        } else {
            showNotification('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'Có lỗi xảy ra khi xóa sản phẩm. Vui lòng thử lại!');
    });
};


// --- Các hàm còn lại (updateCartCount, applyCoupon, showNotification, ...) giữ nguyên ---
// ... (Các hàm khác không thay đổi) ...

// Cập nhật số lượng giỏ hàng trong header
window.updateCartCount = function() {
    // Xác định đường dẫn API đúng (xử lý cả trang con)
    const apiPath = window.location.pathname.includes('/customer/') || 
                     window.location.pathname.includes('/admin/') 
                     ? '../api/cart-handler.php' 
                     : 'api/cart-handler.php';
    
    // Đảm bảo URL có .php
    const fullApiPath = (apiPath.endsWith('.php') ? apiPath : apiPath + '.php') + '?action=count';

    fetch(fullApiPath)
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const cartCountElement = document.querySelector('.cart-count');
            if (cartCountElement) {
                cartCountElement.textContent = data.count;
            }
        }
    })
    .catch(error => {
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
    
    // Xác định đường dẫn API đúng (xử lý cả trang con)
    const apiPath = window.location.pathname.includes('/customer/') || 
                     window.location.pathname.includes('/admin/') 
                     ? '../api/coupon-handler.php' 
                     : 'api/coupon-handler.php';
    
    // Đảm bảo URL có .php
    const fullApiPath = apiPath.endsWith('.php') ? apiPath : apiPath + '.php';
    
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
    // Xóa notification cũ nếu có
    const oldNotif = document.querySelector('.notification');
    if (oldNotif) {
        oldNotif.remove();
    }
    
    // Tạo notification mới
    const notification = document.createElement('div');
    notification.className = `notification notification-${type}`;
    notification.innerHTML = `
        <div class="notification-content">
            <i class="fas fa-${getNotificationIcon(type)}"></i>
            <span>${message}</span>
        </div>
    `;
    
    // Thêm CSS nếu chưa có
    if (!document.querySelector('#notification-styles')) {
        const style = document.createElement('style');
        style.id = 'notification-styles';
        style.textContent = `
            .notification {
                position: fixed;
                top: 80px;
                right: 20px;
                min-width: 300px;
                padding: 15px 20px;
                border-radius: 5px;
                box-shadow: 0 4px 12px rgba(0,0,0,0.2);
                z-index: 9999;
                animation: slideIn 0.3s ease;
            }
            .notification-content {
                display: flex;
                align-items: center;
                gap: 10px;
            }
            .notification-content i {
                font-size: 20px;
            }
            .notification-success {
                background: #28a745;
                color: white;
            }
            .notification-error {
                background: #dc3545;
                color: white;
            }
            .notification-warning {
                background: #ffc107;
                color: #333;
            }
            .notification-info {
                background: #17a2b8;
                color: white;
            }
            @keyframes slideIn {
                from {
                    transform: translateX(400px);
                    opacity: 0;
                }
                to {
                    transform: translateX(0);
                    opacity: 1;
                }
            }
            @keyframes slideOut {
                from {
                    transform: translateX(0);
                    opacity: 1;
                }
                to {
                    transform: translateX(400px);
                    opacity: 0;
                }
            }
        `;
        document.head.appendChild(style);
    }
    
    document.body.appendChild(notification);
    
    // Tự động ẩn sau 3 giây
    setTimeout(() => {
        notification.style.animation = 'slideOut 0.3s ease';
        setTimeout(() => {
            notification.remove();
        }, 300);
    }, 3000);
};

function getNotificationIcon(type) {
    const icons = {
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
    const discount = parseFloat(document.getElementById('discount')?.dataset.value || 0);
    const total = subtotal - discount;
    
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
    
    // Xác định đường dẫn API đúng (xử lý cả trang con)
    const apiPath = window.location.pathname.includes('/customer/') || 
                     window.location.pathname.includes('/admin/') 
                     ? '../api/cart-handler.php' 
                     : 'api/cart-handler.php';
    
    // Đảm bảo URL có .php
    const fullApiPath = apiPath.endsWith('.php') ? apiPath : apiPath + '.php';
    
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