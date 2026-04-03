<?php
declare(strict_types=1);

if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$orderCode = $_GET['code'] ?? '';
?>

<section class="py-5" style="background:#f8f9fa; min-height: 80vh; display: flex; align-items: center; justify-content: center;">
    <div class="container text-center" style="max-width: 600px;">
        <div class="card border-0 shadow-sm rounded-4 p-5">
            <div class="mb-4">
                <i class="fa-solid fa-circle-check" style="font-size: 5rem; color: #FF5722;"></i>
            </div>
            <h2 class="fw-bolder mb-3" style="color: #212121;">Đặt Hàng Thành Công!</h2>
            <p class="text-muted mb-4 fs-5">
                Cảm ơn bạn đã tin tưởng mua sắm tại CycleTrust. Đơn hàng của bạn đang được xử lý.
            </p>
            
            <?php if ($orderCode !== ''): ?>
            <div class="bg-light rounded-3 p-3 mb-4 d-inline-block">
                <span class="text-muted small me-2">Mã đơn hàng:</span>
                <span class="fw-bold fs-5 text-dark">#<?= htmlspecialchars($orderCode, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <?php endif; ?>
            
            <div class="d-flex flex-column flex-sm-row justify-content-center gap-3">
                <a href="<?= BASE_URL ?>?page=user/orders" class="btn text-white fw-bold px-4 py-3 rounded-pill" style="background-color: #FF5722;">
                    Quản Lý Giao Dịch
                </a>
                <a href="<?= BASE_URL ?>" class="btn btn-outline-dark fw-bold px-4 py-3 rounded-pill">
                    Tiếp Tục Mua Sắm
                </a>
            </div>
        </div>
    </div>
</section>
