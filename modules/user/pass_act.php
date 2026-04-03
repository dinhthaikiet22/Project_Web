<?php
/**
 * modules/user/pass_act.php
 * -------------------------------------------------------
 * Xử lý đổi mật khẩu người dùng.
 * Được gọi từ pages/user/profile.php qua POST form (tab "Đổi mật khẩu").
 *
 * Quy tắc đặt tên: file trong /modules/ dùng gạch dưới `_`
 * Ví dụ: save_bike.php, profile_act.php, pass_act.php
 * -------------------------------------------------------
 */
declare(strict_types=1);

// ① Yêu cầu đăng nhập
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

// ② Chỉ chấp nhận POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . BASE_URL . '?page=user/profile');
    exit;
}

/** @var PDO $conn */
$conn = require __DIR__ . '/../../config/db.php';

$userId = (int)$_SESSION['user_id'];

// ③ Lấy dữ liệu từ form
$oldPassword  = (string)($_POST['old_password']  ?? '');
$newPassword  = (string)($_POST['new_password']  ?? '');
$confirmPass  = (string)($_POST['confirm_password'] ?? '');

// ④ Kiểm tra mật khẩu mới và xác nhận mật khẩu phải trùng nhau
if ($newPassword !== $confirmPass) {
    $_SESSION['error'] = 'Mật khẩu mới và xác nhận mật khẩu không khớp!';
    header('Location: ' . BASE_URL . '?page=user/profile&tab=password');
    exit;
}

// ⑤ Kiểm tra độ dài tối thiểu của mật khẩu mới
if (strlen($newPassword) < 6) {
    $_SESSION['error'] = 'Mật khẩu mới phải có ít nhất 6 ký tự!';
    header('Location: ' . BASE_URL . '?page=user/profile&tab=password');
    exit;
}

// ⑥ Lấy mật khẩu hiện tại từ CSDL
$stmt = $conn->prepare('SELECT password FROM users WHERE id = :id');
$stmt->execute([':id' => $userId]);
$user = $stmt->fetch();

if (!$user) {
    // Tình huống hiếm gặp: user trong session nhưng không có trong DB
    $_SESSION['error'] = 'Không tìm thấy tài khoản!';
    header('Location: ' . BASE_URL . '?page=user/profile&tab=password');
    exit;
}

// ⑦ Xác minh mật khẩu cũ bằng password_verify (an toàn với bcrypt)
if (!password_verify($oldPassword, (string)$user['password'])) {
    $_SESSION['error'] = 'Mật khẩu cũ không chính xác!';
    header('Location: ' . BASE_URL . '?page=user/profile&tab=password');
    exit;
}

// ⑧ Mã hóa mật khẩu mới bằng bcrypt (PASSWORD_DEFAULT = bcrypt trong PHP ≥ 5.5)
$hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

// ⑨ Cập nhật mật khẩu mới vào CSDL
$stmtUpdate = $conn->prepare('UPDATE users SET password = :password WHERE id = :id');
$stmtUpdate->execute([
    ':password' => $hashedPassword,
    ':id'       => $userId,
]);

// ⑩ Thông báo thành công
$_SESSION['success'] = 'Đổi mật khẩu thành công!';
header('Location: ' . BASE_URL . '?page=user/profile');
exit;
