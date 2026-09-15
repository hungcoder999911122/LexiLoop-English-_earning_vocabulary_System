# 📋 LexiLoop Functional Requirements (FR-01 → FR-30) Verification Report

Báo cáo kiểm chứng chi tiết 30 yêu cầu chức năng (FR) của hệ thống LexiLoop, bao gồm mã nguồn xử lý, bảng CSDL tác động và kết quả nghiệm thu.

---

## 📑 Bảng Chi tiết Xác minh 30 Yêu cầu Chức năng

| Mã FR | Tên Chức năng | Phân hệ | File Mã nguồn | Bảng CSDL Tác động | Kết quả Test |
|---|---|---|---|---|---|
| **FR-01** | Đăng ký tài khoản | Auth | `pages/auth/A_DangKy.php` | `Users` | ✅ PASS |
| **FR-02** | Đăng nhập tài khoản | Auth | `pages/auth/A_DangNhap.php` | `Users`, `user_login_sessions` | ✅ PASS |
| **FR-03** | Đăng xuất an toàn | Auth | `pages/auth/A_DangXuat.php` | `user_login_sessions` | ✅ PASS |
| **FR-04** | Quên mật khẩu & Gửi OTP | Auth | `pages/auth/A_QuenMatKhau.php`, `A_Gui_otp.php` | `password_resets` | ✅ PASS |
| **FR-05** | Đổi mật khẩu & Cập nhật Profile | Auth | `pages/auth/A_DatLaiMatKhau.php`, `C_Hosocanhan.php` | `Users` | ✅ PASS |
| **FR-06** | Xem danh mục Chủ đề hệ thống | Main | `pages/main/B_DanhSachChuDe.php` | `Topics` | ✅ PASS |
| **FR-07** | Xem danh sách Từ vựng theo chủ đề | Main | `pages/main/B_DanhSachTuVung.php` | `vocabulary`, View `tu_vung` | ✅ PASS |
| **FR-08** | Học thử Flashcard cho Guest (5 từ) | Demo | `pages/demo/B_Flashcarddemo.php` | `vocabulary` (Read-only) | ✅ PASS |
| **FR-09** | Học thử Quiz cho Guest (5 câu) | Demo | `pages/demo/B_Quizdemo.php` | `vocabulary` (Read-only) | ✅ PASS |
| **FR-10** | Trình học Flashcard tương tác 3D | User | `pages/user/C_HocFlashcard.php`, `JS/C_HocFlashcard.js` | `vocabulary`, `vocabulary_images` | ✅ PASS |
| **FR-11** | Đánh giá mức độ nhớ (Again/Hard/Good/Easy)| User | `pages/api/save_flashcard_progress.php` | `user_vocab_progress`, `review_logs`| ✅ PASS |
| **FR-12** | Thuật toán Lặp lại ngắt quãng (SRS) | Core | `pages/api/save_flashcard_progress.php` | `user_vocab_progress` | ✅ PASS |
| **FR-13** | Sinh bài Quiz trắc nghiệm 4 đáp án | User | `pages/user/C_Quiz.php`, `JS/C_Quiz.js` | `vocabulary` | ✅ PASS |
| **FR-14** | Lưu kết quả bài thi Quiz | User | `pages/api/save_quiz_result.php` | `quiz_results`, `quiz_answer_details`| ✅ PASS |
| **FR-15** | Xem lại bài thi Quiz & Lời giải | User | `pages/user/C_KetquaQuiz.php` | `quiz_results`, `quiz_answer_details`| ✅ PASS |
| **FR-16** | Lọc danh sách từ Ôn tập hôm nay | User | `pages/user/C_Ontaphomnay.php` | `user_vocab_progress`, `vocabulary` | ✅ PASS |
| **FR-17** | Quản lý Từ vựng yêu thích (Favorites) | User | `pages/user/C_Tuvungcuatoi.php` | `favorites` | ✅ PASS |
| **FR-18** | Tạo & Quản lý Chủ đề cá nhân | User | `pages/user/C_Tuvungcuatoi.php` | `Topics` (`created_by = userID`) | ✅ PASS |
| **FR-19** | Thêm, Sửa, Xóa Từ vựng cá nhân | User | `pages/user/C_Tuvungcuatoi.php` | `vocabulary` | ✅ PASS |
| **FR-20** | Tạo & Quản lý Bộ từ vựng (Sets) | User | `pages/user/C_Botuvung.php` | `vocabulary_sets`, `set_items` | ✅ PASS |
| **FR-21** | Tạm dừng & Tiếp tục phiên học (Attempt) | User | `pages/api/learning_attempt.php` | `learning_attempts`, `sessions` | ✅ PASS |
| **FR-22** | Dashboard học viên & Thống kê | User | `pages/user/C_Dashboard_user.php` | `user_vocab_progress`, `quiz_results`| ✅ PASS |
| **FR-23** | Xem lịch sử ôn tập & Biểu đồ | User | `pages/user/C_Lichsuontap.php` | `review_logs`, `quiz_results` | ✅ PASS |
| **FR-24** | Góc rèn luyện mở rộng | User | `pages/user/C_Gocrenluyen.php` | `vocabulary`, `Topics` | ✅ PASS |
| **FR-25** | Admin: Quản lý Chủ đề hệ thống | Admin | `pages/admin/D_Quanlychude.php` | `Topics` | ✅ PASS |
| **FR-26** | Admin: Quản lý Kho từ vựng hệ thống | Admin | `pages/admin/D_Quanlytuvung.php` | `vocabulary`, `vocabulary_images` | ✅ PASS |
| **FR-27** | Admin: Quản lý Tài khoản & Phân quyền | Admin | `pages/admin/D_Quanlynguoidung.php` | `Users` | ✅ PASS |
| **FR-28** | Admin: Thống kê Toàn hệ thống | Admin | `pages/admin/D_Thongkehethong.php` | `Users`, `quiz_results`, `review_logs`| ✅ PASS |
| **FR-29** | Admin: Cài đặt & Cấu hình hệ thống | Admin | `pages/admin/D_Caidathethong.php` | Biến cấu hình / App settings | ✅ PASS |
| **FR-30** | Tìm kiếm & Bộ lọc từ vựng nâng cao | Main | `JS/search.js`, `pages/main/B_DanhSachTuVung.php` | `vocabulary` | ✅ PASS |
