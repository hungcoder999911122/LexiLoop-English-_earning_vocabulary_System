<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/auth_guard.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/Connect.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/database_objects.php');

$user_id = (int) $_SESSION['user_id'];
$srs_base_ease = 2.5;
$srs_min_interval = 1;
$tu_can_on_tap = 0;
$dueTopics = [];

try {
    $userRows = dbSelectView($link, 'SELECT srs_base_ease, srs_min_interval FROM Users WHERE userID = ?', 'i', [$user_id]);
    if (!empty($userRows)) {
        $srs_base_ease = (float)($userRows[0]['srs_base_ease'] ?? 2.5);
        $srs_min_interval = (int)($userRows[0]['srs_min_interval'] ?? 1);
    }
    
    $dueTopics = dbSelectView(
        $link,
        'SELECT * FROM vw_srs_due_topics WHERE user_id = ? ORDER BY overdue_days DESC, oldest_due_date ASC',
        'i',
        [$user_id]
    );

    foreach ($dueTopics as $topic) {
        $tu_can_on_tap += (int) $topic['due_word_count'];
    }

} catch (Throwable $error) {
    error_log('Lỗi Ôn tập hôm nay: ' . $error->getMessage());
}
?>

<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ôn tập hôm nay - LexiLoop</title>
    <link rel="stylesheet" href="../../CSS/C_Ontaphomnay.css">
</head>
<body class="C_Ontaphomnay_body">

    <!-- Header -->
    <header class="C_Ontaphomnay_header">
        <h1 class="C_Ontaphomnay_logo">Ôn tập hôm nay</h1>
        <button type="button" id="C_Ontaphomnay_btnThoat" class="C_Ontaphomnay_btnBack" title="Quay lại">
            &times; Thoát
        </button>
    </header>

    <!-- Khối nội dung chính -->
    <main class="C_Ontaphomnay_main">
        
        <div class="C_Ontaphomnay_infoBox">
            <p class="C_Ontaphomnay_subtitle">
                <?php if ($tu_can_on_tap > 0): ?>
                    Bạn có <strong><?php echo $tu_can_on_tap; ?> từ</strong> cần ôn tập hôm nay. Dưới đây là các chủ đề đến hạn:
                <?php else: ?>
                    Tuyệt vời! Bạn không còn từ nào cần ôn tập hôm nay. Hãy nghỉ ngơi hoặc học chủ đề mới.
                <?php endif; ?>
            </p>
        </div>

        <?php if ($tu_can_on_tap > 0): ?>
            <div class="C_Ontaphomnay_topicGrid">
                <?php foreach ($dueTopics as $topic): ?>
                    <?php 
                        $overdueDays = (int) $topic['overdue_days'];
                        $dueStatus = $overdueDays > 0 ? "Quá hạn $overdueDays ngày" : "Đến hạn hôm nay";
                        $statusClass = $overdueDays > 0 ? 'is-overdue' : 'is-due';
                    ?>
                    <article class="C_Ontaphomnay_topicCard">
                        <div class="C_Ontaphomnay_topicHeader">
                            <span class="C_Ontaphomnay_topicCategory"><?php echo htmlspecialchars((string)$topic['category']); ?></span>
                            <h2 class="C_Ontaphomnay_topicTitle"><?php echo htmlspecialchars((string)$topic['topic_name']); ?></h2>
                        </div>
                        <div class="C_Ontaphomnay_topicBody">
                            <div class="C_Ontaphomnay_topicMeta">
                                <strong><?php echo (int) $topic['due_word_count']; ?> từ</strong>
                                <span class="C_Ontaphomnay_dueStatus <?php echo $statusClass; ?>"><?php echo $dueStatus; ?></span>
                            </div>
                            <!-- Truyền tham số topic_id và mode=review để Quiz chỉ lấy từ đến hạn -->
                            <a href="C_Quiz.php?source=topic&id=<?php echo (int) $topic['topic_id']; ?>&mode=review" class="C_Ontaphomnay_btnAction C_Ontaphomnay_btnReview">
                                Ôn tập ngay (Quiz)
                            </a>
                        </div>
                    </article>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="C_Ontaphomnay_emptyState">
                <img src="../../assets/images/all_caught_up.svg" alt="Hoàn thành" onerror="this.style.display='none'">
                <h2>Đã hoàn thành!</h2>
                <p>Bạn đã hoàn tất tất cả lịch ôn tập của ngày hôm nay.</p>
                <a href="../main/B_DanhSachChuDe.php" class="C_Ontaphomnay_btnAction">Khám phá chủ đề mới</a>
            </div>
        <?php endif; ?>

        <!-- Ghi chú thuật toán Spaced Repetition -->
        <footer class="C_Ontaphomnay_bannerContainer">
            <div class="C_Ontaphomnay_bannerBox">
                💡 <strong>Ghi chú:</strong> Hệ thống sử dụng thuật toán Spaced Repetition (SM-2) để lên lịch ôn tập. 
                <button type="button" id="btnToggleSrsConfig" class="C_Ontaphomnay_btnText">Tùy chỉnh thuật toán</button>
                
                <div id="srsConfigPanel" class="C_Ontaphomnay_srsPanel" style="display: none;">
                    <form id="formSrsConfig" class="C_Ontaphomnay_srsForm">
                        <div class="form-group">
                            <label for="srs_base_ease">Hệ số Ease ban đầu (Độ dễ):</label>
                            <input type="number" id="srs_base_ease" name="srs_base_ease" step="0.1" min="1.3" max="3.0" value="<?php echo htmlspecialchars((string)$srs_base_ease); ?>">
                            <small>Mặc định: 2.5. Tăng nếu bạn muốn khoảng thời gian giãn cách dài hơn.</small>
                        </div>
                        <div class="form-group">
                            <label for="srs_min_interval">Khoảng cách tối thiểu (ngày):</label>
                            <input type="number" id="srs_min_interval" name="srs_min_interval" min="1" max="10" value="<?php echo htmlspecialchars((string)$srs_min_interval); ?>">
                            <small>Mặc định: 1. Số ngày giãn cách tối thiểu sau khi nhớ từ mới.</small>
                        </div>
                        <button type="submit" id="btnSaveSrsConfig" class="C_Ontaphomnay_btnAction" style="padding: 0.5rem 1rem; font-size: 0.9rem; height: 36px;">Lưu cấu hình</button>
                        <div id="srsConfigMessage" class="srs-message"></div>
                    </form>
                </div>
            </div>
        </footer>

    </main>

    <script src="../../JS/C_Ontaphomnay.js"></script>
</body>
</html>
