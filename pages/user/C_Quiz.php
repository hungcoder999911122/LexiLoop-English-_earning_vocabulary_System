<?php
require_once '../../includes/auth_guard.php';
require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");

$user_id = (int) $_SESSION['user_id'];
if (empty($_SESSION['C_learning_csrf'])) {
    $_SESSION['C_learning_csrf'] = bin2hex(random_bytes(32));
}
$source = $_GET['source'] ?? 'topic';
$source = in_array($source, ['topic', 'set', 'review'], true) ? $source : 'topic';
$source_id = filter_var($_GET['id'] ?? $_GET['topic_id'] ?? 0, FILTER_VALIDATE_INT) ?: 0;
$limit_option = (string) ($_GET['limit'] ?? '10');
if (!in_array($limit_option, ['5', '10', '20', 'all'], true)) {
    $limit_option = '10';
}

// Topic/set phải có ID thật; review dùng danh sách đến hạn của người dùng.
$id_chu_de = $source === 'topic' ? $source_id : 0;
if ($source !== 'review' && $source_id <= 0) {
    header('Location: C_Gocrenluyen.php');
    exit;
}

$ten_chu_de = $source === 'review' ? 'Từ vựng cần ôn tập' : 'Nguồn học';
$danh_sach_cau_hoi = [];

try {
    if (isset($link) && $link) {
        // Quét danh sách bảng thực tế tránh lỗi phân biệt hoa/thường trên Linux
        $tables_res = @mysqli_query($link, "SHOW TABLES");
        $db_tables = [];
        if ($tables_res) {
            while ($tbl_row = mysqli_fetch_array($tables_res)) {
                $db_tables[strtolower($tbl_row[0])] = $tbl_row[0];
            }
        }

        $tbl_vocab  = isset($db_tables['vocabulary']) ? "`" . $db_tables['vocabulary'] . "`" : "`vocabulary`";
        $tbl_topics = isset($db_tables['topics']) ? "`" . $db_tables['topics'] . "`" : "`Topics`";
        $tbl_sets = isset($db_tables['vocabulary_sets']) ? "`" . $db_tables['vocabulary_sets'] . "`" : "`vocabulary_sets`";
        $tbl_set_items = isset($db_tables['vocabulary_set_items']) ? "`" . $db_tables['vocabulary_set_items'] . "`" : "`vocabulary_set_items`";
        $tbl_progress = isset($db_tables['user_vocab_progress']) ? "`" . $db_tables['user_vocab_progress'] . "`" : "`user_vocab_progress`";

        // Lấy tên chủ đề
        if ($source === 'set') {
            $stmt_set = mysqli_prepare($link, "SELECT name FROM $tbl_sets WHERE id = ? AND user_id = ? LIMIT 1");
            mysqli_stmt_bind_param($stmt_set, 'ii', $source_id, $user_id);
            mysqli_stmt_execute($stmt_set);
            $set_row = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt_set));
            mysqli_stmt_close($stmt_set);
            if (!$set_row) {
                header('Location: C_Gocrenluyen.php');
                exit;
            }
            $ten_chu_de = $set_row['name'];
        } elseif ($source === 'topic' && isset($db_tables['topics'])) {
            $stmt_topic = @mysqli_prepare($link, "SELECT topicName FROM $tbl_topics WHERE topicID = ? LIMIT 1");
            if ($stmt_topic) {
                mysqli_stmt_bind_param($stmt_topic, "i", $id_chu_de);
                mysqli_stmt_execute($stmt_topic);
                $res_t = mysqli_stmt_get_result($stmt_topic);
                if ($row_t = mysqli_fetch_assoc($res_t)) {
                    $ten_chu_de = $row_t['topicName'];
                } else {
                    mysqli_stmt_close($stmt_topic);
                    header('Location: C_Gocrenluyen.php');
                    exit;
                }
                mysqli_stmt_close($stmt_topic);
            }
        }

        // Tự động sinh câu hỏi Quiz từ bảng vocabulary
        if (isset($db_tables['vocabulary'])) {
            // Lấy danh sách từ vựng thuộc chủ đề này (tối đa 10 câu)
            $topic_words = [];
            if ($source === 'set') {
                $word_sql = "SELECT v.id, v.word, v.meaning
                   FROM $tbl_set_items vsi
                   INNER JOIN $tbl_sets vs ON vs.id = vsi.vocabulary_set_id AND vs.user_id = ?
                   INNER JOIN $tbl_vocab v ON v.id = vsi.vocabulary_id
                   WHERE vsi.vocabulary_set_id = ? ORDER BY RAND()";
            } elseif ($source === 'review') {
                $word_sql = "SELECT v.id, v.word, v.meaning
                    FROM $tbl_vocab v
                    INNER JOIN $tbl_progress p ON p.vocabulary_id = v.id
                    WHERE p.user_id = ? AND p.next_review_date <= CURDATE()
                    ORDER BY p.next_review_date ASC";
            } else {
                $word_sql = "SELECT id, word, meaning FROM $tbl_vocab WHERE topic_id = ? ORDER BY RAND()";
            }
            $stmt_w = mysqli_prepare($link, $word_sql);
            if ($stmt_w) {
                if ($source === 'set') {
                    mysqli_stmt_bind_param($stmt_w, 'ii', $user_id, $source_id);
                } elseif ($source === 'review') {
                    mysqli_stmt_bind_param($stmt_w, 'i', $user_id);
                } else {
                    mysqli_stmt_bind_param($stmt_w, 'i', $id_chu_de);
                }
                mysqli_stmt_execute($stmt_w);
                $res_w = mysqli_stmt_get_result($stmt_w);
                while ($rw = mysqli_fetch_assoc($res_w)) {
                    $topic_words[] = $rw;
                }
                mysqli_stmt_close($stmt_w);
            }

            // Lấy kho đáp án sai (các nghĩa của từ ở chủ đề khác)
            $distractor_pool = [];
            $res_pool = @mysqli_query($link, "SELECT DISTINCT meaning FROM $tbl_vocab ORDER BY RAND() LIMIT 40");
            if ($res_pool) {
                while ($rp = mysqli_fetch_assoc($res_pool)) {
                    $distractor_pool[] = trim($rp['meaning']);
                }
            }

            // Gom thêm cả các nghĩa trong cùng chủ đề để làm phong phú đáp án gây nhiễu
            $all_topic_meanings = array_column($topic_words, 'meaning');

            // Tiến hành tạo 4 đáp án A, B, C, D cho từng câu hỏi
            $prefixes = ["A. ", "B. ", "C. ", "D. "];

            foreach ($topic_words as $item) {
                $correct_meaning = trim($item['meaning']);
                
                // Lấy các đáp án sai tiềm năng (khác với đáp án đúng)
                $wrong_candidates = array_filter(
                    array_merge($all_topic_meanings, $distractor_pool), 
                    function($m) use ($correct_meaning) {
                        return trim($m) !== $correct_meaning && !empty($m);
                    }
                );
                $wrong_candidates = array_unique($wrong_candidates);
                shuffle($wrong_candidates);

                // Lấy 3 đáp án sai
                $wrong_answers = array_slice($wrong_candidates, 0, 3);

                // Nếu không đủ 3 đáp án sai, bổ sung thêm đáp án mẫu
                $fallbacks = ["Không xác định", "Phương án khác", "Đáp án khác"];
                while (count($wrong_answers) < 3) {
                    $wrong_answers[] = array_shift($fallbacks);
                }

                // Gộp đáp án đúng và 3 đáp án sai rồi xáo trộn vị trí
                $options = array_merge([$correct_meaning], $wrong_answers);
                shuffle($options);

                // Tìm vị trí đúng (0, 1, 2 hoặc 3)
                $correct_index = array_search($correct_meaning, $options);

                // Gán nhãn tiền tố A., B., C., D.
                $final_options = [];
                foreach ($options as $idx => $opt) {
                    $final_options[] = $prefixes[$idx] . $opt;
                }

                $danh_sach_cau_hoi[] = [
                    "id"          => (int)$item['id'],
                    "tu_vung"     => ucfirst($item['word']),
                    "dap_an"      => $final_options,
                    "dap_an_dung" => (int)$correct_index
                ];
            }
        }
    }
} catch (\Throwable $e) {
    error_log("Lỗi tạo Quiz: " . $e->getMessage());
}

if ($limit_option !== 'all') {
    $danh_sach_cau_hoi = array_slice($danh_sach_cau_hoi, 0, (int) $limit_option);
}

?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Quiz: <?php echo htmlspecialchars($ten_chu_de); ?> - LexiLoop</title>
    <link rel="stylesheet" href="../../CSS/C_Quiz.css">
</head>
<body class="C_Quiz_body">

    <!-- Header -->
    <header class="C_Quiz_header">
        <h1 class="C_Quiz_logo">Quiz: <?php echo htmlspecialchars($ten_chu_de); ?></h1>
        <div class="C_Quiz_headerActions">
            <span class="C_Quiz_progressText" id="C_Quiz_progressText">Câu 1/<?php echo count($danh_sach_cau_hoi); ?></span>
            <button type="button" class="C_Quiz_exitButton" id="C_Quiz_btnThoat">Thoát</button>
        </div>
    </header>

    <!-- Thanh tiến độ Quiz -->
    <div class="C_Quiz_progressBarWrapper">
        <div class="C_Quiz_progressBar">
            <div class="C_Quiz_progressFill" id="C_Quiz_progressFill" style="width: 0%;"></div>
        </div>
    </div>

    <!-- Nội dung chính câu hỏi -->
    <main class="C_Quiz_main">
        
        <!-- Đồng hồ đếm ngược -->
        <div class="C_Quiz_timerWrapper">
            <div class="C_Quiz_timerCircle" id="C_Quiz_timerCircle">15s</div>
        </div>

        <!-- Từ vựng câu hỏi -->
        <div class="C_Quiz_questionSection">
            <p class="C_Quiz_instruction">Chọn nghĩa đúng của từ:</p>
            <h2 class="C_Quiz_questionWord" id="C_Quiz_questionWord">Airport</h2>
        </div>

        <!-- Lưới 4 đáp án -->
        <div class="C_Quiz_optionsGrid" id="C_Quiz_optionsGrid">
            <button type="button" class="C_Quiz_optionBtn" data-index="0">A. Sân bay</button>
            <button type="button" class="C_Quiz_optionBtn" data-index="1">B. Bến xe</button>
            <button type="button" class="C_Quiz_optionBtn" data-index="2">C. Nhà ga</button>
            <button type="button" class="C_Quiz_optionBtn" data-index="3">D. Bến tàu</button>
        </div>

        <!-- Các nút điều hướng -->
        <div class="C_Quiz_navButtons">
            <button type="button" id="C_Quiz_btnCauTruoc" class="C_Quiz_btnNav C_Quiz_btnWhite">
                &larr; Câu trước
            </button>
            <button type="button" id="C_Quiz_btnCauTiep" class="C_Quiz_btnNav C_Quiz_btnGray">
                Câu tiếp &rarr;
            </button>
        </div>

        <!-- Danh sách bóng câu hỏi -->
        <footer class="C_Quiz_questionListSection">
            <p class="C_Quiz_listLabel">Danh sách câu hỏi</p>
            <div class="C_Quiz_bubblesRow" id="C_Quiz_bubblesRow"> </div>
        </footer>

    </main>

    <script>
        const quizQuestions = <?php echo json_encode($danh_sach_cau_hoi, JSON_UNESCAPED_UNICODE); ?>;
        const quizSessionConfig = <?= json_encode([
                                        'csrf' => $_SESSION['C_learning_csrf'],
                                        'source' => $source,
                                        'sourceId' => $source_id,
                                        'limit' => $limit_option
                                    ], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    </script>
    <script src="../../JS/C_Quiz.js"></script>
</body>
</html>
