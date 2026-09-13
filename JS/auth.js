// THÔNG BÁO THÀNH CÔNG/ THẤT BẠI CỦA MỖI CHỨC NĂNG : ĐĂNG NHẬP, ĐĂNG KÝ, ĐĂNG XUẤT
/*
 * ============================================================
 * auth.js
 * Xử lý các chức năng JavaScript liên quan đến tài khoản
 *
 * Bao gồm:
 * 1. Kiểm tra form Đăng nhập
 * 2. Kiểm tra form Đăng ký
 * 3. Chuyển sang trang Đăng ký
 * 4. Xác nhận trước khi Đăng xuất
 *
 * Lưu ý:
 * - JavaScript chỉ kiểm tra dữ liệu phía trình duyệt.
 * - PHP vẫn phải kiểm tra dữ liệu một lần nữa.
 * - JavaScript KHÔNG xử lý Database.
 * - JavaScript KHÔNG xóa Session.
 * ============================================================
 */
/*
|--------------------------------------------------------------------------
| CÁC CHỨC NĂNG CŨ PHỤ THUỘC JQUERY
|--------------------------------------------------------------------------
| Login/Register vẫn dùng jQuery nên chỉ chạy khi trang đã nạp jQuery.
*/
if (window.jQuery) {
    $(document).ready(function () {
            // ========================================================
    // 1. XỬ LÝ FORM ĐĂNG NHẬP
    // ========================================================

    /*
     * Kiểm tra khi người dùng nhấn nút "Đăng nhập".
     *
     * Nếu Email hoặc Mật khẩu bị bỏ trống:
     * -> Hiển thị thông báo
     * -> Chặn form gửi lên PHP
     *
     * Nếu dữ liệu đầy đủ:
     * -> Không gọi preventDefault()
     * -> Cho phép form gửi lên PHP xử lý.
     */
    $('#DangNhap_btn').click(function (event) {

        var email = $('#A_DangNhap_Email').val().trim();
        var password = $('#A_DangNhap_password').val();

        // Kiểm tra Email
        if (email === '') {
            alert('Vui lòng nhập Email.');
            event.preventDefault();
            return;
        }

        // Kiểm tra Mật khẩu
        if (password === '') {
            alert('Vui lòng nhập Mật khẩu.');
            event.preventDefault();
            return;
        }

        /*
         * Nếu chạy đến đây nghĩa là dữ liệu phía Client hợp lệ.
         *
         * KHÔNG dùng:
         * event.preventDefault();
         *
         * để trình duyệt tiếp tục submit form cho PHP.
         */
    });


    // ========================================================
    // 2. XỬ LÝ FORM ĐĂNG KÝ
    // ========================================================

    /*
     * Kiểm tra dữ liệu trước khi gửi form Đăng ký.
     *
     * Đây là phần JavaScript trước đây đang nằm trực tiếp
     * bên trong A_DangKy.php.
     *
     * Bây giờ chuyển sang auth.js để dùng chung và dễ quản lý.
     */
    $('#A_DangKybtn').click(function (event) {

        var fullname = $('#A_DangKy_fullname').val().trim();
        var email = $('#A_DangKy_email').val().trim();
        var password = $('#A_DangKy_password').val();
        var passwordConfirm = $('#A_DangKy_password_confirm').val();
        var agree = $('#A_DangKy_agree').is(':checked');


        // ----------------------------------------------------
        // Kiểm tra Họ tên
        // ----------------------------------------------------

        if (fullname === '') {
            alert('Vui lòng nhập họ và tên.');
            event.preventDefault();
            return;
        }


        // ----------------------------------------------------
        // Kiểm tra Email
        // ----------------------------------------------------

        if (email === '') {
            alert('Vui lòng nhập Email.');
            event.preventDefault();
            return;
        }


        // ----------------------------------------------------
        // Kiểm tra Mật khẩu
        // ----------------------------------------------------

        if (password === '') {
            alert('Vui lòng nhập mật khẩu.');
            event.preventDefault();
            return;
        }


        // ----------------------------------------------------
        // Kiểm tra Xác nhận mật khẩu
        // ----------------------------------------------------

        if (passwordConfirm === '') {
            alert('Vui lòng nhập lại mật khẩu.');
            event.preventDefault();
            return;
        }


        // ----------------------------------------------------
        // Kiểm tra hai mật khẩu có giống nhau không
        // ----------------------------------------------------

        if (password !== passwordConfirm) {
            alert('Mật khẩu xác nhận không khớp.');
            event.preventDefault();
            return;
        }


        // ----------------------------------------------------
        // Kiểm tra điều khoản sử dụng
        // ----------------------------------------------------

        if (!agree) {
            alert('Bạn phải đồng ý với điều khoản sử dụng.');
            event.preventDefault();
            return;
        }


        /*
         * Nếu chạy đến đây:
         * - Dữ liệu phía Client hợp lệ.
         *
         * Không gọi event.preventDefault().
         *
         * Form sẽ được gửi lên PHP để PHP tiếp tục:
         * - Kiểm tra Email đã tồn tại chưa
         * - Hash mật khẩu
         * - INSERT vào Database
         */
    });


    // ========================================================
    // 3. NÚT "TẠO TÀI KHOẢN" Ở TRANG ĐĂNG NHẬP
    // ========================================================

    /*
     * Khi người dùng đang ở A_DangNhap.php và nhấn
     * "Tạo tài khoản":
     *
     * -> Chuyển sang A_DangKy.php
     */
    $('#A_DangNhap_TaoTaiKhoan').click(function () {

        window.location.href = "A_DangKy.php";

        });
    });
}

/*
|--------------------------------------------------------------------------
| XÁC NHẬN ĐĂNG XUẤT DÙNG CHUNG
|--------------------------------------------------------------------------
| Không dùng jQuery để mọi trang đều hoạt động, kể cả trang không nạp jQuery.
|
| data-action="logout" giúp sidebar, menu avatar hoặc menu mobile
| dùng cùng một logic, không phụ thuộc class CSS hay vị trí HTML.
*/
document.addEventListener("click", (event) => {
    const logoutLink = event.target.closest('[data-action="logout"]');

    // Click không thuộc nút/link đăng xuất thì không xử lý.
    if (!logoutLink) {
        return;
    }

    // Tạm chặn việc chuyển trang để hỏi xác nhận.
    event.preventDefault();

    const isConfirmed = window.confirm(
        "Bạn có chắc chắn muốn đăng xuất tài khoản không?"
    );

    // Người dùng chọn OK: chuyển đến đúng link A_DangXuat.php.
    if (isConfirmed) {
        window.location.assign(logoutLink.href);
    }
});