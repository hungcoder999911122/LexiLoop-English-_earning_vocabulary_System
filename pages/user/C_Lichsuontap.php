<?php
if (session_status() === PHP_SESSION_NONE) { session_start(); }
require_once($_SERVER['DOCUMENT_ROOT'] . '/Connect.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/database_objects.php');
$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? (int) $_SESSION['user_id'] : null;
$selectedRange = (string) ($_GET['range'] ?? '7');
if (!in_array($selectedRange, ['7', '30', 'all'], true)) { $selectedRange = '7'; }
$historyPage = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$historyPerPage = 10;
$chartEndDate = date('Y-m-d');
$chartStartDate = null;
$chartTitle = $selectedRange === 'all' ? 'Số từ ôn tập theo tháng' : 'Số từ ôn tập trong ' . $selectedRange . ' ngày qua';
$du_lieu_bieu_do = [];
$danh_sach_lich_su = [];

function dinhDangThoiGian($value): string { return $value ? date('H:i, d/m/Y', strtotime($value)) : ''; }
function dinhDangThoiLuong(int $seconds): string {
    $seconds = max(0, $seconds);
    if ($seconds < 60) { return $seconds . ' giây'; }
    $minutes = intdiv($seconds, 60);
    return $seconds % 60 ? $minutes . ' phút ' . ($seconds % 60) . ' giây' : $minutes . ' phút';
}
function taoDuLieuBieuDoTheoNgay(string $startDate, int $days): array {
    $items = [];
    for ($i = 0; $i < $days; $i++) {
        $date = date('Y-m-d', strtotime("+$i days", strtotime($startDate)));
        $weekday = (int) date('w', strtotime($date));
        $items[$date] = ['key' => $date, 'label' => $days === 7 ? ['CN','T2','T3','T4','T5','T6','T7'][$weekday] : date('d/m', strtotime($date)), 'so_tu' => 0, 'chieu_cao' => '0%'];
    }
    return $items;
}

if ($selectedRange !== 'all') {
    $days = (int) $selectedRange;
    $chartStartDate = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
    $du_lieu_bieu_do = taoDuLieuBieuDoTheoNgay($chartStartDate, $days);
} else {
    $du_lieu_bieu_do = ['empty' => ['key' => 'empty', 'label' => 'Chưa có dữ liệu', 'so_tu' => 0, 'chieu_cao' => '0%']];
}

if ($isLoggedIn && isset($link) && $link instanceof mysqli) {
    try {
        $where = 'user_id = ?';
        $types = 'i';
        $params = [$user_id];
        if ($selectedRange !== 'all') {
            $where .= ' AND DATE(activity_time) BETWEEN ? AND ?';
            $types .= 'ss';
            $params[] = $chartStartDate;
            $params[] = $chartEndDate;
        }
        $activities = dbSelectView($link, "SELECT * FROM vw_user_recent_activity WHERE $where ORDER BY activity_time DESC, id DESC", $types, $params);
        if ($selectedRange === 'all') {
            $du_lieu_bieu_do = [];
        }
        foreach ($activities as $row) {
            $date = date('Y-m-d', strtotime($row['activity_time']));
            $words = $row['activity_type'] === 'quiz' ? (int) $row['total_questions'] : (int) $row['words_studied'];
            $key = $selectedRange === 'all' ? date('Y-m', strtotime($date)) : $date;
            if ($selectedRange === 'all' && !isset($du_lieu_bieu_do[$key])) {
                $du_lieu_bieu_do[$key] = ['key' => $key, 'label' => date('m/Y', strtotime($date)), 'so_tu' => 0, 'chieu_cao' => '0%'];
            }
            if (isset($du_lieu_bieu_do[$key])) { $du_lieu_bieu_do[$key]['so_tu'] += $words; }
            $time = dinhDangThoiGian($row['activity_time']);
            if (!(int) $row['has_exact_time']) { $time = 'Ngày ' . date('d/m/Y', strtotime($row['activity_time'])) . ' · chưa lưu giờ'; }
            $danh_sach_lich_su[] = [
                'loai' => $row['activity_type'],
                'hoat_dong' => ($row['activity_type'] === 'quiz' ? 'Quiz · ' : 'Flashcard · ') . $row['source_name'],
                'ket_qua' => $row['activity_type'] === 'quiz'
                    ? 'Đúng ' . (int) $row['correct_answers'] . '/' . (int) $row['total_questions']
                    : 'Đã học ' . (int) $row['words_studied'] . ' từ',
                'thoi_gian' => $time,
                'thoi_luong' => dinhDangThoiLuong((int) $row['duration_seconds']),
            ];
        }
        if (!$du_lieu_bieu_do) {
            $du_lieu_bieu_do = ['empty' => ['key' => 'empty', 'label' => 'Chưa có dữ liệu', 'so_tu' => 0, 'chieu_cao' => '0%']];
        }
    } catch (Throwable $error) {
        error_log('Lỗi Lịch sử ôn tập: ' . $error->getMessage());
    }
}
$du_lieu_bieu_do = array_values($du_lieu_bieu_do);
$historyTotalItems = count($danh_sach_lich_su);
$historyTotalPages = max(1, (int) ceil($historyTotalItems / $historyPerPage));
$historyPage = min($historyPage, $historyTotalPages);
$danh_sach_lich_su = array_slice($danh_sach_lich_su, ($historyPage - 1) * $historyPerPage, $historyPerPage);
$historyFirstVisiblePage = max(1, min($historyPage - 1, $historyTotalPages - 2));
$historyLastVisiblePage = min($historyTotalPages, $historyFirstVisiblePage + 2);
$tong_tu_tuan = array_sum(array_column($du_lieu_bieu_do, 'so_tu'));
$maxWords = max(array_column($du_lieu_bieu_do, 'so_tu') ?: [0]);
foreach ($du_lieu_bieu_do as &$item) {
    $item['chieu_cao'] = $maxWords > 0 && $item['so_tu'] > 0 ? max(8, min(100, round($item['so_tu'] * 100 / $maxWords))) . '%' : '0%';
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
