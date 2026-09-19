# 📖 LexiLoop Domain & SRS Terminology Dictionary

Từ điển định nghĩa chi tiết các thuật ngữ chuyên ngành khoa học nhận thức, thuật toán Spaced Repetition (Lặp lại ngắt quãng), ngôn ngữ học và kiến trúc dữ liệu áp dụng trong dự án LexiLoop.

---

## 🧠 1. Thuật ngữ Khoa học Nhận thức & Thuật toán Lặp lại Ngắt quãng (SRS)

| Thuật ngữ | Khái niệm khoa học | Ứng dụng cụ thể trong LexiLoop |
|---|---|---|
| **Forgetting Curve (Đường cong lãng quên)** | Lý thuyết của Hermann Ebbinghaus mô tả sự suy giảm theo cấp số nhân của trí nhớ con người theo thời gian nếu không có sự gợi nhắc. | Cơ sở khoa học để thiết kế các điểm kích hoạt ôn tập trước khi thông tin bị quên lãng. |
| **Spaced Repetition System (SRS)** | Phương pháp học tập tăng dần khoảng cách thời gian giữa các lần ôn tập để chuyển thông tin từ Trí nhớ ngắn hạn (Short-term memory) sang Trí nhớ dài hạn (Long-term memory). | Bộ máy trung tâm tính toán ngày ôn tập `next_review_at` cho từng từ vựng của người học. |
| **SuperMemo SM-2** | Thuật toán lặp lại ngắt quãng kinh điển do Piotr Wozniak phát triển, sử dụng hệ số dễ (Ease Factor) và chu kỳ lặp (Repetitions) để điều chỉnh khoảng cách ôn tập. | Thuật toán lõi được tinh chỉnh trong `pages/api/save_flashcard_progress.php`. |
| **Leitner System** | Phương pháp học flashcard cổ điển chia thẻ vào các ngăn hộp (Boxes). Trả lời đúng thẻ được chuyển sang hộp có chu kỳ ôn dài hơn; trả lời sai quay về hộp số 1. | Mô hình trực quan hóa cấp độ ghi nhớ (`mastery_level` từ 1 đến 5). |
| **Ease Factor (EF)** | Hệ số độ dễ của từ vựng (Giá trị mặc định = 2.5). Từ càng khó nhớ thì EF càng giảm; từ càng dễ nhớ thì EF càng tăng. | Xác định tỷ lệ nhân mở rộng khoảng cách ngày ôn trong các lần ôn tiếp theo. |
| **Interval (Khoảng cách ôn tập)** | Số ngày giữa lần ôn tập vừa hoàn thành và lần ôn tập tiếp theo (`interval_days`). | Ví dụ: Lần 1 = 1 ngày, Lần 2 = 3 ngày, Lần 3 = 7 ngày, Lần 4 = 16 ngày,... |
| **Repetition Count** | Số lần người học đã ôn tập thành công liên tiếp một từ vựng mà không bị quên. | Khi người học chọn *Again*, `repetition_count` sẽ bị reset về 0. |

---

## 🔤 2. Thuật ngữ Ngôn ngữ học & Học liệu Tiếng Anh

| Thuật ngữ | Định nghĩa | Trường dữ liệu tương ứng |
|---|---|---|
| **Headword (Từ gốc/Mục từ)** | Từ vựng tiếng Anh nguyên bản cần học. | `vocabulary.word` (VARCHAR(100)) |
| **Part of Speech (Từ loại)** | Loại ngữ pháp của từ (Noun, Verb, Adjective, Adverb, Preposition,...). | `vocabulary.part_of_speech` |
| **IPA (International Phonetic Alphabet)** | Ký hiệu phiên âm quốc tế chuẩn biểu diễn cách phát âm của từ. | `vocabulary.pronunciation` (VARCHAR(100)) |
| **Sense (Nét nghĩa)** | Một định nghĩa hoặc ngữ nghĩa cụ thể của từ trong một ngữ cảnh xác định. | `vocabulary.meaning` / Bảng `vocabulary_senses` |
| **Collocation / Example Sentence** | Cụm từ hay đi kèm hoặc câu ví dụ minh họa cách sử dụng từ trong thực tế. | `vocabulary.example_sentence` (TEXT) |
| **CEFR Levels** | Khung tham chiếu trình độ ngôn ngữ chung của Châu Âu (A1, A2, B1, B2, C1, C2). | Phân loại độ khó của Chủ đề & Từ vựng |

---

## 💻 3. Thuật ngữ Hệ thống & Dữ liệu Kỹ thuật

| Thuật ngữ | Ý nghĩa kỹ thuật trong dự án |
|---|---|
| **Current Progress (Tiến độ hiện tại)** | Trạng thái học mới nhất của 1 học viên đối với 1 từ vựng (Bảng `user_vocab_progress`). |
| **Review Log (Nhật ký ôn tập)** | Bản ghi lịch sử ghi lại chi tiết một lần lật thẻ hoặc làm quiz của học viên (Bảng `review_logs`). |
| **Vocabulary Set (Bộ từ tùy chỉnh)** | Tập hợp các từ vựng do học viên tự chọn lọc từ nhiều chủ đề khác nhau để ôn tập chuyên đề (Bảng `vocabulary_sets`). |
| **Distractor (Phương án nhiễu)** | 3 câu trả lời sai được thuật toán tự động lấy từ các từ vựng khác trong cùng chủ đề để tạo bài Quiz 4 lựa chọn. |
| **Resumable Attempt (Phiên học dở dang)** | Trạng thái lưu tạm vị trí thẻ/câu hỏi khi học viên rời trang giữa chừng để tiếp tục học lại sau (Bảng `learning_attempts`). |
