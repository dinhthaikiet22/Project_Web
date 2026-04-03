<?php
/**
 * modules/user/profile_act.php
 * -------------------------------------------------------
 * Xử lý cập nhật thông tin hồ sơ cá nhân (và upload ảnh đại diện).
 * Được gọi từ pages/user/profile.php qua POST form.
 *
 * Quy tắc đặt tên (modules/): tối đa 2 cụm từ, dùng gạch dưới `_`
 * Ví dụ: save_bike.php | profile_act.php | pass_act.php
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

// ③ Làm sạch & lấy dữ liệu đầu vào (chống XSS bằng htmlspecialchars)
$fullName = htmlspecialchars(trim((string)($_POST['full_name'] ?? '')), ENT_QUOTES, 'UTF-8');
$phone    = htmlspecialchars(trim((string)($_POST['phone']     ?? '')), ENT_QUOTES, 'UTF-8');
$address  = htmlspecialchars(trim((string)($_POST['address']   ?? '')), ENT_QUOTES, 'UTF-8');
$bio      = htmlspecialchars(trim((string)($_POST['bio']       ?? '')), ENT_QUOTES, 'UTF-8');

// ④ Xử lý upload avatar (nếu người dùng chọn ảnh mới)
$avatarFileName = null; // null = không thay đổi ảnh

if (isset($_FILES['avatar']) && $_FILES['avatar']['error'] === UPLOAD_ERR_OK) {
    $file     = $_FILES['avatar'];
    $tmpPath  = (string)$file['tmp_name'];
    $origName = (string)$file['name'];
    $fileSize = (int)$file['size'];

    // ④a Kiểm tra kích thước tối đa 3 MB
    if ($fileSize > 3 * 1024 * 1024) {
        $_SESSION['error'] = 'Ảnh đại diện không được vượt quá 3 MB!';
        header('Location: ' . BASE_URL . '?page=user/profile');
        exit;
    }

    // ④b Kiểm tra loại file thực sự qua MIME type (không tin vào extension)
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tmpPath);
    $allowedMimes = ['image/jpeg', 'image/png', 'image/webp'];

    if (!in_array($mimeType, $allowedMimes, true)) {
        $_SESSION['error'] = 'Chỉ chấp nhận ảnh JPG, PNG hoặc WebP!';
        header('Location: ' . BASE_URL . '?page=user/profile');
        exit;
    }

    // ④c Xác định extension từ MIME (an toàn hơn dùng extension gốc)
    $extMap = [
        'image/jpeg' => 'jpg',
        'image/png'  => 'png',
        'image/webp' => 'webp',
    ];
    $ext = $extMap[$mimeType];

    // ④d Tạo tên file duy nhất: avatar_<userId>_<timestamp>.<ext>
    $avatarFileName = 'avatar_' . $userId . '_' . time() . '.' . $ext;
    $uploadDir      = __DIR__ . '/../../public/uploads/avatars/';

    // Tạo thư mục nếu chưa tồn tại
    if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
    }

    $destPath = $uploadDir . $avatarFileName;

    // ④e Di chuyển file từ thư mục tạm sang thư mục uploads
    if (!move_uploaded_file($tmpPath, $destPath)) {
        $_SESSION['error'] = 'Lỗi khi lưu ảnh đại diện. Vui lòng thử lại!';
        header('Location: ' . BASE_URL . '?page=user/profile');
        exit;
    }

    // ④f Xóa ảnh cũ để tránh rác file (nếu có và không phải ảnh mặc định)
    $stmtOld = $conn->prepare('SELECT avatar FROM users WHERE id = :id');
    $stmtOld->execute([':id' => $userId]);
    $oldRow = $stmtOld->fetch();

    if ($oldRow && !empty($oldRow['avatar'])) {
        $oldFilePath = $uploadDir . $oldRow['avatar'];
        if (is_file($oldFilePath)) {
            unlink($oldFilePath);
        }
    }
}

// ⑤ Cập nhật CSDL bằng PDO (prepared statement – chống SQL Injection)
if ($avatarFileName !== null) {
    // Có ảnh mới → cập nhật luôn cột avatar
    $sql = '
        UPDATE users
        SET
            username = :username,
            phone    = :phone,
            address  = :address,
            bio      = :bio,
            avatar   = :avatar
        WHERE id = :id
    ';
    $params = [
        ':username' => $fullName,
        ':phone'    => $phone,
        ':address'  => $address,
        ':bio'      => $bio,
        ':avatar'   => $avatarFileName,
        ':id'       => $userId,
    ];
} else {
    // Không có ảnh mới → giữ nguyên avatar cũ
    $sql = '
        UPDATE users
        SET
            username = :username,
            phone    = :phone,
            address  = :address,
            bio      = :bio
        WHERE id = :id
    ';
    $params = [
        ':username' => $fullName,
        ':phone'    => $phone,
        ':address'  => $address,
        ':bio'      => $bio,
        ':id'       => $userId,
    ];
}

$stmt = $conn->prepare($sql);
$stmt->execute($params);

// ⑥ Cập nhật tên hiển thị ở session (navbar dùng $_SESSION['username'])
$_SESSION['username'] = $fullName;

// ⑦ Thông báo thành công qua SweetAlert2 (xử lý trong footer.php)
$_SESSION['success'] = 'Cập nhật hồ sơ thành công!';
header('Location: ' . BASE_URL . '?page=user/profile');
exit;
