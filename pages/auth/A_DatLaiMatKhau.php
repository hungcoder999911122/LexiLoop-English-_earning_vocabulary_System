<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");
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
            <p class="A_DatLaiMatKhau_subTitle">Tạo mật khẩu mới cho tài khoản của bạn</p>

            <form id="A_DatLaiMatKhau_formDatLaiMatKhau" action="#" method="POST">
                
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


<?php
if($_SERVER['REQUEST_METHOD'] == 'POST') 
{
    $password = $_POST['A_DatLaiMatKhau_password_hash'];
    $confirm_password = $_POST['A_DatLaiMatKhau_confirm_password'];


//Kiểm tra dữ liệu
$loi="";
if(empty($password) || empty($confirm_password)) 
    {
	$loi="Vui lòng nhập đầy đủ thông tin mật khẩu mới và xác nhận mật khẩu mới.";
    }
 elseif($password != $confirm_password) 
    {
	$loi="Mật khẩu mới và xác nhận mật khẩu mới không khớp.";
    }
    elseif(strlen($password) < 6)
    {
	$loi="Mật khẩu mới phải có ít nhất 6 ký tự.";
    }

}


?>

</body>
</html>