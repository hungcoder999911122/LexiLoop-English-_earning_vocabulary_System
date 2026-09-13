<?php
require_once '../../includes/auth_guard.php';
require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");

/*
 * auth_guard.php đã chuyển guest về đăng nhập.
 * Vì vậy biến này luôn là ID của tài khoản thật.
 */

$user_id = (int) $_SESSION['user_id'];


// Khởi tạo các biến mặc định
$tu_can_on_tap  = 0;
$tu_da_hoc      = 0;
$quiz_da_lam    = 0;
$tong_tu_on_tap = 0;

try {
    if (isset($link) && $link) {
        // Quét danh sách bảng thực tế tránh lỗi phân biệt hoa/thường trên Linux
        $tables_res = @mysqli_query($link, "SHOW TABLES");
        $db_tables = [];
        if ($tables_res) {
            while ($tbl_row = mysqli_fetch_array($tables_res)) {
                $db_tables[strtolower($tbl_row[0])] = $tbl_row[0];
            }
        }

        $tbl_progress = isset($db_tables['user_vocab_progress']) ? "`" . $db_tables['user_vocab_progress'] . "`" : "`user_vocab_progress`";
        $tbl_sessions = isset($db_tables['learning_sessions']) ? "`" . $db_tables['learning_sessions'] . "`" : "`learning_sessions`";
        $tbl_quiz     = isset($db_tables['quiz_results']) ? "`" . $db_tables['quiz_results'] . "`" : "`quiz_results`";

        // Lấy số từ còn cần ôn tập hôm nay (đến hạn next_review_date)
        if (isset($db_tables['user_vocab_progress'])) {
            $stmt = @mysqli_prepare($link, "SELECT COUNT(*) AS total FROM $tbl_progress WHERE user_id = ? AND next_review_date <= CURDATE()");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($res)) {
                    $tu_can_on_tap = (int)($row['total'] ?? 0);
                }
                mysqli_stmt_close($stmt);
            }
        }

        // Lấy số từ đã ôn tập / học trong ngày hôm nay
        if (isset($db_tables['learning_sessions'])) {
            $stmt = @mysqli_prepare($link, "SELECT SUM(words_studied) AS total FROM $tbl_sessions WHERE user_id = ? AND session_date = CURDATE()");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($res)) {
                    $tu_da_hoc = (int)($row['total'] ?? 0);
                }
                mysqli_stmt_close($stmt);
            }
        }

        // Nếu bảng learning_sessions hôm nay chưa có, kiểm tra tiến độ review trong ngày
        if ($tu_da_hoc === 0 && isset($db_tables['user_vocab_progress'])) {
            $stmt = @mysqli_prepare($link, "SELECT COUNT(*) AS total FROM $tbl_progress WHERE user_id = ? AND DATE(last_reviewed_at) = CURDATE()");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($res)) {
                    $tu_da_hoc = (int)($row['total'] ?? 0);
                }
                mysqli_stmt_close($stmt);
            }
        }

        // Lấy số bài Quiz đã hoàn thành trong ngày hôm nay
        if (isset($db_tables['quiz_results'])) {
            $stmt = @mysqli_prepare($link, "SELECT COUNT(*) AS total FROM $tbl_quiz WHERE user_id = ? AND DATE(COALESCE(finished_at, started_at)) = CURDATE()");
            if ($stmt) {
                mysqli_stmt_bind_param($stmt, "i", $user_id);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($res)) {
                    $quiz_da_lam = (int)($row['total'] ?? 0);
                }
                mysqli_stmt_close($stmt);
            }
        }
    }
} catch (\Throwable $e) {
    error_log("Lỗi Ôn tập hôm nay: " . $e->getMessage());
}

$tong_tu_on_tap = $tu_da_hoc + $tu_can_on_tap;

// Tính % tiến độ bước 1 (FlashCard)
if ($tong_tu_on_tap > 0) {
    $phan_tram_b1 = min(100, round(($tu_da_hoc / $tong_tu_on_tap) * 100));
} else {
    // Nếu hôm nay không có từ nào cần ôn, coi như đã đạt 100%
    $phan_tram_b1 = 100;
}

// Mở khóa bước 2 (Quiz) khi đã hoàn thành 100% từ vựng hoặc không có từ tồn đọng
$is_bước2_unlocked = ($tong_tu_on_tap === 0 || $tu_da_hoc >= $tong_tu_on_tap);
$is_buoc2_unlocked = $is_bước2_unlocked;
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ôn tập hôm nay - LexiLoop</title>
    <link rel="stylesheet" href="../../CSS/C_Ontaphomnay.css">
</head>
<body class="C_Ontaphomnay_body">

    <!-- Header -->
    <header class="C_Ontaphomnay_header">
        <h1 class="C_Ontaphomnay_logo">Ôn tập hôm nay</h1>
        <button type="button" id="C_Ontaphomnay_btnThoat" class="C_Ontaphomnay_btnBack" title="Quay lại">
            &times; Thoát
        </button>
    </header>

    <!-- Khối nội dung chính -->
    <main class="C_Ontaphomnay_main">
        
        <div class="C_Ontaphomnay_infoBox">
            <p class="C_Ontaphomnay_subtitle">
                Bạn có <strong><?php echo $tong_tu_on_tap; ?> từ</strong> cần ôn tập hôm nay, được chia thành 2 bước:
            </p>
        </div>

        <!-- BƯỚC 1: HỌC FLASHCARD -->
        <section class="C_Ontaphomnay_stepCard">
            <div class="C_Ontaphomnay_badge C_Ontaphomnay_badgeActive">1</div>

            <div class="C_Ontaphomnay_stepContent">
                <h2 class="C_Ontaphomnay_stepTitle">Học FlashCard</h2>
                <p class="C_Ontaphomnay_stepDesc">
                    Ôn lại <?php echo $tong_tu_on_tap; ?> từ bằng thẻ ghi nhớ, đánh dấu đã nhớ / chưa nhớ.
                </p>

                <div class="C_Ontaphomnay_progressRow">
                    <div class="C_Ontaphomnay_progressBar">
                        <div class="C_Ontaphomnay_progressFill" style="width: <?php echo $phan_tram_b1; ?>%;"></div>
                    </div>
                    <span class="C_Ontaphomnay_progressText"><?php echo $tu_da_hoc; ?>/<?php echo $tong_tu_on_tap; ?></span>
                </div>

                <button type="button" id="C_Ontaphomnay_btnTiepTucHoc" class="C_Ontaphomnay_btnAction">
                    Tiếp tục học
                </button>
            </div>
        </section>

        <!-- BƯỚC 2: LÀM QUIZ ÔN TẬP -->
        <section class="C_Ontaphomnay_stepCard <?php echo !$is_bước2_unlocked ? 'is-locked' : ''; ?>">
            <div class="C_Ontaphomnay_badge <?php echo $is_bước2_unlocked ? 'C_Ontaphomnay_badgeActive' : ''; ?>">2</div>

            <div class="C_Ontaphomnay_stepContent">
                <h2 class="C_Ontaphomnay_stepTitle">Làm Quiz ôn tập</h2>
                <p class="C_Ontaphomnay_stepDesc">
                    Kiểm tra phản xạ và ghi nhớ sâu các từ vựng sau khi hoàn thành FlashCard.
                </p>

                <div class="C_Ontaphomnay_progressRow">
                    <div class="C_Ontaphomnay_progressBar">
                        <div class="C_Ontaphomnay_progressFill" style="width: 0%;"></div>
                    </div>
                    <span class="C_Ontaphomnay_progressText">0/<?php echo $tong_tu_on_tap; ?></span>
                </div>

                <?php if (!$is_bước2_unlocked): ?>
                    <p class="C_Ontaphomnay_lockNote">
                        🔒 Hoàn thành bước 1 để mở khóa
                    </p>
                <?php else: ?>
                    <button type="button" id="C_Ontaphomnay_btnVaoQuiz" class="C_Ontaphomnay_btnAction">
                        Bắt đầu làm Quiz
                    </button>
                <?php endif; ?>
            </div>
        </section>

        <!-- Ghi chú thuật toán Spaced Repetition -->
        <footer class="C_Ontaphomnay_bannerContainer">
            <div class="C_Ontaphomnay_bannerBox">
                💡 <strong>Ghi chú:</strong> Sau khi hoàn thành, hệ thống sẽ tự động tính toán thời gian ngắt quãng (Spaced Repetition) để lên lịch ôn lại tối ưu cho bạn.
            </div>
        </footer>

    </main>

    <script src="../../JS/C_Ontaphomnay.js"></script>
</body>
</html>