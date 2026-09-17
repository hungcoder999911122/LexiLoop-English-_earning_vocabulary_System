<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/Connect.php';
require_once __DIR__ . '/database_objects.php';
$link = getDatabaseConnection();

if (!isset($_SESSION['user_id'])) {
    header('Location: /pages/admin/D_DangNhapAdmin.php');
    exit;
}

// The login scope prevents a normal user-login session being treated as an
// administration session. The database role check below remains authoritative.
if (($_SESSION['auth_scope'] ?? '') !== 'admin') {
    http_response_code(403);
    exit('Phiên đăng nhập này không có phạm vi quản trị.');
}

$adminUserId = (int) $_SESSION['user_id'];
$adminRows = dbSelectView($link, 'SELECT userID FROM vw_users WHERE userID = ? AND role = ? AND status = ? LIMIT 1', 'iss', [$adminUserId, 'admin', 'active']);
if (!$adminRows) {
    http_response_code(403);
    exit('Bạn không có quyền truy cập khu vực quản trị.');
}
