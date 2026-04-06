<?php
declare(strict_types=1);

/**
 * pages/admin/admin_orders.php
 * -------------------------------------------------------
 * Quản lý Đơn hàng - Marketplace Moderation System
 * -------------------------------------------------------
 */

require_once __DIR__ . '/../../includes/admin/admin_header.php';

/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

// 1. NGƯỒN DỮ LIỆU DUY NHẤT (Single Source of Truth)
// Các trạng thái đơn hàng theo yêu cầu
$statuses = [
    '' => 'Tất cả',
    'pending_payment' => 'Chờ thanh toán',
    'paid' => 'Đã xác nhận',
    'shipping' => 'Đang giao',
    'completed' => 'Hoàn tất',
    'cancelled' => 'Đã hủy'
];

$statusFilter = $_GET['status'] ?? '';
if (!array_key_exists($statusFilter, $statuses)) {
    $statusFilter = '';
}
$keyword = trim($_GET['q'] ?? '');

// 2. ĐẾM SỐ LƯỢNG CHO TỪNG TAB (JOIN 2 lần users để lọc theo keyword)
$tabCounts = [];
try {
    foreach ($statuses as $k => $label) {
        $sqlCount = "SELECT COUNT(*) FROM orders o 
                     LEFT JOIN users AS buyer ON o.buyer_id = buyer.id 
                     LEFT JOIN users AS seller ON o.seller_id = seller.id 
                     WHERE 1=1 ";
        $countParams = [];
        
        if ($k !== '') {
            $sqlCount .= " AND o.order_status = ? ";
            $countParams[] = $k;
        }
        
        if ($keyword !== '') {
            $sqlCount .= " AND (o.order_code LIKE ? OR buyer.username LIKE ? OR seller.username LIKE ?) ";
            $kw = "%$keyword%";
            $countParams[] = $kw;
            $countParams[] = $kw;
            $countParams[] = $kw;
        }
        
        $stmtCount = $conn->prepare($sqlCount);
        $stmtCount->execute($countParams);
        $tabCounts[$k] = (int)$stmtCount->fetchColumn();
    }
} catch (PDOException $e) {
    echo '<div class="alert alert-danger mx-3 mt-3">Lỗi đếm dữ liệu: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// 3. TRUY VẤN DANH SÁCH ĐƠN HÀNG (SQL JOIN TWICE)
$orders = [];
try {
    $sql = "SELECT o.*, 
            buyer.username AS buyer_name, 
            seller.username AS seller_name 
            FROM orders o 
            LEFT JOIN users AS buyer ON o.buyer_id = buyer.id 
            LEFT JOIN users AS seller ON o.seller_id = seller.id 
            WHERE 1=1 ";
    $params = [];

    if ($statusFilter !== '') {
        $sql .= " AND o.order_status = ? ";
        $params[] = $statusFilter;
    }

    if ($keyword !== '') {
        $sql .= " AND (o.order_code LIKE ? OR buyer.username LIKE ? OR seller.username LIKE ?) ";
        $kw = "%$keyword%";
        $params[] = $kw;
        $params[] = $kw;
        $params[] = $kw;
    }

    $sql .= " ORDER BY o.created_at DESC";
    
    $stmt = $conn->prepare($sql);
    $stmt->execute($params);
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo '<div class="alert alert-danger mx-3 mt-3">Lỗi truy vấn đơn hàng: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

/**
 * Hiển thị Badge Trạng thái theo yêu cầu
 */
function getStatusBadge(string $status): string {
    switch ($status) {
        case 'pending_payment':
            return '<span class="badge bg-warning text-dark"><i class="fa-regular fa-clock me-1"></i> Chờ thanh toán</span>';
        case 'paid':
            return '<span class="badge bg-info text-dark"><i class="fa-solid fa-check-circle me-1"></i> Đã xác nhận</span>';
        case 'shipping':
            return '<span class="badge bg-primary"><i class="fa-solid fa-truck-fast me-1"></i> Đang giao</span>';
        case 'completed':
            return '<span class="badge bg-success"><i class="fa-solid fa-circle-check me-1"></i> Hoàn tất</span>';
        case 'cancelled':
            return '<span class="badge bg-danger"><i class="fa-solid fa-circle-xmark me-1"></i> Đã hủy</span>';
        default:
            return '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
    }
}

/**
 * Hiển thị Badge Phương thức thanh toán
 */
function getPaymentMethodBadge($method): string {
    $method = strtolower((string)$method);
    if ($method === 'vnpay') return '<span class="badge border border-info text-info bg-white small fw-bold">VNPAY</span>';
    if ($method === 'cod') return '<span class="badge border border-success text-success bg-white small fw-bold">Tiền mặt (COD)</span>';
    return '<span class="badge border border-secondary text-secondary bg-white small fw-bold">' . htmlspecialchars($method) . '</span>';
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h3 fw-bold mb-0">Quản lý Đơn hàng</h2>
</div>

<!-- Tabs Status -->
<ul class="nav nav-tabs border-bottom-0 mb-4 gap-2" role="tablist">
    <?php foreach ($statuses as $k => $label): 
        $isActive = ($statusFilter === $k);
        $link = "index.php?page=admin_orders&status=$k" . ($keyword !== '' ? "&q=" . urlencode($keyword) : "");
    ?>
    <li class="nav-item">
        <a href="<?= $link ?>" class="nav-link px-4 py-3 border-0 fw-bold rounded-top-4 shadow-sm text-decoration-none transition-all" 
           style="<?= $isActive ? 'background: #FF5722; color: #fff;' : 'background: #fff; color: #6c757d; opacity: 0.8;' ?>">
            <?= $label ?>
            <span class="badge <?= $isActive ? 'bg-white text-dark' : 'bg-light text-secondary' ?> ms-1"><?= $tabCounts[$k] ?></span>
        </a>
    </li>
    <?php endforeach; ?>
</ul>

<!-- Search Bar -->
<div class="admin-card mb-4 border-0 shadow-sm rounded-4">
    <form method="GET" action="index.php" class="row g-3 align-items-center">
        <input type="hidden" name="page" value="admin_orders">
        <input type="hidden" name="status" value="<?= htmlspecialchars($statusFilter) ?>">
        
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="fa-solid fa-magnifying-glass"></i></span>
                <input type="text" name="q" class="form-control border-start-0 bg-light" placeholder="Mã đơn, Tên người mua hoặc Tên chủ xe..." value="<?= htmlspecialchars($keyword) ?>">
            </div>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn text-white fw-bold px-4" style="background: #FF5722;">Tìm kiếm</button>
            <?php if ($keyword !== ''): ?>
                <a href="index.php?page=admin_orders&status=<?= htmlspecialchars($statusFilter) ?>" class="btn btn-outline-secondary px-3"><i class="fa-solid fa-xmark"></i></a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Data Table -->
<div class="admin-card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover table-admin align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col" class="py-3 px-4">Mã đơn</th>
                    <th scope="col" class="py-3">Thời gian</th>
                    <th scope="col" class="py-3 text-center">Người mua / Người bán</th>
                    <th scope="col" class="py-3">Thanh toán</th>
                    <th scope="col" class="py-3">Tổng tiền</th>
                    <th scope="col" class="py-3">Trạng thái</th>
                    <th scope="col" class="py-3 text-end px-4">Thao tác</th>
                </tr>
            </thead>
            <tbody class="border-top-0">
                <?php if (empty($orders)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-5">
                            <div class="mx-auto mb-3 text-muted opacity-25" style="font-size: 4rem;"><i class="fa-solid fa-box-open"></i></div>
                            <h5 class="fw-bold text-dark mb-1">Chưa có đơn hàng nào</h5>
                            <p class="text-muted small mb-0">Hệ thống không tìm thấy biên lai giao dịch nào khớp với bộ lọc.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($orders as $ord): ?>
                        <tr>
                            <td class="px-4 fw-bold font-monospace">
                                <a href="index.php?page=admin_order_detail&id=<?= $ord['id'] ?>" class="text-dark text-decoration-none hover-orange">
                                    #<?= htmlspecialchars((string)$ord['order_code']) ?>
                                </a>
                            </td>
                            <td>
                                <div class="small text-muted">
                                    <div class="text-dark fw-semibold"><?= date('d/m/Y', strtotime($ord['created_at'])) ?></div>
                                    <div><?= date('H:i', strtotime($ord['created_at'])) ?></div>
                                </div>
                            </td>
                            <td class="text-center">
                                <div class="d-flex flex-column align-items-center gap-1">
                                    <span class="badge bg-light text-dark border w-75 py-2"><i class="fa-solid fa-user me-1 text-primary"></i> <?= htmlspecialchars((string)($ord['buyer_name'] ?? 'Guest')) ?></span>
                                    <i class="fa-solid fa-arrow-down-long text-muted" style="font-size: 0.6rem;"></i>
                                    <span class="badge bg-light text-muted border w-75 py-2"><i class="fa-solid fa-shop me-1"></i> <?= htmlspecialchars((string)($ord['seller_name'] ?? 'Seller')) ?></span>
                                </div>
                            </td>
                            <td>
                                <?= getPaymentMethodBadge($ord['payment_method'] ?? 'cod') ?>
                            </td>
                            <td class="fw-bold text-danger text-nowrap">
                                <?= number_format((float)$ord['total_price'], 0, ',', '.') ?> đ
                            </td>
                            <td>
                                <?= getStatusBadge($ord['order_status']) ?>
                            </td>
                            <td class="text-end px-4">
                                <div class="d-flex justify-content-end gap-2">
                                    <a href="index.php?page=admin_order_detail&id=<?= $ord['id'] ?>" class="btn btn-sm btn-outline-dark border-0 rounded-circle" style="width:34px; height:34px; display:flex; align-items:center; justify-content:center;" title="Xem chi tiết">
                                        <i class="fa-solid fa-eye"></i>
                                    </a>
                                    <button class="btn btn-sm btn-outline-dark border-0 rounded-circle" style="width:34px; height:34px; display:flex; align-items:center; justify-content:center;" onclick="window.print()" title="In đơn">
                                        <i class="fa-solid fa-print"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.transition-all { transition: all 0.2s ease; }
.hover-orange:hover { color: #FF5722 !important; }
.table-admin tbody tr:hover { background-color: rgba(0,0,0,0.01); }
</style>

<?php require_once __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
