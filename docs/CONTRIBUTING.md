# 🤝 LexiLoop Contribution & Code Style Guide

Tài liệu hướng dẫn quy chuẩn lập trình, đóng góp mã nguồn và kiểm soát chất lượng kỹ thuật trong dự án LexiLoop.

---

## 📐 1. Quy chuẩn Lập trình (Coding Standards)

### 1.1 Backend PHP
- **Chuẩn PSR-1 & PSR-12**: Tuân thủ chuẩn đặt tên, thụt lề 4 spaces, không dùng tab.
- **Biến và Hàm**: Tên biến và hàm dùng `camelCase` (ví dụ: `$userId`, `getVocabularyList()`).
- **Lớp (Class)**: Tên lớp dùng `PascalCase` (ví dụ: `VocabularyController`, `AuthService`).
- **An toàn dữ liệu**: Luôn sử dụng Prepared Statement (`PDO` hoặc `mysqli_stmt`) cho mọi câu truy vấn chứa biến người dùng.
- **Escape ngõ ra**: Bắt buộc dùng `htmlspecialchars($val, ENT_QUOTES, 'UTF-8')` khi in dữ liệu vào template HTML.

### 1.2 Database & SQL
- **Chuẩn đặt tên**: Tên bảng và tên cột dùng danh từ tiếng Anh, quy tắc `snake_case` (ví dụ: `user_vocab_progress`, `created_at`). Khóa chính ưu tiên `id` hoặc `userID`.
- **Khóa ngoại**: Luôn đặt tên ràng buộc rõ ràng (ví dụ: `fk_vocab_progress_user`).
- **Transactions**: Bắt buộc bọc các chuỗi thao tác ghi nhiều bảng liên quan trong `START TRANSACTION` ... `COMMIT` / `ROLLBACK`.

### 1.3 Frontend (HTML / CSS / JS)
- **CSS**: Phân tách rõ ràng giữa `layout.css`, `Style.css` và `responsive.css`. Sử dụng CSS Variables để quản lý theme màu sắc.
- **JavaScript**: Viết mã module hóa theo từng trang (`C_HocFlashcard.js`, `C_Quiz.js`), hạn chế biến toàn cục (Global Scope).
- **Responsive**: Hỗ trợ đầy đủ màn hình Desktop (>= 1200px), Tablet (768px - 1199px) và Mobile (< 768px).

---

## 🌿 2. Quy trình Quản lý Nhánh Git (Git Branching Flow)

```text
main (Production Ready / Bàn giao chính thức)
  └── staging / develop (Tích hợp và kiểm thử)
        ├── feature/flashcard-srs-engine
        ├── feature/quiz-resumable-attempts
        └── bugfix/auth-session-fix
```

- **Commit Message Convention**:
  - `feat: [tên tính năng]` Thêm tính năng mới
  - `fix: [tên lỗi]` Sửa lỗi
  - `docs: [tên tài liệu]` Cập nhật tài liệu
  - `refactor: [tên module]` Tái cấu trúc mã nguồn không đổi hành vi
