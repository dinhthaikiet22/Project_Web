<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/admin/admin_header.php';

/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

// 1. AUTO-MIGRATE: Tạo bảng coupons nếu chưa có
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS coupons (
        id INT AUTO_INCREMENT PRIMARY KEY,
        code VARCHAR(50) NOT NULL UNIQUE,
        discount_amount DECIMAL(15,2) NOT NULL,
        min_spend DECIMAL(15,2) DEFAULT 0,
        expiry_date DATE NOT NULL,
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
        $code = trim(strtoupper((string)($_POST['code'] ?? '')));
        $discount = (float)($_POST['discount_amount'] ?? 0);
        $minSpend = (float)($_POST['min_spend'] ?? 0);
        $expiryDate = (string)($_POST['expiry_date'] ?? '');

        if ($code === '' || $discount <= 0 || empty($expiryDate)) {
            $error = "Vui lòng điền mã hợp lệ, số tiền giảm lớn hơn 0 và hạn sử dụng.";
        } else {
            try {
                $stmt = $conn->prepare("INSERT INTO coupons (code, discount_amount, min_spend, expiry_date) VALUES (?, ?, ?, ?)");
                $stmt->execute([$code, $discount, $minSpend, $expiryDate]);
                header("Location: ?page=admin_coupons&msg=add_success");
                exit;
            } catch (PDOException $e) {
                if ($e->getCode() == 23000) {
                    $error = "Mã giảm giá này (CODE) đã tồn tại. Vui lòng chọn từ khóa khác.";
                } else {
                    $error = "Lỗi thêm mới: " . $e->getMessage();
                }
            }
        }
    } elseif ($action === 'toggle' && isset($_POST['id'])) {
         $id = (int)$_POST['id'];
         try {
             $conn->prepare("UPDATE coupons SET status = IF(status='active', 'disabled', 'active') WHERE id = ?")->execute([$id]);
             header("Location: ?page=admin_coupons&msg=update_success");
             exit;
         } catch(PDOException $e) { $error = "Lỗi Database: " . $e->getMessage(); }
    } elseif ($action === 'delete' && isset($_POST['id'])) {
         $id = (int)$_POST['id'];
         try {
             $conn->prepare("DELETE FROM coupons WHERE id = ?")->execute([$id]);
             header("Location: ?page=admin_coupons&msg=delete_success");
             exit;
         } catch(PDOException $e) { $error = "Lỗi xóa dữ liệu: " . $e->getMessage(); }
    }
}

// 3. FETCH COUPONS
$coupons = [];
try {
    $stmt = $conn->query("SELECT * FROM coupons ORDER BY id DESC");
    $coupons = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Lỗi tải dữ liệu: " . $e->getMessage();
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h3 fw-bold mb-0">Thiết lập Mã Giảm Giá</h2>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row gx-4">
    <!-- Cột trái: Form Thêm -->
    <div class="col-lg-4 mb-4">
        <div class="admin-card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 20px; background-color: #2b2b2b;">
            <h5 class="fw-bold mb-4 text-white d-flex align-items-center"><i class="fa-solid fa-ticket-simple me-2" style="color: #FF5722;"></i> Trình tạo Mã mới</h5>
            <form method="POST" action="?page=admin_coupons">
                <input type="hidden" name="action" value="add">
                
                <div class="mb-3">
                    <label class="form-label fw-semibold text-white-50 small">Mã CODE (Tự động in hoa)</label>
                    <input type="text" name="code" class="form-control text-uppercase font-monospace text-dark fw-bold" placeholder="VD: CYCLE100K" required style="background:#f8f9fa;">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-white-50 small">Trị giá giảm (VNĐ)</label>
                    <input type="number" name="discount_amount" class="form-control font-monospace" placeholder="VD: 100000" min="1000" required>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-semibold text-white-50 small">Đơn tối thiểu để áp dụng</label>
                    <input type="number" name="min_spend" class="form-control font-monospace" placeholder="Mặc định: 0" min="0">
                </div>

                <div class="mb-4">
                    <label class="form-label fw-semibold text-white-50 small">Hạn sử dụng</label>
                    <input type="date" name="expiry_date" class="form-control" required min="<?= date('Y-m-d') ?>">
                </div>

                <button type="submit" class="btn fw-bold w-100 text-white" style="background-color: #FF5722; padding: 12px 0;">
                    <i class="fa-solid fa-plus-circle me-1"></i> Phát hành Mã
                </button>
            </form>
        </div>
    </div>

    <!-- Cột phải: Danh sách Coupon -->
    <div class="col-lg-8">
        <div class="admin-card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
            <div class="table-responsive">
                <table class="table table-hover table-admin align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="py-3 px-4">Coupon Code</th>
                            <th scope="col" class="py-3">Phạm vi áp dụng</th>
                            <th scope="col" class="py-3 text-center">Trạng thái</th>
                            <th scope="col" class="py-3 text-end px-4">Chỉnh sửa</th>
                        </tr>
                    </thead>
                    <tbody class="border-top-0">
                        <?php if(empty($coupons)): ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">Chưa có mã giảm giá nào được tạo.</td></tr>
                        <?php else: ?>
                            <?php foreach ($coupons as $c): 
                                $isExpired = strtotime($c['expiry_date']) < strtotime(date('Y-m-d'));
                            ?>
                                <tr>
                                    <td class="px-4">
                                        <div class="d-flex align-items-center gap-3">
                                            <div class="bg-light rounded d-flex align-items-center justify-content-center border" style="width: 50px; height: 50px; border-style: dashed !important; border-color:#FF5722 !important;">
                                                <i class="fa-solid fa-tags text-dark"></i>
                                            </div>
                                            <div>
                                                <div class="fw-bold font-monospace text-dark fs-5 mb-1" style="<?= $c['status']==='disabled' || $isExpired ? 'text-decoration: line-through; opacity: 0.5;' : '' ?>">
                                                    <?= htmlspecialchars((string)$c['code']) ?>
                                                </div>
                                                <span class="badge bg-danger">Giảm <?= number_format((float)$c['discount_amount'], 0, ',', '.') ?> đ</span>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="text-muted small fw-semibold">Đơn tối thiểu: <?= number_format((float)$c['min_spend'], 0, ',', '.') ?>đ</div>
                                        <div class="text-muted small mt-1 <?=$isExpired?'text-danger fw-bold':''?>">
                                            HSD: <?= date('d/m/Y', strtotime($c['expiry_date'])) ?> <?= $isExpired ? '(Đã Hết Hạn)' : '' ?>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($c['status'] === 'active' && !$isExpired): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="fa-solid fa-bolt me-1"></i>Kích hoạt</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary"><i class="fa-solid fa-power-off me-1"></i>Dừng</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end px-4">
                                        <!-- Nút Tắt/Mở -->
                                        <form method="POST" action="?page=admin_coupons" class="d-inline">
                                            <input type="hidden" name="action" value="toggle">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <?php if ($c['status'] === 'active'): ?>
                                                <button type="submit" class="btn btn-sm btn-outline-warning fw-bold px-2 py-1" title="Tạm dừng">
                                                    <i class="fa-solid fa-pause"></i>
                                                </button>
                                            <?php else: ?>
                                                <button type="submit" class="btn btn-sm btn-outline-success fw-bold px-2 py-1" title="Mở lại">
                                                    <i class="fa-solid fa-play"></i>
                                                </button>
                                            <?php endif; ?>
                                        </form>

                                        <!-- Nút Xóa -->
                                        <form method="POST" action="?page=admin_coupons" class="d-inline ms-1" onsubmit="return confirm('Bạn có chắc chắn muốn XÓA vĩnh viễn Code này?');">
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $c['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger fw-bold px-2 py-1">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    if (msg === 'add_success') {
        Swal.fire({ title: 'Tạo mã thành công!', text: 'Mã giảm giá đã sẵn sàng cho người dùng săn đón.', icon: 'success', confirmButtonColor: '#FF5722' });
        window.history.replaceState({}, document.title, window.location.pathname + "?page=admin_coupons");
    } else if (msg === 'update_success') {
         Swal.fire({ title: 'Đã lưu thay đổi!', icon: 'success', timer: 1500, showConfirmButton: false });
        window.history.replaceState({}, document.title, window.location.pathname + "?page=admin_coupons");
    } else if (msg === 'delete_success') {
         Swal.fire({ title: 'Đã xóa!', icon: 'success', timer: 1500, showConfirmButton: false });
        window.history.replaceState({}, document.title, window.location.pathname + "?page=admin_coupons");
    }
</script>

<?php require_once __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
