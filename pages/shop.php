<?php
declare(strict_types=1);

/** @var PDO $conn */
$conn = require __DIR__ . '/../config/db.php';

// ── 1. Đọc tất cả tham số GET ────────────────────────────────────────────────
$limit       = 9;
$currentPage = isset($_GET['p'])           ? max(1, (int)$_GET['p'])            : 1;
$categoryId  = isset($_GET['category_id']) ? (int)$_GET['category_id']          : 0;
$priceRange  = isset($_GET['price_range']) ? trim((string)$_GET['price_range'])  : '';
$brandFilter = isset($_GET['brand'])       ? trim((string)$_GET['brand'])        : '';
$size        = isset($_GET['size']) && trim((string)$_GET['size']) !== '' ? trim((string)$_GET['size']) : '';
$keyword     = isset($_GET['q'])           ? trim((string)$_GET['q'])            : '';

// ── 2. Sidebar data ───────────────────────────────────────────────────────────
$categories = [];
try {
    $s = $conn->query('SELECT id, name FROM categories ORDER BY id ASC');
    $categories = $s ? $s->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    $categories = [];
}

$brands = [];
try {
    $s = $conn->query(
        "SELECT DISTINCT brand FROM bikes
         WHERE brand IS NOT NULL AND brand != '' AND status = 'available'
         ORDER BY brand ASC"
    );
    $brands = $s ? array_column($s->fetchAll(PDO::FETCH_ASSOC), 'brand') : [];
} catch (PDOException $e) {
    $brands = [];
}

// ── 3. Xây dựng Dynamic SQL (WHERE 1=1 pattern) ──────────────────────────────
//
// Base query: JOIN categories lấy cat_name, chỉ xe available có ảnh.
// $filterSql: chuỗi AND clauses ghép thêm tùy filter người dùng chọn.
// $bindMap:   map placeholder => [value, PDO type] — bindValue từng cái riêng.
//
// QUAN TRỌNG: PDO (EMULATE_PREPARES=false) KHÔNG cho trùng named placeholder
// trong cùng 1 query. LIKE title và description phải là :q_title / :q_desc.

$baseSql = "SELECT b.*, c.name AS cat_name
            FROM bikes b
            INNER JOIN (
                SELECT title, MAX(id) AS max_id
                FROM bikes
                WHERE status = 'available'
                  AND image_url IS NOT NULL
                  AND image_url != ''
                GROUP BY title
            ) latest ON b.id = latest.max_id
            LEFT JOIN categories c ON b.category_id = c.id
            WHERE 1=1";

$filterSql = '';
$bindMap   = [];   // [':placeholder' => ['value' => mixed, 'type' => PDO::PARAM_*]]

// -- Từ khóa: title LIKE :q_title OR description LIKE :q_desc ----------------
if ($keyword !== '') {
    $filterSql .= ' AND (b.title LIKE :q_title OR b.description LIKE :q_desc)';
    $kw = '%' . $keyword . '%';
    $bindMap[':q_title'] = ['value' => $kw, 'type' => PDO::PARAM_STR];
    $bindMap[':q_desc']  = ['value' => $kw, 'type' => PDO::PARAM_STR];
}

// -- Danh mục ----------------------------------------------------------------
if ($categoryId > 0) {
    $filterSql .= ' AND b.category_id = :category_id';
    $bindMap[':category_id'] = ['value' => $categoryId, 'type' => PDO::PARAM_INT];
}

// -- Hãng xe -----------------------------------------------------------------
if ($brandFilter !== '') {
    $filterSql .= ' AND b.brand = :brand';
    $bindMap[':brand'] = ['value' => $brandFilter, 'type' => PDO::PARAM_STR];
}

// -- Kích cỡ -----------------------------------------------------------------
if ($size !== '') {
    $filterSql .= ' AND b.size = :size';
    $bindMap[':size'] = ['value' => $size, 'type' => PDO::PARAM_STR];
}

// -- Khoảng giá: dùng literal số (không phải user input) — an toàn SQL -------
// Hỗ trợ cả 2 format: 'under5'/'5to10'/'10to20'/'over20' và '<5'/'5-10'/'>20'
switch ($priceRange) {
    case 'under5': case '<5':
        $filterSql .= ' AND b.price < 5000000';
        break;
    case '5to10': case '5-10':
        $filterSql .= ' AND b.price BETWEEN 5000000 AND 10000000';
        break;
    case '10to20': case '10-20':
        $filterSql .= ' AND b.price BETWEEN 10000000 AND 20000000';
        break;
    case 'over20': case '>20':
        $filterSql .= ' AND b.price > 20000000';
        break;
}

// ── 4. COUNT tổng kết quả (phân trang) ───────────────────────────────────────
$bikes        = [];
$totalRecords = 0;
$totalPages   = 1;

try {
    $countSql = "SELECT COUNT(*)
                 FROM bikes b
                 INNER JOIN (
                     SELECT title, MAX(id) AS max_id
                     FROM bikes
                     WHERE status = 'available'
                       AND image_url IS NOT NULL
                       AND image_url != ''
                     GROUP BY title
                 ) latest ON b.id = latest.max_id
                 LEFT JOIN categories c ON b.category_id = c.id
                 WHERE 1=1" . $filterSql;

    $countStmt = $conn->prepare($countSql);
    foreach ($bindMap as $ph => $meta) {
        $countStmt->bindValue($ph, $meta['value'], $meta['type']);
    }
    $countStmt->execute();
    $totalRecords = (int)$countStmt->fetchColumn();
    $totalPages   = max(1, (int)ceil($totalRecords / $limit));

    if ($currentPage > $totalPages) {
        $currentPage = $totalPages;
    }
    $offset = ($currentPage - 1) * $limit;

    // ── 5. Lấy danh sách xe trang hiện tại ───────────────────────────────────
    $listSql  = $baseSql . $filterSql . ' ORDER BY b.id DESC LIMIT :lim OFFSET :off';
    $listStmt = $conn->prepare($listSql);

    foreach ($bindMap as $ph => $meta) {
        $listStmt->bindValue($ph, $meta['value'], $meta['type']);
    }
    $listStmt->bindValue(':lim', $limit,  PDO::PARAM_INT);
    $listStmt->bindValue(':off', $offset, PDO::PARAM_INT);
    $listStmt->execute();

    $bikes = $listStmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

} catch (PDOException $e) {
    $bikes        = [];
    $totalRecords = 0;
    $totalPages   = 1;
    $currentPage  = 1;
}

$defaultBikeImage = BASE_URL . 'public/assets/images/default-bike.jpg';

// Hàm tạo URL phân trang — giữ nguyên tất cả filter params hiện tại
function shopUrl(array $extra = []): string
{
    $base  = ['page' => 'shop'];
    $carry = ['category_id', 'price_range', 'brand', 'size', 'q'];
    foreach ($carry as $k) {
        if (isset($_GET[$k]) && trim((string)$_GET[$k]) !== '') {
            $base[$k] = $_GET[$k];
        }
    }
    return BASE_URL . '?' . http_build_query(array_merge($base, $extra));
}

$categoryTitle = 'Tất cả xe';
foreach ($categories as $cat) {
    if ((int)$cat['id'] === $categoryId) {
        $categoryTitle = htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8');
        break;
    }
}

$priceLabels = [
    'under5' => 'Dưới 5 triệu',
    '5to10'  => '5 – 10 triệu',
    '10to20' => '10 – 20 triệu',
    'over20' => 'Trên 20 triệu',
];
$sizes = ['S', 'M', 'L', 'XL'];
?>
<style>
/* ── Sidebar ───────────────────────────────── */
.sf-sidebar {
    background: #fff;
    border: 1px solid rgba(33,33,33,.1);
    border-radius: 16px;
    overflow: hidden;
}
.sf-sidebar__head {
    background: #212121;
    color: #fff;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    gap: 10px;
    font-weight: 700;
    font-size: .95rem;
    letter-spacing: .01em;
}
.sf-sidebar__head i { color: #ff5722; font-size: 1rem; }
.sf-section { padding: 16px 20px; border-bottom: 1px solid rgba(33,33,33,.08); }
.sf-section:last-of-type { border-bottom: none; }
.sf-section__label {
    font-size: .72rem;
    font-weight: 800;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: #9ca3af;
    margin-bottom: 12px;
}
/* Radio pills */
.sf-pill { display: none; }
.sf-pill + label {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    padding: 6px 14px;
    border: 1.5px solid rgba(33,33,33,.15);
    border-radius: 99px;
    font-size: .82rem;
    font-weight: 600;
    cursor: pointer;
    margin: 0 4px 6px 0;
    transition: border-color .18s, background .18s, color .18s;
    user-select: none;
    color: #374151;
}
.sf-pill + label:hover { border-color: #ff5722; color: #ff5722; }
.sf-pill:checked + label { background: #ff5722; border-color: #ff5722; color: #fff; }
/* Input search */
.sf-input {
    border: 1.5px solid rgba(33,33,33,.15);
    border-radius: 10px;
    padding: 9px 14px;
    font-size: .88rem;
    width: 100%;
    outline: none;
    transition: border-color .2s;
}
.sf-input:focus { border-color: #ff5722; }
/* Buttons */
.sf-btn-submit {
    display: block;
    width: calc(100% - 40px);
    margin: 0 20px 12px;
    padding: 12px;
    background: #ff5722;
    color: #fff;
    border: none;
    border-radius: 10px;
    font-weight: 800;
    font-size: .95rem;
    cursor: pointer;
    letter-spacing: .02em;
    transition: background .2s, transform .15s;
}
.sf-btn-submit:hover { background: #e64a19; transform: translateY(-1px); }
.sf-btn-reset {
    display: block;
    width: calc(100% - 40px);
    margin: 0 20px 20px;
    padding: 10px;
    background: transparent;
    color: #6b7280;
    border: 1.5px solid rgba(33,33,33,.15);
    border-radius: 10px;
    font-size: .85rem;
    font-weight: 600;
    cursor: pointer;
    text-align: center;
    text-decoration: none;
    transition: border-color .2s, color .2s;
}
.sf-btn-reset:hover { border-color: #ff5722; color: #ff5722; }
/* Active filter badges */
.sf-active-badge {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 3px 10px 3px 12px;
    background: rgba(255,87,34,.1);
    border: 1px solid rgba(255,87,34,.3);
    border-radius: 99px;
    font-size: .78rem;
    font-weight: 700;
    color: #ff5722;
}
.sf-result-count { font-size: .82rem; color: #6b7280; }
/* Bike cards */
.shop-bike-card {
    background: #fff;
    border: 1px solid rgba(33,33,33,.09);
    border-radius: 14px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 100%;
    transition: box-shadow .25s, transform .2s;
}
.shop-bike-card:hover { box-shadow: 0 8px 28px rgba(0,0,0,.11); transform: translateY(-3px); }
.shop-bike-card__img-wrap {
    display: block;
    aspect-ratio: 4/3;
    overflow: hidden;
    background-color: #f8f9fa;
    padding: 10px;
}
.shop-bike-card__img { width: 100%; height: 100%; object-fit: contain; display: block; }
.shop-bike-card__body { padding: 16px; flex: 1; display: flex; flex-direction: column; }
.shop-bike-card__title {
    font-size: .95rem;
    font-weight: 700;
    line-height: 1.35;
    margin-bottom: 6px;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
.shop-bike-card__price { color: #ff5722; font-weight: 800; font-size: 1.05rem; }
.shop-bike-card__cta {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 6px;
    margin-top: auto;
    padding-top: 12px;
    border-top: 1px solid rgba(33,33,33,.07);
    font-size: .85rem;
    font-weight: 700;
    color: #212121;
    text-decoration: none;
    transition: color .2s;
}
.shop-bike-card__cta:hover { color: #ff5722; }
/* Search result banner */
.sf-search-banner {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 12px 18px;
    background: rgba(255,87,34,.06);
    border: 1px solid rgba(255,87,34,.2);
    border-radius: 12px;
    margin-bottom: 20px;
    font-size: .9rem;
}
/* Empty state */
.sf-empty {
    background: #fff;
    border: 1px solid rgba(33,33,33,.09);
    border-radius: 16px;
    padding: 60px 20px;
    text-align: center;
}
</style>

<section class="py-4 py-lg-5" style="background:rgba(33,33,33,.03); min-height:80vh;">
  <div class="container">

    <div class="d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2 mb-4">
      <div>
        <h1 class="h3 fw-bold mb-1">Cửa hàng xe đạp</h1>
        <div class="sf-result-count">
          <?php
          $isFiltered = $keyword !== '' || $priceRange !== '' || $brandFilter !== '' || $size !== '' || $categoryId > 0;
          if ($isFiltered): ?>
            <span class="me-1">Đang lọc:</span>
            <?php if ($keyword !== ''): ?>
              <span class="sf-active-badge me-1"><i class="fa-solid fa-magnifying-glass"></i><?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
            <?php if ($categoryId > 0): ?>
              <span class="sf-active-badge me-1"><i class="fa-solid fa-layer-group"></i><?= $categoryTitle ?></span>
            <?php endif; ?>
            <?php if ($priceRange !== '' && isset($priceLabels[$priceRange])): ?>
              <span class="sf-active-badge me-1"><i class="fa-solid fa-tag"></i><?= htmlspecialchars($priceLabels[$priceRange], ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
            <?php if ($brandFilter !== ''): ?>
              <span class="sf-active-badge me-1"><i class="fa-solid fa-bicycle"></i><?= htmlspecialchars($brandFilter, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
            <?php if ($size !== ''): ?>
              <span class="sf-active-badge me-1">Size <?= htmlspecialchars($size, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
          <?php else: ?>
            Tất cả xe đạp
          <?php endif; ?>
          — <strong><?= $totalRecords ?></strong> kết quả
        </div>
      </div>
      <a class="btn btn-ghost btn-sm" href="<?= BASE_URL ?>?page=home">
        <i class="fa-solid fa-arrow-left me-1"></i>Về trang chủ
      </a>
    </div>

    <div class="row g-4 align-items-start">

      <!-- ── SIDEBAR LỌC ── -->
      <div class="col-12 col-lg-3">
        <form method="GET" action="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>" id="shopFilterForm">
          <input type="hidden" name="page" value="shop">

          <div class="sf-sidebar">
            <div class="sf-sidebar__head">
              <i class="fa-solid fa-sliders"></i>
              Bộ lọc tìm kiếm
            </div>

            <!-- Từ khóa -->
            <div class="sf-section">
              <div class="sf-section__label">Từ khóa</div>
              <input
                type="search"
                name="q"
                class="sf-input"
                placeholder="Tên xe, mô tả..."
                value="<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>"
                autocomplete="off"
              >
            </div>

            <!-- Danh mục -->
            <div class="sf-section">
              <div class="sf-section__label">Danh mục</div>
              <div>
                <input type="radio" class="sf-pill" name="category_id" id="cat_all" value="" <?= $categoryId === 0 ? 'checked' : '' ?>>
                <label for="cat_all">Tất cả</label>
                <?php foreach ($categories as $cat): ?>
                  <input
                    type="radio"
                    class="sf-pill"
                    name="category_id"
                    id="cat_<?= (int)$cat['id'] ?>"
                    value="<?= (int)$cat['id'] ?>"
                    <?= $categoryId === (int)$cat['id'] ? 'checked' : '' ?>
                  >
                  <label for="cat_<?= (int)$cat['id'] ?>"><?= htmlspecialchars($cat['name'], ENT_QUOTES, 'UTF-8') ?></label>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Khoảng giá -->
            <div class="sf-section">
              <div class="sf-section__label">Khoảng giá</div>
              <div>
                <input type="radio" class="sf-pill" name="price_range" id="pr_all" value="" <?= $priceRange === '' ? 'checked' : '' ?>>
                <label for="pr_all">Tất cả</label>
                <?php foreach ($priceLabels as $val => $label): ?>
                  <input type="radio" class="sf-pill" name="price_range" id="pr_<?= $val ?>" value="<?= $val ?>" <?= $priceRange === $val ? 'checked' : '' ?>>
                  <label for="pr_<?= $val ?>"><?= htmlspecialchars($label, ENT_QUOTES, 'UTF-8') ?></label>
                <?php endforeach; ?>
              </div>
            </div>

            <!-- Hãng xe -->
            <?php if (!empty($brands)): ?>
              <div class="sf-section">
                <div class="sf-section__label">Hãng xe</div>
                <div>
                  <input type="radio" class="sf-pill" name="brand" id="br_all" value="" <?= $brandFilter === '' ? 'checked' : '' ?>>
                  <label for="br_all">Tất cả</label>
                  <?php foreach ($brands as $b):
                    $bid = 'br_' . preg_replace('/[^a-z0-9]/i', '_', $b);
                  ?>
                    <input type="radio" class="sf-pill" name="brand" id="<?= $bid ?>" value="<?= htmlspecialchars($b, ENT_QUOTES, 'UTF-8') ?>" <?= $brandFilter === $b ? 'checked' : '' ?>>
                    <label for="<?= $bid ?>"><?= htmlspecialchars($b, ENT_QUOTES, 'UTF-8') ?></label>
                  <?php endforeach; ?>
                </div>
              </div>
            <?php endif; ?>

            <!-- Size khung -->
            <div class="sf-section">
              <div class="sf-section__label">Kích cỡ khung (Size)</div>
              <div>
                <input type="radio" class="sf-pill" name="size" id="sz_all" value="" <?= $size === '' ? 'checked' : '' ?>>
                <label for="sz_all">Tất cả</label>
                <?php foreach ($sizes as $sz): ?>
                  <input type="radio" class="sf-pill" name="size" id="sz_<?= $sz ?>" value="<?= $sz ?>" <?= $size === $sz ? 'checked' : '' ?>>
                  <label for="sz_<?= $sz ?>"><?= $sz ?></label>
                <?php endforeach; ?>
              </div>
            </div>

          </div>

          <button type="submit" class="sf-btn-submit mt-3">
            <i class="fa-solid fa-filter me-2"></i>Lọc ngay
          </button>
          <a href="index.php?page=shop" class="sf-btn-reset">
            <i class="fa-solid fa-rotate-left me-1"></i>Xóa bộ lọc
          </a>
        </form>
      </div>

      <!-- ── KẾT QUẢ ── -->
      <div class="col-12 col-lg-9">

        <?php if ($keyword !== ''): ?>
          <div class="sf-search-banner">
            <i class="fa-solid fa-magnifying-glass" style="color:#ff5722; flex-shrink:0;"></i>
            <span>
              Tìm thấy <strong style="color:#ff5722;"><?= $totalRecords ?></strong> kết quả cho:
              <strong style="color:#ff5722;">&ldquo;<?= htmlspecialchars($keyword, ENT_QUOTES, 'UTF-8') ?>&rdquo;</strong>
            </span>
          </div>
        <?php endif; ?>

        <?php if (!empty($bikes)): ?>
          <div class="row g-4">
            <?php foreach ($bikes as $row):
              $rTitle     = htmlspecialchars((string)($row['title'] ?? ''), ENT_QUOTES, 'UTF-8');
              $rBrand     = htmlspecialchars(trim((string)($row['brand'] ?? '')), ENT_QUOTES, 'UTF-8');
              $rLoc       = htmlspecialchars(trim((string)($row['location'] ?? '')), ENT_QUOTES, 'UTF-8');
              $rCond      = htmlspecialchars(trim((string)($row['condition_status'] ?? '')), ENT_QUOTES, 'UTF-8');
              $rCondLower = mb_strtolower($rCond, 'UTF-8');
              $rIsNew     = str_contains($rCondLower, 'mới') || str_contains($rCondLower, 'new');
              $rCondStyle = $rIsNew ? 'background-color: #d1fae5; color: #065f46;' : 'background-color: #f1f2f6; color: #1e272e;';
              $rCatName   = htmlspecialchars(trim((string)($row['cat_name'] ?? '')), ENT_QUOTES, 'UTF-8');
              $rPrice     = isset($row['price']) ? (float)$row['price'] : 0.0;
              $rId        = (int)($row['id'] ?? 0);
              $detailUrl  = htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') . '?page=bike-detail&id=' . $rId;
            ?>
              <div class="col-12 col-sm-6 col-xl-4">
                <article class="shop-bike-card">
                  <a href="<?= $detailUrl ?>" tabindex="-1" aria-hidden="true" class="shop-bike-card__img-wrap">
                    <?php if (!empty($row['image_url'])): ?>
                      <img
                        class="shop-bike-card__img"
                        src="<?= BASE_URL ?>public/uploads/bikes/<?= rawurlencode($row['image_url']) ?>"
                        alt="<?= $rTitle ?>"
                        loading="lazy"
                        onerror="this.onerror=null;this.src='<?= BASE_URL ?>public/assets/images/categories/road-bike.jpg';"
                      >
                    <?php else: ?>
                      <img
                        class="shop-bike-card__img"
                        src="<?= BASE_URL ?>public/assets/images/categories/road-bike.jpg"
                        alt="<?= $rTitle ?>"
                        loading="lazy"
                      >
                    <?php endif; ?>
                  </a>
                  <div class="shop-bike-card__body">
                    <div class="shop-bike-card__title"><?= $rTitle ?></div>
                    <div class="d-flex flex-wrap gap-1 mb-2">
                      <?php if ($rBrand !== ''): ?>
                        <span class="badge fw-bold" style="background-color:#f1f2f6; color:#1e272e; font-size:.72rem;">
                          <i class="fa-solid fa-tag me-1"></i><?= $rBrand ?>
                        </span>
                      <?php endif; ?>
                      <?php if ($rCatName !== ''): ?>
                        <span class="badge fw-bold" style="background-color:#f1f2f6; color:#1e272e; font-size:.72rem;"><?= $rCatName ?></span>
                      <?php endif; ?>
                      <?php if ($rCond !== ''): ?>
                        <span class="badge fw-bold" style="<?= $rCondStyle ?> font-size:.72rem;"><?= $rCond ?></span>
                      <?php endif; ?>
                    </div>
                    <div class="shop-bike-card__price mb-2"><?= number_format($rPrice, 0, ',', '.') ?>đ</div>
                    <?php if ($rLoc !== ''): ?>
                      <div class="text-muted small mb-2">
                        <i class="fa-solid fa-location-dot me-1" style="color:#ff5722;"></i><?= $rLoc ?>
                      </div>
                    <?php endif; ?>
                    <a href="<?= $detailUrl ?>" class="shop-bike-card__cta">
                      Xem chi tiết <i class="fa-solid fa-arrow-right"></i>
                    </a>
                  </div>
                </article>
              </div>
            <?php endforeach; ?>
          </div>

          <?php if ($totalPages > 1): ?>
            <nav aria-label="Phân trang" class="mt-5">
              <ul class="pagination justify-content-center flex-wrap mb-0">
                <li class="page-item <?= $currentPage <= 1 ? 'disabled' : '' ?>">
                  <a class="page-link rounded-pill me-1"
                     href="<?= $currentPage <= 1 ? '#' : htmlspecialchars(shopUrl(['p' => $currentPage - 1]), ENT_QUOTES, 'UTF-8') ?>"
                     <?= $currentPage <= 1 ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                    Trang trước
                  </a>
                </li>
                <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                  <li class="page-item <?= $i === $currentPage ? 'active' : '' ?>">
                    <a class="page-link" href="<?= htmlspecialchars(shopUrl(['p' => $i]), ENT_QUOTES, 'UTF-8') ?>"><?= $i ?></a>
                  </li>
                <?php endfor; ?>
                <li class="page-item <?= $currentPage >= $totalPages ? 'disabled' : '' ?>">
                  <a class="page-link rounded-pill ms-1"
                     href="<?= $currentPage >= $totalPages ? '#' : htmlspecialchars(shopUrl(['p' => $currentPage + 1]), ENT_QUOTES, 'UTF-8') ?>"
                     <?= $currentPage >= $totalPages ? 'tabindex="-1" aria-disabled="true"' : '' ?>>
                    Trang sau
                  </a>
                </li>
              </ul>
            </nav>
          <?php endif; ?>

        <?php else: ?>
          <!-- Empty state -->
          <div class="sf-empty">
            <div style="font-size:3.5rem; margin-bottom:16px;">🚲</div>
            <h2 class="h5 fw-bold mb-2">
              <?php if ($isFiltered): ?>
                <?= $size !== '' 
                    ? 'Không tìm thấy xe size ' . htmlspecialchars($size, ENT_QUOTES, 'UTF-8') 
                    : 'Rất tiếc, không có chiếc xe nào phù hợp với bộ lọc của bạn.' ?>
              <?php else: ?>
                Chưa có tin đăng nào.
              <?php endif; ?>
            </h2>
            <p class="text-muted mb-4">
              <?= $isFiltered
                ? 'Hãy thử điều chỉnh bộ lọc hoặc xóa để xem toàn bộ xe.'
                : 'Bạn hãy là người đầu tiên đăng xe đạp của mình.' ?>
            </p>
            <?php if ($isFiltered): ?>
              <a
                href="index.php?page=shop"
                class="btn"
                style="background:#ff5722;color:#fff;border:none;border-radius:10px;padding:11px 28px;font-weight:800;"
              >
                <i class="fa-solid fa-rotate-left me-2"></i>Xóa bộ lọc
              </a>
            <?php else: ?>
              <a
                href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>?page=post-bike"
                class="btn"
                style="background:#ff5722;color:#fff;border:none;border-radius:10px;padding:11px 28px;font-weight:800;"
              >
                <i class="fa-solid fa-plus me-2"></i>Đăng xe ngay
              </a>
            <?php endif; ?>
          </div>
        <?php endif; ?>

      </div>
    </div>
  </div>
</section>

<script>
// Auto-submit khi chọn radio (category, price, brand, size)
(function () {
  var form = document.getElementById('shopFilterForm');
  if (!form) return;
  form.querySelectorAll('input[type=radio]').forEach(function (r) {
    r.addEventListener('change', function () { form.submit(); });
  });
})();
</script>
