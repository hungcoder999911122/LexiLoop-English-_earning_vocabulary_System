# 📑 Software Requirements Specification (SRS) - LexiLoop
**Chuẩn IEEE 830 - Hệ thống Học Từ vựng Tiếng Anh Thông minh Spaced Repetition**

---

## 1. Giới thiệu (Introduction)

### 1.1 Mục đích (Purpose)
Tài liệu Đặc tả Yêu cầu Phần mềm (SRS) này mô tả chi tiết các yêu cầu chức năng, phi chức năng, kiến trúc miền và ràng buộc kỹ thuật của hệ thống **LexiLoop**. Tài liệu đóng vai trò là hợp đồng kỹ thuật chuẩn cho quá trình phát triển, kiểm thử và nghiệm thu đồ án.

### 1.2 Phạm vi Hệ thống (Scope)
LexiLoop là nền tảng web hỗ trợ học từ vựng tiếng Anh theo phương pháp khoa học Spaced Repetition (Lặp lại ngắt quãng), giải quyết triệt để vấn đề "học trước quên sau" của người học ngôn ngữ.

---

## 2. Mô tả Tổng quan (Overall Description)

### 2.1 Các Tác nhân Hệ thống (User Personas)
1. **Guest (Khách vãng lai)**:
   - Người dùng chưa đăng ký hoặc chưa đăng nhập.
   - Được khám phá trang chủ, tra cứu từ điển công khai, trải nghiệm học thử Flashcard và Quiz giới hạn tối đa 5 từ.
2. **Learner / User (Học viên)**:
   - Người dùng đã đăng ký tài khoản và đăng nhập.
   - Quản lý kho từ vựng cá nhân, tạo bộ từ (Sets), học Flashcard với thuật toán SRS thích ứng, làm bài Quiz kiểm tra, theo dõi Dashboard tiến độ và lịch sử ôn tập.
3. **Administrator (Quản trị viên)**:
   - Người quản trị nội dung và hệ thống.
   - Quản lý danh mục chủ đề chuẩn, kho từ vựng hệ thống, quản lý tài khoản người dùng, xem thống kê hoạt động toàn sàn và cấu hình tham số hệ thống.

### 2.2 Ma trận Phân quyền & Giới hạn Truy cập (RBAC Matrix)

| Chức năng / Tài nguyên | Guest | User (Học viên) | Admin (Quản trị viên) |
|---|---|---|---|
| Xem danh mục & từ vựng hệ thống | ✅ Đầy đủ | ✅ Đầy đủ | ✅ Đầy đủ |
| Học thử Flashcard / Quiz (Tối đa 5 từ) | ✅ Giới hạn | ✅ Không giới hạn | ✅ Không giới hạn |
| Thêm từ vào danh sách Yêu thích (Favorites) | ❌ Không | ✅ Có | ❌ Không áp dụng |
| Tạo / Sửa / Xóa Chủ đề cá nhân của mình | ❌ Không | ✅ Có | ❌ Không |
| Tạo / Sửa / Xóa Từ vựng cá nhân của mình | ❌ Không | ✅ Có | ❌ Không |
| Xem / Sửa / Xóa dữ liệu cá nhân của User khác | ❌ Cấm | ❌ Cấm (Chặn IDOR) | ❌ Cấm |
| Tạo / Sửa / Xóa Chủ đề chuẩn hệ thống | ❌ Không | ❌ Không | ✅ Toàn quyền |
| Tạo / Sửa / Xóa Kho từ vựng chuẩn hệ thống | ❌ Không | ❌ Không | ✅ Toàn quyền |
| Quản lý tài khoản & Khóa/Mở người dùng | ❌ Không | ❌ Không | ✅ Toàn quyền |
| Xem báo cáo thống kê toàn hệ thống | ❌ Không | ❌ Không | ✅ Toàn quyền |

---

## 3. Yêu cầu Chức năng Chi tiết (Functional Requirements)

### Phân hệ 1: Xác thực & Tài khoản (Authentication & Profile)
- **FR-01 (Đăng ký)**: Cho phép người dùng đăng ký bằng Họ tên, Email, Mật khẩu. Bắt buộc kiểm tra định dạng email và mật khẩu tối thiểu 6 ký tự; ngăn chặn email trùng lặp.
- **FR-02 (Đăng nhập)**: Xác thực thông tin đăng nhập với mật khẩu mã hóa Bcrypt. Thiết lập Session và hỗ trợ ghi nhớ đăng nhập bằng Cookie an toàn.
- **FR-03 (Đăng xuất)**: Hủy bỏ Session trên máy chủ, xóa cookie token và chuyển hướng về trang đăng nhập.
- **FR-04 (Khôi phục mật khẩu)**: Tiếp nhận email yêu cầu, sinh mã OTP 6 số lưu vào bảng `password_resets` với thời hạn 15 phút, gửi mã xác nhận qua email.
- **FR-05 (Cài đặt tài khoản)**: Cho phép người dùng cập nhật thông tin cá nhân (Họ tên, ảnh đại diện) và đổi mật khẩu mới (yêu cầu xác thực mật khẩu cũ).

### Phân hệ 2: Học Từ vựng & Spaced Repetition (SRS Engine)
- **FR-06 (Học Flashcard)**: Giao diện thẻ 3D lật 2 mặt. Mặt trước hiển thị Từ vựng tiếng Anh, loại từ, phát âm IPA và audio phát âm. Mặt sau hiển thị Nghĩa tiếng Việt, giải thích chi tiết, câu ví dụ và hình ảnh minh họa.
- **FR-07 (Đánh giá ghi nhớ)**: Cung cấp 4 mức đánh giá:
  1. *Again (Lặp lại ngay)*: Không nhớ từ -> Reset chu kỳ ôn về 1 ngày.
  2. *Hard (Khó nhớ)*: Nhớ nhưng mất thời gian -> Tăng chu kỳ nhẹ (x1.2).
  3. *Good (Nhớ tốt)*: Trả lời chuẩn xác -> Nhân hệ số Ease Factor chuẩn.
  4. *Easy (Rất dễ)*: Nhớ tức thì -> Tăng Ease Factor và mở rộng chu kỳ ôn dài.
- **FR-08 (Lịch ôn tập tự động)**: Hệ thống tự động tính toán trường `next_review_at` trong bảng `user_vocab_progress`.
- **FR-09 (Ôn tập hôm nay)**: Tự động truy vấn tất cả từ vựng có `next_review_at <= NOW()` của người dùng hiện tại và xếp vào hàng đợi ôn tập mỗi ngày.

### Phân hệ 3: Kiểm tra Trắc nghiệm (Quiz Engine)
- **FR-10 (Sinh đề Quiz)**: Tự động sinh bài trắc nghiệm từ 5 đến 20 câu hỏi dựa trên chủ đề hoặc bộ từ vựng người học chọn.
- **FR-11 (Tạo phương án nhiễu)**: Tự động trích xuất 3 định nghĩa ngẫu nhiên từ các từ khác cùng chủ đề để tạo thành bộ 4 đáp án (1 đáp án đúng, 3 distractors).
- **FR-12 (Chấm điểm & Lưu vết)**: Ghi nhận điểm số, tổng thời gian làm bài, số câu đúng/sai vào bảng `quiz_results` và chi tiết từng lựa chọn vào `quiz_answer_details`.
- **FR-13 (Xem lại bài kiểm tra)**: Hiển thị giao diện chi tiết từng câu hỏi, đáp án học viên đã chọn, đáp án chính xác và lời giải thích.

### Phân hệ 4: Quản lý Từ vựng Cá nhân & Bộ từ (Personal Sets)
- **FR-14 (Chủ đề cá nhân)**: Học viên có thể tạo mới các chủ đề riêng biệt với trường `created_by = userID`.
- **FR-15 (Từ vựng cá nhân)**: Thêm mới từ vựng cá nhân vào các chủ đề do chính mình tạo ra.
- **FR-16 (Bộ từ vựng tùy chỉnh)**: Tạo các Vocabulary Sets tự chọn, thêm nhiều từ vựng từ các chủ đề khác nhau vào chung một bộ ôn tập.
- **FR-17 (Yêu thích)**: Bật/Tắt trạng thái yêu thích đối với bất kỳ từ vựng hệ thống hoặc cá nhân.

### Phân hệ 5: Quản trị Hệ thống (Admin Management)
- **FR-18 (Quản lý Chủ đề hệ thống)**: Thêm, sửa, xóa các chủ đề chuẩn (`created_by IS NULL`).
- **FR-19 (Quản lý Kho từ vựng chuẩn)**: CRUD từ vựng hệ thống, quản lý phát âm, phiên âm IPA, câu ví dụ và ảnh minh họa.
- **FR-20 (Quản lý Người dùng)**: Xem danh sách thành viên, trạng thái hoạt động, khóa tài khoản vi phạm.
- **FR-21 (Thống kê hệ thống)**: Báo cáo tổng số học viên, số từ vựng đã nạp, số lượt ôn tập và tỷ lệ hoàn thành bài quiz.

---

## 4. Yêu cầu Phi Chức năng (Non-Functional Requirements)

- **NFR-01 (Hiệu năng - Performance)**: Thời gian phản hồi trang chính < 1.0 giây; thời gian xử lý API lật thẻ Flashcard / nộp bài Quiz < 250ms.
- **NFR-02 (Bảo mật - Security)**:
  - 100% mật khẩu được băm bằng thuật toán `Bcrypt` với chi phí cost = 10.
  - Sử dụng cơ chế Parameter Binding (Prepared Statements) để ngăn chặn hoàn toàn tấn công SQL Injection.
  - Xử lý đầu ra bằng `htmlspecialchars` để chống Cross-Site Scripting (XSS).
  - Phân quyền nghiêm ngặt theo User Session để ngăn ngừa Insecure Direct Object Reference (IDOR).
- **NFR-03 (Toàn vẹn dữ liệu - Data Integrity)**:
  - Bắt buộc thiết lập ràng buộc khóa ngoại (Foreign Keys) cho toàn bộ mối quan hệ 1:N và N:M.
  - Ràng buộc duy nhất `UNIQUE(user_id, vocabulary_id)` trong bảng tiến độ học tập `user_vocab_progress`.
- **NFR-04 (Khả năng tương thích - Usability & Responsiveness)**:
  - Hoạt động mượt mà trên tất cả các trình duyệt phổ biến (Google Chrome, Firefox, Safari, Edge).
  - Giao diện tự co giãn thích ứng tốt từ màn hình Mobile (375px) đến Desktop 4K (3840px).
