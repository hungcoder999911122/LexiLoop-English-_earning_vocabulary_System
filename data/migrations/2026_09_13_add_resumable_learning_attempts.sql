-- ============================================================
-- LexiLoop: lưu phiên Flashcard/Quiz đang thực hiện để học tiếp.
-- Chạy MỘT LẦN trong phpMyAdmin sau khi đã sao lưu database.
-- ============================================================

USE `hoc_ngoai_ngu`;

CREATE TABLE `learning_attempts` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `activity_type` ENUM('flashcard', 'quiz') NOT NULL,
    `source_type` ENUM('topic', 'set', 'review') NOT NULL,
    `source_id` INT NULL,
    `item_limit` VARCHAR(10) NOT NULL DEFAULT '10',
    `state_json` JSON NOT NULL,
    `status` ENUM('in_progress', 'completed', 'abandoned') NOT NULL DEFAULT 'in_progress',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `completed_at` DATETIME NULL,
    PRIMARY KEY (`id`),
    KEY `idx_attempt_resume` (`user_id`, `activity_type`, `source_type`, `source_id`, `status`, `updated_at`),
    CONSTRAINT `fk_learning_attempts_user`
        FOREIGN KEY (`user_id`) REFERENCES `Users` (`userID`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

SHOW TABLES LIKE 'learning_attempts';
