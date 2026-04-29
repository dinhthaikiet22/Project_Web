<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/admin/admin_header.php';

/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

// 1. AUTO-MIGRATE: Tạo bảng banners nếu chưa có
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS banners (
        id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        image_url TEXT NOT NULL,
        link_url TEXT,
        status ENUM('active', 'disabled') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");
} catch (PDOException $e) {
    // bo qua loi
}

$msg = $_GET['msg'] ?? '';
$error = '';

// 2. XỬ LÝ POST ACTION (Thêm / Xóa / Kích hoạt)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'add') {
        $title = trim((string)($_POST['title'] ?? ''));
        $image = trim((string)($_POST['image_url'] ?? ''));
        $link = trim((string)($_POST['link_url'] ?? ''));

        if ($title === '' || $image === '') {
            $error = "Vui lòng nhập Tên Banner và Đường dẫn Ảnh hợp lệ.";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO banners (title, image_url, link_url) VALUES (?, ?, ?)");
                $stmt->execute([$title, $image, $link]);
                header("Location: ?page=admin_banners&msg=add_success");
                exit;
            } catch (PDOException $e) {
                $error = "Lỗi thêm mới: " . $e->getMessage();
            }
        }
    } elseif ($action === 'toggle' && isset($_POST['id'])) {
         $id = (int)$_POST['id'];
         try {
             $conn->prepare("UPDATE banners SET status = IF(status='active', 'disabled', 'active') WHERE id = ?")->execute([$id]);
             header("Location: ?page=admin_banners&msg=update_success");
             exit;
         } catch(PDOException $e) { $error = "Lỗi Database: " . $e->getMessage(); }
    } elseif ($action === 'delete' && isset($_POST['id'])) {
         $id = (int)$_POST['id'];
         try {
             $conn->prepare("DELETE FROM banners WHERE id = ?")->execute([$id]);
             header("Location: ?page=admin_banners&msg=delete_success");
             exit;
         } catch(PDOException $e) { $error = "Lỗi xóa dữ liệu: " . $e->getMessage(); }
    }
}

// 3. FETCH BANNERS
$banners = [];
try {
    $stmt = $conn->query("SELECT * FROM banners ORDER BY id DESC");
    $banners = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Lỗi tải dữ liệu: " . $e->getMessage();
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h3 fw-bold mb-0">Quản lý Banner (Slider)</h2>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row gx-4">
    <!-- Cột trái: Form Thêm -->
    <div class="col-lg-4 mb-4">
        <div class="admin-card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 20px; background-color: #2b2b2b;">
            <h5 class="fw-bold mb-4 text-white d-flex align-items-center"><i class="fa-regular fa-images me-2" style="color: #FF5722;"></i> Thêm Banner mới</h5>
            <form method="POST" action="?page=admin_banners">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold text-white-50 small">Tiêu đề (Tên chiến dịch)</label>
                    <input type="text" name="title" class="form-control" placeholder="VD: Khai trương Đại hạ giá" required style="background:#f8f9fa;">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-white-50 small">URL ảnh Banner (1920x600px)</label>
                    <input type="url" name="image_url" class="form-control font-monospace" placeholder="https://..." required>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold text-white-50 small">Link trỏ tới khi người dùng click (Tùy chọn)</label>
                    <input type="url" name="link_url" class="form-control font-monospace" placeholder="https://cycletrust.vn/...">
                </div>

                <button type="submit" class="btn fw-bold w-100 text-white" style="background-color: #FF5722; padding: 12px 0;">
                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> Xuất bản Banner
                </button>
            </form>
        </div>
    </div>

    <!-- Cột phải: Danh sách Banner -->
    <div class="col-lg-8">
        <div class="d-flex flex-column gap-3">
            <?php if(empty($banners)): ?>
                <div class="admin-card border-0 shadow-sm rounded-4 p-5 text-center bg-white text-muted">
                    <i class="fa-regular fa-image fs-1 mb-3"></i>
                    <p class="mb-0">Trang chủ đang không có Banner nào chạy. Hãy thêm mới ngay!</p>
                </div>
            <?php else: ?>
                <?php foreach ($banners as $b): ?>
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden position-relative">
                        <!-- Preview Image -->
                        <div style="height: 180px; background-image: url('<?= htmlspecialchars((string)$b['image_url']) ?>'); background-size: cover; background-position: center; <?= $b['status']=='disabled'?'filter: grayscale(100%) opacity(0.7);':'' ?>">
                            <?php if ($b['status'] === 'active'): ?>
                                <span class="position-absolute top-0 start-0 m-3 badge bg-success shadow-sm px-3 py-2 fs-6"><i class="fa-solid fa-play me-1"></i> Đang hiển thị</span>
                            <?php else: ?>
                                <span class="position-absolute top-0 start-0 m-3 badge bg-secondary shadow-sm px-3 py-2 fs-6"><i class="fa-solid fa-pause me-1"></i> Đã ẩn</span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Content -->
                        <div class="card-body bg-white p-4">
                            <div class="row align-items-center">
                                <div class="col-md-8">
                                    <h5 class="fw-bold text-dark mb-1" style="<?= $b['status']=='disabled'?'text-decoration: line-through;':'' ?>"><?= htmlspecialchars((string)$b['title']) ?></h5>
                                    <?php if (!empty($b['link_url'])): ?>
                                        <div class="small mt-2"><i class="fa-solid fa-link text-muted me-1"></i> <a href="<?= htmlspecialchars((string)$b['link_url']) ?>" target="_blank" class="text-primary text-decoration-none"><?= htmlspecialchars((string)$b['link_url']) ?></a></div>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="col-md-4 text-end mt-3 mt-md-0">
                                    <div class="d-flex justify-content-end gap-2">
                                        <!-- Tắt/Mở -->
                                        <form method="POST" action="?page=admin_banners">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                            <?php if ($b['status'] === 'active'): ?>
                                                <button type="submit" class="btn btn-outline-warning fw-bold d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; border-radius: 50%;">
                                                    <i class="fa-solid fa-eye-slash"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-outline-success fw-bold d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; border-radius: 50%;">
                                                    <i class="fa-solid fa-eye"></i>
                                                </button>
                                            <?php endif; ?>
                                        </form>

                                        <!-- Xóa -->
                                        <form method="POST" action="?page=admin_banners" onsubmit="return confirm('XÓA VĨNH VIỄN Banner này khỏi hệ thống?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $b['id'] ?>">
                                            <button type="submit" class="btn btn-outline-danger fw-bold d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; border-radius: 50%;">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    if (msg === 'add_success') {
        Swal.fire({ title: 'Upload thành công!', text: 'Banner mới đã được xuất bản.', icon: 'success', confirmButtonColor: '#FF5722' });
        window.history.replaceState({}, document.title, window.location.pathname + "?page=admin_banners");
    } else if (msg === 'update_success') {
         Swal.fire({ title: 'Đã lưu thay đổi!', icon: 'success', timer: 1500, showConfirmButton: false });
        window.history.replaceState({}, document.title, window.location.pathname + "?page=admin_banners");
    } else if (msg === 'delete_success') {
         Swal.fire({ title: 'Đã xóa!', icon: 'success', timer: 1500, showConfirmButton: false });
        window.history.replaceState({}, document.title, window.location.pathname + "?page=admin_banners");
    }
</script>

<?php require_once __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
