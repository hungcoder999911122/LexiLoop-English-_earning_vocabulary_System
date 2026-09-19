<?php
require_once __DIR__ . '/../Connect.php';
require_once __DIR__ . '/../includes/database_objects.php';

echo "=== TEST 1: Kiểm tra lấy tài khoản bằng ID qua sp_auth_get_account_by_id ===\n";
$accounts = dbCallProcedure($link, 'CALL sp_auth_get_account_by_id(?)', 'i', [2]);
assert(!empty($accounts), 'Không tìm thấy user 2');
$user2 = $accounts[0];
echo "User 2: full_name=" . $user2['full_name'] . ", email=" . $user2['email'] . ", date_of_birth=" . ($user2['date_of_birth'] ?? 'null') . ", target_level=" . ($user2['target_level'] ?? 'null') . "\n";
echo "✓ Lấy thông tin user 2 thành công!\n\n";

echo "=== TEST 2: Kiểm tra sp_update_user_profile (cập nhật thông tin & độ trơ/idempotent) ===\n";
// Lần 1: cập nhật tên và ngày sinh mới
dbCallProcedure($link, 'CALL sp_update_user_profile(?, ?, ?, ?, ?, ?)', 'isssss', [
    2,
    'Lê Quân Test',
    'quan@gmail.com',
    '/assets/images/avatars/test_avatar.png',
    '2001-08-20',
    'Cao cấp (C1)'
]);
$accAfter1 = dbCallProcedure($link, 'CALL sp_auth_get_account_by_id(?)', 'i', [2]);
assert($accAfter1[0]['full_name'] === 'Lê Quân Test', 'full_name không khớp');
assert($accAfter1[0]['date_of_birth'] === '2001-08-20', 'date_of_birth không khớp');
assert($accAfter1[0]['target_level'] === 'Cao cấp (C1)', 'target_level không khớp');
echo "✓ Cập nhật lần 1 thành công!\n";

// Lần 2: chạy lại với đúng dữ liệu đó -> trước đây bị lỗi 'active user not found', giờ phải thành công
dbCallProcedure($link, 'CALL sp_update_user_profile(?, ?, ?, ?, ?, ?)', 'isssss', [
    2,
    'Lê Quân Test',
    'quan@gmail.com',
    '/assets/images/avatars/test_avatar.png',
    '2001-08-20',
    'Cao cấp (C1)'
]);
echo "✓ Chạy lại cùng dữ liệu không bị lỗi false 'active user not found'!\n\n";

echo "=== TEST 3: Kiểm tra ràng buộc trùng email sp_update_user_profile ===\n";
$duplicateCaught = false;
try {
    // User 1 là admin@example.com, thử đổi user 2 sang admin@example.com
    dbCallProcedure($link, 'CALL sp_update_user_profile(?, ?, ?, ?, ?, ?)', 'isssss', [
        2,
        'Lê Quân Test',
        'admin@example.com',
        null,
        null,
        null
    ]);
} catch (Throwable $e) {
    $duplicateCaught = true;
    echo "✓ Bắt lỗi trùng email chính xác: " . $e->getMessage() . "\n";
}
assert($duplicateCaught, 'Phải chặn trùng lặp email của user khác');
echo "\n";

echo "=== TEST 4: Kiểm tra sp_auth_update_account_settings ===\n";
// Cập nhật cài đặt: reminder 1, time 19:30:00, target 30
dbCallProcedure($link, 'CALL sp_auth_update_account_settings(?, ?, ?, ?, ?)', 'isisi', [
    2,
    null,
    1,
    '19:30:00',
    30
]);
$accSetting1 = dbCallProcedure($link, 'CALL sp_auth_get_account_by_id(?)', 'i', [2]);
assert((int)$accSetting1[0]['daily_reminder_enabled'] === 1, 'reminder_enabled sai');
assert($accSetting1[0]['reminder_time'] === '19:30:00', 'reminder_time sai');
assert((int)$accSetting1[0]['daily_target_words'] === 30, 'daily_target_words sai');
echo "✓ Cập nhật cài đặt tài khoản lần 1 thành công!\n";

// Lần 2: submit lại cùng cài đặt -> không được báo lỗi 'active user not found'
dbCallProcedure($link, 'CALL sp_auth_update_account_settings(?, ?, ?, ?, ?)', 'isisi', [
    2,
    null,
    1,
    '19:30:00',
    30
]);
echo "✓ Submit lại cùng cài đặt thành công không bị lỗi!\n\n";

echo "=== TEST 5: Đổi mật khẩu qua sp_auth_update_account_settings ===\n";
$newHash = password_hash('matkhau123456', PASSWORD_DEFAULT);
dbCallProcedure($link, 'CALL sp_auth_update_account_settings(?, ?, ?, ?, ?)', 'isisi', [
    2,
    $newHash,
    1,
    '19:30:00',
    30
]);
$accSetting2 = dbCallProcedure($link, 'CALL sp_auth_get_account_by_id(?)', 'i', [2]);
assert(password_verify('matkhau123456', $accSetting2[0]['password_hash']), 'Mật khẩu mới không verify được');
echo "✓ Đổi mật khẩu thành công và xác thực chính xác!\n\n";

// Reset lại mật khẩu gốc cho user 2 (để môi trường test ổn định)
$originalHash = '$2y$10$Z2/VG5nprFQcK/p6Gdaj0eYcCOx/q48FpHzysLHp04mFLgsJntsyC';
dbCallProcedure($link, 'CALL sp_auth_update_account_settings(?, ?, ?, ?, ?)', 'isisi', [
    2,
    $originalHash,
    1,
    '21:00:00',
    15
]);
// Reset lại tên gốc cho user 2
dbCallProcedure($link, 'CALL sp_update_user_profile(?, ?, ?, ?, ?, ?)', 'isssss', [
    2,
    'Lê Quân',
    'quan@gmail.com',
    null,
    '2002-05-15',
    'Trung cấp (B1)'
]);
echo "✓ Đã khôi phục dữ liệu gốc cho user 2.\n";
echo "=== TẤT CẢ CÁC BÀI TEST ĐÃ VƯỢT QUA 100%! ===\n";
