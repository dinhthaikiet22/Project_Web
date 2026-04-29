<?php
declare(strict_types=1);

require_once __DIR__ . '/../../includes/admin/admin_header.php';
/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    echo "<div class='alert alert-danger m-4'>Đơn hàng không hợp lệ.</div>";
    require_once __DIR__ . '/../../includes/admin/admin_footer.php';
    exit;
}
$id = (int)$id;

// Handle Status Update
$msg = $_GET['msg'] ?? '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_status') {
    $new_status = $_POST['order_status'] ?? '';
    if (in_array($new_status, ['pending_payment', 'paid', 'shipping', 'completed', 'cancelled'])) {
        try {
            $stmt = $conn->prepare("UPDATE orders SET order_status = ? WHERE id = ?");
            $stmt->execute([$new_status, $id]);
            header("Location: ?page=admin_order_detail&id={$id}&msg=status_updated");
            exit;
        } catch (PDOException $e) {
            $error = "Lỗi cập nhật trạng thái: " . $e->getMessage();
        }
    } else {
        $error = "Trạng thái không hợp lệ.";
    }
}

// Fetch Order Detail
try {
    $sql = "SELECT o.*, 
                   buyer.username AS buyer_username, buyer.email AS buyer_email,
                   seller.username AS seller_username, seller.email AS seller_email,
                   b.title AS bike_title, b.price AS bike_price, b.image_url AS bike_image
            FROM orders o 
            LEFT JOIN users buyer ON o.buyer_id = buyer.id 
            LEFT JOIN users seller ON o.seller_id = seller.id 
            LEFT JOIN bikes b ON o.bike_id = b.id
            WHERE o.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    echo "<div class='alert alert-danger m-4'>Lỗi CSDL: " . htmlspecialchars($e->getMessage()) . "</div>";
    require_once __DIR__ . '/../../includes/admin/admin_footer.php';
    exit;
}

if (!$order) {
    echo "<div class='alert alert-danger m-4'>Không tìm thấy đơn hàng.</div>";
    require_once __DIR__ . '/../../includes/admin/admin_footer.php';
    exit;
}

function getStatusBadge(string $status): string {
    switch ($status) {
        case 'pending_payment': return '<span class="badge bg-warning text-dark"><i class="fa-regular fa-clock"></i> Chờ thanh toán</span>';
        case 'paid': return '<span class="badge bg-info text-dark"><i class="fa-solid fa-check-circle"></i> Đã xác nhận</span>';
        case 'shipping': return '<span class="badge bg-primary"><i class="fa-solid fa-truck-fast"></i> Đang giao</span>';
        case 'completed': return '<span class="badge bg-success"><i class="fa-solid fa-circle-check"></i> Hoàn tất</span>';
        case 'cancelled': return '<span class="badge bg-danger"><i class="fa-solid fa-circle-xmark"></i> Đã hủy</span>';
        default: return '<span class="badge bg-secondary">' . htmlspecialchars($status) . '</span>';
    }
}
function getPaymentMethod(string $method): string {
    $method = strtolower($method);
    if ($method === 'vnpay') return 'Thanh toán trực tuyến (VNPAY)';
    if ($method === 'cod') return 'Thanh toán khi nhận hàng (COD)';
    return htmlspecialchars(strtoupper($method));
}

// Fallback values since the DB might not have the phone/address stored per order but in users table, 
// wait, full_name, phone, address are usually saved into orders when checking out. Let's try to pull from order first, then users.
$buyer_name = $order['recipient_name'] ?? $order['buyer_username'] ?? 'Khách hàng';
$buyer_phone = $order['recipient_phone'] ?? 'Chưa cập nhật';
$buyer_address = $order['shipping_address'] ?? 'Chưa cập nhật';
?>

<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <a href="?page=admin_orders" class="text-decoration-none text-muted mb-2 d-inline-block"><i class="fa-solid fa-arrow-left me-1"></i> Quay lại danh sách</a>
        <h2 class="h3 fw-bold mb-0">Chi tiết đơn hàng #<?= htmlspecialchars((string)$order['order_code']) ?></h2>
    </div>
    <div class="d-flex gap-2">
        <a href="?page=admin_order_print&id=<?= $order['id'] ?>" target="_blank" class="btn btn-outline-secondary fw-bold px-4 shadow-sm bg-white"><i class="fa-solid fa-print me-2"></i>In đơn hàng</a>
    </div>
</div>

<?php if ($error): ?>
    <div class="alert alert-danger shadow-sm"><i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($error) ?></div>
<?php endif; ?>

<div class="row g-4">
    <!-- Cột trái: Thông tin khách hàng & người bán -->
    <div class="col-lg-5">
        <!-- Trạng thái Cập nhật Form -->
        <div class="admin-card border-0 shadow-sm rounded-4 mb-4">
            <h5 class="fw-bold mb-3 d-flex align-items-center"><i class="fa-solid fa-sliders text-primary me-2"></i> Trạng thái Đơn hàng</h5>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="text-muted fw-semibold">Hiện tại:</div>
                <div class="fs-5"><?= getStatusBadge($order['order_status']) ?></div>
            </div>
            
            <form action="?page=admin_order_detail&id=<?= $id ?>" method="POST" class="pt-3 border-top">
                <input type="hidden" name="action" value="update_status">
                <label class="form-label fw-semibold mb-2">Cập nhật trạng thái mới:</label>
                <div class="input-group">
                    <select name="order_status" class="form-select bg-light">
                        <option value="pending_payment" <?= $order['order_status'] === 'pending_payment' ? 'selected' : '' ?>>Chờ thanh toán</option>
                        <option value="paid" <?= $order['order_status'] === 'paid' ? 'selected' : '' ?>>Đã xác nhận</option>
                        <option value="shipping" <?= $order['order_status'] === 'shipping' ? 'selected' : '' ?>>Đang giao hàng</option>
                        <option value="completed" <?= $order['order_status'] === 'completed' ? 'selected' : '' ?>>Hoàn tất</option>
                        <option value="cancelled" <?= $order['order_status'] === 'cancelled' ? 'selected' : '' ?>>Đã hủy</option>
                    </select>
                    <button type="submit" class="btn text-white fw-bold px-3" style="background-color: #FF5722;">Cập nhật</button>
                </div>
            </form>
        </div>

        <!-- Khách hàng -->
        <div class="admin-card border-0 shadow-sm rounded-4 mb-4">
            <h5 class="fw-bold mb-3 d-flex align-items-center"><i class="fa-solid fa-user text-success me-2"></i> Thông tin Người mua</h5>
            <table class="table table-borderless mb-0">
                <tr><td class="text-muted ps-0" style="width:110px;">Họ và tên</td><td class="fw-bold text-dark"><?= htmlspecialchars((string)$buyer_name) ?></td></tr>
                <tr><td class="text-muted ps-0">Điện thoại</td><td class="fw-semibold"><?= htmlspecialchars((string)$buyer_phone) ?></td></tr>
                <?php if (!empty($order['buyer_email'])): ?>
                <tr><td class="text-muted ps-0">Email</td><td><?= htmlspecialchars((string)$order['buyer_email']) ?></td></tr>
                <?php endif; ?>
                <tr><td class="text-muted ps-0 align-top">Giao đến</td><td><?= nl2br(htmlspecialchars((string)$buyer_address)) ?></td></tr>
            </table>
        </div>

        <!-- Người bán -->
        <div class="admin-card border-0 shadow-sm rounded-4">
            <h5 class="fw-bold mb-3 d-flex align-items-center"><i class="fa-solid fa-shop text-orange me-2" style="color: #FF5722;"></i> Thông tin Người bán</h5>
            <table class="table table-borderless mb-0">
                <tr><td class="text-muted ps-0" style="width:110px;">Tên Shop</td><td class="fw-bold text-dark"><?= htmlspecialchars((string)($order['seller_username'] ?? 'Hệ thống/Không rõ')) ?></td></tr>
                <?php if (!empty($order['seller_email'])): ?>
                <tr><td class="text-muted ps-0">Email Shop</td><td><?= htmlspecialchars((string)$order['seller_email']) ?></td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

    <!-- Cột phải: Thông tin sản phẩm, vận chuyển -->
    <div class="col-lg-7">
        <div class="admin-card border-0 shadow-sm rounded-4 h-100 d-flex flex-column">
            <h5 class="fw-bold d-flex align-items-center mb-4"><i class="fa-solid fa-box-open text-primary me-2"></i> Chi tiết Sản phẩm & Thanh toán</h5>
            
            <!-- Sản phẩm -->
            <div class="d-flex p-3 bg-light rounded-3 mb-4 gap-3">
                <div style="width: 100px; height: 100px; background:#fff;" class="border rounded d-flex align-items-center justify-content-center flex-shrink-0">
                    <?php if (!empty($order['bike_image'])): ?>
                        <img src="<?= str_starts_with((string)$order['bike_image'], 'http') ? htmlspecialchars((string)$order['bike_image']) : ('public/uploads/bikes/' . htmlspecialchars((string)$order['bike_image'])) ?>" alt="Bike" style="max-width:100%; max-height:100%; object-fit:contain;">
                    <?php else: ?>
                        <i class="fa-solid fa-bicycle fa-2x text-muted"></i>
                    <?php endif; ?>
                </div>
                <div class="w-100 d-flex flex-column justify-content-center">
                    <h6 class="fw-bold mb-1 text-dark fs-5"><?= htmlspecialchars((string)($order['bike_title'] ?? 'Sản phẩm đã bị xóa')) ?></h6>
                    <?php if (!empty($order['bike_price'])): ?>
                        <div class="text-muted mb-2">Giá niêm yết: <?= number_format((float)$order['bike_price'], 0, ',', '.') ?> đ</div>
                    <?php endif; ?>
                    <div class="mt-auto d-flex justify-content-between align-items-end">
                        <span class="text-muted">Số lượng: 1</span>
                        <!-- Tổng thanh toán của đơn -->
                        <span class="fw-bold text-danger fs-5"><?= number_format((float)$order['total_price'], 0, ',', '.') ?> <span class="fs-6">VNĐ</span></span>
                    </div>
                </div>
            </div>

            <!-- Thanh toán -->
            <div class="row g-3 mb-4 mt-2 border-top pt-4">
                <div class="col-md-6">
                    <div class="text-muted fw-semibold mb-2">Phương thức thanh toán</div>
                    <div class="fw-bold text-dark"><i class="fa-solid fa-money-bill-wave text-success me-1"></i> <?= getPaymentMethod($order['payment_method'] ?? 'COD') ?></div>
                </div>
                <div class="col-md-6">
                    <div class="text-muted fw-semibold mb-2">Ngày đặt hàng</div>
                    <div class="fw-bold text-dark"><i class="fa-solid fa-calendar me-1"></i> <?= date('d/m/Y - H:i', strtotime($order['created_at'])) ?></div>
                </div>
            </div>
            
            <div class="alert alert-warning mt-auto mb-0 bg-warning bg-opacity-10 border-warning border-opacity-25 text-dark">
                <i class="fa-solid fa-bell text-warning me-2"></i> Lưu ý: Mọi thay đổi trạng thái sẽ cập nhật trực tiếp vào tài khoản người dùng mua và bán.
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script>
    const urlParams = new URLSearchParams(window.location.search);
    if (urlParams.get('msg') === 'status_updated') {
        Swal.fire({
            title: 'Thành công!',
            text: 'Trang thái đơn hàng đã được cập nhật.',
            icon: 'success',
            confirmButtonColor: '#FF5722'
        });
        window.history.replaceState({}, document.title, window.location.pathname + "?page=admin_order_detail&id=<?= $id ?>");
    }
</script>

<?php require_once __DIR__ . '/../../includes/admin/admin_footer.php'; ?>
