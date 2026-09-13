-- ============================================================
-- LexiLoop: cho phép phiên Flashcard/Quiz dùng bộ từ cá nhân.
-- Chạy MỘT LẦN trong phpMyAdmin trên database hoc_ngoai_ngu.
-- Không import lại toàn bộ db_LexiLoop.sql vào database đang dùng.
-- ============================================================

USE `hoc_ngoai_ngu`;

ALTER TABLE `learning_sessions`
    ADD COLUMN `vocabulary_set_id` INT NULL AFTER `topic_id`,
    ADD INDEX `idx_learning_sessions_set` (`vocabulary_set_id`),
    ADD CONSTRAINT `fk_learning_sessions_set`
        FOREIGN KEY (`vocabulary_set_id`)
        REFERENCES `vocabulary_sets` (`id`)
        ON DELETE SET NULL;

ALTER TABLE `quiz_results`
    ADD COLUMN `vocabulary_set_id` INT NULL AFTER `topic_id`,
    ADD INDEX `idx_quiz_results_set` (`vocabulary_set_id`),
    ADD CONSTRAINT `fk_quiz_results_set`
        FOREIGN KEY (`vocabulary_set_id`)
        REFERENCES `vocabulary_sets` (`id`)
        ON DELETE SET NULL;

-- Kiểm tra sau khi chạy. Mỗi câu SELECT phải trả về một dòng mô tả cột.
SHOW COLUMNS FROM `learning_sessions` LIKE 'vocabulary_set_id';
SHOW COLUMNS FROM `quiz_results` LIKE 'vocabulary_set_id';
