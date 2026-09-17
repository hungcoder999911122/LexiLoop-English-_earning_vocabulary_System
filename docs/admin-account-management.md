# Quản lý tài khoản admin

## Mục đích và quy tắc

- Người dùng đăng ký công khai vẫn chỉ được tạo vai trò `user` bằng `sp_auth_register_user`.
- Admin có thể tạo tài khoản `user` hoặc `admin`, đổi vai trò và khóa/mở khóa tài khoản khác.
- Không cho phép tự khóa, xóa hoặc đổi vai trò tài khoản đang đăng nhập.
- Xóa vĩnh viễn chỉ áp dụng cho tài khoản đã khóa, chưa có dữ liệu học tập hoặc tài nguyên cá nhân. Với tài khoản đã sử dụng, giữ trạng thái khóa để bảo toàn lịch sử.

## Cập nhật database đang sử dụng

1. Sao lưu database trước khi cập nhật.
2. Mở phpMyAdmin, chọn đúng database `db_LexiLoop`.
3. Chọn **Import**, tải file `data/migrations/2026_09_17_admin_accounts.sql`, rồi thực hiện import.
4. Tải lại trang **Quản lý người dùng**. Khi các procedure đã sẵn sàng, các nút tạo tài khoản và đổi quyền được bật; nút xóa chỉ bật trên tài khoản đã khóa.

Migration chỉ tạo/thay thế Stored Procedure, không thay đổi bảng hay tài khoản hiện có. Không cần import lại toàn bộ dữ liệu `db_LexiLoop.sql`. File `db_objects.sql` cũng đã cập nhật cho cài đặt mới.

| Procedure | Công dụng |
| --- | --- |
| `sp_admin_create_account` | Tạo tài khoản; PHP truyền mật khẩu đã `password_hash()` |
| `sp_admin_change_user_role` | Đổi `user/admin` |
| `sp_admin_change_user_status` | Khóa/mở khóa; thay phiên bản cũ bằng phiên bản transaction |
| `sp_admin_delete_account` | Xóa tài khoản đủ điều kiện |
| `sp_admin_lock_account_pair` | Helper nội bộ: khóa actor/target theo ID tăng dần, kiểm tra lại quyền |
| `sp_admin_account_capabilities` | Kiểm tra các procedure đã tồn tại để bật công cụ quản lý |

Với tài khoản database chỉ có quyền tối thiểu, cần cấp `EXECUTE` cho các procedure ngoài và `SELECT` trên `vw_users`. Helper chạy bằng quyền DEFINER, không cần cấp cho PHP gọi trực tiếp. Cấu hình `webuser` hiện tại có quyền trên database nên không cần bổ sung.

## Cách triển khai

Controller `includes/admin_users_controller.php` xác thực quyền bằng admin guard, kiểm tra CSRF (mã phiên chống gửi biểu mẫu giả mạo), kiểm tra dữ liệu rồi gọi procedure. ID admin lấy từ session, không nhận từ form. Danh sách, tìm kiếm và phân trang lấy dữ liệu từ `vw_users`.

Procedure bắt đầu transaction, khóa dòng tài khoản và đọc lại vai trò/trạng thái. Nếu có lỗi, `ROLLBACK`; thành công thì `COMMIT`. Khóa hai ID theo cùng thứ tự tránh hai admin chờ khóa nhau theo vòng. Admin thực hiện phải luôn còn quyền và không được sửa chính mình, vì vậy hai yêu cầu hạ quyền/khóa lẫn nhau không thể làm mất cả hai admin.

## Kiểm thử

`tests/admin_accounts_integration.php` chỉ chạy CLI và từ chối database không có tiền tố `lexiloop_admin_test_`. Phải tạo schema riêng, import schema/objects vào đúng schema test và cấp quyền trước khi chạy. **Không chạy thử thao tác tài khoản trên database chính** vì procedure tự commit.

Đã kiểm tra trên schema riêng: mật khẩu hash, email trùng, user không được tạo admin, tự thao tác bị chặn, vai trò sai rollback, xóa tài khoản trống đã khóa, giữ tài khoản có lịch sử, HTTP POST/CSRF/redirect giữ bộ lọc và hai admin đồng thời hạ quyền nhau.

Kiểm thử trang admin dùng `tests/admin_pages_integration.php`: các View/Procedure, tìm kiếm/lọc/phân trang và thống kê. Kiểm thử HTTP Apache dùng database của Apache; kiểm thử POST tài khoản dùng web server riêng kế thừa database test.
