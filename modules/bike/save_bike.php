<?php
declare(strict_types=1);

session_start();

require_once '../../config/config.php';
$conn = require_once '../../config/db.php';

function redirectWithError(string $message): void
{
    $_SESSION['error'] = $message;
    header('Location: ' . BASE_URL . '?page=post-bike');
    exit;
}

/**
 * Kiểm tra và lưu một file ảnh upload, trả về tên file lưu trên server (chỉ tên file).
 *
 * @return string|null Tên file mới hoặc null nếu bỏ qua (không upload).
 */
function saveUploadedImage(array $file, string $uploadDir): ?string
{
    $err = (int)($file['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($err === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if ($err !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Upload ảnh thất bại.');
    }

    $originalName = (string)($file['name'] ?? '');
    $tmpName = (string)($file['tmp_name'] ?? '');

    $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    $allowedExt = ['jpg', 'jpeg', 'png', 'webp'];
    if (!in_array($ext, $allowedExt, true)) {
        throw new RuntimeException('Lỗi ảnh: Chỉ chấp nhận JPG, PNG, WEBP.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $tmpName !== '' ? (string)$finfo->file($tmpName) : '';
    $allowedMime = ['image/jpeg', 'image/png', 'image/webp'];
    if ($mime === '' || !in_array($mime, $allowedMime, true)) {
        throw new RuntimeException('File ảnh không hợp lệ.');
    }

    if (!is_dir($uploadDir)) {
        if (!mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
            throw new RuntimeException('Không thể tạo thư mục lưu ảnh.');
        }
    }

    $newFileName = uniqid('', true) . '_' . basename($originalName);
    $targetPath = $uploadDir . $newFileName;
    if (!move_uploaded_file($tmpName, $targetPath)) {
        throw new RuntimeException('Lỗi hệ thống: Không thể lưu file vào thư mục public/uploads/bikes/.');
    }

    return $newFileName;
}

if (!isset($_SESSION['user_id']) || (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST')) {
    exit;
}

$userId = (int)$_SESSION['user_id'];

$title = trim((string)($_POST['title'] ?? ''));
$brand = trim((string)($_POST['brand'] ?? ''));
$categoryIdRaw = trim((string)($_POST['category_id'] ?? ''));
$conditionStatus = trim((string)($_POST['condition_status'] ?? ''));
$priceRaw = trim((string)($_POST['price'] ?? ''));
$location = trim((string)($_POST['location'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));

// --- BẮT ĐẦU: CODE BỔ SUNG ĐỂ NHẬN TỌA ĐỘ ---
$latitudeRaw = trim((string)($_POST['latitude'] ?? ''));
$longitudeRaw = trim((string)($_POST['longitude'] ?? ''));

$latitude = ($latitudeRaw !== '' && is_numeric($latitudeRaw)) ? (float)$latitudeRaw : null;
$longitude = ($longitudeRaw !== '' && is_numeric($longitudeRaw)) ? (float)$longitudeRaw : null;
// --- KẾT THÚC: CODE BỔ SUNG ĐỂ NHẬN TỌA ĐỘ ---

$categoryId = ctype_digit($categoryIdRaw) ? (int)$categoryIdRaw : 0;
if ($categoryId === 0 && $categoryIdRaw !== '') {
    $categoryId = 1;
}
$price = ($priceRaw !== '' && is_numeric($priceRaw)) ? (int)$priceRaw : 0;

if ($title === '') {
    redirectWithError('Vui lòng nhập tên xe đạp.');
}

if ($categoryId <= 0) {
    redirectWithError('Danh mục không hợp lệ.');
}

if ($conditionStatus !== '' && !in_array($conditionStatus, ['Mới', 'Đã sử dụng'], true)) {
    redirectWithError('Tình trạng không hợp lệ.');
}

if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
    redirectWithError('Vui lòng chọn hình ảnh chính cho xe.');
}

$uploadDir = __DIR__ . '/../../public/uploads/bikes/';
$uploadedFiles = [];

try {
    $mainFileName = saveUploadedImage($_FILES['image'], $uploadDir);
    if ($mainFileName === null) {
        redirectWithError('Vui lòng chọn hình ảnh chính cho xe.');
    }
    $uploadedFiles[] = $uploadDir . $mainFileName;

    $galleryPaths = [];
    if (isset($_FILES['gallery']) && is_array($_FILES['gallery']['name'])) {
        $names = $_FILES['gallery']['name'];
        $count = is_array($names) ? count($names) : 0;
        for ($i = 0; $i < $count; $i++) {
            $one = [
                'name' => $_FILES['gallery']['name'][$i] ?? '',
                'type' => $_FILES['gallery']['type'][$i] ?? '',
                'tmp_name' => $_FILES['gallery']['tmp_name'][$i] ?? '',
                'error' => $_FILES['gallery']['error'][$i] ?? UPLOAD_ERR_NO_FILE,
                'size' => $_FILES['gallery']['size'][$i] ?? 0,
            ];
            if ((int)$one['error'] === UPLOAD_ERR_NO_FILE) {
                continue;
            }
            $saved = saveUploadedImage($one, $uploadDir);
            if ($saved !== null) {
                $galleryPaths[] = $saved;
                $uploadedFiles[] = $uploadDir . $saved;
            }
        }
    }

    $conn->beginTransaction();

    // --- BẮT ĐẦU: CẬP NHẬT LỆNH SQL ĐỂ LƯU THÊM LATITUDE VÀ LONGITUDE ---
    $insertBike = $conn->prepare(
        'INSERT INTO bikes (user_id, category_id, title, price, brand, condition_status, description, image_url, location, latitude, longitude, status)
         VALUES (:user_id, :category_id, :title, :price, :brand, :condition_status, :description, :image_url, :location, :latitude, :longitude, \'available\')'
    );
    $insertBike->execute([
        ':user_id' => $userId,
        ':category_id' => $categoryId,
        ':title' => $title,
        ':price' => $price,
        ':brand' => $brand,
        ':condition_status' => $conditionStatus,
        ':description' => $description,
        ':image_url' => $mainFileName,
        ':location' => $location,
        ':latitude' => $latitude,
        ':longitude' => $longitude,
    ]);
    // --- KẾT THÚC: CẬP NHẬT LỆNH SQL ---

    $bikeId = (int)$conn->lastInsertId();
    if ($bikeId <= 0) {
        throw new RuntimeException('Không lấy được ID xe sau khi thêm.');
    }

    $insertGallery = $conn->prepare(
        'INSERT INTO bike_images (bike_id, image_url) VALUES (:bike_id, :image_url)'
    );
    foreach ($galleryPaths as $path) {
        $insertGallery->execute([
            ':bike_id' => $bikeId,
            ':image_url' => $path,
        ]);
    }

    $conn->commit();

    $_SESSION['success'] = 'Đăng tin thành công!';
    // Mình sửa lại chuyển hướng về trang my-postings để bạn tiện xem thành quả vừa đăng
    header('Location: ' . BASE_URL . '?page=my-postings');
    exit;
} catch (PDOException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    foreach ($uploadedFiles as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
    redirectWithError('Lỗi Database: ' . $e->getMessage());
} catch (RuntimeException $e) {
    if ($conn->inTransaction()) {
        $conn->rollBack();
    }
    foreach ($uploadedFiles as $path) {
        if (is_file($path)) {
            @unlink($path);
        }
    }
    redirectWithError($e->getMessage());
}