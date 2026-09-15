# 📝 LexiLoop Changelog

Mọi thay đổi quan trọng trong kiến trúc, cơ sở dữ liệu, giao diện và logic nghiệp vụ của dự án LexiLoop được ghi nhận chi tiết tại đây.

---

## [v1.2.0] - 2026-09-15 (Phiên bản Bàn giao Hoàn chỉnh & Đồng bộ Docs)

### 🌟 Tính năng mới & Cải tiến
- **Tài liệu hóa chuẩn đồ án AURA**: Xây dựng toàn bộ hệ thống tài liệu 9 phân vùng kỹ thuật (`01-requirements`, `02-analysis`, `03-architecture`, `04-database`, `05-api`, `07-testing`, `08-deployment`, `08-report-prep`, `adr`).
- **Resumable Learning Attempts**: Bổ sung bảng `learning_attempts` cho phép người học tạm dừng và tiếp tục phiên học Flashcard/Quiz tại đúng từ đang dang dở.
- **Personal Vocabulary Sets**: Hỗ trợ gom nhóm từ vựng tự do với `vocabulary_sets` và `vocabulary_set_items`.
- **Quiz Detailed Logs**: Lưu vết từng đáp án học viên đã chọn với `quiz_answer_details` phục vụ tra cứu sau khi làm bài.

### 🛡️ Bảo mật & Tối ưu CSDL
- Cố định cơ chế phân quyền RBAC đa tầng (`auth_guard.php`), ngăn chặn hoàn toàn lỗi IDOR trên các tài nguyên cá nhân.
- Chuẩn hóa toàn bộ schema `db_LexiLoop.sql` đạt chuẩn 3NF, bổ sung chỉ mục tối ưu tìm kiếm (`idx_vocabulary_sets_user_id`, `idx_set_items_vocabulary_id`).

---

## [v1.1.0] - 2026-09-10 (Nâng cấp Thuật toán SRS & Dashboard Học viên)

### 🌟 Tính năng
- **SM-2 Adaptive SRS**: Triển khai thuật toán tính khoảng cách ôn tập lặp lại ngắt quãng (1 ngày -> 3 ngày -> 7 ngày -> 14 ngày -> 30 ngày) dựa trên điểm đánh giá ghi nhớ (Again / Hard / Good / Easy).
- **Trang Dashboard thống kê**: Bổ sung widget đếm từ cần ôn hôm nay, tỷ lệ nhớ từ và chuỗi ngày học liên tục (Streak).
- **Hệ thống Quản trị Admin**: Hoàn thiện module CRUD Chủ đề, Từ vựng, Quản lý tài khoản người dùng và xem thống kê tổng hợp.

---

## [v1.0.0] - 2026-09-01 (Phiên bản Khởi tạo Cốt lõi)

### 🌟 Tính năng
- Xác thực người dùng: Đăng ký, Đăng nhập, Đăng xuất, Lưu session, Ghi nhớ đăng nhập Cookie.
- Quản lý từ vựng và chủ đề: Tạo danh mục, tra cứu từ điển từ vựng kèm phát âm IPA và câu ví dụ.
- Học Flashcard tương tác CSS 3D lật thẻ.
- Kiểm tra Quiz trắc nghiệm 4 lựa chọn ngẫu nhiên.
