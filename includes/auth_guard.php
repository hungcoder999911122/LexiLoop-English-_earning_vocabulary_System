<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Bắt buộc phải là user. Nếu là admin (auth_scope = admin), chuyển hướng về trang dashboard admin.
if (!isset($_SESSION['user_id']) || ($_SESSION['auth_scope'] ?? '') !== 'user') {
    if (isset($_SESSION['auth_scope']) && $_SESSION['auth_scope'] === 'admin') {
        header('Location: ../admin/D_Dashboard_admin.php');
    } else {
        header('Location: ../auth/A_DangNhap.php');
    }
    exit;
}
