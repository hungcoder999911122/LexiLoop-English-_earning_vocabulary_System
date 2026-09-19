<?php
declare(strict_types=1);

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database_objects.php';

/**
 * Lấy giá trị cài đặt hệ thống từ view vw_system_settings.
 * Sử dụng prepared statement an toàn thông qua helper dbSelectView.
 */
if (!function_exists('getSystemSetting')) {
    function getSystemSetting(mysqli $connection, string $key, string $default = ''): string
    {
        try {
            $rows = dbSelectView($connection, 'SELECT setting_value FROM vw_system_settings WHERE setting_key = ? LIMIT 1', 's', [$key]);
            if (!empty($rows) && isset($rows[0]['setting_value'])) {
                return (string) $rows[0]['setting_value'];
            }
        } catch (Throwable $e) {
            // Lỗi kết nối hoặc View chưa có, fallback về default
            error_log("Lỗi getSystemSetting($key): " . $e->getMessage());
        }
        return $default;
    }
}

// BỘ LỌC CHẾ ĐỘ BẢO TRÌ (MAINTENANCE MODE)
$link = getDatabaseConnection();
$isMaintenance = getSystemSetting($link, 'maintenance_mode') === '1';

if ($isMaintenance) {
    $currentUri = $_SERVER['REQUEST_URI'] ?? '';
    
    // Nếu người dùng đang ở trang bảo trì, không chuyển hướng để tránh loop
    if (strpos($currentUri, 'maintenance.php') !== false) {
        // Hợp lệ, đang xem thông báo bảo trì
    } else {
        // Cho phép các trang trong /pages/admin/ hoạt động bình thường
        // Cần đảm bảo file D_DangNhapAdmin.php cũng nằm trong khu vực này.
        if (strpos($currentUri, '/pages/admin/') === false) {
            // Nếu không phải trang admin, kiểm tra xem có phải là Admin đang đăng nhập không.
            $isAdmin = false;
            if (isset($_SESSION['user_id']) && isset($_SESSION['auth_scope']) && $_SESSION['auth_scope'] === 'admin') {
                // Kiểm tra lại role dưới database cho chắc
                $userId = (int) $_SESSION['user_id'];
                $userCheck = dbSelectView($link, 'SELECT userID FROM vw_users WHERE userID = ? AND role = ? LIMIT 1', 'is', [$userId, 'admin']);
                if (!empty($userCheck)) {
                    $isAdmin = true;
                }
            }

            // Nếu KHÔNG phải là admin thì đá ra trang bảo trì
            if (!$isAdmin) {
                // Hủy session của user bình thường (nếu muốn bắt đăng nhập lại sau bảo trì), hoặc chỉ đơn giản là chuyển hướng.
                header("Location: /pages/main/maintenance.php");
                exit();
            }
        }
    }
}

// KHAI BÁO CÁC BIẾN CẤU HÌNH TOÀN CỤC ĐỂ DÙNG CHUNG TRONG LAYOUT
$sysSiteName = getSystemSetting($link, 'site_name', 'LexiLoop');
$sysSiteLogo = getSystemSetting($link, 'site_logo', '');
?>
