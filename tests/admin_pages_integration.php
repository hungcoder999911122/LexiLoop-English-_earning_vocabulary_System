<?php
declare(strict_types=1);

// Công cụ kiểm thử nội bộ; không cho phép thực thi từ trình duyệt.
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
// CLI không gửi cookie/cache header; phiên test HTTP vẫn dùng cùng session store.
ini_set('session.use_cookies', '0');
ini_set('session.cache_limiter', '');
error_reporting(E_ALL);
set_error_handler(static function (int $severity, string $message, string $file, int $line): bool {
    if (error_reporting() & $severity) throw new ErrorException($message, 0, $severity, $file, $line);
    return false;
});
$testRoot = dirname(__DIR__);
require_once $testRoot . '/Connect.php';
require_once $testRoot . '/includes/database_objects.php';

// Mỗi trang chạy trong process riêng, đúng cách PHP xử lý từng request.
if (($argv[1] ?? '') === '--render') {
    $testPage = $argv[2] ?? '';
    if (!in_array($testPage, ['D_Dashboard_admin', 'D_Quanlynguoidung', 'D_Quanlychude', 'D_Quanlytuvung', 'D_Thongkehethong', 'D_Caidathethong', 'D_DangNhapAdmin'], true)) exit(2);
    parse_str($argv[3] ?? '', $_GET);
    $_POST = [];
    $_SERVER['DOCUMENT_ROOT'] = $testRoot;
    $_SERVER['REQUEST_METHOD'] = 'GET';
    session_start();
    $testAdmin = dbSelectView($link, "SELECT userID, full_name FROM vw_users WHERE role = 'admin' AND status = 'active' ORDER BY userID LIMIT 1")[0] ?? null;
    if (!$testAdmin) throw new RuntimeException('Cần một tài khoản admin active để chạy kiểm thử.');
    $_SESSION = ['user_id' => (int) $testAdmin['userID'], 'full_name' => $testAdmin['full_name'], 'role' => 'admin', 'auth_scope' => 'admin'];
    ob_start();
    require $testRoot . '/pages/admin/' . $testPage . '.php';
    $testHtml = ob_get_clean();
    $testResult = ['html' => $testHtml];
    foreach (['activityPagination', 'userPagination', 'topicPagination', 'setPagination', 'vocabularyPagination', 'reportPagination', 'quiz', 'learners', 'averageWords', 'scoreGroups', 'lowPercent', 'mediumEnd', 'flashcard', 'flashcardGroups', 'rememberedPercent', 'tongSoTuVung', 'soTuHeThong', 'soTuCaNhan', 'soQuanTriVien', 'soHocVien', 'soLuotHocHomNay', 'soHocVienHomNay', 'soDangKyHomNay', 'baoCaoSoNgay', 'baoCaoTuNgay', 'baoCaoDenNgay', 'chuDeNoiBat', 'phanBoHoc', 'tongLuotBaoCao', 'flashcardPhanTram', 'hoatDongTuan', 'ngayHienTai', 'soChuDe', 'soBoTu', 'soTuVung', 'tuNgayHoatDong', 'denNgayHoatDong'] as $testName) {
        if (isset($$testName)) $testResult[$testName] = $$testName;
    }
    session_destroy();
    echo json_encode($testResult, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    exit;
}

function checkAdmin(bool $condition, string $message): void {
    if (!$condition) throw new RuntimeException($message);
}
function renderAdmin(string $page, array $query = []): array {
    $pipes = [];
    $process = proc_open([PHP_BINARY, __FILE__, '--render', $page, http_build_query($query)], [0 => ['pipe', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $pipes);
    if (!is_resource($process)) throw new RuntimeException('Không thể tạo process kiểm thử.');
    fclose($pipes[0]);
    $output = stream_get_contents($pipes[1]);
    $errors = stream_get_contents($pipes[2]);
    fclose($pipes[1]); fclose($pipes[2]);
    $code = proc_close($process);
    checkAdmin($code === 0, $page . ' lỗi PHP: ' . $errors);
    $result = json_decode($output, true, 512, JSON_THROW_ON_ERROR);
    checkAdmin(str_contains($result['html'], '</html>'), $page . ': HTML chưa render xong');
    return $result;
}
function collectAdminPages(string $page, string $paginationName, string $parameter, array $query = []): array {
    $first = renderAdmin($page, $query);
    $pagination = $first[$paginationName];
    $rows = [];
    for ($number = 1; $number <= $pagination['pages']; $number++) {
        $result = $number === 1 ? $first : renderAdmin($page, array_merge($query, [$parameter => $number]));
        $part = $result[$paginationName];
        checkAdmin(count($part['rows']) <= $part['perPage'], $page . ': quá số dòng/trang');
        checkAdmin($part['page'] === $number, $page . ': sai trang');
        checkAdmin(str_contains($result['html'], 'admin-pagination'), $page . ': thiếu thanh phân trang');
        $rows = array_merge($rows, $part['rows']);
    }
    checkAdmin(count($rows) === $pagination['total'], $page . ': thiếu hoặc dư bản ghi giữa các trang');
    return $rows;
}

/** Kiểm thử Apache bằng một phiên admin riêng, không dùng phiên đang mở của anh. */
function requestAdminHttp(string $page, string $cookie = '', array $query = []): array {
    $options = ['method' => 'GET', 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 10];
    if ($cookie !== '') $options['header'] = 'Cookie: PHPSESSID=' . $cookie . "\r\n";
    $html = file_get_contents('http://127.0.0.1/pages/admin/' . $page . '.php?' . http_build_query($query), false, stream_context_create(['http' => $options]));
    $statusLine = $http_response_header[0] ?? '';
    preg_match('/\s(\d{3})\s/', $statusLine, $match);
    return ['status' => (int) ($match[1] ?? 0), 'html' => $html === false ? '' : $html];
}

try {
    // Chứng minh tất cả đối tượng được PHP tham chiếu đã tồn tại trên MySQL đang chạy.
    $referencedViews = [];
    $referencedProcedures = [];
    foreach (array_merge(glob($testRoot . '/pages/admin/*.php'), [$testRoot . '/includes/admin_users_controller.php']) as $file) {
        $sourceText = file_get_contents($file);
        preg_match_all('/\bvw_[a-z_]+\b/', $sourceText, $matches);
        $referencedViews = array_merge($referencedViews, $matches[0]);
        preg_match_all('/\bCALL\s+(sp_[a-z_]+)\s*\(/i', $sourceText, $matches);
        $referencedProcedures = array_merge($referencedProcedures, $matches[1]);
        checkAdmin(!preg_match('/\bmysqli_(query|prepare)\s*\(/', $sourceText), basename($file) . ': gọi SQL trực tiếp thay vì helper đối tượng CSDL');
    }
    foreach (array_unique($referencedViews) as $view) {
        dbSelectView($link, 'SELECT * FROM ' . $view . ' LIMIT 0');
    }
    // Metadata phục vụ kiểm thử triển khai, không phải truy vấn nghiệp vụ của ứng dụng.
    $routineResult = mysqli_query($link, "SELECT ROUTINE_NAME FROM information_schema.ROUTINES WHERE ROUTINE_SCHEMA = DATABASE() AND ROUTINE_TYPE = 'PROCEDURE'");
    $deployedProcedures = array_column(mysqli_fetch_all($routineResult, MYSQLI_ASSOC), 'ROUTINE_NAME');
    foreach (array_unique($referencedProcedures) as $procedure) checkAdmin(in_array($procedure, $deployedProcedures, true), 'MySQL thiếu procedure ' . $procedure);
    echo 'PASS: ' . count(array_unique($referencedViews)) . ' View và ' . count(array_unique($referencedProcedures)) . " Stored Procedure admin đã triển khai trên MySQL.\n";

    foreach (['D_Dashboard_admin', 'D_Quanlynguoidung', 'D_Quanlychude', 'D_Quanlytuvung', 'D_Thongkehethong', 'D_Caidathethong', 'D_DangNhapAdmin'] as $page) renderAdmin($page);
    echo "PASS: 7 trang admin render với PHP/MySQL thật, E_ALL bật.\n";

    foreach ([['D_Quanlytuvung', 'vocabularyPagination', 'page', 'id'], ['D_Quanlynguoidung', 'userPagination', 'page', 'userID'], ['D_Quanlychude', 'topicPagination', 'topic_page', 'topicID'], ['D_Quanlychude', 'setPagination', 'set_page', 'id']] as [$page, $name, $parameter, $id]) {
        $rows = collectAdminPages($page, $name, $parameter);
        checkAdmin(count(array_unique(array_column($rows, $id))) === count($rows), $page . ': trùng bản ghi khi chuyển trang');
        $last = renderAdmin($page, [$parameter => '999999']);
        checkAdmin($last[$name]['page'] === $last[$name]['pages'], $page . ': không giới hạn trang vượt phạm vi');
        foreach (['-1', 'abc', ['1']] as $invalid) {
            $bad = renderAdmin($page, [$parameter => $invalid]);
            checkAdmin($bad[$name]['page'] === 1, $page . ': tham số trang không hợp lệ');
        }
    }
    $activities = collectAdminPages('D_Dashboard_admin', 'activityPagination', 'activity_page');
    $keys = array_map(static fn($row): string => $row['loai'] . ':' . $row['activity_id'], $activities);
    checkAdmin(count(array_unique($keys)) === count($keys), 'Hoạt động trùng giữa các trang');
    echo "PASS: phân trang toàn bộ danh sách và hoạt động, không trùng/mất dữ liệu; xử lý tham số sai.\n";

    $words = dbSelectView($link, 'SELECT id, word, source_type, topic_id, set_ids FROM vw_vocabulary_catalog ORDER BY created_at DESC, id DESC');
    if (count($words) > 10) {
        $target = end($words);
        $found = renderAdmin('D_Quanlytuvung', ['q' => $target['word']]);
        $filtered = collectAdminPages('D_Quanlytuvung', 'vocabularyPagination', 'page', ['q' => $target['word']]);
        checkAdmin(in_array($target['id'], array_column($filtered, 'id')), 'Tìm kiếm không tìm được từ nằm ngoài trang đầu');
        checkAdmin(str_contains($found['html'], 'name="q"'), 'Thiếu form tìm kiếm server');
    }
    foreach (['system', 'personal'] as $source) {
        $rows = collectAdminPages('D_Quanlytuvung', 'vocabularyPagination', 'page', ['source' => $source]);
        foreach ($rows as $row) checkAdmin($row['source_type'] === $source, 'Sai lọc nguồn');
    }
    foreach (dbSelectView($link, 'SELECT id FROM vw_vocabulary_sets') as $set) {
        $result = renderAdmin('D_Quanlytuvung', ['category' => 'set_' . $set['id']]);
        $expected = array_filter($words, static fn($row): bool => in_array((string) $set['id'], explode(',', $row['set_ids'] ?? ''), true));
        checkAdmin($result['vocabularyPagination']['total'] === count($expected), 'Sai lọc bộ từ bằng ID');
        foreach (['system', 'personal'] as $sourceFilter) {
            $part = renderAdmin('D_Quanlytuvung', ['category' => 'set_' . $set['id'], 'source' => $sourceFilter]);
            $expectedPart = array_filter($expected, static fn($row): bool => $row['source_type'] === $sourceFilter);
            checkAdmin($part['vocabularyPagination']['total'] === count($expectedPart), 'Lọc nguồn làm mất phạm vi bộ từ');
        }
    }

    // Đi theo URL thật từ nút Xem từ, đối chiếu danh sách và thông tin ngữ cảnh.
    $origin = renderAdmin('D_Quanlychude');
    $viewLinks = [];
    foreach (['topicPagination' => 'topic_page', 'setPagination' => 'set_page'] as $name => $parameter) {
        for ($number = 1; $number <= $origin[$name]['pages']; $number++) {
            $part = renderAdmin('D_Quanlychude', [$parameter => $number]);
            preg_match_all('/<a href="([^"]+)" class="D_Quanlychude_NutXem"/', $part['html'], $matches);
            $viewLinks = array_merge($viewLinks, $matches[1]);
        }
    }
    $topicCatalog = dbSelectView($link, 'SELECT topicID, topicName FROM vw_topic_catalog');
    $setCatalog = dbSelectView($link, 'SELECT id, name, owner_name, owner_email, user_id FROM vw_vocabulary_sets');
    checkAdmin(count(array_unique($viewLinks)) === count($topicCatalog) + count($setCatalog), 'Thiếu link Xem từ cho chủ đề hoặc bộ từ');
    foreach (array_unique($viewLinks) as $href) {
        parse_str(parse_url(html_entity_decode($href, ENT_QUOTES, 'UTF-8'), PHP_URL_QUERY) ?? '', $query);
        checkAdmin(isset($query['category']), 'Link Xem từ không truyền ID');
        $result = renderAdmin('D_Quanlytuvung', $query);
        $isTopic = str_starts_with($query['category'], 'topic_');
        $id = (int) substr($query['category'], $isTopic ? 6 : 4);
        $expected = array_filter($words, static fn($row): bool => $isTopic ? (int) $row['topic_id'] === $id : in_array((string) $id, explode(',', $row['set_ids'] ?? ''), true));
        checkAdmin($result['vocabularyPagination']['total'] === count($expected), 'Xem từ hiển thị sai phạm vi');
        checkAdmin($result['tongSoTuVung'] === count($expected), 'Thông tin tổng vẫn lấy số toàn hệ thống');
        checkAdmin($result['soTuHeThong'] + $result['soTuCaNhan'] === count($expected), 'Sai phân bố nguồn trong phạm vi');
        foreach ($result['vocabularyPagination']['rows'] as $row) checkAdmin(in_array($row['id'], array_column($expected, 'id')), 'Lẫn từ ngoài chủ đề/bộ từ đã chọn');
        $catalog = array_values(array_filter($isTopic ? $topicCatalog : $setCatalog, static fn($row): bool => (int) $row[$isTopic ? 'topicID' : 'id'] === $id))[0];
        $title = htmlspecialchars('Từ vựng: ' . $catalog[$isTopic ? 'topicName' : 'name'], ENT_QUOTES, 'UTF-8');
        checkAdmin(str_contains($result['html'], '>' . $title . '</h1>'), 'Tiêu đề không khớp mục đã chọn');
        checkAdmin(str_contains($result['html'], 'href="D_Quanlychude.php?tab=' . ($isTopic ? 'system' : 'usersets') . '"'), 'Quay lại sai tab');
        if (!$isTopic) checkAdmin(str_contains($result['html'], 'Chủ sở hữu:'), 'Thiếu thông tin chủ sở hữu bộ từ');
    }
    $direct = renderAdmin('D_Quanlytuvung');
    checkAdmin($direct['tongSoTuVung'] === count($words), 'Vào trực tiếp không hiển thị tổng toàn hệ thống');
    checkAdmin(str_contains($direct['html'], '>Quản lý từ vựng</h1>'), 'Vào trực tiếp vẫn giữ tiêu đề mục cũ');
    $missing = renderAdmin('D_Quanlytuvung', ['category' => 'set_2147483647']);
    checkAdmin($missing['tongSoTuVung'] === 0 && str_contains($missing['html'], 'không còn tồn tại'), 'ID bộ từ đã xóa không được mở toàn bộ danh sách');
    $originQuery = ['source' => 'system', 'category' => 'topic_' . $topicCatalog[0]['topicID']];
    $searched = renderAdmin('D_Quanlytuvung', array_merge($originQuery, ['q' => 'no_matching_word_9f84']));
    checkAdmin($searched['tongSoTuVung'] === 0, 'Tổng chưa thay đổi theo từ khóa trong chủ đề');
    echo "PASS: nút Xem từ truyền ID, lọc đúng dữ liệu và số liệu ngữ cảnh; vào trực tiếp và ID đã xóa.\n";
    $empty = renderAdmin('D_Quanlytuvung', ['q' => "' OR 1=1 --"]);
    checkAdmin($empty['vocabularyPagination']['total'] === 0, 'Tìm kiếm không được bind an toàn');
    $escaped = renderAdmin('D_Quanlytuvung', ['q' => '<script>alert(1)</script>']);
    checkAdmin(!str_contains($escaped['html'], '<script>alert(1)</script>'), 'Từ khóa tìm kiếm không escape HTML');
    $literal = renderAdmin('D_Quanlytuvung', ['q' => '%']);
    foreach ($literal['vocabularyPagination']['rows'] as $row) {
        checkAdmin(str_contains(implode(' ', array_map(static fn($value): string => (string) $value, $row)), '%'), 'Ký tự % bị coi là wildcard');
    }
    $users = renderAdmin('D_Quanlynguoidung', ['role' => 'admin', 'status' => 'active']);
    foreach ($users['userPagination']['rows'] as $user) checkAdmin($user['role'] === 'admin' && $user['status'] === 'active', 'Sai bộ lọc người dùng');
    $setsTab = renderAdmin('D_Quanlychude', ['tab' => 'usersets', 'topic_page' => 2]);
    checkAdmin(str_contains($setsTab['html'], 'id="tab-usersets" class="tab-pane tab-pane-active"'), 'Không giữ tab bộ từ');
    $filteredPages = renderAdmin('D_Quanlytuvung', ['source' => 'system', 'page' => 2, 'q' => '']);
    checkAdmin(str_contains($filteredPages['html'], 'source=system&amp;page='), 'Link phân trang làm mất lọc nguồn');
    checkAdmin(str_contains($filteredPages['html'], 'action="?source=system&amp;page=2&amp;q="'), 'Form cập nhật làm mất trang và bộ lọc');
    echo "PASS: tìm kiếm toàn dữ liệu, lọc nguồn/bộ từ/người dùng, giữ tab, escape HTML và tham số SQL.\n";

    // Bộ lọc nội dung áp dụng cho cả chủ đề và bộ từ, trước khi phân trang.
    $allTopics = dbSelectView($link, 'SELECT topicID, word_count FROM vw_topic_catalog');
    $allSets = dbSelectView($link, 'SELECT id, word_count FROM vw_vocabulary_sets');
    foreach (['empty', 'with_words'] as $content) {
        $result = renderAdmin('D_Quanlychude', ['content' => $content, 'sort' => 'words']);
        foreach ([['topicPagination', $allTopics], ['setPagination', $allSets]] as [$name, $catalog]) {
            $expected = array_filter($catalog, static fn($row): bool => $content === 'empty' ? (int) $row['word_count'] === 0 : (int) $row['word_count'] > 0);
            checkAdmin($result[$name]['total'] === count($expected), 'Sai bộ lọc có/chưa có từ');
            $counts = array_map('intval', array_column($result[$name]['rows'], 'soTuVung'));
            $sorted = $counts; rsort($sorted);
            checkAdmin($counts === $sorted, 'Sai sắp xếp nhiều từ nhất');
        }
    }
    $topicFilter = renderAdmin('D_Quanlychude', ['content' => 'with_words', 'sort' => 'name', 'topic_page' => 2]);
    checkAdmin(str_contains($topicFilter['html'], 'content=with_words&amp;sort=name'), 'Phân trang không giữ lọc nội dung/sắp xếp');
    foreach (['D_Quanlychude', 'D_Quanlytuvung'] as $page) {
        $result = renderAdmin($page);
        $mainPosition = strpos($result['html'], '<main');
        $formPosition = strpos($result['html'], 'class="admin-list-toolbar"');
        $tablePosition = strpos($result['html'], '<table');
        checkAdmin($mainPosition < $formPosition && $formPosition < $tablePosition, 'Thanh tìm kiếm/lọc phải nằm trong nội dung, trên bảng');
        checkAdmin(substr_count($result['html'], 'id="admin-filters"') === 1, 'Trùng form tìm kiếm');
    }
    echo "PASS: thanh tìm kiếm/lọc trên bảng, lọc có/chưa có từ, sắp xếp và giữ điều kiện phân trang.\n";

    // Đối chiếu thống kê bằng cách gom dữ liệu trong PHP, độc lập với SQL tổng hợp của trang.
    $sessions = dbSelectView($link, 'SELECT user_id, topic_id, session_date, words_studied FROM vw_learning_sessions');
    $quizzes = dbSelectView($link, 'SELECT user_id, topic_id, score, finished_at FROM vw_quiz_results WHERE finished_at IS NOT NULL');
    $studentIds = array_column(dbSelectView($link, "SELECT userID FROM vw_users WHERE role = 'user'"), 'userID');
    $sessions = array_filter($sessions, static fn($row): bool => in_array($row['user_id'], $studentIds) && $row['words_studied'] > 0);
    $quizzes = array_filter($quizzes, static fn($row): bool => in_array($row['user_id'], $studentIds));
    $progressRows = dbSelectView($link, 'SELECT user_id, vocabulary_id, last_quality_rating, last_reviewed_at FROM vw_user_progress');
    foreach ([7, 30, 90] as $days) {
        $start = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
        $end = date('Y-m-d', strtotime('+1 day'));
        $periodSessions = array_filter($sessions, static fn($row): bool => $row['session_date'] >= $start && $row['session_date'] < $end);
        $periodQuizzes = array_filter($quizzes, static fn($row): bool => $row['finished_at'] >= $start && $row['finished_at'] < $end);
        $result = renderAdmin('D_Thongkehethong', ['days' => $days]);
        $assessments = array_filter($progressRows, static fn($row): bool => in_array($row['user_id'], $studentIds)
            && $row['last_reviewed_at'] !== null && $row['last_reviewed_at'] >= $start && $row['last_reviewed_at'] < $end
            && $row['last_quality_rating'] !== null && $row['last_quality_rating'] >= 0 && $row['last_quality_rating'] <= 5);
        $remembered = count(array_filter($assessments, static fn($row): bool => $row['last_quality_rating'] >= 3));
        checkAdmin((int) $result['flashcard']['assessed'] === count($assessments), 'Sai tổng đánh giá Flashcard');
        checkAdmin($result['flashcardGroups'][0]['count'] === $remembered && $result['flashcardGroups'][1]['count'] === count($assessments) - $remembered, 'Sai nhóm nhớ/chưa nhớ');
        $assessmentKeys = array_map(static fn($row): string => $row['user_id'] . ':' . $row['vocabulary_id'], $assessments);
        checkAdmin(count(array_unique($assessmentKeys)) === count($assessments), 'Đếm trùng học viên–từ');
        if ($assessments) {
            checkAdmin(abs((float) $result['rememberedPercent'] - $remembered * 100 / count($assessments)) < 0.0001, 'Sai phần trăm ghi nhớ');
            checkAdmin(str_contains($result['html'], 'aria-label="Đánh giá ghi nhớ Flashcard:'), 'Thiếu biểu đồ Flashcard có dữ liệu');
        } else {
            checkAdmin(str_contains($result['html'], 'Chưa có đánh giá ghi nhớ Flashcard'), 'Thiếu trạng thái Flashcard trống');
        }
        checkAdmin(str_contains($result['html'], 'class="stats-chart-grid"') && str_contains($result['html'], 'Học viên tham gia học') && str_contains($result['html'], 'Lượt luyện từ trung bình/ngày'), 'Sai bố cục/tên khối thống kê');
        $ids = array_unique(array_merge(array_column($periodSessions, 'user_id'), array_column($periodQuizzes, 'user_id')));
        checkAdmin($result['learners'] === count($ids), 'Sai số người học duy nhất');
        checkAdmin(abs($result['averageWords'] - array_sum(array_column($periodSessions, 'words_studied')) / $days) < 0.00001, 'Sai lượt từ/ngày');
        checkAdmin((int) $result['quiz']['completed'] === count($periodQuizzes), 'Sai số Quiz hoàn thành');
        $scores = array_map('floatval', array_column($periodQuizzes, 'score'));
        if ($scores) {
            checkAdmin(abs((float) $result['quiz']['average_score'] - array_sum($scores) / count($scores)) < 0.0001, 'Sai điểm Quiz trung bình');
            checkAdmin(str_contains($result['html'], 'conic-gradient'), 'Có dữ liệu nhưng thiếu biểu đồ tròn');
            foreach ([static fn($v): bool => $v < 50, static fn($v): bool => $v >= 50 && $v < 80, static fn($v): bool => $v >= 80] as $i => $predicate) {
                checkAdmin($result['scoreGroups'][$i]['count'] === count(array_filter($scores, $predicate)), 'Sai nhóm điểm biểu đồ');
            }
        } else {
            checkAdmin($result['quiz']['average_score'] === null, 'Không có Quiz phải trả NULL');
            checkAdmin(str_contains($result['html'], 'Chưa có Quiz hoàn thành'), 'Thiếu trạng thái trống');
        }
        $topicRows = collectAdminPages('D_Thongkehethong', 'reportPagination', 'topic_page', ['days' => $days]);
        foreach ($topicRows as $row) {
            $topicIds = [$row['topicID']];
            $topicSessions = array_filter($periodSessions, static fn($s): bool => in_array($s['topic_id'], $topicIds));
            $topicQuizzes = array_filter($periodQuizzes, static fn($q): bool => in_array($q['topic_id'], $topicIds));
            checkAdmin((int) $row['sessions'] === count($topicSessions), 'Phiên theo chủ đề bị nhân khi JOIN');
            checkAdmin((int) $row['words'] === array_sum(array_column($topicSessions, 'words_studied')), 'Sai lượt từ theo chủ đề');
            checkAdmin((int) $row['completed'] === count($topicQuizzes), 'Sai số Quiz theo chủ đề');
            checkAdmin(str_contains($result['html'], 'name="days"'), 'Thiếu bộ lọc khoảng báo cáo');
        }
        echo 'PASS: thống kê ' . $days . " ngày khớp MySQL; chỉ số, Quiz, ghi nhớ Flashcard và bảng chủ đề.\n";
    }

    // HTTP: kiểm tra cả phiên hợp lệ, chưa đăng nhập và sai phạm vi quản trị.
    $protectedPages = ['D_Dashboard_admin', 'D_Quanlynguoidung', 'D_Quanlychude', 'D_Quanlytuvung', 'D_Thongkehethong', 'D_Caidathethong'];
    foreach ($protectedPages as $page) checkAdmin(requestAdminHttp($page)['status'] === 302, $page . ': chưa đăng nhập phải chuyển đến login');
    $httpAdmin = dbSelectView($link, "SELECT userID, full_name FROM vw_users WHERE role = 'admin' AND status = 'active' LIMIT 1")[0];
    session_start();
    $httpSession = session_id();
    $_SESSION = ['user_id' => (int) $httpAdmin['userID'], 'full_name' => $httpAdmin['full_name'], 'auth_scope' => 'admin'];
    session_write_close();
    try {
        foreach ($protectedPages as $page) {
            $response = requestAdminHttp($page, $httpSession);
            checkAdmin($response['status'] === 200 && str_contains($response['html'], '</html>'), $page . ': HTTP render không thành công');
            checkAdmin(!preg_match('/(Fatal error|Warning:|Undefined variable)/', $response['html']), $page . ': lỗi PHP trên HTTP');
        }
        foreach ([['D_Dashboard_admin', ['activity_page' => 2]], ['D_Quanlychude', ['topic_page' => 2]], ['D_Quanlytuvung', ['source' => 'system', 'page' => 2]], ['D_Thongkehethong', ['days' => 90]]] as [$page, $query]) {
            $response = requestAdminHttp($page, $httpSession, $query);
            checkAdmin($response['status'] === 200 && str_contains($response['html'], 'admin-pagination'), $page . ': HTTP phân trang/bộ lọc lỗi');
        }
        session_id($httpSession); session_start();
        $_SESSION['auth_scope'] = 'user';
        session_write_close();
        checkAdmin(requestAdminHttp('D_Thongkehethong', $httpSession)['status'] === 403, 'Phiên người dùng thường truy cập được thống kê admin');
    } finally {
        session_id($httpSession); session_start(); session_destroy();
    }
    echo "PASS: HTTP Apache, phân trang/bộ lọc, chuyển hướng chưa đăng nhập và từ chối phiên user.\n";
    echo "ALL PASS (chỉ đọc dữ liệu; không thay đổi tài khoản/nội dung).\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . PHP_EOL);
    exit(1);
}
