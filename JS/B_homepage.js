$(document).ready(function () {

    // =========================================================
    // 2. NAVBAR KHI CUỘN TRANG
    // =========================================================
    // Khi người dùng cuộn xuống hơn 50px,
    // thêm class "scrolled" cho navbar.
    //
    // Class này sau đó có thể được CSS sử dụng
    // để tạo bóng hoặc thay đổi giao diện navbar.
    // =========================================================

    $(window).on('scroll', function () {

        // Lấy vị trí hiện tại của thanh cuộn
        var scrollTop = $(window).scrollTop();

        // Nếu đã cuộn xuống hơn 50px
        if (scrollTop > 50) {

            $('.main-nav').addClass('scrolled');

        } else {

            // Nếu quay lại gần đầu trang
            // thì xóa class "scrolled"
            $('.main-nav').removeClass('scrolled');
        }
    });


    // =========================================================
    // 3. HIỆU ỨNG SECTION XUẤT HIỆN KHI CUỘN
    // =========================================================
    // Khi một section xuất hiện trong màn hình,
    // thêm class "show".
    //
    // CSS sẽ đảm nhiệm phần hiệu ứng.
    // JavaScript chỉ kiểm tra:
    // "Section này đã xuất hiện chưa?"
    // =========================================================

    function revealSections() {

        // Lấy chiều cao vùng nhìn thấy của trình duyệt
        var windowHeight = $(window).height();

        // Vị trí hiện tại của thanh cuộn
        var scrollTop = $(window).scrollTop();

        // Duyệt qua các phần tử muốn tạo hiệu ứng
        $('.how-it-works, .features, .cta-section').each(function () {

            // Vị trí của section so với đầu trang
            var sectionTop = $(this).offset().top;

            // Nếu section đã đi vào vùng nhìn thấy
            if (scrollTop + windowHeight - 100 > sectionTop) {

                // Thêm class "show"
                $(this).addClass('show');
            }
        });
    }

    // Gọi ngay khi trang vừa load
    revealSections();

    // Gọi lại mỗi khi người dùng cuộn
    $(window).on('scroll', function () {
        revealSections();
    });


    // =========================================================
    // 4. COOKIE CHÀO MỪNG
    // =========================================================
    // Kiểm tra xem người dùng đã từng truy cập Homepage chưa.
    //
    // Nếu chưa:
    //     -> tạo Cookie
    //
    // Nếu đã có:
    //     -> không làm gì.
    //
    // Cookie này chỉ dùng để minh họa yêu cầu Cookie
    // của đồ án.
    // =========================================================

    if (!getCookie('lexiloop_visited')) {

        // Tạo Cookie có thời hạn 7 ngày
        setCookie('lexiloop_visited', 'true', 7);

        console.log('Chào mừng bạn đến với LexiLoop!');
    }


    // =========================================================
    // HÀM TẠO COOKIE
    // =========================================================

    function setCookie(name, value, days) {
        // Tạo thời gian hết hạn
        var date = new Date();
        date.setTime(
            date.getTime() + (days * 24 * 60 * 60 * 1000)
        );
        // Chuyển thời gian sang dạng GMT
        var expires = 'expires=' + date.toUTCString();
        // Lưu Cookie
        document.cookie =
            name + '=' + value + ';' + expires + ';path=/';
    }
    
    // =========================================================
    // HÀM ĐỌC COOKIE
    // =========================================================
    function getCookie(name) {
        // Lấy toàn bộ Cookie hiện tại
        var cookies = document.cookie.split(';');
        // Duyệt từng Cookie
        for (var i = 0; i < cookies.length; i++) {
            var cookie = cookies[i].trim();
            // Kiểm tra Cookie có tên cần tìm không
            if (cookie.indexOf(name + '=') === 0) {
                // Trả về giá trị của Cookie
                return cookie.substring(
                    name.length + 1,
                    cookie.length
                );
            }
        }
        // Không tìm thấy Cookie
        return '';
    }
});