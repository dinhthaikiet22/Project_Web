<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/admin/admin_header.php';

/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

// Filter
$typeFilter = $_GET['type'] ?? '';
$statusFilter = $_GET['status'] ?? '';
$keyword = $_GET['keyword'] ?? '';

$sql = "SELECT t.*, 
               s.username AS sender_name, 
               r.username AS receiver_name 
        FROM transactions t
        LEFT JOIN users s ON t.sender_id = s.id
        LEFT JOIN users r ON t.receiver_id = r.id
        WHERE 1=1";
$params = [];

if ($typeFilter !== '') {
    $sql .= " AND t.type = ?";
    $params[] = $typeFilter;
}

if ($statusFilter !== '') {
    $sql .= " AND t.status = ?";
    $params[] = $statusFilter;
}

if ($keyword !== '') {
    $sql .= " AND (t.transaction_code LIKE ? OR s.username LIKE ? OR r.username LIKE ?)";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
    $params[] = "%$keyword%";
}

$sql .= " ORDER BY t.created_at DESC";

$transactions = [];
$error = '';
try {
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    if ($e->getCode() == '42S02') {
        $error = "Bảng dữ liệu transactions chưa được khởi tạo. Bạn phải thực hiện 1 giao dịch trên Web trước.";
    } else {
        $error = "Lỗi Database: " . $e->getMessage();
    }
}

function getTxTypeBadge($type) {
    switch($type) {
        case 'payment': return '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary"><i class="fa-solid fa-cart-shopping me-1"></i>Thanh toán</span>';
        case 'refund': return '<span class="badge bg-warning bg-opacity-10 text-dark border border-warning"><i class="fa-solid fa-rotate-left me-1"></i>Hoàn tiền</span>';
        case 'topup': return '<span class="badge bg-success bg-opacity-10 text-success border border-success"><i class="fa-solid fa-wallet me-1"></i>Nạp ví</span>';
        case 'withdraw': return '<span class="badge bg-danger bg-opacity-10 text-danger border border-danger"><i class="fa-solid fa-money-bill-transfer me-1"></i>Rút tiền</span>';
        default: return '<span class="badge bg-secondary">' . htmlspecialchars((string)$type) . '</span>';
    }
}

function getTxStatusBadge($st) {
    if ($st === 'success') return '<span class="fw-bold text-success"><i class="fa-solid fa-check"></i> Thành công</span>';
    if ($st === 'failed') return '<span class="fw-bold text-danger"><i class="fa-solid fa-xmark"></i> Thất bại</span>';
    if ($st === 'pending') return '<span class="fw-bold text-warning"><i class="fa-regular fa-clock"></i> Chờ xử lý</span>';
    return '<span class="text-muted">' . $st . '</span>';
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h3 fw-bold mb-0">Lịch sử Giao dịch Dòng tiền</h2>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<!-- Filters -->
<div class="admin-card mb-4 border-0 shadow-sm rounded-4">
    <form method="GET" action="index.php" class="row g-3 align-items-end">
        <input type="hidden" name="page" value="admin_transactions">
        
        <div class="col-md-4">
            <label class="form-label fw-semibold text-muted small">Tìm theo Nguồn/Đích/Mã GD</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" name="keyword" class="form-control border-start-0 ps-0 bg-light" placeholder="Từ khóa..." value="<?= htmlspecialchars($keyword) ?>">
            </div>
        </div>
        
        <div class="col-md-3">
            <label class="form-label fw-semibold text-muted small">Loại giao dịch</label>
            <select name="type" class="form-select bg-light">
                <option value="">Tất cả</option>
                <option value="payment" <?= $typeFilter==='payment'?'selected':'' ?>>Thanh toán mua xe</option>
                <option value="topup" <?= $typeFilter==='topup'?'selected':'' ?>>Nạp tiền vào ví</option>
                <option value="withdraw" <?= $typeFilter==='withdraw'?'selected':'' ?>>Rút tiền mặt</option>
                <option value="refund" <?= $typeFilter==='refund'?'selected':'' ?>>Hoàn tiền khách</option>
            </select>
        </div>

        <div class="col-md-2">
            <label class="form-label fw-semibold text-muted small">Trạng thái</label>
            <select name="status" class="form-select bg-light">
                <option value="">Tất cả</option>
                <option value="success" <?= $statusFilter==='success'?'selected':'' ?>>Thành công</option>
                <option value="pending" <?= $statusFilter==='pending'?'selected':'' ?>>Chờ xử lý</option>
                <option value="failed" <?= $statusFilter==='failed'?'selected':'' ?>>Thất bại</option>
            </select>
        </div>
        
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn text-white fw-bold px-4 flex-grow-1" style="background-color: #FF5722;"><i class="fa-solid fa-filter me-2"></i> Lọc</button>
            <a href="?page=admin_transactions" class="btn btn-secondary px-3 fw-bold text-white"><i class="fa-solid fa-rotate-right"></i> Làm mới</a>
        </div>
    </form>
</div>

<!-- DATA TABLE -->
<div class="admin-card border-0 shadow-sm rounded-4 overflow-hidden bg-white">
    <div class="table-responsive">
        <table class="table table-hover table-admin align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col" class="py-3 px-4">Thời gian</th>
                    <th scope="col" class="py-3">Mã GD</th>
                    <th scope="col" class="py-3">Phân loại</th>
                    <th scope="col" class="py-3 text-center">Biến động (Nguồn -> Đích)</th>
                    <th scope="col" class="py-3 text-end" style="width: 15%;">Số tiền</th>
                    <th scope="col" class="py-3 text-end px-4">Trạng thái</th>
                </tr>
            </thead>
            <tbody class="border-top-0">
                <?php if(empty($transactions) && !$error): ?>
                    <tr><td colspan="6" class="text-center py-5 text-muted">Chưa có giao dịch nào được ghi nhận trên hệ thống.</td></tr>
                <?php else: ?>
                    <?php foreach ($transactions as $tx): ?>
                        <tr>
                            <td class="px-4">
                                <span class="fw-bold text-dark"><?= date('H:i:s', strtotime($tx['created_at'])) ?></span>
                                <div class="small text-muted"><?= date('d/m/Y', strtotime($tx['created_at'])) ?></div>
                            </td>
                            <td><span class="font-monospace fw-bold text-secondary">#<?= htmlspecialchars($tx['transaction_code']) ?></span></td>
                            <td><?= getTxTypeBadge($tx['type']) ?></td>
                            <td class="text-center">
                                <div class="d-inline-flex align-items-center bg-light rounded-pill px-3 py-1 border shadow-sm">
                                    <span class="fw-semibold text-dark"><i class="fa-solid fa-user me-1 text-muted"></i> <?= htmlspecialchars($tx['sender_name'] ?? 'Hệ thống') ?></span>
                                    <i class="fa-solid fa-arrow-right-long text-success mx-3"></i>
                                    <span class="fw-semibold text-dark"><i class="fa-solid fa-user me-1 text-muted"></i> <?= htmlspecialchars($tx['receiver_name'] ?? 'Hệ thống') ?></span>
                                </div>
                                <?php if(!empty($tx['content'])): ?>
                                    <div class="small text-muted mt-2 fst-italic">"<?= htmlspecialchars($tx['content']) ?>"</div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-bold text-dark fs-5">
                                <?= number_format((float)$tx['amount'], 0, ',', '.') ?> <span style="font-size: 0.8rem; vertical-align: super;">đ</span>
                            </td>
                            <td class="text-end px-4">
                                <?= getTxStatusBadge($tx['status']) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php require_once __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
