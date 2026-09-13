<?php
require_once '../../includes/auth_guard.php';
require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");

// auth_guard.php đã xác thực session trước khi trang sử dụng user_id.
$user_id = (int) $_SESSION['user_id'];

$thong_bao = "";
$loai_thong_bao = "";

// Khởi tạo mảng thông tin hồ sơ (ưu tiên lấy từ Session của tài khoản đang đăng nhập)
$user_profile = [
    "C_Hosocanhan_full_name" => $_SESSION['full_name'] ?? $_SESSION['user_name'] ?? "",
    "C_Hosocanhan_email"     => $_SESSION['email'] ?? "",
    "C_Hosocanhan_ngay_sinh" => $_SESSION['user_profile_ngay_sinh'] ?? "2002-05-15",
    "C_Hosocanhan_trinh_do"  => $_SESSION['user_profile_trinh_do'] ?? "Trung cấp (B1)"
];

$thanh_tuu = [
    "tong_tu_hoc"     => 0,
    "chuoi_ngay"      => 0,
    "quiz_hoan_thanh" => 0,
    "diem_tb_quiz"    => "0%"
];

try {
    if (isset($link) && $link) {
        // Quét danh sách bảng thực tế tránh lỗi phân biệt hoa/thường trên Docker (Linux)
        $tables_res = @mysqli_query($link, "SHOW TABLES");
        $db_tables = [];
        if ($tables_res) {
            while ($tbl_row = mysqli_fetch_array($tables_res)) {
                $db_tables[strtolower($tbl_row[0])] = $tbl_row[0];
            }
        }

        $tbl_users    = isset($db_tables['users']) ? "`" . $db_tables['users'] . "`" : "`Users`";
        $tbl_sessions = isset($db_tables['learning_sessions']) ? "`" . $db_tables['learning_sessions'] . "`" : "`learning_sessions`";
        $tbl_progress = isset($db_tables['user_vocab_progress']) ? "`" . $db_tables['user_vocab_progress'] . "`" : "`user_vocab_progress`";
        $tbl_quiz     = isset($db_tables['quiz_results']) ? "`" . $db_tables['quiz_results'] . "`" : "`quiz_results`";

        // Xác định cột khóa chính của bảng users (userID hoặc id)
        $col_id = 'userID';
        $check_col = @mysqli_query($link, "SHOW COLUMNS FROM $tbl_users LIKE 'userID'");
        if (!$check_col || mysqli_num_rows($check_col) === 0) {
            $col_id = 'id';
        }

        // --- XỬ LÝ KHI NGƯỜI DÙNG BẤM LƯU THAY ĐỔI (POST) ---
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $full_name = trim($_POST["C_Hosocanhan_full_name"] ?? "");
            $email     = trim($_POST["C_Hosocanhan_email"] ?? "");
            $ngay_sinh = trim($_POST["C_Hosocanhan_ngay_sinh"] ?? "");
            $trinh_do  = trim($_POST["C_Hosocanhan_trinh_do"] ?? "");

            if (empty($full_name)) {
                $thong_bao = "Vui lòng nhập họ và tên!";
                $loai_thong_bao = "error";
            } elseif (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $thong_bao = "Địa chỉ email không hợp lệ!";
                $loai_thong_bao = "error";
            } else {
                // Kiểm tra xem email mới có bị trùng với tài khoản của người khác không
                $email_trung = false;
                $sql_check_email = "SELECT `$col_id` FROM $tbl_users WHERE email = ? AND `$col_id` != ? LIMIT 1";
                if ($stmt = @mysqli_prepare($link, $sql_check_email)) {
                    mysqli_stmt_bind_param($stmt, "si", $email, $user_id);
                    mysqli_stmt_execute($stmt);
                    mysqli_stmt_store_result($stmt);
                    if (mysqli_stmt_num_rows($stmt) > 0) {
                        $email_trung = true;
                    }
                    mysqli_stmt_close($stmt);
                }

                if ($email_trung) {
                    $thong_bao = "Email này đã được sử dụng bởi một tài khoản khác!";
                    $loai_thong_bao = "error";
                } else {
                    // Cập nhật thông tin mới vào CSDL
                    $sql_update = "UPDATE $tbl_users SET full_name = ?, email = ? WHERE `$col_id` = ?";
                    if ($stmt = @mysqli_prepare($link, $sql_update)) {
                        mysqli_stmt_bind_param($stmt, "ssi", $full_name, $email, $user_id);
                        if (mysqli_stmt_execute($stmt)) {
                            // Cập nhật đồng bộ sang SESSION để Dashboard đổi theo ngay lập tức
                            $_SESSION['full_name'] = $full_name;
                            $_SESSION['user_name'] = $full_name;
                            $_SESSION['email']     = $email;
                            $_SESSION['user_profile_ngay_sinh'] = $ngay_sinh;
                            $_SESSION['user_profile_trinh_do']  = $trinh_do;

                            $user_profile["C_Hosocanhan_full_name"] = $full_name;
                            $user_profile["C_Hosocanhan_email"]     = $email;
                            $user_profile["C_Hosocanhan_ngay_sinh"] = $ngay_sinh;
                            $user_profile["C_Hosocanhan_trinh_do"]  = $trinh_do;

                            $thong_bao = "Cập nhật thông tin hồ sơ thành công!";
                            $loai_thong_bao = "success";
                        } else {
                            $thong_bao = "Có lỗi xảy ra khi lưu thông tin vào cơ sở dữ liệu!";
                            $loai_thong_bao = "error";
                        }
                        mysqli_stmt_close($stmt);
                    }
                }
            }
        }

        // --- LẤY THÔNG TIN HỒ SƠ THẬT TỪ CSDL CỦA USER ĐANG ĐĂNG NHẬP ---
        if (isset($db_tables['users']) && ($_SERVER["REQUEST_METHOD"] !== "POST" || $loai_thong_bao === "error")) {
            $sql_user = "SELECT full_name, email FROM $tbl_users WHERE `$col_id` = ? LIMIT 1";
            if ($stmt = @mysqli_prepare($link, $sql_user)) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($res)) {
                    $user_profile["C_Hosocanhan_full_name"] = $row['full_name'] ?? $user_profile["C_Hosocanhan_full_name"];
                    $user_profile["C_Hosocanhan_email"]     = $row['email'] ?? $user_profile["C_Hosocanhan_email"];
                }
                mysqli_stmt_close($stmt);
            }
        }

        // --- TÍNH TOÁN CÁC CHỈ SỐ THÀNH TỰU THẬT CỦA TÀI KHOẢN ---
        // Tổng từ vựng đã học
        if (isset($db_tables['user_vocab_progress'])) {
            $sql_total = "SELECT COUNT(*) AS total FROM $tbl_progress WHERE user_id = ? AND status IN ('learning', 'mastered')";
            if ($stmt = @mysqli_prepare($link, $sql_total)) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($res)) {
                    $thanh_tuu["tong_tu_hoc"] = (int)($row['total'] ?? 0);
                }
                mysqli_stmt_close($stmt);
            }
        }

        // Chuỗi ngày học (Streak)
        if (isset($db_tables['learning_sessions'])) {
            $sql_streak = "SELECT streak_count FROM $tbl_sessions WHERE user_id = ? ORDER BY session_date DESC, id DESC LIMIT 1";
            if ($stmt = @mysqli_prepare($link, $sql_streak)) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($res)) {
                    $thanh_tuu["chuoi_ngay"] = (int)($row['streak_count'] ?? 0);
                }
                mysqli_stmt_close($stmt);
            }
        }

        // Tổng số bài Quiz đã hoàn thành & Điểm Quiz trung bình
        if (isset($db_tables['quiz_results'])) {
            $sql_quiz = "SELECT COUNT(*) AS total_quiz, AVG(score) AS avg_score FROM $tbl_quiz WHERE user_id = ?";
            if ($stmt = @mysqli_prepare($link, $sql_quiz)) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($res)) {
                    $thanh_tuu["quiz_hoan_thanh"] = (int)($row['total_quiz'] ?? 0);
                    $thanh_tuu["diem_tb_quiz"]    = $row['avg_score'] !== null ? round($row['avg_score']) . "%" : "0%";
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
} catch (\Throwable $e) {
    error_log("Lỗi Hồ sơ cá nhân: " . $e->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hồ sơ cá nhân - LexiLoop</title>
    <link rel="stylesheet" href="../../CSS/Style.css">
    <link rel="stylesheet" href="../../CSS/C_Hosocanhan.css">
    <link rel="stylesheet" href="../../CSS/topheader.css">
</head>

<body class="C_Hosocanhan_body">

    <!-- =========================================
         SIDEBAR
         ========================================= -->
    <!-- Sidebar dùng chung cho mọi trang người dùng -->
    <?php include '../../includes/sidebar_user.php'; ?>

    <!-- =========================================
         KHU VỰC NỘI DUNG CHÍNH
         ========================================= -->
    <div class="page-content">
        
        <!-- HEADER -->
        <?php
        $headerTitle = 'Hồ sơ cá nhân';
        $topHeaderPageActions = '';

        include '../../includes/topheader.php';
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
                        <span id="C_Hosocanhan_avatarInitials">NA</span>
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