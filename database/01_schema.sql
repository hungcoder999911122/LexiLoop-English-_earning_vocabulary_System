-- =============================================================================
-- LexiLoop — Hệ thống hỗ trợ học từ vựng tiếng Anh
-- File 1/3: SCHEMA (bảng, khóa, ràng buộc)
-- MySQL 8.0+ | Engine InnoDB | utf8mb4
-- Chuẩn hóa: 3NF | Tham khảo Quizlet (bộ thẻ/chủ đề) + Anki (SRS / SM-2)
-- =============================================================================

DROP DATABASE IF EXISTS db_LexiLoop_core;
CREATE DATABASE db_LexiLoop_core
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE db_LexiLoop_core;

-- -----------------------------------------------------------------------------
-- 1. Roles — tách quyền khỏi Users (3NF, tránh ENUM cứng trong Users)
-- -----------------------------------------------------------------------------
CREATE TABLE Roles (
    roleID          INT             PRIMARY KEY AUTO_INCREMENT,
    roleName        VARCHAR(30)     NOT NULL,
    description     VARCHAR(255)    NULL,
    CONSTRAINT uq_roles_name UNIQUE (roleName)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- 2. Users
-- -----------------------------------------------------------------------------
CREATE TABLE Users (
    userID          INT             PRIMARY KEY AUTO_INCREMENT,
    userName        VARCHAR(50)     NOT NULL,
    email           VARCHAR(100)    NOT NULL,
    password_hash   VARCHAR(255)    NOT NULL,
    full_name       VARCHAR(100)    NULL,
    avatar_url      VARCHAR(255)    NULL,
    roleID          INT             NOT NULL DEFAULT 2,
    is_active       TINYINT(1)      NOT NULL DEFAULT 1,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_users_username UNIQUE (userName),
    CONSTRAINT uq_users_email UNIQUE (email),
    CONSTRAINT fk_users_role
        FOREIGN KEY (roleID) REFERENCES Roles(roleID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_users_email CHECK (email LIKE '%@%.%'),
    CONSTRAINT chk_users_active CHECK (is_active IN (0, 1))
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- 3. Topics — tương đương "Study Set" trên Quizlet
-- -----------------------------------------------------------------------------
CREATE TABLE Topics (
    topicID             INT             PRIMARY KEY AUTO_INCREMENT,
    topicName           VARCHAR(100)    NOT NULL,
    topicDescription    TEXT            NULL,
    cefr_level          ENUM('A1','A2','B1','B2','C1','C2') NOT NULL DEFAULT 'A1',
    is_public           TINYINT(1)      NOT NULL DEFAULT 1,
    created_by          INT             NOT NULL,
    created_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at          DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                        ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT uq_topics_name UNIQUE (topicName),
    CONSTRAINT fk_topics_creator
        FOREIGN KEY (created_by) REFERENCES Users(userID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT chk_topics_public CHECK (is_public IN (0, 1))
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- 4. Vocabulary — từ gốc thuộc 1 chủ đề (Quizlet: term trong set)
-- -----------------------------------------------------------------------------
CREATE TABLE Vocabulary (
    vocabularyID    INT             PRIMARY KEY AUTO_INCREMENT,
    topicID         INT             NOT NULL,
    word            VARCHAR(100)    NOT NULL,
    pronunciation   VARCHAR(100)    NULL,
    audio_url       VARCHAR(255)    NULL,
    difficulty      ENUM('EASY','MEDIUM','HARD') NOT NULL DEFAULT 'EASY',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT fk_vocab_topic
        FOREIGN KEY (topicID) REFERENCES Topics(topicID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT,
    CONSTRAINT uq_vocab_topic_word UNIQUE (topicID, word)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- 5. Part_of_Speech — từ loại (n, v, adj...) tách bảng để tái sử dụng
-- -----------------------------------------------------------------------------
CREATE TABLE Part_of_Speech (
    partOfSpeechID      INT             PRIMARY KEY AUTO_INCREMENT,
    partOfSpeechName    VARCHAR(50)     NOT NULL,
    pos_code            VARCHAR(10)     NOT NULL,
    description         VARCHAR(255)    NULL,

    CONSTRAINT uq_pos_name UNIQUE (partOfSpeechName),
    CONSTRAINT uq_pos_code UNIQUE (pos_code)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- 6. Vocabulary_Senses — mỗi từ có nhiều nghĩa / từ loại (1-N)
-- -----------------------------------------------------------------------------
CREATE TABLE Vocabulary_Senses (
    vocabularySenseID   INT             PRIMARY KEY AUTO_INCREMENT,
    vocabularyID        INT             NOT NULL,
    partOfSpeechID      INT             NOT NULL,
    meaning_vi          TEXT            NOT NULL,
    meaning_en          TEXT            NULL,

    CONSTRAINT fk_sense_vocab
        FOREIGN KEY (vocabularyID) REFERENCES Vocabulary(vocabularyID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_sense_pos
        FOREIGN KEY (partOfSpeechID) REFERENCES Part_of_Speech(partOfSpeechID)
        ON UPDATE CASCADE
        ON DELETE RESTRICT
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- 7. Vocabulary_Examples — ví dụ minh họa cho từng nghĩa (1-N)
-- -----------------------------------------------------------------------------
CREATE TABLE Vocabulary_Examples (
    exampleID           INT             PRIMARY KEY AUTO_INCREMENT,
    vocabularySenseID   INT             NOT NULL,
    sentence_en         TEXT            NOT NULL,
    sentence_vi         TEXT            NULL,

    CONSTRAINT fk_example_sense
        FOREIGN KEY (vocabularySenseID) REFERENCES Vocabulary_Senses(vocabularySenseID)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- 8. Favorites — người dùng đánh dấu từ yêu thích (N-N)
-- -----------------------------------------------------------------------------
CREATE TABLE Favorites (
    userID          INT             NOT NULL,
    vocabularyID    INT             NOT NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT pk_favorites PRIMARY KEY (userID, vocabularyID),
    CONSTRAINT fk_fav_user
        FOREIGN KEY (userID) REFERENCES Users(userID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_fav_vocab
        FOREIGN KEY (vocabularyID) REFERENCES Vocabulary(vocabularyID)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- 9. User_Vocab_Progress — trạng thái SRS (Anki / SM-2) theo từng user-từ
-- -----------------------------------------------------------------------------
CREATE TABLE User_Vocab_Progress (
    progressID              INT             PRIMARY KEY AUTO_INCREMENT,
    userID                  INT             NOT NULL,
    vocabularyID            INT             NOT NULL,
    ease_factor             DECIMAL(4,2)    NOT NULL DEFAULT 2.50,
    interval_days           INT             NOT NULL DEFAULT 0,
    repetitions             INT             NOT NULL DEFAULT 0,
    lapses                  INT             NOT NULL DEFAULT 0,
    mastery_level           ENUM('NEW','LEARNING','REVIEWING','MASTERED')
                                            NOT NULL DEFAULT 'NEW',
    next_review_date        DATE            NULL,
    last_reviewed_at        DATETIME        NULL,
    last_quality_rating     TINYINT         NULL,

    CONSTRAINT uq_progress_user_vocab UNIQUE (userID, vocabularyID),
    CONSTRAINT fk_progress_user
        FOREIGN KEY (userID) REFERENCES Users(userID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_progress_vocab
        FOREIGN KEY (vocabularyID) REFERENCES Vocabulary(vocabularyID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT chk_progress_interval CHECK (interval_days >= 0),
    CONSTRAINT chk_progress_reps CHECK (repetitions >= 0),
    CONSTRAINT chk_progress_lapses CHECK (lapses >= 0),
    CONSTRAINT chk_progress_ef CHECK (ease_factor >= 1.30),
    CONSTRAINT chk_progress_quality CHECK (
        last_quality_rating IS NULL
        OR last_quality_rating BETWEEN 0 AND 5
    )
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- 10. Review_Logs — lịch sử từng lần ôn (Anki: revlog) — phục vụ thống kê
-- -----------------------------------------------------------------------------
CREATE TABLE Review_Logs (
    reviewLogID         INT             PRIMARY KEY AUTO_INCREMENT,
    progressID          INT             NOT NULL,
    review_date         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    quality_rating      TINYINT         NOT NULL,
    interval_after      INT             NOT NULL,
    ease_after          DECIMAL(4,2)    NOT NULL,
    response_time_ms    INT             NULL,

    CONSTRAINT fk_review_progress
        FOREIGN KEY (progressID) REFERENCES User_Vocab_Progress(progressID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT chk_review_quality CHECK (quality_rating BETWEEN 0 AND 5),
    CONSTRAINT chk_review_time CHECK (
        response_time_ms IS NULL OR response_time_ms >= 0
    )
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- 11. Study_Sessions — phiên học (flashcard / review / quiz)
-- -----------------------------------------------------------------------------
CREATE TABLE Study_Sessions (
    sessionID       INT             PRIMARY KEY AUTO_INCREMENT,
    userID          INT             NOT NULL,
    topicID         INT             NULL,
    study_mode      ENUM('FLASHCARD','REVIEW','QUIZ') NOT NULL,
    started_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at        DATETIME        NULL,
    items_studied   INT             NOT NULL DEFAULT 0,
    correct_count   INT             NOT NULL DEFAULT 0,
    wrong_count     INT             NOT NULL DEFAULT 0,

    CONSTRAINT fk_session_user
        FOREIGN KEY (userID) REFERENCES Users(userID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_session_topic
        FOREIGN KEY (topicID) REFERENCES Topics(topicID)
        ON UPDATE CASCADE
        ON DELETE SET NULL,
    CONSTRAINT chk_session_counts CHECK (
        items_studied >= 0 AND correct_count >= 0 AND wrong_count >= 0
    )
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- 12. Quizzes — đề kiểm tra sinh theo chủ đề
-- -----------------------------------------------------------------------------
CREATE TABLE Quizzes (
    quizID          INT             PRIMARY KEY AUTO_INCREMENT,
    topicID         INT             NOT NULL,
    created_by      INT             NOT NULL,
    title           VARCHAR(150)    NOT NULL,
    num_questions   INT             NOT NULL DEFAULT 10,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_quiz_topic
        FOREIGN KEY (topicID) REFERENCES Topics(topicID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_quiz_user
        FOREIGN KEY (created_by) REFERENCES Users(userID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT chk_quiz_num CHECK (num_questions BETWEEN 1 AND 50)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- 13. Questions
-- -----------------------------------------------------------------------------
CREATE TABLE Questions (
    questionID      INT             PRIMARY KEY AUTO_INCREMENT,
    quizID          INT             NOT NULL,
    vocabularyID    INT             NOT NULL,
    question_text   VARCHAR(500)    NOT NULL,
    question_type   ENUM('EN_TO_VI','VI_TO_EN') NOT NULL DEFAULT 'EN_TO_VI',

    CONSTRAINT fk_question_quiz
        FOREIGN KEY (quizID) REFERENCES Quizzes(quizID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_question_vocab
        FOREIGN KEY (vocabularyID) REFERENCES Vocabulary(vocabularyID)
        ON UPDATE CASCADE
        ON DELETE CASCADE
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- 14. Answers — 4 lựa chọn / câu, đúng 1 đáp án
-- -----------------------------------------------------------------------------
CREATE TABLE Answers (
    answerID        INT             PRIMARY KEY AUTO_INCREMENT,
    questionID      INT             NOT NULL,
    answer_text     VARCHAR(500)    NOT NULL,
    is_correct      TINYINT(1)      NOT NULL DEFAULT 0,

    CONSTRAINT fk_answer_question
        FOREIGN KEY (questionID) REFERENCES Questions(questionID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT chk_answer_flag CHECK (is_correct IN (0, 1))
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- 15. Quiz_Results — kết quả làm bài
-- -----------------------------------------------------------------------------
CREATE TABLE Quiz_Results (
    resultID        INT             PRIMARY KEY AUTO_INCREMENT,
    quizID          INT             NOT NULL,
    userID          INT             NOT NULL,
    score           INT             NOT NULL,
    total           INT             NOT NULL,
    duration_sec    INT             NULL,
    taken_at        DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,

    CONSTRAINT fk_result_quiz
        FOREIGN KEY (quizID) REFERENCES Quizzes(quizID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_result_user
        FOREIGN KEY (userID) REFERENCES Users(userID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT chk_result_score CHECK (score >= 0 AND score <= total),
    CONSTRAINT chk_result_total CHECK (total > 0)
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- 16. Quiz_Result_Details — chi tiết từng câu (phục vụ xem lại lỗi)
-- -----------------------------------------------------------------------------
CREATE TABLE Quiz_Result_Details (
    detailID            INT             PRIMARY KEY AUTO_INCREMENT,
    resultID            INT             NOT NULL,
    questionID          INT             NOT NULL,
    selected_answerID   INT             NULL,
    is_correct          TINYINT(1)      NOT NULL,

    CONSTRAINT fk_detail_result
        FOREIGN KEY (resultID) REFERENCES Quiz_Results(resultID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_detail_question
        FOREIGN KEY (questionID) REFERENCES Questions(questionID)
        ON UPDATE CASCADE
        ON DELETE CASCADE,
    CONSTRAINT fk_detail_answer
        FOREIGN KEY (selected_answerID) REFERENCES Answers(answerID)
        ON UPDATE CASCADE
        ON DELETE SET NULL
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- 17. Audit_Logs — ghi nhận thay đổi từ TRIGGER (minh họa audit trail)
-- -----------------------------------------------------------------------------
CREATE TABLE Audit_Logs (
    auditID         INT             PRIMARY KEY AUTO_INCREMENT,
    table_name      VARCHAR(50)     NOT NULL,
    action_type     ENUM('INSERT','UPDATE','DELETE') NOT NULL,
    record_pk       VARCHAR(50)     NOT NULL,
    description     VARCHAR(500)    NULL,
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -----------------------------------------------------------------------------
-- INDEX — tối ưu truy vấn ôn bài, tìm từ, thống kê
-- -----------------------------------------------------------------------------
CREATE INDEX idx_users_role             ON Users(roleID);
CREATE INDEX idx_topics_creator         ON Topics(created_by);
CREATE INDEX idx_topics_cefr            ON Topics(cefr_level);
CREATE INDEX idx_vocab_topic            ON Vocabulary(topicID);
CREATE INDEX idx_vocab_word             ON Vocabulary(word);
CREATE INDEX idx_sense_vocab            ON Vocabulary_Senses(vocabularyID);
CREATE INDEX idx_sense_pos              ON Vocabulary_Senses(partOfSpeechID);
CREATE INDEX idx_example_sense          ON Vocabulary_Examples(vocabularySenseID);
CREATE INDEX idx_progress_user          ON User_Vocab_Progress(userID);
CREATE INDEX idx_progress_next          ON User_Vocab_Progress(next_review_date);
CREATE INDEX idx_progress_mastery       ON User_Vocab_Progress(mastery_level);
CREATE INDEX idx_review_progress        ON Review_Logs(progressID);
CREATE INDEX idx_review_date            ON Review_Logs(review_date);
CREATE INDEX idx_session_user           ON Study_Sessions(userID);
CREATE INDEX idx_quiz_topic             ON Quizzes(topicID);
CREATE INDEX idx_question_quiz          ON Questions(quizID);
CREATE INDEX idx_answer_question        ON Answers(questionID);
CREATE INDEX idx_result_user            ON Quiz_Results(userID);
CREATE INDEX idx_result_quiz            ON Quiz_Results(quizID);

ALTER TABLE Vocabulary
    ADD FULLTEXT INDEX ft_vocab_word (word);

ALTER TABLE Vocabulary_Senses
    ADD FULLTEXT INDEX ft_sense_meaning (meaning_vi, meaning_en);
