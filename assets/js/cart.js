// assets/js/cart.js

// Thêm sản phẩm vào giỏ hàng
function addToCart(productId) {
    const quantityInput = document.getElementById('quantity');
    // Lấy số lượng từ input nếu tồn tại, mặc định là 1 nếu không có input (ví dụ: trang danh sách sản phẩm)
    const quantity = quantityInput ? quantityInput.value : 1;

    // Tạo FormData
    const formData = new FormData();
    formData.append('action', 'add');
    formData.append('product_id', productId);
    formData.append('quantity', parseInt(quantity));

    fetch('api/cart-handler.php', {
        method: 'POST',
        // Không cần 'Content-Type' header khi dùng FormData, trình duyệt tự xử lý
        body: formData // Gửi dưới dạng form data
    })
    .then(response => response.json()) // Vẫn mong đợi nhận về JSON
    .then(data => {
        if (data.success) {
            showNotification('success', data.message);
            updateCartCount(); // Cập nhật số lượng trên icon giỏ hàng
        } else {
            showNotification('error', data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        showNotification('error', 'Có lỗi xảy ra khi thêm vào giỏ. Vui lòng thử lại!');
    });
}

// Mua ngay
function buyNow(productId) {
    const quantityInput = document.getElementById('quantity');
    const quantity = quantityInput ? quantityInput.value : 1;

    // Tạo FormData
    const formData = new FormData();
    formData.append('action', 'add'); // Hành động vẫn là 'add' để thêm vào giỏ trước khi chuyển trang
    formData.append('product_id', productId);
    formData.append('quantity', parseInt(quantity));

    fetch('api/cart-handler.php', {
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
}

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

    fetch('api/cart-handler.php', { // Đảm bảo URL đúng từ trang cart.php
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
function removeFromCart(productId) {
    // Không cần confirm nữa vì đã có trong hàm updateCartQuantity hoặc gọi trực tiếp từ nút xóa
    // if (!confirm('Bạn có chắc muốn xóa sản phẩm này?')) {
    //     return;
    // }

    const formData = new FormData();
    formData.append('action', 'remove');
    formData.append('product_id', productId);

    fetch('api/cart-handler.php', { // Đảm bảo URL đúng từ trang cart.php
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
}


// --- Các hàm còn lại (updateCartCount, applyCoupon, showNotification, ...) giữ nguyên ---
// ... (Các hàm khác không thay đổi) ...

// Cập nhật số lượng giỏ hàng trong header (chỉ khi chưa có hàm này)
if (typeof updateCartCount === 'undefined') {
    function updateCartCount() {
        fetch('api/cart-handler.php?action=count') // GET request is fine here
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
    }
}
// ... (Phần còn lại của file cart.js)

// Áp dụng mã giảm giá
function applyCoupon() {
    const couponCode = document.getElementById('coupon_code').value.trim();
    
    if (!couponCode) {
        showNotification('warning', 'Vui lòng nhập mã giảm giá');
        return;
    }
    
    fetch('api/coupon-handler.php', {
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
function showNotification(type, message) {
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
}

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
    
    fetch('api/cart-handler.php', {
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
// Thêm vào cuối file assets/js/cart.js

function applyCoupon() {
    const couponCode = document.getElementById('coupon_code').value.trim();
    const btn = document.getElementById('apply-coupon-btn');
    
    if (!couponCode) {
        alert('Vui lòng nhập mã giảm giá');
        return;
    }
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Đang xử lý...';
    
    fetch('../ajax/apply-coupon.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'coupon_code=' + encodeURIComponent(couponCode)
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Hiển thị giảm giá
            document.getElementById('discount').textContent = data.discount_formatted;
            document.getElementById('discount').setAttribute('data-value', data.discount);
            document.getElementById('total').textContent = data.new_total_formatted;
            
            alert('Áp dụng mã giảm giá thành công!');
        } else {
            alert(data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Có lỗi xảy ra, vui lòng thử lại');
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = 'Áp dụng';
    });
}