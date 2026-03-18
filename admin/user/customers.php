<?php
// admin/users/customers.php - Quản lý khách hàng

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/User.php';

requireStaff();

$userModel = new User();

// Filters & pagination
$filters = [];
$filters['keyword'] = $_GET['q'] ?? '';
$filters['status'] = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Filter customers (server-side for now)
$allCustomers = $userModel->getAllCustomers();
$filtered = array_filter($allCustomers, function($cust) use ($filters) {
    $ok = true;
    if ($filters['keyword']) {
        $kw = mb_strtolower($filters['keyword']);
        $ok = $ok && (
            mb_strpos(mb_strtolower($cust['username']), $kw) !== false ||
            mb_strpos(mb_strtolower($cust['full_name']), $kw) !== false ||
            mb_strpos(mb_strtolower($cust['email']), $kw) !== false ||
            mb_strpos(mb_strtolower($cust['phone']), $kw) !== false
        );
    }
    if ($filters['status'] !== '') {
        $ok = $ok && ((string)$cust['status'] === $filters['status']);
    }
    return $ok;
});
$total = count($filtered);
$customers = array_slice(array_values($filtered), $offset, $perPage);

$pageTitle = 'Quản lý khách hàng - Admin';
$activeMenu = 'customers';
include __DIR__ . '/../layout.php';
?>
            <div class="section-header d-flex justify-between align-center" style="margin-bottom: 20px;">
                <h1 class="section-title"><i class="fas fa-users"></i> Quản lý khách hàng</h1>
                <div class="d-flex gap-10">
                    <button id="exportCustomersBtn" class="btn btn-success">Xuất CSV</button>
                </div>
            </div>

            <div class="admin-toolbar d-flex justify-between mb-20">
                <form method="GET" style="display:flex; gap:10px; align-items:center;">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Tìm username, tên, email, SĐT..." class="form-control">
                    <select name="status" class="form-control">
                        <option value="">Tất cả</option>
                        <option value="1" <?php echo $filters['status'] === '1' ? 'selected' : ''; ?>>Hoạt động</option>
                        <option value="0" <?php echo $filters['status'] === '0' ? 'selected' : ''; ?>>Khóa</option>
                    </select>
                    <button class="btn btn-primary">Lọc</button>
                </form>
                <div class="d-flex gap-10">
                    <button id="bulkUnlockBtn" class="btn btn-success">Kích hoạt</button>
                    <button id="bulkLockBtn" class="btn btn-warning">Khóa</button>
                </div>
            </div>

            <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="data-table">
                <form id="bulkCustomersForm">
                <table>
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="selectAll"></th>
                            <th>ID</th>
                            <th>Username</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>Điện thoại</th>
                            <th>Ngày đăng ký</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                        <tr data-id="<?php echo $customer['id']; ?>">
                            <td><input type="checkbox" name="ids[]" value="<?php echo $customer['id']; ?>"></td>
                            <td><?php echo $customer['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($customer['username']); ?></strong></td>
                            <td><?php echo htmlspecialchars($customer['full_name']); ?></td>
                            <td><?php echo htmlspecialchars($customer['email']); ?></td>
                            <td><?php echo htmlspecialchars($customer['phone']); ?></td>
                            <td><?php echo formatDate($customer['created_at'], 'd/m/Y'); ?></td>
                            <td><?php echo $customer['status'] ? '<span class="badge badge-success">Hoạt động</span>' : '<span class="badge badge-secondary">Khóa</span>'; ?></td>
                            <td>
                                <a href="#" class="btn btn-sm btn-primary view-customer-detail" 
                                   data-id="<?php echo $customer['id']; ?>" 
                                   title="Chi tiết">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </form>

                <!-- Pagination -->
                <?php if ($total > $perPage): ?>
                <div class="pagination mt-20">
                    <?php
                    $pages = ceil($total / $perPage);
                    for ($p = 1; $p <= $pages; $p++):
                        $qs = $_GET; $qs['page'] = $p; $link = '?'.http_build_query($qs);
                    ?>
                        <a href="<?php echo $link; ?>" class="<?php echo $p == $page ? 'active' : ''; ?>"><?php echo $p; ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <!-- Modal chi tiết khách hàng -->
    <div id="customerDetailModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="background-color: #fefefe; margin: 5% auto; padding: 0; border: none; border-radius: 8px; width: 90%; max-width: 800px; box-shadow: 0 4px 20px rgba(0,0,0,0.3);">
            <div class="modal-header" style="padding: 20px; border-bottom: 2px solid #3498db; display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0; color: #2c3e50;">
                    <i class="fas fa-user"></i> Chi tiết khách hàng
                </h2>
                <span class="close-modal" style="color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 20px;">&times;</span>
            </div>
            <div class="modal-body" style="padding: 20px; max-height: 70vh; overflow-y: auto;">
                <div id="customerDetailContent" style="text-align: center; padding: 40px;">
                    <i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #3498db;"></i>
                    <p>Đang tải thông tin...</p>
                </div>
            </div>
        </div>
    </div>

    <style>
    .modal-content {
        animation: modalSlideIn 0.3s ease;
    }
    @keyframes modalSlideIn {
        from {
            transform: translateY(-50px);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    .close-modal:hover {
        color: #000;
    }
    .customer-detail-section {
        margin-bottom: 25px;
        padding: 15px;
        background: #f8f9fa;
        border-radius: 6px;
        border-left: 4px solid #3498db;
    }
    .customer-detail-section h3 {
        margin-top: 0;
        color: #2c3e50;
        border-bottom: 1px solid #dee2e6;
        padding-bottom: 10px;
    }
    .detail-row {
        display: grid;
        grid-template-columns: 150px 1fr;
        gap: 10px;
        padding: 8px 0;
        border-bottom: 1px solid #e9ecef;
    }
    .detail-row:last-child {
        border-bottom: none;
    }
    .detail-label {
        font-weight: bold;
        color: #495057;
    }
    .detail-value {
        color: #212529;
    }
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
        gap: 15px;
        margin-top: 15px;
    }
    .stat-card {
        background: white;
        padding: 15px;
        border-radius: 6px;
        text-align: center;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .stat-card .stat-value {
        font-size: 24px;
        font-weight: bold;
        color: #3498db;
        margin: 10px 0;
    }
    .stat-card .stat-label {
        color: #6c757d;
        font-size: 14px;
    }
    .orders-list {
        margin-top: 15px;
    }
    .order-item {
        background: white;
        padding: 12px;
        margin-bottom: 10px;
        border-radius: 6px;
        border-left: 3px solid #3498db;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    </style>

    <script>
    document.addEventListener('DOMContentLoaded', function(){
        const selectAll = document.getElementById('selectAll');
        if (selectAll) {
            selectAll.addEventListener('change', function(){
                document.querySelectorAll('#bulkCustomersForm tbody input[type=checkbox]').forEach(cb => cb.checked = this.checked);
            });
        }
        function getSelectedIds(){
            return Array.from(document.querySelectorAll('#bulkCustomersForm tbody input[type=checkbox]:checked')).map(i => i.value);
        }
        function doBulkAction(action){
            const ids = getSelectedIds();
            if (ids.length === 0) { alert('Chọn ít nhất một khách hàng'); return; }
            if (!confirm('Xác nhận thực hiện: ' + action + ' trên ' + ids.length + ' khách hàng?')) return;
            fetch('bulk-handler.php', {
                method: 'POST', headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action, ids })
            }).then(r => r.json()).then(data => {
                if (data.success) window.location.reload(); else alert(data.message || 'Lỗi');
            }).catch(()=> alert('Lỗi mạng'));
        }
        var bulkUnlockBtn = document.getElementById('bulkUnlockBtn');
        if (bulkUnlockBtn) {
            bulkUnlockBtn.addEventListener('click', function(){ doBulkAction('bulk_unlock'); });
        }
        var bulkLockBtn = document.getElementById('bulkLockBtn');
        if (bulkLockBtn) {
            bulkLockBtn.addEventListener('click', function(){ doBulkAction('bulk_lock'); });
        }
        var exportCustomersBtn = document.getElementById('exportCustomersBtn');
        if (exportCustomersBtn) {
            exportCustomersBtn.addEventListener('click', function(){
                const rows = Array.from(document.querySelectorAll('.data-table tbody tr'));
                let csv = 'ID,Username,Full Name,Email,Phone,Registered,Status\n';
                rows.forEach(r=>{
                    const cols = r.querySelectorAll('td');
                    if (!cols.length) return;
                    const id = cols[1].innerText.trim();
                    const user = cols[2].innerText.trim();
                    const name = '"' + cols[3].innerText.trim().replace(/"/g,'""') + '"';
                    const email = cols[4].innerText.trim();
                    const phone = cols[5].innerText.trim();
                    const reg = cols[6].innerText.trim();
                    const status = cols[7].innerText.trim();
                    csv += [id, user, name, email, phone, reg, status].join(',') + '\n';
                });
                const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
                const link = document.createElement('a');
                link.href = URL.createObjectURL(blob);
                link.download = 'customers_export.csv';
                document.body.appendChild(link);
                link.click();
                link.remove();
            });
        }

        // Modal chi tiết khách hàng
        const modal = document.getElementById('customerDetailModal');
        const closeModal = document.querySelector('.close-modal');
        const customerDetailContent = document.getElementById('customerDetailContent');

        // Mở modal khi click vào nút xem chi tiết
        document.querySelectorAll('.view-customer-detail').forEach(btn => {
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const customerId = this.getAttribute('data-id');
                loadCustomerDetail(customerId);
                modal.style.display = 'block';
            });
        });

        // Đóng modal
        if (closeModal) {
            closeModal.addEventListener('click', function() {
                modal.style.display = 'none';
            });
        }

        window.addEventListener('click', function(event) {
            if (event.target === modal) {
                modal.style.display = 'none';
            }
        });

        // Hàm load chi tiết khách hàng
        function loadCustomerDetail(customerId) {
            customerDetailContent.innerHTML = '<div style="text-align: center; padding: 40px;"><i class="fas fa-spinner fa-spin" style="font-size: 32px; color: #3498db;"></i><p>Đang tải thông tin...</p></div>';
            
            fetch('get-customer-detail.php?id=' + customerId)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderCustomerDetail(data);
                    } else {
                        customerDetailContent.innerHTML = '<div class="alert alert-danger">' + (data.message || 'Không thể tải thông tin khách hàng') + '</div>';
                    }
                })
                .catch(error => {
                    customerDetailContent.innerHTML = '<div class="alert alert-danger">Lỗi khi tải thông tin: ' + error.message + '</div>';
                });
        }

        // Hàm render chi tiết khách hàng
        function renderCustomerDetail(data) {
            const customer = data.customer;
            const stats = data.statistics;
            const recentOrders = data.recent_orders;

            const statusText = customer.status ? 'Hoạt động' : 'Khóa';
            const statusClass = customer.status ? 'badge-success' : 'badge-secondary';
            
            const formatCurrency = (amount) => {
                return new Intl.NumberFormat('vi-VN', { style: 'currency', currency: 'VND' }).format(amount);
            };

            const formatDate = (dateString) => {
                if (!dateString) return 'N/A';
                const date = new Date(dateString);
                return date.toLocaleDateString('vi-VN', { 
                    year: 'numeric', 
                    month: '2-digit', 
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit'
                });
            };

            const getOrderStatusText = (status) => {
                const statuses = {
                    'pending': 'Chờ xác nhận',
                    'confirmed': 'Đã xác nhận',
                    'processing': 'Đang xử lý',
                    'shipping': 'Đang giao hàng',
                    'delivered': 'Đã giao hàng',
                    'cancelled': 'Đã hủy'
                };
                return statuses[status] || status;
            };

            let html = `
                <div class="customer-detail-section">
                    <h3><i class="fas fa-user-circle"></i> Thông tin cá nhân</h3>
                    <div class="detail-row">
                        <div class="detail-label">ID:</div>
                        <div class="detail-value">#${customer.id}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Username:</div>
                        <div class="detail-value"><strong>${customer.username}</strong></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Họ tên:</div>
                        <div class="detail-value">${customer.full_name || 'N/A'}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Email:</div>
                        <div class="detail-value">${customer.email}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Số điện thoại:</div>
                        <div class="detail-value">${customer.phone || 'N/A'}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Địa chỉ:</div>
                        <div class="detail-value">${customer.address || 'N/A'}</div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Trạng thái:</div>
                        <div class="detail-value"><span class="badge ${statusClass}">${statusText}</span></div>
                    </div>
                    <div class="detail-row">
                        <div class="detail-label">Ngày đăng ký:</div>
                        <div class="detail-value">${formatDate(customer.created_at)}</div>
                    </div>
                    ${customer.updated_at ? `
                    <div class="detail-row">
                        <div class="detail-label">Cập nhật lần cuối:</div>
                        <div class="detail-value">${formatDate(customer.updated_at)}</div>
                    </div>
                    ` : ''}
                </div>

                <div class="customer-detail-section">
                    <h3><i class="fas fa-chart-bar"></i> Thống kê</h3>
                    <div class="stats-grid">
                        <div class="stat-card">
                            <div class="stat-label">Tổng đơn hàng</div>
                            <div class="stat-value">${stats.total_orders}</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Tổng chi tiêu</div>
                            <div class="stat-value">${formatCurrency(stats.total_spent)}</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Chờ xác nhận</div>
                            <div class="stat-value">${stats.order_status.pending}</div>
                        </div>
                        <div class="stat-card">
                            <div class="stat-label">Đã giao</div>
                            <div class="stat-value">${stats.order_status.delivered}</div>
                        </div>
                    </div>
                </div>
            `;

            if (recentOrders && recentOrders.length > 0) {
                html += `
                    <div class="customer-detail-section">
                        <h3><i class="fas fa-shopping-cart"></i> Đơn hàng gần đây</h3>
                        <div class="orders-list">
                `;
                recentOrders.forEach(order => {
                    html += `
                        <div class="order-item">
                            <div>
                                <strong>${order.order_code}</strong>
                                <div style="font-size: 12px; color: #666; margin-top: 5px;">
                                    ${formatDate(order.created_at)} - ${formatCurrency(order.total)}
                                </div>
                            </div>
                            <div>
                                <span class="badge ${getOrderStatusClass(order.order_status)}">${getOrderStatusText(order.order_status)}</span>
                                <a href="../orders/detail.php?id=${order.id}" class="btn btn-sm btn-primary" style="margin-left: 10px;" target="_blank">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </div>
                        </div>
                    `;
                });
                html += `
                        </div>
                        ${stats.total_orders > 5 ? `<p style="text-align: center; margin-top: 15px;"><a href="../orders/index.php?q=${customer.id}" class="btn btn-primary">Xem tất cả đơn hàng</a></p>` : ''}
                    </div>
                `;
            } else {
                html += `
                    <div class="customer-detail-section">
                        <h3><i class="fas fa-shopping-cart"></i> Đơn hàng</h3>
                        <p style="text-align: center; color: #6c757d; padding: 20px;">Khách hàng chưa có đơn hàng nào</p>
                    </div>
                `;
            }

            customerDetailContent.innerHTML = html;
        }

        function getOrderStatusClass(status) {
            const classes = {
                'pending': 'badge-warning',
                'confirmed': 'badge-info',
                'processing': 'badge-primary',
                'shipping': 'badge-primary',
                'delivered': 'badge-success',
                'cancelled': 'badge-danger'
            };
            return classes[status] || 'badge-secondary';
        }
    });
    </script>

</body>
</html>