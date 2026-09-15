<?php
require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");
require_once($_SERVER['DOCUMENT_ROOT'] . "/includes/database_objects.php");

// KIỂM TRA PHIÊN NGƯỜI DÙNG
session_start();

$isLoggedIn = isset($_SESSION['user_id']);

// Xác định trang hiện tại để sidebar tự động active
$currentPage = basename($_SERVER['PHP_SELF']);

// ==========================================
// 1. LẤY TOPIC ID TỪ URL
// ==========================================

$topic_id = isset($_GET['topicID']) ? (int) $_GET['topicID'] : 0;

if ($topic_id <= 0) {
    die("Topic không hợp lệ.");
}


// ==========================================
// 2. LẤY THÔNG TIN TOPIC
// ==========================================

$topicRows = dbSelectView($link, 'SELECT * FROM vw_topic_catalog WHERE topicID = ? LIMIT 1', 'i', [$topic_id]);
$topic = $topicRows[0] ?? null;

if (!$topic) {
    die("Không tìm thấy chủ đề.");
}


// ==========================================
// 3. ĐẾM SỐ LƯỢNG TỪ VỰNG
// ==========================================

$word_count = (int) $topic['word_count'];


// ==========================================
// 4. LẤY DANH SÁCH VOCABULARY
// ==========================================

$vocab_result = dbSelectView(
    $link,
    'SELECT id, word, pronunciation, part_of_speech, meaning, example_sentence, audio_url
     FROM vw_vocabulary_catalog WHERE topic_id = ? ORDER BY id',
    'i',
    [$topic_id]
);

?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Danh sách từ vựng</title>

    <!-- Link file CSS dùng chung -->
    <link rel="stylesheet" type="text/css" href="../../CSS/Style.css">
    <link rel="stylesheet" type="text/css" href="../../CSS/topheader.css">
    <link rel="stylesheet" type="text/css" href="../../CSS/B_DanhSachTuVung.css">
    <link rel="stylesheet" type="text/css" href="../../CSS/responsive.css">
    <link rel="stylesheet" type="text/css" href="../../CSS/guest-preview.css">
    <!-- <link rel="icon" type="image/x-icon" href="../../favicon.ico"> -->
</head>

<body>
    <div class="layout-wrapper">

        <!-- SIDEBAR -->
        <?php

        if ($isLoggedIn) {
            include '../../includes/sidebar_user.php';
        } else {
            include '../../includes/sidebar_guest.php';
        }

        ?>

        <!-- MAIN CONTENT -->
        <main class="main-content">
            <!-- Header dùng chung: tự đổi nội dung theo trạng thái session. -->
            <?php
            $headerTitle = 'Chủ đề';
            include '../../includes/topheader.php';
            ?>

            <?php if (!$isLoggedIn): ?>
                <?php
                $guestInviteTitle = 'Bạn đang xem danh sách từ công khai';
                $guestInviteMessage = 'Hãy đăng nhập để đưa chủ đề này vào Góc rèn luyện, học bằng Flashcard và theo dõi kết quả Quiz.';
                include '../../includes/guest_invite.php';
                ?>
            <?php endif; ?>

            <div class="content-area">

                <section class="topic-header">

                    <!-- PHP đổ dữ liệu từ DB -->
                    <div class="topic-info">

                        <h2 class="topic-name">
                            Chủ đề:
                            <span class="dynamic-text">
                                <?= htmlspecialchars($topic['topicName']) ?>
                            </span>
                        </h2>

                        <p class="topic-count">
                            Số lượng:
                            <span class="dynamic-badge">
                                <?= $word_count ?>
                            </span>
                            từ vựng
                        </p>

                    </div>

                </section>

                <!-- THANH TÌM KIẾM VÀ LỌC  -->
                <div class="vocabs-toolbar">
                    <div class="vocabs-search">
                        <span class="search-icon" aria-hidden="true">🔍</span>
                        <input type="search" id="vocab-search" name="vocab-search" class="search-input"
                            placeholder="Tìm kiếm từ vựng hoặc nghĩa...">
                    </div>

                    <!-- LỌC -->
                    <div class="vocab-filter">
                        <select name="vocab-filter" id="vocab-filter-select">
                            <option value="all">Tất cả từ vựng</option>
                            <option value="vocab-new">Từ mới(Chưa học)</option>
                            <option value="vocab-review">Từ đang ôn tập</option>
                        </select>
                    </div>
                </div>

                <section class="vocab-card">
                    <!-- Bảng danh sách từ vựng -->
                    <table class="vocab-table">
                        <thead>
                            <tr>
                                <th class="col-word">TỪ VỰNG & LOẠI</th>
                                <th class="col-meaning">NGHĨA</th>
                                <th class="col-ipa">PHIÊN ÂM</th>
                                <th class="col-example">CÂU VÍ DỤ</th>
                            </tr>
                        </thead>

                        <tbody id="vocab-data-body" class="vocab-data">

                            <!-- SỬ DỤNG PHP ĐỔ DỮ LIỆU TỪ DATABASE VÀO -->
                            <?php if ($vocab_result): ?>

                                <?php foreach ($vocab_result as $vocab): ?>

                                    <tr>

                                        <td class="col-word">

                                            <div class="word-info">

                                                <div class="word-img-placeholder">
                                                    IMG
                                                </div>

                                                <div class="word-details">

                                                    <!-- Tên từ vựng -> php -->
                                                    <span class="word-name">
                                                        <?= htmlspecialchars($vocab['word']) ?>
                                                    </span>

                                                    <!-- Loại từ vựng -> php -->
                                                    <span class="word-type">
                                                        <?= htmlspecialchars($vocab['part_of_speech'] ?? '') ?>
                                                    </span>

                                                </div>

                                            </div>

                                        </td>

                                        <!-- Nghĩa từ vựng -> php -->
                                        <td class="col-meaning">
                                            <?= htmlspecialchars($vocab['meaning']) ?>
                                        </td>

                                        <!-- Phát âm từ vựng -> php -->
                                        <td class="col-ipa">
                                            <?= htmlspecialchars($vocab['pronunciation'] ?? '') ?>
                                        </td>

                                        <!-- Câu ví dụ về từ vựng -> php -->
                                        <td class="col-example">

                                            <div class="ex-en">
                                                <?= htmlspecialchars($vocab['example_sentence'] ?? '') ?>
                                            </div>

                                        </td>

                                    </tr>

                                <?php endforeach; ?>

                            <?php else: ?>

                                <tr>
                                    <td colspan="4">
                                        Chủ đề này chưa có từ vựng.
                                    </td>
                                </tr>

                            <?php endif; ?>

                        </tbody>
                    </table>
                </section>
            </div>
        </main>
    </div>

    <script src="../../JS/jquery-4.0.0.min.js"></script>
    <script src="../../JS/B_DanhSachTuVung.js"></script>
    <script src="../../JS/auth.js"></script>

</body>

</html>
