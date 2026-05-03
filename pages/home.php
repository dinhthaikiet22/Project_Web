<?php
declare(strict_types=1);

/** @var PDO $conn */
$conn = require __DIR__ . '/../config/db.php';

// Xe mới đăng: 8 tin mới nhất đang bán
$featuredBikes = [];
try {
    $stmt = $conn->query(
        "SELECT * FROM bikes WHERE status = 'available' ORDER BY created_at DESC LIMIT 8"
    );
    $featuredBikes = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
} catch (PDOException $e) {
    $featuredBikes = [];
}

$defaultBikeImage = BASE_URL . 'public/assets/images/default-bike.jpg';
?>

<style>
.hero-banner {
    position: relative;
    overflow: hidden;
    height: 100vh;
}
.hero-video {
    position: absolute;
    width: 100%;
    height: 100vh;
    object-fit: cover;
    z-index: -1;
    top: 0;
    left: 0;
}
.hero-overlay {
    position: absolute;
    width: 100%;
    height: 100vh;
    /* Gradient tối dồn về bên trái để text nổi bật, bên phải trong suốt để thấy rõ xe/người trong video */
    background: linear-gradient(90deg, rgba(0, 0, 0, 0.9) 0%, rgba(0, 0, 0, 0.5) 45%, rgba(0, 0, 0, 0) 100%);
    z-index: 0;
    top: 0;
    left: 0;
}
.hero-content-box {
    max-width: 650px;
    padding-bottom: 5vh; /* Nâng nhẹ nội dung lên trên */
}
@media (max-width: 768px) {
    .hero-overlay {
        background: linear-gradient(180deg, rgba(0, 0, 0, 0.7) 0%, rgba(0, 0, 0, 0.5) 60%, rgba(0, 0, 0, 0.1) 100%);
    }
    .hero-content-box {
        max-width: 100%;
    }
}
</style>
<section class="hero-banner">
  <video autoplay muted loop playsinline poster="<?= BASE_URL ?>public/assets/images/hero-fallback.jpg" class="hero-video" style="display: block !important; visibility: visible !important;">
    <source src="public/assets/videos/hero-bg.mp4" type="video/mp4">
  </video>
  <div class="hero-overlay"></div>
  <div class="hero-banner__content">
    <div class="hero-banner__inner">
      <div class="hero-content-box">
        <div class="hero-banner__kicker">
          <i class="fa-solid fa-shield-halved"></i>
          Minh bạch thông số • Giao dịch an tâm
        </div>
        <h1 class="hero-banner__title" style="font-size: clamp(2.4rem, 5.5vw, 5rem);">
          KHÁM PHÁ XE ĐẠP<br>
          <span class="ct-accent">CHÍNH HÃNG</span>
        </h1>
        <div class="hero-banner__actions">
          <a class="btn ct-btn-hero-dark" href="<?= BASE_URL ?>?page=shop">
            Khám phá ngay
            <i class="fa-solid fa-arrow-right ms-2"></i>
          </a>
        </div>
      </div>
    </div>
  </div>
</section>

<section class="ct-cat-nav">
  <div class="container-fluid px-4 px-lg-5">
    <div class="ct-cat-nav__row">
      <a class="ct-cat-nav__item" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>?page=shop&category_id=1">
        <span class="ct-cat-nav__icon" aria-hidden="true">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none">
            <path d="M4 15c4-6 12-6 16 0" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            <path d="M6 15l2 5m10-5-2 5" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            <circle cx="7" cy="15" r="2" stroke="currentColor" stroke-width="1.4"/>
            <circle cx="17" cy="15" r="2" stroke="currentColor" stroke-width="1.4"/>
          </svg>
        </span>
        <span class="ct-cat-nav__label">Road</span>
      </a>
      <a class="ct-cat-nav__item" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>?page=shop&category_id=2">
        <span class="ct-cat-nav__icon" aria-hidden="true">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none">
            <path d="M5 14c3-4 11-4 14 0" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            <path d="M7 14l-1 6m12-6 1 6" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            <circle cx="7" cy="14" r="2.2" stroke="currentColor" stroke-width="1.4"/>
            <circle cx="17" cy="14" r="2.2" stroke="currentColor" stroke-width="1.4"/>
          </svg>
        </span>
        <span class="ct-cat-nav__label">MTB</span>
      </a>
      <a class="ct-cat-nav__item" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>?page=shop&category_id=3">
        <span class="ct-cat-nav__icon" aria-hidden="true">
          <svg width="30" height="30" viewBox="0 0 24 24" fill="none">
            <path d="M4.5 15.5c4-5 11-5 15 0" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>
            <path d="M9 12h3l3 3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" stroke-linejoin="round"/>
            <circle cx="7" cy="15.5" r="2" stroke="currentColor" stroke-width="1.4"/>
            <circle cx="17" cy="15.5" r="2" stroke="currentColor" stroke-width="1.4"/>
          </svg>
        </span>
        <span class="ct-cat-nav__label">City</span>
      </a>
    </div>
  </div>
</section>

<section class="ct-section-pad ct-shop-by-category">
  <div class="container">
    <div class="ct-section-head mb-4 mb-md-5">
      <div>
        <h2 class="ct-section-title">Khám phá theo dòng xe</h2>
      </div>
    </div>

    <div class="row g-4">
      <div class="col-12 col-md-4">
        <a class="ct-cat-card" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>?page=shop&category_id=2">
          <div class="ct-cat-card__media">
            <img
              src="<?= BASE_URL ?>public/assets/images/categories/mountain-bike.jpg"
              alt="Xe đạp địa hình"
              loading="lazy"
              style="object-fit: cover; width: 100%; height: 100%;"
            >
            <div class="ct-cat-card__overlay" aria-hidden="true" style="background: rgba(0, 0, 0, 0.4);"></div>
          </div>
          <div class="ct-cat-card__body">
            <h3 class="ct-cat-card__title">Xe địa hình</h3>
          </div>
        </a>
      </div>

      <div class="col-12 col-md-4">
        <a class="ct-cat-card" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>?page=shop&category_id=1">
          <div class="ct-cat-card__media">
            <img
              src="<?= BASE_URL ?>public/assets/images/categories/road-bike.jpg"
              alt="Xe đạp đua"
              loading="lazy"
              style="object-fit: cover; width: 100%; height: 100%;"
            >
            <div class="ct-cat-card__overlay" aria-hidden="true" style="background: rgba(0, 0, 0, 0.4);"></div>
          </div>
          <div class="ct-cat-card__body">
            <h3 class="ct-cat-card__title">Xe đua</h3>
          </div>
        </a>
      </div>

      <div class="col-12 col-md-4">
        <a class="ct-cat-card" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>?page=shop&category_id=3">
          <div class="ct-cat-card__media">
            <img
              src="<?= BASE_URL ?>public/assets/images/categories/urban-bike.jpg"
              alt="Xe đường phố"
              loading="lazy"
              style="object-fit: cover; width: 100%; height: 100%;"
            >
            <div class="ct-cat-card__overlay" aria-hidden="true" style="background: rgba(0, 0, 0, 0.4);"></div>
          </div>
          <div class="ct-cat-card__body">
            <h3 class="ct-cat-card__title">Xe đường phố</h3>
          </div>
        </a>
      </div>
    </div>
  </div>
</section>

<section class="ct-mid-cta">
  <div class="container">
    <div class="ct-mid-cta__inner">
      <div>
        <h2 class="ct-mid-cta__title mb-1">Bán xe của bạn ngay hôm nay</h2>
      </div>
      <a class="btn ct-btn-outline-orange" href="<?= BASE_URL ?>?page=post-bike">
        <i class="fa-solid fa-plus me-1"></i>
        Đăng tin
      </a>
    </div>
  </div>
</section>

<section class="ct-section-pad ct-featured">
  <div class="container-fluid px-4 px-lg-5">
    <div class="ct-section-head mb-4">
      <div>
        <h2 class="ct-section-title">Xe mới đăng</h2>
      </div>
      <a class="btn btn-ghost ct-btn-ghost" href="<?= BASE_URL ?>?page=shop">
        Xem tất cả
        <i class="fa-solid fa-arrow-right ms-1"></i>
      </a>
    </div>

    <?php if (!empty($featuredBikes)): ?>
      <div class="row g-5">
        <?php foreach ($featuredBikes as $row): ?>
          <div class="col-12 col-sm-6 col-lg-3">
            <article class="ct-bike-card h-100 d-flex flex-column">
              <div class="ct-bike-card__media">
                <?php if (!empty($row['image_url'])): ?>
                  <img
                    src="<?= BASE_URL ?>public/uploads/bikes/<?= rawurlencode($row['image_url']) ?>"
                    alt="<?= htmlspecialchars((string)($row['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    onerror="this.onerror=null;this.src='<?= BASE_URL ?>public/assets/images/categories/road-bike.jpg';"
                    style="object-fit: cover; width: 100%; height: 100%;"
                  >
                <?php else: ?>
                  <img
                    src="<?= BASE_URL ?>public/assets/images/categories/road-bike.jpg"
                    alt="<?= htmlspecialchars((string)($row['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                    style="object-fit: cover; width: 100%; height: 100%;"
                  >
                <?php endif; ?>
              </div>
              <div class="ct-bike-card__body d-flex flex-column flex-grow-1">
                <h3 class="ct-bike-card__title">
                  <?= htmlspecialchars((string)($row['title'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </h3>
                <?php if (trim((string)($row['brand'] ?? '')) !== ''): ?>
                  <div class="ct-bike-card__meta">
                    <i class="fa-solid fa-tag"></i>
                    <span><?= htmlspecialchars((string)($row['brand'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                  </div>
                <?php endif; ?>
                <div class="ct-bike-card__price mb-3">
                  <strong><?= htmlspecialchars(formatCurrency($row['price'] ?? 0), ENT_QUOTES, 'UTF-8') ?></strong>
                  <?php if (trim((string)($row['condition_status'] ?? '')) !== ''): ?>
                    <span class="badge badge-primary">
                      <?= htmlspecialchars((string)($row['condition_status'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                  <?php endif; ?>
                </div>
                <div class="mt-auto">
                  <?php if (trim((string)($row['location'] ?? '')) !== ''): ?>
                    <div class="ct-bike-card__location mb-3">
                      <i class="fa-solid fa-location-dot"></i>
                      <?= htmlspecialchars((string)($row['location'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    </div>
                  <?php endif; ?>
                  <a class="btn btn-ghost ct-btn-ghost w-100 justify-content-between" href="<?= htmlspecialchars(BASE_URL, ENT_QUOTES, 'UTF-8') ?>?page=bike-detail&id=<?= (int)($row['id'] ?? 0) ?>">
                    Xem chi tiết
                    <i class="fa-solid fa-arrow-right"></i>
                  </a>
                </div>
              </div>
            </article>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="surface p-4">
        <div class="text-muted">Chưa có tin đăng nào. Hãy là người đầu tiên đăng tin xe đạp của bạn.</div>
      </div>
    <?php endif; ?>
  </div>
</section>

