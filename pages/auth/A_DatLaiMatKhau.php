<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/database_objects.php");

$email = $_SESSION['otp_verified_email'] ?? '';
if (empty($email)) {
    header("Location: A_QuenMatKhau.php");
    exit;
}

$loi = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['A_DatLaiMatKhau_password_hash'] ?? '';
    $confirm_password = $_POST['A_DatLaiMatKhau_confirm_password'] ?? '';

    if (empty($password) || empty($confirm_password)) {
        $loi = "Vui lòng nhập đầy đủ thông tin mật khẩu mới và xác nhận mật khẩu mới.";
    } elseif ($password !== $confirm_password) {
        $loi = "Mật khẩu mới và xác nhận mật khẩu mới không khớp.";
    } elseif (strlen($password) < 6) {
        $loi = "Mật khẩu mới phải có ít nhất 6 ký tự.";
    } else {
        try {
            $new_hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = mysqli_prepare($link, "UPDATE `Users` SET `password_hash` = ?, `update_at` = NOW() WHERE `email` = ? AND `status` = 'active'");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "ss", $new_hash, $email);
                mysqli_stmt_execute($stmt);
                mysqli_stmt_close($stmt);

                unset($_SESSION['otp_verified_email']);
                header("Location: A_DangNhap.php?reset=success");
                exit;
            } else {
                $loi = "Không thể cập nhật mật khẩu lúc này.";
            }
        } catch (Throwable $e) {
            error_log('Lỗi đổi mật khẩu: ' . $e->getMessage());
            $loi = "Có lỗi xảy ra khi lưu mật khẩu mới.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Đặt lại mật khẩu - LexiLoop</title>
    <link rel="stylesheet" href="/CSS/A_DatLaiMatKhau.css">
    <link rel="stylesheet" type="text/css" href="/CSS/Style.css">
</head>
<body class="A_DatLaiMatKhau_body">

    <header class="A_DatLaiMatKhau_header">
        <h1 class="A_DatLaiMatKhau_logo">LexiLoop</h1>
    </header>

    <main class="A_DatLaiMatKhau_main">
        <div class="A_DatLaiMatKhau_boxContainer">
            <h2 class="A_DatLaiMatKhau_title">Đặt lại mật khẩu</h2>
            <p class="A_DatLaiMatKhau_subTitle">Tạo mật khẩu mới cho tài khoản: <strong><?php echo htmlspecialchars($email); ?></strong></p>

            <?php if (!empty($loi)): ?>
                <p style="color: red; text-align: center; margin-bottom: 12px;"><?php echo htmlspecialchars($loi); ?></p>
            <?php endif; ?>

            <form id="A_DatLaiMatKhau_formDatLaiMatKhau" action="A_DatLaiMatKhau.php" method="POST">
                
                <div class="A_DatLaiMatKhau_formGroup">
                    <label for="A_DatLaiMatKhau_password_hash" class="A_DatLaiMatKhau_label">Mật khẩu mới</label>
                    <input 
                        type="password" 
                        id="A_DatLaiMatKhau_password_hash" 
                        name="A_DatLaiMatKhau_password_hash" 
                        class="A_DatLaiMatKhau_input" 
                        maxlength="50" 
                        required>
                </div>

                <div class="A_DatLaiMatKhau_formGroup">
                    <label for="A_DatLaiMatKhau_confirm_password" class="A_DatLaiMatKhau_label">Xác nhận mật khẩu mới</label>
                    <input 
                        type="password" 
                        id="A_DatLaiMatKhau_confirm_password" 
                        name="A_DatLaiMatKhau_confirm_password" 
                        class="A_DatLaiMatKhau_input" 
                        maxlength="50" 
                        required>
                </div>

                <button type="submit" id="A_DatLaiMatKhau_btnDatLaiMatKhau" class="A_DatLaiMatKhau_btnSubmit">Đặt lại mật khẩu</button>
            </form>
        </div>
    </main>

</body>
</html>