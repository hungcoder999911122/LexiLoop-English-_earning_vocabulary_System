<?php
/*
|--------------------------------------------------------------------------
| TOPHEADER DÙNG CHUNG
|--------------------------------------------------------------------------
| Trang cha có thể truyền:
|
| $headerTitle
|     Tiêu đề hiển thị trên topheader.
|
| $topHeaderPageActions
|     HTML hành động riêng của trang.
|     Ví dụ: nút Thêm từ hoặc dropdown lọc lịch sử.
*/

$headerTitle = $headerTitle ?? '';
$topHeaderPageActions = $topHeaderPageActions ?? '';

$isLoggedIn = isset($_SESSION['user_id']);
$headerUserName = $_SESSION['full_name'] ?? 'Người dùng';

/* Tạo tối đa hai chữ cái đầu cho avatar. */
$headerInitials = '';

foreach (explode(' ', trim($headerUserName)) as $namePart) {
    if ($namePart !== '') {
        $headerInitials .= strtoupper(substr($namePart, 0, 1));
    }
}

$headerInitials = substr($headerInitials, 0, 2);
?>

<header class="top-header">

    <!-- Bên trái: tên trang và hành động riêng -->
    <div class="top-header-start">
        <div class="top-header-context">
            <?php if ($headerTitle !== ''): ?>
                <h1 class="top-header-title">
                    <?= htmlspecialchars($headerTitle) ?>
                </h1>
            <?php endif; ?>
        </div>

        <?php if ($topHeaderPageActions !== ''): ?>
            <div class="top-header-page-actions">
                <?= $topHeaderPageActions ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Bên phải: chức năng toàn hệ thống và tài khoản -->
    <div class="top-header-act">

        <!-- Tạm thời chỉ là UI; có thể ẩn sau nếu chưa phát triển. -->
        <button
            class="top-header-btn"
            type="button"
            aria-label="Thông báo đang phát triển"
            title="Đang phát triển"
            disabled>
            🔔
        </button>

        <button
            class="top-header-btn"
            type="button"
            aria-label="Chế độ sáng tối đang phát triển"
            title="Đang phát triển"
            disabled>
            🌙
        </button>

        <?php if ($isLoggedIn): ?>
            <details class="top-header-account">
                <summary class="top-header-account-summary">
                    <span class="top-header-avatar" aria-hidden="true">
                        <?= htmlspecialchars($headerInitials) ?>
                    </span>

                    <span class="top-header-user-name">
                        <?= htmlspecialchars($headerUserName) ?>
                    </span>
                </summary>

                <nav class="top-header-account-menu" aria-label="Tài khoản">
                    <a href="../user/C_Hosocanhan.php">Hồ sơ</a>
                    <a href="../auth/A_Caidattaikhoan.php">Cài đặt</a>

                    <a
                        href="../auth/A_DangXuat.php"
                        data-action="logout">
                        Đăng xuất
                    </a>
                </nav>
            </details>
        <?php else: ?>
            <a href="../auth/A_DangNhap.php" class="login-btn">
                Đăng nhập
            </a>
        <?php endif; ?>
    </div>
</header>