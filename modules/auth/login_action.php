<?php
declare(strict_types=1);

session_start();

require_once '../../config/config.php';
$conn = require_once '../../config/db.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    $_SESSION['error'] = 'Yêu cầu không hợp lệ';
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

$login_id = trim((string)($_POST['email'] ?? ''));
$password = (string)($_POST['password'] ?? '');

if ($login_id === '' || $password === '') {
    $_SESSION['error'] = 'Vui lòng nhập đầy đủ thông tin';
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

try {
    $stmt = $conn->prepare('SELECT * FROM users WHERE email = ? OR username = ? LIMIT 1');
    $stmt->execute([$login_id, $login_id]);
    $user = $stmt->fetch();

    if ($user && isset($user['password']) && password_verify($password, (string)$user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['role'] = $user['role'] ?? 'user';

        if ($_SESSION['role'] === 'admin') {
            header('Location: ' . BASE_URL . '?page=admin_dashboard');
        } else {
            header('Location: ' . BASE_URL . '?page=home');
        }
        exit;
    }

    $_SESSION['error'] = 'Sai tài khoản hoặc mật khẩu';
    header('Location: ' . BASE_URL . '?page=login');
    exit;
} catch (PDOException $e) {
    $_SESSION['error'] = 'Có lỗi xảy ra, vui lòng thử lại';
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

