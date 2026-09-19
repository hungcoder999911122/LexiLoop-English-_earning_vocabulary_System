# 🎓 LexiLoop Viva Defense Checklist & Q&A

Sổ tay hướng dẫn chuẩn bị bảo vệ đồ án, kịch bản thuyết trình demo và danh sách câu hỏi - trả lời phản xạ nhanh trước Hội đồng Giám khảo.

---

## ⏱️ 1. Kịch bản Thuyết trình & Demo Chuẩn (10 Phút)

### Phút 1 - 2: Đặt vấn đề & Mục tiêu Đồ án
- Nêu vấn đề học từ vựng truyền thống hay quên (Đường cong lãng quên Hermann Ebbinghaus).
- Giới thiệu giải pháp LexiLoop: Hệ thống học từ vựng tiếng Anh ứng dụng **Spaced Repetition System (SRS)** và **Flashcard 3D** kết hợp bài kiểm tra **Quiz tương tác**.

### Phút 3 - 6: Trình diễn Chức năng Trực tiếp (Live Demo)
1. **Khách (Guest)**: Mở trang chủ -> Xem danh mục chủ đề -> Bấm **Học thử Flashcard** 5 từ -> Thử làm Quiz 5 câu -> Hệ thống mời đăng ký tài khoản.
2. **Học viên (User)**:
   - Đăng nhập -> Vào **Dashboard** (thấy số từ cần ôn hôm nay, chuỗi ngày học).
   - Vào **Ôn tập hôm nay** / **Học Flashcard**: Lật thẻ 3D xem nghĩa, phát âm IPA, câu ví dụ -> Chọn đánh giá ghi nhớ (Easy/Good/Hard/Again) -> Hệ thống tự động tính ngày ôn kế tiếp.
   - Vào **Quiz**: Làm bài trắc nghiệm -> Xem kết quả chi tiết từng câu.
   - Vào **Bộ từ vựng của tôi**: Tạo 1 Topic cá nhân và thêm từ vựng riêng.
3. **Quản trị viên (Admin)**: Đăng nhập quyền Admin -> Quản lý chủ đề & từ vựng hệ thống -> Xem thống kê toàn hệ thống.

### Phút 7 - 8: Điểm nhấn Kỹ thuật & CSDL
- Trình bày mô hình CSDL chuẩn hóa 3NF, phân tách trạng thái hiện tại (`user_vocab_progress`) và nhật ký lịch sử (`review_logs`).
- Trình bày cơ chế bảo mật: Hash mật khẩu Bcrypt, Prepared Statements chống SQLi, Auth Guard chống IDOR.

### Phút 9 - 10: Tổng kết & Trả lời Câu hỏi Hội đồng.

---

## ❓ 2. Bộ Câu hỏi & Trả lời Phản xạ Trọng tâm (Top Defense Q&A)

### Q1: Thuật toán Spaced Repetition (SRS) trong hệ thống hoạt động như thế nào?
> **Trả lời**: Hệ thống áp dụng thuật toán lặp lại ngắt quãng dựa trên nguyên lý SM-2. Mỗi từ vựng có một trạng thái học gồm `repetition_count`, `interval_days`, `ease_factor` và `next_review_at`.
> - Khi học viên chọn **Again (Sai/Quên)**: Reset `interval_days = 1` ngày, `repetition_count = 0`.
> - Khi học viên chọn **Hard**: `interval_days = Math.max(1, Math.round(interval * 1.2))`.
> - Khi học viên chọn **Good**: Lần 1 = 1 ngày, Lần 2 = 3 ngày, Lần 3+ = `interval * ease_factor`.
> - Khi học viên chọn **Easy**: Tăng `ease_factor` và khoảng cách ôn dài hơn (ví dụ x1.5 Good).
> Ngày ôn tập kế tiếp được tính bằng: `next_review_at = CURRENT_TIMESTAMP + interval_days`.

### Q2: Tại sao không tách hai bảng `system_topics` và `personal_topics` riêng biệt?
> **Trả lời**: Vì cấu trúc dữ liệu của chủ đề hệ thống và chủ đề cá nhân hoàn toàn đồng nhất (gồm tên chủ đề, mô tả, ngày tạo). Việc sử dụng chung bảng `Topics` và phân biệt quyền sở hữu bằng trường `created_by` (Nếu `created_by IS NULL` là của Hệ thống/Admin; nếu `created_by = userID` là của Học viên cá nhân) giúp:
> 1. Giảm thiểu dư thừa bảng và cấu trúc trùng lặp.
> 2. Dễ dàng kế thừa và tái sử dụng toàn bộ hàm xử lý, API và View.
> 3. Kiểm soát quyền chặt chẽ bằng chính sách RBAC ở tầng Backend.

### Q3: Tại sao lại tách `user_vocab_progress` và `review_logs` thành 2 bảng riêng?
> **Trả lời**: Đây là thiết kế tối ưu hiệu năng và quản lý dữ liệu:
> - `user_vocab_progress` chỉ lưu **1 bản ghi duy nhất cho mỗi cặp (user_id, vocabulary_id)** đại diện cho **trạng thái hiện tại** (để truy vấn cực nhanh khi lọc từ cần ôn hôm nay hoặc tính % ghi nhớ).
> - `review_logs` lưu **toàn bộ lịch sử các lần ôn tập** (1:N với progress) phục vụ cho việc vẽ biểu đồ tiến độ học tập theo thời gian mà không làm phình to hay ảnh hưởng tốc độ truy vấn trạng thái hiện tại.

### Q4: Hệ thống phòng chống lỗi bảo mật IDOR (Insecure Direct Object Reference) như thế nào?
> **Trả lời**: Ở mọi chức năng Cập nhật hoặc Xóa (Ví dụ sửa từ vựng, xóa chủ đề, xem lịch sử), Backend không chỉ nhận ID truyền lên từ Client mà luôn thực hiện kiểm tra quyền sở hữu:
> ```sql
> SELECT created_by FROM Topics WHERE topicID = ?
> ```
> Nếu `created_by != $_SESSION['user_id']` và người dùng không phải là Admin, Backend lập tức ngắt xử lý và trả về lỗi 403 Forbidden.
