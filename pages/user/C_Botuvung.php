<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once($_SERVER['DOCUMENT_ROOT'] . '/Connect.php');

$isLoggedIn = isset($_SESSION['user_id']);
$userId = $isLoggedIn ? (int) $_SESSION['user_id'] : null;
$flash = $isLoggedIn ? ($_SESSION['C_Botuvung_flash'] ?? null) : null;
if ($isLoggedIn) unset($_SESSION['C_Botuvung_flash']);

if ($isLoggedIn && empty($_SESSION['C_Botuvung_csrf'])) {
    $_SESSION['C_Botuvung_csrf'] = bin2hex(random_bytes(32));
}
$csrfToken = $isLoggedIn ? $_SESSION['C_Botuvung_csrf'] : '';

/** Chuyển hướng theo PRG để refresh không gửi lại thao tác CRUD. */
function redirectVocabularySets(string $message, string $type = 'success'): void
{
    $_SESSION['C_Botuvung_flash'] = ['message' => $message, 'type' => $type];
    header('Location: C_Botuvung.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$isLoggedIn) {
    header('Location: ../auth/A_DangNhap.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $postedToken = $_POST['C_Botuvung_csrf'] ?? '';
    if (!is_string($postedToken) || !hash_equals($csrfToken, $postedToken)) {
        redirectVocabularySets('Yêu cầu không hợp lệ. Vui lòng tải lại trang.', 'error');
    }

    $action = $_POST['C_Botuvung_action'] ?? '';
    $setId = filter_var($_POST['C_Botuvung_setId'] ?? 0, FILTER_VALIDATE_INT) ?: 0;

    if ($action === 'create' || $action === 'update') {
        $name = trim($_POST['C_Botuvung_name'] ?? '');
        $description = trim($_POST['C_Botuvung_description'] ?? '');

        if ($name === '' || mb_strlen($name) > 100) {
            redirectVocabularySets('Tên bộ từ là bắt buộc và không quá 100 ký tự.', 'error');
        }
        if (mb_strlen($description) > 255) {
            redirectVocabularySets('Mô tả không được vượt quá 255 ký tự.', 'error');
        }
        if ($action === 'update' && $setId <= 0) {
            redirectVocabularySets('Bộ từ cần sửa không hợp lệ.', 'error');
        }

        if ($action === 'update') {
            $ownerStmt = mysqli_prepare($link, 'SELECT id FROM vocabulary_sets WHERE id = ? AND user_id = ? LIMIT 1');
            mysqli_stmt_bind_param($ownerStmt, 'ii', $setId, $userId);
            mysqli_stmt_execute($ownerStmt);
            $isOwner = mysqli_num_rows(mysqli_stmt_get_result($ownerStmt)) === 1;
            mysqli_stmt_close($ownerStmt);
            if (!$isOwner) {
                redirectVocabularySets('Không tìm thấy bộ từ hoặc bạn không có quyền sửa.', 'error');
            }
        }

        // Schema chưa có UNIQUE(user_id, name), do đó kiểm tra trùng tại server.
        $duplicateSql = 'SELECT id FROM vocabulary_sets WHERE user_id = ? AND LOWER(name) = LOWER(?) AND id <> ? LIMIT 1';
        $duplicateStmt = mysqli_prepare($link, $duplicateSql);
        mysqli_stmt_bind_param($duplicateStmt, 'isi', $userId, $name, $setId);
        mysqli_stmt_execute($duplicateStmt);
        $isDuplicate = mysqli_num_rows(mysqli_stmt_get_result($duplicateStmt)) > 0;
        mysqli_stmt_close($duplicateStmt);

        if ($isDuplicate) {
            redirectVocabularySets('Bạn đã có một bộ từ trùng tên.', 'error');
        }

        if ($action === 'create') {
            $stmt = mysqli_prepare($link, 'INSERT INTO vocabulary_sets (user_id, name, description) VALUES (?, ?, ?)');
            mysqli_stmt_bind_param($stmt, 'iss', $userId, $name, $description);
            $ok = mysqli_stmt_execute($stmt);
            mysqli_stmt_close($stmt);
            redirectVocabularySets($ok ? 'Đã tạo bộ từ mới.' : 'Không thể tạo bộ từ.', $ok ? 'success' : 'error');
        }

        // Điều kiện user_id ngăn sửa bộ từ của tài khoản khác dù ID bị thay đổi.
        $stmt = mysqli_prepare($link, 'UPDATE vocabulary_sets SET name = ?, description = ? WHERE id = ? AND user_id = ?');
        mysqli_stmt_bind_param($stmt, 'ssii', $name, $description, $setId, $userId);
        $ok = mysqli_stmt_execute($stmt);
        mysqli_stmt_close($stmt);
        redirectVocabularySets($ok ? 'Đã cập nhật bộ từ.' : 'Không thể cập nhật bộ từ.', $ok ? 'success' : 'error');
    }

    if ($action === 'delete') {
        if ($setId <= 0) {
            redirectVocabularySets('Bộ từ cần xóa không hợp lệ.', 'error');
        }

        // ON DELETE CASCADE chỉ xóa liên kết vocabulary_set_items, không xóa vocabulary.
        $stmt = mysqli_prepare($link, 'DELETE FROM vocabulary_sets WHERE id = ? AND user_id = ?');
        mysqli_stmt_bind_param($stmt, 'ii', $setId, $userId);
        $ok = mysqli_stmt_execute($stmt);
        $deletedRows = mysqli_stmt_affected_rows($stmt);
        mysqli_stmt_close($stmt);
        redirectVocabularySets(
            $ok && $deletedRows === 1 ? 'Đã xóa bộ từ.' : 'Không tìm thấy bộ từ hoặc bạn không có quyền xóa.',
            $ok && $deletedRows === 1 ? 'success' : 'error'
        );
    }

    redirectVocabularySets('Thao tác không được hỗ trợ.', 'error');
}

$search = trim($_GET['q'] ?? '');
$page = max(1, filter_var($_GET['page'] ?? 1, FILTER_VALIDATE_INT) ?: 1);
// Mỗi trang hiển thị đúng 6 bộ để lưới 3 cột luôn tạo thành 2 hàng cân đối.
$itemsPerPage = 6;
$searchPattern = '%' . $search . '%';
$totalItems = 0;
$totalPages = 1;
$vocabularySets = [];

if ($isLoggedIn) {
$countSql = 'SELECT COUNT(*) AS total FROM vocabulary_sets WHERE user_id = ? AND (name LIKE ? OR COALESCE(description, \'\') LIKE ?)';
$countStmt = mysqli_prepare($link, $countSql);
mysqli_stmt_bind_param($countStmt, 'iss', $userId, $searchPattern, $searchPattern);
mysqli_stmt_execute($countStmt);
$totalItems = (int) mysqli_fetch_assoc(mysqli_stmt_get_result($countStmt))['total'];
mysqli_stmt_close($countStmt);

$totalPages = max(1, (int) ceil($totalItems / $itemsPerPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $itemsPerPage;

$listSql = '
    SELECT vs.id, vs.name, vs.description, vs.created_at, vs.updated_at, COUNT(vsi.id) AS word_count
    FROM vocabulary_sets vs
    LEFT JOIN vocabulary_set_items vsi ON vsi.vocabulary_set_id = vs.id
    WHERE vs.user_id = ? AND (vs.name LIKE ? OR COALESCE(vs.description, \'\') LIKE ?)
    GROUP BY vs.id, vs.name, vs.description, vs.created_at, vs.updated_at
    ORDER BY vs.updated_at DESC, vs.id DESC
    LIMIT ? OFFSET ?';
$listStmt = mysqli_prepare($link, $listSql);
mysqli_stmt_bind_param($listStmt, 'issii', $userId, $searchPattern, $searchPattern, $itemsPerPage, $offset);
mysqli_stmt_execute($listStmt);
$result = mysqli_stmt_get_result($listStmt);
while ($row = mysqli_fetch_assoc($result)) {
    $vocabularySets[] = $row;
}
mysqli_stmt_close($listStmt);
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bộ từ vựng - LexiLoop</title>
    <link rel="stylesheet" href="../../CSS/Style.css">
    <link rel="stylesheet" href="../../CSS/topheader.css">
    <link rel="stylesheet" href="../../CSS/C_Botuvung.css">
    <link rel="stylesheet" href="../../CSS/guest-preview.css">
    <link rel="stylesheet" href="../../CSS/responsive.css">
</head>
<body class="C_Botuvung_body">
    <?php
    $headerTitle = 'Bộ từ vựng';
    include '../../includes/topheader.php';
    if ($isLoggedIn) {
        include '../../includes/sidebar_user.php';
    } else {
        include '../../includes/sidebar_guest.php';
    }
    ?>

    <main class="C_Botuvung_main">
        <?php if (!$isLoggedIn): ?>
            <?php
            $guestInviteTitle = 'Tạo thư viện từ vựng của riêng bạn';
            $guestInviteMessage = 'Giao diện Bộ từ vựng đang ở chế độ xem trước và không hiển thị dữ liệu cá nhân. Đăng nhập để tạo, quản lý và học các bộ từ của bạn.';
            include '../../includes/guest_invite.php';
            ?>
        <?php endif; ?>
        <section class="C_Botuvung_intro">
            <div>
                <span class="C_Botuvung_eyebrow">THƯ VIỆN CÁ NHÂN</span>
                <h2>Học theo cách của bạn</h2>
                <p>Tạo các bộ từ theo mục tiêu riêng, sau đó thêm từ và bắt đầu ôn tập.</p>
            </div>
            <?php if ($isLoggedIn): ?>
                <button type="button" class="C_Botuvung_createButton" data-open-set-form><span aria-hidden="true">＋</span> Tạo bộ từ mới</button>
            <?php else: ?>
                <a class="C_Botuvung_createButton" href="../auth/A_DangNhap.php"><span aria-hidden="true">＋</span> Đăng nhập để tạo bộ từ</a>
            <?php endif; ?>
        </section>

        <?php if ($flash): ?>
            <div class="C_Botuvung_alert C_Botuvung_alert--<?= $flash['type'] === 'success' ? 'success' : 'error' ?>" role="status">
                <?= htmlspecialchars($flash['message']) ?>
            </div>
        <?php endif; ?>

        <section class="C_Botuvung_toolbar" aria-label="Công cụ bộ từ vựng">
            <form method="get" class="C_Botuvung_searchForm">
                <label for="C_Botuvung_search" class="C_Botuvung_srOnly">Tìm bộ từ vựng</label>
                <span aria-hidden="true">⌕</span>
                <input id="C_Botuvung_search" name="q" value="<?= htmlspecialchars($search) ?>" maxlength="100" placeholder="Tìm theo tên hoặc mô tả...">
                <button type="submit">Tìm kiếm</button>
            </form>
            <div class="C_Botuvung_summary">
                <strong><?= $totalItems ?></strong> bộ từ của bạn
            </div>
        </section>

        <?php if (empty($vocabularySets)): ?>
            <section class="C_Botuvung_emptyState">
                <div class="C_Botuvung_emptyIcon" aria-hidden="true">▤</div>
                <h3><?= !$isLoggedIn ? 'Chưa có dữ liệu ở chế độ khách' : ($search !== '' ? 'Không tìm thấy bộ từ phù hợp' : 'Bắt đầu thư viện đầu tiên') ?></h3>
                <p><?= !$isLoggedIn ? 'Dữ liệu bộ từ là dữ liệu cá nhân và chỉ được tải sau khi đăng nhập.' : ($search !== '' ? 'Hãy thử từ khóa khác hoặc xóa nội dung tìm kiếm.' : 'Gom các từ liên quan vào một bộ để việc học dễ theo dõi hơn.') ?></p>
                <?php if ($isLoggedIn && $search === ''): ?>
                    <button type="button" class="C_Botuvung_createButton" data-open-set-form>Tạo bộ từ</button>
                <?php elseif ($isLoggedIn): ?>
                    <a href="C_Botuvung.php" class="C_Botuvung_secondaryLink">Xóa tìm kiếm</a>
                <?php endif; ?>
            </section>
        <?php else: ?>
            <section class="C_Botuvung_grid" aria-label="Danh sách bộ từ vựng">
                <?php foreach ($vocabularySets as $index => $set): ?>
                    <article class="C_Botuvung_card C_Botuvung_card--<?= ['green', 'blue', 'violet', 'orange'][$index % 4] ?>">
                        <div class="C_Botuvung_cardTop">
                            <span class="C_Botuvung_folderIcon" aria-hidden="true">▱</span>
                            <span class="C_Botuvung_wordCount"><?= (int) $set['word_count'] ?> từ</span>
                        </div>
                        <h3><?= htmlspecialchars($set['name']) ?></h3>
                        <p><?= htmlspecialchars($set['description'] ?: 'Chưa có mô tả cho bộ từ này.') ?></p>
                        <div class="C_Botuvung_cardMeta">Cập nhật <?= htmlspecialchars(date('d/m/Y', strtotime($set['updated_at']))) ?></div>
                        <div class="C_Botuvung_cardActions">
                            <a href="C_Tuvungcuatoi.php" class="C_Botuvung_manageLink">Quản lý từ <span aria-hidden="true">→</span></a>
                            <a href="C_Gocrenluyen.php?source=set&id=<?= (int) $set['id'] ?>" class="C_Botuvung_learnButton">Học</a>
                            <button
                                type="button"
                                class="C_Botuvung_iconButton"
                                aria-label="Sửa <?= htmlspecialchars($set['name']) ?>"
                                data-edit-set
                                data-set-id="<?= (int) $set['id'] ?>"
                                data-set-name="<?= htmlspecialchars($set['name'], ENT_QUOTES, 'UTF-8') ?>"
                                data-set-description="<?= htmlspecialchars($set['description'] ?? '', ENT_QUOTES, 'UTF-8') ?>">✎</button>
                            <form method="post" class="C_Botuvung_deleteForm" data-set-name="<?= htmlspecialchars($set['name'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="C_Botuvung_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                                <input type="hidden" name="C_Botuvung_action" value="delete">
                                <input type="hidden" name="C_Botuvung_setId" value="<?= (int) $set['id'] ?>">
                                <button type="submit" class="C_Botuvung_iconButton C_Botuvung_iconButton--danger" aria-label="Xóa <?= htmlspecialchars($set['name']) ?>">⌫</button>
                            </form>
                        </div>
                    </article>
                <?php endforeach; ?>
            </section>

            <?php if ($totalPages > 1): ?>
                <nav class="C_Botuvung_pagination" aria-label="Phân trang bộ từ vựng">
                    <?php for ($pageNumber = 1; $pageNumber <= $totalPages; $pageNumber++): ?>
                        <a class="<?= $pageNumber === $page ? 'is-active' : '' ?>" href="?q=<?= urlencode($search) ?>&page=<?= $pageNumber ?>"><?= $pageNumber ?></a>
                    <?php endfor; ?>
                </nav>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <?php if ($isLoggedIn): ?>
    <div class="C_Botuvung_modal" id="C_Botuvung_modal" hidden>
        <button type="button" class="C_Botuvung_modalBackdrop" data-close-set-form aria-label="Đóng hộp thoại"></button>
        <section class="C_Botuvung_dialog" role="dialog" aria-modal="true" aria-labelledby="C_Botuvung_dialogTitle">
            <div class="C_Botuvung_dialogHeader">
                <div>
                    <span class="C_Botuvung_eyebrow">BỘ TỪ CÁ NHÂN</span>
                    <h2 id="C_Botuvung_dialogTitle">Tạo bộ từ mới</h2>
                </div>
                <button type="button" class="C_Botuvung_closeButton" data-close-set-form aria-label="Đóng">×</button>
            </div>
            <form method="post" id="C_Botuvung_form">
                <input type="hidden" name="C_Botuvung_csrf" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="C_Botuvung_action" id="C_Botuvung_action" value="create">
                <input type="hidden" name="C_Botuvung_setId" id="C_Botuvung_setId" value="0">
                <label for="C_Botuvung_name">Tên bộ từ <span>*</span></label>
                <input id="C_Botuvung_name" name="C_Botuvung_name" maxlength="100" required placeholder="Ví dụ: Từ vựng IELTS">
                <label for="C_Botuvung_description">Mô tả</label>
                <textarea id="C_Botuvung_description" name="C_Botuvung_description" maxlength="255" rows="4" placeholder="Mục tiêu hoặc nội dung của bộ từ..."></textarea>
                <div class="C_Botuvung_characterCount"><span id="C_Botuvung_descriptionCount">0</span>/255</div>
                <div class="C_Botuvung_formActions">
                    <button type="button" class="C_Botuvung_cancelButton" data-close-set-form>Hủy</button>
                    <button type="submit" class="C_Botuvung_submitButton">Lưu bộ từ</button>
                </div>
            </form>
        </section>
    </div>
    <?php endif; ?>

    <script src="../../JS/jquery-4.0.0.min.js"></script>
    <script src="../../JS/auth.js"></script>
    <?php if ($isLoggedIn): ?><script src="../../JS/C_Botuvung.js"></script><?php endif; ?>
</body>
</html>
