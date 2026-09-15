<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once($_SERVER['DOCUMENT_ROOT'] . '/Connect.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/database_objects.php');

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? (int) $_SESSION['user_id'] : null;
$collection = $_GET['collection'] ?? '';
$source = $_GET['source'] ?? '';
$sourceId = filter_var($_GET['id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
if (preg_match('/^(topic|set):(\d+)$/', $collection, $parts)) {
    $source = $parts[1];
    $sourceId = (int) $parts[2];
}
if (!$isLoggedIn) {
    $source = '';
    $sourceId = 0;
}

$limitOption = (string) ($_GET['limit'] ?? '10');
if (!in_array($limitOption, ['5', '10', '20', 'all'], true)) {
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
$modeProgress = [
    'flashcard' => ['percent' => 0, 'label' => 'Chưa bắt đầu'],
    'quiz' => ['percent' => 0, 'label' => 'Chưa bắt đầu'],
];

try {
    if ($isLoggedIn) {
        $setRows = dbSelectView(
            $link,
            'SELECT source_id AS id, source_name AS name
             FROM vw_learning_sources
             WHERE source_type = ? AND owner_user_id = ?
             ORDER BY source_name',
            'si',
            ['set', $userId]
        );
        $personalSets = $setRows;
    }

    $sourceItems = [];
    if ($isValidSource) {
        $sourceSql = $source === 'set'
            ? 'SELECT * FROM vw_learning_sources WHERE source_type = ? AND source_id = ? AND owner_user_id = ? LIMIT 1'
            : 'SELECT * FROM vw_learning_sources WHERE source_type = ? AND source_id = ? LIMIT 1';
        $sourceRows = dbSelectView(
            $link,
            $sourceSql,
            $source === 'set' ? 'sii' : 'si',
            $source === 'set' ? [$source, $sourceId, $userId] : [$source, $sourceId]
        );
        if (!$sourceRows) {
            $sourceError = $source === 'set'
                ? 'Không tìm thấy bộ từ hoặc bạn không có quyền truy cập.'
                : 'Không tìm thấy chủ đề hệ thống.';
        } else {
            $sourceName = $sourceRows[0]['source_name'];
            $sourceDescription = $sourceRows[0]['source_description'] ?? '';
            $itemSql = $source === 'set'
                ? 'SELECT * FROM vw_learning_items WHERE source_type = ? AND source_id = ? AND owner_user_id = ? ORDER BY display_order, vocabulary_id'
                : 'SELECT * FROM vw_learning_items WHERE source_type = ? AND source_id = ? ORDER BY display_order, vocabulary_id';
            $sourceItems = dbSelectView(
                $link,
                $itemSql,
                $source === 'set' ? 'sii' : 'si',
                $source === 'set' ? [$source, $sourceId, $userId] : [$source, $sourceId]
            );
            $wordCount = count($sourceItems);

            $progressRows = dbSelectView(
                $link,
                'SELECT vocabulary_id, status FROM vw_user_progress WHERE user_id = ?',
                'i',
                [$userId]
            );
            $progressByVocabulary = [];
            foreach ($progressRows as $progress) {
                $progressByVocabulary[(int) $progress['vocabulary_id']] = $progress['status'];
            }
            foreach ($sourceItems as &$item) {
                $item['learning_status'] = $progressByVocabulary[(int) $item['vocabulary_id']] ?? 'new';
                if ($item['learning_status'] === 'mastered') {
                    $masteredCount++;
                }
                $item['id'] = (int) $item['vocabulary_id'];
            }
            unset($item);

            if ($source === 'topic') {
                $topicWordPages = max(1, (int) ceil($wordCount / $topicWordsPerPage));
                $wordPage = min($wordPage, $topicWordPages);
                $topicWords = array_slice(
                    $sourceItems,
                    ($wordPage - 1) * $topicWordsPerPage,
                    $topicWordsPerPage
                );
            }
        }
    }

    if ($isValidSource && $sourceError === '') {
        $latestSql = $source === 'topic'
            ? 'SELECT correct_answers, total_questions FROM vw_quiz_result_summary
               WHERE user_id = ? AND topic_id = ?
               ORDER BY finished_at DESC, quiz_result_id DESC LIMIT 1'
            : 'SELECT correct_answers, total_questions FROM vw_quiz_result_summary
               WHERE user_id = ? AND vocabulary_set_id = ?
               ORDER BY finished_at DESC, quiz_result_id DESC LIMIT 1';
        $latestRows = dbSelectView($link, $latestSql, 'ii', [$userId, $sourceId]);
        if ($latestRows) {
            $latestQuizCorrect = (int) $latestRows[0]['correct_answers'];
            $latestQuizTotal = (int) $latestRows[0]['total_questions'];
        }

        $attemptRows = dbSelectView(
            $link,
            'SELECT activity_type, status, state_json
             FROM vw_learning_attempt_status
             WHERE user_id = ? AND source_type = ? AND source_id = ? AND item_limit = ?
               AND status IN (\'in_progress\', \'completed\')
             ORDER BY updated_at DESC, id DESC',
            'isis',
            [$userId, $source, $sourceId, $limitOption]
        );
        $resolvedActivities = [];
        foreach ($attemptRows as $attempt) {
            $activity = $attempt['activity_type'];
            if (($activity === 'quiz' && $attempt['status'] !== 'in_progress')
                || isset($resolvedActivities[$activity])
                || !isset($modeProgress[$activity])) {
                continue;
            }
            $resolvedActivities[$activity] = true;
            $state = json_decode($attempt['state_json'], true);
            $state = is_array($state) ? $state : [];
            if ($activity === 'flashcard') {
                $total = count($state['cardIds'] ?? []);
                $completed = count(array_filter(
                    $state['cardStatuses'] ?? [],
                    static fn($status): bool => $status === 'da_nho'
                ));
                $flashcardRemembered = $completed;
                $flashcardStatsTotal = $total;
            } else {
                $total = count($state['questions'] ?? []);
                $completed = 0;
                foreach (($state['questions'] ?? []) as $index => $question) {
                    $selectedIndex = $state['userAnswers'][(string) $index]
                        ?? $state['userAnswers'][$index] ?? null;
                    if ($selectedIndex !== null
                        && (int) $selectedIndex === (int) ($question['dap_an_dung'] ?? -1)) {
                        $completed++;
                    }
                }
            }
            $percent = $total > 0 ? min(100, (int) round($completed * 100 / $total)) : 0;
            $modeProgress[$activity] = ['percent' => $percent, 'label' => 'Cần tiếp tục'];
        }
    }
} catch (Throwable $error) {
    error_log('Lỗi Góc rèn luyện: ' . $error->getMessage());
    $sourceError = 'Không thể tải dữ liệu học tập lúc này.';
}

$hasSelectedSource = $isValidSource && $sourceError === '';
$selectedCollection = $hasSelectedSource ? $source . ':' . $sourceId : '';
$selectedLimit = $limitOption === 'all' ? $wordCount : min((int) $limitOption, $wordCount);
$canStartLearning = $hasSelectedSource && $wordCount > 0;
$isTopicContext = $hasSelectedSource && $source === 'topic';
$sourceQuery = http_build_query(['source' => $source, 'id' => $sourceId, 'limit' => $limitOption]);

if ($modeProgress['quiz']['label'] === 'Chưa bắt đầu' && $latestQuizTotal > 0) {
    $modeProgress['quiz'] = [
        'percent' => min(100, (int) round($latestQuizCorrect * 100 / $latestQuizTotal)),
        'label' => 'Kết quả gần nhất',
    ];
}
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
