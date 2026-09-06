-- =============================================================================
-- LexiLoop — File 2/3: VIEW + STORED PROCEDURE + FUNCTION + TRIGGER
-- Chạy SAU 01_schema.sql
-- =============================================================================
USE db_LexiLoop_core;

-- =============================================================================
-- VIEWS (báo cáo / truy vấn phức tạp cho Dashboard & điểm HQTCSDL)
-- =============================================================================

-- Từ đầy đủ: chủ đề + IPA + nghĩa + từ loại
CREATE OR REPLACE VIEW v_vocabulary_detail AS
SELECT
    v.vocabularyID,
    v.word,
    v.pronunciation,
    v.audio_url,
    v.difficulty,
    t.topicID,
    t.topicName,
    t.cefr_level,
    s.vocabularySenseID,
    p.pos_code,
    p.partOfSpeechName,
    s.meaning_vi,
    s.meaning_en
FROM Vocabulary v
JOIN Topics t ON t.topicID = v.topicID
JOIN Vocabulary_Senses s ON s.vocabularyID = v.vocabularyID
JOIN Part_of_Speech p ON p.partOfSpeechID = s.partOfSpeechID;

-- Thẻ đến hạn ôn hôm nay (Anki: due cards)
CREATE OR REPLACE VIEW v_due_reviews AS
SELECT
    p.progressID,
    p.userID,
    u.userName,
    p.vocabularyID,
    v.word,
    v.pronunciation,
    t.topicName,
    p.mastery_level,
    p.interval_days,
    p.ease_factor,
    p.next_review_date,
    p.repetitions,
    (
        SELECT s.meaning_vi
        FROM Vocabulary_Senses s
        WHERE s.vocabularyID = v.vocabularyID
        LIMIT 1
    ) AS meaning_vi
FROM User_Vocab_Progress p
JOIN Users u ON u.userID = p.userID
JOIN Vocabulary v ON v.vocabularyID = p.vocabularyID
JOIN Topics t ON t.topicID = v.topicID
WHERE p.next_review_date IS NOT NULL
  AND p.next_review_date <= CURDATE();

-- Thống kê học tập theo người dùng
CREATE OR REPLACE VIEW v_user_learning_stats AS
SELECT
    u.userID,
    u.userName,
    u.full_name,
    COUNT(DISTINCT p.vocabularyID) AS words_started,
    SUM(p.mastery_level = 'MASTERED') AS words_mastered,
    SUM(p.mastery_level = 'LEARNING') AS words_learning,
    SUM(p.next_review_date IS NOT NULL AND p.next_review_date <= CURDATE()) AS due_today,
    (
        SELECT COUNT(*) FROM Favorites f WHERE f.userID = u.userID
    ) AS favorite_count,
    (
        SELECT COUNT(*) FROM Review_Logs rl
        JOIN User_Vocab_Progress p2 ON p2.progressID = rl.progressID
        WHERE p2.userID = u.userID
          AND DATE(rl.review_date) = CURDATE()
    ) AS reviews_today,
    (
        SELECT ROUND(AVG(qr.score / qr.total * 100), 1)
        FROM Quiz_Results qr
        WHERE qr.userID = u.userID
    ) AS avg_quiz_percent
FROM Users u
LEFT JOIN User_Vocab_Progress p ON p.userID = u.userID
GROUP BY u.userID, u.userName, u.full_name;

-- Báo cáo theo chủ đề
CREATE OR REPLACE VIEW v_topic_report AS
SELECT
    t.topicID,
    t.topicName,
    t.cefr_level,
    COUNT(DISTINCT v.vocabularyID) AS vocab_count,
    COUNT(DISTINCT q.quizID) AS quiz_count,
    ROUND(AVG(qr.score / qr.total * 100), 1) AS avg_score_percent
FROM Topics t
LEFT JOIN Vocabulary v ON v.topicID = t.topicID
LEFT JOIN Quizzes q ON q.topicID = t.topicID
LEFT JOIN Quiz_Results qr ON qr.quizID = q.quizID
GROUP BY t.topicID, t.topicName, t.cefr_level;

-- Bảng xếp hạng (điểm quiz trung bình + số từ mastered)
CREATE OR REPLACE VIEW v_leaderboard AS
SELECT
    u.userID,
    u.userName,
    u.full_name,
    COALESCE(SUM(p.mastery_level = 'MASTERED'), 0) AS mastered,
    COALESCE((
        SELECT ROUND(AVG(qr.score / qr.total * 100), 1)
        FROM Quiz_Results qr WHERE qr.userID = u.userID
    ), 0) AS avg_quiz,
    (
        SELECT COUNT(*) FROM Review_Logs rl
        JOIN User_Vocab_Progress p2 ON p2.progressID = rl.progressID
        WHERE p2.userID = u.userID
    ) AS total_reviews
FROM Users u
LEFT JOIN User_Vocab_Progress p ON p.userID = u.userID
WHERE u.roleID = 2
GROUP BY u.userID, u.userName, u.full_name
ORDER BY mastered DESC, avg_quiz DESC;

-- Từ yếu (quality trung bình < 3)
CREATE OR REPLACE VIEW v_weak_words AS
SELECT
    p.userID,
    v.vocabularyID,
    v.word,
    t.topicName,
    ROUND(AVG(rl.quality_rating), 2) AS avg_quality,
    COUNT(rl.reviewLogID) AS times_reviewed,
    p.mastery_level
FROM User_Vocab_Progress p
JOIN Vocabulary v ON v.vocabularyID = p.vocabularyID
JOIN Topics t ON t.topicID = v.topicID
JOIN Review_Logs rl ON rl.progressID = p.progressID
GROUP BY p.userID, v.vocabularyID, v.word, t.topicName, p.mastery_level
HAVING AVG(rl.quality_rating) < 3
ORDER BY avg_quality ASC;

-- =============================================================================
-- FUNCTION: nhãn thành thạo
-- =============================================================================
DROP FUNCTION IF EXISTS fn_mastery_from_srs;
DELIMITER $$
CREATE FUNCTION fn_mastery_from_srs(p_reps INT, p_interval INT, p_quality INT)
RETURNS VARCHAR(20)
DETERMINISTIC
BEGIN
    IF p_quality < 3 THEN
        RETURN 'LEARNING';
    ELSEIF p_reps >= 5 AND p_interval >= 21 THEN
        RETURN 'MASTERED';
    ELSEIF p_reps >= 2 THEN
        RETURN 'REVIEWING';
    ELSE
        RETURN 'LEARNING';
    END IF;
END$$
DELIMITER ;

-- =============================================================================
-- PROCEDURE: ôn 1 thẻ theo SuperMemo SM-2 (Anki-like)
-- quality 0-5: 0-2 sai / khó nhớ; 3-5 nhớ được
-- =============================================================================
DROP PROCEDURE IF EXISTS sp_review_vocabulary;
DELIMITER $$
CREATE PROCEDURE sp_review_vocabulary(
    IN  p_userID        INT,
    IN  p_vocabularyID  INT,
    IN  p_quality       TINYINT,
    IN  p_response_ms   INT,
    OUT p_next_date     DATE,
    OUT p_mastery       VARCHAR(20),
    OUT p_interval      INT
)
BEGIN
    DECLARE v_progressID INT;
    DECLARE v_ef DECIMAL(4,2);
    DECLARE v_interval INT;
    DECLARE v_reps INT;
    DECLARE v_lapses INT;
    DECLARE v_new_ef DECIMAL(4,2);
    DECLARE v_mastery VARCHAR(20);

    IF p_quality < 0 OR p_quality > 5 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'quality_rating phải từ 0 đến 5';
    END IF;

    -- Gói logic SRS trong 1 procedure (đơn vị công việc / ACID khi gọi trong 1 session)
    SELECT progressID, ease_factor, interval_days, repetitions, lapses
      INTO v_progressID, v_ef, v_interval, v_reps, v_lapses
    FROM User_Vocab_Progress
    WHERE userID = p_userID AND vocabularyID = p_vocabularyID
    FOR UPDATE;

    IF v_progressID IS NULL THEN
        INSERT INTO User_Vocab_Progress (userID, vocabularyID)
        VALUES (p_userID, p_vocabularyID);
        SET v_progressID = LAST_INSERT_ID();
        SET v_ef = 2.50;
        SET v_interval = 0;
        SET v_reps = 0;
        SET v_lapses = 0;
    END IF;

    -- SM-2: EF' = EF + (0.1 - (5-q) * (0.08 + (5-q)*0.02))
    SET v_new_ef = v_ef + (0.1 - (5 - p_quality) * (0.08 + (5 - p_quality) * 0.02));
    IF v_new_ef < 1.30 THEN
        SET v_new_ef = 1.30;
    END IF;

    IF p_quality < 3 THEN
        SET v_reps = 0;
        SET v_interval = 1;
        SET v_lapses = v_lapses + 1;
    ELSE
        SET v_reps = v_reps + 1;
        IF v_reps = 1 THEN
            SET v_interval = 1;
        ELSEIF v_reps = 2 THEN
            SET v_interval = 6;
        ELSE
            SET v_interval = GREATEST(1, ROUND(v_interval * v_new_ef));
        END IF;
    END IF;

    SET v_mastery = fn_mastery_from_srs(v_reps, v_interval, p_quality);

    UPDATE User_Vocab_Progress
    SET ease_factor = v_new_ef,
        interval_days = v_interval,
        repetitions = v_reps,
        lapses = v_lapses,
        mastery_level = v_mastery,
        next_review_date = DATE_ADD(CURDATE(), INTERVAL v_interval DAY),
        last_reviewed_at = NOW(),
        last_quality_rating = p_quality
    WHERE progressID = v_progressID;

    INSERT INTO Review_Logs (progressID, quality_rating, interval_after, ease_after, response_time_ms)
    VALUES (v_progressID, p_quality, v_interval, v_new_ef, p_response_ms);

    SET p_next_date = DATE_ADD(CURDATE(), INTERVAL v_interval DAY);
    SET p_mastery = v_mastery;
    SET p_interval = v_interval;
END$$
DELIMITER ;

-- Khởi tạo progress NEW cho mọi từ công khai khi user mới học chủ đề
DROP PROCEDURE IF EXISTS sp_enroll_topic;
DELIMITER $$
CREATE PROCEDURE sp_enroll_topic(
    IN p_userID INT,
    IN p_topicID INT
)
BEGIN
    INSERT IGNORE INTO User_Vocab_Progress (userID, vocabularyID, next_review_date, mastery_level)
    SELECT p_userID, v.vocabularyID, CURDATE(), 'NEW'
    FROM Vocabulary v
    WHERE v.topicID = p_topicID;
END$$
DELIMITER ;

-- Dashboard 1 user
DROP PROCEDURE IF EXISTS sp_user_dashboard;
DELIMITER $$
CREATE PROCEDURE sp_user_dashboard(IN p_userID INT)
BEGIN
    SELECT * FROM v_user_learning_stats WHERE userID = p_userID;

    SELECT vocabularyID, word, topicName, mastery_level, next_review_date
    FROM v_due_reviews
    WHERE userID = p_userID
    ORDER BY next_review_date ASC
    LIMIT 20;

    SELECT qr.resultID, t.topicName, qr.score, qr.total,
           ROUND(qr.score / qr.total * 100, 1) AS percent, qr.taken_at
    FROM Quiz_Results qr
    JOIN Quizzes q ON q.quizID = qr.quizID
    JOIN Topics t ON t.topicID = q.topicID
    WHERE qr.userID = p_userID
    ORDER BY qr.taken_at DESC
    LIMIT 5;
END$$
DELIMITER ;

-- =============================================================================
-- TRIGGERS
-- =============================================================================

DROP TRIGGER IF EXISTS trg_users_before_insert;
DELIMITER $$
CREATE TRIGGER trg_users_before_insert
BEFORE INSERT ON Users
FOR EACH ROW
BEGIN
    SET NEW.email = LOWER(TRIM(NEW.email));
    SET NEW.userName = TRIM(NEW.userName);
    IF CHAR_LENGTH(NEW.userName) < 3 THEN
        SIGNAL SQLSTATE '45000' SET MESSAGE_TEXT = 'userName tối thiểu 3 ký tự';
    END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_vocab_after_insert;
DELIMITER $$
CREATE TRIGGER trg_vocab_after_insert
AFTER INSERT ON Vocabulary
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Logs (table_name, action_type, record_pk, description)
    VALUES ('Vocabulary', 'INSERT', CAST(NEW.vocabularyID AS CHAR),
            CONCAT('Thêm từ: ', NEW.word, ' (topic ', NEW.topicID, ')'));
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_vocab_after_update;
DELIMITER $$
CREATE TRIGGER trg_vocab_after_update
AFTER UPDATE ON Vocabulary
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Logs (table_name, action_type, record_pk, description)
    VALUES ('Vocabulary', 'UPDATE', CAST(NEW.vocabularyID AS CHAR),
            CONCAT('Sửa từ: ', OLD.word, ' -> ', NEW.word));
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_vocab_after_delete;
DELIMITER $$
CREATE TRIGGER trg_vocab_after_delete
AFTER DELETE ON Vocabulary
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Logs (table_name, action_type, record_pk, description)
    VALUES ('Vocabulary', 'DELETE', CAST(OLD.vocabularyID AS CHAR),
            CONCAT('Xóa từ: ', OLD.word));
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_progress_before_update;
DELIMITER $$
CREATE TRIGGER trg_progress_before_update
BEFORE UPDATE ON User_Vocab_Progress
FOR EACH ROW
BEGIN
    IF NEW.ease_factor < 1.30 THEN
        SET NEW.ease_factor = 1.30;
    END IF;
    IF NEW.interval_days < 0 THEN
        SET NEW.interval_days = 0;
    END IF;
END$$
DELIMITER ;

DROP TRIGGER IF EXISTS trg_quiz_result_after_insert;
DELIMITER $$
CREATE TRIGGER trg_quiz_result_after_insert
AFTER INSERT ON Quiz_Results
FOR EACH ROW
BEGIN
    INSERT INTO Audit_Logs (table_name, action_type, record_pk, description)
    VALUES ('Quiz_Results', 'INSERT', CAST(NEW.resultID AS CHAR),
            CONCAT('User ', NEW.userID, ' đạt ', NEW.score, '/', NEW.total));
END$$
DELIMITER ;
