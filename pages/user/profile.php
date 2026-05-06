<?php
/**
 * pages/user/profile.php
 * -------------------------------------------------------
 * Trang Hồ sơ cá nhân – Giao diện 2 cột (Bootstrap 5, phong cách Cam-Đen)
 *
 * Cột trái : Card thông tin tóm tắt (ảnh đại diện, tên, badge, thống kê)
 * Cột phải : Tab panel (Tab 1: Thông tin cá nhân | Tab 2: Đổi mật khẩu)
 * -------------------------------------------------------
 */
declare(strict_types=1);

// ════════════════════════════════════════════════════
// 1. BẢO MẬT: Yêu cầu đăng nhập
// ════════════════════════════════════════════════════
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

/** @var PDO $conn */
$conn = require __DIR__ . '/../../config/db.php';

$userId = (int)$_SESSION['user_id'];

// ════════════════════════════════════════════════════
// 2. LẤY DỮ LIỆU: Thông tin người dùng từ CSDL
// ════════════════════════════════════════════════════
$stmtUser = $conn->prepare('SELECT * FROM users WHERE id = :id');
$stmtUser->execute([':id' => $userId]);
$user = $stmtUser->fetch();

if (!$user) {
    // Phòng trường hợp session còn nhưng user đã bị xóa
    session_destroy();
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

// ════════════════════════════════════════════════════
// 3. THỐNG KÊ: Số xe đang bán & đã giao dịch thành công
// ════════════════════════════════════════════════════

// Số xe đang bán (status = 'active')
$stmtSelling = $conn->prepare(
    "SELECT COUNT(*) AS total FROM bikes WHERE user_id = :uid AND status = 'active'"
);
$stmtSelling->execute([':uid' => $userId]);
$countSelling = (int)($stmtSelling->fetchColumn() ?: 0);

// Số xe đã giao dịch thành công (status = 'sold')
$stmtSold = $conn->prepare(
    "SELECT COUNT(*) AS total FROM bikes WHERE user_id = :uid AND status = 'sold'"
);
$stmtSold->execute([':uid' => $userId]);
$countSold = (int)($stmtSold->fetchColumn() ?: 0);

// Tổng số tin đã đăng (để xác định "Thành viên uy tín")
$stmtTotal = $conn->prepare('SELECT COUNT(*) FROM bikes WHERE user_id = :uid');
$stmtTotal->execute([':uid' => $userId]);
$totalPosts = (int)($stmtTotal->fetchColumn() ?: 0);

// ════════════════════════════════════════════════════
// 4. XỬ LÝ FORM SUBMIT (dispatch đến modules)
// ════════════════════════════════════════════════════
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = (string)($_POST['_action'] ?? '');

    if ($action === 'update_profile') {
        // Dispatch sang profile_act.php (xử lý lưu hồ sơ + upload avatar)
        require __DIR__ . '/../../modules/user/profile_act.php';
        exit; // profile_act.php luôn redirect, dòng này chỉ để an toàn
    }

    if ($action === 'change_password') {
        // Dispatch sang pass_act.php (xử lý đổi mật khẩu)
        require __DIR__ . '/../../modules/user/pass_act.php';
        exit;
    }
}

// ════════════════════════════════════════════════════
// 5. CHUẨN BỊ BIẾN HIỂN THỊ (htmlspecialchars để chống XSS)
// ════════════════════════════════════════════════════
$displayName = htmlspecialchars((string)($user['username'] ?? ''), ENT_QUOTES, 'UTF-8');
$displayEmail= htmlspecialchars((string)($user['email']    ?? ''), ENT_QUOTES, 'UTF-8');
$displayPhone= htmlspecialchars((string)($user['phone']    ?? ''), ENT_QUOTES, 'UTF-8');
$displayAddr = htmlspecialchars((string)($user['address']  ?? ''), ENT_QUOTES, 'UTF-8');
$displayBio  = htmlspecialchars((string)($user['bio']      ?? ''), ENT_QUOTES, 'UTF-8');

// Đường dẫn ảnh đại diện (dùng ảnh mặc định nếu chưa có)
$avatarRaw   = trim((string)($user['avatar'] ?? ''));
if ($avatarRaw !== '') {
    $avatarUrl = BASE_URL . 'public/uploads/avatars/' . htmlspecialchars($avatarRaw, ENT_QUOTES, 'UTF-8');
} else {
    // Ảnh mặc định dùng UI Avatars API – generate từ tên người dùng
    $avatarUrl = 'https://ui-avatars.com/api/?name=' . urlencode((string)($user['username'] ?? 'U'))
               . '&background=FF5722&color=fff&size=200&bold=true&format=svg';
}

// Xác định tab nào cần active (dùng khi redirect về sau lỗi đổi mật khẩu)
$activeTab = ($_GET['tab'] ?? 'profile') === 'password' ? 'password' : 'profile';

// Hiển thị thông báo lỗi dạng inline (nếu có) để highlight đúng tab
$inlineError = '';
if (!empty($_SESSION['error'])) {
    $inlineError = htmlspecialchars((string)$_SESSION['error'], ENT_QUOTES, 'UTF-8');
    unset($_SESSION['error']);
}

$inlineSuccess = '';
if (!empty($_SESSION['success'])) {
    $inlineSuccess = htmlspecialchars((string)$_SESSION['success'], ENT_QUOTES, 'UTF-8');
    unset($_SESSION['success']);
}
?>

<!-- ═══════════════════════════════════════════════════════════
     GỌI BỞI: index.php (header đã được include trước đó)
     ═══════════════════════════════════════════════════════════ -->

<style>
/* ── Profile page – scoped styles ──────────────────────────── */

/* Banner gradient trên cùng card */
.ct-profile-banner {
  height: 120px;
  background: linear-gradient(135deg, #ff5722 0%, #ff8a50 50%, #212121 100%);
  border-radius: var(--radius) var(--radius) 0 0;
}

/* Avatar bo tròn, viền trắng nổi bật */
.ct-profile-avatar {
  width: 110px;
  height: 110px;
  border-radius: 999px;
  border: 4px solid #fff;
  object-fit: cover;
  margin-top: -55px;        /* kéo lên đè lên banner */
  box-shadow: 0 8px 24px rgba(0,0,0,0.15);
  background: #f0f0f0;
}

/* Badge "Thành viên uy tín" – màu cam */
.ct-badge-trusted {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  background: linear-gradient(135deg, #ff5722, #ff8a50);
  color: #fff;
  font-size: 0.72rem;
  font-weight: 700;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  padding: 4px 10px;
  border-radius: 999px;
}

/* Stat item trong card trái */
.ct-stat-item {
  flex: 1;
  text-align: center;
  padding: 12px 8px;
}

.ct-stat-item:not(:last-child) {
  border-right: 1px solid var(--border);
}

.ct-stat-value {
  font-size: 1.5rem;
  font-weight: 900;
  color: var(--primary);
  line-height: 1.1;
}

.ct-stat-label {
  font-size: 0.72rem;
  font-weight: 600;
  color: var(--muted);
  text-transform: uppercase;
  letter-spacing: 0.08em;
  margin-top: 3px;
}

/* Tab styling */
.ct-profile-tabs .nav-link {
  color: var(--muted);
  font-weight: 600;
  border-radius: 0;
  border-bottom: 2px solid transparent;
  padding: 12px 20px;
  transition: color var(--dur) var(--ease), border-color var(--dur) var(--ease);
}

.ct-profile-tabs .nav-link:hover {
  color: var(--ink);
  border-bottom-color: rgba(33,33,33,0.18);
}

.ct-profile-tabs .nav-link.active {
  color: var(--primary);
  border-bottom-color: var(--primary);
  background: transparent;
}

/* Avatar upload preview */
.ct-avatar-upload {
  position: relative;
  display: inline-block;
  cursor: pointer;
}

.ct-avatar-upload__overlay {
  position: absolute;
  inset: 0;
  border-radius: 999px;
  background: rgba(0,0,0,0.45);
  color: #fff;
  display: flex;
  align-items: center;
  justify-content: center;
  font-size: 0.78rem;
  font-weight: 700;
  opacity: 0;
  transition: opacity 200ms var(--ease);
  cursor: pointer;
  border: 4px solid #fff;
}

.ct-avatar-upload:hover .ct-avatar-upload__overlay {
  opacity: 1;
}

/* Form labels – đậm hơn */
.ct-form-label {
  font-weight: 600;
  font-size: 0.88rem;
  color: rgba(33,33,33,0.72);
  margin-bottom: 6px;
}

/* Input focus – viền cam */
.ct-form-control:focus {
  border-color: rgba(255, 87, 34, 0.65) !important;
  box-shadow: var(--ring) !important;
}

/* Nút cập nhật màu cam */
.btn-profile-save {
  background: linear-gradient(135deg, #ff5722, #ff7043);
  border: none;
  color: #fff;
  font-weight: 700;
  padding: 12px 28px;
  border-radius: 10px;
  letter-spacing: 0.04em;
  transition: transform var(--dur) var(--ease), box-shadow var(--dur) var(--ease);
  box-shadow: 0 10px 30px rgba(255,87,34,0.30);
}

.btn-profile-save:hover {
  transform: translateY(-1px);
  box-shadow: 0 16px 40px rgba(255,87,34,0.38);
  color: #fff;
}

/* Password strength indicator */
.ct-pw-strength {
  height: 4px;
  border-radius: 999px;
  background: #e0e0e0;
  overflow: hidden;
  margin-top: 6px;
  transition: all 300ms;
}

.ct-pw-strength__bar {
  height: 100%;
  width: 0;
  border-radius: 999px;
  transition: width 300ms var(--ease), background 300ms;
}
</style>

<section class="py-5" style="background: rgba(33,33,33,0.03); min-height: calc(100vh - 160px);">
  <div class="container">

    <!-- Tiêu đề trang -->
    <div class="mb-4">
      <h1 class="section-title h3 mb-1">Hồ sơ cá nhân</h1>
      <p class="section-subtitle mb-0">Quản lý thông tin tài khoản và bảo mật của bạn</p>
    </div>

    <!-- Alert thông báo Bootstrap 5 (inline, không dùng SweetAlert ở đây) -->
    <?php if ($inlineSuccess !== ''): ?>
      <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="fa-solid fa-circle-check"></i>
        <span><?= $inlineSuccess ?></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
      </div>
    <?php endif; ?>

    <?php if ($inlineError !== ''): ?>
      <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-4" role="alert">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= $inlineError ?></span>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Đóng"></button>
      </div>
    <?php endif; ?>

    <!-- ════ LAYOUT 2 CỘT ════════════════════════════════════ -->
    <div class="row g-4 align-items-start">

      <!-- ══ CỘT TRÁI – Card Profile ══════════════════════════ -->
      <div class="col-lg-4 col-xl-3">
        <div class="surface" style="box-shadow: var(--shadow-sm); overflow: hidden;">

          <!-- Banner gradient -->
          <div class="ct-profile-banner"></div>

          <!-- Thông tin cơ bản -->
          <div class="px-4 pb-4 text-center">

            <!-- Ảnh đại diện -->
            <div class="d-flex justify-content-center">
              <img
                id="avatarPreviewCard"
                src="<?= $avatarUrl ?>"
                alt="Ảnh đại diện của <?= $displayName ?>"
                class="ct-profile-avatar"
                onerror="this.src='https://ui-avatars.com/api/?name=U&background=FF5722&color=fff&size=200&bold=true'"
              >
            </div>

            <!-- Tên người dùng -->
            <h2 class="h5 fw-700 mt-3 mb-1"><?= $displayName ?: 'Người dùng' ?></h2>

            <!-- Email -->
            <p class="text-muted small mb-2"><?= $displayEmail ?></p>

            <!-- Badge "Thành viên uy tín" – hiện khi tổng tin đăng > 5 -->
            <?php if ($totalPosts > 5): ?>
              <span class="ct-badge-trusted">
                <i class="fa-solid fa-shield-check"></i>
                Thành viên uy tín
              </span>
            <?php else: ?>
              <span class="badge bg-secondary" style="font-size:0.72rem; padding: 4px 10px;">
                <i class="fa-solid fa-user me-1"></i>Thành viên
              </span>
            <?php endif; ?>

            <!-- Divider -->
            <hr class="my-3">

            <!-- Thống kê nhanh -->
            <div class="d-flex">
              <!-- Số xe đang bán -->
              <div class="ct-stat-item">
                <div class="ct-stat-value"><?= $countSelling ?></div>
                <div class="ct-stat-label">Đang bán</div>
              </div>
              <!-- Số xe đã giao dịch thành công -->
              <div class="ct-stat-item">
                <div class="ct-stat-value"><?= $countSold ?></div>
                <div class="ct-stat-label">Đã bán</div>
              </div>
              <!-- Tổng tin đăng -->
              <div class="ct-stat-item">
                <div class="ct-stat-value"><?= $totalPosts ?></div>
                <div class="ct-stat-label">Tổng tin</div>
              </div>
            </div>

            <!-- Nút đi đến trang tin đăng -->
            <a href="<?= BASE_URL ?>?page=my-postings" class="btn btn-outline btn-sm w-100 mt-3">
              <i class="fa-solid fa-rectangle-list"></i>
              Xem tin đăng của tôi
            </a>

          </div>
        </div><!-- /surface card trái -->
      </div><!-- /col-lg-4 -->

      <!-- ══ CỘT PHẢI – Tab Forms ══════════════════════════════ -->
      <div class="col-lg-8 col-xl-9">
        <div class="surface" style="box-shadow: var(--shadow-sm);">

          <!-- Tab navigation -->
          <ul class="nav ct-profile-tabs border-bottom px-4 gap-2" id="profileTabs" role="tablist">
            <li class="nav-item" role="presentation">
              <button
                class="nav-link <?= $activeTab === 'profile' ? 'active' : '' ?>"
                id="tab-info-btn"
                data-bs-toggle="tab"
                data-bs-target="#tab-info"
                type="button"
                role="tab"
                aria-controls="tab-info"
                aria-selected="<?= $activeTab === 'profile' ? 'true' : 'false' ?>"
              >
                <i class="fa-solid fa-user-pen me-2"></i>Thông tin cá nhân
              </button>
            </li>
            <li class="nav-item" role="presentation">
              <button
                class="nav-link <?= $activeTab === 'password' ? 'active' : '' ?>"
                id="tab-pw-btn"
                data-bs-toggle="tab"
                data-bs-target="#tab-pw"
                type="button"
                role="tab"
                aria-controls="tab-pw"
                aria-selected="<?= $activeTab === 'password' ? 'true' : 'false' ?>"
              >
                <i class="fa-solid fa-lock me-2"></i>Đổi mật khẩu
              </button>
            </li>
          </ul>

          <!-- Tab content -->
          <div class="tab-content p-4 p-md-5" id="profileTabsContent">

            <!-- ── TAB 1: THÔNG TIN CÁ NHÂN ──────────────────── -->
            <div
              class="tab-pane fade <?= $activeTab === 'profile' ? 'show active' : '' ?>"
              id="tab-info"
              role="tabpanel"
              aria-labelledby="tab-info-btn"
            >
              <!--
                Action: _action=update_profile → modules/user/profile_act.php
                enctype: multipart/form-data bắt buộc khi có upload file
              -->
              <form
                method="POST"
                action="<?= BASE_URL ?>?page=user/profile"
                enctype="multipart/form-data"
                id="formUpdateProfile"
                novalidate
              >
                <!-- Hidden field để dispatcher biết action nào -->
                <input type="hidden" name="_action" value="update_profile">

                <!-- ─ Upload Avatar ─────────────────────────── -->
                <div class="d-flex align-items-center gap-4 mb-4 flex-wrap">
                  <div class="ct-avatar-upload" title="Nhấn để đổi ảnh đại diện">
                    <img
                      id="avatarPreviewForm"
                      src="<?= $avatarUrl ?>"
                      alt="Avatar"
                      style="width:90px;height:90px;border-radius:999px;object-fit:cover;border:3px solid var(--primary);"
                      onerror="this.src='https://ui-avatars.com/api/?name=U&background=FF5722&color=fff&size=200&bold=true'"
                    >
                    <label class="ct-avatar-upload__overlay" for="avatarInput">
                      <span><i class="fa-solid fa-camera d-block mb-1 fs-5"></i>Đổi ảnh</span>
                    </label>
                  </div>
                  <div>
                    <div class="fw-600 mb-1">Ảnh đại diện</div>
                    <div class="text-muted small mb-2">JPG, PNG hoặc WebP – Tối đa 3 MB</div>
                    <!-- Input file ẩn, label bên trên trigger nó -->
                    <input
                      type="file"
                      name="avatar"
                      id="avatarInput"
                      accept="image/jpeg,image/png,image/webp"
                      class="d-none"
                    >
                    <label for="avatarInput" class="btn btn-outline btn-sm" style="cursor:pointer;">
                      <i class="fa-solid fa-upload me-1"></i>Chọn ảnh
                    </label>
                  </div>
                </div>

                <hr class="mb-4">

                <!-- ─ Các trường thông tin ──────────────────── -->
                <div class="row g-3">

                  <!-- Họ và tên -->
                  <div class="col-md-6">
                    <label for="full_name" class="ct-form-label">
                      <i class="fa-solid fa-user me-1 text-primary"></i>Họ và tên
                    </label>
                    <input
                      type="text"
                      class="form-control ct-form-control"
                      id="full_name"
                      name="full_name"
                      value="<?= $displayName ?>"
                      placeholder="Nhập họ và tên của bạn"
                      maxlength="50"
                    >
                  </div>

                  <!-- Email (readonly – không cho thay đổi) -->
                  <div class="col-md-6">
                    <label for="email_display" class="ct-form-label">
                      <i class="fa-solid fa-envelope me-1 text-primary"></i>Email
                      <span class="text-muted" style="font-weight:400;"> (không thể thay đổi)</span>
                    </label>
                    <input
                      type="email"
                      class="form-control"
                      id="email_display"
                      value="<?= $displayEmail ?>"
                      readonly
                      style="background:rgba(0,0,0,0.03);cursor:not-allowed;"
                    >
                    <div class="form-text">Email dùng để đăng nhập, liên hệ admin để thay đổi.</div>
                  </div>

                  <!-- Số điện thoại -->
                  <div class="col-md-6">
                    <label for="phone" class="ct-form-label">
                      <i class="fa-solid fa-phone me-1 text-primary"></i>Số điện thoại
                    </label>
                    <input
                      type="tel"
                      class="form-control ct-form-control"
                      id="phone"
                      name="phone"
                      value="<?= $displayPhone ?>"
                      placeholder="VD: 0901234567"
                      maxlength="15"
                    >
                  </div>

                  <!-- Địa chỉ -->
                  <div class="col-md-6">
                    <label for="address" class="ct-form-label">
                      <i class="fa-solid fa-location-dot me-1 text-primary"></i>Địa chỉ
                    </label>
                    <input
                      type="text"
                      class="form-control ct-form-control"
                      id="address"
                      name="address"
                      value="<?= $displayAddr ?>"
                      placeholder="VD: Quận 1, TP.HCM"
                      maxlength="255"
                    >
                  </div>

                  <!-- Giới thiệu bản thân (Bio) -->
                  <div class="col-12">
                    <label for="bio" class="ct-form-label">
                      <i class="fa-solid fa-pen-nib me-1 text-primary"></i>Giới thiệu ngắn (Bio)
                    </label>
                    <textarea
                      class="form-control ct-form-control"
                      id="bio"
                      name="bio"
                      rows="3"
                      maxlength="500"
                      placeholder="Mô tả ngắn về bạn: sở thích xe đạp, khu vực giao dịch..."
                    ><?= $displayBio ?></textarea>
                    <div class="form-text text-end">
                      <span id="bioCharCount"><?= mb_strlen($displayBio) ?></span>/500 ký tự
                    </div>
                  </div>

                </div><!-- /row -->

                <!-- Nút submit -->
                <div class="d-flex justify-content-end mt-4">
                  <button type="submit" class="btn btn-profile-save" id="btnSaveProfile">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Cập nhật hồ sơ
                  </button>
                </div>

              </form>
            </div><!-- /tab-pane thông tin cá nhân -->

            <!-- ── TAB 2: ĐỔI MẬT KHẨU ───────────────────────── -->
            <div
              class="tab-pane fade <?= $activeTab === 'password' ? 'show active' : '' ?>"
              id="tab-pw"
              role="tabpanel"
              aria-labelledby="tab-pw-btn"
            >
              <!--
                Action: _action=change_password → modules/user/pass_act.php
              -->
              <form
                method="POST"
                action="<?= BASE_URL ?>?page=user/profile&tab=password"
                id="formChangePassword"
                novalidate
              >
                <input type="hidden" name="_action" value="change_password">

                <p class="text-muted mb-4">
                  <i class="fa-solid fa-shield-halved me-2 text-primary"></i>
                  Hãy tạo mật khẩu mạnh với ít nhất <strong>6 ký tự</strong>,
                  kết hợp chữ hoa, chữ thường và số để bảo vệ tài khoản tốt hơn.
                </p>

                <div class="row g-3">

                  <!-- Mật khẩu cũ -->
                  <div class="col-12">
                    <label for="old_password" class="ct-form-label">
                      <i class="fa-solid fa-lock me-1 text-primary"></i>Mật khẩu hiện tại
                    </label>
                    <div class="input-group">
                      <input
                        type="password"
                        class="form-control ct-form-control"
                        id="old_password"
                        name="old_password"
                        placeholder="Nhập mật khẩu hiện tại"
                        required
                        autocomplete="current-password"
                      >
                      <!-- Nút show/hide password -->
                      <button
                        class="btn btn-outline"
                        type="button"
                        onclick="togglePw('old_password', this)"
                        title="Hiện/ẩn mật khẩu"
                        style="box-shadow:none;"
                      >
                        <i class="fa-solid fa-eye"></i>
                      </button>
                    </div>
                  </div>

                  <!-- Mật khẩu mới -->
                  <div class="col-md-6">
                    <label for="new_password" class="ct-form-label">
                      <i class="fa-solid fa-key me-1 text-primary"></i>Mật khẩu mới
                    </label>
                    <div class="input-group">
                      <input
                        type="password"
                        class="form-control ct-form-control"
                        id="new_password"
                        name="new_password"
                        placeholder="Tối thiểu 6 ký tự"
                        required
                        minlength="6"
                        autocomplete="new-password"
                      >
                      <button
                        class="btn btn-outline"
                        type="button"
                        onclick="togglePw('new_password', this)"
                        title="Hiện/ẩn mật khẩu"
                        style="box-shadow:none;"
                      >
                        <i class="fa-solid fa-eye"></i>
                      </button>
                    </div>
                    <!-- Thanh chỉ độ mạnh mật khẩu -->
                    <div class="ct-pw-strength mt-2">
                      <div class="ct-pw-strength__bar" id="pwStrengthBar"></div>
                    </div>
                    <div class="form-text" id="pwStrengthText">Nhập mật khẩu để kiểm tra độ mạnh</div>
                  </div>

                  <!-- Xác nhận mật khẩu mới -->
                  <div class="col-md-6">
                    <label for="confirm_password" class="ct-form-label">
                      <i class="fa-solid fa-check-double me-1 text-primary"></i>Xác nhận mật khẩu mới
                    </label>
                    <div class="input-group">
                      <input
                        type="password"
                        class="form-control ct-form-control"
                        id="confirm_password"
                        name="confirm_password"
                        placeholder="Nhập lại mật khẩu mới"
                        required
                        autocomplete="new-password"
                      >
                      <button
                        class="btn btn-outline"
                        type="button"
                        onclick="togglePw('confirm_password', this)"
                        title="Hiện/ẩn mật khẩu"
                        style="box-shadow:none;"
                      >
                        <i class="fa-solid fa-eye"></i>
                      </button>
                    </div>
                    <!-- Thông báo khớp/không khớp real-time -->
                    <div class="form-text" id="pwMatchText"></div>
                  </div>

                </div><!-- /row -->

                <!-- Nút submit -->
                <div class="d-flex justify-content-end mt-4">
                  <button type="submit" class="btn btn-profile-save" id="btnChangePw">
                    <i class="fa-solid fa-shield-halved me-2"></i>Đổi mật khẩu
                  </button>
                </div>

              </form>
            </div><!-- /tab-pane đổi mật khẩu -->

          </div><!-- /tab-content -->
        </div><!-- /surface card phải -->
      </div><!-- /col-lg-8 -->

    </div><!-- /row -->
  </div><!-- /container -->
</section>

<!-- ════════════════════════════════════════════════════
     JAVASCRIPT: Preview avatar, đếm ký tự bio,
     độ mạnh mật khẩu, show/hide password
     ════════════════════════════════════════════════════ -->
<script>
/**
 * Preview ảnh đại diện ngay khi người dùng chọn file
 * (không cần submit form mới thấy ảnh mới)
 */
document.getElementById('avatarInput').addEventListener('change', function () {
  const file = this.files[0];
  if (!file) return;

  // Kiểm tra loại file phía client (lớp bảo vệ đầu tiên)
  const allowed = ['image/jpeg', 'image/png', 'image/webp'];
  if (!allowed.includes(file.type)) {
    alert('Chỉ chấp nhận ảnh JPG, PNG hoặc WebP!');
    this.value = '';
    return;
  }

  // Tạo URL tạm thời để preview
  const reader = new FileReader();
  reader.onload = function (e) {
    // Cập nhật cả ảnh trong card trái lẫn form
    document.getElementById('avatarPreviewCard').src = e.target.result;
    document.getElementById('avatarPreviewForm').src = e.target.result;
  };
  reader.readAsDataURL(file);
});

/**
 * Đếm ký tự bio real-time
 */
document.getElementById('bio').addEventListener('input', function () {
  document.getElementById('bioCharCount').textContent = this.value.length;
});

/**
 * Show / Hide password – toggle giữa password và text
 * @param {string} inputId - ID của input cần toggle
 * @param {HTMLButtonElement} btn - Nút bấm để thay icon
 */
function togglePw(inputId, btn) {
  const input = document.getElementById(inputId);
  const icon  = btn.querySelector('i');
  if (input.type === 'password') {
    input.type = 'text';
    icon.classList.replace('fa-eye', 'fa-eye-slash');
  } else {
    input.type = 'password';
    icon.classList.replace('fa-eye-slash', 'fa-eye');
  }
}

/**
 * Kiểm tra độ mạnh mật khẩu mới real-time
 * Trả về điểm 0-4 dựa trên độ dài và loại ký tự
 */
document.getElementById('new_password').addEventListener('input', function () {
  const val  = this.value;
  const bar  = document.getElementById('pwStrengthBar');
  const text = document.getElementById('pwStrengthText');

  let score = 0;
  if (val.length >= 6)  score++;
  if (val.length >= 10) score++;
  if (/[A-Z]/.test(val) && /[a-z]/.test(val)) score++;
  if (/[0-9]/.test(val) || /[^A-Za-z0-9]/.test(val)) score++;

  const levels = [
    { width: '0%',   color: '#e0e0e0', label: 'Nhập mật khẩu để kiểm tra độ mạnh' },
    { width: '25%',  color: '#f44336', label: 'Rất yếu' },
    { width: '50%',  color: '#ff9800', label: 'Yếu' },
    { width: '75%',  color: '#4caf50', label: 'Khá mạnh' },
    { width: '100%', color: '#2196f3', label: 'Rất mạnh 💪' },
  ];

  const level = val === '' ? levels[0] : levels[score];
  bar.style.width    = level.width;
  bar.style.background = level.color;
  text.textContent   = level.label;
  text.style.color   = level.color === '#e0e0e0' ? '' : level.color;
});

/**
 * Kiểm tra xác nhận mật khẩu mới có khớp không – real-time
 */
document.getElementById('confirm_password').addEventListener('input', function () {
  const newPw  = document.getElementById('new_password').value;
  const matchEl= document.getElementById('pwMatchText');

  if (this.value === '') {
    matchEl.textContent = '';
    matchEl.style.color = '';
    return;
  }

  if (this.value === newPw) {
    matchEl.textContent = '✓ Mật khẩu khớp';
    matchEl.style.color = '#4caf50';
  } else {
    matchEl.textContent = '✗ Mật khẩu không khớp';
    matchEl.style.color = '#f44336';
  }
});

/**
 * Client-side validation trước khi submit form đổi mật khẩu
 */
document.getElementById('formChangePassword').addEventListener('submit', function (e) {
  const newPw  = document.getElementById('new_password').value;
  const confPw = document.getElementById('confirm_password').value;

  if (newPw.length < 6) {
    e.preventDefault();
    alert('Mật khẩu mới phải có ít nhất 6 ký tự!');
    return;
  }

  if (newPw !== confPw) {
    e.preventDefault();
    alert('Mật khẩu mới và xác nhận mật khẩu không khớp!');
  }
});
</script>
