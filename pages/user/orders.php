<?php
declare(strict_types=1);

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$conn = require __DIR__ . '/../../config/db.php';
$myId = (int)$_SESSION['user_id'];

// Lấy danh sách Đơn Tôi Mua (buyer_id = $myId)
$stmtBuy = $conn->prepare("
    SELECT o.order_code, o.total_price, o.order_status, o.created_at, 
           o.shipping_address, o.shipping_method, o.payment_method, o.recipient_name, o.recipient_phone,
           b.title, b.image_url, b.id as bike_id
    FROM orders o
    JOIN bikes b ON o.bike_id = b.id
    WHERE o.buyer_id = ?
    ORDER BY o.created_at DESC
");
$stmtBuy->execute([$myId]);
$buyOrders = $stmtBuy->fetchAll(PDO::FETCH_ASSOC);

// Lấy danh sách Đơn Tôi Bán (seller_id = $myId)
$stmtSell = $conn->prepare("
    SELECT o.order_code, o.total_price, o.order_status, o.created_at, 
           o.shipping_address, o.shipping_method, o.payment_method, o.recipient_name, o.recipient_phone,
           b.title, b.image_url, b.id as bike_id
    FROM orders o
    JOIN bikes b ON o.bike_id = b.id
    WHERE o.seller_id = ?
    ORDER BY o.created_at DESC
");
$stmtSell->execute([$myId]);
$sellOrders = $stmtSell->fetchAll(PDO::FETCH_ASSOC);

function resolveOrderImg($raw) {
    if (empty($raw)) return BASE_URL . 'public/assets/images/default-bike.jpg';
    if (str_starts_with(strtolower($raw), 'http')) return $raw;
    return BASE_URL . 'public/uploads/bikes/' . rawurlencode($raw);
}

function getOrderBadge($status) {
    switch ($status) {
        case 'waiting_payment': return '<span class="badge bg-secondary text-white">Chờ xác nhận</span>';
        case 'paid': return '<span class="badge bg-warning text-dark">Đã xác nhận/Thanh toán</span>';
        case 'shipping': return '<span class="badge bg-info text-dark">Đang giao hàng</span>';
        case 'completed': return '<span class="badge bg-success text-white">Hoàn thành</span>';
        case 'cancelled': return '<span class="badge bg-danger text-white">Đã hủy</span>';
        default: return '<span class="badge bg-light text-dark">Chưa rõ</span>';
    }
}

function getPaymentBadge($method) {
    if ($method === 'vietqr') return '<span class="badge border border-primary text-primary bg-white"><i class="fa-solid fa-qrcode"></i> VietQR</span>';
    if ($method === 'cod') return '<span class="badge border border-success text-success bg-white"><i class="fa-solid fa-money-bill-wave"></i> Thu hộ (COD)</span>';
    return '-';
}

function getShippingBadge($method) {
    if ($method === 'express') return '<span class="badge border border-danger text-danger bg-white"><i class="fa-solid fa-truck-fast"></i> Hỏa tốc</span>';
    if ($method === 'pickup') return '<span class="badge border border-dark text-dark bg-white"><i class="fa-solid fa-store"></i> Tự lấy hàng</span>';
    return '<span class="badge border border-secondary text-secondary bg-white"><i class="fa-solid fa-truck"></i> Tiêu chuẩn</span>';
}
?>

<section class="py-5" style="background:#f8f9fa; min-height: 80vh;">
  <div class="container" style="max-width: 900px;">
    
    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item"><a href="<?= BASE_URL ?>" class="text-muted">Trang chủ</a></li>
        <li class="breadcrumb-item active text-dark fw-medium" aria-current="page">Quản lý Đơn hàng</li>
      </ol>
    </nav>

    <h2 class="fw-bold mb-4">Đơn mua & Đơn bán</h2>

    <!-- Nav tabs -->
    <ul class="nav nav-tabs border-bottom-0 mb-4 gap-2" id="orderTabs" role="tablist">
      <li class="nav-item" role="presentation">
        <button class="nav-link active px-4 py-3 border-0 fw-bold rounded-top-3" id="buy-tab" data-bs-toggle="tab" data-bs-target="#buy" type="button" role="tab" aria-controls="buy" aria-selected="true" style="transition: background .2s, color .2s; background: #FF5722; color: #fff;">
            Đơn Tôi Mua <span class="badge bg-white text-dark ms-1"><?= count($buyOrders) ?></span>
        </button>
      </li>
      <li class="nav-item" role="presentation">
        <button class="nav-link px-4 py-3 border-0 fw-bold rounded-top-3 bg-white text-muted shadow-sm" id="sell-tab" data-bs-toggle="tab" data-bs-target="#sell" type="button" role="tab" aria-controls="sell" aria-selected="false">
            Đơn Tôi Bán <span class="badge bg-light text-dark border ms-1"><?= count($sellOrders) ?></span>
        </button>
      </li>
    </ul>

    <!-- Tab panes -->
    <div class="tab-content">
      
      <!-- TAB ĐƠN TÔI MUA -->
      <div class="tab-pane fade show active" id="buy" role="tabpanel" aria-labelledby="buy-tab" tabindex="0">
        <?php if (empty($buyOrders)): ?>
            <div class="text-center bg-white p-5 rounded-4 shadow-sm">
                <div class="mb-3"><i class="fa-solid fa-box-open text-muted" style="font-size: 3rem;"></i></div>
                <h5 class="fw-bold">Chưa có đơn hàng nào</h5>
                <p class="text-muted mb-4">Bạn chưa thực hiện giao dịch đặt mua chiếc xe nào.</p>
                <a href="<?= BASE_URL ?>?page=shop" class="btn fw-bold px-4 py-2 rounded-pill" style="background:#FF5722;color:#fff;">Khám phá ngay</a>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-4">
                <?php foreach ($buyOrders as $ord): ?>
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header bg-white border-bottom-0 pt-3 px-4 d-flex justify-content-between align-items-center">
                            <div class="fw-bold font-monospace text-muted">MÃ ĐƠN: <?= htmlspecialchars($ord['order_code'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div><?= getOrderBadge($ord['order_status']) ?></div>
                        </div>
                        
                        <div class="card-body px-4 pb-3">
                            <div class="d-flex align-items-start gap-4 pb-3 border-bottom mb-3">
                                <img src="<?= resolveOrderImg($ord['image_url']) ?>" alt="Ảnh xe" class="rounded-3 object-fit-cover flex-shrink-0" style="width: 100px; height: 80px; border: 1px solid #eee;">
                                <div class="flex-grow-1">
                                    <h5 class="fw-bold mb-1 line-clamp-2"><a href="<?= BASE_URL ?>?page=bike-detail&id=<?= $ord['bike_id'] ?>" class="text-dark text-decoration-none hover-orange"><?= htmlspecialchars($ord['title'], ENT_QUOTES, 'UTF-8') ?></a></h5>
                                    <div class="text-muted small mb-2">Ngày đặt: <?= date('H:i d/m/Y', strtotime($ord['created_at'])) ?></div>
                                    <div class="d-flex gap-2 mb-1">
                                      <?= getShippingBadge($ord['shipping_method']) ?>
                                      <?= getPaymentBadge($ord['payment_method']) ?>
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <div class="text-muted small mb-1">Tổng tiền</div>
                                    <div class="fw-bold text-danger fs-5"><?= number_format((float)$ord['total_price'], 0, ',', '.') ?> đ</div>
                                </div>
                            </div>
                            
                            <div class="row g-2 align-items-start bg-light rounded-3 p-3 text-muted small">
                              <div class="col-sm-4 fw-bold text-dark"><i class="fa-solid fa-location-dot me-1"></i> Giao đến:</div>
                              <div class="col-sm-8">
                                <span class="fw-medium text-dark"><?= htmlspecialchars((string)$ord['recipient_name'], ENT_QUOTES, 'UTF-8') ?></span> - <?= htmlspecialchars((string)$ord['recipient_phone'], ENT_QUOTES, 'UTF-8') ?><br>
                                <?= htmlspecialchars((string)$ord['shipping_address'], ENT_QUOTES, 'UTF-8') ?>
                              </div>
                            </div>
                        </div>
                        
                        <div class="card-footer bg-white border-top-0 px-4 pb-4 pt-1 text-end">
                            <?php if ($ord['order_status'] === 'waiting_payment'): ?>
                                <button type="button" onclick="Swal.fire('Vui lòng chờ','Chủ shop đang xác nhận đơn hoặc đang chờ khoản tiền check nổi rớt vào QR.','info')" class="btn btn-sm btn-dark fw-bold px-3 py-2 rounded-pill shadow-sm">
                                    <i class="fa-regular fa-clock me-1"></i> Chờ Xác nhận
                                </button>
                            <?php elseif ($ord['order_status'] === 'shipping'): ?>
                                <button type="button" onclick="confirmReceive('<?= $ord['order_code'] ?>')" class="btn btn-sm fw-bold px-3 py-2 rounded-pill shadow-sm" style="background:#10b981;color:#fff;">
                                    <i class="fa-solid fa-box-open me-1"></i> Đã Nhận Hàng
                                </button>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>?page=bike-detail&id=<?= $ord['bike_id'] ?>" class="btn btn-sm btn-outline-secondary fw-bold px-3 py-2 rounded-pill ms-2">
                                Chi tiết xe
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
      </div>

      <!-- TAB ĐƠN TÔI BÁN -->
      <div class="tab-pane fade" id="sell" role="tabpanel" aria-labelledby="sell-tab" tabindex="0">
        <?php if (empty($sellOrders)): ?>
            <div class="text-center bg-white p-5 rounded-4 shadow-sm">
                <div class="mb-3"><i class="fa-solid fa-store-slash text-muted" style="font-size: 3rem;"></i></div>
                <h5 class="fw-bold">Chưa có đơn bán nào</h5>
                <p class="text-muted mb-4">Bạn chưa nhận được order mua hàng nào.</p>
                <a href="<?= BASE_URL ?>?page=post-bike" class="btn fw-bold px-4 py-2 rounded-pill bg-dark text-white">Đăng xe lên ngay</a>
            </div>
        <?php else: ?>
            <div class="d-flex flex-column gap-4">
                <?php foreach ($sellOrders as $ord): ?>
                    <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                        <div class="card-header bg-white border-bottom-0 pt-3 px-4 d-flex justify-content-between align-items-center">
                            <div class="fw-bold font-monospace text-muted">MÃ ĐƠN: <?= htmlspecialchars($ord['order_code'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div><?= getOrderBadge($ord['order_status']) ?></div>
                        </div>
                        <div class="card-body px-4 pb-3">
                            <div class="d-flex align-items-start gap-4 pb-3 border-bottom mb-3">
                                <img src="<?= resolveOrderImg($ord['image_url']) ?>" alt="Ảnh xe" class="rounded-3 object-fit-cover flex-shrink-0" style="width: 100px; height: 80px; border: 1px solid #eee;">
                                <div class="flex-grow-1">
                                    <h5 class="fw-bold mb-1 line-clamp-2"><a href="<?= BASE_URL ?>?page=bike-detail&id=<?= $ord['bike_id'] ?>" class="text-dark text-decoration-none hover-orange"><?= htmlspecialchars($ord['title'], ENT_QUOTES, 'UTF-8') ?></a></h5>
                                    <div class="text-muted small mb-2">Ngày đặt: <?= date('H:i d/m/Y', strtotime($ord['created_at'])) ?></div>
                                    <div class="d-flex gap-2 mb-1">
                                      <?= getShippingBadge($ord['shipping_method']) ?>
                                      <?= getPaymentBadge($ord['payment_method']) ?>
                                    </div>
                                </div>
                                <div class="text-end flex-shrink-0">
                                    <div class="text-muted small mb-1">Thu nhập</div>
                                    <div class="fw-bold text-success fs-5">+ <?= number_format((float)$ord['total_price'], 0, ',', '.') ?> đ</div>
                                </div>
                            </div>

                            <div class="row g-2 align-items-start mt-2 bg-light rounded-3 p-3 text-muted small">
                              <div class="col-sm-4 fw-bold text-dark"><i class="fa-solid fa-address-book me-1"></i> Thông tin khách:</div>
                              <div class="col-sm-8">
                                <span class="fw-medium text-dark"><?= htmlspecialchars((string)$ord['recipient_name'], ENT_QUOTES, 'UTF-8') ?></span> - <?= htmlspecialchars((string)$ord['recipient_phone'], ENT_QUOTES, 'UTF-8') ?><br>
                                <?= htmlspecialchars((string)$ord['shipping_address'], ENT_QUOTES, 'UTF-8') ?>
                              </div>
                            </div>
                        </div>

                        <div class="card-footer bg-white border-top-0 px-4 pb-4 pt-1 text-end">
                            <?php if ($ord['order_status'] === 'waiting_payment'): ?>
                                <button type="button" onclick="Swal.fire('Xác nhận Đơn','Hãy liên hệ khách hàng để xác nhận COD hoặc chờ check biến động số dư VietQR rồi mới tiến hành đi lệnh Giao hàng trên hệ thống vận chuyển nhé!','info')" class="btn btn-sm fw-bold px-3 py-2 rounded-pill shadow-sm" style="background:#FF5722; color:#fff;">
                                    <i class="fa-solid fa-check me-1"></i> Duyệt Đơn
                                </button>
                            <?php endif; ?>
                            <a href="<?= BASE_URL ?>?page=bike-detail&id=<?= $ord['bike_id'] ?>" class="btn btn-sm btn-outline-secondary fw-bold px-3 py-2 rounded-pill ms-2">
                                Chi tiết Sp
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
      </div>

    </div>
  </div>
</section>

<style>
/* Tab Styling */
.nav-tabs .nav-link { cursor: pointer; }
.nav-tabs .nav-link.active { background: #FF5722 !important; color: #fff !important; }
.nav-tabs .nav-link:not(.active):hover { background: #f1f5f9 !important; color: #FF5722 !important; }
.hover-orange:hover { color: #FF5722 !important; }
.line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Dynamic tab styling script
    const tabs = document.querySelectorAll('button[data-bs-toggle="tab"]');
    tabs.forEach(t => {
        t.addEventListener('shown.bs.tab', function(e) {
            tabs.forEach(bt => {
                bt.style.background = '#fff';
                bt.style.color = '#6c757d'; 
            });
            e.target.style.background = '#FF5722';
            e.target.style.color = '#fff';
            e.target.querySelector('.badge').classList.replace('bg-light','bg-white');
            if(e.relatedTarget) {
                e.relatedTarget.querySelector('.badge').classList.replace('bg-white','bg-light');
            }
        });
    });
});

function confirmReceive(orderCode) {
    Swal.fire({
        title: 'Nhận hàng thành công?',
        text: "Bạn xác nhận Shipper đã giao xe an toàn chứ? Đơn hàng sẽ được đóng.",
        icon: 'success',
        showCancelButton: true,
        confirmButtonColor: '#10b981',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Xác nhận Đã nhận'
    }).then((result) => {
        if (result.isConfirmed) {
            Swal.fire('Thành công', 'Tính năng chốt đơn Backend đang trong giai đoạn Alpha.', 'success');
        }
    });
}
</script>
