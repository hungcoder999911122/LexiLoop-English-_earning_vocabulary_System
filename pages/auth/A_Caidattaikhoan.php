<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/auth_guard.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/database_objects.php");

$user_id = (int) $_SESSION['user_id'];
$loi = "";
$thongBao = "";

// Lấy thông tin cài đặt hiện tại của người dùng
$accounts = dbCallProcedure($link, 'CALL sp_auth_get_account_by_id(?)', 'i', [$user_id]);
$userRow = $accounts[0] ?? null;
if (!$userRow) {
    exit('Không tìm thấy tài khoản.');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_pass      = $_POST['A_Caidattaikhoan_password'] ?? '';
    $new_pass          = $_POST['A_Caidattaikhoan_password_new'] ?? '';
    $new_pass_confirm  = $_POST['A_Caidattaikhoan_password_new_acp'] ?? '';
    $reminderEnabled   = isset($_POST['A_Caidattaikhoan_reminder']) ? 1 : 0;
    $hour              = $_POST['hour'] ?? '2000';
    $quantity          = (int) ($_POST['quantity'] ?? 20);

    $normalizedTime = preg_match('/^([01][0-9]|2[0-3])([0-5][0-9])$/', $hour)
        ? substr($hour, 0, 2) . ':' . substr($hour, 2, 2) . ':00'
        : '20:00:00';
    $dailyTarget = ($quantity >= 1 && $quantity <= 200) ? $quantity : 20;

    $new_hash = null;
    $hasPasswordError = false;

    // Nếu người dùng có nhập đổi mật khẩu
    if (!empty($new_pass) || !empty($new_pass_confirm) || !empty($current_pass)) {
        if (empty($current_pass)) {
            $loi = "Vui lòng nhập mật khẩu hiện tại để đổi mật khẩu.";
            $hasPasswordError = true;
        } elseif (!password_verify($current_pass, $userRow['password_hash'])) {
            $loi = "Mật khẩu hiện tại không chính xác.";
            $hasPasswordError = true;
        } elseif (empty($new_pass) || empty($new_pass_confirm)) {
            $loi = "Vui lòng nhập đầy đủ mật khẩu mới và xác nhận mật khẩu mới.";
            $hasPasswordError = true;
        } elseif ($new_pass !== $new_pass_confirm) {
            $loi = "Mật khẩu mới và xác nhận mật khẩu không khớp.";
            $hasPasswordError = true;
        } elseif (strlen($new_pass) < 6) {
            $loi = "Mật khẩu mới phải có ít nhất 6 ký tự.";
            $hasPasswordError = true;
        } else {
            $new_hash = password_hash($new_pass, PASSWORD_DEFAULT);
        }
    }

    if (!$hasPasswordError) {
        try {
            dbCallProcedure(
                $link,
                'CALL sp_auth_update_account_settings(?, ?, ?, ?, ?)',
                'isisi',
                [$user_id, $new_hash, $reminderEnabled, $normalizedTime, $dailyTarget]
            );
            $thongBao = !empty($new_hash) ? "Đổi mật khẩu và cập nhật cài đặt thành công!" : "Cập nhật cài đặt thành công!";

            // Tải lại thông tin mới
            $accounts = dbCallProcedure($link, 'CALL sp_auth_get_account_by_id(?)', 'i', [$user_id]);
            $userRow = $accounts[0] ?? $userRow;

            // Xóa giá trị mật khẩu trên form sau khi cập nhật thành công
            $current_pass = $new_pass = $new_pass_confirm = '';
        } catch (Throwable $error) {
            error_log('Lỗi cập nhật cài đặt: ' . $error->getMessage());
            $loi = "Không thể cập nhật cài đặt lúc này: " . $error->getMessage();
        }
    }
}

$curReminder = (int) ($userRow['daily_reminder_enabled'] ?? 0);
$curTime = $userRow['reminder_time'] ?? '20:00:00';
$curHourVal = str_replace(':', '', substr($curTime, 0, 5));
$curTarget = (int) ($userRow['daily_target_words'] ?? 20);
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cài đặt tài khoản - LexiLoop</title>
    <link rel="stylesheet" href="/CSS/Style.css">
    <link rel="stylesheet" href="/CSS/topheader.css">
    <link rel="stylesheet" href="/CSS/A_Caidattaikhoan.css">
    <link rel="stylesheet" href="/CSS/responsive.css">
</head>
<body class="A_Caidattaikhoan_body">

    <!-- Sidebar dùng chung cho mọi trang người dùng -->
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/sidebar_user.php'; ?>

    <div class="page-content">
        <!-- TOPHEADER -->
        <?php
        $headerTitle = 'Cài đặt tài khoản';
        $topHeaderPageActions = '<a href="/pages/user/C_Hosocanhan.php" class="top-header-btn-action" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:10px;background:var(--color-surface);border:1px solid var(--color-border);font-size:13px;font-weight:600;color:var(--color-text);text-decoration:none;">👤 Xem hồ sơ</a>';
        include $_SERVER['DOCUMENT_ROOT'] . '/includes/topheader.php';
        ?>

        <main class="A_Caidattaikhoan_main">

            <?php if (!empty($thongBao)): ?>
                <div class="A_Caidattaikhoan_alert A_Caidattaikhoan_alert_success" id="A_Caidattaikhoan_alert">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <span><?php echo htmlspecialchars($thongBao); ?></span>
                </div>
            <?php endif; ?>

            <?php if (!empty($loi)): ?>
                <div class="A_Caidattaikhoan_alert A_Caidattaikhoan_alert_error" id="A_Caidattaikhoan_alert">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    <span><?php echo htmlspecialchars($loi); ?></span>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="A_Caidattaikhoan_form">
                <div class="A_Caidattaikhoan_grid">

                    <!-- CỘT TRÁI: ĐỔI MẬT KHẨU -->
                    <section class="A_Caidattaikhoan_card">
                        <div class="A_Caidattaikhoan_cardHeader">
                            <div class="A_Caidattaikhoan_cardIcon">
                                <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <rect x="3" y="11" width="18" height="11" rx="2" ry="2"></rect>
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4"></path>
                                </svg>
                            </div>
                            <div>
                                <h2 class="A_Caidattaikhoan_cardTitle">Đổi mật khẩu</h2>
                                <p class="A_Caidattaikhoan_cardDesc">Cập nhật mật khẩu để tăng tính bảo mật tài khoản</p>
                            </div>
                        </div>

                        <div class="A_Caidattaikhoan_cardBody">
                            <div class="A_Caidattaikhoan_formGroup">
                                <label for="A_Caidattaikhoan_password" class="A_Caidattaikhoan_label">Mật khẩu hiện tại</label>
                                <input
                                    id="A_Caidattaikhoan_password"
                                    name="A_Caidattaikhoan_password"
                                    type="password"
                                    class="A_Caidattaikhoan_input"
                                    placeholder="Để trống nếu không muốn đổi"
                                    autocomplete="current-password">
                            </div>

                            <div class="A_Caidattaikhoan_formGroup">
                                <label for="A_Caidattaikhoan_password_new" class="A_Caidattaikhoan_label">Mật khẩu mới</label>
                                <input
                                    id="A_Caidattaikhoan_password_new"
                                    name="A_Caidattaikhoan_password_new"
                                    type="password"
                                    class="A_Caidattaikhoan_input"
                                    placeholder="Ít nhất 6 ký tự"
                                    autocomplete="new-password">
                            </div>

                            <div class="A_Caidattaikhoan_formGroup">
                                <label for="A_Caidattaikhoan_password_new_acp" class="A_Caidattaikhoan_label">Xác nhận mật khẩu mới</label>
                                <input
                                    id="A_Caidattaikhoan_password_new_acp"
                                    name="A_Caidattaikhoan_password_new_acp"
                                    type="password"
                                    class="A_Caidattaikhoan_input"
                                    placeholder="Nhập lại mật khẩu mới"
                                    autocomplete="new-password">
                            </div>
                        </div>
                    </section>

                    <!-- CỘT PHẢI: NHẮC NHỞ & TÙY CHỌN HỌC TẬP -->
                    <div class="A_Caidattaikhoan_colRight">

                        <!-- Nhắc nhở ôn tập -->
                        <section class="A_Caidattaikhoan_card">
                            <div class="A_Caidattaikhoan_cardHeader">
                                <div class="A_Caidattaikhoan_cardIcon A_Caidattaikhoan_iconWarning">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"></path>
                                        <path d="M13.73 21a2 2 0 0 1-3.46 0"></path>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="A_Caidattaikhoan_cardTitle">Nhắc nhở ôn tập</h2>
                                    <p class="A_Caidattaikhoan_cardDesc">Gửi thông báo định kỳ giúp duy trì chuỗi học tập</p>
                                </div>
                            </div>

                            <div class="A_Caidattaikhoan_cardBody">
                                <div class="A_Caidattaikhoan_toggleRow">
                                    <div class="A_Caidattaikhoan_toggleInfo">
                                        <span class="A_Caidattaikhoan_toggleTitle">Bật nhắc nhở hằng ngày</span>
                                        <span class="A_Caidattaikhoan_toggleSub">Gửi email nhắc nhở ôn từ vựng đến hòm thư</span>
                                    </div>
                                    <label class="A_Caidattaikhoan_switch" aria-label="Bật nhắc nhở ôn tập">
                                        <input
                                            type="checkbox"
                                            id="A_Caidattaikhoan_reminder"
                                            name="A_Caidattaikhoan_reminder"
                                            <?php echo $curReminder === 1 ? 'checked' : ''; ?>>
                                        <span class="A_Caidattaikhoan_slider"></span>
                                    </label>
                                </div>

                                <div class="A_Caidattaikhoan_formGroup" id="A_Caidattaikhoan_timeGroup">
                                    <label for="hour" class="A_Caidattaikhoan_label">Giờ nhận nhắc nhở</label>
                                    <select
                                        name="hour"
                                        id="hour"
                                        class="A_Caidattaikhoan_select"
                                        data-selected="<?php echo htmlspecialchars($curHourVal); ?>">
                                        <?php
                                        for ($h = 0; $h < 24; $h++) {
                                            for ($m = 0; $m < 60; $m += 15) {
                                                $val = sprintf('%02d%02d', $h, $m);
                                                $text = sprintf('%02d:%02d', $h, $m);
                                                $selected = ($val === $curHourVal) ? 'selected' : '';
                                                echo "<option value=\"{$val}\" {$selected}>{$text}</option>\n";
                                            }
                                        }
                                        ?>
                                    </select>
                                </div>
                            </div>
                        </section>

                        <!-- Tùy chọn học tập -->
                        <section class="A_Caidattaikhoan_card">
                            <div class="A_Caidattaikhoan_cardHeader">
                                <div class="A_Caidattaikhoan_cardIcon A_Caidattaikhoan_iconAccent">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <path d="m4.93 4.93 4.24 4.24"></path>
                                        <path d="m14.83 9.17 4.24-4.24"></path>
                                        <path d="m14.83 14.83 4.24 4.24"></path>
                                        <path d="m9.17 14.83-4.24 4.24"></path>
                                        <circle cx="12" cy="12" r="4"></circle>
                                    </svg>
                                </div>
                                <div>
                                    <h2 class="A_Caidattaikhoan_cardTitle">Mục tiêu ôn tập</h2>
                                    <p class="A_Caidattaikhoan_cardDesc">Số lượng từ vựng bạn muốn đặt mục tiêu ôn mỗi ngày</p>
                                </div>
                            </div>

                            <div class="A_Caidattaikhoan_cardBody">
                                <div class="A_Caidattaikhoan_formGroup">
                                    <label for="quantity" class="A_Caidattaikhoan_label">Số từ ôn tập mỗi ngày</label>
                                    <select name="quantity" id="quantity" class="A_Caidattaikhoan_select">
                                        <option value="5" <?php echo $curTarget === 5 ? 'selected' : ''; ?>>5 từ / ngày (Khởi động)</option>
                                        <option value="10" <?php echo $curTarget === 10 ? 'selected' : ''; ?>>10 từ / ngày (Tiêu chuẩn)</option>
                                        <option value="20" <?php echo $curTarget === 20 ? 'selected' : ''; ?>>20 từ / ngày (Khuyến nghị)</option>
                                        <option value="30" <?php echo $curTarget === 30 ? 'selected' : ''; ?>>30 từ / ngày (Chăm chỉ)</option>
                                        <option value="50" <?php echo $curTarget === 50 ? 'selected' : ''; ?>>50 từ / ngày (Tăng tốc)</option>
                                    </select>
                                </div>
                            </div>
                        </section>

                    </div>
                </div>

                <!-- HÀNH ĐỘNG -->
                <div class="A_Caidattaikhoan_actions">
                    <button type="submit" name="saved" value="1" class="A_Caidattaikhoan_btnSubmit">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path>
                            <polyline points="17 21 17 13 7 13 7 21"></polyline>
                            <polyline points="7 3 7 8 15 8"></polyline>
                        </svg>
                        <span>Lưu thay đổi</span>
                    </button>
                </div>
            </form>

        </main>
    </div>

    <script src="/JS/jquery-4.0.0.min.js"></script>
    <script src="/JS/auth.js"></script>
    <script>
        $(document).ready(function() {
            var $reminder = $('#A_Caidattaikhoan_reminder');
            var $timeGroup = $('#A_Caidattaikhoan_timeGroup');
            var $hour = $('#hour');
            var $form = $('.A_Caidattaikhoan_form');

            function syncReminderState() {
                if ($reminder.is(':checked')) {
                    $timeGroup.removeClass('is-disabled');
                } else {
                    $timeGroup.addClass('is-disabled');
                }
            }

            $reminder.on('change', syncReminderState);
            syncReminderState();

            var selectedHour = $hour.attr('data-selected');
            if (selectedHour) {
                $hour.val(selectedHour);
            }

            // Client-side validation kiểm tra mật khẩu trước khi submit
            $form.on('submit', function(e) {
                var currentPass = $('#A_Caidattaikhoan_password').val().trim();
                var newPass = $('#A_Caidattaikhoan_password_new').val().trim();
                var confirmPass = $('#A_Caidattaikhoan_password_new_acp').val().trim();

                if (currentPass !== '' || newPass !== '' || confirmPass !== '') {
                    if (currentPass === '') {
                        e.preventDefault();
                        alert('Vui lòng nhập mật khẩu hiện tại để đổi mật khẩu!');
                        $('#A_Caidattaikhoan_password').focus();
                        return false;
                    }
                    if (newPass === '') {
                        e.preventDefault();
                        alert('Vui lòng nhập mật khẩu mới!');
                        $('#A_Caidattaikhoan_password_new').focus();
                        return false;
                    }
                    if (newPass.length < 6) {
                        e.preventDefault();
                        alert('Mật khẩu mới phải có ít nhất 6 ký tự!');
                        $('#A_Caidattaikhoan_password_new').focus();
                        return false;
                    }
                    if (newPass !== confirmPass) {
                        e.preventDefault();
                        alert('Mật khẩu mới và xác nhận mật khẩu không khớp!');
                        $('#A_Caidattaikhoan_password_new_acp').focus();
                        return false;
                    }
                }
            });

            // Tự động ẩn thông báo sau 4 giây
            var $alert = $('#A_Caidattaikhoan_alert');
            if ($alert.length) {
                setTimeout(function() {
                    $alert.fadeOut(500, function() {
                        $(this).remove();
                    });
                }, 4000);
            }
        });
    </script>
</body>
</html>
