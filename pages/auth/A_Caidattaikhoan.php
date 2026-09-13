<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");

// auth_guard.php đã xác thực session trước khi trang sử dụng user_id.
$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? (int) $_SESSION['user_id'] : null;

$loi = "";

//Xử lý form, CHỈ chạy sau khi đã chắc chắn đăng nhập rồi
if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $A_Caidattaikhoan_password         = $_POST['A_Caidattaikhoan_password'];
    $A_Caidattaikhoan_password_new     = $_POST['A_Caidattaikhoan_password_new'];
    $A_Caidattaikhoan_password_new_acp = $_POST['A_Caidattaikhoan_password_new_acp'];
    $A_Caidattaikhoan_reminder         = isset($_POST['A_Caidattaikhoan_reminder']);
    $hour                              = $_POST['hour'];
    $quantity                          = $_POST['quantity'];

    if(empty($A_Caidattaikhoan_password_new) || empty($A_Caidattaikhoan_password_new_acp)) {
        $loi = "Vui lòng nhập đầy đủ thông tin mật khẩu mới và xác nhận mật khẩu mới.";
    }
    elseif($A_Caidattaikhoan_password_new != $A_Caidattaikhoan_password_new_acp) {
        $loi = "Mật khẩu mới và xác nhận mật khẩu mới không khớp.";
    }
    elseif(strlen($A_Caidattaikhoan_password_new) < 6) {
        $loi = "Mật khẩu mới phải có ít nhất 6 ký tự.";
    }
    else {
        // Kiểm tra mật khẩu hiện tại đúng không
        $user_id = $_SESSION['user_id'];
        $sql = "SELECT password_hash FROM Users WHERE userID='$user_id'";
        $result = mysqli_query($link, $sql);
        $row = mysqli_fetch_assoc($result);

        if(!password_verify($A_Caidattaikhoan_password, $row['password_hash'])) {
            $loi = "Mật khẩu hiện tại không đúng.";
        } else {
            // BƯỚC 4-5: Mã hóa mật khẩu mới, UPDATE vào DB
            $new_hash = password_hash($A_Caidattaikhoan_password_new, PASSWORD_DEFAULT);
            $sql = "UPDATE Users SET password_hash='$new_hash', daily_reminder_enabled='" . ($A_Caidattaikhoan_reminder ? 1 : 0) . "', reminder_time='$hour', daily_target_words='$quantity' WHERE userID='$user_id'";

            if(mysqli_query($link, $sql)) {
                $loi = "Cập nhật thành công!";
            } else {
                $loi = "Lỗi: " . mysqli_error($link);
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" type="text/css" href="/CSS/Style.css"> 
	<link rel="stylesheet" type="text/css" href="/CSS/A_Caidattaikhoan.css"> 
	<script src="/JS/jquery-4.0.0.min.js"></script> 
	<title>Cài đặt tài khoản</title>
</head>
<body>

	<div class="wrapper">
		<h2>Cài đặt tài khoản</h2>

		<div class="top-row">

			<div class="box">
				<h3>Đổi mật khẩu</h3>
				<form method="POST" action="">
					<label>Mật khẩu hiện tại</label> <br />
					<input id="A_Caidattaikhoan_password" name="A_Caidattaikhoan_password" type="password"> <br /><br />

					<label>Mật khẩu mới</label> <br />
					<input id="A_Caidattaikhoan_password_new" name="A_Caidattaikhoan_password_new" type="password"> <br /><br />

					<label>Xác nhận mật khẩu mới</label> <br />
					<input id="A_Caidattaikhoan_password_new_acp" name="A_Caidattaikhoan_password_new_acp" type="password"> <br /><br />
			</div>
				
		

			<div class="box">
				<h3>Nhắc nhở ôn tập</h3>
				
					<div>
						<input type="checkbox" id="A_Caidattaikhoan_reminder" name="A_Caidattaikhoan_reminder">
						<label for="A_Caidattaikhoan_reminder">Bật nhắc nhở ôn tập hằng ngày gửi về Email</label>
					</div>
					<br />
					<div>
						<label for="hour">Giờ nhận nhắc nhở</label> <br />
							<select name="hour" id="hour">
                                <option value="0015">00:15</option>
                                <option value="0030">00:30</option>
                                <option value="0045">00:45</option>
                                <option value="0100">01:00</option>
                                <option value="0115">01:15</option>
                                <option value="0130">01:30</option>
                                <option value="0145">01:45</option>
                                <option value="0200">02:00</option>
                                <option value="0215">02:15</option>
                                <option value="0230">02:30</option>
                                <option value="0245">02:45</option>
                                <option value="0300">03:00</option>
                                <option value="0315">03:15</option>
                                <option value="0330">03:30</option>
                                <option value="0345">03:45</option>
                                <option value="0400">04:00</option>
                                <option value="0415">04:15</option>
                                <option value="0430">04:30</option>
                                <option value="0445">04:45</option>
                                <option value="0500">05:00</option>
                                <option value="0515">05:15</option>
                                <option value="0530">05:30</option>
                                <option value="0545">05:45</option>
                                <option value="0600">06:00</option>
                                <option value="0615">06:15</option>
                                <option value="0630">06:30</option>
                                <option value="0645">06:45</option>
                                <option value="0700">07:00</option>
                                <option value="0715">07:15</option>
                                <option value="0730">07:30</option>
                                <option value="0745">07:45</option>
                                <option value="0800">08:00</option>
                                <option value="0815">08:15</option>
                                <option value="0830">08:30</option>
                                <option value="0845">08:45</option>
                                <option value="0900">09:00</option>
                                <option value="0915">09:15</option>
                                <option value="0930">09:30</option>
                                <option value="0945">09:45</option>
                                <option value="1000">10:00</option>
                                <option value="1015">10:15</option>
                                <option value="1030">10:30</option>
                                <option value="1045">10:45</option>
                                <option value="1100">11:00</option>
                                <option value="1115">11:15</option>
                                <option value="1130">11:30</option>
                                <option value="1145">11:45</option>
                                <option value="1200">12:00</option>
                                <option value="1215">12:15</option>
                                <option value="1230">12:30</option>
                                <option value="1245">12:45</option>
                                <option value="1300">13:00</option>
                                <option value="1315">13:15</option>
                                <option value="1330">13:30</option>
                                <option value="1345">13:45</option>
                                <option value="1400">14:00</option>
                                <option value="1415">14:15</option>
                                <option value="1430">14:30</option>
                                <option value="1445">14:45</option>
                                <option value="1500">15:00</option>
                                <option value="1515">15:15</option>
                                <option value="1530">15:30</option>
                                <option value="1545">15:45</option>
                                <option value="1600">16:00</option>
                                <option value="1615">16:15</option>
                                <option value="1630">16:30</option>
                                <option value="1645">16:45</option>
                                <option value="1700">17:00</option>
                                <option value="1715">17:15</option>
                                <option value="1730">17:30</option>
                                <option value="1745">17:45</option>
                                <option value="1800">18:00</option>
                                <option value="1815">18:15</option>
                                <option value="1830">18:30</option>
                                <option value="1845">18:45</option>
                                <option value="1900">19:00</option>
                                <option value="1915">19:15</option>
                                <option value="1930">19:30</option>
                                <option value="1945">19:45</option>
                                <option value="2000">20:00</option>
                                <option value="2015">20:15</option>
                                <option value="2030">20:30</option>
                                <option value="2045">20:45</option>
                                <option value="2100">21:00</option>
                                <option value="2115">21:15</option>
                                <option value="2130">21:30</option>
                                <option value="2145">21:45</option>
                                <option value="2200">22:00</option>
                                <option value="2215">22:15</option>
                                <option value="2230">22:30</option>
                                <option value="2245">22:45</option>
                                <option value="2300">23:00</option>
                                <option value="2315">23:15</option>
                                <option value="2330">23:30</option>
                                <option value="2345">23:45</option>
						</select>
					</div>
				
		</div>

		<div class="box">
			<h3>Tùy chọn học tập</h3>
			
				<div class="flex-row">
					<div class="form-group">
						<label for="quantity">Số từ ôn tập mỗi ngày</label> <br />
						<select name="quantity" id="quantity">
							<option value="10">10</option>
							<option value="20" selected>20</option>
							<option value="30">30</option>
						</select>
					</div>
				</div>
			

		<div>
			<input type="submit" name="saved" value="Lưu thay đổi" />

		</div>
				</form>
	</div>


</body>
</html>
