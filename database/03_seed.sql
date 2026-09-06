-- =============================================================================
-- LexiLoop — File 3/3: DỮ LIỆU MẪU
-- Mật khẩu mọi tài khoản demo: password
-- Hash BCrypt (Spring Security default): $2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi
-- =============================================================================
USE db_LexiLoop_core;

INSERT INTO Roles (roleID, roleName, description) VALUES
(1, 'ADMIN', 'Quản trị nội dung, người dùng, báo cáo'),
(2, 'LEARNER', 'Học viên: học thẻ, ôn SRS, làm quiz');

INSERT INTO Users (userName, email, password_hash, full_name, roleID) VALUES
('admin',   'admin@lexiloop.edu',   '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Quản trị viên', 1),
('an.nguyen','an@student.edu',      '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Nguyễn Văn An', 2),
('binh.tran','binh@student.edu',    '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Trần Thị Bình', 2),
('chi.le',  'chi@student.edu',      '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Lê Minh Chi', 2);

INSERT INTO Part_of_Speech (partOfSpeechName, pos_code, description) VALUES
('Danh từ',     'n',    'Noun'),
('Động từ',     'v',    'Verb'),
('Tính từ',     'adj',  'Adjective'),
('Trạng từ',    'adv',  'Adverb'),
('Giới từ',     'prep', 'Preposition'),
('Thành ngữ',   'idm',  'Idiom / phrase');

INSERT INTO Topics (topicName, topicDescription, cefr_level, is_public, created_by) VALUES
('Daily Life', 'Từ vựng sinh hoạt hằng ngày — phong cách Quizlet set A1', 'A1', 1, 1),
('Travel', 'Du lịch, sân bay, khách sạn — A2', 'A2', 1, 1),
('Business English', 'Công sở, họp, email — B1', 'B1', 1, 1),
('Academic IELTS', 'Từ học thuật thường gặp IELTS Writing — B2', 'B2', 1, 1);

-- Helper: insert vocab + 1 sense + 1 example
-- Daily Life (topic 1)
INSERT INTO Vocabulary (topicID, word, pronunciation, difficulty) VALUES
(1, 'breakfast', '/ˈbrekfəst/', 'EASY'),
(1, 'commute',   '/kəˈmjuːt/',  'MEDIUM'),
(1, 'household', '/ˈhaʊshəʊld/', 'EASY'),
(1, 'chore',     '/tʃɔː/',       'EASY'),
(1, 'neighbor',  '/ˈneɪbə/',     'EASY'),
(1, 'grocery',   '/ˈɡrəʊsəri/',  'EASY'),
(1, 'exhausted', '/ɪɡˈzɔːstɪd/', 'MEDIUM'),
(1, 'routine',   '/ruːˈtiːn/',   'EASY');

INSERT INTO Vocabulary_Senses (vocabularyID, partOfSpeechID, meaning_vi, meaning_en) VALUES
(1, 1, 'bữa sáng', 'the first meal of the day'),
(2, 2, 'đi lại (nhà-cơ quan)', 'to travel regularly to and from work'),
(3, 1, 'hộ gia đình', 'people who live together in a house'),
(4, 1, 'việc nhà', 'a routine household task'),
(5, 1, 'hàng xóm', 'a person living next door'),
(6, 1, 'thực phẩm / cửa hàng tạp hóa', 'food and household supplies'),
(7, 3, 'kiệt sức', 'extremely tired'),
(8, 1, 'thói quen hằng ngày', 'a usual series of actions');

INSERT INTO Vocabulary_Examples (vocabularySenseID, sentence_en, sentence_vi) VALUES
(1, 'I usually have breakfast at 7 a.m.', 'Tôi thường ăn sáng lúc 7 giờ.'),
(2, 'She commutes to the city by train.', 'Cô ấy đi làm bằng tàu hỏa.'),
(3, 'A typical household has three members.', 'Một hộ điển hình có ba người.'),
(4, 'Washing dishes is my least favorite chore.', 'Rửa chén là việc nhà tôi ghét nhất.'),
(5, 'Our neighbor helped us carry the boxes.', 'Hàng xóm giúp chúng tôi khiêng thùng.'),
(6, 'I need to buy groceries after work.', 'Tôi cần mua thực phẩm sau giờ làm.'),
(7, 'He felt exhausted after a long day.', 'Anh ấy kiệt sức sau một ngày dài.'),
(8, 'Exercise is part of her morning routine.', 'Tập thể dục là thói quen buổi sáng của cô ấy.');

-- Travel (topic 2)
INSERT INTO Vocabulary (topicID, word, pronunciation, difficulty) VALUES
(2, 'departure',  '/dɪˈpɑːtʃə/',  'MEDIUM'),
(2, 'luggage',    '/ˈlʌɡɪdʒ/',    'EASY'),
(2, 'itinerary',  '/aɪˈtɪnərəri/', 'HARD'),
(2, 'reservation','/ˌrezəˈveɪʃn/', 'MEDIUM'),
(2, 'boarding',   '/ˈbɔːdɪŋ/',    'EASY'),
(2, 'destination','/ˌdestɪˈneɪʃn/', 'MEDIUM'),
(2, 'souvenir',   '/ˌsuːvəˈnɪə/', 'EASY'),
(2, 'delay',      '/dɪˈleɪ/',     'EASY');

INSERT INTO Vocabulary_Senses (vocabularyID, partOfSpeechID, meaning_vi, meaning_en) VALUES
(9,  1, 'chuyến khởi hành', 'the act of leaving a place'),
(10, 1, 'hành lý', 'bags that you take when travelling'),
(11, 1, 'lịch trình', 'a detailed plan of a journey'),
(12, 1, 'đặt chỗ trước', 'an arrangement to keep a seat/room'),
(13, 1, 'việc lên máy bay', 'the act of getting on a plane'),
(14, 1, 'điểm đến', 'the place where someone is going'),
(15, 1, 'quà lưu niệm', 'something you buy to remember a place'),
(16, 1, 'sự trì hoãn', 'a period of being late');

INSERT INTO Vocabulary_Examples (vocabularySenseID, sentence_en, sentence_vi) VALUES
(9,  'The departure time is 14:30.', 'Giờ khởi hành là 14:30.'),
(10, 'Please do not leave your luggage unattended.', 'Đừng để hành lý không người trông.'),
(11, 'Check the itinerary before we leave.', 'Xem lịch trình trước khi đi.'),
(12, 'I made a hotel reservation online.', 'Tôi đặt phòng khách sạn trên mạng.'),
(13, 'Boarding starts at gate 12.', 'Lên máy bay bắt đầu ở cửa 12.'),
(14, 'Paris is our final destination.', 'Paris là điểm đến cuối.'),
(15, 'She bought a souvenir for her sister.', 'Cô ấy mua quà lưu niệm cho em.'),
(16, 'The flight delay lasted two hours.', 'Chuyến bay delay hai tiếng.');

-- Business (topic 3)
INSERT INTO Vocabulary (topicID, word, pronunciation, difficulty) VALUES
(3, 'deadline',    '/ˈdedlaɪn/',    'MEDIUM'),
(3, 'negotiate',   '/nɪˈɡəʊʃieɪt/', 'HARD'),
(3, 'stakeholder', '/ˈsteɪkhəʊldə/', 'HARD'),
(3, 'agenda',      '/əˈdʒendə/',    'MEDIUM'),
(3, 'feedback',    '/ˈfiːdbæk/',    'EASY'),
(3, 'budget',      '/ˈbʌdʒɪt/',     'MEDIUM'),
(3, 'collaborate', '/kəˈlæbəreɪt/', 'MEDIUM'),
(3, 'invoice',     '/ˈɪnvɔɪs/',     'MEDIUM');

INSERT INTO Vocabulary_Senses (vocabularyID, partOfSpeechID, meaning_vi, meaning_en) VALUES
(17, 1, 'hạn chót', 'a time by which something must be done'),
(18, 2, 'đàm phán', 'to discuss in order to reach an agreement'),
(19, 1, 'bên liên quan', 'a person with an interest in a project'),
(20, 1, 'chương trình họp', 'a list of items to be discussed'),
(21, 1, 'phản hồi', 'comments about how well something is done'),
(22, 1, 'ngân sách', 'an amount of money available to spend'),
(23, 2, 'hợp tác', 'to work together with someone'),
(24, 1, 'hóa đơn thanh toán', 'a document asking for payment');

INSERT INTO Vocabulary_Examples (vocabularySenseID, sentence_en, sentence_vi) VALUES
(17, 'The report deadline is Friday.', 'Hạn nộp báo cáo là thứ Sáu.'),
(18, 'We need to negotiate a better price.', 'Chúng ta cần đàm phán giá tốt hơn.'),
(19, 'All stakeholders will attend the meeting.', 'Mọi bên liên quan sẽ dự họp.'),
(20, 'Please send the agenda in advance.', 'Hãy gửi chương trình họp trước.'),
(21, 'Her feedback helped us improve.', 'Phản hồi của cô ấy giúp chúng tôi tiến bộ.'),
(22, 'The marketing budget was cut.', 'Ngân sách marketing bị cắt.'),
(23, 'Teams collaborate on the new app.', 'Các nhóm hợp tác làm app mới.'),
(24, 'Please pay the invoice within 14 days.', 'Thanh toán hóa đơn trong 14 ngày.');

-- Academic IELTS (topic 4)
INSERT INTO Vocabulary (topicID, word, pronunciation, difficulty) VALUES
(4, 'significant', '/sɪɡˈnɪfɪkənt/', 'MEDIUM'),
(4, 'hypothesis',  '/haɪˈpɒθəsɪs/',  'HARD'),
(4, 'consequently','/ˈkɒnsɪkwəntli/', 'HARD'),
(4, 'evaluate',    '/ɪˈvæljueɪt/',   'MEDIUM'),
(4, 'perspective', '/pəˈspektɪv/',   'MEDIUM'),
(4, 'evidence',    '/ˈevɪdəns/',     'MEDIUM'),
(4, 'approximately','/əˈprɒksɪmətli/', 'MEDIUM'),
(4, 'contribute',  '/kənˈtrɪbjuːt/', 'MEDIUM');

INSERT INTO Vocabulary_Senses (vocabularyID, partOfSpeechID, meaning_vi, meaning_en) VALUES
(25, 3, 'đáng kể / quan trọng', 'large or important enough to notice'),
(26, 1, 'giả thuyết', 'an idea that is suggested as an explanation'),
(27, 4, 'do đó / vì vậy', 'as a result'),
(28, 2, 'đánh giá', 'to judge how good, useful or successful something is'),
(29, 1, 'góc nhìn / quan điểm', 'a particular way of thinking'),
(30, 1, 'bằng chứng', 'facts that show something is true'),
(31, 4, 'xấp xỉ', 'used to show that a number is not exact'),
(32, 2, 'đóng góp', 'to help to cause something');

INSERT INTO Vocabulary_Examples (vocabularySenseID, sentence_en, sentence_vi) VALUES
(25, 'There was a significant increase in sales.', 'Doanh số tăng đáng kể.'),
(26, 'The study tests a simple hypothesis.', 'Nghiên cứu kiểm tra một giả thuyết đơn giản.'),
(27, 'Demand fell; consequently, prices dropped.', 'Nhu cầu giảm; do đó giá giảm.'),
(28, 'Teachers evaluate students every term.', 'Giáo viên đánh giá học sinh mỗi kỳ.'),
(29, 'Try to see the issue from another perspective.', 'Hãy nhìn vấn đề từ góc khác.'),
(30, 'There is little evidence to support the claim.', 'Có ít bằng chứng ủng hộ nhận định đó.'),
(31, 'Approximately 30% of students agreed.', 'Khoảng 30% sinh viên đồng ý.'),
(32, 'Volunteers contribute to the local community.', 'Tình nguyện viên đóng góp cho cộng đồng.');

-- Một từ đa nghĩa (minh họa 1-N senses)
INSERT INTO Vocabulary_Senses (vocabularyID, partOfSpeechID, meaning_vi, meaning_en) VALUES
(16, 2, 'làm chậm / trì hoãn', 'to make something late');
INSERT INTO Vocabulary_Examples (vocabularySenseID, sentence_en, sentence_vi) VALUES
(33, 'Heavy rain delayed the match.', 'Mưa lớn làm trận đấu bị hoãn.');

-- User An enroll Daily Life + một phần progress giả lập
CALL sp_enroll_topic(2, 1);
CALL sp_review_vocabulary(2, 1, 5, 1800, @d, @m, @i);
CALL sp_review_vocabulary(2, 2, 2, 4200, @d, @m, @i);
CALL sp_review_vocabulary(2, 3, 4, 2100, @d, @m, @i);
CALL sp_review_vocabulary(2, 4, 3, 2500, @d, @m, @i);

INSERT INTO Favorites (userID, vocabularyID) VALUES
(2, 11), (2, 18), (2, 25), (3, 11), (3, 19);

INSERT INTO Study_Sessions (userID, topicID, study_mode, started_at, ended_at, items_studied, correct_count, wrong_count) VALUES
(2, 1, 'FLASHCARD', DATE_SUB(NOW(), INTERVAL 2 HOUR), DATE_SUB(NOW(), INTERVAL 1 HOUR), 8, 6, 2),
(2, 1, 'REVIEW', DATE_SUB(NOW(), INTERVAL 50 MINUTE), DATE_SUB(NOW(), INTERVAL 30 MINUTE), 4, 3, 1);
