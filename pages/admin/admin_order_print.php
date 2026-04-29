<?php
declare(strict_types=1);

/** @var PDO $conn */
$conn = require_once __DIR__ . '/../../config/db.php';

$id = $_GET['id'] ?? null;
if (!$id || !is_numeric($id)) {
    die("Lỗi: Mã đơn hàng không hợp lệ.");
}
$id = (int)$id;

try {
    $sql = "SELECT o.*, 
                   buyer.username AS buyer_username, buyer.email AS buyer_email,
                   seller.username AS seller_username,
                   b.title AS bike_title, b.price AS bike_price
            FROM orders o 
            LEFT JOIN users buyer ON o.buyer_id = buyer.id 
            LEFT JOIN users seller ON o.seller_id = seller.id 
            LEFT JOIN bikes b ON o.bike_id = b.id
            WHERE o.id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$id]);
    $order = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("Lỗi kết nối CSDL: " . $e->getMessage());
}

if (!$order) {
    die("Không tìm thấy đơn hàng trên hệ thống.");
}

// Bóc tách thông tin liên hệ
$buyer_name = $order['recipient_name'] ?? $order['buyer_username'] ?? 'Khách hàng';
$buyer_phone = $order['recipient_phone'] ?? 'Chưa cung cấp';
$buyer_address = $order['shipping_address'] ?? 'Chưa cung cấp';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>In Hoá Đơn Đơn Hàng #<?= htmlspecialchars((string)$order['order_code']) ?></title>
    <!-- Google Fonts cho sự chuyên nghiệp ngay cả trên bản in -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&display=swap" rel="stylesheet">
    <style>
        /* CSS Dành riêng cho Bản in Trắng Đen / Hóa Đơn */
        body {
            font-family: 'Inter', sans-serif;
            margin: 0;
            padding: 40px;
            color: #000;
            background: #fff;
            font-size: 14px;
            line-height: 1.5;
        }
        * {
            box-sizing: border-box;
        }
        .invoice-wrapper {
            max-width: 800px;
            margin: 0 auto;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
            margin-bottom: 30px;
        }
        .company-info h1 {
            margin: 0 0 5px 0;
            font-size: 24px;
            font-weight: 700;
            letter-spacing: 1px;
        }
        .company-info p {
            margin: 0;
            color: #333;
        }
        .invoice-title {
            text-align: right;
        }
        .invoice-title h2 {
            margin: 0;
            font-size: 28px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .invoice-title p {
            margin: 5px 0 0 0;
            font-weight: 600;
        }
        .details {
            display: flex;
            justify-content: space-between;
            margin-bottom: 40px;
        }
        .billed-to, .order-meta {
            width: 45%;
        }
        .billed-to h3, .order-meta h3 {
            margin: 0 0 10px 0;
            font-size: 16px;
            color: #000;
            text-transform: uppercase;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .details p {
            margin: 5px 0;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background-color: #f9f9f9;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 12px;
            border-top: 1px solid #000;
            border-bottom: 1px solid #000;
        }
        .text-right {
            text-align: right;
        }
        .text-center {
            text-align: center;
        }
        .summary {
            width: 50%;
            float: right;
            border-top: 2px solid #000;
            padding-top: 15px;
        }
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 10px;
            font-size: 16px;
        }
        .summary-row.total {
            font-size: 20px;
            font-weight: 700;
            border-top: 1px solid #ccc;
            padding-top: 10px;
            margin-top: 10px;
        }
        .footer {
            clear: both;
            margin-top: 60px;
            text-align: center;
            font-size: 12px;
            color: #555;
            border-top: 1px dashed #ccc;
            padding-top: 20px;
        }
        /* Style điều chỉnh khi Máy in bắt đầu render */
        @media print {
            body { padding: 0; margin: 0; }
            .no-print { display: none !important; }
            @page { margin: 1cm; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="text-align:center; padding: 10px; background: #f0f0f0; margin-bottom: 20px; border-radius: 5px;">
        Nếu hộp thoại in không tự mở, hãy nhấn tổ hợp phím <strong>Ctrl + P</strong> (hoặc Cmd + P).
        <button onclick="window.print()" style="margin-left: 10px; padding: 5px 10px; cursor: pointer; border: 1px solid #333; background: #fff; border-radius: 3px;">In lại</button>
    </div>

    <div class="invoice-wrapper">
        <!-- HEADER CÔNG TY -->
        <div class="header">
            <div class="company-info">
                <h1>CYCLETRUST</h1>
                <p>Nền tảng mua bán xe đạp uy tín</p>
                <p>Hotline: 1900 1234</p>
                <p>Email: contact@cycletrust.com</p>
            </div>
            <div class="invoice-title">
                <h2>HÓA ĐƠN BÁN LẺ</h2>
                <p>Thẻ ID giao dịch: #<?= htmlspecialchars((string)$order['order_code']) ?></p>
            </div>
        </div>

        <!-- THÔNG TIN NGƯỜI MUA & NGÀY THÁNG -->
        <div class="details">
            <div class="billed-to">
                <h3>Người Mua (Billed To)</h3>
                <p><strong><?= mb_strtoupper(htmlspecialchars((string)$buyer_name)) ?></strong></p>
                <p>Điện thoại: <?= htmlspecialchars((string)$buyer_phone) ?></p>
                <p>Địa chỉ: <?= htmlspecialchars((string)$buyer_address) ?></p>
            </div>
            <div class="order-meta">
                <h3>Chi Chiết Đơn (Order Meta)</h3>
                <p><strong>Ngày lập:</strong> <?= date('d/m/Y', strtotime($order['created_at'])) ?></p>
                <p><strong>Giờ lập:</strong> <?= date('H:i', strtotime($order['created_at'])) ?></p>
                <p><strong>Phương thức TT:</strong> <?= strtoupper(htmlspecialchars((string)$order['payment_method'])) ?></p>
                <p><strong>Phân phối bởi Shop:</strong> <?= htmlspecialchars((string)($order['seller_username'] ?? 'Hệ thống')) ?></p>
            </div>
        </div>

        <!-- DANH SÁCH MẶT HÀNG -->
        <table>
            <thead>
                <tr>
                    <th style="width: 50%;">Tên Sản Phẩm / Dịch Vụ</th>
                    <th class="text-center" style="width: 15%;">Số Lượng</th>
                    <th class="text-right" style="width: 35%;">Đơn Giá (VNĐ)</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <strong style="font-size: 14px;"><?= htmlspecialchars((string)($order['bike_title'] ?? 'Sản phẩm xe đạp hệ thống')) ?></strong>
                    </td>
                    <td class="text-center">1</td>
                    <td class="text-right"><?= number_format((float)$order['total_price'], 0, ',', '.') ?>đ</td>
                </tr>
            </tbody>
        </table>

        <!-- TỔNG CỘNG -->
        <div class="summary">
            <div class="summary-row">
                <span>Tạm tính:</span>
                <span><?= number_format((float)$order['total_price'], 0, ',', '.') ?>đ</span>
            </div>
            <div class="summary-row">
                <span>Vận chuyển:</span>
                <span>0đ</span>
            </div>
            <div class="summary-row total">
                <span>TỔNG CỘNG:</span>
                <span><?= number_format((float)$order['total_price'], 0, ',', '.') ?>đ</span>
            </div>
        </div>

        <!-- FOOTER -->
        <div class="footer">
            <p><strong>CHÂN THÀNH CẢM ƠN QUÝ KHÁCH ĐÃ LỰA CHỌN CYCLETRUST</strong></p>
            <p>Hóa đơn này được xuất tự động từ Hệ thống phần mềm CycleTrust. <br>Mọi thắc mắc vui lòng liên hệ Hotline 1900 1234.</p>
        </div>
    </div>

    <!-- TỰ ĐỘNG IN -->
    <script>
        window.addEventListener('load', function() {
            // Mở hộp thoại in ngay khi cấu trúc DOM và CSS sẵn sàng
            setTimeout(function() {
                window.print();
            }, 500); // Thêm delay nhỏ để font kịp load 100%
        });
    </script>
</body>
</html>
