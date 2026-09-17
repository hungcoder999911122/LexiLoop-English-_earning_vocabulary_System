<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
require dirname(__DIR__) . '/Connect.php';
require dirname(__DIR__) . '/includes/database_objects.php';
function checkDashboard(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
function dashboardTestRender(array $query = []): array {
    $process = proc_open([PHP_BINARY, __DIR__ . '/admin_pages_integration.php', '--render', 'D_Dashboard_admin', http_build_query($query)], [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    $output = stream_get_contents($pipes[1]); fclose($pipes[1]);
    $errors = stream_get_contents($pipes[2]); fclose($pipes[2]);
    checkDashboard(proc_close($process) === 0, $errors);
    return json_decode($output, true, 512, JSON_THROW_ON_ERROR);
}
try {
    $page = dashboardTestRender(['activity_page' => 2]);
    $users = dbSelectView($link, 'SELECT userID, role, status, created_at FROM vw_users');
    $admins = array_filter($users, fn($u) => $u['role'] === 'admin');
    $learners = array_filter($users, fn($u) => $u['role'] === 'user');
    $learnerIds = array_fill_keys(array_column($learners, 'userID'), true);
    $studyCounts = [];
    $todayLearners = [];
    $addStudy = static function (array $row, string $date) use ($page, $learnerIds, &$studyCounts, &$todayLearners): void {
        if (!isset($learnerIds[$row['user_id']]) || $date < $page['tuNgayHoatDong'] || $date >= $page['denNgayHoatDong']) return;
        $studyCounts[$date] = ($studyCounts[$date] ?? 0) + 1;
        if ($date === $page['ngayHienTai']) $todayLearners[$row['user_id']] = true;
    };
    $sessions = dbSelectView($link, 'SELECT user_id, topic_id, session_date, words_studied FROM vw_learning_sessions');
    $quizzes = dbSelectView($link, 'SELECT user_id, topic_id, finished_at FROM vw_quiz_results');
    foreach ($sessions as $session) {
        if ($session['words_studied'] > 0) $addStudy($session, $session['session_date']);
    }
    foreach ($quizzes as $quiz) {
        if ($quiz['finished_at'] !== null) $addStudy($quiz, substr($quiz['finished_at'], 0, 10));
    }
    checkDashboard($page['soQuanTriVien'] === count($admins) && $page['soHocVien'] === count($learners), 'Sai số tài khoản theo vai trò');
    $todayCount = $studyCounts[$page['ngayHienTai']] ?? 0;
    checkDashboard($page['soLuotHocHomNay'] === $todayCount && $page['soHocVienHomNay'] === count($todayLearners), 'Sai số lượt học/học viên hôm nay');
    for ($index = 0; $index < 7; $index++) {
        $date = date('Y-m-d', strtotime($page['tuNgayHoatDong'] . ' +' . $index . ' days'));
        checkDashboard($page['hoatDongTuan'][$index] === ($studyCounts[$date] ?? 0), 'Sai biểu đồ ngày: ' . $date);
    }
    checkDashboard($page['hoatDongTuan'][6] === $page['soLuotHocHomNay'], 'Thẻ hôm nay khác cột hôm nay');
    $registrations = count(array_filter($learners, fn($u) => $u['created_at'] >= $page['ngayHienTai'] && $u['created_at'] < $page['denNgayHoatDong']));
    checkDashboard($page['soDangKyHomNay'] === $registrations, 'Sai số học viên đăng ký hôm nay');
    foreach ([7, 30, 90] as $days) {
        $report = dashboardTestRender(['days' => $days, 'activity_page' => 2]);
        $periodSessions = array_filter($sessions, fn($s) => isset($learnerIds[$s['user_id']]) && $s['words_studied'] > 0 && $s['session_date'] >= $report['baoCaoTuNgay'] && $s['session_date'] < $report['baoCaoDenNgay']);
        $periodQuizzes = array_filter($quizzes, fn($q) => isset($learnerIds[$q['user_id']]) && $q['finished_at'] !== null && $q['finished_at'] >= $report['baoCaoTuNgay'] && $q['finished_at'] < $report['baoCaoDenNgay']);
        checkDashboard((int) $report['phanBoHoc']['flashcard'] === count($periodSessions) && (int) $report['phanBoHoc']['quiz'] === count($periodQuizzes), 'Sai số phiên/bài trong biểu đồ tròn');
        $total = count($periodSessions) + count($periodQuizzes);
        checkDashboard($report['tongLuotBaoCao'] === $total, 'Sai tổng biểu đồ tròn');
        checkDashboard(abs($report['flashcardPhanTram'] - ($total ? count($periodSessions) * 100 / $total : 0)) < .0001, 'Sai phần trăm Flashcard');
        $topicUsers = [];
        foreach (array_merge(array_values($periodSessions), array_values($periodQuizzes)) as $activity) {
            if ($activity['topic_id'] !== null) $topicUsers[(int) $activity['topic_id']][(int) $activity['user_id']] = true;
        }
        $expectedTopics = [];
        foreach ($topicUsers as $id => $ids) $expectedTopics[] = ['id' => $id, 'learners' => count($ids)];
        usort($expectedTopics, fn($a, $b) => ($b['learners'] <=> $a['learners']) ?: ($a['id'] <=> $b['id']));
        $expectedTopics = array_slice($expectedTopics, 0, 5);
        checkDashboard(count($report['chuDeNoiBat']) === count($expectedTopics), 'Sai số chủ đề nổi bật');
        foreach ($expectedTopics as $index => $expected) {
            checkDashboard((int) $report['chuDeNoiBat'][$index]['topicID'] === $expected['id'] && (int) $report['chuDeNoiBat'][$index]['learners'] === $expected['learners'], 'Sai xếp hạng/đếm trùng người học chủ đề');
        }
        checkDashboard($report['soLuotHocHomNay'] === $page['soLuotHocHomNay'] && $report['soDangKyHomNay'] === $registrations && $report['hoatDongTuan'] === $page['hoatDongTuan'], 'Bộ lọc báo cáo ảnh hưởng số hôm nay/tuần');
        checkDashboard($report['activityPagination']['page'] === $page['activityPagination']['page'], 'Đổi bộ lọc làm mất trang hoạt động');
        checkDashboard(str_contains($report['html'], 'id="dashboard-report-filter"') && str_contains($report['html'], 'id="dashboard-topics-title"') && str_contains($report['html'], 'id="dashboard-distribution-title"'), 'Thiếu bố cục báo cáo');
        checkDashboard($total ? str_contains($report['html'], 'class="dashboard-donut"') : str_contains($report['html'], 'Chưa có lượt học trong khoảng thời gian này'), 'Sai trạng thái trống/có dữ liệu');
        if ($report['activityPagination']['pages'] > 1) checkDashboard(str_contains($report['html'], 'days=' . $days . '&amp;activity_page='), 'Phân trang không giữ thời gian thống kê');
        echo 'PASS: báo cáo ' . $days . " ngày, xếp hạng chủ đề, Flashcard/Quiz, phạm vi độc lập và phân trang.\n";
    }
    foreach (['abc', '999', ['7']] as $invalidDays) checkDashboard(dashboardTestRender(['days' => $invalidDays])['baoCaoSoNgay'] === 30, 'Không xử lý khoảng ngày sai');
    foreach (['soChuDe' => 'vw_topic_catalog', 'soBoTu' => 'vw_vocabulary_sets', 'soTuVung' => 'vw_vocabulary_catalog'] as $key => $view) {
        checkDashboard($page[$key] === count(dbSelectView($link, 'SELECT * FROM ' . $view)), 'Sai số nội dung: ' . $key);
    }
    foreach (['D_Quanlynguoidung.php?role=admin', 'D_Quanlynguoidung.php?role=user', 'D_Quanlychude.php?tab=system', 'D_Quanlychude.php?tab=usersets'] as $url) checkDashboard(str_contains($page['html'], 'href="' . $url . '"'), 'Sai liên kết: ' . $url);
    checkDashboard(!str_contains($page['html'], 'Người dùng đăng ký') && !str_contains($page['html'], 'Quiz hoàn thành') && !str_contains($page['html'], 'dashboard-activity-meter'), 'Còn khối cũ');
    checkDashboard(str_contains($page['html'], 'admin-pagination') && str_contains($page['html'], '</html>'), 'Mất phân trang hoạt động hoặc HTML lỗi');
    echo "PASS: PHP E_ALL, lượt học hôm nay, học viên duy nhất, biểu đồ 7 ngày, vai trò/nội dung, liên kết và phân trang.\n";
    echo 'Hôm nay: ' . $todayCount . ' lượt, ' . count($todayLearners) . " học viên.\n";
} catch (Throwable $error) { fwrite(STDERR, 'FAIL: ' . $error->getMessage() . PHP_EOL); exit(1); }
