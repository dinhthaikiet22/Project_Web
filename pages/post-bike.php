<?php
declare(strict_types=1);

// if (!isset($_SESSION['user_id'])) {
//     header('Location: ' . BASE_URL . '?page=login');
//     exit;
// }
?>

<section class="py-5" style="background: var(--bg);">
  <div class="container" style="max-width: 800px;">
    
    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-end justify-content-between gap-2 mb-4">
      <div>
        <h1 class="ct-section-title">Đăng tin xe đạp</h1>
        <div class="ct-section-subtitle">Điền thông tin rõ ràng để người mua dễ đánh giá</div>
      </div>
      <a class="btn btn-ghost" href="<?= BASE_URL ?>?page=home">
        <i class="fa-solid fa-arrow-left me-1"></i>
        Về trang chủ
      </a>
    </div>

    <div class="surface p-4 p-md-5" style="box-shadow: var(--shadow-md); border-radius: var(--radius-lg);">
      
      <?php if (!empty($_SESSION['error'])): ?>
        <div class="alert alert-danger mb-4" role="alert" style="background: rgba(239, 68, 68, 0.1); border: 1px solid var(--danger); color: var(--danger); padding: 12px; border-radius: var(--radius-sm);">
          <?= htmlspecialchars((string)$_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['error']); ?>
      <?php endif; ?>

      <?php if (!empty($_SESSION['success'])): ?>
        <div class="alert alert-success mb-4" role="alert" style="background: rgba(16, 185, 129, 0.1); border: 1px solid var(--success); color: var(--success); padding: 12px; border-radius: var(--radius-sm);">
          <?= htmlspecialchars((string)$_SESSION['success'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['success']); ?>
      <?php endif; ?>

      <form method="POST" action="<?= BASE_URL ?>modules/bike/save_bike.php" enctype="multipart/form-data">
        
        <h3 class="fw-700 mb-4 border-bottom pb-2">1. Thông tin cơ bản</h3>

        <div class="ct-form-group">
          <label for="bike_name" class="ct-form-label required">Tên xe đạp</label>
          <input id="bike_name" name="title" type="text" class="ct-input" placeholder="vd: Giant TCR Advanced 2020" required>
        </div>

        <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
          <div class="ct-form-group">
            <label for="brand" class="ct-form-label">Hãng xe</label>
            <input id="brand" name="brand" type="text" class="ct-input" placeholder="vd: Giant / Trek">
          </div>

          <div class="ct-form-group">
            <label for="category" class="ct-form-label">Danh mục</label>
            <select id="category" name="category_id" class="ct-select">
              <option value="1">Road Bike</option>
              <option value="2">Mountain Bike</option>
              <option value="3">City Bike</option>
            </select>
          </div>
        </div>

        <div class="row" style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
          <div class="ct-form-group">
            <label for="price" class="ct-form-label">Giá bán (VNĐ)</label>
            <input id="price" name="price" type="number" class="ct-input" placeholder="vd: 15000000" min="0" step="1000">
          </div>

          <div class="ct-form-group">
            <label for="condition" class="ct-form-label">Tình trạng</label>
            <select id="condition" name="condition_status" class="ct-select">
              <option value="Mới">Mới</option>
              <option value="Đã sử dụng" selected>Đã sử dụng</option>
            </select>
          </div>
        </div>

        <div class="ct-form-group">
          <label for="location" class="ct-form-label">Khu vực xem xe</label>
          <input id="location" name="location" type="text" class="ct-input" placeholder="vd: Quận 1, TP.HCM">
          
          <input type="hidden" name="latitude" id="inputLat" value="10.7769">
          <input type="hidden" name="longitude" id="inputLng" value="106.7009">
        </div>

        <h3 class="fw-700 mb-4 mt-5 border-bottom pb-2">2. Hình ảnh</h3>

        <div class="ct-form-group">
          <label for="image" class="ct-form-label required">Ảnh chính (Bắt buộc)</label>
          <div class="ct-file-upload" onclick="document.getElementById('image').click()">
            <i class="fa-solid fa-image ct-file-upload__icon"></i>
            <p class="fw-600 mb-1">Click để tải ảnh đại diện lên</p>
            <span class="ct-form-hint">Đây sẽ là ảnh bìa của tin đăng.</span>
            <input id="image" name="image" type="file" style="display: none;" accept="image/*" required>
          </div>
        </div>

        <div class="ct-form-group">
          <label for="gallery" class="ct-form-label">Ảnh phụ (Không bắt buộc)</label>
          <div class="ct-file-upload" onclick="document.getElementById('gallery').click()" style="padding: 20px;">
            <i class="fa-solid fa-images ct-file-upload__icon" style="font-size: 1.5rem;"></i>
            <p class="fw-600 mb-1 fs-sm">Click để tải nhiều ảnh chi tiết</p>
            <input id="gallery" name="gallery[]" type="file" style="display: none;" accept="image/*" multiple>
          </div>
        </div>

        <h3 class="fw-700 mb-4 mt-5 border-bottom pb-2">3. Mô tả chi tiết</h3>

        <div class="ct-form-group">
          <label for="description" class="ct-form-label">Chi tiết về xe</label>
          <textarea id="description" name="description" class="ct-textarea" rows="6" placeholder="Mô tả size khung, groupset, độ mòn, phụ tùng đã thay, lỗi (nếu có)..."></textarea>
        </div>

        <div class="mt-5 pt-3 d-flex justify-content-end gap-3 border-top">
          <button type="submit" class="btn btn-primary" style="padding: 14px 24px;">
            <i class="fa-solid fa-bolt"></i>
            Đăng tin ngay
          </button>
        </div>

      </form>
    </div>
  </div>
</section>

<script>
document.getElementById('location').addEventListener('input', function(e) {
    let text = e.target.value.toLowerCase();
    let lat = 10.7769; // Default (Q1)
    let lng = 106.7009;

    // Giả lập vài tọa độ cơ bản của TP.HCM
    if (text.includes('quận 1')) { lat = 10.7769; lng = 106.7009; }
    else if (text.includes('quận 2')) { lat = 10.7871; lng = 106.7485; }
    else if (text.includes('quận 3')) { lat = 10.7824; lng = 106.6853; }
    else if (text.includes('quận 4')) { lat = 10.7617; lng = 106.7082; }
    else if (text.includes('quận 7')) { lat = 10.7325; lng = 106.7323; }
    else if (text.includes('thủ đức')) { lat = 10.8505; lng = 106.7588; }
    else if (text.includes('bình thạnh')) { lat = 10.8105; lng = 106.7091; }

    document.getElementById('inputLat').value = lat;
    document.getElementById('inputLng').value = lng;
});
</script>