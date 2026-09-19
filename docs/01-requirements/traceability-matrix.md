# 🔗 Requirement Traceability Matrix (RTM) - LexiLoop

Ma trận truy vết yêu cầu phần mềm kết nối giữa Yêu cầu Nghiệp vụ (FR) → Thiết kế CSDL → Tầng API Backend → Giao diện Frontend → Kịch bản Kiểm thử (Test Cases).

---

## 🧭 Bảng Ma trận Truy vết Toàn diện

| Mã FR | Tên Yêu cầu Nghiệp vụ | Bảng CSDL Liên quan | File API / Controller | Giao diện Frontend | Mã Test Case |
|---|---|---|---|---|---|
| **FR-01** | Đăng ký tài khoản | `Users` | `pages/auth/A_DangKy.php` | `pages/auth/A_DangKy.php` | TC-AUTH-01 |
| **FR-02** | Đăng nhập hệ thống | `Users`, `user_login_sessions` | `pages/auth/A_DangNhap.php` | `pages/auth/A_DangNhap.php` | TC-AUTH-02 |
| **FR-03** | Đăng xuất an toàn | `user_login_sessions` | `pages/auth/A_DangXuat.php` | Top Header Button | TC-AUTH-03 |
| **FR-04** | Quên mật khẩu & OTP | `password_resets` | `pages/auth/A_Gui_otp.php` | `pages/auth/A_QuenMatKhau.php` | TC-AUTH-04 |
| **FR-05** | Đổi thông tin / Mật khẩu | `Users` | `pages/auth/A_DatLaiMatKhau.php` | `pages/user/C_Hosocanhan.php` | TC-AUTH-05 |
| **FR-06** | Xem danh mục Chủ đề | `Topics` | `pages/main/B_DanhSachChuDe.php` | `pages/main/B_DanhSachChuDe.php` | TC-VOCAB-01 |
| **FR-07** | Xem danh sách Từ vựng | `vocabulary`, View `tu_vung` | `pages/main/B_DanhSachTuVung.php` | `pages/main/B_DanhSachTuVung.php` | TC-VOCAB-02 |
| **FR-08** | Học thử Guest (5 từ) | `vocabulary` | `pages/demo/B_Flashcarddemo.php` | `pages/demo/B_Flashcarddemo.php` | TC-DEMO-01 |
| **FR-09** | Học Flashcard tương tác | `vocabulary`, `vocabulary_images`| `pages/user/C_HocFlashcard.php` | `pages/user/C_HocFlashcard.php` | TC-SRS-01 |
| **FR-10** | Đánh giá nhớ & Lịch SRS | `user_vocab_progress`, `review_logs` | `pages/api/save_flashcard_progress.php`| `JS/C_HocFlashcard.js` | TC-SRS-02 |
| **FR-11** | Sinh đề Quiz trắc nghiệm | `vocabulary` | `pages/user/C_Quiz.php` | `pages/user/C_Quiz.php` | TC-QUIZ-01 |
| **FR-12** | Chấm điểm & Lưu kết quả | `quiz_results`, `quiz_answer_details` | `pages/api/save_quiz_result.php` | `JS/C_Quiz.js` | TC-QUIZ-02 |
| **FR-13** | Xem lại bài thi Quiz | `quiz_results`, `quiz_answer_details` | `pages/user/C_KetquaQuiz.php` | `pages/user/C_KetquaQuiz.php` | TC-QUIZ-03 |
| **FR-14** | Ôn tập hôm nay theo SRS | `user_vocab_progress`, `vocabulary` | `pages/user/C_Ontaphomnay.php` | `pages/user/C_Ontaphomnay.php` | TC-SRS-03 |
| **FR-15** | Quản lý Yêu thích | `favorites` | `pages/user/C_Tuvungcuatoi.php` | `pages/user/C_Tuvungcuatoi.php` | TC-FAV-01 |
| **FR-16** | CRUD Chủ đề cá nhân | `Topics` (`created_by`) | `pages/user/C_Tuvungcuatoi.php` | `pages/user/C_Tuvungcuatoi.php` | TC-USER-01 |
| **FR-17** | CRUD Từ vựng cá nhân | `vocabulary` (`created_by`) | `pages/user/C_Tuvungcuatoi.php` | `pages/user/C_Tuvungcuatoi.php` | TC-USER-02 |
| **FR-18** | Quản lý Bộ từ vựng Sets | `vocabulary_sets`, `set_items` | `pages/user/C_Botuvung.php` | `pages/user/C_Botuvung.php` | TC-SET-01 |
| **FR-19** | Lưu phiên học dở dang | `learning_attempts`, `sessions` | `pages/api/learning_attempt.php` | `JS/learning.js` | TC-ATT-01 |
| **FR-20** | Dashboard & Thống kê | `user_vocab_progress`, `quiz_results`| `pages/user/C_Dashboard_user.php` | `pages/user/C_Dashboard_user.php` | TC-DASH-01 |
| **FR-21** | Admin CRUD Chủ đề | `Topics` | `pages/admin/D_Quanlychude.php` | `pages/admin/D_Quanlychude.php` | TC-ADM-01 |
| **FR-22** | Admin CRUD Từ vựng | `vocabulary`, `vocabulary_images`| `pages/admin/D_Quanlytuvung.php` | `pages/admin/D_Quanlytuvung.php` | TC-ADM-02 |
| **FR-23** | Admin Quản lý User | `Users` | `pages/admin/D_Quanlynguoidung.php`| `pages/admin/D_Quanlynguoidung.php` | TC-ADM-03 |
| **FR-24** | Admin Thống kê Toàn sàn| `Users`, `quiz_results`, `review_logs`| `pages/admin/D_Thongkehethong.php` | `pages/admin/D_Thongkehethong.php` | TC-ADM-04 |
| **FR-25** | Chống lỗi IDOR & RBAC | Tất cả các bảng dữ liệu | `includes/auth_guard.php` | Tất cả trang hệ thống | TC-SEC-01 |
