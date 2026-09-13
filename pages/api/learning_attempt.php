<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn.']);
    exit;
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/Connect.php');

$userId = (int) $_SESSION['user_id'];
$payload = json_decode(file_get_contents('php://input'), true);
if (!is_array($payload)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Dữ liệu phiên học không hợp lệ.']);
    exit;
}

$csrf = $payload['csrf'] ?? '';
if (!is_string($csrf) || empty($_SESSION['C_learning_csrf']) || !hash_equals($_SESSION['C_learning_csrf'], $csrf)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'CSRF token không hợp lệ.']);
    exit;
}

$action = $payload['action'] ?? '';
$activity = $payload['activity'] ?? '';
$source = $payload['source'] ?? '';
$sourceId = filter_var($payload['sourceId'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$itemLimit = (string) ($payload['limit'] ?? '10');
$state = $payload['state'] ?? null;

if (!in_array($action, ['load', 'save', 'complete', 'restart'], true)
    || !in_array($activity, ['flashcard', 'quiz'], true)
    || !in_array($source, ['topic', 'set', 'review'], true)
    || ($source !== 'review' && $sourceId <= 0)
    || !in_array($itemLimit, ['5', '10', '20', 'all'], true)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Thông tin phiên học không hợp lệ.']);
    exit;
}

try {
    if ($source === 'topic') {
        $topicStmt = mysqli_prepare($link, 'SELECT topicID FROM Topics WHERE topicID = ? LIMIT 1');
        mysqli_stmt_bind_param($topicStmt, 'i', $sourceId);
        mysqli_stmt_execute($topicStmt);
        $topicExists = mysqli_num_rows(mysqli_stmt_get_result($topicStmt)) === 1;
        mysqli_stmt_close($topicStmt);
        if (!$topicExists) {
            http_response_code(404);
            echo json_encode(['success' => false, 'message' => 'Không tìm thấy chủ đề học.']);
            exit;
        }
    }

    // Không cho dùng ID của bộ từ thuộc tài khoản khác.
    if ($source === 'set') {
        $ownerStmt = mysqli_prepare($link, 'SELECT id FROM vocabulary_sets WHERE id = ? AND user_id = ? LIMIT 1');
        mysqli_stmt_bind_param($ownerStmt, 'ii', $sourceId, $userId);
        mysqli_stmt_execute($ownerStmt);
        $isOwner = mysqli_num_rows(mysqli_stmt_get_result($ownerStmt)) === 1;
        mysqli_stmt_close($ownerStmt);
        if (!$isOwner) {
            http_response_code(403);
            echo json_encode(['success' => false, 'message' => 'Bạn không có quyền truy cập bộ từ này.']);
            exit;
        }
    }

    $sourceIdForDb = $source === 'review' ? null : $sourceId;
    $findSql = 'SELECT id, state_json, started_at, updated_at
        FROM learning_attempts
        WHERE user_id = ? AND activity_type = ? AND source_type = ?
          AND source_id <=> ? AND item_limit = ? AND status = \'in_progress\'
        ORDER BY updated_at DESC, id DESC LIMIT 1';
    $findStmt = mysqli_prepare($link, $findSql);
    mysqli_stmt_bind_param($findStmt, 'issis', $userId, $activity, $source, $sourceIdForDb, $itemLimit);
    mysqli_stmt_execute($findStmt);
    $attempt = mysqli_fetch_assoc(mysqli_stmt_get_result($findStmt));
    mysqli_stmt_close($findStmt);

    if ($action === 'load') {
        echo json_encode([
            'success' => true,
            'attempt' => $attempt ? [
                'id' => (int) $attempt['id'],
                'state' => json_decode($attempt['state_json'], true),
                'startedAt' => $attempt['started_at'],
                'updatedAt' => $attempt['updated_at']
            ] : null
        ]);
        exit;
    }

    if ($action === 'restart') {
        if ($attempt) {
            $attemptId = (int) $attempt['id'];
            $stmt = mysqli_prepare($link, "UPDATE learning_attempts SET status = 'abandoned' WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt, 'ii', $attemptId, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if ($action === 'complete') {
        if ($attempt) {
            $attemptId = (int) $attempt['id'];
            $stmt = mysqli_prepare($link, "UPDATE learning_attempts SET status = 'completed', completed_at = NOW() WHERE id = ? AND user_id = ?");
            mysqli_stmt_bind_param($stmt, 'ii', $attemptId, $userId);
            mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
        }
        echo json_encode(['success' => true]);
        exit;
    }

    if (!is_array($state)) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Trạng thái cần lưu không hợp lệ.']);
        exit;
    }
    $stateJson = json_encode($state, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($stateJson === false || strlen($stateJson) > 1000000) {
        http_response_code(413);
        echo json_encode(['success' => false, 'message' => 'Dữ liệu phiên học vượt quá giới hạn.']);
        exit;
    }

    if ($attempt) {
        $attemptId = (int) $attempt['id'];
        $stmt = mysqli_prepare($link, 'UPDATE learning_attempts SET state_json = ?, updated_at = NOW() WHERE id = ? AND user_id = ?');
        mysqli_stmt_bind_param($stmt, 'sii', $stateJson, $attemptId, $userId);
    } else {
        $stmt = mysqli_prepare($link, 'INSERT INTO learning_attempts
            (user_id, activity_type, source_type, source_id, item_limit, state_json)
            VALUES (?, ?, ?, ?, ?, ?)');
        mysqli_stmt_bind_param($stmt, 'ississ', $userId, $activity, $source, $sourceIdForDb, $itemLimit, $stateJson);
    }
    mysqli_stmt_execute($stmt);
    $attemptId = $attempt ? (int) $attempt['id'] : mysqli_insert_id($link);
    mysqli_stmt_close($stmt);
    echo json_encode(['success' => true, 'attemptId' => $attemptId]);
} catch (Throwable $error) {
    error_log('Lỗi phiên học: ' . $error->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Không thể lưu phiên học. Hãy chạy migration learning_attempts.']);
}
