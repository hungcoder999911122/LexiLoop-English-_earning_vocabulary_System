# 📦 LexiLoop System Handover Guide

Sổ tay hướng dẫn bàn giao hệ thống, tài khoản quản trị mặc định, quy trình vận hành và kiểm tra sau bàn giao.

---

## 🔑 1. Tài khoản Truy cập Mặc định

| Vai trò (Role) | Email đăng nhập | Mật khẩu mặc định | Ghi chú quyền hạn |
|---|---|---|---|
| **Administrator** | `admin@lexiloop.com` | `Admin@123456` | Toàn quyền quản trị: Chủ đề hệ thống, Kho từ vựng, Người dùng, Thống kê |
| **User (Demo 1)** | `learner@lexiloop.com` | `User@123456` | Học viên đã có sẵn dữ liệu học tập, SRS, Favorites và bài Quiz |
| **User (Demo 2)** | `newbie@lexiloop.com` | `User@123456` | Học viên mới để test luồng học Flashcard và làm bài từ đầu |
| **Guest** | Không cần tài khoản | N/A | Truy cập trang chủ, xem chủ đề và học thử 5 từ |

---

## ⚙️ 2. Các Bước Khởi động & Vận hành Hệ thống

### 2.1 Môi trường XAMPP / WampServer (Windows)
1. Copy thư mục mã nguồn vào thư mục `C:/xampp/htdocs/LexiLoop`.
2. Khởi động **Apache** và **MySQL** trong XAMPP Control Panel.
3. Mở phpMyAdmin (`http://localhost/phpmyadmin`), tạo cơ sở dữ liệu `db_lexiloop`.
4. Import file `data/db_LexiLoop.sql` vào cơ sở dữ liệu vừa tạo.
5. Kiểm tra file `Connect.php`:
   ```php
   $host = "localhost";
   $user = "root";
   $pass = "";
   $dbname = "db_lexiloop";
   ```
6. Mở trình duyệt và truy cập: `http://localhost/LexiLoop/index.php`.

### 2.2 Môi trường Docker
1. Chạy lệnh: `docker-compose up -d --build`.
2. Truy cập hệ thống tại: `http://localhost:8080`.

---

## 📋 3. Danh mục Kiểm tra Khi Nhận Bàn giao (Handover Verification)

1. [x] Đăng nhập thành công với tài khoản Admin và tài khoản User.
2. [x] Học 1 thẻ Flashcard và chọn đánh giá ghi nhớ -> CSDL cập nhật `user_vocab_progress`.
3. [x] Làm bài Quiz 5 câu hỏi -> Nộp bài -> Hiển thị kết quả điểm và lưu vào `quiz_results`.
4. [x] Admin tạo mới 1 Topic và 1 Vocabulary -> Học viên thấy xuất hiện trên danh mục hệ thống.
5. [x] User tạo 1 Topic cá nhân -> Admin và User khác không nhìn thấy trong phần cá nhân của họ.
