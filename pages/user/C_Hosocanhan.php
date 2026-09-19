<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/auth_guard.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/Connect.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/database_objects.php');

$user_id = (int) $_SESSION['user_id'];
$thong_bao = '';
$loai_thong_bao = '';
$user_profile = [
    'C_Hosocanhan_full_name' => $_SESSION['full_name'] ?? '',
    'C_Hosocanhan_email' => $_SESSION['email'] ?? '',
    'C_Hosocanhan_avatar_url' => $_SESSION['avatar_url'] ?? '',
    'C_Hosocanhan_ngay_sinh' => $_SESSION['user_profile_ngay_sinh'] ?? '2002-05-15',
    'C_Hosocanhan_trinh_do' => $_SESSION['user_profile_trinh_do'] ?? 'Trung cấp (B1)',
];
$thanh_tuu = ['tong_tu_hoc' => 0, 'chuoi_ngay' => 0, 'quiz_hoan_thanh' => 0, 'diem_tb_quiz' => '0%'];

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $full_name = trim($_POST['C_Hosocanhan_full_name'] ?? '');
        $email = trim($_POST['C_Hosocanhan_email'] ?? '');
        $ngay_sinh = trim($_POST['C_Hosocanhan_ngay_sinh'] ?? '');
        $trinh_do = trim($_POST['C_Hosocanhan_trinh_do'] ?? '');

        if (mb_strlen($full_name, 'UTF-8') < 2) {
            throw new InvalidArgumentException('Vui lòng nhập họ và tên hợp lệ (ít nhất 2 ký tự)!');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Địa chỉ email không hợp lệ!');
        }

        // Xử lý upload ảnh đại diện nếu người dùng chọn file
        $avatar_url = null;
        if (isset($_FILES['C_Hosocanhan_avatar_file']) && $_FILES['C_Hosocanhan_avatar_file']['error'] === UPLOAD_ERR_OK) {
            $file = $_FILES['C_Hosocanhan_avatar_file'];
            $maxFileSize = 5 * 1024 * 1024; // 5MB
            if ($file['size'] > $maxFileSize) {
                throw new InvalidArgumentException('Ảnh đại diện không được vượt quá 5MB.');
            }

            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $allowedExts = ['jpg', 'jpeg', 'png', 'gif', 'webp'];
            if (!in_array($ext, $allowedExts, true)) {
                throw new InvalidArgumentException('Chỉ chấp nhận các định dạng ảnh: JPG, JPEG, PNG, GIF, WEBP.');
            }

            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $mimeType = finfo_file($finfo, $file['tmp_name']);
            finfo_close($finfo);
            $allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
            if (!in_array($mimeType, $allowedMimes, true)) {
                throw new InvalidArgumentException('Tệp tải lên không phải là hình ảnh hợp lệ.');
            }

            $uploadDir = $_SERVER['DOCUMENT_ROOT'] . '/assets/images/avatars/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0777, true);
            }

            $newFileName = 'avatar_user_' . $user_id . '_' . time() . '.' . $ext;
            $targetPath = $uploadDir . $newFileName;
            $saved = is_uploaded_file($file['tmp_name'])
                ? move_uploaded_file($file['tmp_name'], $targetPath)
                : copy($file['tmp_name'], $targetPath);

            if ($saved) {
                $avatar_url = '/assets/images/avatars/' . $newFileName;
                $_SESSION['avatar_url'] = $avatar_url;
                $user_profile['C_Hosocanhan_avatar_url'] = $avatar_url;
            } else {
                throw new RuntimeException('Không thể lưu ảnh đại diện. Vui lòng thử lại.');
            }
        }

        // Cập nhật thông tin vào cơ sở dữ liệu
        dbCallProcedure(
            $link,
            'CALL sp_update_user_profile(?, ?, ?, ?, ?, ?)',
            'isssss',
            [
                $user_id,
                $full_name,
                $email,
                $avatar_url,
                $ngay_sinh !== '' ? $ngay_sinh : null,
                $trinh_do !== '' ? $trinh_do : null
            ]
        );

        $_SESSION['full_name'] = $_SESSION['user_name'] = $full_name;
        $_SESSION['email'] = $email;
        if ($ngay_sinh !== '') {
            $_SESSION['user_profile_ngay_sinh'] = $ngay_sinh;
        }
        if ($trinh_do !== '') {
            $_SESSION['user_profile_trinh_do'] = $trinh_do;
        }

        $thong_bao = 'Cập nhật thông tin hồ sơ thành công!';
        $loai_thong_bao = 'success';
    }

    // Tải thông tin mới nhất từ cơ sở dữ liệu
    $users = dbSelectView(
        $link,
        'SELECT full_name, email, avatar_url, date_of_birth, target_level FROM vw_users WHERE userID = ? LIMIT 1',
        'i',
        [$user_id]
    );
    if ($users) {
        $row = $users[0];
        $user_profile['C_Hosocanhan_full_name'] = $row['full_name'] ?? '';
        $user_profile['C_Hosocanhan_email'] = $row['email'] ?? '';
        if (!empty($row['avatar_url'])) {
            $user_profile['C_Hosocanhan_avatar_url'] = $row['avatar_url'];
            $_SESSION['avatar_url'] = $row['avatar_url'];
        }
        if (!empty($row['date_of_birth'])) {
            $user_profile['C_Hosocanhan_ngay_sinh'] = $row['date_of_birth'];
            $_SESSION['user_profile_ngay_sinh'] = $row['date_of_birth'];
        }
        if (!empty($row['target_level'])) {
            $user_profile['C_Hosocanhan_trinh_do'] = $row['target_level'];
            $_SESSION['user_profile_trinh_do'] = $row['target_level'];
        }
    }

    // Tính toán số liệu thống kê thành tựu học tập
    $progress = dbSelectView($link, "SELECT COUNT(*) AS total FROM vw_user_progress WHERE user_id = ? AND status IN ('learning','mastered')", 'i', [$user_id]);
    $thanh_tuu['tong_tu_hoc'] = (int) ($progress[0]['total'] ?? 0);

    $streak = dbSelectView($link, 'SELECT fn_get_current_streak(?) AS value', 'i', [$user_id]);
    $thanh_tuu['chuoi_ngay'] = (int) ($streak[0]['value'] ?? 0);

    $quiz = dbSelectView($link, 'SELECT COUNT(*) AS total_quiz, AVG(score) AS avg_score FROM vw_quiz_results WHERE user_id = ?', 'i', [$user_id]);
    $thanh_tuu['quiz_hoan_thanh'] = (int) ($quiz[0]['total_quiz'] ?? 0);
    $thanh_tuu['diem_tb_quiz'] = isset($quiz[0]['avg_score']) ? round((float) $quiz[0]['avg_score']) . '%' : '0%';
} catch (InvalidArgumentException $error) {
    $thong_bao = $error->getMessage();
    $loai_thong_bao = 'error';
} catch (Throwable $error) {
    error_log('Lỗi Hồ sơ cá nhân: ' . $error->getMessage());
    if (strpos($error->getMessage(), 'email already exists') !== false || (int) $error->getCode() === 1062) {
        $thong_bao = 'Email này đã được sử dụng bởi tài khoản khác!';
    } else {
        $thong_bao = 'Không thể xử lý hồ sơ: ' . $error->getMessage();
    }
    $loai_thong_bao = 'error';
}

$profileInitials = '';
foreach (explode(' ', trim($user_profile['C_Hosocanhan_full_name'] ?: 'User')) as $namePart) {
    if ($namePart !== '') {
        $profileInitials .= mb_substr($namePart, 0, 1, 'UTF-8');
    }
}
$profileInitials = mb_substr($profileInitials, 0, 2, 'UTF-8') ?: 'U';
$profileInitials = mb_strtoupper($profileInitials, 'UTF-8');
$hasAvatar = !empty($user_profile['C_Hosocanhan_avatar_url']);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ cá nhân - LexiLoop</title>
    <link rel="stylesheet" href="/CSS/Style.css">
    <link rel="stylesheet" href="/CSS/topheader.css">
    <link rel="stylesheet" href="/CSS/C_Hosocanhan.css">
    <link rel="stylesheet" href="/CSS/responsive.css">
</head>

<body class="C_Hosocanhan_body">

    <!-- =========================================
         SIDEBAR
         ========================================= -->
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/sidebar_user.php'; ?>

    <!-- =========================================
         KHU VỰC NỘI DUNG CHÍNH
         ========================================= -->
    <div class="page-content">
        
        <!-- HEADER -->
        <?php
        $headerTitle = 'Hồ sơ cá nhân';
        $topHeaderPageActions = '<a href="/pages/auth/A_Caidattaikhoan.php" class="top-header-btn-action" style="display:inline-flex;align-items:center;gap:6px;padding:8px 14px;border-radius:10px;background:var(--color-surface);border:1px solid var(--color-border);font-size:13px;font-weight:600;color:var(--color-text);text-decoration:none;">⚙️ Cài đặt tài khoản</a>';

        include $_SERVER['DOCUMENT_ROOT'] . '/includes/topheader.php';
        ?>

        <main class="C_Hosocanhan_main">

            <?php if (!empty($thong_bao)): ?>
                <div class="C_Hosocanhan_alert C_Hosocanhan_alert_<?php echo $loai_thong_bao; ?>" id="C_Hosocanhan_alert">
                    <?php echo htmlspecialchars($thong_bao); ?>
                </div>
            <?php endif; ?>

            <!-- Thông tin cá nhân -->
            <section class="C_Hosocanhan_profileSection">

                <div class="C_Hosocanhan_avatarWrapper">
                    <div class="C_Hosocanhan_avatarCircle" id="C_Hosocanhan_avatarCircle">
                        <span id="C_Hosocanhan_avatarInitials" style="<?php echo $hasAvatar ? 'display:none;' : ''; ?>">
                            <?php echo htmlspecialchars($profileInitials); ?>
                        </span>
                        <img id="C_Hosocanhan_avatarPreview" 
                             src="<?php echo $hasAvatar ? htmlspecialchars($user_profile['C_Hosocanhan_avatar_url']) : ''; ?>" 
                             alt="Avatar" 
                             style="<?php echo $hasAvatar ? 'display:block;' : 'display:none;'; ?>">
                    </div>
                    <!-- Liên kết input file với form qua thuộc tính form="C_Hosocanhan_formThongTin" -->
                    <input type="file" id="C_Hosocanhan_fileInput" name="C_Hosocanhan_avatar_file" accept="image/*" form="C_Hosocanhan_formThongTin" style="display:none;">
                    <button type="button" id="C_Hosocanhan_btnDoiAnh" class="C_Hosocanhan_btnAvatar">
                        Đổi ảnh đại diện
                    </button>
                </div>

                <form id="C_Hosocanhan_formThongTin" class="C_Hosocanhan_form" action="C_Hosocanhan.php" method="POST" enctype="multipart/form-data">
                    <div class="C_Hosocanhan_formGroup">
                        <label for="C_Hosocanhan_full_name" class="C_Hosocanhan_label">Họ tên</label>
                        <input
                            type="text"
                            id="C_Hosocanhan_full_name"
                            name="C_Hosocanhan_full_name"
                            class="C_Hosocanhan_input"
                            maxlength="100"
                            value="<?php echo htmlspecialchars($user_profile['C_Hosocanhan_full_name']); ?>"
                            required>
                    </div>

                    <div class="C_Hosocanhan_formGroup">
                        <label for="C_Hosocanhan_email" class="C_Hosocanhan_label">Email</label>
                        <input
                            type="email"
                            id="C_Hosocanhan_email"
                            name="C_Hosocanhan_email"
                            class="C_Hosocanhan_input"
                            maxlength="50"
                            value="<?php echo htmlspecialchars($user_profile['C_Hosocanhan_email']); ?>"
                            required>
                    </div>

                    <div class="C_Hosocanhan_formRow">
                        <div class="C_Hosocanhan_formGroup">
                            <label for="C_Hosocanhan_ngay_sinh" class="C_Hosocanhan_label">Ngày sinh</label>
                            <input
                                type="date"
                                id="C_Hosocanhan_ngay_sinh"
                                name="C_Hosocanhan_ngay_sinh"
                                class="C_Hosocanhan_input"
                                value="<?php echo htmlspecialchars($user_profile['C_Hosocanhan_ngay_sinh']); ?>">
                        </div>

                        <div class="C_Hosocanhan_formGroup">
                            <label for="C_Hosocanhan_trinh_do" class="C_Hosocanhan_label">Trình độ mục tiêu</label>
                            <input
                                type="text"
                                id="C_Hosocanhan_trinh_do"
                                name="C_Hosocanhan_trinh_do"
                                class="C_Hosocanhan_input"
                                placeholder="VD: Sơ cấp (A2), Trung cấp (B1)..."
                                value="<?php echo htmlspecialchars($user_profile['C_Hosocanhan_trinh_do']); ?>">
                        </div>
                    </div>

                    <button type="submit" id="C_Hosocanhan_btnCapNhat" class="C_Hosocanhan_btnSubmit">
                        Cập nhật hồ sơ
                    </button>
                </form>

            </section>

            <div class="C_Hosocanhan_spacer"></div>

            <!-- Thành tựu học tập -->
            <section class="C_Hosocanhan_achievementSection">
                <h2 class="C_Hosocanhan_sectionTitle">Thành tựu học tập</h2>

                <div class="C_Hosocanhan_achievementGrid">
                    <div class="C_Hosocanhan_card">
                        <span class="C_Hosocanhan_cardLabel">Tổng từ đã học</span>
                        <span class="C_Hosocanhan_cardValue"><?php echo $thanh_tuu['tong_tu_hoc']; ?></span>
                    </div>

                    <div class="C_Hosocanhan_card">
                        <span class="C_Hosocanhan_cardLabel">Chuỗi ngày học</span>
                        <span class="C_Hosocanhan_cardValue"><?php echo $thanh_tuu['chuoi_ngay']; ?></span>
                    </div>

                    <div class="C_Hosocanhan_card">
                        <span class="C_Hosocanhan_cardLabel">Quiz đã hoàn thành</span>
                        <span class="C_Hosocanhan_cardValue"><?php echo $thanh_tuu['quiz_hoan_thanh']; ?></span>
                    </div>

                    <div class="C_Hosocanhan_card">
                        <span class="C_Hosocanhan_cardLabel">Điểm TB Quiz</span>
                        <span class="C_Hosocanhan_cardValue"><?php echo $thanh_tuu['diem_tb_quiz']; ?></span>
                    </div>
                </div>
            </section>

        </main>
    </div>
    <script src="/JS/jquery-4.0.0.min.js"></script>
    <script src="/JS/C_Hosocanhan.js"></script>
    <script src="/JS/auth.js"></script>
</body>

</html>
