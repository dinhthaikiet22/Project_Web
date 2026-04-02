<?php declare(strict_types=1); ?>
<!doctype html>
<html lang="vi">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>CycleTrust</title>

  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

  <!-- Font Awesome 6 -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

  <!-- CycleTrust styles -->
  <link rel="stylesheet" href="<?= BASE_URL ?>public/assets/css/style.css">
</head>
<body>

<header class="sticky-top">
  <nav class="navbar navbar-expand-lg navbar-light ct-navbar ct-navbar--white">
    <div class="container">
      <a class="navbar-brand d-flex align-items-center gap-2" href="<?= BASE_URL ?>">
        <span class="ct-logo ct-logo--dark">CYCLETRUST</span>
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#ctNavbar" aria-controls="ctNavbar" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="ctNavbar">
        <form class="d-flex mx-lg-auto my-3 my-lg-0 ct-search ct-search--white" role="search" action="<?= BASE_URL ?>" method="get">
          <input type="hidden" name="page" value="shop">
          <div class="input-group ct-search__group">
            <span class="input-group-text ct-search__icon"><i class="fa-solid fa-magnifying-glass"></i></span>
            <input class="form-control ct-search__input" type="search" name="q" placeholder="Tìm xe theo hãng, giá, khu vực..." aria-label="Search" value="<?= htmlspecialchars((string)($_GET['q'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
          </div>
        </form>

        <ul class="navbar-nav align-items-lg-center gap-lg-2 ms-lg-3 ct-nav">
          <li class="nav-item">
            <a class="nav-link ct-nav__link ct-nav__link--dark" href="<?= BASE_URL ?>?page=shop">
              Khám phá
            </a>
          </li>
          <?php if (isset($_SESSION['user_id'])): ?>
            <li class="nav-item mt-2 mt-lg-0">
              <a class="btn btn-primary ct-btn-cta ct-btn-cta--orange w-100" href="<?= BASE_URL ?>?page=post-bike">
                <i class="fa-solid fa-plus me-1"></i>
                Đăng tin
              </a>
            </li>
            <li class="nav-item mt-2 mt-lg-0">
              <a class="nav-link ct-icon-btn" href="<?= BASE_URL ?>?page=shop" aria-label="Giỏ hàng">
                <svg class="ct-icon" width="22" height="22" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                  <path d="M6 7h15l-2 9H7L6 7Z" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>
                  <path d="M6 7 5 3H2" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                  <path d="M9 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2ZM17 20a1 1 0 1 0 0-2 1 1 0 0 0 0 2Z" stroke="currentColor" stroke-width="1.5"/>
                </svg>
              </a>
            </li>
            <li class="nav-item dropdown mt-2 mt-lg-0">
              <a
                class="nav-link dropdown-toggle ct-user ct-user--dark"
                href="#"
                role="button"
                data-bs-toggle="dropdown"
                aria-expanded="false"
              >
                <span class="ct-user__icon ct-user__icon--dark" aria-hidden="true">
                  <svg class="ct-icon" width="22" height="22" viewBox="0 0 24 24" fill="none">
                    <path d="M20 21a8 8 0 1 0-16 0" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"/>
                    <path d="M12 13a4 4 0 1 0 0-8 4 4 0 0 0 0 8Z" stroke="currentColor" stroke-width="1.5"/>
                  </svg>
                </span>
                <span class="ct-user__name"><?= htmlspecialchars((string)($_SESSION['username'] ?? 'User'), ENT_QUOTES, 'UTF-8') ?></span>
              </a>
              <ul class="dropdown-menu dropdown-menu-end ct-dropdown ct-dropdown--white">
                <li>
                  <a class="dropdown-item" href="<?= BASE_URL ?>?page=my-postings">
                    <i class="fa-solid fa-rectangle-list me-2"></i>
                    Tin của tôi
                  </a>
                </li>
                <li>
                  <a class="dropdown-item" href="<?= BASE_URL ?>?page=post-bike">
                    <i class="fa-solid fa-pen-to-square me-2"></i>
                    Đăng tin mới
                  </a>
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                  <a class="dropdown-item text-danger" href="<?= BASE_URL ?>modules/auth/logout.php">
                    <i class="fa-solid fa-right-from-bracket me-2"></i>
                    Đăng xuất
                  </a>
                </li>
              </ul>
            </li>
          <?php else: ?>
            <li class="nav-item">
              <a class="nav-link ct-nav__link ct-nav__link--dark" href="<?= BASE_URL ?>?page=register">Đăng ký</a>
            </li>
            <li class="nav-item mt-2 mt-lg-0">
              <a class="btn btn-primary ct-btn-cta ct-btn-cta--orange w-100" href="<?= BASE_URL ?>?page=login">
                <i class="fa-solid fa-right-to-bracket me-1"></i>
                Đăng nhập
              </a>
            </li>
          <?php endif; ?>
        </ul>
      </div>
    </div>
  </nav>
</header>

<main>

