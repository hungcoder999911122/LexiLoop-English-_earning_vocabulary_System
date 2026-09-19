# ✅ LexiLoop - Master Quality & Delivery Checklist

Bảng kiểm tra toàn diện chất lượng sản phẩm, mức độ hoàn thiện các yêu cầu chức năng (FR), yêu cầu phi chức năng (NFR), chuẩn cơ sở dữ liệu và bảo mật.

---

## 📌 1. Bảng Kiểm tra Yêu cầu Chức năng (FR-01 → FR-30)

| Mã FR | Tên chức năng | Chi tiết nghiệp vụ | Trạng thái | Ghi chú kỹ thuật |
|---|---|---|---|---|
| **FR-01** | Đăng ký tài khoản | Nhập họ tên, email, mật khẩu; kiểm tra trùng lặp email | ✅ Hoàn thành | Validation JS + PHP, hash bcrypt |
| **FR-02** | Đăng nhập hệ thống | Xác thực email/password; tạo session; hỗ trợ Remember Me | ✅ Hoàn thành | `user_login_sessions` / Cookie token |
| **FR-03** | Đăng xuất an toàn | Hủy session máy chủ, xóa cookie token | ✅ Hoàn thành | `A_DangXuat.php` |
| **FR-04** | Quên mật khẩu & OTP | Gửi mã OTP xác nhận đặt lại mật khẩu qua email | ✅ Hoàn thành | `password_resets` table |
| **FR-05** | Đổi mật khẩu / Profile | Cập nhật họ tên, ảnh đại diện, đổi mật khẩu mới | ✅ Hoàn thành | `A_Caidattaikhoan.php`, `C_Hosocanhan.php` |
| **FR-06** | Xem Topic hệ thống | Khách và học viên xem danh sách các chủ đề hệ thống | ✅ Hoàn thành | `B_DanhSachChuDe.php` |
| **FR-07** | Xem Từ vựng hệ thống | Xem danh sách từ theo chủ đề, tra nghĩa, phát âm | ✅ Hoàn thành | `B_DanhSachTuVung.php`, View `tu_vung` |
| **FR-08** | Học thử cho Guest | Khách chưa đăng nhập được học thử tối đa 5 từ | ✅ Hoàn thành | `B_Flashcarddemo.php`, `B_Quizdemo.php` |
| **FR-09** | Học Flashcard tương tác | Lật thẻ 2 mặt (Từ vựng ↔ Nghĩa, IPA, ví dụ, audio) | ✅ Hoàn thành | `C_HocFlashcard.php`, CSS 3D Flip |
| **FR-10** | Đánh giá mức độ ghi nhớ | Người học chọn Easy / Good / Hard / Again | ✅ Hoàn thành | Tính toán SRS Interval & Ease Factor |
| **FR-11** | Bài kiểm tra Quiz trắc nghiệm | Sinh câu hỏi ngẫu nhiên 4 lựa chọn (1 đúng, 3 sai) | ✅ Hoàn thành | `C_Quiz.php`, tạo distractors tự động |
| **FR-12** | Lưu kết quả Quiz | Chấm điểm, ghi nhận số câu đúng/sai, thời gian làm | ✅ Hoàn thành | `quiz_results`, `quiz_answer_details` |
| **FR-13** | Xem lại chi tiết Quiz | Hiển thị bảng đáp án đã chọn và đáp án chính xác | ✅ Hoàn thành | `C_KetquaQuiz.php` |
| **FR-14** | Thuật toán Lặp lại ngắt quãng | Tự động tính ngày ôn tập tiếp theo (`next_review_at`) | ✅ Hoàn thành | `user_vocab_progress` |
| **FR-15** | Ôn tập hôm nay | Lọc danh sách từ vựng đến hạn ôn (`next_review_at <= NOW`) | ✅ Hoàn thành | `C_Ontaphomnay.php` |
| **FR-16** | Quản lý Từ vựng yêu thích | Thêm/Xóa từ vào danh sách Favorites | ✅ Hoàn thành | `favorites` table (Unique user + vocab) |
| **FR-17** | Tạo Topic cá nhân | Học viên tự tạo chủ đề riêng của bản thân | ✅ Hoàn thành | `created_by = userID` |
| **FR-18** | CRUD Từ vựng cá nhân | Thêm, sửa, xóa từ vựng trong chủ đề cá nhân | ✅ Hoàn thành | `C_Tuvungcuatoi.php` |
| **FR-19** | Quản lý Bộ từ vựng (Sets) | Gom nhóm từ vựng thành các Custom Vocabulary Sets | ✅ Hoàn thành | `vocabulary_sets`, `vocabulary_set_items` |
| **FR-20** | Lưu tiến trình học dở dang | Tự động ghi nhận vị trí đang học để tiếp tục sau | ✅ Hoàn thành | `learning_attempts`, `learning_sessions` |
| **FR-21** | Dashboard học viên | Thống kê: Tổng số từ đã học, từ cần ôn, chuỗi ngày học | ✅ Hoàn thành | `C_Dashboard_user.php` |
| **FR-22** | Lịch sử học & Biểu đồ tiến độ | Xem nhật ký các lần ôn tập, tỷ lệ chính xác theo tuần | ✅ Hoàn thành | `C_Lichsuontap.php`, `review_logs` |
| **FR-23** | Góc rèn luyện mở rộng | Các bài luyện tập chuyên sâu theo chủ đề yếu | ✅ Hoàn thành | `C_Gocrenluyen.php` |
| **FR-24** | Admin: Quản lý Chủ đề | CRUD danh mục chủ đề hệ thống | ✅ Hoàn thành | `D_Quanlychude.php` |
| **FR-25** | Admin: Quản lý Từ vựng | CRUD kho từ vựng chuẩn hệ thống, upload ảnh/audio | ✅ Hoàn thành | `D_Quanlytuvung.php` |
| **FR-26** | Admin: Quản lý Người dùng | Khóa/Mở tài khoản, xem phân quyền, quản lý vai trò | ✅ Hoàn thành | `D_Quanlynguoidung.php` |
| **FR-27** | Admin: Thống kê Hệ thống | Biểu đồ người dùng mới, tổng số từ, bài quiz hoàn thành | ✅ Hoàn thành | `D_Thongkehethong.php` |
| **FR-28** | Admin: Cấu hình Hệ thống | Thiết lập thông số SRS mặc định, tham số phân trang | ✅ Hoàn thành | `D_Caidathethong.php` |
| **FR-29** | Kiểm soát phân quyền (RBAC) | Chặn truy cập trái phép bằng Middleware / Auth Guard | ✅ Hoàn thành | `auth_guard.php`, chống IDOR |
| **FR-30** | Tìm kiếm & Bộ lọc nâng cao | Tìm kiếm từ theo từ khóa, chủ đề, trạng thái nhớ | ✅ Hoàn thành | AJAX dynamic search |

---

## 🛡️ 2. Bảng Kiểm tra Bảo mật & Toàn vẹn Dữ liệu

- [x] **Mật khẩu an toàn**: 100% mật khẩu được mã hóa bằng `password_hash()` (Bcrypt).
- [x] **Chống SQL Injection**: Sử dụng `PDO` với `prepare()` và `bindValue()` hoặc `mysqli_prepare`.
- [x] **Chống XSS (Cross-Site Scripting)**: Lọc và escape toàn bộ dữ liệu xuất ra HTML bằng `htmlspecialchars(..., ENT_QUOTES, 'UTF-8')`.
- [x] **Chống IDOR (Insecure Direct Object Reference)**: Kiểm tra quyền sở hữu `created_by == $_SESSION['user_id']` trước khi cho phép Cập nhật/Xóa Topic/Từ vựng.
- [x] **Kiểm soát phiên làm việc**: Hủy Session cũ khi đăng xuất, tái tạo Session ID khi đăng nhập thành công (`session_regenerate_id()`).
- [x] **Toàn vẹn khóa ngoại (Referential Integrity)**: Cấu hình `ON DELETE CASCADE` cho bảng con (`review_logs`, `quiz_answer_details`, `vocabulary_set_items`) và `ON DELETE SET NULL` cho quan hệ mềm.
- [x] **Giao dịch an toàn (Transactions)**: Áp dụng `START TRANSACTION` và `COMMIT/ROLLBACK` khi thực hiện xóa dây chuyền Topic hoặc nộp bài Quiz nhiều câu hỏi.
