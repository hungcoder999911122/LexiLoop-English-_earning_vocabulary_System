<?php
// End-to-end test simulating web requests for C_Hosocanhan and A_Caidattaikhoan
require_once __DIR__ . '/../Connect.php';
require_once __DIR__ . '/../includes/database_objects.php';

echo "=== BẮT ĐẦU TEST E2E CHO 2 TRANG C_Hosocanhan và A_Caidattaikhoan ===\n";

// 1. Khởi tạo session cho user_id = 2 (Lê Quân)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$_SESSION['user_id'] = 2;
$_SESSION['full_name'] = 'Lê Quân';
$_SESSION['role'] = 'user';
$_SESSION['auth_scope'] = 'user';
$_SESSION['email'] = 'quan@gmail.com';

// 2. Test GET C_Hosocanhan.php
ob_start();
$_SERVER['REQUEST_METHOD'] = 'GET';
$_SERVER['DOCUMENT_ROOT'] = dirname(__DIR__);
include dirname(__DIR__) . '/pages/user/C_Hosocanhan.php';
$htmlHosocanhan = ob_get_clean();

assert(strpos($htmlHosocanhan, 'Hồ sơ cá nhân') !== false, 'Trang C_Hosocanhan không chứa tiêu đề');
assert(strpos($htmlHosocanhan, 'Lê Quân') !== false, 'Trang C_Hosocanhan không hiển thị tên Lê Quân');
assert(strpos($htmlHosocanhan, 'C_Hosocanhan_btnDoiAnh') !== false, 'Nút đổi ảnh không tồn tại');
assert(strpos($htmlHosocanhan, 'C_Hosocanhan_formThongTin') !== false, 'Form không tồn tại');
echo "✓ GET C_Hosocanhan.php render thành công đầy đủ UI, thông tin và thành tựu học tập!\n";

// 3. Test POST C_Hosocanhan.php (Cập nhật thông tin & upload avatar giả lập)
// Tạo 1 file ảnh PNG test chuẩn bằng raw bytes
$testImgPath = sys_get_temp_dir() . '/test_avatar_' . time() . '.png';
file_put_contents($testImgPath, base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg=='));

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['C_Hosocanhan_full_name'] = 'Lê Quân Pro';
$_POST['C_Hosocanhan_email'] = 'quan@gmail.com';
$_POST['C_Hosocanhan_ngay_sinh'] = '2000-11-22';
$_POST['C_Hosocanhan_trinh_do'] = 'Trung cấp trên (B2)';
$_FILES['C_Hosocanhan_avatar_file'] = [
    'name' => 'my_avatar.png',
    'type' => 'image/png',
    'tmp_name' => $testImgPath,
    'error' => UPLOAD_ERR_OK,
    'size' => filesize($testImgPath)
];

ob_start();
include dirname(__DIR__) . '/pages/user/C_Hosocanhan.php';
$htmlPostProfile = ob_get_clean();

assert(strpos($htmlPostProfile, 'Cập nhật thông tin hồ sơ thành công!') !== false, 'Không thấy thông báo thành công');
assert($_SESSION['full_name'] === 'Lê Quân Pro', 'Session full_name không được cập nhật');
assert($_SESSION['user_profile_ngay_sinh'] === '2000-11-22', 'Session ngay_sinh không được cập nhật');
assert($_SESSION['user_profile_trinh_do'] === 'Trung cấp trên (B2)', 'Session trinh_do không được cập nhật');
assert(!empty($_SESSION['avatar_url']), 'Session avatar_url không được set');
echo "✓ POST C_Hosocanhan.php cập nhật thành công (Họ tên, Email, Ngày sinh, Trình độ, Avatar)!\n";

// Kiểm tra trong DB
$checkUser = dbCallProcedure($link, 'CALL sp_auth_get_account_by_id(?)', 'i', [2]);
assert($checkUser[0]['full_name'] === 'Lê Quân Pro', 'DB full_name không khớp');
assert($checkUser[0]['date_of_birth'] === '2000-11-22', 'DB date_of_birth không khớp');
assert($checkUser[0]['target_level'] === 'Trung cấp trên (B2)', 'DB target_level không khớp');
assert(!empty($checkUser[0]['avatar_url']), 'DB avatar_url không có');
echo "✓ Dữ liệu hồ sơ đã được lưu bền vững vào database!\n";

// 4. Test GET A_Caidattaikhoan.php
unset($_POST, $_FILES);
$_SERVER['REQUEST_METHOD'] = 'GET';
ob_start();
include dirname(__DIR__) . '/pages/auth/A_Caidattaikhoan.php';
$htmlCaidat = ob_get_clean();

assert(strpos($htmlCaidat, 'Cài đặt tài khoản') !== false, 'Trang A_Caidattaikhoan không chứa tiêu đề');
assert(strpos($htmlCaidat, 'Đổi mật khẩu') !== false, 'Card Đổi mật khẩu không tồn tại');
assert(strpos($htmlCaidat, 'Nhắc nhở ôn tập') !== false, 'Card Nhắc nhở không tồn tại');
assert(strpos($htmlCaidat, 'Mục tiêu ôn tập') !== false, 'Card Mục tiêu không tồn tại');
echo "✓ GET A_Caidattaikhoan.php render thành công đầy đủ giao diện cài đặt!\n";

// 5. Test POST A_Caidattaikhoan.php (Thay đổi cài đặt reminder & target words)
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['A_Caidattaikhoan_reminder'] = 'on';
$_POST['hour'] = '0830';
$_POST['quantity'] = '30';
$_POST['saved'] = '1';

ob_start();
include dirname(__DIR__) . '/pages/auth/A_Caidattaikhoan.php';
$htmlPostSettings = ob_get_clean();

assert(strpos($htmlPostSettings, 'Cập nhật cài đặt thành công!') !== false, 'Không thấy thông báo cập nhật cài đặt thành công');
$checkSettings = dbCallProcedure($link, 'CALL sp_auth_get_account_by_id(?)', 'i', [2]);
assert((int)$checkSettings[0]['daily_reminder_enabled'] === 1, 'daily_reminder_enabled không đúng');
assert($checkSettings[0]['reminder_time'] === '08:30:00', 'reminder_time không đúng');
assert((int)$checkSettings[0]['daily_target_words'] === 30, 'daily_target_words không đúng');
echo "✓ POST A_Caidattaikhoan.php lưu cấu hình học tập thành công vào DB!\n";

// 6. Test POST A_Caidattaikhoan.php (Đổi mật khẩu)
// Thiết lập trước mật khẩu hiện tại đã biết là 'matkhau123456'
$knownCurrentPass = 'matkhau123456';
$knownHash = password_hash($knownCurrentPass, PASSWORD_DEFAULT);
dbCallProcedure($link, 'CALL sp_auth_update_account_settings(?, ?, ?, ?, ?)', 'isisi', [
    2,
    $knownHash,
    1,
    '20:00:00',
    20
]);

$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['A_Caidattaikhoan_password'] = $knownCurrentPass;
$_POST['A_Caidattaikhoan_password_new'] = 'newpass654321';
$_POST['A_Caidattaikhoan_password_new_acp'] = 'newpass654321';
$_POST['A_Caidattaikhoan_reminder'] = 'on';
$_POST['hour'] = '2000';
$_POST['quantity'] = '20';

ob_start();
include dirname(__DIR__) . '/pages/auth/A_Caidattaikhoan.php';
$htmlPassChange = ob_get_clean();

assert(strpos($htmlPassChange, 'Đổi mật khẩu và cập nhật cài đặt thành công!') !== false, 'Không thấy thông báo đổi mk thành công');
$checkPass = dbCallProcedure($link, 'CALL sp_auth_get_account_by_id(?)', 'i', [2]);
assert(password_verify('newpass654321', $checkPass[0]['password_hash']), 'Mật khẩu mới không khớp');
echo "✓ POST A_Caidattaikhoan.php đổi mật khẩu thành công!\n";

// 7. Test kiểm tra mật khẩu hiện tại sai
$_SERVER['REQUEST_METHOD'] = 'POST';
$_POST['A_Caidattaikhoan_password'] = 'saimatkhau';
$_POST['A_Caidattaikhoan_password_new'] = 'anotherpass';
$_POST['A_Caidattaikhoan_password_new_acp'] = 'anotherpass';

ob_start();
include dirname(__DIR__) . '/pages/auth/A_Caidattaikhoan.php';
$htmlPassFail = ob_get_clean();

assert(strpos($htmlPassFail, 'Mật khẩu hiện tại không chính xác.') !== false, 'Không bắt được lỗi sai mật khẩu');
echo "✓ POST A_Caidattaikhoan.php chặn đúng khi nhập sai mật khẩu hiện tại!\n";

// Dọn dẹp: Khôi phục lại user 2 về ban đầu
$defaultPassHash = '$2y$10$Z2/VG5nprFQcK/p6Gdaj0eYcCOx/q48FpHzysLHp04mFLgsJntsyC';
dbCallProcedure($link, 'CALL sp_auth_update_account_settings(?, ?, ?, ?, ?)', 'isisi', [
    2,
    $defaultPassHash,
    1,
    '21:00:00',
    15
]);
dbCallProcedure($link, 'CALL sp_update_user_profile(?, ?, ?, ?, ?, ?)', 'isssss', [
    2,
    'Lê Quân',
    'quan@gmail.com',
    null,
    '2002-05-15',
    'Trung cấp (B1)'
]);
if (file_exists($testImgPath)) {
    @unlink($testImgPath);
}

echo "=== TOÀN BỘ CÁC BƯỚC TEST E2E ĐÃ HOÀN TẤT VÀ VƯỢT QUA 100%! ===\n";
