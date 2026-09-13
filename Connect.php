<?php

define('ROOT_PATH', __DIR__);

// Đồng bộ múi giờ Việt Nam giữa PHP và phiên kết nối MySQL.
date_default_timezone_set('Asia/Ho_Chi_Minh');

// Kết nối MySQL
$link = mysqli_connect(
    "db",
    "webuser",
    "webpass123",
    "db_LexiLoop"
);

// Kiểm tra kết nối
if (!$link) {
    die("Kết nối thất bại: " . mysqli_connect_error());
}

// Thiết lập bộ mã hóa tiếng Việt
mysqli_set_charset($link, "utf8mb4");
mysqli_query($link, "SET time_zone = '+07:00'");

?>
