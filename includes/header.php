<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CycleTrust</title>

  <!-- Google Fonts: Inter -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- CycleTrust styles -->
  <link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/style.css?v=<?= time(); ?>">

  <style>
    /* -------------------------------------
     * CUSTOM HEADER CSS (MARKETPLACE STYLE)
     * ------------------------------------- */
    body {
        font-family: 'Inter', sans-serif;
    }
    
    .ct-main-header {
        background-color: #fff;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05); /* Chiều sâu nhẹ */
        padding: 12px 0;
        border-bottom: 1px solid rgba(0,0,0,0.03);
    }

    /* Logo */
    .ct-brand-logo {
        font-weight: 800;
        font-size: 1.6rem;
        letter-spacing: -0.5px;
        text-decoration: none;
    }
    .ct-brand-cycle { color: #212121; }
    .ct-brand-trust { color: #FF5722; }

    /* Search Bar */
    .ct-search-box {
        max-width: 600px;
        width: 100%;
    }
    .ct-search-input-wrapper {
        position: relative;
        display: flex;
        align-items: center;
        background: #f8f9fa;
        border: 1.5px solid #e9ecef;
        border-radius: 50px;
        transition: all 0.2s ease-in-out;
        padding: 4px 16px;
    }
    .ct-search-input-wrapper:focus-within {
        border-color: #FF5722;
        background: #fff;
        box-shadow: 0 0 0 4px rgba(255, 87, 34, 0.1);
    }
    .ct-search-icon {
        color: #adb5bd;
        font-size: 1rem;
    }
    .ct-search-input-wrapper:focus-within .ct-search-icon {
        color: #FF5722;
    }
    .ct-search-input {
        border: none;
        background: transparent;
        box-shadow: none !important;
        padding: 8px 12px;
        font-size: 0.95rem;
        width: 100%;
        outline: none;
    }
    .ct-search-input::placeholder {
        color: #adb5bd;
    }

    /* Action Buttons & Icons */
    .ct-btn-post {
        background-color: #FF5722;
        color: #fff;
        border-radius: 50px;
        padding: 10px 24px;
        font-weight: 600;
        font-size: 0.95rem;
        border: none;
        transition: background-color 0.2s, transform 0.1s;
    }
    .ct-btn-post:hover {
        background-color: #e64a19;
        color: #fff;
        transform: translateY(-1px);
    }

    .ct-action-icon {
        color: #495057; /* Xám đậm */
        font-size: 1.35rem;
        position: relative;
        padding: 6px;
        transition: color 0.2s;
        text-decoration: none;
    }
    .ct-action-icon:hover {
        color: #FF5722;
    }

    /* Badge tin nhắn */
    .ct-badge-unread {
        background-color: #FF5722;
        color: #fff;
        font-size: 0.65rem;
        font-weight: 800;
        border: 2px solid #fff;
        padding: 0.35em 0.55em;
        position: absolute;
        top: 0;
        right: -2px;
        transform: translate(25%, -15%);
        z-index: 2;
    }

    /* User Dropdown */
    .ct-user-menu {
        display: flex;
        align-items: center;
        gap: 10px;
        color: #212121;
        font-weight: 600;
        text-decoration: none;
        padding: 6px 12px;
        border-radius: 50px;
        transition: background-color 0.2s;
        border: 1px solid transparent;
    }
    .ct-user-menu:hover {
        background-color: #f8f9fa;
        border-color: #e9ecef;
        color: #FF5722;
    }
    .ct-user-avatar {
        width: 32px;
        height: 32px;
        border-radius: 50%;
        object-fit: cover;
        background-color: #e9ecef;
        border: 1px solid #ced4da;
    }
  </style>
</head>
<body>

<header class="sticky-top ct-main-header">
  <div class="container">
    <!-- Cấu trúc Responsive: Trên Mobile chia 2 dòng, trên PC nằm 1 hàng dọc -->
    <div class="row align-items-center gx-3 gy-3">
      
      <!-- LOGO -->
      <div class="col-auto">
        <a class="ct-brand-logo d-flex align-items-center gap-1" href="<?= BASE_URL ?>">
          <img src="<?= BASE_URL ?>public/assets/images/logo.png" alt="" onerror="this.style.display='none'" style="height: 30px;">
          <span>
            <span class="ct-brand-cycle">CYCLE</span><span class="ct-brand-trust">TRUST</span>
          </span>
        </a>
      </div>

      <!-- SEARCH BAR -->
      <div class="col col-lg-5 col-xl-6 order-3 order-lg-2">
        <div class="ct-search-box w-100 mx-auto">
          <form action="<?= BASE_URL ?>" method="get" class="m-0">
            <input type="hidden" name="page" value="shop">
            <div class="ct-search-input-wrapper">
              <i class="fa-solid fa-magnifying-glass ct-search-icon"></i>
              <input 
                type="text" 
                name="q" 
                class="ct-search-input" 
                placeholder="Tìm xe theo hãng, giá, khu vực..." 
                value="<?= htmlspecialchars((string)($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
              >
            </div>
          </form>
        </div>
      </div>

      <!-- ACTION BUTTONS -->
      <div class="col-auto ms-auto order-2 order-lg-3">
        <div class="d-flex align-items-center gap-3 gap-md-4">
          
          <?php if (isset($_SESSION['user_id'])): ?>
            
            <!-- Nút đăng tin -->
            <a href="<?= BASE_URL ?>?page=post-bike" class="ct-btn-post d-none d-md-inline-flex align-items-center gap-2 text-decoration-none shadow-sm">
              <i class="fa-solid fa-cloud-arrow-up"></i> Đăng tin
            </a>

            <!-- Icon Chat -->
            <a href="<?= BASE_URL ?>?page=chat_room" class="ct-action-icon" id="navChatIcon" title="Tin nhắn">
              <i class="fa-regular fa-message"></i>
              <span class="badge rounded-pill ct-badge-unread shadow-sm" id="unreadMsgBadge" style="display: none;">0</span>
            </a>

            <!-- Icon Cart -->
            <a href="<?= BASE_URL ?>?page=shop" class="ct-action-icon" title="Giỏ hàng">
              <i class="fa-solid fa-cart-shopping"></i>
            </a>

            <!-- User Dropdown (Gọn gàng + Avatar) -->
            <div class="dropdown">
              <a href="#" class="ct-user-menu dropdown-toggle" data-bs-toggle="dropdown" aria-expanded="false" data-bs-offset="0,10">
                <?php
                  $uName = htmlspecialchars((string)($_SESSION['username'] ?? 'User'), ENT_QUOTES, 'UTF-8');
                  $hAvatar = '';
                  // Fallback load Avatar siêu tốc dùng UI-Avatar API
                  if (isset($_SESSION['avatar']) && trim($_SESSION['avatar']) !== '') {
                      $hAvatar = BASE_URL . 'public/uploads/avatars/' . rawurlencode($_SESSION['avatar']);
                  } else {
                      $hAvatar = 'https://ui-avatars.com/api/?name=' . urlencode($uName) . '&background=f8f9fa&color=FF5722&bold=true';
                  }
                ?>
                <img src="<?= $hAvatar ?>" alt="Avatar" class="ct-user-avatar">
                <span class="d-none d-md-block"><?= $uName ?></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end border-0 shadow mt-2" style="border-radius: 12px; min-width: 220px;">
                <li><h6 class="dropdown-header text-uppercase text-muted" style="font-size:0.75rem;">Cá nhân</h6></li>
                <li><a class="dropdown-item py-2 fw-medium" href="<?= BASE_URL ?>?page=user/profile"><i class="fa-solid fa-user-circle text-muted me-2"></i> Hồ sơ của tôi</a></li>
                <li><a class="dropdown-item py-2 fw-medium" href="<?= BASE_URL ?>?page=user/orders"><i class="fa-solid fa-bag-shopping text-muted me-2"></i> Đơn mua & Đơn bán</a></li>
                <li><a class="dropdown-item py-2 fw-medium" href="<?= BASE_URL ?>?page=my-postings"><i class="fa-regular fa-rectangle-list text-muted me-2"></i> Quản lý tin đăng</a></li>
                <li><a class="dropdown-item py-2 fw-medium d-md-none" href="<?= BASE_URL ?>?page=post-bike"><i class="fa-solid fa-plus text-muted me-2"></i> Đăng tin mới</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item py-2 text-danger fw-bold" href="<?= BASE_URL ?>modules/auth/logout.php"><i class="fa-solid fa-arrow-right-from-bracket me-2"></i> Đăng xuất</a></li>
              </ul>
            </div>

          <?php else: ?>
            
            <a href="<?= BASE_URL ?>?page=register" class="text-dark fw-bold text-decoration-none d-none d-sm-block hover-orange" style="transition: color .2s;" onmouseover="this.style.color='#FF5722'" onmouseout="this.style.color=''">Đăng ký</a>
            <a href="<?= BASE_URL ?>?page=login" class="ct-btn-post text-decoration-none shadow-sm">
              <i class="fa-regular fa-circle-user me-1"></i> Đăng nhập
            </a>
            
          <?php endif; ?>

        </div>
      </div>

    </div>
  </div>
</header>

<main>
