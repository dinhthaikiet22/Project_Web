<?php
declare(strict_types=1);

if (!isset($_SESSION['user_id'])) {
    echo "<script>window.location.href='".BASE_URL."?page=login';</script>";
    exit;
}

require_once __DIR__ . '/../config/config_vnpay.php';

$vnp_SecureHash = $_GET['vnp_SecureHash'] ?? '';
// Loại bỏ các thẻ không tham gia Hash
unset($_GET['vnp_SecureHashType']);
unset($_GET['vnp_SecureHash']);
unset($_GET['page']);

$inputData = array();
foreach ($_GET as $key => $value) {
    if (substr($key, 0, 4) == "vnp_") {
        $inputData[$key] = $value;
    }
}

ksort($inputData);
$i = 0;
$hashData = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashData = $hashData . '&' . urlencode($key) . "=" . urlencode((string)$value);
    } else {
        $hashData = $hashData . urlencode($key) . "=" . urlencode((string)$value);
        $i = 1;
    }
}

$secureHash = hash_hmac('sha512', $hashData, VNP_HASH_SECRET);
$isValid = ($secureHash === $vnp_SecureHash);

$vnp_ResponseCode = $_GET['vnp_ResponseCode'] ?? '';
$vnp_TxnRef = $_GET['vnp_TxnRef'] ?? '';
$orderCodeParts = explode('_', $vnp_TxnRef);
$orderCode = $orderCodeParts[0];

$isSuccess = false;
if ($isValid && $vnp_ResponseCode == '00') {
    $conn = require __DIR__ . '/../config/db.php';
    try {
        $stmtUpdate = $conn->prepare("UPDATE orders SET order_status = 'paid', payment_method = 'vnpay' WHERE order_code = ?");
        $stmtUpdate->execute([$orderCode]);
        
        $stmtBike = $conn->prepare("UPDATE bikes SET status = 'sold' WHERE id = (SELECT bike_id FROM orders WHERE order_code = ? LIMIT 1)");
        $stmtBike->execute([$orderCode]);
    } catch (Exception $e) {
        error_log("Update order error in vnpay_return: " . $e->getMessage());
    }
    $isSuccess = true;
}
?>

<div class="container d-flex justify-content-center align-items-center" style="min-height: 80vh;">
    <div class="text-center">
        <?php if ($isSuccess): ?>
            <!-- Spinner ẩn, dùng cho SweetAlert hiện lên nổi bật hơn -->
            <div class="spinner-border text-orange d-none" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <h4 class="text-white">Đang xử lý kết quả...</h4>
        <?php else: ?>
            <h2 class="fw-bold text-danger">
                <i class="fa-solid fa-circle-xmark"></i> Thanh toán thất bại
            </h2>
            <p class="text-muted mt-2">Giao dịch của bạn không thành công hoặc đã bị hủy từ ngân hàng.</p>
            <p class="text-muted small">Kiểm tra lại chữ ký hoặc kết nối.</p>
            <a href="<?= BASE_URL ?>?page=shop" class="btn btn-dark rounded-pill px-5 py-2 mt-3 fw-bold shadow-sm">
                Quay lại cửa hàng
            </a>
        <?php endif; ?>
    </div>
</div>

<?php if ($isSuccess): ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    Swal.fire({
        icon: 'success',
        title: 'Thanh toán thành công!',
        text: 'Đơn hàng #<?= htmlspecialchars($orderCode) ?> đã được thanh toán an toàn.',
        confirmButtonText: 'Xem Đơn Hàng',
        confirmButtonColor: '#FF5722',
        background: '#212121', /* Dark UI Luxury */
        color: '#fff',
        allowOutsideClick: false,
        padding: '3em',
        customClass: {
            title: 'fs-4 fw-bolder mb-2',
            confirmButton: 'px-4 py-2 rounded-pill fw-bold border-0 shadow-sm'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = '<?= BASE_URL ?>?page=user/orders';
        }
    });
});
</script>
<?php endif; ?>

<style>
.text-orange { color: #FF5722 !important; }
.swal2-popup.swal2-modal {
    box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5) !important;
    border: 1px solid #333 !important;
}
</style>
