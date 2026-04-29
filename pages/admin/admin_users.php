<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/admin/admin_header.php';

/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

// 1. AUTO-MIGRATE: Thêm cột `status` vào bảng `users` nếu chưa có
try {
    $conn->exec("ALTER TABLE users ADD COLUMN status ENUM('active', 'banned') DEFAULT 'active'");
} catch (PDOException $e) {
    // Column already exists or other error, ignore
}

$msg = $_GET['msg'] ?? '';
$error = '';

// 2. XỬ LÝ POST ACTION (Khóa / Mở khóa User)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $userId = (int)($_POST['user_id'] ?? 0);

    if ($userId > 0 && $_SESSION['user_id'] != $userId) { // Khong cho phep xoa/khoa chinh minh
        try {
            if ($action === 'ban') {
                $conn->prepare("UPDATE users SET status = 'banned' WHERE id = ? AND role = 'user'")->execute([$userId]);
                header("Location: ?page=admin_users&msg=ban_success");
                exit;
            } elseif ($action === 'unban') {
                $conn->prepare("UPDATE users SET status = 'active' WHERE id = ? AND role = 'user'")->execute([$userId]);
                header("Location: ?page=admin_users&msg=unban_success");
                exit;
            }
        } catch (PDOException $e) {
            $error = "Lỗi Database: " . $e->getMessage();
        }
    }
}

// 3. FETCH USERS
$keyword = $_GET['keyword'] ?? '';
$sql = "SELECT * FROM users WHERE role = 'user' ";
$params = [];
if ($keyword !== '') {
    $sql .= " AND (username LIKE ? OR email LIKE ? OR phone LIKE ?) ";
    $params = ["%$keyword%", "%$keyword%", "%$keyword%"];
}
$sql .= " ORDER BY id DESC";

$users = [];
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Lỗi tải dữ liệu: " . $e->getMessage();
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h3 fw-bold mb-0">Quản lý Khách hàng (Users)</h2>
</div>

<!-- Filters -->
<div class="admin-card mb-4 border-0 shadow-sm rounded-4">
    <form method="GET" action="index.php" class="row g-3 align-items-end">
        <input type="hidden" name="page" value="admin_users">
        
        <div class="col-md-6">
            <label class="form-label fw-semibold text-muted small">Tìm kiếm tài khoản (Tên, Email, SĐT)</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" name="keyword" class="form-control border-start-0 ps-0 bg-light" placeholder="Nhập từ khóa tìm kiếm..." value="<?= htmlspecialchars($keyword) ?>">
            </div>
        </div>
        
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn text-white fw-bold px-4 flex-grow-1" style="background-color: #FF5722;"><i class="fa-solid fa-filter me-2"></i> Lọc</button>
            <a href="?page=admin_users" class="btn btn-secondary px-3 fw-bold text-white"><i class="fa-solid fa-rotate-right"></i> Làm mới</a>
        </div>
    </form>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Users Table -->
<div class="admin-card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover table-admin align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col" class="py-3 px-4">UID</th>
                    <th scope="col" class="py-3">Thông tin Khách hàng</th>
                    <th scope="col" class="py-3">Liên lạc</th>
                    <th scope="col" class="py-3 text-center">Trạng thái</th>
                    <th scope="col" class="py-3 text-end px-4">Thao tác</th>
                </tr>
            </thead>
            <tbody class="border-top-0">
                <?php if (empty($users)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5 text-muted">Không tìm thấy khách hàng nào.</td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($users as $u): 
                        $statusRaw = $u['status'] ?? 'active';
                    ?>
                        <tr>
                            <td class="px-4 fw-bold text-muted">#<?= $u['id'] ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-3">
                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm" style="width: 45px; height: 45px; font-size: 1.2rem;">
                                        <?= strtoupper(substr($u['username'], 0, 1)) ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark fs-6"><?= htmlspecialchars((string)$u['username']) ?></div>
                                        <span class="badge bg-light text-dark border mt-1"><i class="fa-solid fa-user me-1"></i> User</span>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div class="small fw-semibold text-dark"><i class="fa-regular fa-envelope me-1 text-muted"></i> <?= htmlspecialchars((string)$u['email']) ?></div>
                                <div class="small text-muted mt-1"><i class="fa-solid fa-phone me-1"></i> <?= htmlspecialchars((string)$u['phone'] ?: 'Chưa cập nhật') ?></div>
                            </td>
                            <td class="text-center">
                                <?php if ($statusRaw === 'active'): ?>
                                    <span class="badge bg-success px-3 py-2"><i class="fa-solid fa-check-circle me-1"></i> Hoạt động</span>
                                <?php else: ?>
                                    <span class="badge bg-danger px-3 py-2"><i class="fa-solid fa-lock me-1"></i> Đã bị Khóa</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end px-4">
                                <form method="POST" action="?page=admin_users" class="d-inline">
                                    <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                                    <?php if ($statusRaw === 'active'): ?>
                                        <input type="hidden" name="action" value="ban">
                                        <button type="submit" onclick="return confirm('Bạn muốn KHÓA tài khoản này? Khách hàng sẽ không thể đăng nhập.');" class="btn btn-sm btn-outline-danger fw-bold px-3 py-2 rounded-3 shadow-sm" title="Khóa User">
                                            <i class="fa-solid fa-ban"></i> Khóa
                                        </button>
                                    <?php else: ?>
                                        <input type="hidden" name="action" value="unban">
                                        <button type="submit" onclick="return confirm('Bạn muốn MỞ KHÓA tài khoản này?');" class="btn btn-sm btn-outline-success fw-bold px-3 py-2 rounded-3 shadow-sm" title="Mở khóa User">
                                            <i class="fa-solid fa-unlock"></i> Mở khóa
                                        </button>
                                    <?php endif; ?>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    if (msg === 'ban_success') {
        Swal.fire({ title: 'Đã khóa!', text: 'Tài khoản khách hàng đã bị đình chỉ.', icon: 'success', confirmButtonColor: '#dc3545' });
        window.history.replaceState({}, document.title, window.location.pathname + "?page=admin_users");
    } else if (msg === 'unban_success') {
         Swal.fire({ title: 'Mở khóa thành công!', text: 'Khách hàng đã có thể truy cập lại.', icon: 'success', confirmButtonColor: '#10b981' });
        window.history.replaceState({}, document.title, window.location.pathname + "?page=admin_users");
    }
</script>

<?php require_once __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
