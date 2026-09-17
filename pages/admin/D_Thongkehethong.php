<?php
require_once dirname(__DIR__, 2) . '/includes/admin_guard.php';

// Khai báo rõ kết nối dùng chung trước khi gọi View/Stored Procedure.
$link = getDatabaseConnection();
require_once dirname(__DIR__, 2) . '/includes/admin_pagination.php';

// Chỉ chấp nhận các khoảng báo cáo cố định; tham số ngày được bind trong SQL.
$requestedDays = $_GET['days'] ?? '30';
$days = is_string($requestedDays) && in_array($requestedDays, ['7', '30', '90'], true) ? (int) $requestedDays : 30;
$today = new DateTimeImmutable('today');
$start = $today->modify('-' . ($days - 1) . ' days');
$end = $today->modify('+1 day');
$previousStart = $start->modify('-' . $days . ' days');
$range = [$start->format('Y-m-d'), $end->format('Y-m-d')];
$previousRange = [$previousStart->format('Y-m-d'), $start->format('Y-m-d')];

function adminLearningSummary($link, array $range): array {
    return dbSelectView($link, "SELECT COALESCE(SUM(l.words_studied), 0) AS words FROM vw_learning_sessions l
        INNER JOIN vw_users u ON u.userID = l.user_id
        WHERE u.role = 'user' AND l.words_studied > 0 AND l.session_date >= ? AND l.session_date < ?", 'ss', $range)[0];
}

function adminLearnerCount($link, array $range): int {
    // UNION loại trùng người học giữa Flashcard và Quiz.
    return (int) dbSelectView($link, "SELECT COUNT(*) AS learners FROM (
        SELECT user_id FROM vw_learning_sessions WHERE session_date >= ? AND session_date < ? AND words_studied > 0
        UNION SELECT user_id FROM vw_quiz_results WHERE finished_at >= ? AND finished_at < ?
    ) AS learners_in_period INNER JOIN vw_users u ON u.userID = learners_in_period.user_id
    WHERE u.role = 'user'", 'ssss', array_merge($range, $range))[0]['learners'];
}

function adminPeriodChange(float $current, float $previous, string $unit = ''): string {
    if ($previous == 0) {
        return $current > 0 ? 'Kỳ trước chưa có dữ liệu để so sánh' : 'Không thay đổi so với kỳ trước';
    }
    $difference = $current - $previous;
    $value = $unit === '' ? round($difference * 100 / $previous, 1) : round($difference, 1);
    return ($value > 0 ? '+' : '') . number_format($value, 1) . ($unit ?: '%') . ' so với kỳ trước';
}

$learning = adminLearningSummary($link, $range);
$previousLearning = adminLearningSummary($link, $previousRange);
$learners = adminLearnerCount($link, $range);
$previousLearners = adminLearnerCount($link, $previousRange);
$quizSql = 'SELECT COUNT(*) AS completed, AVG(score) AS average_score,
    SUM(CASE WHEN score < 50 THEN 1 ELSE 0 END) AS low_score,
    SUM(CASE WHEN score >= 50 AND score < 80 THEN 1 ELSE 0 END) AS medium_score,
    SUM(CASE WHEN score >= 80 THEN 1 ELSE 0 END) AS high_score
    FROM vw_quiz_results WHERE finished_at >= ? AND finished_at < ?
    AND user_id IN (SELECT userID FROM vw_users WHERE role = \'user\')';
$quiz = dbSelectView($link, $quizSql, 'ss', $range)[0];
$previousQuiz = dbSelectView($link, $quizSql, 'ss', $previousRange)[0];
$averageWords = (float) $learning['words'] / $days;
$previousAverageWords = (float) $previousLearning['words'] / $days;

// Tổng hợp từng nguồn trước khi JOIN để tránh nhân số phiên và kết quả Quiz.
$topicReportFromSql = 'FROM vw_topic_catalog t
    LEFT JOIN (SELECT topic_id, COUNT(*) AS sessions, SUM(words_studied) AS words
        FROM vw_learning_sessions WHERE session_date >= ? AND session_date < ? AND words_studied > 0
        AND user_id IN (SELECT userID FROM vw_users WHERE role = \'user\') GROUP BY topic_id) l ON l.topic_id = t.topicID
    LEFT JOIN (SELECT topic_id, COUNT(*) AS completed, AVG(score) AS average_score
        FROM vw_quiz_results WHERE finished_at >= ? AND finished_at < ?
        AND user_id IN (SELECT userID FROM vw_users WHERE role = \'user\') GROUP BY topic_id) q ON q.topic_id = t.topicID
    WHERE COALESCE(l.sessions, 0) + COALESCE(q.completed, 0) > 0';
$topicReportSql = 'SELECT t.topicID, t.topicName, COALESCE(l.sessions, 0) AS sessions, COALESCE(l.words, 0) AS words, COALESCE(q.completed, 0) AS completed, q.average_score '
    . $topicReportFromSql . ' ORDER BY words DESC, completed DESC, t.topicID';
$topicCountSql = 'SELECT COUNT(*) AS total ' . $topicReportFromSql;
$reportPagination = adminPaginateView($link, $topicCountSql, $topicReportSql, 'topic_page', 10, 'ssss', array_merge($range, $range));
$topics = $reportPagination['rows'];
$scoreGroups = [
    ['label' => 'Dưới 50%', 'count' => (int) $quiz['low_score']],
    ['label' => '50% đến dưới 80%', 'count' => (int) $quiz['medium_score']],
    ['label' => 'Từ 80% trở lên', 'count' => (int) $quiz['high_score']],
];
// Dùng số thập phân theo chuẩn CSS, tránh dấu phẩy từ định dạng locale.
$completed = (int) $quiz['completed'];
$lowPercent = number_format($completed > 0 ? $scoreGroups[0]['count'] * 100 / $completed : 0, 4, '.', '');
$mediumEnd = number_format($completed > 0 ? ($scoreGroups[0]['count'] + $scoreGroups[1]['count']) * 100 / $completed : 0, 4, '.', '');
$scoreSummary = implode(', ', array_map(static function (array $group): string {
    return $group['label'] . ': ' . $group['count'] . ' bài';
}, $scoreGroups));

// View có một dòng cho mỗi học viên–từ, lấy đánh giá gần nhất trong kỳ.
// Flashcard lưu Đã nhớ=5, Chưa nhớ=2; thang ôn cũ coi >=3 là nhớ được.
// Không dùng status learning/mastered: đó là tiến độ SRS, không phải lựa chọn nhớ.
$flashcard = dbSelectView($link, "SELECT COUNT(*) AS assessed,
    COALESCE(SUM(p.last_quality_rating >= 3), 0) AS remembered,
    COALESCE(SUM(p.last_quality_rating < 3), 0) AS not_remembered
    FROM vw_user_progress p INNER JOIN vw_users u ON u.userID = p.user_id
    WHERE u.role = 'user' AND p.last_reviewed_at >= ? AND p.last_reviewed_at < ?
      AND p.last_quality_rating BETWEEN 0 AND 5", 'ss', $range)[0];
$flashcardTotal = (int) $flashcard['assessed'];
$flashcardGroups = [
    ['label' => 'Đã nhớ', 'count' => (int) $flashcard['remembered'], 'class' => 'stats-dot-remembered'],
    ['label' => 'Chưa nhớ', 'count' => (int) $flashcard['not_remembered'], 'class' => 'stats-dot-not-remembered'],
];
$rememberedPercent = number_format($flashcardTotal > 0 ? $flashcardGroups[0]['count'] * 100 / $flashcardTotal : 0, 4, '.', '');
$flashcardSummary = 'Đã nhớ: ' . $flashcardGroups[0]['count'] . ', chưa nhớ: ' . $flashcardGroups[1]['count'];
?>
<!doctype html>
<html lang="vi">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>LexiLoop Admin - Thống kê hệ thống</title>
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link rel="stylesheet" type="text/css" href="/CSS/D_Thongkehethong.css" />
    <link rel="stylesheet" href="/CSS/admin-pagination.css" />
    <link rel="stylesheet" href="/CSS/admin-sidebar.css" />
  </head>

  <body>
    <div class="D_Thongkehethong_Wrapper">
      <!-- Topbar Header -->
      <header class="D_Thongkehethong_Topbar">
        <div class="D_Thongkehethong_Logo">
          <span class="logo-emoji">🌿</span>
          <span class="logo-text">LexiLoop <span class="badge-admin">Admin</span></span>
        </div>
        <div class="D_Thongkehethong_TopbarPhai">
          <div class="D_Thongkehethong_UserMenu">
            <div class="D_Thongkehethong_Avatar"><?php echo strtoupper(substr($_SESSION['full_name'] ?? 'AD', 0, 2)); ?></div>
            <span class="admin-name"><?php echo htmlspecialchars($_SESSION['full_name'] ?? 'Admin'); ?></span>
          </div>
        </div>
      </header>

      <div class="D_Thongkehethong_Body">
        <!-- Sidebar Navigation -->
        <nav class="D_Thongkehethong_Sidebar admin-sidebar" aria-label="Điều hướng quản trị">
          <div class="sidebar-section-title">QUẢN TRỊ HỆ THỐNG</div>
          <a href="D_Dashboard_admin.php" class="D_Thongkehethong_MucMenu">
            <span class="menu-icon">📊</span>
            <span>Dashboard</span>
          </a>
          <a href="D_Quanlynguoidung.php" class="D_Thongkehethong_MucMenu">
            <span class="menu-icon">👥</span>
            <span>Người dùng</span>
          </a>
          <a href="D_Quanlychude.php" class="D_Thongkehethong_MucMenu">
            <span class="menu-icon">📚</span>
            <span>Chủ đề & Bộ từ</span>
          </a>
          <a href="D_Quanlytuvung.php" class="D_Thongkehethong_MucMenu">
            <span class="menu-icon">🔤</span>
            <span>Từ vựng</span>
          </a>

          <a href="D_Caidathethong.php" class="D_Thongkehethong_MucMenu">
            <span class="menu-icon">⚙️</span>
            <span>Cài đặt</span>
          </a>
          <hr class="D_Thongkehethong_GachNgang" />
          <a href="../auth/A_DangXuat.php" class="D_Thongkehethong_MucMenu D_Thongkehethong_DangXuat">
            <span class="menu-icon">🚪</span>
            <span>Đăng xuất</span>
          </a>
        </nav>

        <!-- Main Content -->
        <main class="D_Thongkehethong_NoiDung">
          <div class="D_Thongkehethong_HangTieuDe">
            <h1 class="D_Thongkehethong_TieuDe">Phân tích học tập</h1>
              <p class="D_Thongkehethong_MoTaTrang">Theo dõi mức độ tham gia, ghi nhớ Flashcard, kết quả quiz và hiệu quả từng chủ đề.</p>
          </div>
          <form class="stats-filter" method="get">
            <div><label for="stats-days">Khoảng báo cáo</label>
              <select id="stats-days" name="days">
                <?php foreach ([7, 30, 90] as $option): ?>
                  <option value="<?php echo $option; ?>" <?php echo $days === $option ? 'selected' : ''; ?>><?php echo $option; ?> ngày gần nhất</option>
                <?php endforeach; ?>
              </select><button type="submit">Xem báo cáo</button>
            </div>
            <p><?php echo $start->format('d/m/Y'); ?> – <?php echo $today->format('d/m/Y'); ?><br><small>So sánh với <?php echo $previousStart->format('d/m/Y'); ?> – <?php echo $start->modify('-1 day')->format('d/m/Y'); ?></small></p>
          </form>
          <div class="D_Thongkehethong_HangTheSo">
            <?php
              $cards = [
                ['Học viên tham gia học', number_format($learners), adminPeriodChange($learners, $previousLearners), 'Mỗi học viên tính một lần nếu đã học/ôn từ hoặc hoàn thành quiz trong kỳ; không tính admin.'],
                ['Lượt luyện từ trung bình/ngày', number_format($averageWords, 1), adminPeriodChange($averageWords, $previousAverageWords), 'Tổng lượt từ Flashcard chia cho ' . $days . ' ngày, kể cả ngày không học; một từ có thể được luyện nhiều lần.'],
                ['Điểm quiz trung bình', $quiz['average_score'] === null ? 'Chưa có' : number_format((float) $quiz['average_score'], 1) . '%', $quiz['average_score'] === null || $previousQuiz['average_score'] === null ? 'Chưa đủ dữ liệu so sánh' : adminPeriodChange((float) $quiz['average_score'], (float) $previousQuiz['average_score'], ' điểm phần trăm'), 'Trung bình điểm của các bài quiz hoàn thành trong kỳ.'],
              ];
              foreach ($cards as [$label, $value, $change, $description]):
            ?>
              <article class="D_Thongkehethong_TheSo" title="<?php echo htmlspecialchars($description, ENT_QUOTES, 'UTF-8'); ?>"><div>
                <p class="D_Thongkehethong_NhanTheSo"><?php echo $label; ?></p>
                <p class="D_Thongkehethong_SoLieu"><?php echo $value; ?></p>
                <p class="stats-change"><?php echo $change; ?></p>
              </div></article>
            <?php endforeach; ?>
          </div>
          <div class="stats-chart-grid">
          <section class="D_Thongkehethong_HopBieuDo stats-quiz-panel" aria-labelledby="stats-quiz-title">
            <h2 id="stats-quiz-title" class="D_Thongkehethong_TieuDeHop">Phân bố điểm quiz</h2>
            <?php if ((int) $quiz['completed'] === 0): ?>
              <p class="stats-empty">Chưa có Quiz hoàn thành trong khoảng thời gian này.</p>
            <?php else: ?>
              <div class="stats-quiz-layout">
                <div class="stats-donut" role="img" aria-label="Phân bố điểm Quiz: <?php echo htmlspecialchars($scoreSummary, ENT_QUOTES, 'UTF-8'); ?>" style="background: conic-gradient(#d97706 0% <?php echo $lowPercent; ?>%, #2563eb <?php echo $lowPercent; ?>% <?php echo $mediumEnd; ?>%, #0e7748 <?php echo $mediumEnd; ?>% 100%);">
                  <div class="stats-donut-center"><strong><?php echo number_format((int) $quiz['completed']); ?></strong><span>Quiz hoàn thành</span></div>
                </div>
                <ul class="stats-legend">
                  <?php foreach ($scoreGroups as $index => $group): $percent = $group['count'] * 100 / (int) $quiz['completed']; ?>
                    <li><span class="stats-dot stats-dot-<?php echo $index; ?>" aria-hidden="true"></span><div><span><?php echo $group['label']; ?></span><strong><?php echo number_format($group['count']); ?> bài · <?php echo number_format($percent, 1); ?>%</strong></div></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
          </section>
          <section class="D_Thongkehethong_HopBieuDo stats-flashcard-panel" aria-labelledby="stats-flashcard-title">
            <h2 id="stats-flashcard-title" class="D_Thongkehethong_TieuDeHop">Đánh giá ghi nhớ Flashcard</h2>
            <?php if ($flashcardTotal === 0): ?>
              <p class="stats-empty">Chưa có đánh giá ghi nhớ Flashcard trong khoảng thời gian này.</p>
            <?php else: ?>
              <div class="stats-quiz-layout">
                <div class="stats-donut" role="img" aria-label="Đánh giá ghi nhớ Flashcard: <?php echo htmlspecialchars($flashcardSummary, ENT_QUOTES, 'UTF-8'); ?>" style="background: conic-gradient(#0e7748 0% <?php echo $rememberedPercent; ?>%, #d97706 <?php echo $rememberedPercent; ?>% 100%);">
                  <div class="stats-donut-center"><strong><?php echo number_format($flashcardTotal); ?></strong><span>Bản ghi đánh giá</span></div>
                </div>
                <ul class="stats-legend">
                  <?php foreach ($flashcardGroups as $group): ?>
                    <li><span class="stats-dot <?php echo $group['class']; ?>" aria-hidden="true"></span><div><span><?php echo $group['label']; ?></span><strong><?php echo number_format($group['count']); ?> bản ghi · <?php echo number_format($group['count'] * 100 / $flashcardTotal, 1); ?>%</strong></div></li>
                  <?php endforeach; ?>
                </ul>
              </div>
            <?php endif; ?>
            <p class="stats-note stats-assessment-note">Đánh giá gần nhất trong kỳ, mỗi học viên–từ vựng tính một lần. Theo tự đánh giá của học viên, chưa phản ánh khả năng ghi nhớ lâu dài.</p>
          </section>
          </div>
          <section class="D_Thongkehethong_HopBieuDo stats-section">
            <h2 class="D_Thongkehethong_TieuDeHop">Hiệu quả theo chủ đề hệ thống</h2>
            <p class="stats-note">Sắp xếp theo lượt từ học. Chỉ gồm chủ đề hệ thống. “—” là chưa có Quiz hoàn thành.</p>
            <div class="stats-table-scroll"><table class="D_Thongkehethong_Bang">
              <thead><tr><th scope="col">Chủ đề</th><th scope="col">Phiên Flashcard</th><th scope="col">Lượt luyện từ</th><th scope="col">Quiz hoàn thành</th><th scope="col">Điểm quiz TB</th></tr></thead>
              <tbody>
                <?php if (!$topics): ?><tr><td colspan="5" class="stats-empty">Chưa có hoạt động học theo chủ đề trong kỳ.</td></tr><?php endif; ?>
                <?php foreach ($topics as $topic): ?>
                  <tr><td><strong><?php echo htmlspecialchars($topic['topicName'], ENT_QUOTES, 'UTF-8'); ?></strong></td><td><?php echo number_format((int) $topic['sessions']); ?></td><td><?php echo number_format((int) $topic['words']); ?></td><td><?php echo number_format((int) $topic['completed']); ?></td><td><?php echo $topic['average_score'] === null ? '—' : number_format((float) $topic['average_score'], 1) . '%'; ?></td></tr>
                <?php endforeach; ?>
              </tbody>
            </table></div>
            <?php adminRenderPagination($reportPagination, 'Phân trang thống kê chủ đề', ['days' => $days]); ?>
          </section>
        </main>
      </div>
    </div>
  </body>
</html>
