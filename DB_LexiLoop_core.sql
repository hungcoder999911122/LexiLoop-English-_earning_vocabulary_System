CREATE DATABASE IF NOT EXISTS db_LexiLoop_core;
USE db_LexiLoop_core;

-- 1. BẢNG users
CREATE TABLE Users
(
    userID              INT             PRIMARY KEY AUTO_INCREMENT,
    userName        VARCHAR(50)     NOT NULL UNIQUE,
    email           VARCHAR(100)    NOT NULL UNIQUE,
    password_hash   VARCHAR(255)    NOT NULL,
    full_name       VARCHAR(100)    NULL,
    avatar_url      VARCHAR(255)    NULL,
    role            ENUM('user', 'admin')
                                    NOT NULL DEFAULT 'user',
    created_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                    ON UPDATE CURRENT_TIMESTAMP
);

-- 2. BẢNG topics
CREATE TABLE Topics
(
    topicID         	INT             PRIMARY KEY AUTO_INCREMENT,
    topicname       	VARCHAR(100)    NOT NULL,
    topicDescription	TEXT            NULL,
    created_by      	INT             NOT NULL,
    created_at      	DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      	DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
										ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (created_by)
        REFERENCES Users(userID)
);

-- 3. BẢNG vocabulary
CREATE TABLE Vocabulary
(
    vocabularyID       INT             PRIMARY KEY AUTO_INCREMENT,
    topicID            INT             NOT NULL,
    word               VARCHAR(100)    NOT NULL,
    pronunciation      VARCHAR(100)    NULL,
    audio_url          VARCHAR(255)    NULL,
    created_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP
                                     ON UPDATE CURRENT_TIMESTAMP,

    FOREIGN KEY (topicID)
        REFERENCES Topics(topicID),

    UNIQUE (topicID, word)
);

-- 4. BẢNG Part_of_speech
CREATE TABLE Part_of_Speech
(
    partOfSpeechID     INT             PRIMARY KEY AUTO_INCREMENT,
    partOfSpeechName   VARCHAR(50)     NOT NULL UNIQUE,
    description        VARCHAR(255)    NULL
);

-- 5. BẢNG Vocabulary_sense
CREATE TABLE Vocabulary_Senses
(
    vocabularySenseID  INT             PRIMARY KEY AUTO_INCREMENT,
    vocabularyID       INT             NOT NULL,
    partOfSpeechID     INT             NOT NULL,
    meaning            TEXT            NOT NULL,
    example_sentence   TEXT            NULL,

    FOREIGN KEY (vocabularyID)
        REFERENCES Vocabulary(vocabularyID)
        ON DELETE CASCADE,

    FOREIGN KEY (partOfSpeechID)
        REFERENCES Part_of_Speech(partOfSpeechID)
);

-- 6. BẢNG Vocabulary_progress
CREATE TABLE User_Vocab_Progress
(
    progressID          INT             PRIMARY KEY AUTO_INCREMENT,
    userID              INT             NOT NULL,
    vocabularyID        INT             NOT NULL,
    interval_days       INT             NOT NULL DEFAULT 0,
    repetitions          INT            NOT NULL DEFAULT 0,
    next_review_date    DATE            NULL,
    last_reviewed_at    DATETIME        NULL,
    last_quality_rating TINYINT         NULL,

    FOREIGN KEY (userID)
        REFERENCES Users(userID)
        ON DELETE CASCADE,

    FOREIGN KEY (vocabularyID)
        REFERENCES Vocabulary(vocabularyID)
        ON DELETE CASCADE,

    UNIQUE (userID, vocabularyID),

    CHECK (interval_days >= 0),
    CHECK (repetitions >= 0),
    CHECK (
        last_quality_rating IS NULL
        OR last_quality_rating BETWEEN 0 AND 5
    )
);

-- 7. BẢNG Review_logs
CREATE TABLE Review_Logs
(
    reviewLogID         INT             PRIMARY KEY AUTO_INCREMENT,
    progressID          INT             NOT NULL,
    review_date         DATETIME        NOT NULL DEFAULT CURRENT_TIMESTAMP,
    quality_rating      TINYINT         NOT NULL,
    response_time_ms    INT             NULL,

    FOREIGN KEY (progressID)
        REFERENCES User_Vocab_Progress(progressID)
        ON DELETE CASCADE,

    CHECK (quality_rating BETWEEN 0 AND 5),
    CHECK (
        response_time_ms IS NULL
        OR response_time_ms >= 0
    )
);



