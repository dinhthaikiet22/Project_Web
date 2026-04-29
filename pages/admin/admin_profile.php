<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/admin/admin_header.php';
/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

$userId = $_SESSION['user_id'] ?? 0;
$msg = $_GET['msg'] ?? '';
$error = $_GET['error'] ?? '';

// Xử lý Cập nhật
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $password_confirm = $_POST['password_confirm'] ?? '';

    if ($email === '') {
        $error = "Email không được để trống";
    } else {
        try {
            if ($password !== '') {
                if ($password !== $password_confirm) {
                    $error = "Mật khẩu nhập lại không khớp.";
                } else {
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET email = ?, password = ? WHERE id = ?");
                    $stmt->execute([$email, $hashedPassword, $userId]);
                    $msg = "update_success";
                }
            } else {
                $stmt = $conn->prepare("UPDATE users SET email = ? WHERE id = ?");
                $stmt->execute([$email, $userId]);
                $msg = "update_success";
            }
        } catch (PDOException $e) {
            $error = "Lỗi cơ sở dữ liệu: " . $e->getMessage();
        }
    }
    
    // Redirect để xóa cache POST và mượt màn hình
    if (!$error && $msg === 'update_success') {
        header("Location: ?page=admin_profile&msg=update_success");
        exit;
    }
}

// Lấy thông tin user hiện tại
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$userId]);
$adminUser = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$adminUser) {
    echo "<div class='alert alert-danger mx-3 mt-3'>Không tìm thấy thông tin tài khoản</div>";
    require_once __DIR__ . '/../../includes/admin/admin_footer.php';
    exit;
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h3 fw-bold mb-0">Trang cá nhân</h2>
</div>

<div class="row">
    <div class="col-lg-6 col-md-8">
        <div class="admin-card border-0 shadow-sm rounded-4">
            <div class="admin-card-title mb-4">
                <h4 class="h5 fw-bold mb-0"><i class="fa-solid fa-user-shield text-primary me-2"></i>Cập nhật thông tin</h4>
            </div>
            
            <?php if ($error): ?>
                <div class="alert alert-danger shadow-sm rounded mb-4"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
            <?php endif; ?>

            <form action="?page=admin_profile" method="POST">
                <input type="hidden" name="action" value="update_profile">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold">Tên đăng nhập (Username)</label>
                    <input type="text" class="form-control bg-light" value="<?= htmlspecialchars((string)$adminUser['username'], ENT_QUOTES, 'UTF-8') ?>" readonly disabled>
                    <small class="text-muted"><i class="fa-solid fa-info-circle me-1"></i> Username dùng để đăng nhập và không thể thay đổi.</small>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-semibold">Email <span class="text-danger">*</span></label>
                    <input type="email" id="email" name="email" class="form-control" value="<?= htmlspecialchars((string)$adminUser['email'], ENT_QUOTES, 'UTF-8') ?>" required>
                </div>
                
                <hr class="my-4">
                <h5 class="fw-bold mb-3 fs-6">Đổi mật khẩu <span class="text-muted fw-normal">(Bỏ trống nếu không đổi)</span></h5>

                <div class="mb-3">
                    <label for="password" class="form-label fw-semibold">Mật khẩu mới</label>
                    <input type="password" id="password" name="password" class="form-control" placeholder="Nhập mật khẩu mới">
                </div>

                <div class="mb-4">
                    <label for="password_confirm" class="form-label fw-semibold">Nhập lại mật khẩu mới</label>
                    <input type="password" id="password_confirm" name="password_confirm" class="form-control" placeholder="Xác nhận mật khẩu mới">
                </div>
                
                <button type="submit" class="btn text-white fw-bold w-100 py-2 d-flex align-items-center justify-content-center gap-2 shadow-sm" style="background-color: #FF5722; transition: opacity 0.2s;" onmouseover="this.style.opacity='0.9'" onmouseout="this.style.opacity='1'">
                    <i class="fa-solid fa-save"></i> Cập nhật thông tin
                </button>
            </form>
        </div>
    </div>
</div>

<!-- SweetAlert2 Scripts -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const urlParams = new URLSearchParams(window.location.search);
    const msgParam = urlParams.get('msg');
    if (msgParam === 'update_success') {
        Swal.fire({
            title: 'Thành công!',
            text: 'Thông tin tài khoản đã được cập nhật.',
            icon: 'success',
            confirmButtonColor: '#FF5722'
        });
        // Dọn URL
        window.history.replaceState({}, document.title, window.location.pathname + window.location.search.replace(/[\?&]msg=update_success/, ''));
    }
</script>

<?php require_once __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
