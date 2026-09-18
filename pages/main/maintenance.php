<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");

// Nếu hệ thống ĐÃ TẮT bảo trì, chuyển hướng về trang chủ
if (getSystemSetting($link, 'maintenance_mode') !== '1') {
    header("Location: /index.php");
    exit();
}

// Lấy thông tin cấu hình hiển thị
$siteName = getSystemSetting($link, 'site_name', 'LexiLoop');
$siteLogo = getSystemSetting($link, 'site_logo', '/assets/images/logo.png');
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bảo trì hệ thống - <?php echo htmlspecialchars($siteName); ?></title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f8f9fa;
            color: #333;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
            margin: 0;
            text-align: center;
        }
        .maintenance-container {
            background-color: #fff;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
            max-width: 500px;
            width: 90%;
        }
        .maintenance-logo {
            max-width: 150px;
            margin-bottom: 20px;
        }
        h1 {
            font-size: 24px;
            color: #e74c3c;
            margin-bottom: 10px;
        }
        p {
            font-size: 16px;
            line-height: 1.5;
            color: #555;
            margin-bottom: 20px;
        }
        .btn-admin {
            display: inline-block;
            padding: 10px 20px;
            background-color: #3498db;
            color: #fff;
            text-decoration: none;
            border-radius: 5px;
            font-weight: bold;
            transition: background-color 0.3s;
        }
        .btn-admin:hover {
            background-color: #2980b9;
        }
    </style>
</head>
<body>
    <div class="maintenance-container">
        <img src="<?php echo htmlspecialchars($siteLogo); ?>" alt="Logo" class="maintenance-logo">
        <h1>Hệ thống đang bảo trì</h1>
        <p>Xin lỗi vì sự bất tiện này. Chúng tôi đang thực hiện nâng cấp hệ thống và sẽ sớm quay lại. Vui lòng thử lại sau ít phút.</p>
        <p>Nếu bạn là Quản trị viên, bạn có thể đăng nhập bên dưới.</p>
        <a href="/pages/admin/D_DangNhapAdmin.php" class="btn-admin">Đăng nhập Admin</a>
    </div>
</body>
</html>
