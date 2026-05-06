<?php declare(strict_types=1); ?>

</main>

<footer class="ct-footer mt-5">
  <div class="container py-4 d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between gap-2">
    <div class="ct-footer__brand">
      <span class="ct-logo">CycleTrust</span>
      <div class="ct-footer__meta">Đồ án UTH - CycleTrust</div>
    </div>
    <div class="ct-footer__links text-muted">
      <span><i class="fa-regular fa-circle-check me-1"></i>Minh bạch kỹ thuật</span>
      <span class="mx-2 d-none d-md-inline">•</span>
      <span><i class="fa-solid fa-shield-halved me-1"></i>Giao dịch an toàn</span>
    </div>
  </div>
</footer>

<!-- Bootstrap bundle -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

<!-- SweetAlert2 -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php
// Khởi chạy thông báo hệ thống (Toast System)
if (!empty($_SESSION['success'])) {
    $msg = (string)$_SESSION['success'];
    unset($_SESSION['success']); // Hủy ngay session để không lặp lại khi F5
    $jsonMsg = json_encode($msg, JSON_UNESCAPED_UNICODE);
    echo "<script>
      Swal.fire({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        icon: 'success',
        title: {$jsonMsg}
      });
    </script>";
}

if (!empty($_SESSION['error'])) {
    $msg = (string)$_SESSION['error'];
    unset($_SESSION['error']); // Hủy ngay session để không lặp lại khi F5
    $jsonMsg = json_encode($msg, JSON_UNESCAPED_UNICODE);
    echo "<script>
      Swal.fire({
        toast: true,
        position: 'top-end',
        showConfirmButton: false,
        timer: 3000,
        timerProgressBar: true,
        icon: 'error',
        title: {$jsonMsg}
      });
    </script>";
}
?>

<?php if (isset($_SESSION['user_id'])): ?>
<!-- REAL-TIME JS: Đếm tin nhắn chưa đọc & Hiện animation -->
<script>
  (function() {
    const unreadBadge = document.getElementById('unreadMsgBadge');
    const chatIcon = document.getElementById('navChatIcon');
    
    async function fetchUnreadCount() {
        try {
            const res = await fetch('<?= BASE_URL ?>modules/chat/count_unread.php');
            const data = await res.json();
            if (data.status === 'success') {
                const count = parseInt(data.unread_count);
                if (count > 0) {
                    const currentString = unreadBadge.innerText;
                    const isNewMessage = (unreadBadge.style.display === 'none' || parseInt(currentString) !== count);
                    
                    if (isNewMessage) {
                        // Kích hoạt hiệu ứng tịnh tiến "shake" nhẹ báo hiệu có tin mới tới
                        if (chatIcon) {
                            chatIcon.classList.add('chat-shake');
                            setTimeout(() => {
                                chatIcon.classList.remove('chat-shake');
                            }, 600);
                        }
                    }
                    
                    unreadBadge.style.display = 'inline-block';
                    unreadBadge.innerText = count > 99 ? '99+' : count;
                } else {
                    unreadBadge.style.display = 'none';
                    unreadBadge.innerText = '0';
                }
            }
        } catch (e) {
             console.error('Lỗi khi tải log tin nhắn unread:', e);
        }
    }
    
    // Inject Dynamic Style class cho hiệu ứng rung chuông (Bell Shake Style)
    const style = document.createElement('style');
    style.innerHTML = `
      @keyframes shakeAndColor {
        0% { transform: rotate(0deg); }
        25% { transform: rotate(-25deg); color: #ff5722; }
        50% { transform: rotate(25deg); color: #ff5722; }
        75% { transform: rotate(-25deg); color: #ff5722;}
        100% { transform: rotate(0deg); }
      }
      .chat-shake i {
        animation: shakeAndColor 0.6s ease-in-out;
      }
    `;
    document.head.appendChild(style);

    // Kích hoạt Fetch ở lần load trang đầu tiên
    fetchUnreadCount();
    // Vòng lặp đếm tin nhắn ngầm (Background Sync) mỗi 5 giây
    setInterval(fetchUnreadCount, 5000);
  })();
</script>
<?php endif; ?>

<style>
.btn-favorite.active i {
    color: red !important;
}
</style>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const favoriteButtons = document.querySelectorAll('.btn-favorite');
    
    favoriteButtons.forEach(button => {
        button.addEventListener('click', async function(e) {
            e.preventDefault();
            e.stopPropagation(); // Ngăn chặn nổi bọt (ví dụ khi nhấn vào thẻ card)
            
            const bikeId = this.getAttribute('data-id');
            const icon = this.querySelector('i');
            
            if (!bikeId) return;

            try {
                // Tạo form data để gửi bằng POST
                const formData = new FormData();
                formData.append('bike_id', bikeId);

                const response = await fetch('<?= BASE_URL ?>modules/handle_favorite.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.status === 'unauthorized') {
                    // Nếu dùng SweetAlert2 (đã được include trong footer)
                    if (typeof Swal !== 'undefined') {
                        Swal.fire({
                            icon: 'warning',
                            title: 'Yêu cầu đăng nhập',
                            text: 'Bạn cần đăng nhập để thêm xe vào danh sách yêu thích.',
                            confirmButtonText: 'Đăng nhập ngay',
                            showCancelButton: true,
                            cancelButtonText: 'Đóng'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                window.location.href = '<?= BASE_URL ?>?page=login';
                            }
                        });
                    } else {
                        alert('Bạn cần đăng nhập để thêm xe vào danh sách yêu thích.');
                    }
                } else if (data.status === 'success') {
                    if (data.action === 'added') {
                        this.classList.add('active');
                    } else if (data.action === 'removed') {
                        this.classList.remove('active');
                    }
                } else {
                    console.error('Lỗi yêu thích:', data.message);
                }
            } catch (error) {
                console.error('Lỗi khi gọi API yêu thích:', error);
            }
        });
    });
});
</script>

</body>
</html>

