<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/admin/admin_header.php';

/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

$msg = $_GET['msg'] ?? '';
$error = '';

// XỬ LÝ POST ACTION (Thêm Admin / Tước Quyền)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'promote' && !empty($_POST['identifier'])) {
        $identifier = trim((string)$_POST['identifier']);
        try {
            $stmt = $conn->prepare("SELECT id, role FROM users WHERE email = ? OR username = ? LIMIT 1");
            $stmt->execute([$identifier, $identifier]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($user) {
                if ($user['role'] === 'admin') {
                    $error = "Tài khoản này đã là Quản trị viên!";
                } else {
                    $conn->prepare("UPDATE users SET role = 'admin' WHERE id = ?")->execute([$user['id']]);
                    header("Location: ?page=admin_admins&msg=promote_success");
                    exit;
                }
            } else {
                $error = "Không tìm thấy tài khoản Khách hàng nào khớp với Email/Username trên.";
            }
        } catch (PDOException $e) {
            $error = "Lỗi Database: " . $e->getMessage();
        }
    } elseif ($action === 'revoke' && isset($_POST['user_id'])) {
        $userId = (int)$_POST['user_id'];
        if ($userId > 0 && $_SESSION['user_id'] != $userId) { // Khong cho phep tu tuoc quyen cua minh
            try {
                $conn->prepare("UPDATE users SET role = 'user' WHERE id = ? AND role = 'admin'")->execute([$userId]);
                header("Location: ?page=admin_admins&msg=revoke_success");
                exit;
            } catch (PDOException $e) {
                $error = "Lỗi Database: " . $e->getMessage();
            }
        } else {
            $error = "Hành động không hợp lệ. Bạn không thể tự tước quyền của chính mình.";
        }
    }
}

// FETCH ADMINS
$sql = "SELECT id, username, email, phone, role FROM users WHERE role = 'admin' ORDER BY id ASC";
$admins = [];
try {
    $stmt = $conn->query($sql);
    $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Lỗi tải dữ liệu: " . $e->getMessage();
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h3 fw-bold mb-0">Quản trị viên (Admins)</h2>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Form Cấp Quyền Mới -->
<div class="admin-card mb-4 border-0 shadow-sm rounded-4 p-4" style="background-color: #fffaf8; border: 1px solid #ffebe1 !important;">
    <h5 class="fw-bold mb-3 d-flex align-items-center text-dark"><i class="fa-solid fa-user-shield me-2" style="color:#FF5722;"></i> Cấp quyền Quản trị mới</h5>
    <form method="POST" action="?page=admin_admins" class="row g-3 align-items-end">
        <input type="hidden" name="action" value="promote">
        <div class="col-md-8">
            <label class="form-label text-muted small fw-semibold">Email hoặc Username của Khách hàng hiện tại</label>
            <input type="text" name="identifier" class="form-control" placeholder="VD: kietnguyen@gmail.com hoặc kiet123" required>
        </div>
        <div class="col-md-4">
            <button type="submit" class="btn text-white fw-bold w-100" style="background-color: #FF5722;"><i class="fa-solid fa-arrow-up-right-dots me-1"></i> Thăng cấp Admin</button>
        </div>
    </form>
</div>

<!-- Admin Table -->
<div class="admin-card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover table-admin align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col" class="py-3 px-4">UID</th>
                    <th scope="col" class="py-3">Thông tin Admin</th>
                    <th scope="col" class="py-3">Liên lạc</th>
                    <th scope="col" class="py-3 text-center">Đặc quyền</th>
                    <th scope="col" class="py-3 text-end px-4">Thao tác</th>
                </tr>
            </thead>
            <tbody class="border-top-0">
                <?php foreach ($admins as $index => $a): ?>
                    <tr>
                        <td class="px-4 fw-bold text-muted">#<?= $a['id'] ?></td>
                        <td>
                            <div class="d-flex align-items-center gap-3">
                                <div class="rounded-circle d-flex align-items-center justify-content-center text-white shadow-sm" style="width: 45px; height: 45px; font-size: 1.2rem; background-color: <?= $index===0?'#dc3545':'#FF5722' ?>;">
                                    <?= strtoupper(substr($a['username'], 0, 1)) ?>
                                </div>
                                <div>
                                    <div class="fw-bold text-dark fs-6 d-flex align-items-center gap-2">
                                        <?= htmlspecialchars((string)$a['username']) ?>
                                        <?php if ($a['id'] == $_SESSION['user_id']): ?>
                                            <span class="badge bg-danger rounded-pill" style="font-size: 0.65rem;">YOU</span>
                                        <?php endif; ?>
                                    </div>
                                    <span class="badge bg-light text-dark border mt-1"><i class="fa-solid fa-crown me-1 text-warning"></i> Quản trị viên</span>
                                </div>
                            </div>
                        </td>
                        <td>
                            <div class="small fw-semibold text-dark"><i class="fa-regular fa-envelope me-1 text-muted"></i> <?= htmlspecialchars((string)$a['email']) ?></div>
                            <div class="small text-muted mt-1"><i class="fa-solid fa-phone me-1"></i> <?= htmlspecialchars((string)$a['phone'] ?: 'Chưa cập nhật') ?></div>
                        </td>
                        <td class="text-center">
                            <span class="badge bg-dark fw-normal"><i class="fa-solid fa-shield-halved me-1"></i> Toàn quyền Sàn</span>
                        </td>
                        <td class="text-end px-4">
                            <?php if ($a['id'] != $_SESSION['user_id'] && $index !== 0): // K cho xoa chinh minh hoac admin dau tien (Founder) ?>
                                <form method="POST" action="?page=admin_admins" class="d-inline" onsubmit="return confirm('Hạ cấp người này xuống thành Khách hàng (User) bình thường? Họ sẽ mất quyền chui vào trang Admin này.');">
                                    <input type="hidden" name="action" value="revoke">
                                    <input type="hidden" name="user_id" value="<?= $a['id'] ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-secondary fw-bold px-3 py-2 rounded-3 shadow-sm" title="Hạ cấp">
                                        <i class="fa-solid fa-arrow-down-long"></i> Giáng chức
                                    </button>
                                </form>
                            <?php else: ?>
                                <span class="text-muted small fst-italic">Không thể thao tác</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    if (msg === 'promote_success') {
        Swal.fire({ title: 'Thăng cấp thành công!', text: 'Người dùng đã trở thành Quản trị viên mới.', icon: 'success', confirmButtonColor: '#FF5722' });
        window.history.replaceState({}, document.title, window.location.pathname + "?page=admin_admins");
    } else if (msg === 'revoke_success') {
         Swal.fire({ title: 'Đã giáng chức!', text: 'Tài khoản đã bị hạ quyền xuống thành Khách hàng thường.', icon: 'success', confirmButtonColor: '#858796' });
        window.history.replaceState({}, document.title, window.location.pathname + "?page=admin_admins");
    }
</script>

<?php require_once __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
