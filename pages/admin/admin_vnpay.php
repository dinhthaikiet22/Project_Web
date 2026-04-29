<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/admin/admin_header.php';

/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

// Filter
$keyword = $_GET['keyword'] ?? '';

$sql = "SELECT t.*, u.username AS sender_name 
        FROM transactions t
        LEFT JOIN users u ON t.sender_id = u.id
        WHERE t.vnp_transaction_no IS NOT NULL AND t.vnp_transaction_no != '' ";
$params = [];

if ($keyword !== '') {
    $sql .= " AND (t.transaction_code LIKE ? OR t.vnp_transaction_no LIKE ? OR u.username LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

$sql .= " ORDER BY t.created_at DESC";

$vnp_transactions = [];
$error = '';
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $vnp_transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    if ($e->getCode() == '42S02') {
        $error = "Bảng dữ liệu transactions chưa được khởi tạo. Bạn phải thực hiện 1 giao dịch trên Web trước.";
    } else {
        $error = "Lỗi Database: " . $e->getMessage();
    }
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h3 fw-bold mb-0">Hệ thống Đối soát Thanh toán VNPAY</h2>
    <a href="https://sandbox.vnpayment.vn/merchantv2/" target="_blank" class="btn btn-primary fw-bold px-4" style="background-color: #004a9c; border:none;">
        <i class="fa-solid fa-arrow-up-right-from-square me-2"></i> Truy cập VNPAY Merchant
    </a>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Filters -->
<div class="admin-card mb-4 border-0 shadow-sm rounded-4" style="border-left: 5px solid #004a9c !important;">
    <form method="GET" action="index.php" class="row g-3 align-items-end">
        <input type="hidden" name="page" value="admin_vnpay">
        
        <div class="col-md-6">
            <label class="form-label fw-semibold text-muted small">Tra cứu theo Mã tham chiếu Nội bộ / Mã GD VNPAY / Tên Khách</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" name="keyword" class="form-control border-start-0 ps-0 bg-light" placeholder="Nhập từ khóa tìm kiếm nhanh..." value="<?= htmlspecialchars($keyword) ?>">
            </div>
        </div>
        
        <div class="col-md-4 d-flex gap-2">
            <button type="submit" class="btn text-white fw-bold px-4 flex-grow-1" style="background-color: #004a9c;"><i class="fa-solid fa-filter me-2"></i> Đối soát</button>
            <a href="?page=admin_vnpay" class="btn btn-secondary px-3 fw-bold text-white"><i class="fa-solid fa-rotate-right"></i> Làm mới</a>
        </div>
    </form>
</div>

<!-- DATA TABLE -->
<div class="admin-card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table table-hover table-admin align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col" class="py-3 px-4" style="min-width: 150px;">Ngày TT (VNPAY)</th>
                    <th scope="col" class="py-3">Mã GD (Nội bộ)</th>
                    <th scope="col" class="py-3">Mã GD (VNPAY)</th>
                    <th scope="col" class="py-3">Ngân hàng</th>
                    <th scope="col" class="py-3">Khách hàng</th>
                    <th scope="col" class="py-3 text-end" style="width: 15%;">Số tiền</th>
                    <th scope="col" class="py-3 text-center px-4">Trạng thái Cổng TT</th>
                </tr>
            </thead>
            <tbody class="border-top-0">
                <?php if(empty($vnp_transactions) && !$error): ?>
                    <tr><td colspan="7" class="text-center py-5 text-muted">Không tìm thấy giao dịch chuyển khoản VNPAY nào.</td></tr>
                <?php else: ?>
                    <?php foreach ($vnp_transactions as $tx): ?>
                        <tr>
                            <td class="px-4 text-dark font-monospace fw-semibold">
                                <?php 
                                    if ($tx['vnp_pay_date']) {
                                        echo date('d/m/Y H:i', strtotime($tx['vnp_pay_date']));
                                    } else {
                                        echo '<span class="text-muted">Chưa ghi nhận</span>';
                                    }
                                ?>
                            </td>
                            <td><span class="font-monospace text-secondary fw-semibold">#<?= htmlspecialchars($tx['transaction_code']) ?></span></td>
                            <td><span class="font-monospace text-primary fw-bold"><?= htmlspecialchars($tx['vnp_transaction_no']) ?></span></td>
                            <td>
                                <?php if (!empty($tx['vnp_bank_code'])): ?>
                                    <span class="badge bg-light text-dark border"><i class="fa-solid fa-building-columns me-1"></i> <?= htmlspecialchars($tx['vnp_bank_code']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted small">Ví VNPAY</span>
                                <?php endif; ?>
                            </td>
                            <td class="fw-semibold text-dark"><i class="fa-solid fa-user me-1 text-muted"></i> <?= htmlspecialchars($tx['sender_name'] ?? 'Ẩn danh') ?></td>
                            <td class="text-end fw-bold text-success fs-6">
                                + <?= number_format((float)$tx['amount'], 0, ',', '.') ?> <span style="font-size: 0.7rem;">đ</span>
                            </td>
                            <td class="text-center px-4">
                                <?php if ($tx['status'] === 'success'): ?>
                                    <span class="badge bg-success shadow-sm px-3 py-2"><i class="fa-solid fa-square-check me-1"></i> Đã thanh toán</span>
                                <?php else: ?>
                                    <span class="badge bg-danger shadow-sm px-3 py-2"><i class="fa-solid fa-circle-xmark me-1"></i> Giao dịch lỗi</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
