<?php
header('Content-Type: application/json; charset=utf-8');

if (session_status() === PHP_SESSION_NONE) session_start();
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Phiên đăng nhập đã hết hạn.']);
    exit;
}

require_once($_SERVER['DOCUMENT_ROOT'] . '/Connect.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/database_objects.php');
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
    $validatedAnswers = [];
    foreach ($answers as $answer) {
        if (!is_array($answer)) {
            throw new InvalidArgumentException('Câu trả lời Quiz không hợp lệ.');
        }
        $vocabularyId = filter_var($answer['vocabularyId'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
        $selectedAnswer = trim((string) ($answer['selectedAnswer'] ?? ''));
        if ($vocabularyId <= 0) {
            throw new InvalidArgumentException('ID từ vựng không hợp lệ.');
        }
        $validatedAnswers[] = [
            'vocabularyId' => $vocabularyId,
            'selectedAnswer' => $selectedAnswer,
            'responseTimeMs' => isset($answer['responseTimeMs']) ? max(0, (int) $answer['responseTimeMs']) : null,
        ];
    }
    $answersJson = json_encode($validatedAnswers, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
    $sourceIdForDb = $source === 'review' ? null : $sourceId;
    $rows = dbCallProcedure(
        $link,
        'CALL sp_submit_quiz(?, ?, ?, ?, ?, ?)',
        'isissi',
        [$userId, $source, $sourceIdForDb, $itemLimit, $answersJson, $duration]
    );
    $result = $rows[0] ?? [];
    $quizResultId = (int) ($result['quiz_result_id'] ?? 0);
    $correctCount = (int) ($result['correct_count'] ?? 0);
    $total = (int) ($result['total_questions'] ?? 0);

    echo json_encode([
        'success' => true,
        'quizResultId' => $quizResultId,
        'correctCount' => $correctCount,
        'totalQuestions' => $total,
        'isPerfect' => $correctCount === $total
    ]);
} catch (Throwable $error) {
    error_log('Lỗi lưu Quiz: ' . $error->getMessage());
    http_response_code($error instanceof InvalidArgumentException ? 422 : 500);
    echo json_encode(['success' => false, 'message' => 'Không thể lưu kết quả Quiz.']);
}
