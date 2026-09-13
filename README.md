# 📚 LexiLoop

> **Học thông minh hơn. Ghi nhớ lâu hơn.**

LexiLoop là hệ thống hỗ trợ học từ vựng tiếng Anh ứng dụng phương pháp **Spaced Repetition (Lặp lại ngắt quãng)**, giúp người học ghi nhớ từ vựng hiệu quả thông qua việc ôn tập đúng thời điểm.

Dự án được xây dựng nhằm mục đích học tập, nghiên cứu và thực hành phát triển Website theo mô hình **MVC** sử dụng **PHP** và **MySQL**.

---

# 📖 Giới thiệu

Việc học từ vựng theo phương pháp truyền thống thường khiến người học nhanh quên sau một khoảng thời gian ngắn.

LexiLoop được xây dựng nhằm giải quyết vấn đề này bằng cách:

- Quản lý bộ từ vựng cá nhân.
- Học từ mới bằng Flashcard.
- Ôn tập theo phương pháp Spaced Repetition.
- Kiểm tra khả năng ghi nhớ bằng Quiz.
- Theo dõi tiến độ học tập thông qua Dashboard.
- Tự động tạo lịch ôn tập phù hợp với từng người dùng.

---

# 🎯 Mục tiêu dự án

- Xây dựng một hệ thống học từ vựng trực tuyến hoàn chỉnh.
- Áp dụng phương pháp Spaced Repetition vào quá trình ôn tập.
- Thực hành xây dựng Website theo mô hình MVC.
- Vận dụng các kiến thức đã học về phát triển Web.
- Tạo ra một sản phẩm có tính ứng dụng thực tế.

---

# ✨ Chức năng chính

## 👤 Xác thực người dùng

- Đăng ký tài khoản
- Đăng nhập
- Đăng xuất
- Quản lý Session
- Ghi nhớ đăng nhập bằng Cookie

---

## 📖 Quản lý bộ từ vựng

Người dùng có thể:

- Thêm từ mới
- Chỉnh sửa từ
- Xóa từ
- Phân loại theo chủ đề

Thông tin của mỗi từ gồm:

- Từ tiếng Anh
- Nghĩa tiếng Việt
- Phiên âm (IPA)
- Ví dụ
- Ghi chú
- Hình ảnh minh họa (nếu có)

---

## 🃏 Học bằng Flashcard

Người dùng học từ thông qua Flashcard.

Mỗi Flashcard hiển thị:

- Từ tiếng Anh
- Nghĩa
- Phiên âm
- Ví dụ

---

## 🧠 Ôn tập theo Spaced Repetition

Hệ thống tự động xác định thời điểm cần ôn lại từng từ dựa trên kết quả học của người dùng.

Ví dụ:

Trả lời đúng

↓

Ôn lại sau **3 ngày**

Trả lời sai

↓

Ôn lại sau **1 ngày**

Ngày ôn tiếp theo sẽ được hệ thống lưu tự động trong cơ sở dữ liệu.

---

## 📝 Quiz

Hệ thống hỗ trợ kiểm tra từ vựng bằng:

- Trắc nghiệm
- Điền từ *(dự kiến mở rộng)*

Sau khi hoàn thành Quiz:

- Chấm điểm
- Cập nhật kết quả học
- Điều chỉnh lịch ôn tập

---

## 📊 Dashboard

Hiển thị thông tin học tập của người dùng:

- Tổng số từ đã học
- Số từ cần ôn hôm nay
- Tỷ lệ trả lời đúng
- Tiến độ học tập

---

## 📅 Lịch ôn tập

Website tự động tạo lịch ôn dựa trên kết quả học.

Người dùng **không cần tự lập lịch học**.

Hệ thống sẽ hiển thị:

- Số lượng từ cần ôn trong ngày
- Danh sách từ cần ôn
- Tiến trình hoàn thành

---

## 👨‍💻 Trang quản trị

Quản trị viên có thể:

- Quản lý người dùng
- Quản lý chủ đề
- Quản lý từ vựng
- Theo dõi thống kê hệ thống

---

# 🛠 Công nghệ sử dụng

## Front-end

- HTML5
- CSS3
- JavaScript
- jQuery

---

## Back-end

- PHP

---

## Cơ sở dữ liệu

- MySQL

---

## Kiến trúc hệ thống

- MVC (Model - View - Controller)

---

# 📋 Các yêu cầu kỹ thuật

Dự án áp dụng các kỹ thuật sau:

- ✅ Đăng ký / Đăng nhập
- ✅ CRUD dữ liệu
- ✅ Upload hình ảnh
- ✅ Rich Text Editor (CKEditor hoặc TinyMCE)
- ✅ Tìm kiếm
- ✅ Phân trang
- ✅ Responsive Web Design
- ✅ Kiểm tra dữ liệu đầu vào (Validation)
- ✅ Session & Cookie
- ✅ REST API
- ✅ SEO cơ bản (Friendly URL, Meta Tag,...)

---

# 📂 Cấu trúc dự án

```text
LexiLoop/

│

├── app/

│   ├── controllers/

│   ├── models/

│   ├── views/

│

├── public/

│   ├── css/

│   ├── js/

│   ├── images/

│   ├── uploads/

│

├── api/

├── config/

├── database/

├── routes/

├── README.md

└── index.php
```

---

# 🗄 Thiết kế cơ sở dữ liệu

| Bảng | Chức năng |
|------|-----------|
| Users | Lưu thông tin người dùng |
| Topics | Danh mục chủ đề |
| Vocabulary | Danh sách từ vựng |
| UserVocabulary | Theo dõi tiến độ học tập |
| QuizHistory | Lưu kết quả Quiz |
| Roles | Phân quyền người dùng |

---

# 🔐 Phân quyền hệ thống

## Khách (Guest)

Có thể:

- Xem trang chủ
- Đăng ký
- Đăng nhập

---

## Người dùng (User)

Có thể:

- Quản lý bộ từ vựng
- Học bằng Flashcard
- Làm Quiz
- Xem Dashboard
- Ôn tập theo lịch

---

## Quản trị viên (Administrator)

Có thể:

- Quản lý người dùng
- Quản lý chủ đề
- Quản lý từ vựng
- Quản lý toàn bộ hệ thống

---

# 🔄 Quy trình hoạt động

```text
Đăng ký / Đăng nhập
        │
        ▼
Quản lý bộ từ vựng
        │
        ▼
Học bằng Flashcard
        │
        ▼
Làm bài Quiz
        │
        ▼
Hệ thống đánh giá kết quả
        │
        ▼
Cập nhật lịch ôn tập
        │
        ▼
Dashboard cập nhật tiến độ
        │
        ▼
Tiếp tục quá trình học
```

---

# ⚙ Hướng dẫn cài đặt

### 1. Clone dự án

```bash
git clone https://github.com/your-username/LexiLoop.git
```

### 2. Đưa dự án vào thư mục

```text
xampp/htdocs/
```

### 3. Import cơ sở dữ liệu

```text
database/lexiloop.sql
```

### 4. Cấu hình kết nối cơ sở dữ liệu

```text
config/database.php
```

### 5. Khởi động

- Apache
- MySQL

### 6. Truy cập Website

```text
http://localhost/LexiLoop
```

---

# 🚀 Hướng phát triển

Trong tương lai, hệ thống có thể mở rộng thêm:

- Đồng bộ dữ liệu nhiều thiết bị.
- Phát âm từ vựng.
- AI tạo ví dụ cho từ mới.
- Chia sẻ bộ từ giữa người dùng.
- Chế độ giao diện tối (Dark Mode).
- Phiên bản ứng dụng di động.

---

# 👥 Thành viên thực hiện

| Họ và tên | MSSV | Vai trò |
|-----------|------|----------|
| ... | ... | Front-end |
| ... | ... | Back-end |
| ... | ... | Cơ sở dữ liệu |

---

# 📄 Giấy phép

Dự án được phát triển phục vụ mục đích học tập, nghiên cứu và thực hành phát triển Website.

---

# ❤️ Lời cảm ơn

Cảm ơn bạn đã quan tâm đến dự án **LexiLoop**.

Nếu bạn có góp ý hoặc phát hiện lỗi trong quá trình sử dụng, vui lòng tạo **Issue** hoặc **Pull Request** để cùng đóng góp và cải thiện dự án.
