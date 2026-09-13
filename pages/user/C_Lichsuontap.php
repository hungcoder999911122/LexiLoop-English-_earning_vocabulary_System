<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");

/* =========================================================
   1. XÁC ĐỊNH TRẠNG THÁI ĐĂNG NHẬP
   ========================================================= */
$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? (int) $_SESSION['user_id'] : null;

/* =========================================================
   2. NHẬN VÀ KIỂM TRA FILTER TỪ URL
   Ví dụ:
   - C_Lichsuontap.php?range=7
   - C_Lichsuontap.php?range=30
   - C_Lichsuontap.php?range=all
   ========================================================= */
$selectedRange = $_GET['range'] ?? '7';
$historyPage = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$historyPerPage = 10;
$historyTotalItems = 0;
$historyTotalPages = 1;

$allowedRanges = ['7', '30', 'all'];

if (!in_array($selectedRange, $allowedRanges, true)) {
    $selectedRange = '7';
}

/* =========================================================
   3. HÀM ĐỊNH DẠNG THỜI GIAN HIỂN THỊ TRONG BẢNG
   ========================================================= */
if (!function_exists('dinhDangThoiGian')) {
    function dinhDangThoiGian($datetime_str)
    {
        if (!$datetime_str) {
            return '';
        }

        $time = strtotime($datetime_str);
        return date('H:i, d/m/Y', $time);
    }
}

if (!function_exists('dinhDangThoiLuong')) {
    function dinhDangThoiLuong(int $seconds): string
    {
        $seconds = max(0, $seconds);
        if ($seconds < 60) return $seconds . ' giây';
        $minutes = intdiv($seconds, 60);
        $remainingSeconds = $seconds % 60;
        return $remainingSeconds > 0
            ? $minutes . ' phút ' . $remainingSeconds . ' giây'
            : $minutes . ' phút';
    }
}

/* =========================================================
   4. HÀM TẠO KHUNG BIỂU ĐỒ THEO NGÀY
   Ngày chưa học vẫn có cột 0 để biểu đồ không bị thiếu ngày.
   ========================================================= */
if (!function_exists('taoDuLieuBieuDoTheoNgay')) {
    function taoDuLieuBieuDoTheoNgay(
        string $startDate,
        int $numberOfDays
    ): array {
        $chartData = [];

        for ($index = 0; $index < $numberOfDays; $index++) {
            $date = date(
                'Y-m-d',
                strtotime(
                    '+' . $index . ' days',
                    strtotime($startDate)
                )
            );

            $dayOfWeek = (int) date('w', strtotime($date));

            $chartData[] = [
                'key' => $date,

                /*
                 * 7 ngày: hiển thị Thứ.
                 * 30 ngày: hiển thị ngày/tháng.
                 */
                'label' => $numberOfDays === 7
                    ? ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'][$dayOfWeek]
                    : date('d/m', strtotime($date)),

                'so_tu' => 0,
                'chieu_cao' => '0%'
            ];
        }

        return $chartData;
    }
}

/* =========================================================
   5. KHỞI TẠO DỮ LIỆU MẶC ĐỊNH
   Guest dùng dữ liệu 0, user sẽ được thay bằng dữ liệu thật.
   ========================================================= */
$chartEndDate = date('Y-m-d');
$chartStartDate = null;
$chartTitle = '';
$du_lieu_bieu_do = [];
$danh_sach_lich_su = [];
$tong_tu_tuan = 0;

if ($selectedRange === '7' || $selectedRange === '30') {
    $numberOfDays = (int) $selectedRange;

    $chartStartDate = date(
        'Y-m-d',
        strtotime('-' . ($numberOfDays - 1) . ' days')
    );

    $chartTitle = 'Số từ ôn tập trong ' . $numberOfDays . ' ngày qua';

    /*
     * Guest cũng có dữ liệu khung đúng cấu trúc.
     * Vì vậy HTML không bị lỗi $item['label'].
     */
    $du_lieu_bieu_do = taoDuLieuBieuDoTheoNgay(
        $chartStartDate,
        $numberOfDays
    );
} else {
    $chartTitle = 'Số từ ôn tập theo tháng';

    /*
     * Trạng thái mặc định cho guest hoặc user chưa có lịch sử.
     */
    $du_lieu_bieu_do = [
        [
            'key' => 'empty',
            'label' => 'Chưa có dữ liệu',
            'so_tu' => 0,
            'chieu_cao' => '0%'
        ]
    ];
}

/* =========================================================
   6. CHỈ USER ĐÃ ĐĂNG NHẬP MỚI TRUY VẤN DỮ LIỆU CÁ NHÂN
   ========================================================= */
if ($isLoggedIn && isset($link) && $link) {
    try {
        $tables_res = mysqli_query($link, 'SHOW TABLES');
        $db_tables = [];

        if ($tables_res) {
            while ($tbl_row = mysqli_fetch_array($tables_res)) {
                $db_tables[strtolower($tbl_row[0])] = $tbl_row[0];
            }
        }

        $tbl_sessions = isset($db_tables['learning_sessions'])
            ? '`' . $db_tables['learning_sessions'] . '`'
            : '`learning_sessions`';

        $tbl_quiz = isset($db_tables['quiz_results'])
            ? '`' . $db_tables['quiz_results'] . '`'
            : '`quiz_results`';

        $tbl_topics = isset($db_tables['topics'])
            ? '`' . $db_tables['topics'] . '`'
            : '`Topics`';

        $tbl_sets = isset($db_tables['vocabulary_sets'])
            ? '`' . $db_tables['vocabulary_sets'] . '`'
            : '`vocabulary_sets`';

        /* =================================================
           6.1. LẤY DỮ LIỆU BIỂU ĐỒ 7 HOẶC 30 NGÀY
           ================================================= */
        if (
            ($selectedRange === '7' || $selectedRange === '30') &&
            isset($db_tables['learning_sessions']) &&
            isset($db_tables['quiz_results'])
        ) {
            /*
             * Chuyển mảng thành map để cập nhật đúng từng ngày.
             */
            $chartDataMap = [];

            foreach ($du_lieu_bieu_do as $item) {
                $chartDataMap[$item['key']] = $item;
            }

            $sqlChart = "
                SELECT
                    activity_date AS session_date,
                    SUM(words_count) AS total_words
                FROM (
                    SELECT session_date AS activity_date, words_studied AS words_count
                    FROM $tbl_sessions
                    WHERE user_id = ? AND words_studied > 0

                    UNION ALL

                    SELECT DATE(COALESCE(finished_at, started_at)) AS activity_date,
                           total_questions AS words_count
                    FROM $tbl_quiz
                    WHERE user_id = ?
                ) learning_activity
                WHERE activity_date BETWEEN ? AND ?
                GROUP BY activity_date
                ORDER BY activity_date ASC
            ";

            if ($stmt = mysqli_prepare($link, $sqlChart)) {
                mysqli_stmt_bind_param(
                    $stmt,
                    'iiss',
                    $user_id,
                    $user_id,
                    $chartStartDate,
                    $chartEndDate
                );

                mysqli_stmt_execute($stmt);

                $result = mysqli_stmt_get_result($stmt);

                while ($row = mysqli_fetch_assoc($result)) {
                    $date = $row['session_date'];

                    if (isset($chartDataMap[$date])) {
                        $chartDataMap[$date]['so_tu'] =
                            (int) $row['total_words'];
                    }
                }

                mysqli_stmt_close($stmt);
            }

            $du_lieu_bieu_do = array_values($chartDataMap);
        }

        /* =================================================
           6.2. LẤY DỮ LIỆU BIỂU ĐỒ TẤT CẢ THỜI GIAN
           Gộp theo tháng để không tạo quá nhiều cột.
           ================================================= */
        if (
            $selectedRange === 'all' &&
            isset($db_tables['learning_sessions']) &&
            isset($db_tables['quiz_results'])
        ) {
            $sqlChart = "
                SELECT
                    DATE_FORMAT(activity_date, '%Y-%m') AS period_key,
                    DATE_FORMAT(activity_date, '%m/%Y') AS period_label,
                    SUM(words_count) AS total_words
                FROM (
                    SELECT session_date AS activity_date, words_studied AS words_count
                    FROM $tbl_sessions
                    WHERE user_id = ? AND words_studied > 0

                    UNION ALL

                    SELECT DATE(COALESCE(finished_at, started_at)) AS activity_date,
                           total_questions AS words_count
                    FROM $tbl_quiz
                    WHERE user_id = ?
                ) learning_activity
                GROUP BY
                    DATE_FORMAT(activity_date, '%Y-%m'),
                    DATE_FORMAT(activity_date, '%m/%Y')
                ORDER BY period_key ASC
            ";

            if ($stmt = mysqli_prepare($link, $sqlChart)) {
                mysqli_stmt_bind_param($stmt, 'ii', $user_id, $user_id);

                mysqli_stmt_execute($stmt);

                $result = mysqli_stmt_get_result($stmt);
                $allTimeChartData = [];

                while ($row = mysqli_fetch_assoc($result)) {
                    $allTimeChartData[] = [
                        'key' => $row['period_key'],
                        'label' => $row['period_label'],
                        'so_tu' => (int) $row['total_words'],
                        'chieu_cao' => '0%'
                    ];
                }

                mysqli_stmt_close($stmt);

                if (!empty($allTimeChartData)) {
                    $du_lieu_bieu_do = $allTimeChartData;
                }
            }
        }

        /* =================================================
           6.3. LỌC BẢNG NHẬT KÝ CÙNG KHOẢNG VỚI BIỂU ĐỒ
           ================================================= */
        if (
            isset($db_tables['quiz_results']) &&
            isset($db_tables['learning_sessions'])
        ) {
            if ($selectedRange === 'all') {
                $sqlHistory = "
                    (
                        SELECT
                            q.id,
                            CONCAT(
                                'Quiz - ',
                                COALESCE(t.topicName, vs.name, 'Ôn tập tổng hợp')
                            ) AS hoat_dong,
                            'quiz' AS loai,
                            CONCAT(
                                q.correct_answers,
                                '/',
                                q.total_questions
                            ) AS ket_qua,
                            COALESCE(
                                q.finished_at,
                                q.started_at
                            ) AS thoi_gian_raw,
                            GREATEST(0, TIMESTAMPDIFF(SECOND, q.started_at, q.finished_at)) AS thoi_luong_giay,
                            1 AS co_gio_chinh_xac
                        FROM $tbl_quiz q
                        LEFT JOIN $tbl_topics t
                            ON q.topic_id = t.topicID
                        LEFT JOIN $tbl_sets vs
                            ON q.vocabulary_set_id = vs.id
                        WHERE q.user_id = ?
                    )

                    UNION ALL

                    (
                        SELECT
                            s.id,
                            CONCAT(
                                'Flashcard - ',
                                COALESCE(t.topicName, vs.name, 'Ôn tập tổng hợp')
                            ) AS hoat_dong,
                            'flashcard' AS loai,
                            CONCAT(s.words_studied, ' thẻ') AS ket_qua,
                            COALESCE(
                                s.finished_at,
                                s.started_at,
                                CAST(CONCAT(s.session_date, ' 12:00:00') AS DATETIME)
                            ) AS thoi_gian_raw,
                            COALESCE(s.duration_seconds, 0) AS thoi_luong_giay,
                            IF(s.finished_at IS NULL AND s.started_at IS NULL, 0, 1) AS co_gio_chinh_xac
                        FROM $tbl_sessions s
                        LEFT JOIN $tbl_topics t
                            ON s.topic_id = t.topicID
                        LEFT JOIN $tbl_sets vs
                            ON s.vocabulary_set_id = vs.id
                        WHERE s.user_id = ?
                          AND s.words_studied > 0
                    )

                    ORDER BY thoi_gian_raw DESC
                ";

                if ($stmt = mysqli_prepare($link, $sqlHistory)) {
                    mysqli_stmt_bind_param(
                        $stmt,
                        'ii',
                        $user_id,
                        $user_id
                    );

                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    while ($row = mysqli_fetch_assoc($result)) {
                        $danh_sach_lich_su[] = $row;
                    }

                    mysqli_stmt_close($stmt);
                }
            } else {
                $sqlHistory = "
                    (
                        SELECT
                            q.id,
                            CONCAT(
                                'Quiz - ',
                                COALESCE(t.topicName, vs.name, 'Ôn tập tổng hợp')
                            ) AS hoat_dong,
                            'quiz' AS loai,
                            CONCAT(
                                q.correct_answers,
                                '/',
                                q.total_questions
                            ) AS ket_qua,
                            COALESCE(
                                q.finished_at,
                                q.started_at
                            ) AS thoi_gian_raw,
                            GREATEST(0, TIMESTAMPDIFF(SECOND, q.started_at, q.finished_at)) AS thoi_luong_giay,
                            1 AS co_gio_chinh_xac
                        FROM $tbl_quiz q
                        LEFT JOIN $tbl_topics t
                            ON q.topic_id = t.topicID
                        LEFT JOIN $tbl_sets vs
                            ON q.vocabulary_set_id = vs.id
                        WHERE q.user_id = ?
                          AND DATE(
                              COALESCE(
                                  q.finished_at,
                                  q.started_at
                              )
                          ) BETWEEN ? AND ?
                    )

                    UNION ALL

                    (
                        SELECT
                            s.id,
                            CONCAT(
                                'Flashcard - ',
                                COALESCE(t.topicName, vs.name, 'Ôn tập tổng hợp')
                            ) AS hoat_dong,
                            'flashcard' AS loai,
                            CONCAT(s.words_studied, ' thẻ') AS ket_qua,
                            COALESCE(
                                s.finished_at,
                                s.started_at,
                                CAST(CONCAT(s.session_date, ' 12:00:00') AS DATETIME)
                            ) AS thoi_gian_raw,
                            COALESCE(s.duration_seconds, 0) AS thoi_luong_giay,
                            IF(s.finished_at IS NULL AND s.started_at IS NULL, 0, 1) AS co_gio_chinh_xac
                        FROM $tbl_sessions s
                        LEFT JOIN $tbl_topics t
                            ON s.topic_id = t.topicID
                        LEFT JOIN $tbl_sets vs
                            ON s.vocabulary_set_id = vs.id
                        WHERE s.user_id = ?
                          AND s.session_date BETWEEN ? AND ?
                          AND s.words_studied > 0
                    )

                    ORDER BY thoi_gian_raw DESC
                ";

                if ($stmt = mysqli_prepare($link, $sqlHistory)) {
                    mysqli_stmt_bind_param(
                        $stmt,
                        'ississ',
                        $user_id,
                        $chartStartDate,
                        $chartEndDate,
                        $user_id,
                        $chartStartDate,
                        $chartEndDate
                    );

                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);

                    while ($row = mysqli_fetch_assoc($result)) {
                        $danh_sach_lich_su[] = $row;
                    }

                    mysqli_stmt_close($stmt);
                }
            }

            /*
             * Thêm id hiển thị và định dạng thời gian sau khi query.
             */
            foreach ($danh_sach_lich_su as $index => &$row) {
                $row['id'] = $index + 1;
                $row['thoi_gian'] = dinhDangThoiGian(
                    $row['thoi_gian_raw']
                );
                if (!(int) ($row['co_gio_chinh_xac'] ?? 0)) {
                    $row['thoi_gian'] = 'Ngày ' . date('d/m/Y', strtotime($row['thoi_gian_raw']))
                        . ' (dữ liệu cũ chưa lưu giờ)';
                }
                $row['thoi_luong'] = dinhDangThoiLuong((int) ($row['thoi_luong_giay'] ?? 0));

                if ($row['loai'] === 'quiz') {
                    [$correct, $total] = array_pad(explode('/', $row['ket_qua'], 2), 2, 0);
                    $correct = (int) $correct;
                    $total = (int) $total;
                    $percent = $total > 0 ? (int) round(($correct / $total) * 100) : 0;
                    $row['ket_qua'] = "Đúng $correct/$total";
                    // $percent%
                } else {
                    $wordTotal = (int) $row['ket_qua'];
                    $row['ket_qua'] = "Đã học $wordTotal từ";
                }
            }

            unset($row);
        }
    } catch (Throwable $e) {
        error_log('Lỗi Lịch sử ôn tập: ' . $e->getMessage());
    }
}

// Phân trang sau khi hợp nhất Quiz và Flashcard để giữ đúng thứ tự thời gian.
$historyTotalItems = count($danh_sach_lich_su);
$historyTotalPages = max(1, (int) ceil($historyTotalItems / $historyPerPage));
$historyPage = min($historyPage, $historyTotalPages);
$historyOffset = ($historyPage - 1) * $historyPerPage;
$danh_sach_lich_su = array_slice($danh_sach_lich_su, $historyOffset, $historyPerPage);
$historyFirstVisiblePage = max(1, min($historyPage - 1, $historyTotalPages - 2));
$historyLastVisiblePage = min($historyTotalPages, $historyFirstVisiblePage + 2);

/* =========================================================
   7. TÍNH TỔNG VÀ CHIỀU CAO CỘT BIỂU ĐỒ
   ========================================================= */
$tong_tu_tuan = array_sum(
    array_column($du_lieu_bieu_do, 'so_tu')
);

$maxWords = max(
    array_column($du_lieu_bieu_do, 'so_tu') ?: [0]
);

foreach ($du_lieu_bieu_do as &$item) {
    if ($maxWords > 0 && $item['so_tu'] > 0) {
        $percent = round(
            ($item['so_tu'] / $maxWords) * 100
        );

        /*
         * Cột có dữ liệu thấp vẫn hiển thị tối thiểu 15%.
         */
        $item['chieu_cao'] = max(
            15,
            min(100, $percent)
        ) . '%';
    }
}

unset($item);
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Lịch sử ôn tập - LexiLoop</title>

    <link rel="stylesheet" href="../../CSS/Style.css">
    <link rel="stylesheet" href="../../CSS/C_Lichsuontap.css">
    <link rel="stylesheet" href="../../CSS/topheader.css">
</head>

<body class="C_Lichsuontap_body">

    <!-- SIDEBAR: thay đổi theo trạng thái đăng nhập -->
    <?php if ($isLoggedIn): ?>
        <?php include '../../includes/sidebar_user.php'; ?>
    <?php else: ?>
        <?php include '../../includes/sidebar_guest.php'; ?>
    <?php endif; ?>

    <div class="page-content">

        <!-- TOP HEADER -->
        <?php
        $headerTitle = 'Lịch sử ôn tập';
        $topHeaderPageActions = '';

        /*
         * Filter chỉ dành cho user đã đăng nhập,
         * vì guest không có lịch sử cá nhân để lọc.
         */
        if ($isLoggedIn) {
            ob_start();
        ?>
            <select
                id="C_Lichsuontap_filterSelect"
                class="top-header-page-action top-header-page-action--control C_Lichsuontap_filterSelect"
                aria-label="Lọc lịch sử ôn tập theo thời gian">

                <option
                    value="7"
                    <?= $selectedRange === '7' ? 'selected' : '' ?>>
                    7 ngày qua
                </option>

                <option
                    value="30"
                    <?= $selectedRange === '30' ? 'selected' : '' ?>>
                    30 ngày qua
                </option>

                <option
                    value="all"
                    <?= $selectedRange === 'all' ? 'selected' : '' ?>>
                    Tất cả thời gian
                </option>
            </select>
        <?php
            $topHeaderPageActions = ob_get_clean();
        }

        include '../../includes/topheader.php';
        ?>

        <!-- Anh yêu cầu giữ guest preview ngoài main -->
        <?php if (!$isLoggedIn): ?>
            <section class="guest-preview-card">
                <span
                    class="guest-preview-card__icon"
                    aria-hidden="true">
                    📊
                </span>

                <div class="guest-preview-card__content">
                    <h2>Lịch sử ôn tập cá nhân</h2>

                    <p>
                        Bạn đang chưa đăng nhập. Đăng nhập để theo dõi
                        số từ đã học, lịch sử Flashcard và kết quả Quiz
                        của riêng bạn.
                    </p>

                    <ul class="guest-preview-card__benefits">
                        <li>Xem tiến độ học theo ngày</li>
                        <li>Theo dõi lịch sử Flashcard và Quiz</li>
                        <li>Đánh giá thói quen học tập cá nhân</li>
                    </ul>

                    <a
                        href="../auth/A_DangNhap.php"
                        class="guest-preview-card__login">
                        Đăng nhập
                    </a>
                </div>
            </section>
        <?php endif; ?>

        <main class="C_Lichsuontap_main">

            <!-- BIỂU ĐỒ -->
            <section class="C_Lichsuontap_chartCard">
                <div class="C_Lichsuontap_chartHeader">
                    <h2 class="C_Lichsuontap_chartTitle">
                        <?= htmlspecialchars($chartTitle) ?>
                    </h2>

                    <span class="C_Lichsuontap_totalBadge">
                        Tổng:
                        <strong><?= $tong_tu_tuan ?> từ</strong>
                    </span>
                </div>

                <div class="C_Lichsuontap_chartArea">
                    <div class="C_Lichsuontap_barsContainer <?= count($du_lieu_bieu_do) > 7
                                                                ? 'C_Lichsuontap_barsContainer--scrollable'
                                                                : '' ?>">
                            
                        <?php foreach ($du_lieu_bieu_do as $item): ?>
                            <div class="C_Lichsuontap_barGroup">
                                <div class="C_Lichsuontap_barWrapper">
                                    <span class="C_Lichsuontap_barValue"><?= (int) $item['so_tu'] ?></span>
                                    <div
                                        class="C_Lichsuontap_bar"
                                        style="height: <?= $item['chieu_cao'] ?>;"
                                        data-count="<?= $item['so_tu'] ?> từ">
                                    </div>
                                </div>

                                <span class="C_Lichsuontap_barLabel">
                                    <?= htmlspecialchars($item['label']) ?>
                                </span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>

            <!-- BẢNG NHẬT KÝ -->
            <section class="C_Lichsuontap_tableCard">
                <div class="C_Lichsuontap_tableHeader">
                    <h2 class="C_Lichsuontap_sectionTitle">
                        Nhật ký hoạt động
                    </h2>
                </div>

                <div class="C_Lichsuontap_tableResponsive">
                    <table
                        class="C_Lichsuontap_table"
                        id="C_Lichsuontap_table">

                        <thead>
                            <tr>
                                <th class="C_Lichsuontap_th">
                                    Hoạt động
                                </th>
                                <th class="C_Lichsuontap_th">
                                    Kết quả
                                </th>
                                <th class="C_Lichsuontap_th">
                                    Thời gian
                                </th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php if (empty($danh_sach_lich_su)): ?>
                                <tr>
                                    <td
                                        colspan="3"
                                        style="text-align: center; color: #888; padding: 25px 0;">
                                        Chưa có hoạt động ôn tập hoặc làm bài Quiz nào.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($danh_sach_lich_su as $row): ?>
                                    <tr>
                                        <td class="C_Lichsuontap_td">
                                            <span
                                                class="activity-badge activity-<?= htmlspecialchars($row['loai']) ?>">
                                                <?= $row['loai'] === 'quiz'
                                                    ? 'Quiz'
                                                    : 'Flashcard' ?>
                                            </span>

                                            <strong>
                                                <?= htmlspecialchars($row['hoat_dong']) ?>
                                            </strong>
                                        </td>

                                        <td class="C_Lichsuontap_td">
                                            <span class="result-tag">
                                                <?= htmlspecialchars($row['ket_qua']) ?>
                                            </span>
                                        </td>

                                        <td class="C_Lichsuontap_td time-text">
                                            <strong><?= htmlspecialchars($row['thoi_gian']) ?></strong>
                                            <small>Thời lượng: <?= htmlspecialchars($row['thoi_luong']) ?></small>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($historyTotalPages > 1): ?>
                    <nav class="C_Lichsuontap_pagination" aria-label="Phân trang lịch sử ôn tập">
                        <?php if ($historyPage > 1): ?>
                            <?php $previousQuery = http_build_query(['range' => $selectedRange, 'page' => $historyPage - 1]); ?>
                            <a href="?<?= htmlspecialchars($previousQuery) ?>" aria-label="Trang trước">&lt;</a>
                        <?php else: ?>
                            <span class="is-disabled" aria-hidden="true">&lt;</span>
                        <?php endif; ?>

                        <?php for ($pageNumber = $historyFirstVisiblePage; $pageNumber <= $historyLastVisiblePage; $pageNumber++): ?>
                            <?php $pageQuery = http_build_query(['range' => $selectedRange, 'page' => $pageNumber]); ?>
                            <a href="?<?= htmlspecialchars($pageQuery) ?>"
                               class="<?= $pageNumber === $historyPage ? 'is-active' : '' ?>"
                               <?= $pageNumber === $historyPage ? 'aria-current="page"' : '' ?>><?= $pageNumber ?></a>
                        <?php endfor; ?>

                        <?php if ($historyPage < $historyTotalPages): ?>
                            <?php $nextQuery = http_build_query(['range' => $selectedRange, 'page' => $historyPage + 1]); ?>
                            <a href="?<?= htmlspecialchars($nextQuery) ?>" aria-label="Trang sau">&gt;</a>
                        <?php else: ?>
                            <span class="is-disabled" aria-hidden="true">&gt;</span>
                        <?php endif; ?>
                    </nav>
                <?php endif; ?>
            </section>
        </main>
    </div>

    <!-- Dẫn đúng JavaScript của trang Lịch sử ôn tập -->
    <script src="../../JS/jquery-4.0.0.min.js"></script>
    <script src="../../JS/C_Lichsuontap.js"></script>
    <script src="../../JS/auth.js"></script>
</body>

</html>
