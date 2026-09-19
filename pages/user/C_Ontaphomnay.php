<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/auth_guard.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/Connect.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/database_objects.php');

$user_id = (int) $_SESSION['user_id'];
$srs_base_ease = 2.5;
$srs_min_interval = 1;
$tu_can_on_tap = 0;
$tu_da_hoc = 0;
$quiz_da_lam = 0;

try {
    $userRows = dbSelectView($link, 'SELECT srs_base_ease, srs_min_interval FROM Users WHERE userID = ?', 'i', [$user_id]);
    if (!empty($userRows)) {
        $srs_base_ease = (float)($userRows[0]['srs_base_ease'] ?? 2.5);
        $srs_min_interval = (int)($userRows[0]['srs_min_interval'] ?? 1);
    }
    $dueRows = dbSelectView(
        $link,
        'SELECT COUNT(*) AS total FROM vw_user_progress WHERE user_id = ? AND next_review_date <= CURRENT_DATE',
        'i',
        [$user_id]
    );
    $tu_can_on_tap = (int) ($dueRows[0]['total'] ?? 0);

    $studyRows = dbSelectView(
        $link,
        'SELECT COALESCE(SUM(words_studied), 0) AS total FROM vw_learning_sessions WHERE user_id = ? AND session_date = CURRENT_DATE',
        'i',
        [$user_id]
    );
    $tu_da_hoc = (int) ($studyRows[0]['total'] ?? 0);
    if ($tu_da_hoc === 0) {
        $reviewRows = dbSelectView(
            $link,
            'SELECT COUNT(*) AS total FROM vw_user_progress WHERE user_id = ? AND DATE(last_reviewed_at) = CURRENT_DATE',
            'i',
            [$user_id]
        );
        $tu_da_hoc = (int) ($reviewRows[0]['total'] ?? 0);
    }

    $quizRows = dbSelectView(
        $link,
        'SELECT COUNT(*) AS total FROM vw_quiz_results
         WHERE user_id = ? AND DATE(COALESCE(finished_at, started_at)) = CURRENT_DATE',
        'i',
        [$user_id]
    );
    $quiz_da_lam = (int) ($quizRows[0]['total'] ?? 0);
} catch (Throwable $error) {
    error_log('Lỗi Ôn tập hôm nay: ' . $error->getMessage());
}

$tong_tu_on_tap = $tu_da_hoc + $tu_can_on_tap;
$phan_tram_b1 = $tong_tu_on_tap > 0
    ? min(100, round($tu_da_hoc * 100 / $tong_tu_on_tap))
    : 100;
$is_bước2_unlocked = $tong_tu_on_tap === 0 || $tu_da_hoc >= $tong_tu_on_tap;
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
                Bạn có <strong><?php echo $tong_tu_on_tap; ?> từ</strong> cần ôn tập hôm nay, được chia thành 2 bước:
            </p>
        </div>

        <!-- BƯỚC 1: HỌC FLASHCARD -->
        <section class="C_Ontaphomnay_stepCard">
            <div class="C_Ontaphomnay_badge C_Ontaphomnay_badgeActive">1</div>

            <div class="C_Ontaphomnay_stepContent">
                <h2 class="C_Ontaphomnay_stepTitle">Học FlashCard</h2>
                <p class="C_Ontaphomnay_stepDesc">
                    Ôn lại <?php echo $tong_tu_on_tap; ?> từ bằng thẻ ghi nhớ, đánh dấu đã nhớ / chưa nhớ.
                </p>

                <div class="C_Ontaphomnay_progressRow">
                    <div class="C_Ontaphomnay_progressBar">
                        <div class="C_Ontaphomnay_progressFill" style="width: <?php echo $phan_tram_b1; ?>%;"></div>
                    </div>
                    <span class="C_Ontaphomnay_progressText"><?php echo $tu_da_hoc; ?>/<?php echo $tong_tu_on_tap; ?></span>
                </div>

                <button type="button" id="C_Ontaphomnay_btnTiepTucHoc" class="C_Ontaphomnay_btnAction">
                    Tiếp tục học
                </button>
            </div>
        </section>

        <!-- BƯỚC 2: LÀM QUIZ ÔN TẬP -->
        <section class="C_Ontaphomnay_stepCard <?php echo !$is_bước2_unlocked ? 'is-locked' : ''; ?>">
            <div class="C_Ontaphomnay_badge <?php echo $is_bước2_unlocked ? 'C_Ontaphomnay_badgeActive' : ''; ?>">2</div>

            <div class="C_Ontaphomnay_stepContent">
                <h2 class="C_Ontaphomnay_stepTitle">Làm Quiz ôn tập</h2>
                <p class="C_Ontaphomnay_stepDesc">
                    Kiểm tra phản xạ và ghi nhớ sâu các từ vựng sau khi hoàn thành FlashCard.
                </p>

                <div class="C_Ontaphomnay_progressRow">
                    <div class="C_Ontaphomnay_progressBar">
                        <div class="C_Ontaphomnay_progressFill" style="width: 0%;"></div>
                    </div>
                    <span class="C_Ontaphomnay_progressText">0/<?php echo $tong_tu_on_tap; ?></span>
                </div>

                <?php if (!$is_bước2_unlocked): ?>
                    <p class="C_Ontaphomnay_lockNote">
                        🔒 Hoàn thành bước 1 để mở khóa
                    </p>
                <?php else: ?>
                    <button type="button" id="C_Ontaphomnay_btnVaoQuiz" class="C_Ontaphomnay_btnAction">
                        Bắt đầu làm Quiz
                    </button>
                <?php endif; ?>
            </div>
        </section>

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
