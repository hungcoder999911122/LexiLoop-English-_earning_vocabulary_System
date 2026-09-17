<?php

define('ROOT_PATH', __DIR__);

// Đồng bộ múi giờ Việt Nam giữa PHP và phiên kết nối MySQL.
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Cấu hình kết nối MySQL (hỗ trợ qua Environment Variables hoặc mặc định)
$db_host = getenv('DB_HOST') ?: 'db';
$db_user = getenv('DB_USER') ?: 'webuser';
$db_pass = getenv('DB_PASS') ?: 'webpass123';
$db_name = getenv('DB_NAME') ?: 'db_LexiLoop';
$db_port = (int)(getenv('DB_PORT') ?: 3306);

// Tắt quăng exception tự động để xử lý fallback an toàn
mysqli_report(MYSQLI_REPORT_OFF);

// Kết nối MySQL
$link = @mysqli_connect($db_host, $db_user, $db_pass, $db_name, $db_port);

// Nếu không kết nối được theo hostname db (ví dụ chạy PHP ngoài Docker), thử fallback sang localhost/127.0.0.1 cổng 3309 hoặc 3306
if (!$link) {
    $fallback_hosts = [
        ['host' => '127.0.0.1', 'port' => 3309],
        ['host' => '127.0.0.1', 'port' => 3306],
        ['host' => 'localhost', 'port' => 3309],
        ['host' => 'localhost', 'port' => 3306],
    ];

    foreach ($fallback_hosts as $fb) {
        if ($fb['host'] === $db_host && $fb['port'] === $db_port) {
            continue;
        }
        $link = @mysqli_connect($fb['host'], $db_user, $db_pass, $db_name, $fb['port']);
        if ($link) {
            break;
        }
    }
}

// Bật lại báo lỗi ngoại lệ sau khi kết nối thành công hoặc xử lý lỗi
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Kiểm tra kết nối
if (!$link) {
    die("<div style='font-family: Arial, sans-serif; padding: 20px; color: #721c24; background-color: #f8d7da; border: 1px solid #f5c6cb; border-radius: 6px; margin: 20px;'>"
        . "<h3>⚠️ Lỗi kết nối Cơ sở dữ liệu</h3>"
        . "<p><strong>Chi tiết:</strong> " . mysqli_connect_error() . "</p>"
        . "<p><strong>Hướng dẫn khắc phục:</strong></p>"
        . "<ul>"
        . "<li>Nếu đang chạy Docker Compose, hãy chạy: <code>docker compose down</code> và <code>docker compose up -d</code> để khởi động lại toàn bộ dịch vụ.</li>"
        . "<li>Kiểm tra container MySQL đã sẵn sàng bằng lệnh: <code>docker ps</code>.</li>"
        . "<li>Nếu chạy trên localhost không qua Docker, hãy kiểm tra cổng MySQL (3306 hoặc 3309) trong XAMPP / MySQL Server.</li>"
        . "</ul>"
        . "</div>");
}

// Thiết lập bộ mã hóa tiếng Việt và múi giờ
mysqli_set_charset($link, "utf8mb4");
mysqli_query($link, "SET time_zone = '+07:00'");

// Lưu kết nối dùng chung kể cả khi file được require trong phạm vi một hàm.
$GLOBALS['lexiLoopDatabaseConnection'] = $link;

/** Lấy kết nối đã khởi tạo, không mở thêm kết nối MySQL. */
function getDatabaseConnection(): mysqli
{
    $connection = $GLOBALS['lexiLoopDatabaseConnection'] ?? null;
    if (!$connection instanceof mysqli) {
        throw new RuntimeException('Kết nối CSDL chưa được khởi tạo.');
    }
    return $connection;
}
?>
