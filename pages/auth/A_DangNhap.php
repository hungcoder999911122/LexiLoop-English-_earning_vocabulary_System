<?php
// MỚI - phương thức phù hợp và tránh được vài trường hợp có thể cải thiện thêm !!!
require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");
session_start();

$loi = "";

if ($_SERVER['REQUEST_METHOD'] == 'POST') 
{
	$email    = trim($_POST['A_DangNhap_Email'] ?? '');
	$password = $_POST['A_DangNhap_password'] ?? '';

	if (empty($email) || empty($password)) 
	{
		$loi = "Vui lòng nhập đầy đủ thông tin đăng nhập.";
	} else {
		$sql = "
            SELECT userID, password_hash, full_name
            FROM Users
            WHERE email = ?
            AND status = 'active'
        ";

		$stmt = mysqli_prepare($link, $sql);
		mysqli_stmt_bind_param($stmt, "s", $email);
		mysqli_stmt_execute($stmt);
		$result = mysqli_stmt_get_result($stmt);

		if (mysqli_num_rows($result) == 0) 
		{
			$loi = "Email hoặc mật khẩu không chính xác.";
		} else 
		{
			$row = mysqli_fetch_assoc($result);

			if (!password_verify($password, $row['password_hash'])) {
				$loi = "Email hoặc mật khẩu không chính xác.";
			} else {
				// Tạo session mới sau khi đăng nhập thành công
				session_regenerate_id(true);

				$_SESSION['user_id']   = $row['userID'];
				$_SESSION['full_name'] = $row['full_name'];

				// Chuyển đến Dashboard
				header("Location: ../user/C_Dashboard_user.php?login=success");
				exit();
			}
		}

		mysqli_stmt_close($stmt);
		mysqli_close($link);
	}
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
	<meta charset="UTF-8">
	<link rel="stylesheet" type="text/css" href="/CSS/Style.css">
	<link rel="stylesheet" type="text/css" href="/CSS/A_DangNhap.css">
	<script src="/JS/jquery-4.0.0.min.js"></script>
	<title>Đăng nhập</title>
</head>

<body>
	<fieldset>
		<div class="box">
			<h2>Đăng nhập</h2>

			<?php if (!empty($loi)) { ?>
				<p style="color:red; text-align:center;"><?php echo $loi; ?></p>
			<?php } ?>

			<form method="POST" action="">
				<label>Email</label> <br />
				<input type="email" id="A_DangNhap_Email" name="A_DangNhap_Email" placeholder="Vui lòng nhập Email"> <br/>

				<label>Mật khẩu</label> <br />
				<input type="password" id="A_DangNhap_password" name="A_DangNhap_password" placeholder="Vui lòng nhập Mật khẩu">

				<div>
					<a href="A_QuenMatKhau.php">Quên mật khẩu?</a>
				</div>

				<input type="submit" name="DangNhap_btn" id="DangNhap_btn" value="Đăng nhập" />
			</form>

			<div>
				<label>hoặc</label> <br />
				<input type="button" name="A_DangNhap_TaoTaiKhoan" id="A_DangNhap_TaoTaiKhoan" value="Tạo tài khoản" />
			</div>
		</div>
	</fieldset>

	<script src="/JS/jquery-4.0.0.min.js"></script>

	<script>
		$(document).ready(function () {
			$('#A_DangNhap_TaoTaiKhoan').click(function () {
				window.location.href = "A_DangKy.php";
			});
		});
	</script>
</body>

</html>