# Backend database và kiểm soát đồng thời

## 1. Mục tiêu và nguyên tắc thiết kế

PHP chỉ thực hiện ba vai trò: xác thực phiên đăng nhập, kiểm tra dữ liệu HTTP và gọi **View/Stored Procedure** bằng prepared statement. Quy tắc SRS, kiểm tra dữ liệu, ghi lịch sử và tính toán tiến độ nằm trong MySQL. Trigger không được gọi trực tiếp; nó chạy tự động khi Stored Procedure ghi dữ liệu.

`data/db_objects.sql` cung cấp:

| Đối tượng | Trách nhiệm |
|---|---|
| `vw_user_progress` | Dữ liệu dashboard/tiến độ đã ghép User - Word - Topic. |
| `vw_daily_vocab_list` | Danh sách từ đến hạn ôn theo từng người dùng. |
| `fn_calculate_retention_rate` | Tỷ lệ câu trả lời đạt (quality >= 3) trong 30 ngày. |
| `fn_get_current_streak` | Số ngày học/ôn liên tục tính đến hôm nay. |
| `trg_validate_review_log` | Chặn quality ngoài 0..5 và thời gian phản hồi âm. |
| `trg_update_last_studied_date` | Đồng bộ lần ôn cuối sang `user_vocab_progress`. |
| `sp_record_study_session` | Ghi một câu trả lời Flashcard theo transaction và cập nhật SRS. |
| `sp_add_new_vocabulary` | Chỉ admin đang active được thêm từ mới. |

`user_vocab_progress(user_id, vocabulary_id)` được đặt unique vì một cặp người dùng - từ vựng chỉ có đúng một trạng thái tiến độ. Đây là ràng buộc dữ liệu, không phải chỉ là kiểm tra ở giao diện.

## 2. Ví dụ PHP PDO: gọi Stored Procedure

Không lấy `user_id` từ JSON/form gửi lên; phải lấy từ session đã xác thực. `CALL` vẫn dùng prepared statement để bind tham số, tránh SQL Injection. PHP không tự viết `INSERT`, `UPDATE` hay quy tắc SRS.

```php
<?php
declare(strict_types=1);

session_start();
require_once __DIR__ . '/../config/pdo.php'; // tạo $pdo, ERRMODE_EXCEPTION

if (empty($_SESSION['user_id'])) {
    http_response_code(401);
    exit('Unauthenticated');
}

$input = json_decode(file_get_contents('php://input'), true, 512, JSON_THROW_ON_ERROR);
$userId = (int) $_SESSION['user_id'];
$topicId = isset($input['topic_id']) ? (int) $input['topic_id'] : null;
$vocabularyId = filter_var($input['vocabulary_id'] ?? null, FILTER_VALIDATE_INT);
$quality = filter_var($input['quality_rating'] ?? null, FILTER_VALIDATE_INT);
$responseMs = isset($input['response_time_ms']) ? filter_var($input['response_time_ms'], FILTER_VALIDATE_INT) : null;
$sessionType = $input['session_type'] ?? 'review';

if (!$vocabularyId || $quality === false || !in_array($sessionType, ['new_learning', 'review'], true)) {
    http_response_code(422);
    exit('Invalid input');
}

try {
    $statement = $pdo->prepare(
        'CALL sp_record_study_session(:user_id, :topic_id, :vocabulary_id, :quality, :response_ms, :session_type)'
    );
    $statement->execute([
        'user_id' => $userId,
        'topic_id' => $topicId,
        'vocabulary_id' => $vocabularyId,
        'quality' => $quality,
        'response_ms' => $responseMs,
        'session_type' => $sessionType,
    ]);
    $statement->closeCursor(); // MySQL may return an extra result set after CALL.
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
} catch (PDOException $exception) {
    // SQLSTATE 40001 / MySQL 1213 is retried in the deadlock policy below.
    http_response_code(422);
    echo json_encode(['ok' => false, 'message' => 'Không thể lưu kết quả học.']);
}
```

Đọc dữ liệu cũng đi qua view, đồng thời phải giới hạn theo ID từ session:

```php
$stmt = $pdo->prepare(
    'SELECT * FROM vw_daily_vocab_list WHERE user_id = :user_id ORDER BY next_review_date, vocabulary_id LIMIT 20'
);
$stmt->execute(['user_id' => (int) $_SESSION['user_id']]);
```

> Phân quyền PHP vẫn bắt buộc. View không thể biết người gọi HTTP là ai; không bao giờ tin `user_id` do trình duyệt gửi lên.

## 3. Nền tảng học thuật

Một transaction phải bảo đảm **ACID**: Atomicity (toàn bộ hoặc không gì), Consistency (giữ ràng buộc), Isolation (các transaction đồng thời không thấy trạng thái trung gian sai), Durability (đã commit thì không mất). InnoDB mặc định dùng `REPEATABLE READ`, MVCC cho đọc nhất quán và khóa hàng/index cho ghi. Isolation càng cao càng tránh nhiều anomaly nhưng thường giảm mức song song.

`SELECT ... FOR UPDATE` là locking read: lấy exclusive lock trên hàng đã đọc và giữ đến `COMMIT`/`ROLLBACK`. Đây là biểu hiện thực tế của **strict two-phase locking (strict 2PL)**: sau khi bắt đầu ghi, transaction không nhả khóa trước khi kết thúc. Nó tránh Lost Update và tránh cascading rollback do dữ liệu chưa commit.

## 4. Năm tình huống đồng thời

### 4.1 Lost Update — Mất dữ liệu cập nhật

**UI:** Anh mở Flashcard ở điện thoại và laptop. Cả hai cùng thấy `repetitions = 2`. Điện thoại trả lời đúng nên UI báo 3 lần ôn; laptop cũng trả lời đúng. Sau cùng dashboard vẫn chỉ là 3 thay vì 4: một lần ôn biến mất.

**Mã lỗi (read-modify-write tách rời):**

```sql
-- T1 (điện thoại)                         -- T2 (laptop)
BEGIN;                                      BEGIN;
SELECT repetitions FROM user_vocab_progress
 WHERE id = 10; -- 2
                                             SELECT repetitions FROM user_vocab_progress
                                              WHERE id = 10; -- 2
UPDATE user_vocab_progress SET repetitions = 3
 WHERE id = 10; COMMIT;
                                             UPDATE user_vocab_progress SET repetitions = 3
                                              WHERE id = 10; COMMIT;
```

**Cách sửa:** một cách tốt là dùng `sp_record_study_session`: khóa user rồi khóa progress bằng `FOR UPDATE`, tính từ giá trị mới nhất và commit cả progress + review log + session. T2 sẽ chờ T1; khi T2 được chạy, nó đọc 3 và ghi 4. Với bộ đếm đơn giản có thể dùng một câu atomic `SET repetitions = repetitions + 1`; nhưng SRS cần nhiều cột phụ thuộc nhau nên transaction + `FOR UPDATE` dễ bảo trì và đúng hơn.

```sql
START TRANSACTION;
SELECT id, repetitions, interval_days, ease_factor
FROM user_vocab_progress
WHERE user_id = :user_id AND vocabulary_id = :vocabulary_id
FOR UPDATE;
-- tính SRS, UPDATE, INSERT review_logs
COMMIT;
```

### 4.2 Dirty Read — Đọc dữ liệu rác

**UI:** Người học mở dashboard thấy streak 12 và trạng thái "Đã thuộc" ngay khi phiên học đang lưu. Một lỗi mạng khiến transaction bị rollback. Refresh lại, streak/quá trình trở về cũ. Lần hiển thị đầu đã đọc dữ liệu chưa được xác nhận.

**Mã lỗi:** Dirty Read chỉ xảy ra nếu hạ isolation xuống `READ UNCOMMITTED`.

```sql
-- T1: lưu phiên, chưa commit
SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;
BEGIN;
UPDATE user_vocab_progress SET status = 'mastered', repetitions = 5 WHERE id = 10;

-- T2: dashboard đọc bản ghi chưa commit của T1
SET TRANSACTION ISOLATION LEVEL READ UNCOMMITTED;
SELECT status, repetitions FROM user_vocab_progress WHERE id = 10; -- mastered, 5

-- T1 gặp lỗi
ROLLBACK;
```

**Cách sửa:** không dùng `READ UNCOMMITTED` cho dữ liệu học. Dùng tối thiểu `READ COMMITTED` (hoặc InnoDB mặc định `REPEATABLE READ`) để T2 chỉ thấy committed version. Quy tắc strict 2PL của procedure cũng giữ write lock đến commit/rollback.

```sql
SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
START TRANSACTION;
SELECT status, repetitions FROM vw_user_progress WHERE progress_id = :progress_id;
COMMIT;
```

### 4.3 Non-repeatable Read — Không đọc lại được

**UI:** Anh mở trang chi tiết tiến độ, ban đầu thấy `next_review_date = 2026-09-15`. Trong lúc trang tính số từ cần ôn, một thiết bị khác hoàn tất Flashcard và đổi ngày thành `2026-09-18`. Cùng một request đọc lại lại thấy dữ liệu khác, khiến tổng và danh sách hiển thị lệch nhau.

**Mã lỗi:**

```sql
-- T1 dashboard
SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
BEGIN;
SELECT next_review_date FROM user_vocab_progress WHERE id = 10; -- 2026-09-15

-- T2 hoàn tất học
BEGIN;
UPDATE user_vocab_progress SET next_review_date = '2026-09-18' WHERE id = 10;
COMMIT;

-- T1 đọc cùng hàng lần hai
SELECT next_review_date FROM user_vocab_progress WHERE id = 10; -- 2026-09-18
COMMIT;
```

**Cách sửa:** nếu cả màn hình cần một snapshot nhất quán, dùng `REPEATABLE READ` read-only transaction (InnoDB/MVCC cho hai lần đọc cùng committed snapshot). Nếu T1 chuẩn bị sửa bản ghi, dùng `FOR UPDATE` để khóa trước khi quyết định.

```sql
SET TRANSACTION ISOLATION LEVEL REPEATABLE READ;
START TRANSACTION READ ONLY;
SELECT * FROM vw_user_progress WHERE progress_id = :progress_id;
-- Các SELECT tiếp theo trong T1 đọc cùng snapshot.
COMMIT;
```

### 4.4 Phantom Read — Bóng ma

**UI:** Anh vào "Từ đến hạn hôm nay"; badge báo 10 từ. Khi bấm "Bắt đầu", danh sách lại có 11 từ dù không ai sửa 10 dòng cũ: một từ mới vừa được tạo progress và đến hạn hôm nay, tức là một hàng *phantom* xuất hiện trong kết quả theo điều kiện.

**Mã lỗi:**

```sql
-- T1 tạo danh sách theo predicate
SET TRANSACTION ISOLATION LEVEL READ COMMITTED;
BEGIN;
SELECT COUNT(*) FROM user_vocab_progress
WHERE user_id = 7 AND next_review_date <= CURRENT_DATE; -- 10

-- T2 thêm một hàng thỏa predicate
INSERT INTO user_vocab_progress(user_id, vocabulary_id, next_review_date)
VALUES (7, 500, CURRENT_DATE);
COMMIT;

-- T1 query lại theo cùng predicate
SELECT COUNT(*) FROM user_vocab_progress
WHERE user_id = 7 AND next_review_date <= CURRENT_DATE; -- 11 (phantom)
COMMIT;
```

**Cách sửa:** cho báo cáo/danh sách chỉ đọc, `REPEATABLE READ` cung cấp consistent snapshot. Nếu transaction cần quyết định ghi dựa trên toàn tập "từ đến hạn", dùng `SERIALIZABLE` hoặc locking read `FOR UPDATE` trên predicate có index phù hợp (`user_id, next_review_date`). InnoDB sẽ dùng next-key/gap locks để ngăn insert vào phạm vi điều kiện đến khi T1 kết thúc. Không nâng `SERIALIZABLE` cho mọi request vì nó làm giảm concurrency.

```sql
SET TRANSACTION ISOLATION LEVEL SERIALIZABLE;
START TRANSACTION;
SELECT id FROM user_vocab_progress
WHERE user_id = 7 AND next_review_date <= CURRENT_DATE
FOR UPDATE;
-- quyết định/ghi dựa trên tập này
COMMIT;
```

### 4.5 Deadlock — Khóa chết

**UI:** Anh nhấn "Nộp quiz" gần đồng thời ở hai tab. Một request báo "Đang lưu, vui lòng thử lại" thay vì treo vô hạn; dữ liệu không bị nửa chừng. Đây là hành vi đúng khi DB phát hiện deadlock và chọn một nạn nhân để rollback.

**Mã lỗi:** T1 và T2 đều giữ một lock rồi yêu cầu lock mà transaction còn lại đang giữ; tạo vòng chờ `T1 -> T2 -> T1`.

```sql
-- T1                                        -- T2
BEGIN;
SELECT * FROM user_vocab_progress
 WHERE id = 10 FOR UPDATE;                   BEGIN;
                                             SELECT * FROM user_vocab_progress
                                              WHERE id = 11 FOR UPDATE;
UPDATE user_vocab_progress SET repetitions = repetitions + 1
 WHERE id = 11; -- waits for T2
                                             UPDATE user_vocab_progress SET repetitions = repetitions + 1
                                              WHERE id = 10; -- waits for T1
-- InnoDB detects cycle; rolls back one transaction with error 1213 / SQLSTATE 40001.
```

**Cách sửa trong DB:** mọi procedure phải lấy lock cùng thứ tự xác định, ví dụ `ORDER BY vocabulary_id ASC`, và transaction càng ngắn càng tốt. `sp_record_study_session` chỉ cập nhật một progress row; việc khóa `Users` trước rồi `user_vocab_progress` tạo thứ tự nhất quán cho cùng một user. MySQL/InnoDB là cơ chế wait-for graph/deadlock detection, không phải tự động Wait-Die/Wound-Wait. Wait-Die/Wound-Wait là timestamp protocols trong lý thuyết; có thể trình bày như phương án DBMS khác, nhưng không nên tuyên bố MySQL đang dùng chúng.

**Cách sửa trong PHP:** deadlock vẫn có thể xảy ra khi các luồng nghiệp vụ phức tạp hơn. Chỉ retry toàn bộ transaction khi lỗi retryable (`1213` hoặc SQLSTATE `40001`), với exponential backoff nhỏ; không retry lỗi validation/business rule.

```php
function callWithDeadlockRetry(PDO $pdo, array $arguments): void {
    for ($attempt = 0; $attempt < 3; $attempt++) {
        try {
            $stmt = $pdo->prepare('CALL sp_record_study_session(?, ?, ?, ?, ?, ?)');
            $stmt->execute($arguments);
            $stmt->closeCursor();
            return;
        } catch (PDOException $e) {
            $mysqlCode = (int) ($e->errorInfo[1] ?? 0);
            $isDeadlock = $mysqlCode === 1213 || $e->getCode() === '40001';
            if (!$isDeadlock || $attempt === 2) {
                throw $e;
            }
            usleep((50 * (2 ** $attempt) + random_int(0, 50)) * 1000);
        }
    }
}
```

## 5. Kiểm thử đề xuất

Mở hai MySQL sessions riêng và chạy từng timeline theo thứ tự T1/T2 ở trên. Sau mỗi case, xác nhận dữ liệu bằng view và `review_logs`; kiểm tra transaction bị rollback không để lại tiến độ/session dở dang. Trước khi import `db_objects.sql`, backup database. Vì script thêm unique key, cần chạy sau khi bảo đảm không có dữ liệu trùng `(user_id, vocabulary_id)`.

## TÓM TẮT

- PHP gọi View/Stored Procedure bằng prepared statement; không nhúng DML vào trang giao diện.
- Stored Procedure tạo transaction nguyên tử; View phục vụ đọc; Function tính chỉ số; Trigger bảo vệ và đồng bộ dữ liệu.
- Isolation và lock được chọn theo mục đích: `READ COMMITTED` chống dirty read, `REPEATABLE READ` giữ snapshot, `SERIALIZABLE`/range locking khi cần chống phantom.

## NHẬN ĐỊNH

- Không coi `SELECT ... FOR UPDATE` là giải pháp cho mọi truy vấn: dùng khi transaction sẽ ghi hoặc cần bảo vệ một invariant; nếu lạm dụng sẽ giảm hiệu năng.
- Isolation không thay thế validation, foreign key, unique key hay authorization server-side.
- Bước tiếp theo: import script vào một database test, chạy 5 kịch bản bằng hai session, rồi thay từng endpoint PHP bằng `CALL`/view theo thứ tự ưu tiên của luồng Flashcard và Quiz.
