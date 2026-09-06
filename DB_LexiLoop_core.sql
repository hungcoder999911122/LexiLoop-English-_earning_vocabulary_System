-- LexiLoop: import LẦN LƯỢT 3 file trong thư mục database/
--   1) database/01_schema.sql
--   2) database/02_views_procedures_triggers.sql
--   3) database/03_seed.sql
--
-- Công cụ: MySQL 8.0+ | MySQL Workbench | phpMyAdmin (tab SQL, chạy từng file)
-- Database: db_LexiLoop_core
--
-- Tài khoản demo (mật khẩu: password)
--   admin / admin@lexiloop.edu     → ADMIN
--   an.nguyen / an@student.edu     → LEARNER

SOURCE database/01_schema.sql;
SOURCE database/02_views_procedures_triggers.sql;
SOURCE database/03_seed.sql;
