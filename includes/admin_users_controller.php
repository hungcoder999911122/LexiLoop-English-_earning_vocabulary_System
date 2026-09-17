<?php
declare(strict_types=1);
require_once __DIR__ . '/admin_guard.php';
require_once __DIR__ . '/admin_pagination.php';
$link = getDatabaseConnection();

if (empty($_SESSION['admin_accounts_csrf'])) {
    $_SESSION['admin_accounts_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $_SESSION['admin_accounts_csrf'];
$thongBao = $_SESSION['admin_accounts_flash'] ?? '';
unset($_SESSION['admin_accounts_flash']);
$loaiThongBao = $thongBao !== '' ? 'thanhcong' : '';
$accountToolsReady = false;
try {
    $capabilities = dbCallProcedure($link, 'CALL sp_admin_account_capabilities()');
    $accountToolsReady = (int) ($capabilities[0]['ready'] ?? 0) === 1;
} catch (mysqli_sql_exception $error) {
    if ((int) $error->getCode() !== 1305) {
        error_log('Admin account capabilities: ' . $error->getMessage());
    }
}

function adminAccountPostText(string $name): string
{
    $value = $_POST[$name] ?? '';
    return is_string($value) ? $value : '';
}

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if (!hash_equals($csrfToken, adminAccountPostText('csrf_token'))) {
            throw new RuntimeException('Phiên thao tác không hợp lệ. Vui lòng tải lại trang.');
        }
        $action = adminAccountPostText('hanhdong');
        if (!in_array($action, ['them', 'doiquyen', 'xoa', 'doitrangthai'], true)) {
            throw new RuntimeException('Thao tác không hợp lệ.');
        }
        if (!$accountToolsReady && $action !== 'doitrangthai') {
            throw new RuntimeException('Chức năng quản lý tài khoản chưa được cập nhật trong CSDL.');
        }
        if ($action === 'them') {
            $name = trim(adminAccountPostText('full_name'));
            $email = strtolower(trim(adminAccountPostText('email')));
            $password = adminAccountPostText('password');
            $newRole = adminAccountPostText('new_role');
            if (!preg_match('/^.{2,100}$/uD', $name)) throw new RuntimeException('Họ tên phải có từ 2 đến 100 ký tự.');
            if (strlen($email) > 50 || !filter_var($email, FILTER_VALIDATE_EMAIL)) throw new RuntimeException('Email không hợp lệ hoặc dài quá 50 ký tự.');
            if (strlen($password) < 8 || strlen($password) > 72) throw new RuntimeException('Mật khẩu phải có từ 8 đến 72 byte.');
            if ($password !== adminAccountPostText('password_confirm')) throw new RuntimeException('Xác nhận mật khẩu chưa khớp.');
            if (!in_array($newRole, ['user', 'admin'], true)) throw new RuntimeException('Vai trò không hợp lệ.');
            dbCallProcedure($link, 'CALL sp_admin_create_account(?, ?, ?, ?, ?)', 'issss', [$adminUserId, $name, $email, password_hash($password, PASSWORD_DEFAULT), $newRole]);
            $thongBao = 'Đã tạo tài khoản thành công.';
        } else {
            $userId = filter_var(adminAccountPostText('userID'), FILTER_VALIDATE_INT);
            if (!$userId || $userId < 1) throw new RuntimeException('Tài khoản không hợp lệ.');
            if ($userId === $adminUserId) throw new RuntimeException('Không thể khóa, xóa hoặc đổi quyền tài khoản đang đăng nhập.');
            if ($action === 'doitrangthai') {
                $newStatus = adminAccountPostText('new_status');
                if (!in_array($newStatus, ['active', 'locked'], true)) throw new RuntimeException('Trạng thái không hợp lệ.');
                dbCallProcedure($link, 'CALL sp_admin_change_user_status(?, ?, ?)', 'iis', [$adminUserId, $userId, $newStatus]);
                $thongBao = $newStatus === 'locked' ? 'Đã khóa tài khoản.' : 'Đã mở khóa tài khoản.';
            } elseif ($action === 'doiquyen') {
                $newRole = adminAccountPostText('new_role');
                if (!in_array($newRole, ['user', 'admin'], true)) throw new RuntimeException('Vai trò không hợp lệ.');
                dbCallProcedure($link, 'CALL sp_admin_change_user_role(?, ?, ?)', 'iis', [$adminUserId, $userId, $newRole]);
                $thongBao = 'Đã cập nhật vai trò tài khoản.';
            } else {
                dbCallProcedure($link, 'CALL sp_admin_delete_account(?, ?)', 'ii', [$adminUserId, $userId]);
                $thongBao = 'Đã xóa tài khoản.';
            }
        }
        // Post/Redirect/Get: tải lại trang không gửi lại thao tác tạo/xóa tài khoản.
        $_SESSION['admin_accounts_flash'] = $thongBao;
        header('Location: /pages/admin/D_Quanlynguoidung.php' . adminPageUrl());
        exit;
    }
} catch (Throwable $error) {
    error_log('Admin account operation: ' . $error->getMessage());
    $messages = [
        'admin required' => 'Tài khoản đang thao tác không còn quyền quản trị.',
        'self account change denied' => 'Không thể thay đổi tài khoản đang đăng nhập.',
        'account not found' => 'Tài khoản không còn tồn tại.',
        'account must be locked' => 'Vui lòng khóa tài khoản trước khi xóa.',
        'account has learning data' => 'Tài khoản có dữ liệu học tập. Hãy giữ trạng thái khóa để bảo toàn lịch sử.',
        'account has personal data' => 'Tài khoản có bộ từ hoặc từ cá nhân. Hãy dùng thao tác khóa.',
        'invalid role' => 'Vai trò không hợp lệ.',
        'invalid status' => 'Trạng thái không hợp lệ.',
    ];
    $thongBao = $error instanceof RuntimeException && !($error instanceof mysqli_sql_exception)
        ? $error->getMessage() : 'Không thể thực hiện thao tác lúc này.';
    foreach ($messages as $key => $message) {
        if (stripos($error->getMessage(), $key) !== false) $thongBao = $message;
    }
    if ((int) $error->getCode() === 1062) $thongBao = 'Email đã tồn tại trong hệ thống.';
    if ((int) $error->getCode() === 1305) $thongBao = 'Chức năng quản lý tài khoản chưa được cập nhật trong CSDL.';
    if (in_array((int) $error->getCode(), [1205, 1213], true)) $thongBao = 'Tài khoản đang được cập nhật đồng thời. Vui lòng thử lại.';
    $loaiThongBao = 'loi';
}

$keyword = adminQueryText('q');
$role = adminQueryText('role', 'tat_ca');
$status = adminQueryText('status', 'tat_ca');
$role = in_array($role, ['user', 'admin'], true) ? $role : 'tat_ca';
$status = in_array($status, ['active', 'locked'], true) ? $status : 'tat_ca';
$conditions = [];
$types = '';
$values = [];
if ($keyword !== '') {
    $conditions[] = "(full_name LIKE ? ESCAPE '!' OR email LIKE ? ESCAPE '!')";
    $types .= 'ss';
    $values = [adminSearchPattern($keyword), adminSearchPattern($keyword)];
}
foreach (['role' => $role, 'status' => $status] as $column => $value) {
    if ($value !== 'tat_ca') {
        $conditions[] = $column . ' = ?';
        $types .= 's';
        $values[] = $value;
    }
}
$where = $conditions ? ' WHERE ' . implode(' AND ', $conditions) : '';
$userPagination = adminPaginateView($link, 'SELECT COUNT(*) AS total FROM vw_users' . $where,
    'SELECT userID, full_name, email, role, status, created_at FROM vw_users' . $where . ' ORDER BY created_at DESC, userID DESC',
    'page', 10, $types, $values);
$ketQuaDanhSach = $userPagination['rows'];
$tongSoNguoiDung = (int) dbSelectView($link, 'SELECT COUNT(*) AS total FROM vw_users')[0]['total'];
