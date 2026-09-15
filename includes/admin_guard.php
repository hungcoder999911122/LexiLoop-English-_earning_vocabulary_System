<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once($_SERVER['DOCUMENT_ROOT'] . '/Connect.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/database_objects.php');

if (!isset($_SESSION['user_id'])) {
    header('Location: ../auth/A_DangNhap.php');
    exit;
}

$adminUserId = (int) $_SESSION['user_id'];
$adminRows = dbSelectView($link, 'SELECT userID FROM vw_users WHERE userID = ? AND role = ? AND status = ? LIMIT 1', 'iss', [$adminUserId, 'admin', 'active']);
if (!$adminRows) {
    http_response_code(403);
    exit('Bạn không có quyền truy cập khu vực quản trị.');
}
