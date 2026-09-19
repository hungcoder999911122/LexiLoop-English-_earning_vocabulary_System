<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/Connect.php';
require_once dirname(__DIR__, 2) . '/includes/database_objects.php';
$link = getDatabaseConnection();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$loi = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim((string) ($_POST['email'] ?? ''));
    $password = (string) ($_POST['password'] ?? '');

    if ($email === '' || $password === '') {
        $loi = 'Vui lòng nhập email và mật khẩu.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $loi = 'Thông tin đăng nhập không hợp lệ.';
    } else {
        try {
            $accounts = dbCallProcedure(
                $link,
                'CALL sp_auth_get_active_admin_by_email(?)',
                's',
                [strtolower($email)]
            );
            $admin = $accounts[0] ?? null;

            // Keep one generic message so attackers cannot enumerate admins.
            if (!$admin || !password_verify($password, $admin['password_hash'])) {
                $loi = 'Thông tin đăng nhập không hợp lệ.';
            } else {
                session_regenerate_id(true);
                $_SESSION['user_id'] = (int) $admin['userID'];
                $_SESSION['full_name'] = $admin['full_name'];
                $_SESSION['role'] = 'admin';
                $_SESSION['auth_scope'] = 'admin';

                header('Location: /pages/admin/D_Dashboard_admin.php');
                exit;
            }
        } catch (Throwable $error) {
            error_log('Admin login failed: ' . $error->getMessage());
            $loi = 'Không thể đăng nhập lúc này. Vui lòng thử lại.';
        }
    }
}
?>
<!doctype html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>LexiLoop Admin - Đăng nhập</title>
    <link rel="stylesheet" href="/CSS/D_DangNhapAdmin.css">
</head>
<body>
    <main class="admin-login" aria-labelledby="admin-login-title">
        <section class="admin-login__card">
            <p class="admin-login__brand">LEXILOOP ADMIN</p>
            <h1 id="admin-login-title">Đăng nhập quản trị</h1>
            <p class="admin-login__description">Khu vực dành riêng cho quản trị viên hệ thống.</p>

            <?php if ($loi !== ''): ?>
                <p class="admin-login__error" role="alert"><?php echo htmlspecialchars($loi, ENT_QUOTES, 'UTF-8'); ?></p>
            <?php endif; ?>

            <form method="post" novalidate>
                <label for="admin-email">Email quản trị</label>
                <input id="admin-email" name="email" type="email" autocomplete="username" required>

                <label for="admin-password">Mật khẩu</label>
                <input id="admin-password" name="password" type="password" autocomplete="current-password" required>

                <button type="submit">Đăng nhập quản trị</button>
            </form>

            <a class="admin-login__back" href="/pages/main/B_homepage.html">← Quay về trang chủ</a>
        </section>
    </main>
</body>
</html>
