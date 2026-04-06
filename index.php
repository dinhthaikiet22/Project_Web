<?php
declare(strict_types=1);
ob_start();

session_start();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/functions.php';

$page = $_GET['page'] ?? 'home';
if (!is_string($page)) {
    $page = 'home';
}

$page = trim($page);
if ($page === '') {
    $page = 'home';
}

// Allow only safe page slugs, including one level of sub-directory (e.g. "user/profile").
// Prevents directory traversal by whitelisting safe characters and a single slash.
if (!preg_match('/\A[a-zA-Z0-9_-]+(\/[a-zA-Z0-9_-]+)?\z/', $page)) {
    $page = '404';
}

$isAdminRoute = str_starts_with($page, 'admin_');

if ($isAdminRoute) {
    $pageFile = __DIR__ . '/pages/admin/' . $page . '.php';
} else {
    $pageFile = __DIR__ . '/pages/' . $page . '.php';
}

if (!is_file($pageFile)) {
    $pageFile = __DIR__ . '/pages/404.php';
    $isAdminRoute = false;
}

// Pages that ONLY perform actions then redirect (no HTML output).
// Must be included BEFORE header.php so header() calls are never blocked.
// Note: edit-bike.php renders an HTML form, so it stays in the normal flow.
// Pages that are purely action-handlers (redirect after processing, no HTML output).
$actionPages = ['delete-bike'];
if (in_array($page, $actionPages, true)) {
    require $pageFile;
    exit;
}

if ($isAdminRoute) {
    require_once __DIR__ . '/includes/admin/admin_header.php';
} else {
    require_once __DIR__ . '/includes/header.php';
}

if (is_file($pageFile)) {
    require $pageFile;
} else {
    http_response_code(404);
    echo '<h1>404</h1><p>Page not found.</p>';
}

if ($isAdminRoute) {
    require_once __DIR__ . '/includes/admin/admin_footer.php';
} else {
    require_once __DIR__ . '/includes/footer.php';
}
