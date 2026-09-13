<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");

// KIỂM TRA PHIÊN NGƯỜI DÙNG
session_start();

$isLoggedIn = isset($_SESSION['user_id']);

$sql = "SELECT 
        t.topicID,
        t.topicName,
        t.topicDescription,
        t.category,
        COUNT(v.id) AS word_count
    FROM Topics t
    LEFT JOIN vocabulary v
        ON t.topicID = v.topic_id
    GROUP BY
        t.topicID,
        t.topicName,
        t.topicDescription,
        t.category
    ORDER BY t.topicID ASC
";

$result = mysqli_query($link, $sql);

if (!$result) {
    die("Lỗi truy vấn: " . mysqli_error($link));
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Danh sách chủ đề</title>

    <link rel="stylesheet" type="text/css" href="../../CSS/Style.css">
    <link rel="stylesheet" type="text/css" href="../../CSS/B_DanhSachChuDe.css">
    <link rel="stylesheet" href="../../CSS/topheader.css">
    <link rel="stylesheet" type="text/css" href="../../CSS/responsive.css">
    <link rel="stylesheet" href="../../CSS/auth-required.css">
    <link rel="stylesheet" href="../../CSS/guest-preview.css">
    <!-- <link rel="icon" type="image/x-icon" href="../../favicon.ico"> -->
</head>

<body>

    <!-- SIDEBAR -->
    <?php

    if ($isLoggedIn) {
        include '../../includes/sidebar_user.php';
    } else {
        include '../../includes/sidebar_guest.php';
    }
    ?>

    <main class="page-content">

        <!-- Header dùng chung: tự đổi nội dung theo trạng thái session. -->
        <?php
        $headerTitle = 'Chủ đề';
        include '../../includes/topheader.php';
        ?>

        <?php if (!$isLoggedIn): ?>
            <?php
            $guestInviteTitle = 'Khám phá kho chủ đề miễn phí';
            $guestInviteMessage = 'Bạn có thể xem chủ đề và danh sách từ vựng công khai. Đăng nhập để học Flashcard, làm Quiz và lưu tiến độ cá nhân.';
            include '../../includes/guest_invite.php';
            ?>
        <?php endif; ?>

        <section class="topics-header">
            <div class="topics-header-content">

                <span class="topics-eyebrow">
                    — KHÁM PHÁ
                </span>
                <h1>Khám phá các chủ đề </h1>

                <p>
                    Hãy cùng khám phá các chủ đề từ vựng tiếng Anh thú vị và đầy mới mẻ
                </p>
            </div>

            <!-- THANH TÌM KIẾM VÀ LỌC -->
            <div class="topics-toolbar">
                <!-- Tìm kiếm -->
                <div class="topics-search">
                    <span class="search-icon" aria-hidden="true">🔍</span>
                    <input type="search" id="topic-search" name="topic-search" class="search-input"
                        placeholder="Tìm kiếm chủ đề...">
                </div>

                <!-- Lọc -->
                <div class="topic-filter">

                    <select id="topic-filter" name="topic-filter">
                        <option value="all">Tất cả</option>
                        <option value="Ielts">IELTS</option>
                        <option value="Toiec">TOEIC</option>
                        <option value="common">Common</option>
                    </select>

                </div>

            </div>
        </section>

        <section class="topic-section">
            <div class="topic-list">

                <!-- CÁC TOPIC  -->
                <!-- SỬ DỤNG PHP ĐỔ DỮ LIỆU DB VÀO -->
                <?php while ($topic = mysqli_fetch_assoc($result)): ?>

                    <article
                        class="topic-card"
                        data-category="<?= htmlspecialchars($topic['category']) ?>">

                        <div class="topic-icon">
                            <img
                                src="../../assets/icons/book-open-svgrepo-com.svg"
                                alt="book">
                        </div>

                        <!-- NỘI DUNG -->
                        <div class="topic-content">

                            <h3 class="topic-title">
                                <?= htmlspecialchars($topic['topicName']) ?>
                            </h3>

                            <!-- THÔNG TIN TOPIC -->
                            <div class="topic-meta">
                                <span class="topic-count">
                                    <?= $topic['word_count'] ?> từ vựng
                                </span>

                                <a
                                    class="topic-list-link"
                                    href="./B_DanhSachTuVung.php?topicID=<?= $topic['topicID'] ?>">
                                    Danh sách
                                </a>
                            </div>

                            <!-- 2 NÚT HÀNH ĐỘNG -->
                            <div class="topic-actions">

                                <!-- =========================================
                                    NÚT HỌC TỪ MỚI
                                    - User: chọn Flashcard hoặc Quiz tại Góc rèn luyện.
                                    - Guest: mở modal yêu cầu đăng nhập.
                                ========================================= -->
                                <a
                                    class="topic-action topic-action-learn"
                                    href="<?= $isLoggedIn
                                                ? '../user/C_Gocrenluyen.php?source=topic&id=' . (int) $topic['topicID']
                                                : '../auth/A_DangNhap.php' ?>"

                                    <?php if (!$isLoggedIn): ?>
                                    data-requires-auth
                                    data-feature-title=""
                                    data-feature-benefits="Lưu tiến độ học tập|Tạo lịch ôn SRS cá nhân|Theo dõi kết quả từng chủ đề"
                                    <?php endif; ?>>

                                    <span class="topic-action-number">
                                        <?= $topic['word_count'] ?>
                                    </span>

                                    <span class="topic-action-label">
                                        HỌC TỪ MỚI
                                    </span>
                                </a>

                                <!-- =========================================
                                    NÚT ÔN TẬP
                                    - User: đi đến Flashcard ở chế độ review.
                                    - Guest: mở modal yêu cầu đăng nhập.
                                    
                                    Dùng C_HocFlashcard.php?mode=review vì file này
                                    đã có auth_guard.php và truy vấn đúng user_id.
                                ========================================= -->
                                <a
                                    class="topic-action topic-action-review"
                                    href="<?= $isLoggedIn
                                                ? '../user/C_HocFlashcard.php?mode=review'
                                                : '../auth/A_DangNhap.php' ?>"

                                    <?php if (!$isLoggedIn): ?>
                                    data-requires-auth
                                    data-feature-title=""
                                    data-feature-benefits="Ôn từ đến hạn theo lịch SRS|Lưu kết quả ôn tập|Cải thiện trí nhớ lâu dài"
                                    <?php endif; ?>>

                                    <span class="topic-action-number">
                                        0
                                    </span>

                                    <span class="topic-action-label">
                                        ÔN TẬP
                                    </span>
                                </a>

                            </div>

                        </div>

                    </article>

                <?php endwhile; ?>

            </div>

            <!-- PHÂN TRANG -->
            <nav class="pagination" aria-label="Phân trang danh sách chủ đề">

                <!-- TRANG TRƯỚC -->
                <a href="#" class="pagination-button pagination-arrow" aria-label="Trang trước"> &laquo; </a>

                <!-- SỐ TRANG -->
                <a href="#" class="pagination-number active" aria-current="page"> 1 </a>

                <a href="#" class="pagination-number"> 2 </a>

                <a href="#" class="pagination-number"> 3 </a>

                <!-- TRANG SAU -->
                <a href="#" class="pagination-button pagination-arrow" aria-label="Trang sau"> &raquo; </a>

            </nav>
        </section>
    </main>

    <script src="../../JS/jquery-4.0.0.min.js"></script>
    <script src="../../JS/B_DanhSachChuDe.js"></script>
    <script src="../../JS/auth.js"></script>
    <script src="../../JS/auth-required.js"></script>
</body>

</html>
