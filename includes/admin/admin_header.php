<?php
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header('Location: index.php?page=home');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CycleTrust - Admin Panel</title>
    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- FontAwesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <!-- Admin Custom CSS -->
    <link rel="stylesheet" href="public/assets/css/admin_style.css?v=<?= time(); ?>">
</head>
<body class="admin-body">

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <a href="?page=admin_dashboard" class="admin-brand">
            <i class="fa-solid fa-motorcycle"></i> CycleTrust
        </a>
        <div class="px-3 py-2 border-bottom border-secondary mb-2" style="border-color: rgba(255,255,255,0.1) !important;">
            <a href="index.php" class="btn btn-outline-light w-100 btn-sm text-start" target="_blank" style="opacity: 0.8; transition: 0.3s;" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0.8'">
                <i class="fa-solid fa-external-link-alt me-2"></i> Xem Website
            </a>
        </div>
        <?php
        $page = $page ?? $_GET['page'] ?? '';
        $product_pages = ['admin_bikes', 'admin_categories', 'admin_brands'];
        $order_pages = ['admin_orders', 'admin_refunds'];
        $customer_pages = ['admin_users', 'admin_admins'];
        $finance_pages = ['admin_transactions', 'admin_vnpay'];
        $marketing_pages = ['admin_coupons', 'admin_banners'];
        ?>
        <nav class="admin-nav d-flex flex-column">
            <a href="?page=admin_dashboard" class="admin-nav-link <?= ($page === 'admin_dashboard') ? 'active' : '' ?>">
                <span><i class="fa-solid fa-chart-pie"></i> Dashboard</span>
            </a>
            
            <!-- Sản phẩm -->
            <a href="#productMenu" class="admin-nav-link <?= in_array($page, $product_pages) ? '' : 'collapsed' ?>" data-bs-toggle="collapse">
                <span><i class="fa-solid fa-box"></i> Quản lý Sản phẩm</span>
                <i class="fa-solid fa-chevron-down toggle-icon"></i>
            </a>
            <div class="collapse <?= in_array($page, $product_pages) ? 'show' : '' ?>" id="productMenu">
                <div class="admin-submenu">
                    <a href="?page=admin_bikes" class="admin-submenu-link <?= ($page === 'admin_bikes') ? 'active' : '' ?>">Danh sách Xe</a>
                    <a href="?page=admin_categories" class="admin-submenu-link <?= ($page === 'admin_categories') ? 'active' : '' ?>">Danh mục</a>
                    <a href="?page=admin_brands" class="admin-submenu-link <?= ($page === 'admin_brands') ? 'active' : '' ?>">Thương hiệu</a>
                </div>
            </div>

            <!-- Đơn hàng -->
            <a href="#orderMenu" class="admin-nav-link <?= in_array($page, $order_pages) ? '' : 'collapsed' ?>" data-bs-toggle="collapse">
                <span><i class="fa-solid fa-cart-shopping"></i> Quản lý Đơn hàng</span>
                <i class="fa-solid fa-chevron-down toggle-icon"></i>
            </a>
            <div class="collapse <?= in_array($page, $order_pages) ? 'show' : '' ?>" id="orderMenu">
                <div class="admin-submenu">
                    <a href="?page=admin_orders" class="admin-submenu-link <?= ($page === 'admin_orders') ? 'active' : '' ?>">Tất cả đơn hàng</a>
                    <a href="?page=admin_refunds" class="admin-submenu-link <?= ($page === 'admin_refunds') ? 'active' : '' ?>">Yêu cầu Hoàn/Hủy</a>
                </div>
            </div>

            <!-- Khách hàng -->
            <a href="#customerMenu" class="admin-nav-link <?= in_array($page, $customer_pages) ? '' : 'collapsed' ?>" data-bs-toggle="collapse">
                <span><i class="fa-solid fa-users"></i> Khách hàng & Phân quyền</span>
                <i class="fa-solid fa-chevron-down toggle-icon"></i>
            </a>
            <div class="collapse <?= in_array($page, $customer_pages) ? 'show' : '' ?>" id="customerMenu">
                <div class="admin-submenu">
                    <a href="?page=admin_users" class="admin-submenu-link <?= ($page === 'admin_users') ? 'active' : '' ?>">Danh sách User</a>
                    <a href="?page=admin_admins" class="admin-submenu-link <?= ($page === 'admin_admins') ? 'active' : '' ?>">Quản trị viên</a>
                </div>
            </div>

            <!-- Tài chính -->
            <a href="#financeMenu" class="admin-nav-link <?= in_array($page, $finance_pages) ? '' : 'collapsed' ?>" data-bs-toggle="collapse">
                <span><i class="fa-solid fa-wallet"></i> Tài chính</span>
                <i class="fa-solid fa-chevron-down toggle-icon"></i>
            </a>
            <div class="collapse <?= in_array($page, $finance_pages) ? 'show' : '' ?>" id="financeMenu">
                <div class="admin-submenu">
                    <a href="?page=admin_transactions" class="admin-submenu-link <?= ($page === 'admin_transactions') ? 'active' : '' ?>">Giao dịch hệ thống</a>
                    <a href="?page=admin_vnpay" class="admin-submenu-link <?= ($page === 'admin_vnpay') ? 'active' : '' ?>">Đối soát VNPAY</a>
                </div>
            </div>

            <!-- Marketing -->
            <a href="#marketingMenu" class="admin-nav-link <?= in_array($page, $marketing_pages) ? '' : 'collapsed' ?>" data-bs-toggle="collapse">
                <span><i class="fa-solid fa-bullhorn"></i> Marketing</span>
                <i class="fa-solid fa-chevron-down toggle-icon"></i>
            </a>
            <div class="collapse <?= in_array($page, $marketing_pages) ? 'show' : '' ?>" id="marketingMenu">
                <div class="admin-submenu">
                    <a href="?page=admin_coupons" class="admin-submenu-link <?= ($page === 'admin_coupons') ? 'active' : '' ?>">Mã giảm giá</a>
                    <a href="?page=admin_banners" class="admin-submenu-link <?= ($page === 'admin_banners') ? 'active' : '' ?>">Quản lý Banner</a>
                </div>
            </div>

            <a href="?page=admin_settings" class="admin-nav-link <?= ($page === 'admin_settings') ? 'active' : '' ?>">
                <span><i class="fa-solid fa-gear"></i> Cấu hình hệ thống</span>
            </a>

            <a href="?page=logout" class="admin-nav-link mt-auto" style="border-top: 1px solid rgba(255,255,255,0.1);">
                <span><i class="fa-solid fa-arrow-right-from-bracket"></i> Đăng xuất</span>
            </a>
        </nav>
    </aside>

    <!-- Main Content Wrapper -->
    <div class="admin-main-content">
        <!-- Top Header -->
        <header class="admin-header d-flex justify-content-between align-items-center w-100">
            <!-- Left: Breadcrumb & Title -->
            <div class="d-flex flex-column">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-1">
                        <li class="breadcrumb-item"><a href="#" class="text-muted text-decoration-none small">Admin</a></li>
                        <li class="breadcrumb-item active text-dark fw-bold small" aria-current="page">CycleTrust</li>
                    </ol>
                </nav>
            </div>

            <div class="d-flex align-items-center gap-3 bg-white p-2 rounded-pill shadow-sm">
                <!-- View Website Button -->
                <a href="index.php" class="btn btn-sm text-muted rounded-pill px-3 d-flex align-items-center fw-medium" target="_blank">
                    <i class="fa-solid fa-external-link-alt me-2" style="color: #FF5C00;"></i>Xem Website
                </a>
                
                <!-- Right: Profile Widget -->
                <div class="dropdown">
                    <button class="btn border-0 p-0 text-start d-flex align-items-center dropdown-toggle profile-widget pe-3" type="button" id="adminDropdown" data-bs-toggle="dropdown" aria-expanded="false" style="background:transparent;">
                        <div class="position-relative ms-2">
                            <img src="https://ui-avatars.com/api/?name=<?= urlencode($_SESSION['username'] ?? 'Admin') ?>&background=FF5C00&color=fff&bold=true" alt="Admin Avatar" class="rounded-circle shadow-sm" style="width: 42px; height: 42px; border: 2px solid #FFD700; object-fit: cover;">
                            <span class="position-absolute bottom-0 end-0 p-1 bg-success border border-light rounded-circle" style="width: 12px; height: 12px; transform: translate(10%, 10%);">
                                <span class="visually-hidden">Online</span>
                            </span>
                        </div>
                        <div class="ms-2 d-none d-md-block text-dark lh-sm flex-grow-1">
                            <div class="fw-bold fs-6"><?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?></div>
                        </div>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 rounded-4 mt-2" aria-labelledby="adminDropdown" style="min-width: 200px;">
                        <li><a class="dropdown-item py-2" href="?page=admin_profile"><i class="fa-solid fa-user me-2" style="color: #FF5C00;"></i> Trang cá nhân</a></li>
                        <li><a class="dropdown-item py-2" href="?page=admin_settings"><i class="fa-solid fa-cog me-2 text-secondary"></i> Cài đặt hệ thống</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item py-2 text-danger fw-medium" href="modules/auth/logout.php"><i class="fa-solid fa-sign-out-alt me-2"></i> Đăng xuất</a></li>
                    </ul>
                </div>
            </div>
        </header>

        <!-- Dynamic Page Content Container -->
        <main class="admin-container">
