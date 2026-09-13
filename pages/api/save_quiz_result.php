<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn.']);
    exit;
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/Connect.php');
$userId = (int) $_SESSION['user_id'];
$payload = json_decode(file_get_contents('php://input'), true);
$csrf = is_array($payload) ? ($payload['csrf'] ?? '') : '';

if (!is_string($csrf) || empty($_SESSION['C_learning_csrf']) || !hash_equals($_SESSION['C_learning_csrf'], $csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token không hợp lệ.']);
    exit;
}

$source = $payload['source'] ?? '';
$sourceId = filter_var($payload['sourceId'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$itemLimit = (string) ($payload['limit'] ?? '10');
$answers = is_array($payload['answers'] ?? null) ? $payload['answers'] : [];
$duration = max(0, min(86400, (int) ($payload['durationSeconds'] ?? 0)));

if (!in_array($source, ['topic', 'set', 'review'], true)
    || ($source !== 'review' && $sourceId <= 0)
    || !in_array($itemLimit, ['5', '10', '20', 'all'], true)
    || !$answers) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Kết quả Quiz không hợp lệ.']);
    exit;
}

try {
    mysqli_begin_transaction($link);
    if ($source === 'topic') {
        $wordSql = 'SELECT id, word, meaning FROM vocabulary WHERE id = ? AND topic_id = ? LIMIT 1';
    } elseif ($source === 'set') {
        $wordSql = 'SELECT v.id, v.word, v.meaning FROM vocabulary v
           INNER JOIN vocabulary_set_items vsi ON vsi.vocabulary_id = v.id
           INNER JOIN vocabulary_sets vs ON vs.id = vsi.vocabulary_set_id
           WHERE v.id = ? AND vsi.vocabulary_set_id = ? AND vs.user_id = ? LIMIT 1';
    } else {
        $wordSql = 'SELECT v.id, v.word, v.meaning FROM vocabulary v
            INNER JOIN user_vocab_progress uvp ON uvp.vocabulary_id = v.id
            WHERE v.id = ? AND uvp.user_id = ? AND uvp.next_review_date <= CURDATE() LIMIT 1';
    }
    $wordStmt = mysqli_prepare($link, $wordSql);
    $validatedAnswers = [];
    $correctCount = 0;

    foreach ($answers as $index => $answer) {
        if (!is_array($answer)) continue;
        $vocabularyId = filter_var($answer['vocabularyId'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
        $selectedAnswer = trim((string) ($answer['selectedAnswer'] ?? ''));
        if ($vocabularyId <= 0) continue;

        if ($source === 'topic') {
            mysqli_stmt_bind_param($wordStmt, 'ii', $vocabularyId, $sourceId);
        } elseif ($source === 'set') {
            mysqli_stmt_bind_param($wordStmt, 'iii', $vocabularyId, $sourceId, $userId);
        } else {
            mysqli_stmt_bind_param($wordStmt, 'ii', $vocabularyId, $userId);
        }
        mysqli_stmt_execute($wordStmt);
        $word = mysqli_fetch_assoc(mysqli_stmt_get_result($wordStmt));
        if (!$word) continue;

        $isCorrect = $selectedAnswer !== '' && hash_equals(trim($word['meaning']), $selectedAnswer);
        if ($isCorrect) $correctCount++;
        $validatedAnswers[] = [
            'vocabulary_id' => $vocabularyId,
            'question_order' => count($validatedAnswers) + 1,
            'selected_answer' => $selectedAnswer,
            'correct_answer' => trim($word['meaning']),
            'is_correct' => $isCorrect ? 1 : 0
        ];
    }
    mysqli_stmt_close($wordStmt);

    if (!$validatedAnswers) throw new RuntimeException('Không có câu trả lời hợp lệ.');

    $topicId = $source === 'topic' ? $sourceId : null;
    $setId = $source === 'set' ? $sourceId : null;
    $total = count($validatedAnswers);
    $resultStmt = mysqli_prepare($link, '
        INSERT INTO quiz_results
            (user_id, topic_id, vocabulary_set_id, total_questions, correct_answers, started_at, finished_at)
        VALUES (?, ?, ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? SECOND), NOW())');
    mysqli_stmt_bind_param($resultStmt, 'iiiiii', $userId, $topicId, $setId, $total, $correctCount, $duration);
    mysqli_stmt_execute($resultStmt);
    $quizResultId = mysqli_insert_id($link);
    mysqli_stmt_close($resultStmt);

    $detailStmt = mysqli_prepare($link, '
        INSERT INTO quiz_answer_details
            (quiz_result_id, vocabulary_id, question_order, selected_answer, correct_answer, is_correct)
        VALUES (?, ?, ?, ?, ?, ?)');
    foreach ($validatedAnswers as $answer) {
        mysqli_stmt_bind_param(
            $detailStmt,
            'iiissi',
            $quizResultId,
            $answer['vocabulary_id'],
            $answer['question_order'],
            $answer['selected_answer'],
            $answer['correct_answer'],
            $answer['is_correct']
        );
        mysqli_stmt_execute($detailStmt);
    }
    mysqli_stmt_close($detailStmt);

    // Quiz chỉ lưu kết quả làm bài. Trạng thái "đã thuộc" và lịch ôn tập
    // thuộc trách nhiệm của luồng Flashcard, tránh hai chế độ ghi đè nhau.

    // Ghi kết quả và đóng checkpoint trong cùng transaction. Mọi bài đã bấm
    // Nộp đều kết thúc phiên, kể cả còn câu sai; chỉ nút Thoát giữ in_progress.
    $sourceIdForDb = $source === 'review' ? null : $sourceId;
    $completeAttemptStmt = mysqli_prepare($link, '
        UPDATE learning_attempts
        SET status = \'completed\', completed_at = NOW(), updated_at = NOW()
        WHERE user_id = ? AND activity_type = \'quiz\' AND source_type = ?
          AND source_id <=> ? AND item_limit = ? AND status = \'in_progress\'');
    mysqli_stmt_bind_param($completeAttemptStmt, 'isis', $userId, $source, $sourceIdForDb, $itemLimit);
    mysqli_stmt_execute($completeAttemptStmt);
    mysqli_stmt_close($completeAttemptStmt);

    mysqli_commit($link);

    echo json_encode([
        'success' => true,
        'quizResultId' => $quizResultId,
        'correctCount' => $correctCount,
        'totalQuestions' => $total,
        'isPerfect' => $correctCount === $total
    ]);
} catch (Throwable $error) {
    mysqli_rollback($link);
    error_log('Lỗi lưu Quiz: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể lưu kết quả. Hãy kiểm tra migration database']);
}
