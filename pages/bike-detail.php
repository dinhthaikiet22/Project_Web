<?php
declare(strict_types=1);

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
if ($id <= 0) {
    header('Location: ' . BASE_URL . '?page=404');
    exit;
}

/** @var PDO $conn */
$conn = require __DIR__ . '/../config/db.php';

function resolveBikeImg(string $raw): string
{
    $raw = trim($raw);
    if ($raw === '') {
        return BASE_URL . 'public/assets/images/default-bike.jpg';
    }
    if (str_starts_with(strtolower($raw), 'http')) {
        return $raw;
    }
    return BASE_URL . 'public/uploads/bikes/' . rawurlencode($raw);
}

try {
    $stmt = $conn->prepare(
        'SELECT b.*, c.name AS category_name,
                u.username AS seller_name, u.phone AS seller_phone
         FROM bikes b
         LEFT JOIN categories c ON b.category_id = c.id
         INNER JOIN users u ON b.user_id = u.id
         WHERE b.id = ?
         LIMIT 1'
    );
    $stmt->execute([$id]);
    $bike = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    header('Location: ' . BASE_URL . '?page=404');
    exit;
}

if ($bike === false || $bike === null) {
    header('Location: ' . BASE_URL . '?page=404');
    exit;
}

$galleryImages = [];
try {
    $imgStmt = $conn->prepare(
        'SELECT image_url FROM bike_images WHERE bike_id = ? ORDER BY id ASC'
    );
    $imgStmt->execute([$id]);
    foreach ($imgStmt->fetchAll(PDO::FETCH_ASSOC) as $imgRow) {
        $p = trim((string)($imgRow['image_url'] ?? ''));
        if ($p !== '') {
            $galleryImages[] = resolveBikeImg($p);
        }
    }
} catch (PDOException $e) {
    $galleryImages = [];
}

if (empty($galleryImages)) {
    $galleryImages[] = resolveBikeImg((string)($bike['image_url'] ?? ''));
}
$mainImg = $galleryImages[0];

$title        = htmlspecialchars((string)($bike['title'] ?? ''), ENT_QUOTES, 'UTF-8');
$brand        = trim((string)($bike['brand'] ?? ''));
$condition    = trim((string)($bike['condition_status'] ?? ''));
$location     = trim((string)($bike['location'] ?? ''));
$description  = (string)($bike['description'] ?? '');
$categoryName = trim((string)($bike['category_name'] ?? ''));
$categoryId   = (int)($bike['category_id'] ?? 0);
$sellerName   = htmlspecialchars(trim((string)($bike['seller_name'] ?? 'N/A')), ENT_QUOTES, 'UTF-8');
$sellerPhone  = trim((string)($bike['seller_phone'] ?? ''));
$price        = isset($bike['price']) ? (float)$bike['price'] : 0.0;
$createdAt    = (string)($bike['created_at'] ?? '');
$postedLabel  = $createdAt !== '' ? date('d/m/Y', strtotime($createdAt)) : '—';

$condLower = mb_strtolower($condition, 'UTF-8');
$isNew     = str_contains($condLower, 'mới') || str_contains($condLower, 'new');
$condBadge = $condition === '' ? 'bg-secondary' : ($isNew ? 'bg-success' : 'bg-warning text-dark');
$condText  = $condition !== '' ? $condition : 'Chưa cập nhật';

$na = 'Chưa xác định';
$specFrameMaterial = trim((string)($bike['frame_material'] ?? ''));
$specGroupset      = trim((string)($bike['groupset'] ?? ''));
$specWheelSize     = trim((string)($bike['wheel_size'] ?? ''));
$specWeight        = trim((string)($bike['weight'] ?? ''));
$specBikeSize      = trim((string)($bike['bike_size'] ?? ''));

$similarBikes = [];
if ($categoryId > 0) {
    try {
        $simStmt = $conn->prepare(
            "SELECT id, title, brand, price, condition_status, image_url, location
             FROM bikes
             WHERE category_id = ? AND id != ? AND status = 'available'
             ORDER BY created_at DESC
             LIMIT 4"
        );
        $simStmt->execute([$categoryId, $id]);
        $similarBikes = $simStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    } catch (PDOException $e) {
        $similarBikes = [];
    }
}
?>
<style>
.bd-main-img {
    width: 100%;
    aspect-ratio: 4/3;
    object-fit: cover;
    border-radius: 14px;
    box-shadow: 0 4px 24px rgba(0,0,0,.12);
    display: block;
}
.bd-price {
    color: #ff5722;
    font-size: clamp(1.8rem, 3vw, 2.3rem);
    font-weight: 800;
    letter-spacing: -.02em;
    line-height: 1.1;
}
.bd-sticky-card {
    position: sticky;
    top: 20px;
    background: #fff;
    border-radius: 16px;
    border: 1px solid rgba(33,33,33,.1);
    box-shadow: 0 6px 28px rgba(0,0,0,.09);
    padding: 28px;
}
.bd-seller-box {
    background: rgba(255,87,34,.06);
    border: 1px solid rgba(255,87,34,.2);
    border-radius: 12px;
    padding: 16px;
}
.bd-btn-call {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 10px;
    background: #212121;
    color: #ff5722 !important;
    border: none;
    border-radius: 12px;
    font-weight: 800;
    font-size: 1.05rem;
    padding: 14px 20px;
    text-decoration: none;
    transition: background .2s, transform .15s;
    width: 100%;
}
.bd-btn-call:hover { background: #000; transform: translateY(-2px); }
.bd-thumb {
    width: 80px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid rgba(33,33,33,.14);
    cursor: pointer;
    transition: border-color .2s, box-shadow .2s;
}
.bd-thumb.active {
    border-color: #ff5722;
    box-shadow: 0 0 0 3px rgba(255,87,34,.22);
}
.bd-sim-card {
    display: flex;
    flex-direction: column;
    height: 100%;
    border-radius: 14px;
    overflow: hidden;
    background: #fff;
    border: 1px solid rgba(33,33,33,.1);
    text-decoration: none;
    color: inherit;
    transition: box-shadow .25s, transform .2s;
}
.bd-sim-card:hover {
    box-shadow: 0 8px 28px rgba(0,0,0,.13);
    transform: translateY(-3px);
}
.bd-sim-card img {
    width: 100%;
    height: 170px;
    object-fit: cover;
    display: block;
}
.bd-sim-card__body { padding: 14px 16px 18px; flex: 1; display: flex; flex-direction: column; }
.bd-sim-card__title {
    font-size: .9rem;
    font-weight: 700;
    line-height: 1.4;
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.bd-sim-card__price { color: #ff5722; font-weight: 800; font-size: 1rem; margin-top: auto; padding-top: 8px; }
/* ── Spec Grid ───────────────────────── */
.spec-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 12px;
}
@media (max-width: 575px) { .spec-grid { grid-template-columns: 1fr; } }
.spec-item {
    display: flex;
    align-items: flex-start;
    gap: 13px;
    padding: 14px 16px;
    border: 1px solid rgba(33,33,33,.1);
    border-radius: 8px;
    background: #fafafa;
    transition: box-shadow .22s ease, border-color .22s ease;
    min-width: 0;
}
.spec-item:hover {
    box-shadow: 0 4px 16px rgba(0,0,0,.09);
    border-color: rgba(255,87,34,.3);
}
.spec-item__icon {
    flex-shrink: 0;
    width: 36px;
    height: 36px;
    background: rgba(255,87,34,.1);
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #ff5722;
}
.spec-item__icon svg { width: 18px; height: 18px; fill: currentColor; }
.spec-item__body { min-width: 0; }
.spec-item__label {
    font-size: .72rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .07em;
    color: #9ca3af;
    margin-bottom: 3px;
    white-space: nowrap;
}
.spec-item__value {
    font-size: .93rem;
    font-weight: 700;
    color: #111;
    line-height: 1.3;
    word-break: break-word;
}
.spec-item__value.is-na { color: #d1d5db; font-weight: 500; font-style: italic; }
</style>

<section class="py-4 py-lg-5">
  <div class="container">

    <nav aria-label="breadcrumb" class="mb-4">
      <ol class="breadcrumb mb-0">
        <li class="breadcrumb-item">
          <a href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>" class="text-muted">Trang chủ</a>
        </li>
        <li class="breadcrumb-item">
          <a href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>?page=shop" class="text-muted">Cửa hàng</a>
        </li>
        <li class="breadcrumb-item active text-truncate" aria-current="page" style="max-width:260px;">
          <?= $title ?>
        </li>
      </ol>
    </nav>

    <div class="row g-4 g-lg-5 align-items-start">

      <div class="col-12 col-lg-8">

        <img
          id="bdMainImg"
          class="bd-main-img mb-3"
          src="<?= htmlspecialchars($mainImg, ENT_QUOTES, 'UTF-8') ?>"
          alt="<?= $title ?>"
          onerror="this.onerror=null;this.src='<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>public/assets/images/default-bike.jpg';"
        >

        <?php if (count($galleryImages) > 1): ?>
          <div class="d-flex gap-2 flex-wrap mb-4" id="bdThumbRow">
            <?php foreach ($galleryImages as $i => $thumb): ?>
              <img
                class="bd-thumb<?= $i === 0 ? ' active' : '' ?>"
                src="<?= htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8') ?>"
                alt="Ảnh <?= $i + 1 ?>"
                data-src="<?= htmlspecialchars($thumb, ENT_QUOTES, 'UTF-8') ?>"
                loading="lazy"
                onerror="this.onerror=null;this.src='<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>public/assets/images/default-bike.jpg';"
              >
            <?php endforeach; ?>
          </div>
        <?php endif; ?>

        <div class="bg-white rounded-3 border p-4 p-md-5 mb-4">
          <div class="d-flex align-items-center gap-2 mb-4">
            <h2 class="h5 fw-bold mb-0">Thông số kỹ thuật</h2>
            <span class="badge rounded-pill ms-1" style="background:rgba(255,87,34,.12);color:#ff5722;font-size:.72rem;">MINH BẠCH 100%</span>
          </div>
          <div class="spec-grid">

            <?php
            $specItems = [
                [
                    'label' => 'Thương hiệu',
                    'value' => $brand,
                    'icon'  => '<svg viewBox="0 0 16 16"><path d="M8 0a8 8 0 1 0 0 16A8 8 0 0 0 8 0zm0 1.5a6.5 6.5 0 1 1 0 13 6.5 6.5 0 0 1 0-13zM5 6.5a3 3 0 1 1 6 0 3 3 0 0 1-6 0zm3-1.5a1.5 1.5 0 1 0 0 3 1.5 1.5 0 0 0 0-3z"/></svg>',
                ],
                [
                    'label' => 'Dòng xe',
                    'value' => $categoryName,
                    'icon'  => '<svg viewBox="0 0 16 16"><path d="M3.5 3a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1h-9zm0 3a.5.5 0 0 0 0 1h9a.5.5 0 0 0 0-1h-9zm0 3a.5.5 0 0 0 0 1h5a.5.5 0 0 0 0-1h-5z"/></svg>',
                ],
                [
                    'label' => 'Khung sườn (Frame)',
                    'value' => $specFrameMaterial,
                    'icon'  => '<svg viewBox="0 0 16 16"><path d="M13 3.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zM3.5 5A1.5 1.5 0 1 1 3.5 2a1.5 1.5 0 0 1 0 3zm9 5.5a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zm-9 0a1.5 1.5 0 1 1-3 0 1.5 1.5 0 0 1 3 0zM3.5 6.5l3 3m0 0l3-3m-3 3V2.5"/></svg>',
                ],
                [
                    'label' => 'Bộ truyền động (Groupset)',
                    'value' => $specGroupset,
                    'icon'  => '<svg viewBox="0 0 16 16"><path d="M8 4.754a3.246 3.246 0 1 0 0 6.492 3.246 3.246 0 0 0 0-6.492zM5.754 8a2.246 2.246 0 1 1 4.492 0 2.246 2.246 0 0 1-4.492 0z"/><path d="M9.796 1.343c-.527-1.79-3.065-1.79-3.592 0l-.094.319a.873.873 0 0 1-1.255.52l-.292-.16c-1.64-.892-3.433.902-2.54 2.541l.159.292a.873.873 0 0 1-.52 1.255l-.319.094c-1.79.527-1.79 3.065 0 3.592l.319.094a.873.873 0 0 1 .52 1.255l-.16.292c-.892 1.64.901 3.434 2.541 2.54l.292-.159a.873.873 0 0 1 1.255.52l.094.319c.527 1.79 3.065 1.79 3.592 0l.094-.319a.873.873 0 0 1 1.255-.52l.292.16c1.64.893 3.434-.902 2.54-2.541l-.159-.292a.873.873 0 0 1 .52-1.255l.319-.094c1.79-.527 1.79-3.065 0-3.592l-.319-.094a.873.873 0 0 1-.52-1.255l.16-.292c.893-1.64-.902-3.433-2.541-2.54l-.292.159a.873.873 0 0 1-1.255-.52l-.094-.319zm-2.633.283c.246-.835 1.428-.835 1.674 0l.094.319a1.873 1.873 0 0 0 2.693 1.115l.291-.16c.764-.415 1.6.42 1.184 1.185l-.159.292a1.873 1.873 0 0 0 1.116 2.692l.318.094c.835.246.835 1.428 0 1.674l-.319.094a1.873 1.873 0 0 0-1.115 2.693l.16.291c.415.764-.42 1.6-1.185 1.184l-.291-.159a1.873 1.873 0 0 0-2.693 1.116l-.094.318c-.246.835-1.428.835-1.674 0l-.094-.319a1.873 1.873 0 0 0-2.692-1.115l-.292.16c-.764.415-1.6-.42-1.184-1.185l.159-.291A1.873 1.873 0 0 0 1.945 8.93l-.319-.094c-.835-.246-.835-1.428 0-1.674l.319-.094A1.873 1.873 0 0 0 3.06 4.375l-.16-.292c-.415-.764.42-1.6 1.185-1.184l.292.159a1.873 1.873 0 0 0 2.692-1.115l.094-.319z"/></svg>',
                ],
                [
                    'label' => 'Kích thước bánh (Wheel)',
                    'value' => $specWheelSize,
                    'icon'  => '<svg viewBox="0 0 16 16"><circle cx="8" cy="8" r="6.5" stroke="currentColor" stroke-width="1.3" fill="none"/><circle cx="8" cy="8" r="1.5"/><line x1="8" y1="1.5" x2="8" y2="5" stroke="currentColor" stroke-width="1.2"/><line x1="8" y1="11" x2="8" y2="14.5" stroke="currentColor" stroke-width="1.2"/><line x1="1.5" y1="8" x2="5" y2="8" stroke="currentColor" stroke-width="1.2"/><line x1="11" y1="8" x2="14.5" y2="8" stroke="currentColor" stroke-width="1.2"/></svg>',
                ],
                [
                    'label' => 'Trọng lượng (Weight)',
                    'value' => $specWeight,
                    'icon'  => '<svg viewBox="0 0 16 16"><path d="M8 1a2 2 0 0 1 2 2h3.5a.5.5 0 0 1 .5.5v10a.5.5 0 0 1-.5.5h-11a.5.5 0 0 1-.5-.5v-10a.5.5 0 0 1 .5-.5H6a2 2 0 0 1 2-2zm0 1a1 1 0 1 0 0 2 1 1 0 0 0 0-2zM3 4v9h10V4H3zm2 2h6v1H5V6zm0 2h4v1H5V8z"/></svg>',
                ],
                [
                    'label' => 'Kích cỡ khung (Size)',
                    'value' => $specBikeSize,
                    'icon'  => '<svg viewBox="0 0 16 16"><path d="M1 1v14h14V1H1zm1 1h12v12H2V2zm1 1v2h2V3H3zm8 0v2h2V3h-2zM3 11v2h2v-2H3zm8 0v2h2v-2h-2z"/></svg>',
                ],
                [
                    'label' => 'Tình trạng',
                    'value' => $condition,
                    'icon'  => '<svg viewBox="0 0 16 16"><path d="M2.5 8a5.5 5.5 0 0 1 8.25-4.764.5.5 0 0 0 .5-.866A6.5 6.5 0 1 0 14.5 8a.5.5 0 0 0-1 0 5.5 5.5 0 1 1-11 0z"/><path d="M15.354 3.354a.5.5 0 0 0-.708-.708L8 9.293 5.354 6.646a.5.5 0 1 0-.708.708l3 3a.5.5 0 0 0 .708 0l7-7z"/></svg>',
                ],
                [
                    'label' => 'Khu vực giao dịch',
                    'value' => $location,
                    'icon'  => '<svg viewBox="0 0 16 16"><path d="M8 16s6-5.686 6-10A6 6 0 0 0 2 6c0 4.314 6 10 6 10zm0-7a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>',
                ],
            ];
            foreach ($specItems as $spec):
                $val = $spec['value'];
                $isEmpty = ($val === '');
                $displayVal = $isEmpty ? $na : htmlspecialchars($val, ENT_QUOTES, 'UTF-8');
            ?>
              <div class="spec-item">
                <div class="spec-item__icon"><?= $spec['icon'] ?></div>
                <div class="spec-item__body">
                  <div class="spec-item__label"><?= htmlspecialchars($spec['label'], ENT_QUOTES, 'UTF-8') ?></div>
                  <div class="spec-item__value<?= $isEmpty ? ' is-na' : '' ?>"><?= $displayVal ?></div>
                </div>
              </div>
            <?php endforeach; ?>

          </div>
        </div>

        <div class="bg-white rounded-3 border p-4 p-md-5">
          <h2 class="h5 fw-bold mb-3">Mô tả chi tiết</h2>
          <div style="font-size:1.02rem;line-height:1.85;color:#374151;">
            <?= nl2br(htmlspecialchars($description !== '' ? $description : 'Người bán chưa bổ sung mô tả.', ENT_QUOTES, 'UTF-8')) ?>
          </div>
        </div>

      </div>

      <div class="col-12 col-lg-4">
        <div class="bd-sticky-card">

          <h1 class="h3 fw-bold mb-2" style="line-height:1.25;letter-spacing:-.02em;">
            <?= $title ?>
          </h1>

          <div class="bd-price mb-3">
            <?= htmlspecialchars(formatCurrency($price), ENT_QUOTES, 'UTF-8') ?>
          </div>

          <div class="d-flex flex-wrap gap-2 mb-4">
            <span class="badge <?= htmlspecialchars($condBadge, ENT_QUOTES, 'UTF-8') ?> rounded-pill px-3 py-2">
              <?= htmlspecialchars($condText, ENT_QUOTES, 'UTF-8') ?>
            </span>
            <?php if ($brand !== ''): ?>
              <span class="badge bg-dark rounded-pill px-3 py-2">
                <?= htmlspecialchars($brand, ENT_QUOTES, 'UTF-8') ?>
              </span>
            <?php endif; ?>
            <?php if ($categoryName !== ''): ?>
              <span class="badge bg-light text-dark rounded-pill px-3 py-2">
                <?= htmlspecialchars($categoryName, ENT_QUOTES, 'UTF-8') ?>
              </span>
            <?php endif; ?>
          </div>

          <?php if ($location !== ''): ?>
            <div class="d-flex align-items-center gap-2 text-muted mb-4 small">
              <i class="fa-solid fa-location-dot"></i>
              <span><?= htmlspecialchars($location, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
          <?php endif; ?>

          <div class="bd-seller-box mb-4">
            <div class="d-flex align-items-center gap-3 mb-3">
              <span
                class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                style="width:46px;height:46px;background:rgba(255,87,34,.14);color:#ff5722;"
              >
                <i class="fa-solid fa-user"></i>
              </span>
              <div>
                <div class="text-muted small">Người bán</div>
                <div class="fw-bold"><?= $sellerName ?></div>
              </div>
            </div>

            <?php if ($sellerPhone !== ''): ?>
              <a
                href="tel:<?= htmlspecialchars($sellerPhone, ENT_QUOTES, 'UTF-8') ?>"
                class="bd-btn-call"
              >
                <i class="fa-solid fa-phone-volume"></i>
                GỌI NGAY: <?= htmlspecialchars($sellerPhone, ENT_QUOTES, 'UTF-8') ?>
              </a>
            <?php else: ?>
              <button type="button" class="bd-btn-call" disabled style="opacity:.5;cursor:not-allowed;">
                <i class="fa-solid fa-phone-slash"></i>
                Chưa có số liên hệ
              </button>
            <?php endif; ?>
          </div>

          <button type="button" class="btn w-100 py-3 rounded-3" style="border:1.5px solid rgba(33,33,33,.2);font-weight:600;transition:border-color .2s,color .2s;" onmouseover="this.style.borderColor='#ff5722';this.style.color='#ff5722';" onmouseout="this.style.borderColor='rgba(33,33,33,.2)';this.style.color='';">
            <i class="fa-regular fa-heart me-2"></i>
            Thêm vào yêu thích
          </button>

        </div>
      </div>
    </div>

    <?php if (!empty($similarBikes)): ?>
      <div class="mt-5 pt-3">
        <div class="d-flex align-items-center justify-content-between mb-4">
          <h2 class="h4 fw-bold mb-0">Xe tương tự</h2>
          <a
            href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>?page=shop&category_id=<?= $categoryId ?>"
            class="btn btn-ghost btn-sm"
          >
            Xem tất cả <i class="fa-solid fa-arrow-right ms-1"></i>
          </a>
        </div>
        <div class="row g-4">
          <?php foreach ($similarBikes as $sim):
            $simId    = (int)($sim['id'] ?? 0);
            $simTitle = htmlspecialchars((string)($sim['title'] ?? ''), ENT_QUOTES, 'UTF-8');
            $simBrand = trim((string)($sim['brand'] ?? ''));
            $simCond  = trim((string)($sim['condition_status'] ?? ''));
            $simLoc   = trim((string)($sim['location'] ?? ''));
            $simPrice = isset($sim['price']) ? (float)$sim['price'] : 0.0;
            $simImg   = resolveBikeImg(trim((string)($sim['image_url'] ?? '')));
          ?>
            <div class="col-6 col-md-4 col-lg-3">
              <a
                href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>?page=bike-detail&id=<?= $simId ?>"
                class="bd-sim-card"
                title="<?= $simTitle ?>"
              >
                <img
                  src="<?= htmlspecialchars($simImg, ENT_QUOTES, 'UTF-8') ?>"
                  alt="<?= $simTitle ?>"
                  loading="lazy"
                  onerror="this.onerror=null;this.src='<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>public/assets/images/default-bike.jpg';"
                >
                <div class="bd-sim-card__body">
                  <div class="bd-sim-card__title"><?= $simTitle ?></div>
                  <?php if ($simBrand !== ''): ?>
                    <div class="text-muted small mb-1">
                      <i class="fa-solid fa-tag me-1"></i><?= htmlspecialchars($simBrand, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                  <?php endif; ?>
                  <?php if ($simCond !== ''): ?>
                    <div class="mb-1">
                      <span class="badge bg-light text-dark"><?= htmlspecialchars($simCond, ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                  <?php endif; ?>
                  <?php if ($simLoc !== ''): ?>
                    <div class="text-muted small">
                      <i class="fa-solid fa-location-dot me-1"></i><?= htmlspecialchars($simLoc, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                  <?php endif; ?>
                  <div class="bd-sim-card__price"><?= htmlspecialchars(formatCurrency($simPrice), ENT_QUOTES, 'UTF-8') ?></div>
                </div>
              </a>
            </div>
          <?php endforeach; ?>
        </div>
      </div>
    <?php endif; ?>

  </div>
</section>

<script>
(function () {
  var main = document.getElementById('bdMainImg');
  var row  = document.getElementById('bdThumbRow');
  if (!main || !row) return;
  row.querySelectorAll('.bd-thumb').forEach(function (img) {
    img.addEventListener('click', function () {
      main.src = img.getAttribute('data-src');
      row.querySelectorAll('.bd-thumb').forEach(function (t) { t.classList.remove('active'); });
      img.classList.add('active');
    });
  });
})();
</script>
