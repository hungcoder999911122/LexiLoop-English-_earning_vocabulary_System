# 📅 Kế hoạch Thực hiện Đồ án LexiLoop

Kế hoạch chi tiết theo các giai đoạn phát triển phần mềm, phân bổ công việc, cột mốc (Milestones) và kết quả bàn giao.

---

## 🚩 1. Các Cột mốc Chính (Project Milestones)

```text
Giai đoạn 1: Khởi tạo & Đặc tả Nghiệp vụ (Tuần 1 - Tuần 2)
  ├── Thu thập yêu cầu, định hình phương pháp Spaced Repetition (SRS)
  └── Viết tài liệu Core Business Rules V1.0 & IEEE 830 SRS

Giai đoạn 2: Thiết kế Hệ thống & Cơ sở Dữ liệu (Tuần 3 - Tuần 4)
  ├── Thiết kế mô hình dữ liệu ERD, chuẩn hóa 3NF, phân tách Progress/Logs
  └── Thiết kế kiến trúc tổng thể, Security Guard, API Endpoints

Giai đoạn 3: Phát triển Backend & Cơ sở Dữ liệu (Tuần 5 - Tuần 7)
  ├── Xây dựng CSDL MySQL, tạo Khóa ngoại, Chỉ mục & Views
  ├── Xây dựng Module Xác thực (Đăng ký, Đăng nhập, OTP Quên mật khẩu)
  └── Xây dựng API Quản lý Từ vựng, Flashcard SRS Engine, Quiz Engine

Giai đoạn 4: Phát triển Frontend & Tương tác (Tuần 8 - Tuần 10)
  ├── Xây dựng giao diện CSS 3D Flashcard, responsive layout đa thiết bị
  ├── Phát triển màn hình Quiz trắc nghiệm, hiển thị kết quả & giải thích
  └── Xây dựng Dashboard học viên và trang quản trị Admin

Giai đoạn 5: Tích hợp, Kiểm thử & Đóng gói Bàn giao (Tuần 11 - Tuần 12)
  ├── Kiểm thử chức năng (TC-01 -> TC-50), kiểm định bảo mật (IDOR, SQLi)
  ├── Chuẩn bị bộ tài liệu 9 phân vùng kỹ thuật chuẩn AURA
  └── Đóng gói Docker, Docker Compose, chuẩn bị hồ sơ bảo vệ đồ án
```

---

## 👥 2. Phân công Trách nhiệm & Vai trò

| Thành viên / Vai trò | Nhiệm vụ chính phụ trách | Sản phẩm bàn giao |
|---|---|---|
| **Trưởng nhóm / Kiến trúc sư** | Thiết kế kiến trúc hệ thống, thuật toán SRS, bảo mật RBAC | `03-architecture`, `auth_guard.php`, API Engine |
| **Kỹ sư Cơ sở Dữ liệu** | Thiết kế ERD, viết DDL/DML, chuẩn hóa 3NF, tối ưu Index | `04-database`, `db_LexiLoop.sql`, Migrations |
| **Kỹ sư Backend PHP** | Xây dựng API, xử lý nghiệp vụ Flashcard, Quiz, Admin CRUD | `pages/api/`, `pages/auth/`, `pages/admin/` |
| **Kỹ sư Frontend (UI/UX)** | Thiết kế giao diện HTML/CSS/JS, CSS 3D, biểu đồ thống kê | `pages/user/`, `CSS/`, `JS/` |
| **Đảm bảo Chất lượng (QA)** | Viết Test Cases, thực hiện kiểm thử tự động & thủ công | `07-testing`, `CHECKLIST.md`, Audit Reports |
