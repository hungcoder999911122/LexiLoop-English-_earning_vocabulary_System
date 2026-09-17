<?php

session_start();

// Lưu loại phiên trước khi xóa session để chọn đúng trang đăng nhập.
// Phiên cũ chưa có auth_scope thì dùng vai trò làm phương án tương thích.
$isAdminSession = ($_SESSION['auth_scope'] ?? '') === 'admin'
    || (!isset($_SESSION['auth_scope']) && ($_SESSION['role'] ?? '') === 'admin');
$redirectUrl = $isAdminSession
    ? '/pages/admin/D_DangNhapAdmin.php?logout=success'
    : '/pages/main/B_homepage.html?logout=success';

$_SESSION = [];

// Xóa cả cookie phiên trong trình duyệt, không chỉ dữ liệu trên server.
if (ini_get('session.use_cookies')) {
    $cookie = session_get_cookie_params();
    setcookie(session_name(), '', [
        'expires' => time() - 3600,
        'path' => $cookie['path'],
        'domain' => $cookie['domain'],
        'secure' => $cookie['secure'],
        'httponly' => $cookie['httponly'],
        'samesite' => $cookie['samesite'] ?? '',
    ]);
}

session_destroy();

header('Location: ' . $redirectUrl);
exit;
