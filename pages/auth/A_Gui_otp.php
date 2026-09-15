<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/database_objects.php");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Email không hợp lệ!']);
        exit;
    }

    // Kiểm tra xem email có tồn tại trong hệ thống không
    $users = dbSelectView($link, 'SELECT userID FROM vw_users WHERE email = ? AND status = ? LIMIT 1', 'ss', [strtolower($email), 'active']);
    if (!$users) {
        echo json_encode(['status' => 'error', 'message' => 'Email này chưa được đăng ký trong hệ thống!']);
        exit;
    }

    // 1. Sinh mã OTP 6 số ngẫu nhiên
    $otp = (string) random_int(100000, 999999);

    // 2. Lưu OTP và Email vào SESSION (thời hạn 5 phút)
    $_SESSION['reset_otp'] = $otp;
    $_SESSION['reset_email'] = strtolower($email);
    $_SESSION['otp_expire'] = time() + 300;

    // 3. Gửi Email bằng PHPMailer nếu có vendor, hoặc trả về mã OTP trong môi trường phát triển
    $vendorAutoload = $_SERVER['DOCUMENT_ROOT'] . '/vendor/autoload.php';
    $emailSent = false;

    if (file_exists($vendorAutoload)) {
        require_once $vendorAutoload;
        if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
            try {
                $mail = new \PHPMailer\PHPMailer\PHPMailer(true);
                $mail->isSMTP();
                $mail->Host       = getenv('SMTP_HOST') ?: 'smtp.gmail.com';
                $mail->SMTPAuth   = true;
                $mail->Username   = getenv('SMTP_USER') ?: '';
                $mail->Password   = getenv('SMTP_PASS') ?: '';
                $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = (int) (getenv('SMTP_PORT') ?: 587);

                if (!empty($mail->Username) && !empty($mail->Password)) {
                    $mail->setFrom($mail->Username, 'LexiLoop Support');
                    $mail->addAddress($email);
                    $mail->isHTML(true);
                    $mail->Subject = 'Mã OTP đặt lại mật khẩu - LexiLoop';
                    $mail->Body    = "Mã OTP của bạn là: <b style='font-size: 20px;'>$otp</b>. Mã có hiệu lực trong 5 phút.";
                    $mail->send();
                    $emailSent = true;
                }
            } catch (\Throwable $e) {
                error_log('Lỗi gửi mail OTP: ' . $e->getMessage());
            }
        }
    }

    if ($emailSent) {
        echo json_encode(['status' => 'success', 'message' => 'Đã gửi mã OTP đến email của bạn!']);
    } else {
        // Môi trường thử nghiệm / phát triển cục bộ
        echo json_encode([
            'status' => 'success',
            'message' => "Mã OTP thử nghiệm của bạn là: $otp (Có hiệu lực 5 phút)",
            'otp' => $otp
        ]);
    }
    exit;
}
?>