<?php
/**
 * TRANG PHÒNG CHAT (Chat Room)
 * --------------------------------------------------------------------
 * Trang này cho phép người mua và người bán trao đổi trực tiếp với nhau.
 * Bên trái hiển thị danh sách tất cả những người dùng đã từng nhắn tin.
 * Bên phải là chi tiết nội dung cuộc trò chuyện hiện tại.
 */

declare(strict_types=1);

// 1. Kiểm tra đăng nhập: Người dùng phải là thành viên mới được vào chat
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . BASE_URL . '?page=login');
    exit;
}

/** @var PDO $conn */
$conn = require __DIR__ . '/../config/db.php';

// 2. Nhận các tham số từ URL
$myId = (int)$_SESSION['user_id'];
// receiver_id: ID của người đang trò chuyện cùng
$receiverId = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;
// bike_id: ID của xe đang được quan tâm (nếu có)
$bikeId = isset($_GET['bike_id']) ? (int)$_GET['bike_id'] : 0;

// --------------------------------------------------------------------
// BƯỚC 1: Lấy danh sách các người dùng đã từng chat (Sidebar trái)
// --------------------------------------------------------------------
// Dùng truy vấn lấy ra những người gửi (sender) hoặc nhận (receiver) tin nhắn với mình
$stmtChatList = $conn->prepare("
    SELECT u.id, u.username, u.avatar,
           -- Lấy tin nhắn cuối cùng để hiển thị đoạn preview
           (SELECT message FROM messages 
            WHERE (sender_id = u.id AND receiver_id = ?) 
               OR (sender_id = ? AND receiver_id = u.id) 
            ORDER BY created_at DESC LIMIT 1) as last_message,
           -- Lấy thời gian tin nhắn cuối cùng để sắp xếp danh sách
           (SELECT created_at FROM messages 
            WHERE (sender_id = u.id AND receiver_id = ?) 
               OR (sender_id = ? AND receiver_id = u.id) 
            ORDER BY created_at DESC LIMIT 1) as last_msg_time
    FROM users u
    WHERE u.id IN (
        SELECT sender_id FROM messages WHERE receiver_id = ?
        UNION
        SELECT receiver_id FROM messages WHERE sender_id = ?
    )
    ORDER BY last_msg_time DESC
");
$stmtChatList->execute([$myId, $myId, $myId, $myId, $myId, $myId]);
$chatUsers = $stmtChatList->fetchAll(PDO::FETCH_ASSOC);

// Nếu đang cố gắng chat với một user mới chưa có trong danh sách cũ -> Thêm họ tạm thời lên đầu
if ($receiverId > 0) {
    $found = false;
    foreach ($chatUsers as $cu) {
        if ($cu['id'] == $receiverId) {
            $found = true;
            break;
        }
    }
    if (!$found) {
        $stmtReceiver = $conn->prepare("SELECT id, username, avatar FROM users WHERE id = ?");
        $stmtReceiver->execute([$receiverId]);
        if ($newRc = $stmtReceiver->fetch(PDO::FETCH_ASSOC)) {
            $newRc['last_message'] = 'Bắt đầu trò chuyện...';
            $newRc['last_msg_time'] = date('Y-m-d H:i:s');
            array_unshift($chatUsers, $newRc); // Thêm lên đầu mảng
        } else {
            $receiverId = 0; // Không tồn tại user này
        }
    }
} else {
    // Nếu không truyền ID vào, lấy đại cuộc trò chuyện gần nhất làm mặc định
    if (!empty($chatUsers)) {
        $receiverId = (int)$chatUsers[0]['id'];
    }
}

// Lấy thông tin chi tiết của người nhận đang chat
$currentChatUser = null;
if ($receiverId > 0) {
    foreach ($chatUsers as $cu) {
        if ($cu['id'] == $receiverId) {
            $currentChatUser = $cu;
            break;
        }
    }
}

// Khi người dùng bấm vào cuộc trò chuyện này, toàn bộ tin nhắn trước đây với người đó được coi như đã xem
if ($receiverId > 0 && $currentChatUser) {
    $updateReadStmt = $conn->prepare("UPDATE messages SET is_read = 1 WHERE receiver_id = ? AND sender_id = ? AND is_read = 0");
    $updateReadStmt->execute([$myId, $receiverId]);
}

// --------------------------------------------------------------------
// BƯỚC 2: Xử lý hiển thị thông tin chiếc xe (Top header của chat)
// Lúc người dùng bấm nút "Chat với người bán" từ trang chi tiết xe,
// $_GET['bike_id'] sẽ được truyền vào. Chúng ta cần query lấy thông tin xe.
// --------------------------------------------------------------------
$bikeInfo = null;

// Nếu URL có truyền bike_id, ưu tiên truy vấn thông tin chiếc xe này
if ($bikeId > 0) {
    $stmtBike = $conn->prepare("SELECT id, title, price, image_url FROM bikes WHERE id = ?");
    $stmtBike->execute([$bikeId]);
    $bikeInfo = $stmtBike->fetch(PDO::FETCH_ASSOC);
}
// Nếu không truyền nhưng có lịch sử chat với nhau, tìm lại xem tin nhắn cũ có link đến xe nào không
if (!$bikeInfo && $receiverId > 0) {
    $stmtRecentBike = $conn->prepare("
        SELECT bike_id FROM messages 
        WHERE ((sender_id = ? AND receiver_id = ?) 
           OR (sender_id = ? AND receiver_id = ?))
          AND bike_id IS NOT NULL 
        ORDER BY created_at DESC LIMIT 1
    ");
    $stmtRecentBike->execute([$myId, $receiverId, $receiverId, $myId]);
    $recentBikeId = $stmtRecentBike->fetchColumn();
    
    if ($recentBikeId) {
        $bikeId = (int)$recentBikeId; // Cập nhật lại biến $bikeId để dùng trong JS
        $stmtBike = $conn->prepare("SELECT id, title, price, image_url FROM bikes WHERE id = ?");
        $stmtBike->execute([$bikeId]);
        $bikeInfo = $stmtBike->fetch(PDO::FETCH_ASSOC);
    }
}

// Hàm format giá thành tiền VND
function formatVND($amount) {
    return number_format((float)$amount, 0, ',', '.') . ' ₫';
}

// Hàm lấy ảnh avatar. Nếu user không có ảnh thì lấy ảnh tạo tự động theo tên
function getChatAvatar($rawAvatar, $username) {
    $raw = trim((string)$rawAvatar);
    if ($raw !== '') {
        return BASE_URL . 'public/uploads/avatars/' . rawurlencode($raw);
    }
    // API tạo avatar theo tên, kết hợp với tone màu Cam của dự án (FF5722)
    return 'https://ui-avatars.com/api/?name=' . urlencode((string)$username) . '&background=FF5722&color=fff&size=100&bold=true';
}

// Hàm định dạng đường dẫn hình ảnh cho chiếc xe
function getBikeThumb($raw) {
    $raw = trim((string)$raw);
    if ($raw === '') return BASE_URL . 'public/assets/images/default-bike.jpg';
    if (str_starts_with(strtolower($raw), 'http')) return $raw;
    return BASE_URL . 'public/uploads/bikes/' . rawurlencode($raw);
}
?>

<!-- ------------------------------------------------------------------
  CSS CỦA TRANG CHAT
  Sử dụng mô hình Flexbox kết hợp tone Cam - Đen như thiết kế
------------------------------------------------------------------- -->
<style>
    /* Tổng quan giao diện chat */
    .chat-wrapper {
        height: 75vh;
        min-height: 550px;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(0,0,0,0.08); /* Bóng đổ cho khung chat nổi bật */
        overflow: hidden;
        display: flex;
        border: 1px solid rgba(0,0,0,.08);
    }

    /* Khung danh sách Sidebar (bên trái) */
    .chat-sidebar {
        width: 340px;
        border-right: 1px solid rgba(0,0,0,.08);
        display: flex;
        flex-direction: column;
        background: #fafafa;
    }
    .chat-sidebar-header {
        padding: 20px;
        background: #212121; /* Nền tone Đen */
        color: #fff; /* Chữ trắng */
        border-bottom: 3px solid #ff5722; /* Họa tiết viền dưới màu cam */
    }
    .chat-list {
        flex: 1;
        overflow-y: auto;
    }
    
    /* Từng người dùng trong đoạn chat (bên trái) */
    .chat-list-item {
        display: flex;
        align-items: center;
        padding: 15px 20px;
        gap: 15px;
        border-bottom: 1px solid #dee2e6; /* Đường phân cách màu xám nhạt */
        cursor: pointer;
        text-decoration: none;
        color: inherit;
        transition: all 0.2s;
    }
    .chat-list-item:hover {
        background: #f1f3f5;
    }
    .chat-list-item.active {
        background: rgba(255, 87, 34, 0.08); /* Màu nền cam nhạt khi kích hoạt */
        border-left: 5px solid #ff5722;      /* Viền cam báo hiệu */
        padding-left: 15px;
    }
    .chat-avatar {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        object-fit: cover;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
    }
    .chat-list-name {
        font-weight: 700;
        margin-bottom: 2px;
        color: #212121; /* Text đen sẫm chuẩn tone */
        font-size: 1rem;
    }
    .chat-list-preview {
        font-size: 0.85rem;
        color: #6c757d;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    
    /* Cột chính hiển thị tin nhắn (bên phải) */
    .chat-main {
        flex: 1;
        display: flex;
        flex-direction: column;
        background: #fff;
    }
    .chat-main-header {
        padding: 15px 20px;
        border-bottom: 1px solid #e9ecef;
        display: flex;
        align-items: center;
        justify-content: space-between;
        background: #fff;
    }
    
    /* Box hiển thị thông tin chiếc xe đang được đề cập */
    .chat-bike-info {
        display: flex;
        align-items: center;
        padding: 10px 20px;
        background: #fff8f5; /* Cam rất nhạt */
        border-bottom: 1px solid #ffe8e0;
        gap: 15px;
    }
    .chat-bike-thumb {
        width: 60px;
        height: 45px;
        object-fit: cover;
        border-radius: 6px;
        border: 1px solid #ff5722; /* Viền xe màu cam */
    }
    .chat-bike-title {
        font-size: 0.95rem;
        font-weight: 700;
        color: #212121;
        margin-bottom: 2px;
    }
    .chat-bike-price {
        font-size: 0.9rem;
        font-weight: 700;
        color: #ff5722; /* Giá màu cam */
    }

    /* Box nội dung tin nhắn dùng Flexbox để dàn xếp từ trên xuống dưới */
    .chat-body {
        flex: 1;
        padding: 20px;
        overflow-y: auto;
        background: #f8f9fa; /* Nền xám cho cửa sổ chat */
        display: flex;
        flex-direction: column;
        gap: 15px;
    }
    
    /* Bong bóng Chat (Bubble) cơ bản */
    .chat-msg {
        max-width: 75%;
        display: flex;
        flex-direction: column;
    }
    .chat-msg-bubble {
        padding: 12px 18px;
        border-radius: 20px;
        font-size: 0.95rem;
        line-height: 1.5;
        word-wrap: break-word;
        box-shadow: 0 1px 3px rgba(0,0,0,0.05); /* Hiệu ứng nổi bọt */
    }
    
    /* Tin nhắn của bản thân (căn phải, nền cam, chữ trắng) */
    .chat-msg.me {
        align-self: flex-end; /* Căn phải màn hình flexbox */
    }
    .chat-msg.me .chat-msg-bubble {
        background: #ff5722; /* Cam */
        color: #fff; /* Trắng */
        border-bottom-right-radius: 4px; /* Vát đuôi tin nhắn */
    }
    
    /* Tin nhắn đối tác gửi (căn trái, nền xám nhạt, chữ đen) */
    .chat-msg.partner {
        align-self: flex-start; /* Căn trái màn hình flexbox */
    }
    .chat-msg.partner .chat-msg-bubble {
        background: #e9ecef; /* Xám nhạt */
        color: #212529; /* Đen */
        border-bottom-left-radius: 4px; /* Vát đuôi tin nhắn */
    }
    
    /* Text thể hiện thời gian nhắn */
    .chat-msg-time {
        font-size: 0.72rem;
        margin-top: 5px;
        color: #adb5bd;
    }
    .chat-msg.me .chat-msg-time {
        text-align: right;
    }
    
    /* Khu vực nhập input gửi tin */
    .chat-footer {
        padding: 15px 20px;
        border-top: 1px solid #dee2e6;
        background: #fff;
    }
    .chat-input-wrapper {
        display: flex;
        gap: 12px;
        align-items: center;
    }
    .chat-input {
        flex: 1;
        border: 2px solid #e9ecef;
        border-radius: 25px;
        padding: 12px 20px;
        outline: none;
        transition: border-color .2s;
    }
    .chat-input:focus {
        border-color: #ff5722; /* Viền cam khi nhấp vào input */
    }
    .chat-btn-send {
        background: #ff5722;
        color: #fff;
        border: none;
        width: 50px;
        height: 50px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: background .2s, transform .2s;
        font-size: 1.1rem;
    }
    .chat-btn-send:hover {
        background: #e64a19; /* Chuyển màu sậm khi di chuột */
        transform: scale(1.05); /* Hiệu ứng nhảy bật */
    }
</style>

<section class="py-4 py-lg-5">
    <div class="container">
        <!-- Tiêu đề trang -->
        <div class="mb-4 d-flex align-items-center">
            <h1 class="h3 fw-bold mb-0 text-dark"><i class="fa-brands fa-rocketchat text-primary me-2"></i>Tin Nhắn CycleTrust</h1>
        </div>
        
        <!-- Ô Bọc Khung Layout Chat Màn Giữa -->
        <div class="chat-wrapper">
            
            <!-- CỘT TRÁI: DANH SÁCH CUỘC TRÒ CHUYỆN -->
            <div class="chat-sidebar d-none d-md-flex">
                <div class="chat-sidebar-header">
                    <h5 class="mb-0 fw-bold"><i class="fa-solid fa-users me-2"></i>Trò chuyện gần đây</h5>
                </div>
                <div class="chat-list">
                    <?php if (empty($chatUsers)): ?>
                        <!-- Nếu tài khoản chưa nhắn ai thì hiện dòng chữ thông báo -->
                        <div class="text-muted text-center p-4">Bạn chưa trò chuyện với ai.<br>Hãy tìm xe và ấn "Chat với người bán".</div>
                    <?php else: ?>
                        <!-- Duyệt qua mảng chatUsers đã lấy ở đầu file bằng vòng lặp foreach -->
                        <?php foreach($chatUsers as $cu): 
                            // Nếu id người đang lặp bằng id receiver đang nhắm đến thì tô màu nền cam
                            $isActive = ($cu['id'] == $receiverId);
                            $avatar = getChatAvatar($cu['avatar'] ?? '', $cu['username']);
                        ?>
                            <a href="<?= BASE_URL ?>?page=chat_room&receiver_id=<?= $cu['id'] ?>" class="chat-list-item <?= $isActive ? 'active' : '' ?>">
                                <img src="<?= htmlspecialchars($avatar) ?>" class="chat-avatar" alt="Avatar">
                                <div class="w-100 flex-1 min-w-0" style="min-width: 0;">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <div class="chat-list-name"><?= htmlspecialchars($cu['username']) ?></div>
                                    </div>
                                    <div class="chat-list-preview">
                                        <?= htmlspecialchars(mb_strimwidth((string)$cu['last_message'], 0, 30, '...')) ?>
                                    </div>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>

                <!-- LƯU Ý AN TOÀN (SAFETY TIPS) DƯỚI ĐÁY SIDEBAR -->
                <div class="p-3" style="border-top: 1px solid rgba(0,0,0,.08); background: #fffcf8;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="fa-solid fa-shield-cat text-warning mt-1 fs-5"></i>
                        <div>
                            <div class="fw-bold fs-sm text-dark mb-1" style="font-size: 0.85rem;">Giao dịch an toàn</div>
                            <div class="text-muted" style="font-size: 0.75rem; line-height: 1.4;">
                                Cảnh báo: <strong class="text-dark">Không chuyển khoản đặt cọc</strong> trước khi gặp xem xe trực tiếp và kiểm tra kỹ lưỡng.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- CỘT PHẢI: CHI TIẾT CUỘC TRÒ CHUYỆN -->
            <div class="chat-main">
                <!-- Điều kiện để hiển thị khung phải: Phải có 1 ID receiver -->
                <?php if ($receiverId > 0 && $currentChatUser): ?>
                    <!-- HEADER CỦA CỬA SỔ PHẢI (Thông tin người đang chat) -->
                    <div class="chat-main-header">
                        <div class="d-flex align-items-center gap-3">
                            <img src="<?= htmlspecialchars(getChatAvatar($currentChatUser['avatar'] ?? '', $currentChatUser['username'])) ?>" class="chat-avatar" style="width:40px;height:40px;" alt="Avatar">
                            <div>
                                <h5 class="mb-0 fw-bold" style="color: #212121;"><?= htmlspecialchars($currentChatUser['username']) ?></h5>
                                <div class="text-success small fw-bold"><i class="fa-solid fa-circle text-success" style="font-size:8px;"></i> Đang hoạt động</div>
                            </div>
                        </div>
                        <a href="<?= BASE_URL ?>?page=shop" class="btn btn-outline-secondary btn-sm rounded-pill"><i class="fa-solid fa-store me-1"></i>Về cửa hàng</a>
                    </div>
                    
                    <!-- NẾU CÓ THÔNG TIN CHIẾC XE: IN BOX CHIẾC XE NÀY RA -->
                    <?php if ($bikeInfo): ?>
                        <div class="chat-bike-info">
                            <img src="<?= htmlspecialchars(getBikeThumb($bikeInfo['image_url'])) ?>" class="chat-bike-thumb" alt="Bike">
                            <div>
                                <div class="chat-bike-title"><?= htmlspecialchars($bikeInfo['title']) ?></div>
                                <div class="chat-bike-price"><?= htmlspecialchars(formatVND($bikeInfo['price'])) ?></div>
                            </div>
                            <div class="ms-auto">
                                <a href="<?= BASE_URL ?>?page=bike-detail&id=<?= $bikeInfo['id'] ?>" class="btn btn-sm btn-outline-primary" style="font-size: 0.8rem;">Xem chi tiết xe</a>
                            </div>
                        </div>
                    <?php endif; ?>
                    
                    <!-- THÂN CỬA SỔ CHAT chứa tất cả dòng tin nhắn -->
                    <!-- Tại đây gán id="chatBody" để dùng cho javascript JS Scroll to bottom -->
                    <div class="chat-body" id="chatBody">
                        <div class="text-center w-100 my-auto pb-5 text-muted">
                            <i class="fa-solid fa-circle-notch fa-spin fs-2 mb-3 text-secondary"></i>
                            <div>Đang tải kết nối...</div>
                        </div>
                        <!-- Cấu trúc HTML của tin nhắn sẽ được render tự động nhờ Javascript Fetch và nhét đè vào vị trí này -->
                    </div>
                    
                    <!-- FORM GỬI TIN NHẮN Ở ĐÁY -->
                    <div class="chat-footer">
                        <!-- action nhắm tới đúng API của ta để trong tương lai có thể không cần JS vẫn hoạt động -->
                        <form id="chatForm" class="chat-input-wrapper" method="POST" action="<?= BASE_URL ?>modules/chat/send_msg_act.php">
                            <!-- Input ẩn lưu giá trị để submit lên -->
                            <input type="hidden" id="receiver_id" name="receiver_id" value="<?= $receiverId ?>">
                            <input type="hidden" id="bike_id" name="bike_id" value="<?= $bikeId ?>">
                            
                            <!-- Box văn bản thật sự của người nhập -->
                            <input type="text" id="chatInputMesg" name="message" class="chat-input" placeholder="Viết tin nhắn gửi cho <?= htmlspecialchars($currentChatUser['username']) ?>..." autocomplete="off">
                            <button type="submit" class="chat-btn-send" title="Gửi tin nhắn (Enter)">
                                <i class="fa-solid fa-paper-plane"></i>
                            </button>
                        </form>
                    </div>
                    
                <?php else: ?>
                    <!-- Lúc chưa chọn đoạn hội thoại nào ở bên trái -->
                    <div class="d-flex flex-column align-items-center justify-content-center h-100 text-muted" style="background: rgba(33,33,33,0.02)">
                        <i class="fa-brands fa-rocketchat fa-4x mb-3" style="color: #dee2e6;"></i>
                        <h5 class="fw-bold text-dark">Hãy chọn một đoạn hội thoại.</h5>
                        <p>Nhấp vào người mua / người bán bên trái để xem và bắt đầu!</p>
                    </div>
                <?php endif; ?>
            </div>
            
        </div>
    </div>
</section>

<!-- ------------------------------------------------------------------
  JAVASCRIPT TƯƠNG TÁC: FETCH / GỬI VÀ SCROLL TO BOTTOM 
------------------------------------------------------------------- -->
<?php if ($receiverId > 0 && $currentChatUser): ?>
<script>
    // 1. Khai báo các đối tượng DOM element 
    const chatBody = document.getElementById('chatBody');
    const chatForm = document.getElementById('chatForm');
    const chatInput = document.getElementById('chatInputMesg');
    const rxId = document.getElementById('receiver_id').value;
    const bkId = document.getElementById('bike_id').value;
    
    let lastMsgId = 0; // Biến đánh dấu giữ thông tin mốc id tin nhắn cao nhất
    let isFetching = false; // Phù hiệu khoá tranh chấp load mạng

    // 2. Logic tự động Scroll xuống đáy - được gọi khi có tin mới
    function scrollToBottom() {
        chatBody.scrollTop = chatBody.scrollHeight;
    }

    // 3. Hàm lấy các tin nhắn trực tiếp bằng đường dẫn API get_msg_act.php
    async function loadMessages() {
        if(isFetching) return;
        isFetching = true;
        
        try {
            // Chuẩn bị form giả lập gửi POST data để get messages
            const formData = new FormData();
            formData.append('receiver_id', rxId);
            formData.append('last_msg_id', lastMsgId);
            
            // Gọi FETCH API (Async/Await pattern)
            const response = await fetch('<?= BASE_URL ?>modules/chat/get_msg_act.php', {
                method: 'POST',
                body: formData
            });
            const data = await response.json();
            
            // Có dữ liệu thành công -> nhét vào chatBodyHTML
            if (data.status === 'success' && data.messages.length > 0) {
                // Nếu đây là lần load gốc đầu tiên thì dọn trạng thái spin đi
                if (lastMsgId == 0) {
                    chatBody.innerHTML = ''; 
                }
                
                // Toán tử kiểm tra xem người dùng có đang cố tình cuộn chuột ở giữa không.
                // Nếu họ ghim đáy thì tự động cuốn tiếp.
                let shouldScrollToBottom = (chatBody.scrollTop + chatBody.clientHeight) >= (chatBody.scrollHeight - 60);
                if (lastMsgId == 0) shouldScrollToBottom = true; 
                
                // Vòng lặp bốc dữ liệu thả vào layout bong bóng
                data.messages.forEach(msg => {
                    const isMyMsg = (msg.sender_id == <?= $myId ?>); // boolean
                    
                    // Tạo Element div -> nhét các lớp class vào
                    const div = document.createElement('div');
                    // Gắn class tương ứng với "Tin của tôi" (nền cam, phải) và "Tin người đó" (nền xám, trái)
                    div.className = `chat-msg ${isMyMsg ? 'me' : 'partner'}`;
                    
                    // Chèn nội dung, thời gian
                    div.innerHTML = `
                        <div class="chat-msg-bubble">${escapeHtml(msg.message)}</div>
                        <div class="chat-msg-time">${formatTime(msg.created_at)}</div>
                    `;
                    chatBody.appendChild(div);
                    
                    // Nâng chóp Message ID
                    lastMsgId = Math.max(lastMsgId, msg.id);
                });

                // Tự động scroll đáy cực gọn
                if (shouldScrollToBottom) {
                    scrollToBottom();
                }
            } else if (lastMsgId == 0 && data.messages && data.messages.length === 0) {
                // Trường hợp 0 tin nhắn
                chatBody.innerHTML = '<div class="text-center text-muted w-100 my-auto">Chưa có dòng tin nhắn nào.<br>Bắt đầu trao đổi bằng cách gõ tin nhắn xuống dưới nhé!</div>';
            }

        } catch (err) {
            console.error('Lỗi lấy dữ liệu tin nhắn qua Ajax:', err);
        } finally {
            isFetching = false;
        }
    }

    // 4. Sự kiện ấn Submit form -> Thay thế bởi Javascript fetch tránh việc f5 page
    chatForm.addEventListener('submit', async function(e) {
        e.preventDefault(); // CHẶN LẠI HOẠT ĐỘNG REDIRECT TỰ ĐỘNG CỦA DO TRÌNH DUYỆT FORM GÂY RA
        
        const msgText = chatInput.value.trim();
        if (!msgText) return;
        
        // Làm trống ô input liền để cho phép người dùng đánh dòng mới
        chatInput.value = ''; 
        
        try {
            // Định hình dữ liệu form qua biến FormData
            const fd = new FormData();
            fd.append('receiver_id', rxId);
            fd.append('bike_id', bkId);
            fd.append('message', msgText);
            
            // Xả Data vào file send_msg_act.php chuyên xử lí lưu log
            const res = await fetch(this.action, {
                method: 'POST',
                body: fd
            });
            
            const d = await res.json();
            if (d.status === 'success') {
                // Nếu Database đã confirm thành công. Mình ra lệnh đọc Database ngay 1 lần để nhét tin nhắn của bản thân lên màn luôn
                await loadMessages(); 
                // Khi gửi tin nhắn mình phải auto cuốn xuống mạnh
                scrollToBottom();
            } else {
                alert("Không thể gửi được! Lỗi: " + d.message);
            }
        } catch (err) {
            console.error('Lưu dữ liệu fail thủng mạng r:', err);
        }
    });

    // Hàm chống XSS chèn lệnh Javascript độc bậy vào ô tin nhắn
    function escapeHtml(text) {
        const map = { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;' };
        return text.replace(/[&<>"']/g, function(m) { return map[m]; });
    }

    // Hàm chuyển datetime raw thành thời gian rõ nghĩa VD: 18:30 
    function formatTime(datetimeRaw) {
        const d = new Date(datetimeRaw);
        return d.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}); 
    }

    // Kích hoạt load liền message vào lúc trang load DOM xong
    loadMessages();
    
    // Đặt vòng lặp vô tận quét và pull dữ liệu với Delay 2,5 giây !
    setInterval(loadMessages, 2500);

</script>
<?php endif; ?>
