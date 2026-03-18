<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/Database.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../models/Contact.php';

requireStaff();

$contactModel = new Contact();

if (isset($_POST['update_status'])) {
    $contactModel->updateContactStatus($_POST['contact_id'], $_POST['status']);
    setFlashMessage('success', 'Cập nhật trạng thái thành công');
    redirect('index.php');
}

if (isset($_GET['delete'])) {
    if (!isAdmin()) {
        setFlashMessage('error', 'Bạn không có quyền xóa liên hệ');
        redirect('index.php');
    }
    $contactModel->deleteContact($_GET['delete']);
    setFlashMessage('success', 'Xóa liên hệ thành công');
    redirect('index.php');
}

$filters = [];
$filters['keyword'] = $_GET['q'] ?? '';
$filters['status'] = $_GET['status'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

if (method_exists($contactModel, 'getAdminContacts')) {
    $total = $contactModel->countAdminContacts($filters);
    $contacts = $contactModel->getAdminContacts($filters, $perPage, $offset);
} else {
    $allContacts = $contactModel->getAllContacts();
    $filtered = array_filter($allContacts, function($c) use ($filters) {
        $ok = true;
        if ($filters['keyword']) {
            $kw = mb_strtolower($filters['keyword']);
            $ok = $ok && (mb_strpos(mb_strtolower($c['name']), $kw) !== false || mb_strpos(mb_strtolower($c['email']), $kw) !== false);
        }
        if ($filters['status'] !== '') $ok = $ok && ($c['status'] === $filters['status']);
        return $ok;
    });
    $total = count($filtered);
    $contacts = array_slice(array_values($filtered), $offset, $perPage);
}

$pageTitle = 'Quản lý liên hệ - Admin';
$activeMenu = 'contacts';
include __DIR__ . '/../layout.php';
?>
            <div class="section-header d-flex justify-between align-center" style="margin-bottom: 20px;">
                <h1 class="section-title"><i class="fas fa-envelope"></i> Quản lý liên hệ</h1>
                <button id="exportContactsBtn" class="btn btn-success">Xuất CSV</button>
            </div>
            
            <div class="admin-toolbar d-flex justify-between mb-20">
                <form method="GET" style="display:flex; gap:10px; align-items:center;">
                    <input type="text" name="q" value="<?php echo htmlspecialchars($filters['keyword']); ?>" placeholder="Tìm tên, email..." class="form-control">
                    <select name="status" class="form-control">
                        <option value="">Tất cả</option>
                        <option value="pending" <?php echo $filters['status'] === 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                        <option value="processing" <?php echo $filters['status'] === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                        <option value="resolved" <?php echo $filters['status'] === 'resolved' ? 'selected' : ''; ?>>Đã xử lý</option>
                    </select>
                    <button class="btn btn-primary">Lọc</button>
                </form>
                <div class="d-flex gap-10">
                    <button id="bulkMarkReadBtn" class="btn btn-success">Đánh dấu đã đọc</button>
                    <?php if (isAdmin()): ?>
                    <button id="bulkDeleteBtn" class="btn btn-danger">Xóa</button>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="data-table">
                <form id="bulkContactsForm">
                <table>
                    <thead>
                        <tr>
                            <th style="width:40px;"><input type="checkbox" id="selectAll"></th>
                            <th>ID</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>SĐT</th>
                            <th>Tiêu đề</th>
                            <th>Trạng thái</th>
                            <th>Ngày gửi</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contacts as $contact): ?>
                        <tr data-id="<?php echo $contact['id']; ?>">
                            <td><input type="checkbox" name="ids[]" value="<?php echo $contact['id']; ?>"></td>
                            <td><?php echo $contact['id']; ?></td>
                            <td><?php echo htmlspecialchars($contact['name']); ?></td>
                            <td><?php echo htmlspecialchars($contact['email']); ?></td>
                            <td><?php echo htmlspecialchars($contact['phone']); ?></td>
                            <td><?php echo htmlspecialchars($contact['subject']); ?></td>
                            <td><span class="badge badge-<?php echo $contact['status'] === 'resolved' ? 'success' : ($contact['status'] === 'processing' ? 'warning' : 'secondary'); ?>"><?php echo ['pending' => 'Chờ xử lý', 'processing' => 'Đang xử lý', 'resolved' => 'Đã xử lý'][$contact['status']] ?? $contact['status']; ?></span></td>
                            <td><?php echo formatDate($contact['created_at'], 'd/m/Y H:i'); ?></td>
                            <td>
                                <button class="btn btn-sm btn-primary view-contact-detail" 
                                        data-id="<?php echo $contact['id']; ?>"
                                        data-name="<?php echo htmlspecialchars($contact['name'], ENT_QUOTES); ?>"
                                        data-email="<?php echo htmlspecialchars($contact['email'], ENT_QUOTES); ?>"
                                        data-phone="<?php echo htmlspecialchars($contact['phone'] ?? '', ENT_QUOTES); ?>"
                                        data-subject="<?php echo htmlspecialchars($contact['subject'] ?? '', ENT_QUOTES); ?>"
                                        data-message="<?php echo htmlspecialchars($contact['message'], ENT_QUOTES); ?>"
                                        data-status="<?php echo htmlspecialchars($contact['status'], ENT_QUOTES); ?>"
                                        data-created="<?php echo htmlspecialchars($contact['created_at'], ENT_QUOTES); ?>"
                                        title="Chi tiết">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <?php if (isAdmin()): ?>
                                <a href="?delete=<?php echo $contact['id']; ?>" class="btn btn-sm btn-danger" onclick="return confirm('Xóa liên hệ này?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                </form>
            </div>
            
            <?php if ($total > $perPage): ?>
            <div class="pagination mt-20">
                <?php for ($p = 1; $p <= ceil($total / $perPage); $p++): $qs = $_GET; $qs['page'] = $p; ?>
                <a href="?<?php echo http_build_query($qs); ?>" class="<?php echo $p == $page ? 'active' : ''; ?>"><?php echo $p; ?></a>
                <?php endfor; ?>
            </div>
            <?php endif; ?>
        </main>
    </div>

    <!-- Modal chi tiết liên hệ -->
    <div id="contactDetailModal" class="modal" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(0,0,0,0.5);">
        <div class="modal-content" style="background-color: var(--admin-card); margin: 5% auto; padding: 0; border: none; border-radius: 8px; width: 90%; max-width: 700px; box-shadow: 0 4px 20px rgba(2, 40, 89, 0.3);">
            <div class="modal-header" style="padding: 20px; border-bottom: 2px solid var(--admin-primary); display: flex; justify-content: space-between; align-items: center;">
                <h2 style="margin: 0; color: var(--admin-text);">
                    <i class="fas fa-envelope"></i> Chi tiết liên hệ
                </h2>
                <span class="close-modal" style="color: #aaa; font-size: 28px; font-weight: bold; cursor: pointer; line-height: 20px;">&times;</span>
            </div>
            <div class="modal-body" style="padding: 20px;">
                <div id="contactDetailContent"></div>
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
        color: var(--admin-text);
    }
    .contact-detail-row {
        display: grid;
        grid-template-columns: 150px 1fr;
        gap: 10px;
        padding: 12px 0;
        border-bottom: 1px solid #e9ecef;
    }
    .contact-detail-row:last-child {
        border-bottom: none;
    }
    .contact-detail-label {
        font-weight: bold;
        color: var(--admin-text-light);
    }
    .contact-detail-value {
        color: var(--admin-text);
    }
    .contact-message-box {
        background: var(--admin-primary-very-pale);
        border-left: 4px solid var(--admin-primary);
        padding: 15px;
        margin-top: 15px;
        border-radius: 4px;
        white-space: pre-wrap;
        word-wrap: break-word;
    }
    </style>
    
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Select all checkbox
            const selectAllCheckbox = document.getElementById('selectAll');
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    document.querySelectorAll('#bulkContactsForm input[name="ids[]"]').forEach(cb => cb.checked = this.checked);
                });
            }
            
            // Bulk mark as read
            const bulkMarkReadBtn = document.getElementById('bulkMarkReadBtn');
            if (bulkMarkReadBtn) {
                bulkMarkReadBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const ids = Array.from(document.querySelectorAll('#bulkContactsForm input[name="ids[]"]:checked')).map(cb => cb.value);
                    if (ids.length === 0) {
                        alert('Vui lòng chọn ít nhất 1 liên hệ');
                        return;
                    }
                    
                    // Disable button during request
                    this.disabled = true;
                    this.textContent = 'Đang xử lý...';
                    
                    fetch('bulk-handler.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({action: 'bulk_mark_read', ids: ids})
                    })
                    .then(response => {
                        // Đọc response text trước để debug
                        return response.text().then(text => {
                            try {
                                const data = JSON.parse(text);
                                if (!response.ok) {
                                    // Nếu response không ok nhưng có JSON, trả về data
                                    return Promise.reject({data: data, status: response.status});
                                }
                                return data;
                            } catch (e) {
                                // Nếu không parse được JSON, trả về text
                                throw new Error('Server response: ' + text + ' (Status: ' + response.status + ')');
                            }
                        });
                    })
                    .then(data => {
                        if (data.success) {
                            alert(data.message || 'Đánh dấu đã đọc thành công');
                            location.reload();
                        } else {
                            alert(data.message || 'Có lỗi xảy ra');
                            this.disabled = false;
                            this.textContent = 'Đánh dấu đã đọc';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        let errorMessage = 'Có lỗi xảy ra khi đánh dấu đã đọc';
                        if (error.data && error.data.message) {
                            errorMessage = error.data.message;
                        } else if (error.message) {
                            errorMessage = error.message;
                        }
                        alert(errorMessage);
                        this.disabled = false;
                        this.textContent = 'Đánh dấu đã đọc';
                    });
                });
            }
            
            // Bulk delete
            const bulkDeleteBtn = document.getElementById('bulkDeleteBtn');
            if (bulkDeleteBtn) {
                bulkDeleteBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    const ids = Array.from(document.querySelectorAll('#bulkContactsForm input[name="ids[]"]:checked')).map(cb => cb.value);
                    if (ids.length === 0) {
                        alert('Vui lòng chọn ít nhất 1 liên hệ');
                        return;
                    }
                    if (!confirm('Bạn có chắc chắn muốn xóa ' + ids.length + ' liên hệ đã chọn?')) {
                        return;
                    }
                    
                    this.disabled = true;
                    this.textContent = 'Đang xóa...';
                    
                    fetch('bulk-handler.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({action: 'bulk_delete', ids: ids})
                    })
                    .then(response => {
                        if (!response.ok) {
                            throw new Error('Network response was not ok');
                        }
                        return response.json();
                    })
                    .then(data => {
                        if (data.success) {
                            alert(data.message || 'Xóa thành công');
                            location.reload();
                        } else {
                            alert(data.message || 'Có lỗi xảy ra');
                            this.disabled = false;
                            this.textContent = 'Xóa';
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Có lỗi xảy ra khi xóa: ' + error.message);
                        this.disabled = false;
                        this.textContent = 'Xóa';
                    });
                });
            }
            
            // Export CSV
            const exportContactsBtn = document.getElementById('exportContactsBtn');
            if (exportContactsBtn) {
                exportContactsBtn.addEventListener('click', function() {
                    const rows = document.querySelectorAll('#bulkContactsForm table tbody tr');
                    let csv = '"ID","Họ tên","Email","SĐT","Tiêu đề","Trạng thái","Ngày gửi"\n';
                    rows.forEach(row => {
                        const cells = row.querySelectorAll('td');
                        if (cells.length >= 8) {
                            const values = [cells[1], cells[2], cells[3], cells[4], cells[5], cells[6], cells[7]].map(c => '"' + (c.textContent || '').trim().replace(/"/g, '""') + '"');
                            csv += values.join(',') + '\n';
                        }
                    });
                    const blob = new Blob([csv], {type: 'text/csv;charset=utf-8;'});
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = 'contacts-' + new Date().toISOString().split('T')[0] + '.csv';
                    link.click();
                });
            }

            // Modal chi tiết liên hệ
            const contactModal = document.getElementById('contactDetailModal');
            const contactDetailContent = document.getElementById('contactDetailContent');
            const closeContactModal = document.querySelector('.close-modal');

            if (contactModal && contactDetailContent) {
                // Event listeners cho các nút xem chi tiết
                document.querySelectorAll('.view-contact-detail').forEach(btn => {
                    btn.addEventListener('click', function(e) {
                        e.preventDefault();
                        e.stopPropagation();
                        
                        const data = {
                            id: this.getAttribute('data-id') || '',
                            name: this.getAttribute('data-name') || '',
                            email: this.getAttribute('data-email') || '',
                            phone: this.getAttribute('data-phone') || '',
                            subject: this.getAttribute('data-subject') || '',
                            message: this.getAttribute('data-message') || '',
                            status: this.getAttribute('data-status') || 'pending',
                            created: this.getAttribute('data-created') || ''
                        };

                        const statusText = {
                            'pending': 'Chờ xử lý',
                            'processing': 'Đang xử lý',
                            'resolved': 'Đã xử lý'
                        };
                        const statusClass = {
                            'pending': 'badge-secondary',
                            'processing': 'badge-warning',
                            'resolved': 'badge-success'
                        };

                        const formatDate = (dateString) => {
                            if (!dateString) return 'N/A';
                            try {
                                const date = new Date(dateString);
                                if (isNaN(date.getTime())) return dateString;
                                return date.toLocaleDateString('vi-VN', { 
                                    year: 'numeric', 
                                    month: '2-digit', 
                                    day: '2-digit',
                                    hour: '2-digit',
                                    minute: '2-digit'
                                });
                            } catch (e) {
                                return dateString;
                            }
                        };

                        // Escape HTML để tránh XSS
                        const escapeHtml = (text) => {
                            if (!text) return '';
                            const div = document.createElement('div');
                            div.textContent = String(text);
                            return div.innerHTML;
                        };
                        
                        contactDetailContent.innerHTML = `
                            <div class="contact-detail-row">
                                <div class="contact-detail-label">ID:</div>
                                <div class="contact-detail-value">#${escapeHtml(data.id)}</div>
                            </div>
                            <div class="contact-detail-row">
                                <div class="contact-detail-label">Họ tên:</div>
                                <div class="contact-detail-value"><strong>${escapeHtml(data.name)}</strong></div>
                            </div>
                            <div class="contact-detail-row">
                                <div class="contact-detail-label">Email:</div>
                                <div class="contact-detail-value"><a href="mailto:${escapeHtml(data.email)}">${escapeHtml(data.email)}</a></div>
                            </div>
                            <div class="contact-detail-row">
                                <div class="contact-detail-label">Số điện thoại:</div>
                                <div class="contact-detail-value">${escapeHtml(data.phone || 'N/A')}</div>
                            </div>
                            <div class="contact-detail-row">
                                <div class="contact-detail-label">Tiêu đề:</div>
                                <div class="contact-detail-value">${escapeHtml(data.subject || 'N/A')}</div>
                            </div>
                            <div class="contact-detail-row">
                                <div class="contact-detail-label">Trạng thái:</div>
                                <div class="contact-detail-value"><span class="badge ${statusClass[data.status] || 'badge-secondary'}">${statusText[data.status] || escapeHtml(data.status)}</span></div>
                            </div>
                            <div class="contact-detail-row">
                                <div class="contact-detail-label">Ngày gửi:</div>
                                <div class="contact-detail-value">${formatDate(data.created)}</div>
                            </div>
                            <div style="margin-top: 20px;">
                                <div class="contact-detail-label" style="margin-bottom: 10px;">Nội dung tin nhắn:</div>
                                <div class="contact-message-box">${escapeHtml(data.message).replace(/\n/g, '<br>')}</div>
                            </div>
                        `;
                        contactModal.style.display = 'block';
                    });
                });

                // Đóng modal khi click vào nút đóng
                if (closeContactModal) {
                    closeContactModal.addEventListener('click', function() {
                        contactModal.style.display = 'none';
                    });
                }

                // Đóng modal khi click ra ngoài
                window.addEventListener('click', function(event) {
                    if (event.target === contactModal) {
                        contactModal.style.display = 'none';
                    }
                });
            }
        });
    </script>
</body>
</html>