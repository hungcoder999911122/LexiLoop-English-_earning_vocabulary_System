<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");
?>
    /* Gửi mã OTP bằng AJAX -> sinh code */
<?php
session_start();

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'vendor/autoload.php'; // Hoặc đường dẫn tới thư viện PHPMailer

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = $_POST['email'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Email không hợp lệ!']);
        exit;
    }

    // 1. Sinh mã OTP 6 số ngẫu nhiên
    $otp = random_int(100000, 999999);

    // 2. Lưu OTP và Email vào SESSION (thời hạn 5 phút)
    $_SESSION['reset_otp'] = $otp;
    $_SESSION['reset_email'] = $email;
    $_SESSION['otp_expire'] = time() + 300; 

    // 3. Gửi Email bằng PHPMailer
    $mail = new PHPMailer(true);

    try {
        // Cấu hình Server SMTP của Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'your-email@gmail.com'; // Email gửi
        $mail->Password   = 'your-app-password';    // Mật khẩu ứng dụng (App Password)
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Cấu hình người gửi & nhận
        $mail->setFrom('your-email@gmail.com', 'LexiLoop Support');
        $mail->addAddress($email);

        // Nội dung Email
        $mail->isHTML(true);
        $mail->Subject = 'Mã OTP đặt lại mật khẩu - LexiLoop';
        $mail->Body    = "Mã OTP của bạn là: <b style='font-size: 20px;'>$otp</b>. Mã có hiệu lực trong 5 phút.";

        $mail->send();
        echo json_encode(['status' => 'success', 'message' => 'Đã gửi mã OTP thành công!']);
    } catch (Exception $e) {
        echo json_encode(['status' => 'error', 'message' => 'Không thể gửi email: ' . $mail->ErrorInfo]);
    }
}
?>