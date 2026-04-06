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
    <link rel="stylesheet" href="public/css/admin_style.css">
</head>
<body class="admin-body">

    <!-- Sidebar -->
    <aside class="admin-sidebar">
        <a href="?page=admin_dashboard" class="admin-brand">
            <i class="fa-solid fa-motorcycle"></i> CycleTrust
        </a>
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
        <header class="admin-header">
            <div class="admin-user-info">
                <i class="fa-solid fa-user-shield text-warning me-2"></i> 
                Welcome, <?= htmlspecialchars($_SESSION['username'] ?? 'Admin') ?>
            </div>
        </header>

        <!-- Dynamic Page Content Container -->
        <main class="admin-container">
