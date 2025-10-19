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
    $contactModel->deleteContact($_GET['delete']);
    setFlashMessage('success', 'Xóa liên hệ thành công');
    redirect('index.php');
}

$contacts = $contactModel->getAllContacts();
$pageTitle = 'Quản lý liên hệ - Admin';
$activeMenu = 'contacts';
include __DIR__ . '/../layout.php';
?>
            <h1><i class="fas fa-envelope"></i> Quản lý liên hệ</h1>
            
            <?php if ($flash = getFlashMessage()): ?>
                <div class="alert alert-<?php echo $flash['type']; ?>">
                    <?php echo htmlspecialchars($flash['message']); ?>
                </div>
            <?php endif; ?>
            
            <div class="data-table">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Họ tên</th>
                            <th>Email</th>
                            <th>SĐT</th>
                            <th>Tiêu đề</th>
                            <th>Ngày gửi</th>
                            <th>Trạng thái</th>
                            <th>Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($contacts as $contact): ?>
                        <tr>
                            <td><?php echo $contact['id']; ?></td>
                            <td><?php echo htmlspecialchars($contact['name']); ?></td>
                            <td><?php echo htmlspecialchars($contact['email']); ?></td>
                            <td><?php echo htmlspecialchars($contact['phone']); ?></td>
                            <td><?php echo htmlspecialchars($contact['subject']); ?></td>
                            <td><?php echo formatDate($contact['created_at'], 'd/m/Y H:i'); ?></td>
                            <td>
                                <form method="POST" style="display: inline;">
                                    <input type="hidden" name="contact_id" value="<?php echo $contact['id']; ?>">
                                    <select name="status" class="form-control" style="width: auto;" 
                                            onchange="if(confirm('Cập nhật trạng thái?')) this.form.submit()">
                                        <option value="pending" <?php echo $contact['status'] === 'pending' ? 'selected' : ''; ?>>Chờ xử lý</option>
                                        <option value="processing" <?php echo $contact['status'] === 'processing' ? 'selected' : ''; ?>>Đang xử lý</option>
                                        <option value="resolved" <?php echo $contact['status'] === 'resolved' ? 'selected' : ''; ?>>Đã xử lý</option>
                                    </select>
                                    <button type="submit" name="update_status" style="display: none;"></button>
                                </form>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-primary" onclick="viewMessage(<?php echo $contact['id']; ?>, '<?php echo htmlspecialchars($contact['message'], ENT_QUOTES); ?>')">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <a href="?delete=<?php echo $contact['id']; ?>" class="btn btn-sm btn-danger"
                                   onclick="return confirm('Xóa liên hệ này?')">
                                    <i class="fas fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </main>
    </div>
    
    <script>
        function viewMessage(id, message) {
            alert('Tin nhắn #' + id + ':\n\n' + message);
        }
    </script>
</body>
</html>