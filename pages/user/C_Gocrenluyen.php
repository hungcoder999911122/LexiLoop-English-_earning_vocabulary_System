<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once($_SERVER['DOCUMENT_ROOT'] . '/Connect.php');

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? (int) $_SESSION['user_id'] : null;
$collection = $_GET['collection'] ?? '';
$source = $_GET['source'] ?? '';
$sourceId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;

// Dropdown gửi một giá trị gọn dạng "topic:3" hoặc "set:8".
if (preg_match('/^(topic|set):(\d+)$/', $collection, $collectionParts)) {
    $source = $collectionParts[1];
    $sourceId = (int) $collectionParts[2];
}

// Guest chỉ xem bố cục tổng quát, không nhận dữ liệu nguồn học qua URL.
if (!$isLoggedIn) {
    $source = '';
    $sourceId = 0;
}

$limitOption = (string) ($_GET['limit'] ?? '10');
$allowedLimits = ['5', '10', '20', 'all'];
if (!in_array($limitOption, $allowedLimits, true)) {
    $limitOption = '10';
}

$isValidSource = in_array($source, ['topic', 'set'], true) && $sourceId > 0;
$sourceName = '';
$sourceDescription = '';
$wordCount = 0;
$masteredCount = 0;
$latestQuizCorrect = null;
$latestQuizTotal = null;
$flashcardRemembered = 0;
$flashcardStatsTotal = 0;
$sourceError = '';
$personalSets = [];
$topicWords = [];
$topicWordsPerPage = 10;
$wordPage = max(1, filter_var($_GET['word_page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
$topicWordPages = 1;

// Bộ lọc cá nhân chỉ được truy vấn sau khi đăng nhập.
if ($isLoggedIn) {
    $setsStmt = mysqli_prepare($link, 'SELECT id, name FROM vocabulary_sets WHERE user_id = ? ORDER BY name ASC');
    mysqli_stmt_bind_param($setsStmt, 'i', $userId);
    mysqli_stmt_execute($setsStmt);
    $setsResult = mysqli_stmt_get_result($setsStmt);
    while ($set = mysqli_fetch_assoc($setsResult)) {
        $personalSets[] = $set;
    }
    mysqli_stmt_close($setsStmt);
}

if ($isValidSource && $source === 'topic') {
    $stmt = mysqli_prepare($link, '
        SELECT t.topicName, t.topicDescription, COUNT(v.id) AS word_count,
               COUNT(DISTINCT CASE WHEN uvp.status = \'mastered\' THEN v.id END) AS mastered_count
        FROM Topics t
        LEFT JOIN vocabulary v ON v.topic_id = t.topicID
        LEFT JOIN user_vocab_progress uvp ON uvp.vocabulary_id = v.id AND uvp.user_id = ?
        WHERE t.topicID = ?
        GROUP BY t.topicID, t.topicName, t.topicDescription');
    mysqli_stmt_bind_param($stmt, 'ii', $userId, $sourceId);
    mysqli_stmt_execute($stmt);
    $sourceData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($sourceData) {
        $sourceName = $sourceData['topicName'];
        $sourceDescription = $sourceData['topicDescription'] ?? '';
        $wordCount = (int) $sourceData['word_count'];
        $masteredCount = (int) $sourceData['mastered_count'];

        // Topic hệ thống có danh sách riêng tại Góc rèn luyện. Danh sách này
        // không được trộn vào trang quản lý từ thuộc các bộ cá nhân.
        $topicWordPages = max(1, (int) ceil($wordCount / $topicWordsPerPage));
        $wordPage = min($wordPage, $topicWordPages);
        $wordOffset = ($wordPage - 1) * $topicWordsPerPage;
        $wordsStmt = mysqli_prepare($link, '
            SELECT v.id, v.word, v.pronunciation, v.part_of_speech, v.meaning,
                   v.example_sentence, COALESCE(uvp.status, \'new\') AS learning_status
            FROM vocabulary v
            LEFT JOIN user_vocab_progress uvp
              ON uvp.vocabulary_id = v.id AND uvp.user_id = ?
            WHERE v.topic_id = ?
            ORDER BY v.id ASC
            LIMIT ? OFFSET ?');
        mysqli_stmt_bind_param($wordsStmt, 'iiii', $userId, $sourceId, $topicWordsPerPage, $wordOffset);
        mysqli_stmt_execute($wordsStmt);
        $wordsResult = mysqli_stmt_get_result($wordsStmt);
        while ($word = mysqli_fetch_assoc($wordsResult)) {
            $topicWords[] = $word;
        }
        mysqli_stmt_close($wordsStmt);
    } else {
        $sourceError = 'Không tìm thấy chủ đề hệ thống.';
    }
}

if ($isValidSource && $source === 'set') {
    // user_id là điều kiện phân quyền, không chỉ là điều kiện lọc giao diện.
    $stmt = mysqli_prepare($link, '
        SELECT vs.name, vs.description, COUNT(vsi.id) AS word_count,
               COUNT(DISTINCT CASE WHEN uvp.status = \'mastered\' THEN vsi.vocabulary_id END) AS mastered_count
        FROM vocabulary_sets vs
        LEFT JOIN vocabulary_set_items vsi ON vsi.vocabulary_set_id = vs.id
        LEFT JOIN user_vocab_progress uvp ON uvp.vocabulary_id = vsi.vocabulary_id AND uvp.user_id = ?
        WHERE vs.id = ? AND vs.user_id = ?
        GROUP BY vs.id, vs.name, vs.description');
    mysqli_stmt_bind_param($stmt, 'iii', $userId, $sourceId, $userId);
    mysqli_stmt_execute($stmt);
    $sourceData = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
    mysqli_stmt_close($stmt);

    if ($sourceData) {
        $sourceName = $sourceData['name'];
        $sourceDescription = $sourceData['description'] ?? '';
        $wordCount = (int) $sourceData['word_count'];
        $masteredCount = (int) $sourceData['mastered_count'];
    } else {
        $sourceError = 'Không tìm thấy bộ từ hoặc bạn không có quyền truy cập.';
    }
}

$hasSelectedSource = $isValidSource && $sourceError === '';
$selectedCollection = $hasSelectedSource ? $source . ':' . $sourceId : '';
$selectedLimit = $limitOption === 'all' ? $wordCount : min((int) $limitOption, $wordCount);
$canStartLearning = $hasSelectedSource && $wordCount > 0;
$isTopicContext = $hasSelectedSource && $source === 'topic';
$sourceQuery = http_build_query(['source' => $source, 'id' => $sourceId, 'limit' => $limitOption]);

// Điểm Quiz là thống kê độc lập, lấy đúng bài đã nộp gần nhất của nguồn học.
if ($hasSelectedSource) {
    if ($source === 'topic') {
        $latestQuizSql = 'SELECT correct_answers, total_questions
            FROM quiz_results
            WHERE user_id = ? AND topic_id = ?
            ORDER BY finished_at DESC, id DESC LIMIT 1';
    } else {
        $latestQuizSql = 'SELECT correct_answers, total_questions
            FROM quiz_results
            WHERE user_id = ? AND vocabulary_set_id = ?
            ORDER BY finished_at DESC, id DESC LIMIT 1';
    }
    $latestQuizStmt = mysqli_prepare($link, $latestQuizSql);
    mysqli_stmt_bind_param($latestQuizStmt, 'ii', $userId, $sourceId);
    mysqli_stmt_execute($latestQuizStmt);
    $latestQuiz = mysqli_fetch_assoc(mysqli_stmt_get_result($latestQuizStmt));
    mysqli_stmt_close($latestQuizStmt);
    if ($latestQuiz) {
        $latestQuizCorrect = (int) $latestQuiz['correct_answers'];
        $latestQuizTotal = (int) $latestQuiz['total_questions'];
    }
}

// Tiến độ phiên được tách riêng theo activity_type. Trạng thái mastered chỉ
// thể hiện mức độ ghi nhớ từ, không còn được dùng làm tiến độ Flashcard/Quiz.
$modeProgress = [
    'flashcard' => ['percent' => 0, 'label' => 'Chưa bắt đầu'],
    'quiz' => ['percent' => 0, 'label' => 'Chưa bắt đầu']
];

if ($hasSelectedSource) {
    $attemptTableResult = @mysqli_query($link, "SHOW TABLES LIKE 'learning_attempts'");
    if ($attemptTableResult && mysqli_num_rows($attemptTableResult) > 0) {
        $attemptSql = "
            SELECT activity_type, status, state_json
            FROM learning_attempts
            WHERE user_id = ? AND source_type = ? AND source_id = ? AND item_limit = ?
              AND status IN ('in_progress', 'completed')
            ORDER BY updated_at DESC, id DESC
        ";
        $attemptStmt = mysqli_prepare($link, $attemptSql);
        mysqli_stmt_bind_param($attemptStmt, 'isis', $userId, $source, $sourceId, $limitOption);
        mysqli_stmt_execute($attemptStmt);
        $attemptResult = mysqli_stmt_get_result($attemptStmt);
        $resolvedActivities = [];

        while ($attempt = mysqli_fetch_assoc($attemptResult)) {
            $activity = $attempt['activity_type'];
            // Quiz đã nộp được thống kê bằng quiz_results. learning_attempts
            // của Quiz chỉ dùng để nhận biết một phiên thoát giữa chừng.
            if ($activity === 'quiz' && $attempt['status'] !== 'in_progress') {
                continue;
            }
            if (isset($resolvedActivities[$activity]) || !isset($modeProgress[$activity])) {
                continue;
            }
            $resolvedActivities[$activity] = true;

            $state = json_decode($attempt['state_json'], true);
            $state = is_array($state) ? $state : [];
            if ($activity === 'flashcard') {
                $total = count($state['cardIds'] ?? []);
                // Flashcard chỉ tính những từ người dùng xác nhận "Đã nhớ".
                $completed = count(array_filter(
                    $state['cardStatuses'] ?? [],
                    static fn($status) => $status === 'da_nho'
                ));
                $flashcardRemembered = $completed;
                $flashcardStatsTotal = $total;
            } else {
                $total = count($state['questions'] ?? []);
                // Quiz chỉ tính câu đã chọn đúng; câu sai và chưa làm đều chưa
                // đóng góp vào phần trăm hoàn thành.
                $completed = 0;
                foreach (($state['questions'] ?? []) as $index => $question) {
                    $selectedIndex = $state['userAnswers'][(string) $index]
                        ?? $state['userAnswers'][$index]
                        ?? null;
                    if (
                        $selectedIndex !== null
                        && (int) $selectedIndex === (int) ($question['dap_an_dung'] ?? -1)
                    ) {
                        $completed++;
                    }
                }
            }
            $percent = $total > 0 ? min(100, (int) round(($completed / $total) * 100)) : 0;
            $modeProgress[$activity] = [
                'percent' => $percent,
                // Quiz đạt 100% nhưng chưa bấm Nộp bài vẫn là phiên cần tiếp tục.
                'label' => $percent === 100 && $attempt['status'] === 'completed'
                    ? 'Đã hoàn thành'
                    : 'Cần tiếp tục'
            ];
        }
        mysqli_stmt_close($attemptStmt);

        if (!isset($resolvedActivities['quiz']) && $latestQuizTotal > 0) {
            $latestQuizPercent = min(100, (int) round(($latestQuizCorrect / $latestQuizTotal) * 100));
            $modeProgress['quiz'] = [
                'percent' => $latestQuizPercent,
                'label' => 'Kết quả gần nhất'
            ];
        }
    }
}

if ($modeProgress['quiz']['label'] === 'Chưa bắt đầu' && $latestQuizTotal > 0) {
    $latestQuizPercent = min(100, (int) round(($latestQuizCorrect / $latestQuizTotal) * 100));
    $modeProgress['quiz'] = [
        'percent' => $latestQuizPercent,
        'label' => 'Kết quả gần nhất'
    ];
}

// Chưa có phiên Flashcard thì hiển thị 0 trên số thẻ sẽ học. Tuyệt đối không
// lấy user_vocab_progress cho thống kê này vì dữ liệu cũ có thể từng bị Quiz ghi.
if ($flashcardStatsTotal === 0) {
    $flashcardStatsTotal = $selectedLimit;
}
?>
<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Góc rèn luyện - LexiLoop</title>
    <link rel="stylesheet" href="../../CSS/Style.css">
    <link rel="stylesheet" href="../../CSS/topheader.css">
    <link rel="stylesheet" href="../../CSS/C_Gocrenluyen.css">
    <link rel="stylesheet" href="../../CSS/guest-preview.css">
    <link rel="stylesheet" href="../../CSS/responsive.css">
</head>

<body class="C_Gocrenluyen_body">
    <?php
    $headerTitle = 'Góc rèn luyện';
    include '../../includes/topheader.php';
    if ($isLoggedIn) {
        include '../../includes/sidebar_user.php';
    } else {
        include '../../includes/sidebar_guest.php';
    }
    ?>

    <main class="C_Gocrenluyen_main">
        <?php if (!$isLoggedIn): ?>
            <?php
            $guestInviteTitle = 'Khám phá các chế độ rèn luyện';
            $guestInviteMessage = 'Bạn có thể xem trước giao diện Flashcard và Quiz. Hãy đăng nhập để chọn nguồn từ, bắt đầu học và lưu tiến độ.';
            include '../../includes/guest_invite.php';
            ?>
        <?php endif; ?>
        <form method="get" class="C_Gocrenluyen_filters" id="C_Gocrenluyen_filters">
            <?php if ($isTopicContext): ?>
                <!-- Topic đến từ nút Học của hệ thống: không cho đổi sang nguồn khác tại đây. -->
                <input type="hidden" name="source" value="topic">
                <input type="hidden" name="id" value="<?= $sourceId ?>">
                <div class="C_Gocrenluyen_fixedTopic">
                    <span>Chủ đề hệ thống</span>
                    <strong><?= htmlspecialchars($sourceName) ?></strong>
                </div>
            <?php else: ?>
                <!-- Vào từ sidebar/Bộ từ vựng: bộ lọc chỉ chứa bộ của chính người dùng. -->
                <div class="C_Gocrenluyen_filterGroup">
                    <label for="C_Gocrenluyen_collection">Bộ từ vựng cá nhân</label>
                    <select name="collection" id="C_Gocrenluyen_collection">
                        <option value="">Chọn bộ từ cá nhân</option>
                        <?php foreach ($personalSets as $set): ?>
                            <?php $optionValue = 'set:' . (int) $set['id']; ?>
                            <option value="<?= $optionValue ?>" <?= $selectedCollection === $optionValue ? 'selected' : '' ?>>
                                <?= htmlspecialchars($set['name']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            <?php endif; ?>
            <div class="C_Gocrenluyen_filterGroup">
                <label for="C_Gocrenluyen_limit">Số lượng</label>
                <select name="limit" id="C_Gocrenluyen_limit">
                    <option value="5" <?= $limitOption === '5' ? 'selected' : '' ?>>5 từ</option>
                    <option value="10" <?= $limitOption === '10' ? 'selected' : '' ?>>10 từ</option>
                    <option value="20" <?= $limitOption === '20' ? 'selected' : '' ?>>20 từ</option>
                    <option value="all" <?= $limitOption === 'all' ? 'selected' : '' ?>>Tất cả từ</option>
                </select>
            </div>
            <button type="submit" class="C_Gocrenluyen_applyButton" <?= !$isLoggedIn ? 'disabled' : '' ?>>Áp dụng</button>
            <div class="C_Gocrenluyen_readyCount">
                <strong><?= $hasSelectedSource ? $selectedLimit : 0 ?></strong>
                <span>từ sẵn sàng</span>
            </div>
        </form>

        <?php if ($hasSelectedSource): ?>
            <section class="C_Gocrenluyen_sourceCard">
                <a class="C_Gocrenluyen_backLink" href="<?= $source === 'set' ? 'C_Botuvung.php' : '../main/B_DanhSachChuDe.php' ?>" aria-label="Quay lại">←</a>
                <div class="C_Gocrenluyen_sourceInfo">
                    <span class="C_Gocrenluyen_eyebrow"><?= $source === 'set' ? 'BỘ TỪ CÁ NHÂN' : 'CHỦ ĐỀ HỆ THỐNG' ?></span>
                    <h2><?= htmlspecialchars($sourceName) ?></h2>
                    <p><?= htmlspecialchars($sourceDescription ?: 'Sẵn sàng bắt đầu phiên học với nội dung đã chọn.') ?></p>
                    <div class="C_Gocrenluyen_badges">
                        <span><?= $wordCount ?> từ vựng</span>
                        <span><?= $flashcardRemembered ?>/<?= $flashcardStatsTotal ?> đã thuộc qua Flashcard</span>
                        <span>
                            <?= $latestQuizCorrect !== null
                                ? $latestQuizCorrect . '/' . $latestQuizTotal . ' câu đúng Quiz gần nhất'
                                : 'Chưa có kết quả Quiz' ?>
                        </span>
                    </div>
                </div>
            </section>
        <?php endif; ?>

        <section class="C_Gocrenluyen_modes" aria-labelledby="C_Gocrenluyen_modesTitle">
            <div class="C_Gocrenluyen_sectionHeading">
                <div>
                    <span class="C_Gocrenluyen_eyebrow">PHƯƠNG PHÁP HỌC</span>
                    <h2 id="C_Gocrenluyen_modesTitle">Chọn chế độ rèn luyện</h2>
                    <p>Hai phương pháp cốt lõi, dùng chung một nguồn từ vựng bạn vừa chọn.</p>
                </div>
                <span class="C_Gocrenluyen_modeCount">2 chế độ</span>
            </div>

            <?php if (!$hasSelectedSource): ?>
                <div class="C_Gocrenluyen_warning">
                    <?= htmlspecialchars($sourceError ?: 'Hãy chọn một chủ đề hệ thống hoặc bộ từ cá nhân để bắt đầu.') ?>
                </div>
            <?php elseif ($wordCount === 0): ?>
                <div class="C_Gocrenluyen_warning">Nội dung đã chọn chưa có từ vựng. Hãy thêm từ hoặc chọn nội dung khác.</div>
            <?php endif; ?>

            <div class="C_Gocrenluyen_modeGrid">
                <a class="C_Gocrenluyen_modeCard C_Gocrenluyen_modeCard--flashcard <?= !$canStartLearning ? 'is-disabled' : '' ?>"
                    href="<?= $canStartLearning ? 'C_HocFlashcard.php?' . htmlspecialchars($sourceQuery) : '#' ?>"
                    <?= !$canStartLearning ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                    <span class="C_Gocrenluyen_modeIcon" aria-hidden="true">▤</span>
                    <span class="C_Gocrenluyen_modeLabel">GHI NHỚ CHỦ ĐỘNG</span>
                    <h3>Flashcard</h3>
                    <p>Lật thẻ để ghi nhớ từ, nghĩa, phiên âm và ví dụ theo nhịp học riêng.</p>
                    <div class="C_Gocrenluyen_modeProgress">
                        <span><?= htmlspecialchars($modeProgress['flashcard']['label']) ?></span>
                        <strong><?= $modeProgress['flashcard']['percent'] ?>%</strong>
                        <div role="progressbar" aria-label="Tiến độ Flashcard" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $modeProgress['flashcard']['percent'] ?>">
                            <i style="width: <?= $modeProgress['flashcard']['percent'] ?>%"></i>
                        </div>
                    </div>
                    <span class="C_Gocrenluyen_startLink">
                        <?= $modeProgress['flashcard']['label'] === 'Đã hoàn thành' ? 'Học lại từ đầu' : ($modeProgress['flashcard']['label'] === 'Cần tiếp tục' ? 'Tiếp tục học' : 'Bắt đầu học') ?>
                        <b aria-hidden="true">→</b>
                    </span>
                </a>

                <a class="C_Gocrenluyen_modeCard C_Gocrenluyen_modeCard--quiz <?= !$canStartLearning ? 'is-disabled' : '' ?>"
                    href="<?= $canStartLearning ? 'C_Quiz.php?' . htmlspecialchars($sourceQuery) : '#' ?>"
                    <?= !$canStartLearning ? 'aria-disabled="true" tabindex="-1"' : '' ?>>
                    <span class="C_Gocrenluyen_modeIcon" aria-hidden="true">✓</span>
                    <span class="C_Gocrenluyen_modeLabel">KIỂM TRA NHANH</span>
                    <h3>Quiz</h3>
                    <p>Chọn nghĩa đúng để tự kiểm tra khả năng ghi nhớ từ vựng trong nguồn học.</p>
                    <div class="C_Gocrenluyen_modeProgress">
                        <span><?= htmlspecialchars($modeProgress['quiz']['label']) ?></span>
                        <strong><?= $modeProgress['quiz']['percent'] ?>%</strong>
                        <div role="progressbar" aria-label="Tiến độ Quiz" aria-valuemin="0" aria-valuemax="100" aria-valuenow="<?= $modeProgress['quiz']['percent'] ?>">
                            <i style="width: <?= $modeProgress['quiz']['percent'] ?>%"></i>
                        </div>
                    </div>
                    <span class="C_Gocrenluyen_startLink">
                        <?= $modeProgress['quiz']['label'] === 'Đã hoàn thành' ? 'Làm lại Quiz' : ($modeProgress['quiz']['label'] === 'Cần tiếp tục' ? 'Tiếp tục Quiz' : 'Làm Quiz') ?>
                        <b aria-hidden="true">→</b>
                    </span>
                </a>
            </div>
        </section>

        <?php if ($isTopicContext): ?>
            <section class="C_Gocrenluyen_words" aria-labelledby="C_Gocrenluyen_wordsTitle">
                <div class="C_Gocrenluyen_sectionHeading">
                    <div>
                        <span class="C_Gocrenluyen_eyebrow">NỘI DUNG CHỦ ĐỀ</span>
                        <h2 id="C_Gocrenluyen_wordsTitle">Danh sách từ vựng</h2>
                    </div>
                    <span class="C_Gocrenluyen_modeCount"><?= $wordCount ?> từ</span>
                </div>

                <div class="C_Gocrenluyen_tableResponsive">
                    <table class="C_Gocrenluyen_table">
                        <thead>
                            <tr>
                                <th>Từ vựng</th>
                                <th>Nghĩa</th>
                                <th>Loại từ</th>
                                <th>Ví dụ</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topicWords as $word): ?>
                                <?php
                                $statusLabels = ['new' => 'Mới', 'learning' => 'Đang học', 'mastered' => 'Đã thuộc'];
                                $learningStatus = $word['learning_status'];
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($word['word']) ?></strong>
                                        <?php if (!empty($word['pronunciation'])): ?>
                                            <small><?= htmlspecialchars($word['pronunciation']) ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td><?= htmlspecialchars($word['meaning']) ?></td>
                                    <td><span class="C_Gocrenluyen_posTag"><?= htmlspecialchars($word['part_of_speech'] ?: '—') ?></span></td>
                                    <td class="C_Gocrenluyen_example"><?= htmlspecialchars($word['example_sentence'] ?: '—') ?></td>
                                    <td><span class="C_Gocrenluyen_status C_Gocrenluyen_status--<?= htmlspecialchars($learningStatus) ?>"><?= htmlspecialchars($statusLabels[$learningStatus] ?? 'Mới') ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($topicWordPages > 1): ?>
                    <nav class="C_Gocrenluyen_pagination" aria-label="Phân trang từ vựng của chủ đề">
                        <?php for ($pageNumber = 1; $pageNumber <= $topicWordPages; $pageNumber++): ?>
                            <?php $pageQuery = http_build_query(['source' => 'topic', 'id' => $sourceId, 'limit' => $limitOption, 'word_page' => $pageNumber]); ?>
                            <a class="<?= $pageNumber === $wordPage ? 'is-active' : '' ?>" href="?<?= htmlspecialchars($pageQuery) ?>"><?= $pageNumber ?></a>
                        <?php endfor; ?>
                    </nav>
                <?php endif; ?>
            </section>
        <?php endif; ?>
    </main>

    <script src="../../JS/jquery-4.0.0.min.js"></script>
    <script src="../../JS/auth.js"></script>
    <script src="../../JS/C_Gocrenluyen.js"></script>
</body>

</html>
