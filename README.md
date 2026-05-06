# CycleTrust — Mua bán xe đạp thể thao cũ

![PHP](https://img.shields.io/badge/PHP-Native-777BB4?style=for-the-badge&logo=php&logoColor=white)
![MySQL](https://img.shields.io/badge/MySQL-Database-4479A1?style=for-the-badge&logo=mysql&logoColor=white)
![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?style=for-the-badge&logo=bootstrap&logoColor=white)

**Đồ án UTH** — Nền tảng rao vặt xe đạp thể thao đã qua sử dụng, tập trung minh bạch thông tin và trải nghiệm người dùng.

## Mô tả

CycleTrust được xây dựng bằng **PHP thuần (Native)**, kết nối cơ sở dữ liệu qua **PDO** (prepared statements), giao diện **Bootstrap 5** và **Font Awesome**. Dự án gồm đăng ký / đăng nhập, đăng tin xe (upload ảnh), danh sách có phân trang, trang chi tiết và quản lý tin đăng cơ bản.

## Quy trình kiểm duyệt tin

Hệ thống hoạt động dựa trên quy trình kiểm duyệt nội dung nhằm đảm bảo chất lượng:
1. **Người dùng đăng tin**: Seller cung cấp thông tin, hình ảnh chi tiết của xe đạp.
2. **Admin xét duyệt**: Quản trị viên kiểm tra tính hợp lệ của tin đăng trên Dashboard.
3. **Hiển thị trang chủ**: Tin đăng sau khi được phê duyệt mới được phép hiển thị ở khu vực công khai để người mua tiếp cận.

## Tính năng Marketing

Để gia tăng giá trị hệ thống đồ án, dự án tích hợp thêm các công cụ hỗ trợ bán hàng:
- **Hệ thống Coupon**: Cho phép khởi tạo, quản lý và cấp phát mã giảm giá với số lượng/điều kiện tùy chỉnh.
- **Quản lý Banner**: Quản lý hình ảnh quảng cáo hiển thị ở trang chủ theo chiến dịch Marketing.

## Yêu cầu môi trường

- PHP 8.x (khuyến nghị 8.1+)
- MySQL hoặc MariaDB
- Apache với mod_rewrite (hoặc máy chủ tương đương), ví dụ **XAMPP**
- Extension PHP: `pdo_mysql`, `fileinfo` (khuyến nghị cho upload ảnh)

## Hướng dẫn cài đặt (cho Giảng viên / người chấm)

### 1. Clone mã nguồn

```bash
git clone <URL-repository-GitHub-của-sinh-viên>.git
cd CycleTrust
```

Thay `<URL-repository-GitHub-của-sinh-viên>` bằng URL thật (HTTPS hoặc SSH) do nhóm cung cấp.

### 2. Cấu hình web server

- Đặt thư mục dự án vào `htdocs` (XAMPP) hoặc document root tương ứng.
- Trỏ trình duyệt tới thư mục chứa `index.php`, ví dụ:  
  `http://localhost/CycleTrust/`

### 3. Tạo database và import dữ liệu mẫu

1. Mở **phpMyAdmin** (hoặc MySQL CLI).
2. Tạo database tên: **`cycle_trust`** (utf8mb4 nếu được hỏi).
3. Chọn database `cycle_trust` → tab **Import**.
4. Chọn file: **`database/database.sql`** trong thư mục dự án.
5. Thực hiện import và kiểm tra không báo lỗi.

File dump mặc định dùng tên database `cycle_trust` (thống nhất với `config/config.php`).

> **Lưu ý:** Nếu trong quá trình import bị thiếu tài khoản Admin, bạn có thể chạy đoạn SQL dự phòng sau để tạo quyền Quản trị cao nhất:
> ```sql
> -- Lệnh SQL dự phòng để tạo Admin (Mật khẩu hash của 111111)
> INSERT INTO users (username, email, password, role) 
> VALUES ('admin', 'admin@admin.com', '$2y$10$8W3Y6G8Q1j2zYyK/E/zSre.m7B6A7f9.X8LhX.6o3.Z.u9n6W5S1i', 'admin');
> ```

### 4. Cấu hình kết nối PHP (nếu cần)

Mở `config/config.php` và chỉnh cho đúng môi trường local:

- `BASE_URL` (ví dụ: `http://localhost/CycleTrust/`)
- `DB_HOST`, `DB_NAME`, `DB_USER`, `DB_PASS`

Mặc định thường dùng với XAMPP: `root`, mật khẩu rỗng.

### 5. Quyền thư mục upload

Đảm bảo thư mục `public/uploads/` (và các thư mục con như `bikes/`, `products/` nếu có) cho phép ghi khi chạy đăng tin có upload ảnh.

---

## Tài khoản mặc định trong file SQL mẫu

Trong **`database/database.sql`**, bảng `users` có **dữ liệu mẫu**:

| Username | Email           | Mật khẩu   | Vai trò (`role`) | Ghi chú |
|----------|-----------------|------------|------------------|---------|
| `admin`  | `admin@admin.com` | `111111`   | `admin`          | Mật khẩu Admin được thiết lập để demo nhanh, khuyến nghị đổi ngay sau khi bàn giao. |
| `pain1`  | `p@p.com`       | *ẩn*       | `user`           | Mật khẩu lưu dạng **bcrypt** trong dump. |

**Gợi ý cho giảng viên:**

- Đăng nhập bằng tài khoản Admin để truy cập khu vực Dashboard Quản trị.
- Dùng chức năng **Đăng ký** trên website để tạo user mới và test tính năng mua/bán.

## Cấu trúc thư mục (rút gọn)

```
CycleTrust/
├── config/           # Cấu hình, PDO
├── database/         # File SQL dump (import vào cycle_trust)
├── includes/         # Header, footer, hàm dùng chung
├── modules/          # Xử lý auth, bike, ...
├── pages/            # Các trang nội dung (router include)
├── public/           # CSS, JS, uploads (public)
├── index.php         # Điểm vào, router đơn giản
└── README.md
```

## Tác giả / môn học

Đồ án **UTH** — CycleTrust.

---

*Nếu có thắc mắc kỹ thuật khi chạy thử (404, lỗi PDO, import SQL), xin hãy kiểm tra `BASE_URL`, tên database `cycle_trust`, và log lỗi PHP/Apache.*
