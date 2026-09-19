# 👤 User Requirements & Acceptance Criteria - LexiLoop

Tập hợp các User Stories (Câu chuyện người dùng) và Tiêu chuẩn Nghiệm thu (Acceptance Criteria) theo 3 nhóm tác nhân: Khách (Guest), Học viên (User) và Quản trị viên (Admin).

---

## 🌟 1. User Stories Dành cho Khách (Guest)

### US-G01: Khám phá Chủ đề Công khai
- **Là một** Khách vãng lai chưa có tài khoản,
- **Tôi muốn** xem danh sách các chủ đề từ vựng tiếng Anh trên trang chủ,
- **Để** đánh giá xem nội dung của LexiLoop có phù hợp với nhu cầu học tập của tôi hay không.
- **Tiêu chuẩn nghiệm thu (Acceptance Criteria)**:
  1. Hiển thị danh sách các chủ đề hệ thống kèm số lượng từ vựng trong mỗi chủ đề.
  2. Bấm vào chủ đề sẽ xem được danh sách từ vựng (từ tiếng Anh, phiên âm, nghĩa tiếng Việt).

### US-G02: Trải nghiệm Học thử Giới hạn (5 từ)
- **Là một** Khách vãng lai,
- **Tôi muốn** học thử Flashcard và làm thử 1 bài Quiz ngắn 5 câu hỏi,
- **Để** trải nghiệm phương pháp học và hiệu ứng tương tác của trang web.
- **Tiêu chuẩn nghiệm thu**:
  1. Cho phép lật thẻ Flashcard tối đa 5 từ mẫu.
  2. Khi hoàn thành 5 từ, hệ thống hiển thị modal mời đăng ký tài khoản miễn phí để lưu lại tiến trình học.

---

## 🎓 2. User Stories Dành cho Học viên (User / Learner)

### US-U01: Học Flashcard với Thuật toán Lặp lại Ngắt quãng (SRS)
- **Là một** Học viên đã đăng nhập,
- **Tôi muốn** học các thẻ từ vựng với giao diện lật thẻ 3D và tự đánh giá mức độ ghi nhớ (Again, Hard, Good, Easy),
- **Để** hệ thống tự động lên lịch nhắc nhở tôi ôn tập lại đúng thời điểm chuẩn bị quên.
- **Tiêu chuẩn nghiệm thu**:
  1. Thẻ lật mượt mà khi bấm chuột hoặc bấm phím `Space`.
  2. Sau khi bấm đánh giá ghi nhớ, hệ thống gửi AJAX cập nhật CSDL ngay lập tức mà không cần tải lại toàn trang.
  3. Từ vựng được chuyển tự động sang ngày ôn tập tương ứng với công thức SRS.

### US-U02: Làm bài Kiểm tra Quiz & Xem Lời giải Chi tiết
- **Là một** Học viên,
- **Tôi muốn** làm các bài kiểm tra trắc nghiệm 4 đáp án theo chủ đề tôi đang học,
- **Để** đánh giá chính xác mức độ nhớ từ và phản xạ nghĩa của mình.
- **Tiêu chuẩn nghiệm thu**:
  1. Bộ câu hỏi được tạo ngẫu nhiên, không bị trùng lặp đáp án trong cùng 1 câu.
  2. Sau khi nộp bài, hiển thị ngay điểm số, thời gian làm và danh sách đáp án đúng/sai kèm giải thích.

### US-U03: Tự Quản lý Kho Từ vựng Cá nhân
- **Là một** Học viên,
- **Tôi muốn** tự thêm các từ vựng mới mà tôi gặp trong công việc hoặc đời sống vào chủ đề riêng của tôi,
- **Để** tôi có thể ôn tập từ vựng cá nhân hóa theo đúng mục tiêu của mình.
- **Tiêu chuẩn nghiệm thu**:
  1. Học viên có thể tạo Chủ đề cá nhân (`created_by = userID`).
  2. Học viên có toàn quyền Thêm, Sửa, Xóa từ vựng trong chủ đề cá nhân của mình.
  3. Người dùng khác và Khách tuyệt đối không thể xem hoặc can thiệp vào kho từ cá nhân này.

---

## 🛠️ 3. User Stories Dành cho Quản trị viên (Admin)

### US-A01: Quản trị Kho Dữ liệu Chuẩn Hệ thống
- **Là một** Quản trị viên,
- **Tôi muốn** Thêm, Sửa, Xóa các chủ đề và từ vựng chuẩn của hệ thống,
- **Để** cung cấp nguồn học liệu chuẩn xác, chất lượng cao cho toàn bộ học viên.
- **Tiêu chuẩn nghiệm thu**:
  1. Admin thao tác tại phân hệ quản trị `pages/admin/`.
  2. Cho phép upload ảnh minh họa và cấu hình phát âm IPA chuẩn.
  3. Dữ liệu hệ thống có `created_by IS NULL`.

### US-A02: Giám sát Thống kê Hoạt động Toàn sàn
- **Là một** Quản trị viên,
- **Tôi muốn** theo dõi biểu đồ tăng trưởng người dùng, tổng số lượt học Flashcard và số bài Quiz hoàn thành,
- **Để** nắm bắt hiệu quả vận hành và mức độ gắn kết của học viên với nền tảng.
- **Tiêu chuẩn nghiệm thu**:
  1. Hiển thị Dashboard với các chỉ số KPI trực quan.
  2. Biểu đồ trực quan theo ngày/tuần/tháng.
