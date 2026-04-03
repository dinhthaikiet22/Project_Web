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
            <div class="card-body p-4">
              <h5 class="fw-bold mb-4"><i class="fa-solid fa-credit-card text-success me-2"></i> Phương thức Thanh toán</h5>
              
              <div class="d-flex flex-column gap-3">
                <!-- VIETQR -->
                <label class="border p-3 rounded-3 c-radio-label" style="cursor: pointer;">
                  <div class="d-flex align-items-center gap-3">
                    <input class="form-check-input mt-0" type="radio" name="payment_method" value="vietqr" onchange="togglePaymentUI()" checked>
                    <div class="d-flex align-items-center gap-2">
                      <img src="https://img.vietqr.io/image/mb-1-compact.jpg" alt="VietQR" style="height: 30px; object-fit: contain;">
                      <div class="fw-bold text-dark">Chuyển khoản VietQR (Tự động xác nhận)</div>
                    </div>
                  </div>
                  
                  <div id="vietqrBox" class="mt-4 pt-3 border-top text-center" style="display: block;">
                    <div class="mb-2 text-dark font-monospace fw-medium">Quét mã QR dưới đây bằng App Ngân Hàng/MoMo:</div>
                    <img id="qrImage" src="" alt="VietQR" style="width: 200px; height: 200px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.1); border: 2px solid #FF5722;" class="mb-3">
                    <div class="text-danger small fw-bold">Vui lòng quét đúng mã QR để hệ thống tự động ghi nhận thanh toán!</div>
                  </div>
                </label>

                <!-- COD -->
                <label class="border p-3 rounded-3 c-radio-label" style="cursor: pointer;">
                  <div class="d-flex align-items-center gap-3">
                    <input class="form-check-input mt-0" type="radio" name="payment_method" value="cod" onchange="togglePaymentUI()">
                    <div>
                      <div class="fw-bold text-dark">Thanh toán khi nhận hàng (COD)</div>
                      <div class="text-muted small">Thanh toán tiền mặt cho Shipper khi xe được giao tới</div>
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

<style>
.c-radio-label:has(input:checked) {
  border-color: #FF5722 !important;
  background-color: rgba(255,87,34,0.03);
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

    // Display
    document.getElementById('lblFee').innerText = (fee > 0 ? fee.toLocaleString('vi-VN') : '0') + ' đ';
    document.getElementById('lblTotal').innerText = total.toLocaleString('vi-VN') + ' đ';

    // Update QR Code
    updateQRCode(total);
}

function updateQRCode(totalAmount) {
    // VietQR Syntax: NGANHANG-STK
    // Dữ liệu giả lập Ngân Hàng MB (BIN 970422) và STK 0123456789 để Test.
    const bankId = 'MB'; // MBBank
    const accNo = '0123456789'; // Thay bằng STK của hệ thống bạn
    const accountName = encodeURIComponent('CYCLE TRUST');
    const orderInfo = encodeURIComponent('CycleTrust Order ' + <?= $bikeId ?>);
    
    const qrUrl = `https://img.vietqr.io/image/${bankId}-${accNo}-compact.jpg?amount=${totalAmount}&addInfo=${orderInfo}&accountName=${accountName}`;
    document.getElementById('qrImage').src = qrUrl;
}

function togglePaymentUI() {
    const payMethod = document.querySelector('input[name="payment_method"]:checked').value;
    const qrBox = document.getElementById('vietqrBox');
    
    if (payMethod === 'vietqr') {
        qrBox.style.display = 'block';
    } else {
        qrBox.style.display = 'none';
    }
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
                Swal.fire({
                    icon: 'success',
                    title: 'Đặt hàng thành công!',
                    text: 'Mã Đơn: ' + result.order_code,
                    confirmButtonText: 'Xem Đơn Hàng',
                    confirmButtonColor: '#FF5722'
                }).then(() => {
                    window.location.href = '<?= BASE_URL ?>?page=user/orders';
                });
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
