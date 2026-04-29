<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/admin/admin_header.php';

/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

// 1. Auto-migration: Tạo bảng yêu cầu hoàn/hủy nếu chưa có
try {
    $conn->exec("CREATE TABLE IF NOT EXISTS refund_requests (
        id INT AUTO_INCREMENT PRIMARY KEY,
        order_id INT NOT NULL,
        user_id INT NOT NULL,
        reason TEXT,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;");

    // Khởi tạo dữ liệu mẫu nếu bảng trống (để Demo trực quan)
    $stmt = $conn->query("SELECT COUNT(*) FROM refund_requests");
    if ($stmt->fetchColumn() == 0) {
        $stmtOrder = $conn->query("SELECT id, buyer_id FROM orders LIMIT 2");
        $orders = $stmtOrder->fetchAll(PDO::FETCH_ASSOC);
        if (count($orders) > 0) {
            $insertStmt = $conn->prepare("INSERT INTO refund_requests (order_id, user_id, reason, status) VALUES (?, ?, ?, ?)");
            $insertStmt->execute([$orders[0]['id'], $orders[0]['buyer_id'], 'Sản phẩm trầy xước nặng trong quá trình vận chuyển, không giống mô tả.', 'pending']);
            if (isset($orders[1])) {
                $insertStmt->execute([$orders[1]['id'], $orders[1]['buyer_id'], 'Tôi vô tình đặt nhầm mẫu xe này, xin hãy hủy sớm.', 'pending']);
            }
        }
    }
} catch (PDOException $e) {
    // Bỏ qua lỗi migration
}

$msg = $_GET['msg'] ?? '';
$error = '';

// 2. Xử lý chức năng Duyệt / Từ chối (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $requestId = (int)($_POST['request_id'] ?? 0);

    if ($requestId > 0) {
        try {
            $conn->beginTransaction();
            
            // Lấy thông tin Refund Request
            $stmtReq = $conn->prepare("SELECT * FROM refund_requests WHERE id = ? FOR UPDATE");
            $stmtReq->execute([$requestId]);
            $requestData = $stmtReq->fetch(PDO::FETCH_ASSOC);

            if ($requestData && $requestData['status'] === 'pending') {
                if ($action === 'approve') {
                    // Cập nhật trạng thái thành approved
                    $conn->prepare("UPDATE refund_requests SET status = 'approved' WHERE id = ?")->execute([$requestId]);
                    
                    // Cập nhật đơn hàng thành cancelled
                    $conn->prepare("UPDATE orders SET order_status = 'cancelled' WHERE id = ?")->execute([$requestData['order_id']]);
                    
                    // Khoản tiền (Transaction) ở môi trường Alpha thường sẽ được ban quản trị hoàn tay qua bank
                    // Giải phóng xe về trạng thái available
                    $conn->prepare("UPDATE bikes SET status = 'available' WHERE id = (SELECT bike_id FROM orders WHERE id = ? LIMIT 1)")->execute([$requestData['order_id']]);
                    
                    $conn->commit();
                    header("Location: ?page=admin_refunds&msg=approve_success");
                    exit;

                } elseif ($action === 'reject') {
                    $conn->prepare("UPDATE refund_requests SET status = 'rejected' WHERE id = ?")->execute([$requestId]);
                    $conn->commit();
                    header("Location: ?page=admin_refunds&msg=reject_success");
                    exit;
                }
            } else {
                $conn->rollBack();
                $error = "Yêu cầu không hợp lệ hoặc đã được xử lý trước đó.";
            }

        } catch (PDOException $e) {
            $conn->rollBack();
            $error = "Lỗi xử lý Database: " . $e->getMessage();
        }
    }
}

// 3. Truy xuất danh sách Refund Requests
$requests = [];
try {
    $sql = "SELECT r.*, 
                   o.order_code, o.total_price, o.payment_method, o.order_status,
                   u.username AS requester_name
            FROM refund_requests r
            JOIN orders o ON r.order_id = o.id
            JOIN users u ON r.user_id = u.id
            ORDER BY r.status = 'pending' DESC, r.created_at DESC";
    $stmtList = $conn->query($sql);
    $requests = $stmtList->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error = "Lỗi tải dữ liệu: " . $e->getMessage();
}

function getRefundStatusBadge($status) {
    switch($status) {
        case 'pending': return '<span class="badge bg-warning text-dark"><i class="fa-solid fa-hourglass-half"></i> Đang chờ xử lý</span>';
        case 'approved': return '<span class="badge bg-success"><i class="fa-solid fa-check"></i> Đã hoàn tiền / Hủy</span>';
        case 'rejected': return '<span class="badge bg-danger"><i class="fa-solid fa-ban"></i> Đã từ chối</span>';
        default: return '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
    }
}
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <h2 class="h3 fw-bold mb-0">Yêu cầu Hoàn/Hủy Đơn Hàng</h2>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="admin-card border-0 shadow-sm rounded-4 overflow-hidden">
    <div class="table-responsive">
        <table class="table table-hover table-admin align-middle mb-0">
            <thead class="table-light">
                <tr>
                    <th scope="col" class="py-3 px-4">Mã Đơn</th>
                    <th scope="col" class="py-3">Người yêu cầu</th>
                    <th scope="col" class="py-3" style="max-width: 250px;">Lý do Hoàn/Hủy</th>
                    <th scope="col" class="py-3">Trị giá đơn</th>
                    <th scope="col" class="py-3 text-center">Trạng thái</th>
                    <th scope="col" class="py-3 text-end px-4">Thao tác Admin</th>
                </tr>
            </thead>
            <tbody class="border-top-0">
                <?php if (empty($requests)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <div class="mx-auto mb-3 text-muted opacity-25" style="font-size: 4rem;"><i class="fa-solid fa-clipboard-check"></i></div>
                            <h5 class="fw-bold text-dark mb-1">Tin vui!</h5>
                            <p class="text-muted small mb-0">Không có yêu cầu hoàn tiền hay khiếu nại nào cần xử lý.</p>
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($requests as $req): ?>
                        <tr>
                            <td class="px-4 fw-bold font-monospace">
                                <a href="?page=admin_order_detail&id=<?= $req['order_id'] ?>" class="text-dark text-decoration-none hover-orange">
                                    #<?= htmlspecialchars((string)$req['order_code']) ?>
                                </a>
                                <div class="text-muted small" style="font-size: 0.75rem;"><?= date('d/m/Y - H:i', strtotime($req['created_at'])) ?></div>
                            </td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div class="bg-secondary rounded-circle d-flex align-items-center justify-content-center text-white" style="width: 28px; height: 28px; font-size: 0.75rem;">
                                        <i class="fa-solid fa-user"></i>
                                    </div>
                                    <span class="fw-semibold text-dark"><?= htmlspecialchars((string)$req['requester_name']) ?></span>
                                </div>
                            </td>
                            <td style="max-width: 250px;">
                                <div class="text-muted small line-clamp-2" title="<?= htmlspecialchars((string)$req['reason']) ?>">
                                    <?= htmlspecialchars((string)$req['reason']) ?>
                                </div>
                            </td>
                            <td class="fw-bold text-danger text-nowrap">
                                <?= number_format((float)$req['total_price'], 0, ',', '.') ?> đ
                            </td>
                            <td class="text-center">
                                <?= getRefundStatusBadge($req['status']) ?>
                            </td>
                            <td class="text-end px-4">
                                <?php if ($req['status'] === 'pending'): ?>
                                    <div class="d-flex justify-content-end gap-2">
                                        <!-- Form Duyệt -->
                                        <form method="POST" action="?page=admin_refunds" class="d-inline" onsubmit="return confirm('Bạn có chắc chắn CHẤP NHẬN hoàn tiền và Hủy đơn hàng này? Hệ thống sẽ giải phóng xe về trạng thái Đang hiển thị.');">
                                            <input type="hidden" name="action" value="approve">
                                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success font-weight-bold px-3 py-1 rounded-3 d-flex align-items-center gap-1 shadow-sm border-success">
                                                <i class="fa-solid fa-check"></i> Chấp nhận
                                            </button>
                                        </form>

                                        <!-- Form Từ chối -->
                                        <form method="POST" action="?page=admin_refunds" class="d-inline" onsubmit="return confirm('Bạn muốn TỪ CHỐI yêu cầu này? Đơn hàng vẫn sẽ giữ nguyên trạng thái cũ.');">
                                            <input type="hidden" name="action" value="reject">
                                            <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger font-weight-bold px-3 py-1 rounded-3 d-flex align-items-center gap-1 shadow-sm border-danger">
                                                <i class="fa-solid fa-xmark"></i> Quét bỏ
                                            </button>
                                        </form>
                                    </div>
                                <?php else: ?>
                                    <span class="text-muted small fst-italic"><i class="fa-solid fa-lock"></i> Đã đóng</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<style>
.hover-orange:hover { color: #FF5722 !important; }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
.table-admin tbody tr:hover { background-color: rgba(0,0,0,0.01); }
</style>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const urlParams = new URLSearchParams(window.location.search);
    const msg = urlParams.get('msg');
    
    if (msg === 'approve_success') {
        Swal.fire({
            title: 'Thành công!',
            text: 'Yêu cầu được chấp nhận. Đơn hàng đã Hủy và Xe được giải phóng.',
            icon: 'success',
            confirmButtonColor: '#10b981'
        });
        window.history.replaceState({}, document.title, window.location.pathname + "?page=admin_refunds");
    } else if (msg === 'reject_success') {
         Swal.fire({
            title: 'Đã từ chối!',
            text: 'Yêu cầu khiếu nại đã bị từ chối.',
            icon: 'info',
            confirmButtonColor: '#FF5722'
        });
        window.history.replaceState({}, document.title, window.location.pathname + "?page=admin_refunds");
    }
</script>

<?php require_once __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
