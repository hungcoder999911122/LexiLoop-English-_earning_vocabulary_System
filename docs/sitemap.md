# 🗺️ LexiLoop Sitemap (Sơ đồ Điều hướng Website)

Sơ đồ cấu trúc cây điều hướng liên kết và luồng truy cập người dùng trong hệ thống LexiLoop.

---

## 🌳 Sơ đồ Cây Điều hướng (Visual Tree Sitemap)

```text
[Trang chủ (B_homepage.html)]
  │
  ├── [Danh mục Chủ đề (B_DanhSachChuDe.php)]
  │     └── [Chi tiết Từ vựng Chủ đề (B_DanhSachTuVung.php)]
  │
  ├── [Giới thiệu Dự án (B_LexiLoopinfo.php)]
  │
  ├── [Khu vực Học thử Dành cho Guest]
  │     ├── [Học thử Flashcard (B_Flashcarddemo.php)]
  │     └── [Làm thử bài Quiz (B_Quizdemo.php)]
  │
  ├── [Cổng Xác thực]
  │     ├── [Đăng nhập (A_DangNhap.php)]
  │     ├── [Đăng ký (A_DangKy.php)]
  │     └── [Quên mật khẩu (A_QuenMatKhau.php)] ──► [Đặt lại mật khẩu (A_DatLaiMatKhau.php)]
  │
  ├── [Cổng Học viên (Đã đăng nhập)]
  │     ├── [Dashboard Học viên (C_Dashboard_user.php)]
  │     ├── [Ôn tập Hôm nay (C_Ontaphomnay.php)] ──► [Học Flashcard SRS (C_HocFlashcard.php)]
  │     ├── [Kho từ vựng của tôi (C_Tuvungcuatoi.php)]
  │     ├── [Bộ từ vựng cá nhân (C_Botuvung.php)]
  │     ├── [Làm bài kiểm tra Quiz (C_Quiz.php)] ──► [Xem kết quả (C_KetquaQuiz.php)]
  │     ├── [Góc rèn luyện chuyên sâu (C_Gocrenluyen.php)]
  │     ├── [Lịch sử ôn tập & Thống kê (C_Lichsuontap.php)]
  │     └── [Hồ sơ cá nhân & Cài đặt (C_Hosocanhan.php / A_Caidattaikhoan.php)]
  │
  └── [Cổng Quản trị Viên (Admin Only)]
        ├── [Dashboard Admin (D_Dashboard_admin.php)]
        ├── [Quản lý Chủ đề hệ thống (D_Quanlychude.php)]
        ├── [Quản lý Kho từ vựng hệ thống (D_Quanlytuvung.php)]
        ├── [Quản lý Tài khoản người dùng (D_Quanlynguoidung.php)]
        ├── [Báo cáo Thống kê hệ thống (D_Thongkehethong.php)]
        └── [Cài đặt cấu hình hệ thống (D_Caidathethong.php)]
```
