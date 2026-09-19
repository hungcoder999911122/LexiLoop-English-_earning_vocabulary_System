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
        if ($full_name === '') {
            throw new InvalidArgumentException('Vui lòng nhập họ và tên!');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException('Địa chỉ email không hợp lệ!');
        }
        dbCallProcedure($link, 'CALL sp_update_user_profile(?, ?, ?)', 'iss', [$user_id, $full_name, $email]);
        $_SESSION['full_name'] = $_SESSION['user_name'] = $full_name;
        $_SESSION['email'] = $email;
        $_SESSION['user_profile_ngay_sinh'] = $ngay_sinh;
        $_SESSION['user_profile_trinh_do'] = $trinh_do;
        $thong_bao = 'Cập nhật thông tin hồ sơ thành công!';
        $loai_thong_bao = 'success';
    }

    $users = dbSelectView($link, 'SELECT full_name, email FROM vw_users WHERE userID = ? LIMIT 1', 'i', [$user_id]);
    if ($users) {
        $user_profile['C_Hosocanhan_full_name'] = $users[0]['full_name'] ?? '';
        $user_profile['C_Hosocanhan_email'] = $users[0]['email'] ?? '';
    }
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
    $thong_bao = (int) $error->getCode() === 1062 ? 'Email này đã được sử dụng!' : 'Không thể xử lý hồ sơ lúc này.';
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
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ cá nhân - LexiLoop</title>
    <link rel="stylesheet" href="../../CSS/Style.css">
    <link rel="stylesheet" href="../../CSS/topheader.css">
    <link rel="stylesheet" href="../../CSS/C_Hosocanhan.css">
    <link rel="stylesheet" href="../../CSS/responsive.css">
</head>

<body class="C_Hosocanhan_body">

    <!-- =========================================
         SIDEBAR
         ========================================= -->
    <!-- Sidebar dùng chung cho mọi trang người dùng -->
    <?php include $_SERVER['DOCUMENT_ROOT'] . '/includes/sidebar_user.php'; ?>

    <!-- =========================================
         KHU VỰC NỘI DUNG CHÍNH
         ========================================= -->
    <div class="page-content">
        
        <!-- HEADER -->
        <?php
        $headerTitle = 'Hồ sơ cá nhân';
        $topHeaderPageActions = '';

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
                        <span id="C_Hosocanhan_avatarInitials"><?php echo htmlspecialchars($profileInitials); ?></span>
                        <img id="C_Hosocanhan_avatarPreview" src="" alt="Avatar" style="display:none;">
                    </div>
                    <input type="file" id="C_Hosocanhan_fileInput" name="C_Hosocanhan_avatar_file" accept="image/*" style="display:none;">
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
                            <label for="C_Hosocanhan_trinh_do" class="C_Hosocanhan_label">Trình độ</label>
                            <input
                                type="text"
                                id="C_Hosocanhan_trinh_do"
                                name="C_Hosocanhan_trinh_do"
                                class="C_Hosocanhan_input"
                                placeholder="VD: B1, B2..."
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
    <script src="../../JS/jquery-4.0.0.min.js"></script>
    <script src="../../JS/C_Hosocanhan.js"></script>
    <script src="../../JS/auth.js"></script>
</body>

</html>
