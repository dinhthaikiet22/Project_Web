<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/admin/admin_header.php';
/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

// Auto-migration bọc gọn: Tạo bảng settings
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS settings (
        id INT AUTO_INCREMENT PRIMARY KEY,
        key_name VARCHAR(50) NOT NULL UNIQUE,
        key_value TEXT
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Khởi tạo các giá trị mặc định nếu bảng chưa có dữ liệu
    $stmt = $conn->query("SELECT COUNT(*) FROM settings");
    if ($stmt->fetchColumn() == 0) {
        $defaults = [
            ['site_name', 'CycleTrust - Sàn giao dịch xe đạp uy tín'],
            ['hotline', '1900 1234'],
            ['contact_email', 'contact@cycletrust.com'],
            ['office_address', '123 Đường Điện Biên Phủ, Quận Bình Thạnh, TP.HCM']
        ];
        $insertQuery = "INSERT INTO settings (key_name, key_value) VALUES (?, ?)";
        $insertStmt = $conn->prepare($insertQuery);
        foreach ($defaults as $item) {
            $insertStmt->execute($item);
        }
    }
} catch (PDOException $e) {
    // Bỏ qua lỗi auto-migration nếu do DB perms
}

$msg = $_GET['msg'] ?? '';
$error = '';

// Xử lý POST lưu settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_settings') {
    $site_name = $_POST['site_name'] ?? '';
    $hotline = $_POST['hotline'] ?? '';
    $contact_email = $_POST['contact_email'] ?? '';
    $office_address = $_POST['office_address'] ?? '';

    try {
        $updateStmt = $conn->prepare("UPDATE settings SET key_value = ? WHERE key_name = ?");
        // Update từng key
        $updateStmt->execute([$site_name, 'site_name']);
        $updateStmt->execute([$hotline, 'hotline']);
        $updateStmt->execute([$contact_email, 'contact_email']);
        $updateStmt->execute([$office_address, 'office_address']);
        
        header("Location: ?page=admin_settings&msg=success");
        exit;
    } catch (PDOException $e) {
        $error = "Lỗi cập nhật cấu hình: " . $e->getMessage();
    }
}

// Fetch settings ra mảng key-value
$settings = [];
try {
    $stmt = $conn->query("SELECT key_name, key_value FROM settings");
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($results as $row) {
        $settings[$row['key_name']] = $row['key_value'];
    }
} catch (PDOException $e) {
    $error = "Lỗi tải cấu hình: " . $e->getMessage();
}

?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h3 fw-bold mb-0">Cài đặt hệ thống</h2>
</div>

<div class="row">
    <div class="col-lg-8">
        <div class="admin-card border-0 shadow-sm rounded-4">
            <div class="admin-card-title mb-4">
                <h4 class="h5 fw-bold mb-0"><i class="fa-solid fa-cogs text-primary me-2"></i>Cấu hình thông tin Website</h4>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger shadow-sm rounded mb-4"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="?page=admin_settings" method="POST">
                <input type="hidden" name="action" value="update_settings">
                
                <div class="mb-4">
                    <label for="site_name" class="form-label fw-semibold">Tên sàn (Site Name) <span class="text-danger">*</span></label>
                    <input type="text" id="site_name" name="site_name" class="form-control bg-light" value="<?= htmlspecialchars((string)($settings['site_name'] ?? 'CycleTrust'), ENT_QUOTES, 'UTF-8') ?>" required>
                    <small class="text-muted"><i class="fa-solid fa-info-circle me-1"></i> Tên này sẽ được hiển thị trên Tiêu đề trang (Title) hoặc Header / Footer.</small>
                </div>

                <div class="mb-4">
                    <label for="hotline" class="form-label fw-semibold">Hotline liên hệ</label>
                    <input type="text" id="hotline" name="hotline" class="form-control bg-light" value="<?= htmlspecialchars((string)($settings['hotline'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ví dụ: 1900 1234">
                </div>

                <div class="mb-4">
                    <label for="contact_email" class="form-label fw-semibold">Email nhận thông báo / CSKH</label>
                    <input type="email" id="contact_email" name="contact_email" class="form-control bg-light" value="<?= htmlspecialchars((string)($settings['contact_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ví dụ: contact@cycletrust.com">
                </div>

                <div class="mb-4">
                    <label for="office_address" class="form-label fw-semibold">Địa chỉ văn phòng <span class="text-muted fw-normal">(Hiển thị ở Footer)</span></label>
                    <textarea id="office_address" name="office_address" class="form-control bg-light" rows="3" placeholder="Nhập địa chỉ công ty..."><?= htmlspecialchars((string)($settings['office_address'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                </div>
                
                <div class="d-flex justify-content-end pt-3 border-top">
                    <button type="submit" class="btn text-white fw-bold px-4 py-2 shadow-sm d-flex align-items-center gap-2" style="background-color: #FF5722; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                        <i class="fa-solid fa-save"></i> Lưu cấu hình
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- SweetAlert2 Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const urlParams = new URLSearchParams(window.location.search);
    const msgParam = urlParams.get('msg');
    
    if (msgParam === 'success') {
        Swal.fire({
            title: 'Cập nhật thành công!',
            text: 'Cấu hình hệ thống đã được lưu lại và áp dụng.',
            icon: 'success',
            confirmButtonColor: '#FF5722'
        });
        window.history.replaceState({}, document.title, window.location.pathname + window.location.search.replace(/[\?&]msg=success/, ''));
    }
</script>

<?php require_once __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
