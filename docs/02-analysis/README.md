# 🧠 LexiLoop Domain Analysis & Terminology

Phân vùng tài liệu phân tích miền nghiệp vụ, mô hình hóa khái niệm và từ điển thuật ngữ chuyên ngành ứng dụng trong LexiLoop.

---

## 📚 Danh mục Tài liệu Phân tích

1. **[VOCABULARY_AND_SRS_TERMINOLOGY_DICTIONARY.md](VOCABULARY_AND_SRS_TERMINOLOGY_DICTIONARY.md)**: Từ điển thuật ngữ chi tiết về Spaced Repetition (SRS), SuperMemo SM-2, Leitner Box, Ngôn ngữ học ứng dụng, Phiên âm quốc tế (IPA) và Khung tham chiếu Châu Âu (CEFR).

---

## 🎯 Bản đồ Khái niệm Nghiệp vụ Cốt lõi (Domain Concepts)

```text
[User (Học viên)] ─── sở hữu ───► [Personal Topics] ─── chứa ───► [Vocabulary]
      │                                                                  ▲
      ├─── tạo ───────────────► [Vocabulary Sets] ─── gom nhóm ──────────┤
      │                                                                  │
      ├─── học với SRS ───────► [user_vocab_progress] ─── tham chiếu ────┤
      │                                │                                 │
      │                                └─── sinh ra ──► [review_logs]    │
      │                                                                  │
      └─── làm bài kiểm tra ──► [quiz_results] ─── chi tiết ─────────────┘
                                       │
                                       └─── lưu vết ──► [quiz_answer_details]
```
