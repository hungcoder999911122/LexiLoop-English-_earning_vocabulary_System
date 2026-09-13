<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");
?>


<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quên mật khẩu - LexiLoop</title>
    <link rel="stylesheet" href="/CSS/A_QuenMatKhau.css">
    <link rel="stylesheet" type="text/css" href="/CSS/Style.css">
</head>

<body class="A_QuenMatKhau_body">

    <header class="A_QuenMatKhau_header">
        <h1 class="A_QuenMatKhau_logo">LexiLoop</h1>
    </header>

    <main class="A_QuenMatKhau_main">
        <div class="A_QuenMatKhau_boxContainer">
            <h2 class="A_QuenMatKhau_title">Quên mật khẩu</h2>
            <p class="A_QuenMatKhau_subTitle">Nhập email để nhận mã OTP đặt lại mật khẩu</p>

            <?php if(!empty($loi)): ?>
                <p style="color: red; text-align: center;"><?php echo $loi; ?></p>
            <?php endif; ?>

            <form id="A_QuenMatKhau_formQuenMatKhau" action="A_QuenMatKhau.php" method="POST">

                <div class="A_QuenMatKhau_formGroup">
                    <label for="A_QuenMatKhau_email" class="A_QuenMatKhau_label">Email</label>
                    <input type="email"
                           id="A_QuenMatKhau_email"
                           name="A_QuenMatKhau_email"
                           class="A_QuenMatKhau_input"
                           maxlength="100"
                           value="<?php echo htmlspecialchars($_POST['A_QuenMatKhau_email'] ?? ''); ?>">
                           
                    <!-- Đổi type="button" để tránh submit form -->
                    <button style="background-color:#4CAF50; color:white; border:none; padding:8px 12px; cursor:pointer;" 
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


    
</body>
</html>