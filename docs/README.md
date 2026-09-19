# 📚 LexiLoop Documentation Hub
> Hệ thống Tài liệu Đặc tả Kỹ thuật, Kiến trúc, Cơ sở Dữ liệu và Báo cáo Đồ án Hệ thống Học Từ vựng Tiếng Anh Thông minh (Spaced Repetition System - SRS).

---

## 🧭 Cấu trúc Tài liệu Dự án

Tài liệu được chuẩn hóa theo mô hình phân tầng kỹ thuật chuyên nghiệp (tương tự chuẩn công nghiệp và chuẩn đồ án AURA):

```text
docs/
├── README.md                                    # Bản đồ điều hướng tài liệu tổng quan
├── CHECKLIST.md                                 # Danh mục kiểm tra tiến độ, tính năng và tuân thủ
├── CHANGELOG.md                                 # Nhật ký phiên bản và nâng cấp hệ thống
├── CONTRIBUTING.md                              # Hướng dẫn đóng góp mã nguồn và quy chuẩn code
├── HANDOVER.md                                  # Hướng dẫn bàn giao, tài khoản mặc định và vận hành
├── AUDIT_REPORT.md                              # Báo cáo kiểm định toàn diện (SRS, CSDL, Code, Bảo mật)
├── SOURCE_CODE_DEEP_ANALYSIS_REPORT.md          # Báo cáo phân tích chuyên sâu mã nguồn dự án
├── REAL_WORLD_PRODUCTION_READINESS_REPORT.md    # Đánh giá mức độ sẵn sàng triển khai thực tế
├── VIVA_DEFENSE_CHECKLIST.md                    # Sổ tay câu hỏi & trả lời bảo vệ đồ án (Viva Defense)
├── FR_1_TO_30_VERIFICATION_REPORT.md            # Báo cáo xác minh 30+ yêu cầu chức năng (FR-01 -> FR-30)
├── KE_HOACH_THUC_HIEN_DO_AN.md                  # Kế hoạch thực hiện đồ án, timeline và phân công
├── frontend-checklist.md                        # Checklist kiểm tra chất lượng giao diện Frontend
├── page-list.md                                 # Danh mục tất cả các trang giao diện (Routing & UI)
├── sitemap.md                                   # Sơ đồ điều hướng website (Sitemap)
│
├── 01-requirements/                            # Đặc tả Yêu cầu Nghiệp vụ & Phạm vi
│   ├── mvp-scope.md                            # Phạm vi MVP & Kế hoạch phát triển
│   ├── software-requirements-specification.md  # Đặc tả yêu cầu phần mềm chuẩn IEEE 830 (SRS)
│   ├── traceability-matrix.md                  # Ma trận truy vết yêu cầu (Traceability Matrix)
│   └── user-requirements.md                    # User Stories & Tiêu chí nghiệm thu (Acceptance Criteria)
│
├── 02-analysis/                                # Phân tích Miền nghiệp vụ & Thuật ngữ
│   ├── README.md                               # Tổng quan phân tích hệ thống
│   └── VOCABULARY_AND_SRS_TERMINOLOGY_DICTIONARY.md # Từ điển thuật ngữ SRS, Ngôn ngữ học & CEFR
│
├── 03-architecture/                            # Thiết kế Kiến trúc Hệ thống
│   ├── architecture-design-document.md         # Tài liệu thiết kế kiến trúc tổng thể (MVC + REST API)
│   ├── system-context.md                       # Sơ đồ ngữ cảnh C4 (Context & Container Diagram)
│   ├── security-design.md                      # Thiết kế bảo mật, RBAC, Auth Guard & XSS/CSRF
│   ├── architecture-blueprint-role-matrix.md   # Ma trận phân quyền 3 vai trò (Guest - User - Admin)
│   ├── sdd-spaced-repetition-and-adaptive-scheduler.md # Đặc tả thuật toán Lặp lại ngắt quãng SRS
│   ├── sdd-flashcard-and-quiz-engine.md        # Thiết kế bộ máy học Flashcard & tạo bài Quiz
│   └── sdd-resumable-learning-sessions-and-sync.md # Thiết kế cơ chế lưu & tiếp tục phiên học
│
├── 04-database/                                # Thiết kế Cơ sở Dữ liệu
│   ├── README.md                               # Hướng dẫn CSDL & quy trình migration
│   ├── database-design.md                      # Mô hình ERD, quan hệ thực thể & chuẩn hóa 3NF
│   ├── database-design-document.md             # Đặc tả chi tiết bảng, chỉ mục, view và trigger
│   └── data-dictionary.md                      # Từ điển dữ liệu chi tiết từng trường (Data Dictionary)
│
├── 05-api/                                     # Đặc tả Giao diện Lập trình Ứng dụng (API)
│   ├── README.md                               # Quy chuẩn kết nối API & xác thực
│   ├── backend-api.md                          # Danh mục Endpoints & Request/Response Samples
│   ├── api-specification-document.md           # Đặc tả API chi tiết chuẩn OpenAPI / Swagger
│   └── error-codes.md                          # Bảng mã lỗi chuẩn hóa & HTTP Status Codes
│
├── 07-testing/                                 # Kiểm thử & Đảm bảo Chất lượng (QA)
│   ├── README.md                               # Tổng quan kiểm thử hệ thống
│   ├── test-plan.md                            # Kế hoạch kiểm thử tổng thể (Master Test Plan)
│   ├── test-plan-and-test-cases.md             # Bộ Test Cases chi tiết (TC-01 -> TC-50)
│   ├── qa-master-signoff-report.md             # Báo cáo nghiệm thu chất lượng QA
│   ├── security-privacy-comprehensive-audit-report.md # Báo cáo kiểm định an toàn & bảo mật
│   ├── independent-code-review-report.md       # Báo cáo Review mã nguồn độc lập
│   ├── code-review-clean-ui-and-frontend.md    # Đánh giá UI/UX và độ tương thích Responsive
│   └── security-audit-signoff-idor-and-auth-guard.md # Báo cáo kiểm tra chống IDOR và Auth Guard
│
├── 08-deployment/                              # Triển khai & Vận hành
│   ├── environment-variables.md                # Cấu hình biến môi trường & Database Config
│   └── installation-and-user-manual.md         # Hướng dẫn cài đặt (XAMPP/Docker) & Sổ tay HDSD
│
├── 08-report-prep/                             # Hồ sơ Báo cáo Đồ án & Ôn tập
│   ├── BAN_DO_SOURCE.md                        # Bản đồ chi tiết toàn bộ file mã nguồn
│   ├── BAO_CAO_DU_AN.md                        # Báo cáo tổng kết đồ án hoàn chỉnh (Chuẩn mẫu học thuật)
│   └── ON_TAP_NHANH.md                         # Sổ tay tóm tắt phản xạ nhanh khi bảo vệ đồ án
│
└── adr/                                        # Architecture Decision Records (Quyết định kiến trúc)
    ├── README.md                               # Danh mục quyết định kiến trúc
    ├── ADR-001-mvc-monolith-with-rest-api-hybridation.md # Quyết định kiến trúc MVC + REST API
    ├── ADR-002-spaced-repetition-sm2-adaptive-scheduler.md # Quyết định thuật toán SRS (SM-2 tinh chỉnh)
    ├── ADR-003-unified-topic-vocabulary-rbac-ownership.md # Quyết định quản lý quyền sở hữu dữ liệu
    └── ADR-004-resumable-learning-attempts-and-progress-tracking.md # Quyết định lưu trữ phiên học dở dang
```

---

## 🎯 Tóm tắt Nhanh về Hệ thống LexiLoop

1. **Mục tiêu**: Nền tảng học và ghi nhớ từ vựng tiếng Anh ứng dụng thuật toán **Spaced Repetition (Lặp lại ngắt quãng)** dựa trên nguyên lý đường cong lãng quên Ebbinghaus.
2. **Đối tượng người dùng**:
   - **Guest (Khách)**: Xem danh mục công khai, làm quen hệ thống, trải nghiệm học thử tối đa 5 từ.
   - **User (Học viên)**: Học flashcard, làm quiz trắc nghiệm, quản lý bộ từ vựng cá nhân, theo dõi lịch ôn tập hàng ngày, xem biểu đồ tiến độ.
   - **Admin (Quản trị viên)**: Quản lý danh mục chủ đề chuẩn của hệ thống, quản trị kho từ vựng gốc, quản lý danh sách tài khoản, theo dõi thống kê học tập toàn hệ thống.
3. **Công nghệ cốt lõi**:
   - **Giao diện**: HTML5, CSS3, JavaScript ES6, jQuery, Responsive layout.
   - **Phía máy chủ**: PHP Native (mô hình MVC tinh gọn) kết hợp kiến trúc RESTful API; Module mở rộng Java Spring Boot (`be/`).
   - **Cơ sở dữ liệu**: MySQL 8.x / MariaDB với chuẩn hóa 3NF, bảo vệ toàn vẹn bằng Foreign Keys & Transactions.
   - **Bảo mật**: Password Hash Bcrypt, Session Guard, Prepared Statements chống SQL Injection, Sanitization chống XSS, RBAC chống IDOR.
