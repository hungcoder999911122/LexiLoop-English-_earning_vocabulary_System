<?php
session_start();
require_once __DIR__ . '/Connect.php';

// Kiểm tra xem user đã đăng nhập chưa
if (!isset($_SESSION['user_id'])) {
    die("<h3>Vui lòng đăng nhập vào tài khoản User trước khi test!</h3>");
}

$user_id = $_SESSION['user_id'];

// Cập nhật toàn bộ từ vựng đã học của user này thành "Cần ôn tập ngay hôm nay"
$sql = "UPDATE `user_vocab_progress` 
        SET `next_review_date` = CURRENT_DATE 
        WHERE `user_id` = ? AND `status` IN ('learning', 'mastered')";

$stmt = $link->prepare($sql);
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    $affected = $stmt->affected_rows;
    echo "<h3>Đã mô phỏng thành công! Cập nhật {$affected} từ vựng thành trạng thái ĐẾN HẠN ÔN TẬP (SRS).</h3>";
    echo "<p>Đang chuyển hướng về trang Dashboard trong 3 giây để xem kết quả...</p>";
    echo "<script>
            setTimeout(function() {
                window.location.href = 'pages/user/C_Dashboard_user.php';
            }, 3000);
          </script>";
} else {
    echo "<h3>Có lỗi xảy ra khi cập nhật Database: " . $link->error . "</h3>";
}
?>
