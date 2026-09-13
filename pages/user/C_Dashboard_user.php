<?php

// =====================================================
// 1. KẾT NỐI DATABASE
// =====================================================
// =====================================================
// 2. KHỞI ĐỘNG SESSION
// =====================================================
// =====================================================
// 3. KIỂM TRA ĐĂNG NHẬP
// =====================================================

require_once '../../includes/auth_guard.php';
require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");

// auth_guard.php đã chắc chắn user_id tồn tại.
// Ép kiểu int giúp dữ liệu truyền vào truy vấn nhất quán.
$userId = (int) $_SESSION['user_id'];

// =====================================================
// LẤY THÔNG TIN USER
// =====================================================
$sqlUser = "
    SELECT
        userID,
        full_name,
        avatar_url,
        daily_target_words
    FROM Users
    WHERE userID = ?
";

$stmtUser = mysqli_prepare($link, $sqlUser);

mysqli_stmt_bind_param($stmtUser, "i", $userId);

mysqli_stmt_execute($stmtUser);

$resultUser = mysqli_stmt_get_result($stmtUser);

$user = mysqli_fetch_assoc($resultUser);

if (!$user) {
    die("Không tìm thấy thông tin người dùng.");
}


// =====================================================
// 6. THÔNG TIN HIỂN THỊ
// =====================================================
$fullName = $user['full_name'];

$dailyTarget = (int)$user['daily_target_words'];

// =====================================================
// 7. LẤY CHUỖI HỌC GẦN NHẤT
// =====================================================

$sqlStreak = "
    SELECT streak_count
    FROM learning_sessions
    WHERE user_id = ?
    ORDER BY session_date DESC, id DESC
    LIMIT 1
";

$stmtStreak = mysqli_prepare($link, $sqlStreak);

mysqli_stmt_bind_param($stmtStreak, "i", $userId);

mysqli_stmt_execute($stmtStreak);

$resultStreak = mysqli_stmt_get_result($stmtStreak);

$rowStreak = mysqli_fetch_assoc($resultStreak);

$streak = $rowStreak ? (int)$rowStreak['streak_count'] : 0;

// =====================================================
// 8. SỐ TỪ ĐANG HỌC
// =====================================================

$sqlLearning = "
    SELECT COUNT(*) AS total
    FROM user_vocab_progress
    WHERE user_id = ?
      AND status = 'learning'
";

$stmtLearning = mysqli_prepare($link, $sqlLearning);

mysqli_stmt_bind_param($stmtLearning, "i", $userId);

mysqli_stmt_execute($stmtLearning);

$resultLearning = mysqli_stmt_get_result($stmtLearning);

$rowLearning = mysqli_fetch_assoc($resultLearning);

$learningWords = (int)$rowLearning['total'];

// =====================================================
// 9. SỐ TỪ ĐÃ THUỘC
// =====================================================

$sqlMastered = "
    SELECT COUNT(*) AS total
    FROM user_vocab_progress
    WHERE user_id = ?
      AND status = 'mastered'
";

$stmtMastered = mysqli_prepare($link, $sqlMastered);

mysqli_stmt_bind_param($stmtMastered, "i", $userId);

mysqli_stmt_execute($stmtMastered);

$resultMastered = mysqli_stmt_get_result($stmtMastered);

$rowMastered = mysqli_fetch_assoc($resultMastered);

$masteredWords = (int)$rowMastered['total'];

// =====================================================
// 10. TIẾN ĐỘ GHI NHỚ TỔNG THỂ
// =====================================================
$trackedWords = $learningWords + $masteredWords;
$learningProgressPercent = $trackedWords > 0
    ? min(100, (int) round(($masteredWords / $trackedWords) * 100))
    : 0;

// =====================================================
// 11. SỐ TỪ CẦN ÔN HÔM NAY
// =====================================================

$sqlReviewToday = "
    SELECT COUNT(*) AS total
    FROM user_vocab_progress
    WHERE user_id = ?
      AND next_review_date <= CURDATE()
";

$stmtReviewToday = mysqli_prepare($link, $sqlReviewToday);

mysqli_stmt_bind_param($stmtReviewToday, "i", $userId);

mysqli_stmt_execute($stmtReviewToday);

$resultReviewToday = mysqli_stmt_get_result($stmtReviewToday);

$rowReviewToday = mysqli_fetch_assoc($resultReviewToday);

$reviewToday = (int)$rowReviewToday['total'];

// =====================================================
// 12. SỐ TỪ ĐÃ HỌC HÔM NAY
// =====================================================

$sqlTodayWords = "
    SELECT COALESCE(SUM(words_count), 0) AS total
    FROM (
        SELECT words_studied AS words_count
        FROM learning_sessions
        WHERE user_id = ? AND session_date = CURDATE()

        UNION ALL

        SELECT total_questions AS words_count
        FROM quiz_results
        WHERE user_id = ? AND DATE(COALESCE(finished_at, started_at)) = CURDATE()
    ) today_activity
";

$stmtTodayWords = mysqli_prepare($link, $sqlTodayWords);

mysqli_stmt_bind_param($stmtTodayWords, "ii", $userId, $userId);

mysqli_stmt_execute($stmtTodayWords);

$resultTodayWords = mysqli_stmt_get_result($stmtTodayWords);

$rowTodayWords = mysqli_fetch_assoc($resultTodayWords);

$todayWords = (int)$rowTodayWords['total'];

// =====================================================
// 13. TÍNH TIẾN ĐỘ MỤC TIÊU
// =====================================================

if ($dailyTarget > 0) {
    $targetPercent = ($todayWords / $dailyTarget) * 100;
    $targetPercent = min($targetPercent, 100);
} else {
    $targetPercent = 0;
}

$remainingWords = max($dailyTarget - $todayWords, 0);

// =====================================================
// 14. HOẠT ĐỘNG FLASHCARD VÀ QUIZ GẦN ĐÂY
// =====================================================

$sqlRecentActivity = "
    SELECT *
    FROM (
        SELECT
            qr.id,
            'quiz' AS activity_type,
            COALESCE(t.topicName, vs.name, 'Ôn tập tổng hợp') AS source_name,
            qr.correct_answers,
            qr.total_questions,
            NULL AS words_studied,
            COALESCE(qr.finished_at, qr.started_at) AS activity_time,
            GREATEST(0, TIMESTAMPDIFF(SECOND, qr.started_at, qr.finished_at)) AS duration_seconds,
            1 AS has_exact_time
        FROM quiz_results qr
        LEFT JOIN Topics t ON qr.topic_id = t.topicID
        LEFT JOIN vocabulary_sets vs ON qr.vocabulary_set_id = vs.id
        WHERE qr.user_id = ?

        UNION ALL

        SELECT
            ls.id,
            'flashcard' AS activity_type,
            COALESCE(t.topicName, vs.name, 'Ôn tập tổng hợp') AS source_name,
            NULL AS correct_answers,
            NULL AS total_questions,
            ls.words_studied,
            COALESCE(ls.finished_at, ls.started_at, CAST(CONCAT(ls.session_date, ' 00:00:00') AS DATETIME)) AS activity_time,
            COALESCE(ls.duration_seconds, 0) AS duration_seconds,
            IF(ls.finished_at IS NULL AND ls.started_at IS NULL, 0, 1) AS has_exact_time
        FROM learning_sessions ls
        LEFT JOIN Topics t ON ls.topic_id = t.topicID
        LEFT JOIN vocabulary_sets vs ON ls.vocabulary_set_id = vs.id
        WHERE ls.user_id = ? AND ls.words_studied > 0
    ) recent_activity
    ORDER BY activity_time DESC, id DESC
    LIMIT 5
";

$stmtRecentActivity = mysqli_prepare($link, $sqlRecentActivity);
mysqli_stmt_bind_param($stmtRecentActivity, "ii", $userId, $userId);
mysqli_stmt_execute($stmtRecentActivity);
$resultRecentActivity = mysqli_stmt_get_result($stmtRecentActivity);
$recentActivities = [];

while ($row = mysqli_fetch_assoc($resultRecentActivity)) {
    $row['display_time'] = $row['activity_time']
        ? date('H:i, d/m/Y', strtotime($row['activity_time']))
        : 'Chưa ghi nhận thời gian';
    if (!(int) $row['has_exact_time'] && $row['activity_time']) {
        $row['display_time'] = 'Ngày ' . date('d/m/Y', strtotime($row['activity_time'])) . ' · chưa lưu giờ';
    }
    $durationSeconds = max(0, (int) $row['duration_seconds']);
    $row['duration_text'] = $durationSeconds >= 60
        ? intdiv($durationSeconds, 60) . ' phút'
        : $durationSeconds . ' giây';
    if ($row['activity_type'] === 'quiz') {
        $row['score_percent'] = (int) $row['total_questions'] > 0
            ? (int) round(((int) $row['correct_answers'] / (int) $row['total_questions']) * 100)
            : 0;
    }
    $recentActivities[] = $row;
}
mysqli_stmt_close($stmtRecentActivity);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - LexiLoop</title>
    <link rel="stylesheet" href="../../CSS/Style.css">
    <link rel="stylesheet" href="../../CSS/C_Dashboard_user.css">

    <link rel="stylesheet" href="../../CSS/topheader.css">
    <link rel="stylesheet" href="../../CSS/responsive.css">
</head>

<body class="C_Dashboard_user_body">

    <!-- Header dùng chung; tên người dùng lấy từ session, không query lại database. -->
    <?php
    $headerTitle = 'Dashboard';
    include '../../includes/topheader.php';
    ?>

    <!-- SIDEBAR -->
    <?php include '../../includes/sidebar_user.php'; ?>

    <main class="C_Dashboard_user_mainContent">
        <!-- Tổng quan đầu trang: chào mừng, chỉ số và chuỗi học liên tục. -->
        <section class="C_Dashboard_user_overview" aria-labelledby="dashboard-welcome-title">
            <div class="C_Dashboard_user_welcomeCard">
                <span class="C_Dashboard_user_eyebrow">TỔNG QUAN HÔM NAY</span>
                <h2 id="dashboard-welcome-title">Chào mừng trở lại, <?= htmlspecialchars($fullName) ?>!</h2>
                <p>
                    <?php if ($reviewToday > 0): ?>
                        Có <strong><?= $reviewToday ?> từ</strong> đang chờ bạn ôn tập hôm nay.
                    <?php else: ?>
                        Hôm nay chưa có từ đến hạn. Bạn có thể tự chọn và học thêm một chủ đề mới.
                    <?php endif; ?>
                </p>
                <a href="C_Ontaphomnay.php" class="C_Dashboard_user_primaryButton">
                    Ôn tập ngay <span aria-hidden="true">→</span>
                </a>
                <span class="C_Dashboard_user_decorWord" aria-hidden="true">Aa</span>
            </div>

            <div class="C_Dashboard_user_statsCard" aria-label="Thống kê học tập">
                <div class="C_Dashboard_user_statItem C_Dashboard_user_statItem--blue">
                    <span class="C_Dashboard_user_statIcon" aria-hidden="true">↗</span>
                    <strong><?= $learningWords ?></strong>
                    <span>Đang học</span>
                </div>
                <div class="C_Dashboard_user_statItem C_Dashboard_user_statItem--green">
                    <span class="C_Dashboard_user_statIcon" aria-hidden="true">✓</span>
                    <strong><?= $masteredWords ?></strong>
                    <span>Đã thuộc</span>
                </div>
                <div class="C_Dashboard_user_statItem C_Dashboard_user_statItem--violet">
                    <span class="C_Dashboard_user_statIcon" aria-hidden="true">◔</span>
                    <strong><?= $learningProgressPercent ?>%</strong>
                    <span>Tiến độ %</span>

                    <!-- Bổ sung về sau -->
                    <!-- <small><?= $masteredWords ?>/<?= $trackedWords ?> từ đã thuộc</small>
                    <div class="C_Dashboard_user_statProgress" aria-hidden="true"><i style="width: <?= $learningProgressPercent ?>%"></i></div> -->
                </div>
                <div class="C_Dashboard_user_statItem C_Dashboard_user_statItem--orange">
                    <span class="C_Dashboard_user_statIcon" aria-hidden="true">◎</span>
                    <strong><?= $todayWords ?></strong>
                    <span>Lượt từ luyện hôm nay</span>
                </div>
            </div>

            <div class="C_Dashboard_user_streakCard">
                <div class="C_Dashboard_user_streakLabel"><span aria-hidden="true">🔥</span> Chuỗi ngày học</div>
                <div class="C_Dashboard_user_streakValue"><strong><?= $streak ?></strong><span>ngày</span></div>
                <p><?= $streak > 0 ? 'Duy trì nhịp học mỗi ngày nhé!' : 'Bắt đầu chuỗi học đầu tiên hôm nay.' ?></p>
                <div class="C_Dashboard_user_streakTip"><span aria-hidden="true">✦</span> Mỗi ngày một bước tiến</div>
            </div>
        </section>

        <section class="C_Dashboard_user_quickSection" aria-labelledby="quick-access-title">
            <div class="C_Dashboard_user_sectionHeader">
                <div>
                    <span class="C_Dashboard_user_eyebrow">BẮT ĐẦU HỌC</span>
                    <h3 id="quick-access-title">Truy cập nhanh</h3>
                </div>
            </div>
            <div class="C_Dashboard_user_quickGrid">
                <a href="C_Botuvung.php" class="C_Dashboard_user_quickCard C_Dashboard_user_quickCard--blue">
                    <span class="C_Dashboard_user_quickIcon" aria-hidden="true">＋</span>
                    <span><strong>Bộ từ vựng</strong><small>Tạo và quản lý bộ từ cá nhân</small></span>
                    <b aria-hidden="true">→</b>
                </a>
                <a href="../main/B_DanhSachChuDe.php" class="C_Dashboard_user_quickCard C_Dashboard_user_quickCard--violet">
                    <span class="C_Dashboard_user_quickIcon" aria-hidden="true">⚡</span>
                    <span><strong>Học theo chủ đề</strong><small>Khám phá kho từ vựng có sẵn</small></span>
                    <b aria-hidden="true">→</b>
                </a>
                <a href="C_Tuvungcuatoi.php" class="C_Dashboard_user_quickCard C_Dashboard_user_quickCard--green">
                    <span class="C_Dashboard_user_quickIcon" aria-hidden="true">▤</span>
                    <span><strong>Từ vựng của tôi</strong><small>Xem lại danh sách đã lưu</small></span>
                    <b aria-hidden="true">→</b>
                </a>
                <a href="C_Lichsuontap.php" class="C_Dashboard_user_quickCard C_Dashboard_user_quickCard--orange">
                    <span class="C_Dashboard_user_quickIcon" aria-hidden="true">◷</span>
                    <span><strong>Lịch sử ôn tập</strong><small>Theo dõi quá trình học chi tiết</small></span>
                    <b aria-hidden="true">→</b>
                </a>
            </div>
        </section>

        <div class="C_Dashboard_user_contentGrid">
            <section class="C_Dashboard_user_panel" aria-labelledby="today-target-title">
                <div class="C_Dashboard_user_sectionHeader">
                    <div>
                        <span class="C_Dashboard_user_eyebrow">NHỊP HỌC CÁ NHÂN</span>
                        <h3 id="today-target-title">Mục tiêu hôm nay</h3>
                    </div>
                    <strong class="C_Dashboard_user_targetPercent"><?= round($targetPercent) ?>%</strong>
                </div>
                <div class="C_Dashboard_user_targetNumbers">
                    <strong><?= $todayWords ?></strong><span>/ <?= $dailyTarget ?> từ</span>
                </div>
                <div class="C_Dashboard_user_targetProgress" role="progressbar" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= round($targetPercent) ?>">
                    <div class="C_Dashboard_user_targetProgressBar" style="width: <?= $targetPercent ?>%;"></div>
                </div>
                <p class="C_Dashboard_user_targetNote">
                    <?= $remainingWords > 0
                        ? 'Còn ' . $remainingWords . ' từ nữa để hoàn thành mục tiêu.'
                        : 'Bạn đã hoàn thành mục tiêu hôm nay.' ?>
                </p>
                <a href="../main/B_DanhSachChuDe.php" class="C_Dashboard_user_textLink">Tiếp tục học từ mới →</a>
            </section>

            <section class="C_Dashboard_user_panel" aria-labelledby="recent-activity-title">
                <div class="C_Dashboard_user_sectionHeader">
                    <div>
                        <span class="C_Dashboard_user_eyebrow">DÒNG THỜI GIAN HỌC TẬP</span>
                        <h3 id="recent-activity-title">Hoạt động gần đây</h3>
                    </div>
                    <a href="C_Lichsuontap.php" class="C_Dashboard_user_textLink">Xem lịch sử</a>
                </div>
                <div class="C_Dashboard_user_activityList">
                    <?php if (empty($recentActivities)): ?>
                        <div class="C_Dashboard_user_emptyState">
                            <span aria-hidden="true">✦</span>
                            <p>Chưa có hoạt động Flashcard hoặc Quiz gần đây.</p>
                        </div>
                    <?php else: ?>
                        <?php foreach ($recentActivities as $activity): ?>
                            <div class="C_Dashboard_user_activityRow">
                                <span class="C_Dashboard_user_activityIcon C_Dashboard_user_activityIcon--<?= $activity['activity_type'] ?>" aria-hidden="true">
                                    <?= $activity['activity_type'] === 'quiz' ? '✓' : '▤' ?>
                                </span>
                                <span class="C_Dashboard_user_activityInfo">
                                    <strong><?= $activity['activity_type'] === 'quiz' ? 'Quiz' : 'Flashcard' ?> - <?= htmlspecialchars($activity['source_name']) ?></strong>
                                    <small><?= htmlspecialchars($activity['display_time']) ?> · <?= htmlspecialchars($activity['duration_text']) ?></small>
                                </span>
                                <strong class="C_Dashboard_user_activityScore">
                                    <?php if ($activity['activity_type'] === 'quiz'): ?>
                                        Đúng <?= (int) $activity['correct_answers'] ?>/<?= (int) $activity['total_questions'] ?>
                                    <?php else: ?>
                                        Đã học <?= (int) $activity['words_studied'] ?> từ
                                    <?php endif; ?>
                                </strong>
                            </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </section>
        </div>
    </main>
    <script src="../../JS/jquery-4.0.0.min.js"></script>
    <script src="../../JS/auth.js"></script>
</body>

</html>
