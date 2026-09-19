# 🎨 LexiLoop Frontend Quality Checklist

Bảng tiêu chuẩn đánh giá giao diện, trải nghiệm người dùng, độ phản hồi và tính tiếp cận trên toàn bộ hệ thống LexiLoop.

---

## 📱 1. Kiểm tra Độ Tương thích Màn hình (Responsive Breakpoints)

- [x] **Desktop lớn (>= 1200px)**: Bố cục 3 cột (Sidebar - Main Content - Stats/Tools), hiển thị tối ưu Flashcard kích thước lớn.
- [x] **Desktop tiêu chuẩn & Laptop (992px - 1199px)**: Bố cục co giãn linh hoạt, lưới chủ đề tự động căn chỉnh.
- [x] **Tablet (768px - 991px)**: Sidebar thu gọn thành icon hoặc drawer menu, giữ nguyên kích thước lật thẻ Flashcard.
- [x] **Mobile (< 768px)**: Menu hamburger, Flashcard tối ưu chạm (Touch swipe), nút bấm kích thước tối thiểu 44px dễ tương tác.

---

## ✨ 2. Hiệu ứng Tương tác & Trải nghiệm Người dùng (UI/UX Micro-interactions)

- [x] **Flashcard 3D Flip**: Hiệu ứng chuyển động lật thẻ mượt mà với CSS `transform: rotateY(180deg)` và `backface-visibility: hidden`.
- [x] **Phím tắt nhanh (Keyboard Navigation)**:
  - `Space`: Lật mặt trước / mặt sau của Flashcard.
  - `1`, `2`, `3`, `4`: Đánh giá mức độ nhớ tương ứng (Again / Hard / Good / Easy).
  - `Phím mũi tên Trái / Phải`: Chuyển từ trước / từ kế tiếp.
- [x] **Quiz Feedback**: Đổi màu trực quan ngay khi chọn đáp án (Xanh cho đáp án đúng, Đỏ cho đáp án sai).
- [x] **Toast Notifications**: Thông báo nổi khi Thêm từ vựng thành công, Lưu tiến trình hoặc Đổi mật khẩu.
