<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/auth_guard.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/database_objects.php");

$user_id = (int) $_SESSION['user_id'];
$loi = "";
$thongBao = "";

// Lấy thông tin cài đặt hiện tại của người dùng
$accounts = dbCallProcedure($link, 'CALL sp_auth_get_account_by_id(?)', 'i', [$user_id]);
$userRow = $accounts[0] ?? null;
if (!$userRow) {
    exit('Không tìm thấy tài khoản.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pass      = $_POST['A_Caidattaikhoan_password'] ?? '';
    $new_pass          = $_POST['A_Caidattaikhoan_password_new'] ?? '';
    $new_pass_confirm  = $_POST['A_Caidattaikhoan_password_new_acp'] ?? '';
    $reminderEnabled   = isset($_POST['A_Caidattaikhoan_reminder']) ? 1 : 0;
    $hour              = $_POST['hour'] ?? '2000';
    $quantity          = (int) ($_POST['quantity'] ?? 20);

    $normalizedTime = preg_match('/^([01][0-9]|2[0-3])([0-5][0-9])$/', $hour)
        ? substr($hour, 0, 2) . ':' . substr($hour, 2, 2) . ':00'
        : '20:00:00';
    $dailyTarget = ($quantity >= 1 && $quantity <= 200) ? $quantity : 20;

    $new_hash = null;
    $hasPasswordError = false;

    // Nếu người dùng có nhập đổi mật khẩu
    if (!empty($new_pass) || !empty($new_pass_confirm) || !empty($current_pass)) {
        if (empty($current_pass)) {
            $loi = "Vui lòng nhập mật khẩu hiện tại để đổi mật khẩu.";
            $hasPasswordError = true;
        } elseif (!password_verify($current_pass, $userRow['password_hash'])) {
            $loi = "Mật khẩu hiện tại không chính xác.";
            $hasPasswordError = true;
        } elseif (empty($new_pass) || empty($new_pass_confirm)) {
            $loi = "Vui lòng nhập đầy đủ mật khẩu mới và xác nhận mật khẩu mới.";
            $hasPasswordError = true;
        } elseif ($new_pass !== $new_pass_confirm) {
            $loi = "Mật khẩu mới và xác nhận mật khẩu không khớp.";
            $hasPasswordError = true;
        } elseif (strlen($new_pass) < 6) {
            $loi = "Mật khẩu mới phải có ít nhất 6 ký tự.";
            $hasPasswordError = true;
        } else {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        }
    }

    if (!$hasPasswordError) {
        try {
            dbCallProcedure(
                $link,
                'CALL sp_auth_update_account_settings(?, ?, ?, ?, ?)',
                'isisi',
                [$user_id, $new_hash, $reminderEnabled, $normalizedTime, $dailyTarget]
            );
            $thongBao = "Cập nhật cài đặt thành công!";

            // Tải lại thông tin mới
            $accounts = dbCallProcedure($link, 'CALL sp_auth_get_account_by_id(?)', 'i', [$user_id]);
            $userRow = $accounts[0] ?? $userRow;
        } catch (Throwable $error) {
            error_log('Lỗi cập nhật cài đặt: ' . $error->getMessage());
            $loi = "Không thể cập nhật cài đặt lúc này.";
        }
    }
}

$curReminder = (int) ($userRow['daily_reminder_enabled'] ?? 0);
$curTime = $userRow['reminder_time'] ?? '20:00:00';
$curHourVal = str_replace(':', '', substr($curTime, 0, 5));
$curTarget = (int) ($userRow['daily_target_words'] ?? 20);
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

		<?php if (!empty($thongBao)): ?>
			<div style="background-color: #d4edda; color: #155724; padding: 12px; margin-bottom: 20px; border-radius: 6px; text-align: center;">
				<?php echo htmlspecialchars($thongBao); ?>
			</div>
		<?php endif; ?>

		<?php if (!empty($loi)): ?>
			<div style="background-color: #f8d7da; color: #721c24; padding: 12px; margin-bottom: 20px; border-radius: 6px; text-align: center;">
				<?php echo htmlspecialchars($loi); ?>
			</div>
		<?php endif; ?>

		<div class="top-row">

			<div class="box">
				<h3>Đổi mật khẩu</h3>
				<form method="POST" action="">
					<label>Mật khẩu hiện tại</label> <br />
					<input id="A_Caidattaikhoan_password" name="A_Caidattaikhoan_password" type="password" placeholder="Để trống nếu không muốn đổi"> <br /><br />

					<label>Mật khẩu mới</label> <br />
					<input id="A_Caidattaikhoan_password_new" name="A_Caidattaikhoan_password_new" type="password" placeholder="Ít nhất 6 ký tự"> <br /><br />

					<label>Xác nhận mật khẩu mới</label> <br />
					<input id="A_Caidattaikhoan_password_new_acp" name="A_Caidattaikhoan_password_new_acp" type="password" placeholder="Nhập lại mật khẩu mới"> <br /><br />
			</div>
				
			<div class="box">
				<h3>Nhắc nhở ôn tập</h3>
				
					<div>
						<input type="checkbox" id="A_Caidattaikhoan_reminder" name="A_Caidattaikhoan_reminder" <?php echo $curReminder === 1 ? 'checked' : ''; ?>>
						<label for="A_Caidattaikhoan_reminder">Bật nhắc nhở ôn tập hằng ngày gửi về Email</label>
					</div>
					<br />
					<div>
						<label for="hour">Giờ nhận nhắc nhở</label> <br />
							<select name="hour" id="hour" data-selected="<?php echo htmlspecialchars($curHourVal); ?>">
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
							<option value="5" <?php echo $curTarget === 5 ? 'selected' : ''; ?>>5 từ</option>
							<option value="10" <?php echo $curTarget === 10 ? 'selected' : ''; ?>>10 từ</option>
							<option value="20" <?php echo $curTarget === 20 ? 'selected' : ''; ?>>20 từ</option>
							<option value="30" <?php echo $curTarget === 30 ? 'selected' : ''; ?>>30 từ</option>
							<option value="50" <?php echo $curTarget === 50 ? 'selected' : ''; ?>>50 từ</option>
						</select>
					</div>
				</div>
			

		<div>
			<input type="submit" name="saved" value="Lưu thay đổi" />

		</div>
				</form>
	</div>

	<script>
		$(document).ready(function() {
			var selectedHour = $('#hour').data('selected');
			if (selectedHour) {
				$('#hour').val(selectedHour);
			}
		});
	</script>
</body>
</html>
