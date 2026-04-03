<?php 
declare(strict_types=1);

if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = 'Bạn cần đăng nhập để thanh toán.';
    echo "<script>window.location.href='".BASE_URL."?page=login';</script>";
    exit;
}

$bikeId = isset($_GET['bike_id']) ? (int)$_GET['bike_id'] : 0;
if ($bikeId <= 0) {
    header('Location: ' . BASE_URL . '?page=404');
    exit;
}

$conn = require __DIR__ . '/../config/db.php';
$myId = (int)$_SESSION['user_id'];

try {
    // Thông tin xe
    $bikeStmt = $conn->prepare("SELECT id, title, price, status, user_id, image_url FROM bikes WHERE id = ? LIMIT 1");
    $bikeStmt->execute([$bikeId]);
    $bike = $bikeStmt->fetch(PDO::FETCH_ASSOC);

    if (!$bike || $bike['status'] !== 'available') {
        $_SESSION['error'] = 'Xe không tồn tại hoặc đã được mua.';
        echo "<script>window.location.href='".BASE_URL."?page=shop';</script>";
        exit;
    }

    if ($bike['user_id'] == $myId) {
        $_SESSION['error'] = 'Bạn không thể tự mua xe của chính mình.';
        echo "<script>window.location.href='".BASE_URL."?page=shop';</script>";
        exit;
    }

    $price = (float)$bike['price'];
    
    // Resolve Image
    $imgRaw = trim((string)$bike['image_url']);
    if ($imgRaw === '') {
        $bikeImg = BASE_URL . 'public/assets/images/default-bike.jpg';
    } elseif (str_starts_with(strtolower($imgRaw), 'http')) {
        $bikeImg = $imgRaw;
    } else {
        $bikeImg = BASE_URL . 'public/uploads/bikes/' . rawurlencode($imgRaw);
    }

} catch (PDOException $e) {
    $_SESSION['error'] = 'Lỗi hệ thống: ' . $e->getMessage();
    echo "<script>window.location.href='".BASE_URL."?page=shop';</script>";
    exit;
}
?>

<section class="py-5" style="background:#f8f9fa; min-height: 80vh;">
  <div class="container" style="max-width: 1000px;">
    
    <div class="row g-4">
      <!-- CỘT TRÁI: FORM ĐIỀN THÔNG TIN -->
      <div class="col-lg-7">
        <form id="checkoutForm">
          <input type="hidden" name="bike_id" value="<?= $bikeId ?>">
          
          <!-- THÔNG TIN GIAO HÀNG -->
          <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
              <h5 class="fw-bold mb-4"><i class="fa-solid fa-location-dot text-danger me-2"></i> Thông tin Nhận hàng</h5>
              
              <div class="row g-3">
                <div class="col-md-6">
                  <label class="form-label text-muted small fw-bold">Tên người nhận</label>
                  <input type="text" name="recipient_name" class="form-control form-control-lg border-opacity-50" placeholder="VD: Nguyễn Văn A" required>
                </div>
                <div class="col-md-6">
                  <label class="form-label text-muted small fw-bold">Số điện thoại</label>
                  <input type="text" name="recipient_phone" class="form-control form-control-lg border-opacity-50" placeholder="09xxxxxxxx" required>
                </div>
                <div class="col-12">
                  <label class="form-label text-muted small fw-bold">Địa chỉ giao hàng</label>
                  <textarea name="shipping_address" class="form-control border-opacity-50" rows="3" placeholder="Số nhà, Tên đường, Xã/Phường, Quận/Huyện, Tỉnh/TP" required></textarea>
                </div>
              </div>
            </div>
          </div>

          <!-- PHƯƠNG THỨC VẬN CHUYỂN -->
          <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4">
              <h5 class="fw-bold mb-4"><i class="fa-solid fa-truck-fast text-primary me-2"></i> Phương thức Vận chuyển</h5>
              
              <div class="d-flex flex-column gap-3">
                <label class="border p-3 rounded-3 d-flex align-items-center justify-content-between c-radio-label" style="cursor: pointer; transition: all 0.2s;">
                  <div class="d-flex align-items-center gap-3">
                    <input class="form-check-input mt-0" type="radio" name="shipping_method" value="standard" onchange="calcTotal()" checked>
                    <div>
                      <div class="fw-bold text-dark">Giao hàng Tiêu chuẩn</div>
                      <div class="text-muted small">Dự kiến nhận hàng trong 3-5 ngày</div>
                    </div>
                  </div>
                  <div class="fw-bold">+ 50.000 đ</div>
                </label>

                <label class="border p-3 rounded-3 d-flex align-items-center justify-content-between c-radio-label" style="cursor: pointer; transition: all 0.2s;">
                  <div class="d-flex align-items-center gap-3">
                    <input class="form-check-input mt-0" type="radio" name="shipping_method" value="express" onchange="calcTotal()">
                    <div>
                      <div class="fw-bold text-dark">Giao hàng Hỏa tốc</div>
                      <div class="text-muted small">Nhận hàng dự kiến trong 1-2 ngày</div>
                    </div>
                  </div>
                  <div class="fw-bold">+ 150.000 đ</div>
                </label>

                <label class="border p-3 rounded-3 d-flex align-items-center justify-content-between c-radio-label" style="cursor: pointer; transition: all 0.2s;">
                  <div class="d-flex align-items-center gap-3">
                    <input class="form-check-input mt-0" type="radio" name="shipping_method" value="pickup" onchange="calcTotal()">
                    <div>
                      <div class="fw-bold text-dark">Lấy hàng trực tiếp</div>
                      <div class="text-muted small">Sang tận nơi để lái thử và lấy hàng</div>
                    </div>
                  </div>
                  <div class="fw-bold text-success">Miễn phí</div>
                </label>
              </div>
            </div>
          </div>

          <!-- PHƯƠNG THỨC THANH TOÁN -->
          <div class="card border-0 shadow-sm rounded-4 mb-4">
            <div class="card-body p-4 p-md-5">
              <h5 class="fw-bold mb-4 fs-5"><i class="fa-solid fa-credit-card me-2" style="color: #FF5722;"></i> Phương thức thanh toán</h5>
              
              <div class="d-flex flex-column gap-3">
                <!-- VNPAY -->
                <label class="border rounded-4 p-3 p-md-4 c-radio-label position-relative" style="cursor: pointer; transition: all 0.3s ease;">
                  <div class="d-flex align-items-center gap-3 gap-md-4">
                    <input class="form-check-input mt-0 position-absolute opacity-0" type="radio" name="payment_method" value="vnpay" checked>
                    <div class="d-flex align-items-center justify-content-center rounded-3 bg-light" style="width: 58px; height: 58px; flex-shrink: 0;">
                       <img src="https://vnpay.vn/s1/statics.vnpay.vn/2023/6/0oxhzjmxbksr1686814746087.png" alt="VNPAY" style="height: 24px; object-fit: contain;">
                    </div>
                    <div class="flex-grow-1">
                       <div class="fw-bold text-dark" style="font-size: 1.1rem;">Thanh toán trực tuyến VNPAY</div>
                       <div class="text-muted small mt-1">Hỗ trợ thẻ ATM nội địa, Visa/Mastercard và quét mã QR App Ngân hàng</div>
                    </div>
                    <div class="ms-auto checkmark-container ps-2">
                       <i class="fa-solid fa-circle-check fs-4 text-orange opacity-0" style="transition: all 0.3s ease; transform: scale(0.8);"></i>
                    </div>
                  </div>
                </label>

                <!-- COD -->
                <label class="border rounded-4 p-3 p-md-4 c-radio-label position-relative" style="cursor: pointer; transition: all 0.3s ease;">
                  <div class="d-flex align-items-center gap-3 gap-md-4">
                    <input class="form-check-input mt-0 position-absolute opacity-0" type="radio" name="payment_method" value="cod">
                    <div class="d-flex align-items-center justify-content-center rounded-3 bg-light" style="width: 58px; height: 58px; flex-shrink: 0;">
                      <i class="fa-solid fa-hand-holding-dollar fs-3 text-success"></i>
                    </div>
                    <div class="flex-grow-1">
                      <div class="fw-bold text-dark" style="font-size: 1.1rem;">Thanh toán khi nhận hàng (COD)</div>
                      <div class="text-muted small mt-1">Nhận xe, kiểm tra trực tiếp và thanh toán tiền mặt cho đơn vị vận chuyển</div>
                    </div>
                    <div class="ms-auto checkmark-container ps-2">
                       <i class="fa-solid fa-circle-check fs-4 text-orange opacity-0" style="transition: all 0.3s ease; transform: scale(0.8);"></i>
                    </div>
                  </div>
                </label>
              </div>

            </div>
          </div>
        </form>
      </div>

      <!-- CỘT PHẢI: TÓM TẮT ĐƠN HÀNG (STICKY) -->
      <div class="col-lg-5">
        <div class="card border-0 shadow-sm rounded-4 sticky-top" style="top: 100px;">
          <div class="card-header bg-dark text-white p-4 border-0 rounded-top-4">
            <h5 class="fw-bold mb-0">Tóm tắt đơn hàng</h5>
          </div>
          <div class="card-body p-4">
            
            <div class="d-flex gap-3 border-bottom pb-4 mb-4">
              <img src="<?= htmlspecialchars($bikeImg, ENT_QUOTES, 'UTF-8') ?>" alt="Bike" class="rounded-3 object-fit-cover" style="width: 80px; height: 80px;">
              <div>
                <h6 class="fw-bold mb-1 line-clamp-2"><?= htmlspecialchars($bike['title'], ENT_QUOTES, 'UTF-8') ?></h6>
                <div class="text-muted small">Mã SP: #<?= $bikeId ?></div>
              </div>
            </div>

            <div class="d-flex justify-content-between mb-3">
              <span class="text-muted">Tạm tính (Giá xe)</span>
              <span class="fw-medium"><?= number_format($price, 0, ',', '.') ?> đ</span>
            </div>

            <div class="d-flex justify-content-between mb-3">
              <span class="text-muted">Phí Vận chuyển</span>
              <span class="fw-medium" id="lblFee">50.000 đ</span>
            </div>

            <div class="d-flex justify-content-between border-top pt-3 mt-4">
              <span class="fw-bold text-dark">TỔNG CỘNG</span>
              <span class="fw-bold fs-4" style="color: #FF5722;" id="lblTotal"><?= number_format($price + 50000, 0, ',', '.') ?> đ</span>
            </div>

            <button type="button" id="btnSubmitOrder" class="btn w-100 py-3 rounded-pill text-white fw-bold mt-4" style="background: #FF5722; font-size: 1.1rem; transition: transform 0.1s, box-shadow 0.2s;" onmouseover="this.style.transform='translateY(-2px)'; this.style.boxShadow='0 8px 15px rgba(255,87,34,0.3)';" onmouseout="this.style.transform='none'; this.style.boxShadow='none';">
              HOÀN TẤT ĐẶT HÀNG
            </button>

          </div>
        </div>
      </div>


    </div>
  </div>
</section>

<!-- OVERLAY LOADING CHO VNPAY -->
<div id="vnpayOverlay" class="d-none align-items-center justify-content-center" style="position: fixed; inset: 0; background: rgba(33, 33, 33, 0.90); z-index: 9999; backdrop-filter: blur(8px);">
    <div class="text-center text-white" style="transform: translateY(-10%);">
        <div class="spinner-border mb-4" style="width: 4.5rem; height: 4.5rem; color: #FF5722;" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
        <h4 class="fw-bolder mb-2" style="letter-spacing: 0.5px;">Đang khởi tạo giao dịch...</h4>
        <p class="text-white-50 small mb-0">Vui lòng không đóng trình duyệt, hệ thống đang chuyển hướng tới <span class="fw-bold text-white">VNPAY</span> an toàn.</p>
    </div>
</div>

<style>
.text-orange { color: #FF5722 !important; }
.bg-light { background-color: #f8f9fa !important; }

/* Luxury Radio Labels */
.c-radio-label {
  border-width: 2px !important;
  border-color: #e9ecef !important;
}
.c-radio-label:hover {
  border-color: #adb5bd !important;
  background-color: #fafafa;
}

/* Checked State */
.c-radio-label:has(input:checked) {
  border-color: #FF5722 !important;
  background-color: rgba(255, 87, 34, 0.04) !important;
  box-shadow: 0 4px 15px rgba(255,87,34,0.08);
}

.c-radio-label:has(input:checked) i.text-orange {
  opacity: 1 !important;
  transform: scale(1) !important;
}

.c-radio-label:has(input:checked) .bg-light {
  background-color: #fff !important;
  box-shadow: 0 2px 8px rgba(0,0,0,0.05);
}
</style>

<script>
const basePrice = <?= $price ?>;

function calcTotal() {
    let fee = 0;
    const shipMethod = document.querySelector('input[name="shipping_method"]:checked').value;
    
    if (shipMethod === 'standard') fee = 50000;
    else if (shipMethod === 'express') fee = 150000;
    else fee = 0;

    const total = basePrice + fee;

    // Display updates instantly without reload
    document.getElementById('lblFee').innerText = (fee > 0 ? fee.toLocaleString('vi-VN') : '0') + ' đ';
    document.getElementById('lblTotal').innerText = total.toLocaleString('vi-VN') + ' đ';
}

document.addEventListener('DOMContentLoaded', function() {
    calcTotal(); // Khởi tạo tính toán ban đầu

    const btnSubmit = document.getElementById('btnSubmitOrder');
    btnSubmit.addEventListener('click', async function() {
        const formObj = document.getElementById('checkoutForm');
        if(!formObj.reportValidity()) return;

        const formData = new FormData(formObj);
        
        // Hiệu ứng Spin
        const originalText = btnSubmit.innerHTML;
        btnSubmit.innerHTML = '<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Đang xử lý...';
        btnSubmit.disabled = true;

        try {
            const response = await fetch('<?= BASE_URL ?>modules/order/checkout_act.php', {
                method: 'POST',
                body: formData
            });

            const result = await response.json();

            if (result.status === 'success') {
                if (result.payment_url) {
                    const overlay = document.getElementById('vnpayOverlay');
                    overlay.classList.remove('d-none');
                    overlay.classList.add('d-flex');
                    setTimeout(() => {
                        window.location.href = result.payment_url;
                    }, 1200); 
                    return;
                }

                if (result.redirect_url) {
                    window.location.href = result.redirect_url;
                    return;
                }

                window.location.href = '<?= BASE_URL ?>?page=order_success&code=' + result.order_code;
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Lỗi',
                    text: result.message,
                    confirmButtonColor: '#212121'
                });
                btnSubmit.innerHTML = originalText;
                btnSubmit.disabled = false;
            }
        } catch (err) {
            Swal.fire({
                icon: 'error',
                title: 'Lỗi mạng',
                text: 'Không thể kết nối đến máy chủ.',
                confirmButtonColor: '#212121'
            });
            btnSubmit.innerHTML = originalText;
            btnSubmit.disabled = false;
        }
    });
});
</script>
