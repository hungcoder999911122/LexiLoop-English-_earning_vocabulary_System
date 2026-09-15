# 🔬 LexiLoop Source Code Deep Analysis Report

Báo cáo phân tích chuyên sâu toàn bộ mã nguồn hệ thống LexiLoop: Cấu trúc thư mục, luồng thực thi các tầng, chi tiết logic PHP/JS và kiến trúc module.

---

## 📁 1. Kiến trúc Tổ chức Mã nguồn

Hệ thống được tổ chức theo mô hình tách biệt rõ ràng giữa Giao diện (Presentation), Xử lý nghiệp vụ (Business Logic), API Services và Truy xuất Dữ liệu (Data Access):

```text
LexiLoop/
├── Connect.php                      # Kết nối cơ sở dữ liệu dùng chung (PDO/mysqli)
├── index.php                        # Điểm vào chính của ứng dụng (Router/Landing redirect)
│
├── includes/                        # Các thành phần tái sử dụng (Shared Components)
│   ├── auth_guard.php               # Middleware kiểm tra phiên đăng nhập & phân quyền RBAC
│   ├── topheader.php                # Thanh điều hướng trên cùng (Header & User Profile bar)
│   ├── sidebar_guest.php            # Thanh menu bên dành cho Khách chưa đăng nhập
│   ├── sidebar_user.php             # Thanh menu bên dành cho Học viên đã đăng nhập
│   └── guest_invite.php             # Modal/Banner khuyến khích đăng ký tài khoản
│
├── pages/                           # Các trang chức năng theo phân hệ
│   ├── auth/                        # Phân hệ Xác thực & Tài khoản
│   │   ├── A_DangNhap.php           # Trang & Xử lý Đăng nhập
│   │   ├── A_DangKy.php             # Trang & Xử lý Đăng ký tài khoản mới
│   │   ├── A_DangXuat.php           # Xử lý Hủy session & Đăng xuất
│   │   ├── A_QuenMatKhau.php        # Trang yêu cầu gửi OTP khôi phục mật khẩu
│   │   ├── A_Gui_otp.php            # Xử lý phát sinh mã OTP gửi qua email
│   │   ├── A_DatLaiMatKhau.php      # Trang nhập mật khẩu mới với OTP hợp lệ
│   │   └── A_Caidattaikhoan.php     # Trang cập nhật thông tin cá nhân & mật khẩu
│   │
│   ├── main/                        # Phân hệ Trang chủ & Tra cứu Công khai
│   │   ├── B_homepage.html          # Trang chủ giới thiệu nền tảng & lợi ích SRS
│   │   ├── B_DanhSachChuDe.php      # Xem danh mục các chủ đề từ vựng hệ thống
│   │   ├── B_DanhSachTuVung.php     # Xem danh sách từ vựng thuộc chủ đề
│   │   └── B_LexiLoopinfo.php       # Trang giới thiệu về dự án & phương pháp học
│   │
│   ├── demo/                        # Phân hệ Học thử Dành cho Guest
│   │   ├── B_Flashcarddemo.php      # Trải nghiệm học Flashcard 5 từ mẫu
│   │   ├── B_Quizdemo.php           # Trải nghiệm làm bài Quiz 5 câu trắc nghiệm
│   │   └── Learn_demo.php           # Khung điều hướng học thử
│   │
│   ├── user/                        # Phân hệ Dành cho Học viên (Đã đăng nhập)
│   │   ├── C_Dashboard_user.php     # Bảng điều khiển tổng quan tiến độ & thống kê
│   │   ├── C_Botuvung.php           # Quản lý các bộ từ vựng cá nhân (Sets)
│   │   ├── C_Tuvungcuatoi.php       # Quản lý kho từ vựng do người dùng tự tạo
│   │   ├── C_HocFlashcard.php       # Trình học Flashcard toàn diện kèm thuật toán SRS
│   │   ├── C_Quiz.php               # Trình làm bài kiểm tra trắc nghiệm tương tác
│   │   ├── C_KetquaQuiz.php         # Xem kết quả chi tiết & đáp án giải thích
│   │   ├── C_Ontaphomnay.php        # Lọc danh sách từ vựng cần ôn tập hôm nay theo SRS
│   │   ├── C_Lichsuontap.php        # Xem nhật ký lịch sử học tập và tỷ lệ nhớ từ
│   │   ├── C_Gocrenluyen.php        # Khu vực luyện tập tăng cường theo chủ đề
│   │   ├── C_Hosocanhan.php         # Xem và chỉnh sửa hồ sơ cá nhân
│   │   └── statistics.php           # Biểu đồ phân tích năng suất học tập
│   │
│   ├── admin/                       # Phân hệ Quản trị Hệ thống (Admin Only)
│   │   ├── D_Dashboard_admin.php    # Bảng điều khiển quản trị tổng thể
│   │   ├── D_Quanlychude.php        # Quản lý (CRUD) danh mục chủ đề chuẩn
│   │   ├── D_Quanlytuvung.php       # Quản lý (CRUD) kho từ vựng hệ thống
│   │   ├── D_Quanlynguoidung.php    # Quản lý tài khoản, trạng thái & vai trò
│   │   ├── D_Thongkehethong.php     # Xem báo cáo thống kê hoạt động toàn hệ thống
│   │   └── D_Caidathethong.php      # Cấu hình các thông số hệ thống & SRS
│   │
│   └── api/                         # Backend API Endpoints (JSON)
│       ├── save_flashcard_progress.php # API cập nhật tiến độ SRS sau khi lật thẻ
│       ├── save_quiz_result.php        # API chấm điểm & lưu kết quả bài Quiz
│       └── learning_attempt.php        # API lưu/khôi phục phiên học dở dang
│
├── JS/                              # Tầng xử lý Logic Frontend
│   ├── auth.js / validation.js      # Kiểm tra hợp lệ form dữ liệu người dùng
│   ├── C_HocFlashcard.js            # Hiệu ứng Flip 3D, điều hướng thẻ, gọi API SRS
│   ├── C_Quiz.js                    # Sinh câu hỏi trắc nghiệm, tính giờ, nộp bài
│   ├── C_Dashboard_user.js          # Render biểu đồ & widget thống kê
│   └── vocabulary.js / review.js    # Logic tra cứu, lọc danh mục & ôn tập
│
├── CSS/                             # Tầng Định kiểu Giao diện
│   ├── Style.css                    # Phong cách thiết kế chính (Color palette, Card, Typography)
│   ├── layout.css                   # Cấu trúc bố cục (Header, Sidebar, Content Grid)
│   ├── topheader.css                # Định kiểu thanh điều hướng trên cùng
│   └── responsive.css               # Media Queries tối ưu cho Mobile/Tablet
│
├── be/                              # Module Mở rộng Backend Java Spring Boot (Tùy chọn)
│   ├── pom.xml                      # Cấu hình Maven Dependencies
│   └── src/main/java/...            # Controllers, Entities, Repositories, Services
│
└── data/                            # Tài nguyên Cơ sở Dữ liệu
    ├── db_LexiLoop.sql              # Bản xuất cơ sở dữ liệu hoàn chỉnh (DDL + DML)
    └── migrations/                  # Các bản cập nhật schema CSDL theo phiên bản
```

---

## ⚙️ 2. Luồng Thực thi Chính (Execution Flows)

### 2.1 Luồng Học Flashcard & Cập nhật SRS
```text
[Học viên mở C_HocFlashcard.php]
       │
       ▼
[PHP truy vấn từ vựng cần học/ôn từ database]
       │
       ▼
[Giao diện Render Flashcard] ◄─── (JS xử lý lật thẻ 3D)
       │
       ▼
[Học viên bấm đánh giá: Again (1), Hard (2), Good (3), Easy (4)]
       │
       ▼
[JS gửi AJAX POST tới pages/api/save_flashcard_progress.php]
       │
       ▼
[PHP tính toán Interval mới, Ease Factor, next_review_at]
       │
       ▼
[Ghi đè user_vocab_progress (Trạng thái hiện tại)]
       │
       ▼
[Thêm bản ghi vào review_logs (Lịch sử ôn tập)]
       │
       ▼
[Trả về JSON {success: true, next_interval: X days}]
```

### 2.2 Luồng Làm bài Quiz & Chấm điểm
```text
[Học viên chọn Chủ đề/Bộ từ -> Mở C_Quiz.php]
       │
       ▼
[Hệ thống chọn ngẫu nhiên N từ vựng làm câu hỏi]
       │
       ▼
[Tạo 3 phương án nhiễu (Distractors) từ các từ cùng chủ đề]
       │
       ▼
[Học viên chọn đáp án từng câu và bấm Nộp bài]
       │
       ▼
[JS gửi mảng câu trả lời tới pages/api/save_quiz_result.php]
       │
       ▼
[PHP mở Transaction -> Chấm điểm -> Insert quiz_results]
       │
       ▼
[Insert chi tiết từng câu vào quiz_answer_details]
       │
       ▼
[Cập nhật tăng cường SRS cho các từ trả lời đúng/sai]
       │
       ▼
[Commit Transaction -> Redirect sang C_KetquaQuiz.php?result_id=X]
```
