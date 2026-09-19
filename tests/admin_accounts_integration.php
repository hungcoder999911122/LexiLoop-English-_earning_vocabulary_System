<?php
declare(strict_types=1);
// Chỉ chạy trên schema test: procedure tự COMMIT, không thể rollback cả bài test.
if (PHP_SAPI !== 'cli') { http_response_code(404); exit; }
ini_set('session.use_cookies', '0');
ini_set('session.cache_limiter', '');
if (!preg_match('/^lexiloop_admin_test_[a-z0-9_]+$/', getenv('DB_NAME') ?: '')) {
    fwrite(STDERR, "Chỉ được chạy trên DB_NAME=lexiloop_admin_test_...\n"); exit(2);
}
require dirname(__DIR__) . '/Connect.php';
require dirname(__DIR__) . '/includes/database_objects.php';
function verifyAccount(bool $ok, string $message): void {
    if (!$ok) throw new RuntimeException($message);
}
function accountRow(int $id): array {
    global $link;
    return dbSelectView($link, 'SELECT * FROM vw_users WHERE userID = ?', 'i', [$id])[0] ?? [];
}
function expectAccountError(callable $operation, string $text): void {
    try { $operation(); } catch (mysqli_sql_exception $error) {
        verifyAccount(str_contains($error->getMessage(), $text), 'Sai lỗi: ' . $error->getMessage()); return;
    }
    throw new RuntimeException('Không chặn thao tác: ' . $text);
}
if (($argv[1] ?? '') === '--demote') {
    try {
        dbCallProcedure($link, 'CALL sp_admin_change_user_role(?, ?, ?)', 'iis', [(int) $argv[2], (int) $argv[3], 'user']);
        echo 'success';
    } catch (mysqli_sql_exception $error) { echo $error->getMessage(); }
    exit;
}
$server = null;
$sessionId = '';
try {
    $actor = (int) dbSelectView($link, "SELECT userID FROM vw_users WHERE role='admin' AND status='active' ORDER BY userID LIMIT 1")[0]['userID'];
    verifyAccount((int) dbCallProcedure($link, 'CALL sp_admin_account_capabilities()')[0]['ready'] === 1, 'Thiếu procedure');
    $hash = password_hash('TestPass123!', PASSWORD_DEFAULT);
    $created = dbCallProcedure($link, 'CALL sp_admin_create_account(?, ?, ?, ?, ?)', 'issss', [$actor, 'Test Account', 'account-test@example.com', $hash, 'user']);
    $target = (int) $created[0]['user_id'];
    verifyAccount(accountRow($target)['role'] === 'user', 'Sai quyền tạo');
    // View login trả hash để xác minh PHP không lưu mật khẩu rõ.
    $stored = accountRow($target)['password_hash'];
    verifyAccount(password_verify('TestPass123!', $stored), 'Mật khẩu chưa hash đúng');
    expectAccountError(fn() => dbCallProcedure($link, 'CALL sp_admin_create_account(?, ?, ?, ?, ?)', 'issss', [$actor, 'Duplicate', 'account-test@example.com', $hash, 'user']), 'Duplicate entry');
    expectAccountError(fn() => dbCallProcedure($link, 'CALL sp_admin_create_account(?, ?, ?, ?, ?)', 'issss', [$target, 'Forbidden', 'forbidden@example.com', $hash, 'admin']), 'admin required');
    expectAccountError(fn() => dbCallProcedure($link, 'CALL sp_admin_change_user_role(?, ?, ?)', 'iis', [$actor, $actor, 'user']), 'self account change denied');
    expectAccountError(fn() => dbCallProcedure($link, 'CALL sp_admin_change_user_status(?, ?, ?)', 'iis', [$actor, $actor, 'locked']), 'self account change denied');
    expectAccountError(fn() => dbCallProcedure($link, 'CALL sp_admin_delete_account(?, ?)', 'ii', [$actor, $actor]), 'self account change denied');
    expectAccountError(fn() => dbCallProcedure($link, 'CALL sp_admin_change_user_role(?, ?, ?)', 'iis', [$actor, $target, 'owner']), 'invalid role');
    verifyAccount(accountRow($target)['role'] === 'user', 'Rollback không giữ vai trò');
    dbCallProcedure($link, 'CALL sp_admin_change_user_role(?, ?, ?)', 'iis', [$actor, $target, 'admin']);
    verifyAccount(accountRow($target)['role'] === 'admin', 'Không nâng quyền');
    dbCallProcedure($link, 'CALL sp_admin_change_user_role(?, ?, ?)', 'iis', [$actor, $target, 'user']);
    expectAccountError(fn() => dbCallProcedure($link, 'CALL sp_admin_delete_account(?, ?)', 'ii', [$actor, $target]), 'account must be locked');
    dbCallProcedure($link, 'CALL sp_admin_change_user_status(?, ?, ?)', 'iis', [$actor, $target, 'locked']);
    dbCallProcedure($link, 'CALL sp_admin_delete_account(?, ?)', 'ii', [$actor, $target]);
    verifyAccount(accountRow($target) === [], 'Không xóa tài khoản trống');
    $historyUser = dbSelectView($link, 'SELECT user_id FROM vw_learning_sessions WHERE user_id <> ? LIMIT 1', 'i', [$actor])[0]['user_id'] ?? null;
    verifyAccount($historyUser !== null, 'Fixture thiếu người dùng có lịch sử');
    dbCallProcedure($link, 'CALL sp_admin_change_user_status(?, ?, ?)', 'iis', [$actor, $historyUser, 'locked']);
    expectAccountError(fn() => dbCallProcedure($link, 'CALL sp_admin_delete_account(?, ?)', 'ii', [$actor, $historyUser]), 'account has learning data');
    verifyAccount(accountRow((int) $historyUser) !== [], 'Mất tài khoản có lịch sử');
    dbCallProcedure($link, 'CALL sp_admin_change_user_status(?, ?, ?)', 'iis', [$actor, $historyUser, 'active']);
    echo "PASS: tạo/hash, email trùng, quyền, tự thao tác, rollback và bảo toàn lịch sử.\n";

    // Web server riêng kế thừa DB_NAME test, không gửi POST đến Apache/CSDL chính.
    session_start(); $sessionId = session_id();
    $_SESSION = ['user_id' => $actor, 'auth_scope' => 'admin', 'full_name' => 'Test Admin'];
    session_write_close();
    $server = proc_open([PHP_BINARY, '-S', '127.0.0.1:18089', '-t', dirname(__DIR__)], [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/tmp/lexiloop-admin-account-test.log', 'a'], 2 => ['file', '/tmp/lexiloop-admin-account-test.log', 'a']], $pipes);
    verifyAccount(is_resource($server), 'Không mở được server test');
    for ($attempt = 0; $attempt < 30; $attempt++) {
        $socket = @fsockopen('127.0.0.1', 18089);
        if ($socket) { fclose($socket); break; }
        usleep(100000);
    }
    $request = static function (array $post = []) use ($sessionId): array {
        $options = ['method' => $post ? 'POST' : 'GET', 'ignore_errors' => true, 'follow_location' => 0, 'timeout' => 10,
            'header' => 'Cookie: PHPSESSID=' . $sessionId . "\r\n"];
        if ($post) { $options['content'] = http_build_query($post); $options['header'] .= "Content-Type: application/x-www-form-urlencoded\r\n"; }
        $html = file_get_contents('http://127.0.0.1:18089/pages/admin/D_Quanlynguoidung.php?role=admin', false, stream_context_create(['http' => $options]));
        return ['html' => $html, 'headers' => $http_response_header];
    };
    $page = $request();
    verifyAccount(str_contains($page['headers'][0], '200'), 'GET quản lý tài khoản lỗi');
    preg_match('/name="csrf_token" value="([a-f0-9]+)"/', $page['html'], $tokenMatch);
    $token = $tokenMatch[1] ?? '';
    verifyAccount(strlen($token) === 64, 'Thiếu CSRF');
    $payload = ['hanhdong' => 'them', 'full_name' => 'HTTP Test', 'email' => 'http-test@example.com', 'password' => 'TestPass123!', 'password_confirm' => 'TestPass123!', 'new_role' => 'admin'];
    $invalid = $request($payload + ['csrf_token' => 'invalid']);
    verifyAccount(str_contains($invalid['html'], 'Phiên thao tác không hợp lệ'), 'Không chặn CSRF');
    verifyAccount(!dbSelectView($link, 'SELECT userID FROM vw_users WHERE email = ?', 's', [$payload['email']]), 'CSRF vẫn tạo tài khoản');
    $valid = $request($payload + ['csrf_token' => $token]);
    verifyAccount(str_contains($valid['headers'][0], '302'), 'Thiếu chuyển hướng sau POST');
    verifyAccount(count(array_filter($valid['headers'], fn($h) => str_contains($h, 'Location:') && str_contains($h, 'role=admin'))) === 1, 'Mất bộ lọc sau POST');
    $httpTarget = (int) dbSelectView($link, 'SELECT userID FROM vw_users WHERE email = ?', 's', [$payload['email']])[0]['userID'];
    verifyAccount(accountRow($httpTarget)['role'] === 'admin', 'HTTP không tạo admin');
    $change = $request(['hanhdong' => 'doiquyen', 'csrf_token' => $token, 'userID' => $httpTarget, 'new_role' => 'user']);
    verifyAccount(str_contains($change['headers'][0], '302') && accountRow($httpTarget)['role'] === 'user', 'HTTP đổi quyền lỗi');
    $request(['hanhdong' => 'doitrangthai', 'csrf_token' => $token, 'userID' => $httpTarget, 'new_status' => 'locked']);
    $delete = $request(['hanhdong' => 'xoa', 'csrf_token' => $token, 'userID' => $httpTarget]);
    verifyAccount(str_contains($delete['headers'][0], '302') && !accountRow($httpTarget), 'HTTP xóa lỗi');
    echo "PASS: HTTP GET/POST, CSRF, tạo admin, đổi quyền, khóa/xóa, giữ bộ lọc và PRG.\n";

    $otherAdmin = (int) dbCallProcedure($link, 'CALL sp_admin_create_account(?, ?, ?, ?, ?)', 'issss', [$actor, 'Concurrent Admin', 'concurrent@example.com', $hash, 'admin'])[0]['user_id'];
    $workers = [];
    foreach ([[$actor, $otherAdmin], [$otherAdmin, $actor]] as $pair) {
        $child = proc_open([PHP_BINARY, __FILE__, '--demote', (string) $pair[0], (string) $pair[1]], [0 => ['file', '/dev/null', 'r'], 1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $childPipes);
        $workers[] = [$child, $childPipes];
    }
    $results = [];
    foreach ($workers as [$child, $childPipes]) {
        $results[] = stream_get_contents($childPipes[1]); fclose($childPipes[1]);
        $errors = stream_get_contents($childPipes[2]); fclose($childPipes[2]);
        verifyAccount(proc_close($child) === 0, $errors);
    }
    verifyAccount(count(array_filter($results, fn($r) => $r === 'success')) === 1, 'Hai admin đồng thời hạ quyền nhau');
    verifyAccount(count(array_filter($results, fn($r) => str_contains($r, 'admin required'))) === 1, 'Không kiểm tra lại quyền sau chờ khóa');
    verifyAccount(count(array_filter([accountRow($actor), accountRow($otherAdmin)], fn($r) => $r['role'] === 'admin' && $r['status'] === 'active')) === 1, 'Mất cả hai admin');
    echo "PASS: hai kết nối đồng thời hạ quyền nhau vẫn giữ một admin active.\nALL PASS (schema test).\n";
} catch (Throwable $error) {
    fwrite(STDERR, 'FAIL: ' . $error->getMessage() . PHP_EOL); $testExitCode = 1;
} finally {
    if (is_resource($server)) { proc_terminate($server); proc_close($server); }
    if ($sessionId !== '') { session_id($sessionId); session_start(); session_destroy(); }
}
exit($testExitCode ?? 0);
