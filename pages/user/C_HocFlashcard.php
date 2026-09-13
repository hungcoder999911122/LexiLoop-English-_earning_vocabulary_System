<?php
require_once '../../includes/auth_guard.php';
require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");

// auth_guard.php đã xác thực session trước khi trang sử dụng user_id.
$user_id = (int) $_SESSION['user_id'];
if (empty($_SESSION['C_learning_csrf'])) {
    $_SESSION['C_learning_csrf'] = bin2hex(random_bytes(32));
}
$source = $_GET['source'] ?? 'topic';
$source = in_array($source, ['topic', 'set'], true) ? $source : 'topic';
$source_id = filter_var($_GET['id'] ?? $_GET['topic_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$limit_option = (string) ($_GET['limit'] ?? '10');
if (!in_array($limit_option, ['5', '10', '20', 'all'], true)) {
    $limit_option = '10';
}

// Đồng bộ biến kết nối DB
if (isset($link) && !isset($conn)) {
    $conn = $link;
}
// Nguồn topic/set bắt buộc có ID hợp lệ; không tự ý rơi sang topic mặc định.
$id_chu_de = $source === 'topic' ? $source_id : 0;
$mode = $_GET['mode'] ?? ''; // Chế độ: 'review' (ôn tập) hoặc học theo chủ đề
if ($mode !== 'review' && $source_id <= 0) {
    header('Location: C_Gocrenluyen.php');
    exit;
}
$ten_chu_de = ($mode === 'review') ? "Từ vựng cần ôn tập" : "Chủ đề học";
$danh_sach_tu = [];

try {
    if (isset($link) && $link) {
        // Quét danh sách bảng thực tế tránh lỗi hoa/thường trên Docker
        $tables_res = @mysqli_query($link, "SHOW TABLES");
        $db_tables = [];
        if ($tables_res) {
            while ($tbl_row = mysqli_fetch_array($tables_res)) {
                $db_tables[strtolower($tbl_row[0])] = $tbl_row[0];
            }
        }

        $tbl_vocab    = isset($db_tables['vocabulary']) ? "`" . $db_tables['vocabulary'] . "`" : "`vocabulary`";
        $tbl_topics   = isset($db_tables['topics']) ? "`" . $db_tables['topics'] . "`" : "`Topics`";
        $tbl_progress = isset($db_tables['user_vocab_progress']) ? "`" . $db_tables['user_vocab_progress'] . "`" : "`user_vocab_progress`";
        $tbl_sets     = isset($db_tables['vocabulary_sets']) ? "`" . $db_tables['vocabulary_sets'] . "`" : "`vocabulary_sets`";
        $tbl_set_items = isset($db_tables['vocabulary_set_items']) ? "`" . $db_tables['vocabulary_set_items'] . "`" : "`vocabulary_set_items`";

        // Lấy tên chủ đề
        if ($mode !== 'review' && $source === 'set') {
            // Bộ từ cá nhân bắt buộc thuộc tài khoản đang đăng nhập.
            $sql_set = "SELECT name FROM $tbl_sets WHERE id = ? AND user_id = ? LIMIT 1";
            $stmt = mysqli_prepare($link, $sql_set);
            mysqli_stmt_bind_param($stmt, 'ii', $source_id, $user_id);
            mysqli_stmt_execute($stmt);
            $set_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));
            mysqli_stmt_close($stmt);
            if (!$set_row) {
                header('Location: C_Gocrenluyen.php');
                exit;
            }
            $ten_chu_de = $set_row['name'];
        } elseif ($mode !== 'review' && isset($db_tables['topics'])) {
            $sql_topic = "SELECT topicName FROM $tbl_topics WHERE topicID = ? LIMIT 1";
            if ($stmt = @mysqli_prepare($link, $sql_topic)) {
                mysqli_stmt_bind_param($stmt, "i", $id_chu_de);
                mysqli_stmt_execute($stmt);
                $res = mysqli_stmt_get_result($stmt);
                if ($row = mysqli_fetch_assoc($res)) {
                    $ten_chu_de = $row['topicName'];
                } else {
                    mysqli_stmt_close($stmt);
                    header('Location: C_Gocrenluyen.php');
                    exit;
                }
                mysqli_stmt_close($stmt);
            }
        }

        // Lấy danh sách từ vựng theo ID người dùng đang đăng nhập
        if (isset($db_tables['vocabulary'])) {
            if ($mode === 'review' && isset($db_tables['user_vocab_progress'])) {
                // Chế độ ôn tập: Từ vựng đến hạn của người dùng
                $sql_words = "
                    SELECT 
                        v.id,
                        v.word AS tu_vung,
                        v.meaning AS nghia,
                        v.pronunciation AS phien_am,
                        v.part_of_speech AS loai_tu,
                        v.example_sentence AS vi_du,
                        v.audio_url,
                        p.next_review_date
                    FROM $tbl_vocab v
                    INNER JOIN $tbl_progress p ON v.id = p.vocabulary_id
                    WHERE p.user_id = ? AND p.next_review_date <= CURDATE()
                    ORDER BY p.next_review_date ASC
                ";
                $stmt = @mysqli_prepare($link, $sql_words);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "i", $user_id);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    while ($row = mysqli_fetch_assoc($result)) {
                        $danh_sach_tu[] = [
                            "id"        => (int)$row['id'],
                            "tu_vung"   => $row['tu_vung'],
                            "nghia"     => $row['nghia'],
                            "phien_am"  => $row['phien_am'] ?? '',
                            "loai_tu"   => $row['loai_tu'] ?? '',
                            "vi_du"     => $row['vi_du'] ?? '',
                            "audio_url" => $row['audio_url'] ?? '',
                            "is_review" => true
                        ];
                    }
                    mysqli_stmt_close($stmt);
                }
            } elseif ($source === 'set') {
                // Chỉ lấy từ thuộc bộ cá nhân đã được kiểm tra quyền sở hữu ở trên.
                $sql_words = "
                    SELECT v.id, v.word AS tu_vung, v.meaning AS nghia,
                           v.pronunciation AS phien_am, v.part_of_speech AS loai_tu,
                           v.example_sentence AS vi_du, v.audio_url, p.next_review_date
                    FROM $tbl_set_items vsi
                    INNER JOIN $tbl_sets vs ON vs.id = vsi.vocabulary_set_id AND vs.user_id = ?
                    INNER JOIN $tbl_vocab v ON v.id = vsi.vocabulary_id
                    LEFT JOIN $tbl_progress p ON p.vocabulary_id = v.id AND p.user_id = ?
                    WHERE vsi.vocabulary_set_id = ?
                    ORDER BY vsi.display_order ASC, vsi.id ASC
                ";
                $stmt = mysqli_prepare($link, $sql_words);
                mysqli_stmt_bind_param($stmt, 'iii', $user_id, $user_id, $source_id);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $today = date('Y-m-d');
                while ($row = mysqli_fetch_assoc($result)) {
                    $danh_sach_tu[] = [
                        'id' => (int) $row['id'],
                        'tu_vung' => $row['tu_vung'],
                        'nghia' => $row['nghia'],
                        'phien_am' => $row['phien_am'] ?? '',
                        'loai_tu' => $row['loai_tu'] ?? '',
                        'vi_du' => $row['vi_du'] ?? '',
                        'audio_url' => $row['audio_url'] ?? '',
                        'is_review' => !empty($row['next_review_date']) && $row['next_review_date'] <= $today
                    ];
                }
                mysqli_stmt_close($stmt);
            } else {
                // Chế độ học từ mới theo Chủ đề
                $sql_words = "
                    SELECT 
                        v.id,
                        v.word AS tu_vung,
                        v.meaning AS nghia,
                        v.pronunciation AS phien_am,
                        v.part_of_speech AS loai_tu,
                        v.example_sentence AS vi_du,
                        v.audio_url,
                        p.next_review_date
                    FROM $tbl_vocab v
                    LEFT JOIN $tbl_progress p ON v.id = p.vocabulary_id AND p.user_id = ?
                    WHERE v.topic_id = ?
                    ORDER BY v.id ASC
                ";
                $stmt = @mysqli_prepare($link, $sql_words);
                if ($stmt) {
                    mysqli_stmt_bind_param($stmt, "ii", $user_id, $id_chu_de);
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    $today = date('Y-m-d');
                    while ($row = mysqli_fetch_assoc($result)) {
                        $is_review = (!empty($row['next_review_date']) && $row['next_review_date'] <= $today);
                        $danh_sach_tu[] = [
                            "id"        => (int)$row['id'],
                            "tu_vung"   => $row['tu_vung'],
                            "nghia"     => $row['nghia'],
                            "phien_am"  => $row['phien_am'] ?? '',
                            "loai_tu"   => $row['loai_tu'] ?? '',
                            "vi_du"     => $row['vi_du'] ?? '',
                            "audio_url" => $row['audio_url'] ?? '',
                            "is_review" => $is_review
                        ];
                    }
                    mysqli_stmt_close($stmt);
                }
            }
        }
    }
} catch (\Throwable $e) {
    error_log("Lỗi Flashcard: " . $e->getMessage());
}

if ($limit_option !== 'all') {
    $danh_sach_tu = array_slice($danh_sach_tu, 0, (int) $limit_option);
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Học FlashCard - LexiLoop</title>
    <link rel="stylesheet" href="../../CSS/Style.css">
    <link rel="stylesheet" href="../../CSS/C_HocFlashcard.css">

    <link rel="stylesheet" href="../../CSS/responsive.css">
    <!-- <link rel="stylesheet" href="../../CSS/topheader.css"> -->
</head>

<body class="C_HocFlashcard_body">

    <!-- Header -->
    <header class="C_HocFlashcard_header">

        <h1 class="C_HocFlashcard_logo">
            <?php echo $mode === 'review'
                ? 'Ôn tập Flashcard'
                : 'Học từ mới: ' . htmlspecialchars($ten_chu_de); ?>
        </h1>

        <span class="C_HocFlashcard_progressText" id="C_HocFlashcard_progressText">Thẻ 1/5</span>
    </header>

    <!-- Thanh tiến độ học -->
    <div class="C_HocFlashcard_progressBarWrapper">
        <div class="C_HocFlashcard_progressBar">
            <div class="C_HocFlashcard_progressFill" id="C_HocFlashcard_progressFill"></div>
        </div>
    </div>

    <!-- Khu vực thẻ học và điều hướng -->
    <main class="C_HocFlashcard_main">
        <div class="C_HocFlashcard_cardArea">
            <!-- Nút từ trước -->
            <button type="button" id="C_HocFlashcard_btnPrev" class="C_HocFlashcard_navBtn" title="Thẻ trước">&larr;</button>

            <!-- Hộp thẻ Flashcard -->
            <div class="C_HocFlashcard_cardBox" id="C_HocFlashcard_cardBox">
                <!-- Badge R (Ôn tập - Review) -->
                <div class="C_HocFlashcard_badgeR" id="C_HocFlashcard_badgeR" title="Thẻ cần ôn tập">R</div>

                <h2 class="C_HocFlashcard_word" id="C_HocFlashcard_word">Software</h2>

                <!-- Thông tin phát âm đặt bên dưới Flashcard -->
                <div
                    class="C_HocFlashcard_pronunciationArea"
                    id="C_HocFlashcard_pronunciationArea">

                    <p
                        class="C_HocFlashcard_pronunciation"
                        id="C_HocFlashcard_pronunciation">
                        /.../
                    </p>

                    <button
                        type="button"
                        class="C_HocFlashcard_audioButton"
                        id="C_HocFlashcard_audioButton"
                        hidden>
                        🔊 Nghe phát âm
                    </button>

                    <audio
                        id="C_HocFlashcard_audioPlayer"
                        preload="none">
                    </audio>
                </div>
                <p class="C_HocFlashcard_hint" id="C_HocFlashcard_hint">Nhấn để xem nghĩa</p>
            </div>

            <!-- Nút từ tiếp theo -->
            <button type="button" id="C_HocFlashcard_btnNext" class="C_HocFlashcard_navBtn" title="Thẻ tiếp theo">&rarr;</button>
        </div>

        <!-- 2 Nút Đánh Giá -->
        <!-- aria-pressed giúp trình duyệt và công cụ hỗ trợ biết trạng thái nút đang được chọn. Đúng chuẩn accessibility. -->
        <div class="C_HocFlashcard_btnGroup">
            <button
                type="button"
                id="C_HocFlashcard_btnChuaNho"
                class="C_HocFlashcard_btn C_HocFlashcard_btnWhite"
                data-status="chua_nho"
                aria-pressed="false">
                Chưa nhớ
            </button>

            <button
                type="button"
                id="C_HocFlashcard_btnDaNho"
                class="C_HocFlashcard_btn C_HocFlashcard_btnGray"
                data-status="da_nho"
                aria-pressed="false">
                Đã nhớ
            </button>
        </div>
    </main>

    <!-- Footer thống kê và Kết thúc sớm -->
    <footer class="C_HocFlashcard_footerWrapper">
        <div class="C_HocFlashcard_footerBox">
            <div class="C_HocFlashcard_stats" id="C_HocFlashcard_stats">
                Đã học: 0 &bull; Đã nhớ: 0 &bull; Chưa nhớ: 0
            </div>

            <button
                type="button"
                id="C_HocFlashcard_btnKetThuc"
                class="C_HocFlashcard_btnKetThuc">
                Kết thúc sớm
            </button>
        </div>
    </footer>

    <script>
        /*
         * Dữ liệu thẻ được PHP lấy từ database.
         * JavaScript chỉ dùng để hiển thị giao diện.
         */
        const flashcardsData = <?php
                                echo json_encode(
                                    $danh_sach_tu,
                                    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
                                );
                                ?>;

        /*
         * Metadata của phiên học.
         * topicId sẽ được dùng khi lưu phiên học và tạo Quiz.
         */
        const flashcardSessionConfig = <?php
                                        echo json_encode(
                                            [
                                                'topicId' => $id_chu_de,
                                                'source' => $mode === 'review' ? 'review' : $source,
                                                'sourceId' => $source_id,
                                                'limit' => $limit_option,
                                                'csrf' => $_SESSION['C_learning_csrf'],
                                                'topicName' => $ten_chu_de,
                                                'mode' => $mode === 'review' ? 'review' : 'new_learning'
                                            ],
                                            JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP
                                        );
                                        ?>;
    </script>
    <script src="../../JS/C_HocFlashcard.js"></script>
</body>

</html>
