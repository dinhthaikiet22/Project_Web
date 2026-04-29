<?php
declare(strict_types=1);
session_start();
date_default_timezone_set('Asia/Ho_Chi_Minh');

if (!isset($_SESSION['user_id'])) {
    die('Bạn chưa đăng nhập.');
}

require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../config/config_vnpay.php';
$conn = require __DIR__ . '/../../config/db.php';

$orderCode = $_GET['order_code'] ?? '';
$myId = (int)$_SESSION['user_id'];

if (!$orderCode) {
    die('Chưa truyền mã đơn hàng.');
}

// Lấy thông tin order, đảm bảo đơn của mình và chưa thanh toán
$stmt = $conn->prepare("SELECT id, total_price, order_status FROM orders WHERE order_code = ? AND buyer_id = ? LIMIT 1");
$stmt->execute([$orderCode, $myId]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    die('Không tìm thấy đơn hàng của bạn.');
}

if ($order['order_status'] !== 'waiting_payment') {
    die('Đơn hàng này không ở trạng thái chờ thanh toán.');
}

$vnp_TxnRef = $orderCode . '_' . time(); // Thêm time() để đảm bảo mã giao dịch VNPAY qua mỗi lần nhấn là duy nhất
$vnp_OrderInfo = "ThanhToanCycleTrust_" . $orderCode;
$vnp_OrderType = 'other';
$vnp_Amount = (int)($order['total_price'] * 100);
$vnp_Locale = 'vn';
$vnp_BankCode = '';
$vnp_IpAddr = $_SERVER['REMOTE_ADDR'] === '::1' ? '127.0.0.1' : $_SERVER['REMOTE_ADDR'];

$vnp_CreateDate = date('YmdHis');
$vnp_ExpireDate = date('YmdHis', strtotime('+15 minutes', strtotime($vnp_CreateDate)));

$inputData = array(
    "vnp_Version" => "2.1.0",
    "vnp_TmnCode" => VNP_TMN_CODE,
    "vnp_Amount" => $vnp_Amount,
    "vnp_Command" => "pay",
    "vnp_CreateDate" => $vnp_CreateDate,
    "vnp_CurrCode" => "VND",
    "vnp_IpAddr" => $vnp_IpAddr,
    "vnp_Locale" => $vnp_Locale,
    "vnp_OrderInfo" => $vnp_OrderInfo,
    "vnp_OrderType" => $vnp_OrderType,
    "vnp_ReturnUrl" => VNP_RETURN_URL,
    "vnp_TxnRef" => $vnp_TxnRef,
    "vnp_ExpireDate" => $vnp_ExpireDate
);

ksort($inputData);
$query = "";
$i = 0;
$hashdata = "";
foreach ($inputData as $key => $value) {
    if ($i == 1) {
        $hashdata .= '&' . urlencode($key) . "=" . urlencode((string)$value);
    } else {
        $hashdata .= urlencode($key) . "=" . urlencode((string)$value);
        $i = 1;
    }
    $query .= urlencode($key) . "=" . urlencode((string)$value) . '&';
}

$vnp_Url = VNP_URL . "?" . rtrim($query, '&');
if (VNP_HASH_SECRET !== "") {
    $vnpSecureHash = hash_hmac('sha512', $hashdata, VNP_HASH_SECRET);
    $vnp_Url .= '&vnp_SecureHash=' . $vnpSecureHash;
}

header('Location: ' . $vnp_Url);
exit;
