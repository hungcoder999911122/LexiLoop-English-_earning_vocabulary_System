<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/database_objects.php");

$loi = "";
$thongBao = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['A_QuenMatKhau_email'] ?? '');
    $otp_input = trim($_POST['A_QuenMatKhau_otp_code'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loi = "Vui lòng nhập địa chỉ email hợp lệ.";
    } elseif (empty($otp_input)) {
        $loi = "Vui lòng nhập mã OTP đã nhận.";
    } else {
        $sess_otp = $_SESSION['reset_otp'] ?? '';
        $sess_email = $_SESSION['reset_email'] ?? '';
        $sess_expire = $_SESSION['otp_expire'] ?? 0;

        if (empty($sess_otp) || strtolower($sess_email) !== strtolower($email)) {
            $loi = "Bạn chưa yêu cầu mã OTP cho email này hoặc thông tin không khớp.";
        } elseif (time() > $sess_expire) {
            $loi = "Mã OTP đã hết hạn (5 phút). Vui lòng lấy mã mới.";
        } elseif ($sess_otp !== $otp_input) {
            $loi = "Mã OTP không chính xác. Vui lòng thử lại.";
        } else {
            // Xác thực thành công -> Cho phép đặt lại mật khẩu
            $_SESSION['otp_verified_email'] = strtolower($email);
            unset($_SESSION['reset_otp'], $_SESSION['otp_expire']);
            header("Location: A_DatLaiMatKhau.php");
            exit;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - LexiLoop</title>
    <link rel="stylesheet" href="/CSS/A_QuenMatKhau.css">
    <link rel="stylesheet" type="text/css" href="/CSS/Style.css">
    <script src="/JS/jquery-4.0.0.min.js"></script>
</head>

<body class="A_QuenMatKhau_body">

    <header class="A_QuenMatKhau_header">
        <h1 class="A_QuenMatKhau_logo">LexiLoop</h1>
    </header>

    <main class="A_QuenMatKhau_main">
        <div class="A_QuenMatKhau_boxContainer">
            <h2 class="A_QuenMatKhau_title">Quên mật khẩu</h2>
            <p class="A_QuenMatKhau_subTitle">Nhập email để nhận mã OTP đặt lại mật khẩu</p>

            <div id="otpStatusBox" style="display: none; padding: 10px; margin-bottom: 15px; border-radius: 4px; text-align: center; font-size: 14px;"></div>

            <?php if (!empty($loi)): ?>
                <p style="color: red; text-align: center; margin-bottom: 12px;"><?php echo htmlspecialchars($loi); ?></p>
            <?php endif; ?>

            <form id="A_QuenMatKhau_formQuenMatKhau" action="A_QuenMatKhau.php" method="POST">

                <div class="A_QuenMatKhau_formGroup">
                    <label for="A_QuenMatKhau_email" class="A_QuenMatKhau_label">Email</label>
                    <input type="email"
                           id="A_QuenMatKhau_email"
                           name="A_QuenMatKhau_email"
                           class="A_QuenMatKhau_input"
                           maxlength="100"
                           required
                           value="<?php echo htmlspecialchars($_POST['A_QuenMatKhau_email'] ?? $_SESSION['reset_email'] ?? ''); ?>">
                           
                    <button style="background-color:#4CAF50; color:white; border:none; padding:8px 12px; cursor:pointer; border-radius: 4px; margin-top: 6px;" 
                            type="button" 
                            id="btnGuiOTP">Gửi mã OTP</button>
                </div> 

                <div class="A_QuenMatKhau_formGroup">
                    <label for="A_QuenMatKhau_otp_code" class="A_QuenMatKhau_label">Mã OTP</label>
                    <input type="text"
                           id="A_QuenMatKhau_otp_code"
                           name="A_QuenMatKhau_otp_code"
                           class="A_QuenMatKhau_input"
                           maxlength="6"
                           pattern="[0-9]{6}"
                           inputmode="numeric"
                           required
                           title="Vui lòng nhập đúng 6 chữ số OTP">
                    <small class="A_QuenMatKhau_note">Mã sẽ được gửi đến email của bạn</small>
                </div>

                <button type="submit" id="A_QuenMatKhau_btnXacNhan" class="A_QuenMatKhau_btnSubmit">Xác nhận</button>

                <div class="A_QuenMatKhau_linkWrapper">
                    <a href="A_DangNhap.php" id="A_QuenMatKhau_linkDangNhap" class="A_QuenMatKhau_link">Quay lại đăng nhập</a>
                </div>
            </form>
        </div>
    </main>

    <script>
        $(document).ready(function () {
            $('#btnGuiOTP').click(function () {
                var email = $('#A_QuenMatKhau_email').val().trim();
                var statusBox = $('#otpStatusBox');

                if (!email) {
                    statusBox.show().css({'background-color': '#f8d7da', 'color': '#721c24'}).text('Vui lòng nhập email trước khi gửi OTP!');
                    return;
                }

                var btn = $(this);
                btn.prop('disabled', true).text('Đang gửi...');

                $.ajax({
                    url: 'A_Gui_otp.php',
                    type: 'POST',
                    data: { email: email },
                    dataType: 'json',
                    success: function (res) {
                        if (res.status === 'success') {
                            statusBox.show().css({'background-color': '#d4edda', 'color': '#155724'}).text(res.message);
                            if (res.otp) {
                                $('#A_QuenMatKhau_otp_code').val(res.otp);
                            }
                            var countdown = 60;
                            var timer = setInterval(function () {
                                countdown--;
                                if (countdown > 0) {
                                    btn.text('Gửi lại (' + countdown + 's)');
                                } else {
                                    clearInterval(timer);
                                    btn.prop('disabled', false).text('Gửi mã OTP');
                                }
                            }, 1000);
                        } else {
                            statusBox.show().css({'background-color': '#f8d7da', 'color': '#721c24'}).text(res.message || 'Lỗi gửi OTP!');
                            btn.prop('disabled', false).text('Gửi mã OTP');
                        }
                    },
                    error: function () {
                        statusBox.show().css({'background-color': '#f8d7da', 'color': '#721c24'}).text('Không thể kết nối máy chủ gửi OTP!');
                        btn.prop('disabled', false).text('Gửi mã OTP');
                    }
                });
            });
        });
    </script>
</body>
</html>