<?php
define('VNP_TMN_CODE', '2QN09Z4U');
define('VNP_HASH_SECRET', '82164BD4B0568B4286663E0EF65507E9');
define('VNP_URL', 'https://sandbox.vnpayment.vn/paymentv2/vpcpay.html');
define('VNP_RETURN_URL', 'http://localhost/CycleTrust/index.php?page=vnpay_return');
$orderCode = 'ORD1234567890';
$totalPrice = 14050000;
        $vnp_TxnRef = $orderCode . '_' . time(); // Đảm bảo duy nhất
        $vnp_OrderInfo = trim("Thanh toan don hang " . $orderCode);
        $vnp_OrderType = 'other';
        $vnp_Amount = (int)($totalPrice * 100);
        $vnp_Locale = 'vn';
        $vnp_BankCode = '';
        $vnp_IpAddr = '127.0.0.1';
        
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

echo json_encode([
            'status' => 'success',
            'message' => 'Đang chuyển hướng VNPAY...',
            'payment_url' => $vnp_Url,
            'order_code' => $orderCode,
            'debug_hash' => $hashdata
        ], JSON_UNESCAPED_SLASHES);
