# 🎯 LexiLoop MVP Scope & Product Roadmap

Tài liệu định nghĩa phạm vi sản phẩm khả dụng tối thiểu (Minimum Viable Product - MVP) và lộ trình phát triển mở rộng của LexiLoop.

---

## 📦 1. Phạm vi Sản phẩm Khả dụng Tối thiểu (MVP Scope)

### 1.1 Tính năng Cốt lõi (In-Scope for MVP)
- **Xác thực & Quản lý Tài khoản**: Đăng ký, Đăng nhập, Đăng xuất, Lưu phiên Session, Quên mật khẩu qua OTP.
- **Quản lý Chủ đề & Từ vựng**:
  - Chủ đề & Từ vựng hệ thống do Admin quản trị.
  - Chủ đề & Từ vựng cá nhân do từng Học viên tự quản lý.
  - Hỗ trợ phát âm IPA, câu ví dụ, hình ảnh và phân loại từ.
- **Phương pháp Lặp lại Ngắt quãng (SRS)**:
  - Tự động tính toán chu kỳ ôn tập (`interval_days`) dựa trên đánh giá người học (Again / Hard / Good / Easy).
  - Tự động lên lịch danh sách từ vựng cần ôn tập hôm nay (`next_review_at <= NOW()`).
- **Bộ máy Học Flashcard**:
  - Giao diện lật thẻ 3D trực quan, hỗ trợ phím tắt (`Space`, `1-4`).
- **Bộ máy Quiz Trắc nghiệm**:
  - Sinh đề thi 4 lựa chọn ngẫu nhiên, chấm điểm tự động và xem lại lời giải chi tiết.
- **Dashboard & Lịch sử Học tập**:
  - Thống kê số từ đã học, số từ cần ôn, chuỗi ngày học liên tục (Streak).
  - Ghi nhận chi tiết nhật ký ôn tập (`review_logs`) và kết quả quiz (`quiz_results`).
- **Phân quyền người dùng (RBAC)**:
  - Phân tách 3 vai trò: Guest (Chưa đăng nhập - học thử 5 từ), User (Học viên) và Admin (Quản trị viên).

### 1.2 Ngoài phạm vi MVP (Out-of-Scope / Future Roadmap)
- Hệ thống AI tự động sinh câu ví dụ theo ngữ cảnh và trình độ CEFR.
- Trợ lý ảo AI chấm điểm phát âm tiếng Anh qua Microphone (Speech-to-Text).
- Đồng bộ đa nền tảng thời gian thực với ứng dụng di động Flutter/React Native.
- Tính năng mạng xã hội: Chia sẻ bộ từ vựng cộng đồng, thi đấu PvP từ vựng đối kháng trực tuyến.
