<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn.']);
    exit;
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/Connect.php');
$userId = (int) $_SESSION['user_id'];
$payload = json_decode(file_get_contents('php://input'), true);

if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu gửi lên không hợp lệ.']);
    exit;
}

$csrf = $payload['csrf'] ?? '';
if (!is_string($csrf) || empty($_SESSION['C_learning_csrf']) || !hash_equals($_SESSION['C_learning_csrf'], $csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token không hợp lệ.']);
    exit;
}

$source = $payload['source'] ?? '';
$sourceId = filter_var($payload['sourceId'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$duration = max(0, min(86400, (int) ($payload['durationSeconds'] ?? 0)));
$isFinal = filter_var($payload['isFinal'] ?? true, FILTER_VALIDATE_BOOLEAN);
$statuses = is_array($payload['statuses'] ?? null) ? $payload['statuses'] : [];

if (!in_array($source, ['topic', 'set', 'review'], true) || ($source !== 'review' && $sourceId <= 0) || !$statuses) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Nguồn học hoặc tiến trình không hợp lệ.']);
    exit;
}

try {
    mysqli_begin_transaction($link);

    // Câu kiểm tra này đồng thời xác nhận từ thuộc đúng nguồn và bộ cá nhân thuộc user.
    if ($source === 'topic') {
        $membershipSql = 'SELECT id FROM vocabulary WHERE id = ? AND topic_id = ? LIMIT 1';
    } elseif ($source === 'set') {
        $membershipSql = 'SELECT v.id FROM vocabulary v
           INNER JOIN vocabulary_set_items vsi ON vsi.vocabulary_id = v.id
           INNER JOIN vocabulary_sets vs ON vs.id = vsi.vocabulary_set_id
           WHERE v.id = ? AND vsi.vocabulary_set_id = ? AND vs.user_id = ? LIMIT 1';
    } else {
        $membershipSql = 'SELECT v.id FROM vocabulary v
            INNER JOIN user_vocab_progress uvp ON uvp.vocabulary_id = v.id
            WHERE v.id = ? AND uvp.user_id = ? AND uvp.next_review_date <= CURDATE() LIMIT 1';
    }
    $membershipStmt = mysqli_prepare($link, $membershipSql);

    $validStatuses = [];
    foreach ($statuses as $vocabularyIdRaw => $answer) {
        $vocabularyId = filter_var($vocabularyIdRaw, FILTER_VALIDATE_INT) ?: 0;
        if ($vocabularyId <= 0 || !in_array($answer, ['da_nho', 'chua_nho'], true)) {
            continue;
        }
        if ($source === 'topic') {
            mysqli_stmt_bind_param($membershipStmt, 'ii', $vocabularyId, $sourceId);
        } elseif ($source === 'review') {
            mysqli_stmt_bind_param($membershipStmt, 'ii', $vocabularyId, $userId);
        } else {
            mysqli_stmt_bind_param($membershipStmt, 'iii', $vocabularyId, $sourceId, $userId);
        }
        mysqli_stmt_execute($membershipStmt);
        if (mysqli_num_rows(mysqli_stmt_get_result($membershipStmt)) === 1) {
            $validStatuses[$vocabularyId] = $answer;
        }
    }
    mysqli_stmt_close($membershipStmt);

    if (!$validStatuses) {
        throw new RuntimeException('Không có từ vựng hợp lệ để lưu.');
    }

    // Chuỗi ngày dựa trên phiên gần nhất của chính người dùng.
    $streak = 1;
    $streakStmt = mysqli_prepare($link, 'SELECT session_date, streak_count FROM learning_sessions WHERE user_id = ? ORDER BY session_date DESC, id DESC LIMIT 1');
    mysqli_stmt_bind_param($streakStmt, 'i', $userId);
    mysqli_stmt_execute($streakStmt);
    $lastSession = mysqli_fetch_assoc(mysqli_stmt_get_result($streakStmt));
    mysqli_stmt_close($streakStmt);
    if ($lastSession) {
        $daysApart = (int) ((strtotime(date('Y-m-d')) - strtotime($lastSession['session_date'])) / 86400);
        if ($daysApart === 0) $streak = max(1, (int) $lastSession['streak_count']);
        if ($daysApart === 1) $streak = max(1, (int) $lastSession['streak_count'] + 1);
    }

    $topicId = $source === 'topic' ? $sourceId : null;
    $setId = $source === 'set' ? $sourceId : null;
    $wordCount = count($validStatuses);
    $sessionType = $source === 'review' ? 'review' : 'new_learning';
    $learningSessionId = null;
    if ($isFinal) {
        // Chỉ phiên đạt điều kiện hoàn tất mới trở thành một bản ghi lịch sử.
        $sessionStmt = mysqli_prepare($link, '
            INSERT INTO learning_sessions
                (user_id, topic_id, vocabulary_set_id, session_type, session_date, words_studied, duration_seconds, streak_count, started_at, finished_at)
            VALUES (?, ?, ?, ?, CURDATE(), ?, ?, ?, DATE_SUB(NOW(), INTERVAL ? SECOND), NOW())');
        mysqli_stmt_bind_param($sessionStmt, 'iiisiiii', $userId, $topicId, $setId, $sessionType, $wordCount, $duration, $streak, $duration);
        mysqli_stmt_execute($sessionStmt);
        $learningSessionId = mysqli_insert_id($link);
        mysqli_stmt_close($sessionStmt);
    }

    // LAST_INSERT_ID(id) trả về id hiện có cả khi ON DUPLICATE KEY UPDATE.
    $repetitionUpdate = $isFinal ? 'repetitions = repetitions + 1' : 'repetitions = repetitions';
    $progressStmt = mysqli_prepare($link, '
        INSERT INTO user_vocab_progress
            (user_id, vocabulary_id, status, interval_days, repetitions, next_review_date, last_reviewed_at, last_quality_rating)
        VALUES (?, ?, ?, ?, 1, DATE_ADD(CURDATE(), INTERVAL ? DAY), NOW(), ?)
        ON DUPLICATE KEY UPDATE
            id = LAST_INSERT_ID(id), status = VALUES(status),
            interval_days = VALUES(interval_days), ' . $repetitionUpdate . ',
            next_review_date = VALUES(next_review_date), last_reviewed_at = NOW(),
            last_quality_rating = VALUES(last_quality_rating)');
    $reviewStmt = $isFinal
        ? mysqli_prepare($link, 'INSERT INTO review_logs (progress_id, review_date, quality_rating, response_time_ms) VALUES (?, CURDATE(), ?, NULL)')
        : null;

    foreach ($validStatuses as $vocabularyId => $answer) {
        $status = $answer === 'da_nho' ? 'mastered' : 'learning';
        $intervalDays = $answer === 'da_nho' ? 7 : 1;
        $quality = $answer === 'da_nho' ? 5 : 2;
        mysqli_stmt_bind_param($progressStmt, 'iisiii', $userId, $vocabularyId, $status, $intervalDays, $intervalDays, $quality);
        mysqli_stmt_execute($progressStmt);
        $progressId = mysqli_insert_id($link);
        if ($reviewStmt) {
            mysqli_stmt_bind_param($reviewStmt, 'ii', $progressId, $quality);
            mysqli_stmt_execute($reviewStmt);
        }
    }
    mysqli_stmt_close($progressStmt);
    if ($reviewStmt) mysqli_stmt_close($reviewStmt);
    mysqli_commit($link);

    echo json_encode(['success' => true, 'learningSessionId' => $learningSessionId]);
} catch (Throwable $error) {
    mysqli_rollback($link);
    error_log('Lỗi lưu Flashcard: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể lưu tiến trình. Hãy kiểm tra migration database.']);
}
