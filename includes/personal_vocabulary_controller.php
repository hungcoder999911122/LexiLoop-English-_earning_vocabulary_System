<?php
declare(strict_types=1);

require_once($_SERVER['DOCUMENT_ROOT'] . '/Connect.php');
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/database_objects.php');

$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? (int) $_SESSION['user_id'] : null;
$thong_bao = '';
$loai_thong_bao = '';
$danh_sach_tu = [];
$danh_sach_chu_de = [];
$danh_sach_bo_tu = [];
$thong_ke_tu = ['tong' => 0, 'thuoc' => 0, 'chua_thuoc' => 0, 'phan_tram' => 0];

if (isset($_SESSION['C_Tuvungcuatoi_flash'])) {
    $thong_bao = (string) $_SESSION['C_Tuvungcuatoi_flash']['message'];
    $loai_thong_bao = (string) $_SESSION['C_Tuvungcuatoi_flash']['type'];
    unset($_SESSION['C_Tuvungcuatoi_flash']);
}
if ($isLoggedIn && empty($_SESSION['C_Tuvungcuatoi_csrf'])) {
    $_SESSION['C_Tuvungcuatoi_csrf'] = bin2hex(random_bytes(32));
}

try {
    if ($isLoggedIn && isset($link) && $link instanceof mysqli) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $token = $_POST['C_Tuvungcuatoi_csrf'] ?? '';
            if (!is_string($token) || !hash_equals($_SESSION['C_Tuvungcuatoi_csrf'], $token)) {
                throw new RuntimeException('Yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.');
            }

            $action = (string) ($_POST['C_Tuvungcuatoi_action'] ?? 'save');
            if ($action === 'create_set') {
                $name = trim((string) ($_POST['C_Tuvungcuatoi_newSetName'] ?? ''));
                $description = trim((string) ($_POST['C_Tuvungcuatoi_newSetDescription'] ?? ''));
                if ($name === '' || mb_strlen($name) > 100 || mb_strlen($description) > 255) {
                    throw new RuntimeException('Tên bộ từ bắt buộc, tối đa 100 ký tự; mô tả tối đa 255 ký tự.');
                }
                dbCallProcedure($link, 'CALL sp_save_vocabulary_set(?, NULL, ?, ?)', 'iss', [$user_id, $name, $description]);
                $thong_bao = 'Đã tạo bộ từ cá nhân mới.';
            } elseif ($action === 'bulk_status') {
                $status = (string) ($_POST['C_Tuvungcuatoi_status'] ?? '');
                $ids = array_values(array_unique(array_filter(array_map('intval', (array) ($_POST['C_Tuvungcuatoi_selectedIds'] ?? [])), static fn(int $id): bool => $id > 0)));
                if (!$ids || !in_array($status, ['mastered', 'learning'], true)) {
                    throw new RuntimeException('Vui lòng chọn ít nhất một từ vựng.');
                }
                $result = dbCallProcedure($link, 'CALL sp_set_vocabulary_statuses(?, ?, ?)', 'iss', [$user_id, json_encode($ids), $status]);
                $count = (int) ($result[0]['updated_count'] ?? count($ids));
                $thong_bao = $count . ' lượt cập nhật đã được ghi nhận.';
            } elseif ($action === 'bulk_delete' || $action === 'delete') {
                $ids = $action === 'delete'
                    ? [(int) ($_POST['C_Tuvungcuatoi_deleteId'] ?? 0)]
                    : array_map('intval', (array) ($_POST['C_Tuvungcuatoi_selectedIds'] ?? []));
                $ids = array_values(array_unique(array_filter($ids, static fn(int $id): bool => $id > 0)));
                if (!$ids) {
                    throw new RuntimeException('Vui lòng chọn ít nhất một từ để xóa.');
                }
                $result = dbCallProcedure($link, 'CALL sp_delete_personal_vocabularies(?, ?)', 'is', [$user_id, json_encode($ids)]);
                $count = (int) ($result[0]['deleted_count'] ?? 0);
                $thong_bao = $count > 0 ? "Đã xóa $count từ vựng cá nhân." : 'Không có từ cá nhân nào được xóa.';
            } else {
                $word = trim((string) ($_POST['C_Tuvungcuatoi_txtTuVung'] ?? ''));
                $vocabularyId = (int) ($_POST['C_Tuvungcuatoi_editId'] ?? 0);
                $pronunciation = trim((string) ($_POST['C_Tuvungcuatoi_txtPhienAm'] ?? ''));
                $meaning = trim((string) ($_POST['C_Tuvungcuatoi_txtNghia'] ?? ''));
                $partOfSpeech = trim((string) ($_POST['C_Tuvungcuatoi_selTuLoai'] ?? ''));
                $setId = (int) ($_POST['C_Tuvungcuatoi_selBoTuForm'] ?? 0);
                $example = trim((string) ($_POST['C_Tuvungcuatoi_txtViDu'] ?? ''));
                $allowedParts = ['noun','verb','adjective','adverb','pronoun','preposition','conjunction','phrase','other'];
                if ($word === '' || mb_strlen($word) > 100 || $meaning === '' || mb_strlen($pronunciation) > 100
                    || !in_array($partOfSpeech, $allowedParts, true) || $setId <= 0) {
                    throw new RuntimeException('Dữ liệu từ vựng không hợp lệ hoặc chưa chọn bộ từ cá nhân.');
                }
                dbCallProcedure(
                    $link,
                    'CALL sp_save_personal_vocabulary(?, ?, ?, ?, ?, ?, ?, ?)',
                    'iiisssss',
                    [$user_id, $vocabularyId, $setId, $word, $pronunciation, $partOfSpeech, $meaning, $example]
                );
                $thong_bao = $vocabularyId > 0 ? "Cập nhật từ vựng \"$word\" thành công!" : "Thêm từ vựng \"$word\" thành công!";
            }
            $loai_thong_bao = 'success';
        }

        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_SESSION['C_Tuvungcuatoi_flash'] = ['message' => $thong_bao, 'type' => $loai_thong_bao];
            header('Location: C_Tuvungcuatoi.php');
            exit;
        }

        $rows = dbSelectView($link, 'SELECT * FROM vw_personal_vocabulary WHERE user_id = ? ORDER BY id DESC', 'i', [$user_id]);
        $today = date('Y-m-d');
        foreach ($rows as $row) {
            $memoryLevel = 'moi';
            if ($row['progress_status'] === 'mastered') {
                $memoryLevel = 'tot';
            } elseif ($row['progress_status'] === 'learning' || (!empty($row['next_review_date']) && $row['next_review_date'] <= $today)) {
                $memoryLevel = 'can_on_tap';
            }
            $danh_sach_tu[] = [
                'id' => (int) $row['id'], 'tu_vung' => $row['word'], 'phien_am' => $row['pronunciation'],
                'tu_loai' => $row['part_of_speech'], 'nghia' => $row['meaning'], 'cau_vi_du' => $row['example_sentence'],
                'topic_id' => (int) ($row['topic_id'] ?? 0), 'set_ids' => $row['set_ids'] ?? '',
                'set_names' => $row['set_names'] ?? '', 'chu_de' => $row['set_names'] ?: 'Chưa phân loại',
                'muc_do_nho' => $memoryLevel, 'is_owner' => (int) $row['created_by'] === $user_id,
            ];
        }
        $setRows = dbSelectView($link, 'SELECT id, name FROM vw_vocabulary_sets WHERE user_id = ? ORDER BY updated_at DESC, name ASC', 'i', [$user_id]);
        foreach ($setRows as $row) {
            $danh_sach_bo_tu[] = ['id' => (int) $row['id'], 'name' => $row['name']];
        }
        foreach (dbSelectView($link, 'SELECT topicID, topicName FROM vw_topic_catalog ORDER BY topicName') as $row) {
            $danh_sach_chu_de[] = ['id' => (int) $row['topicID'], 'name' => $row['topicName']];
        }
        $thong_ke_tu['tong'] = count($danh_sach_tu);
        $thong_ke_tu['thuoc'] = count(array_filter($danh_sach_tu, static fn(array $word): bool => $word['muc_do_nho'] === 'tot'));
        $thong_ke_tu['chua_thuoc'] = $thong_ke_tu['tong'] - $thong_ke_tu['thuoc'];
        $thong_ke_tu['phan_tram'] = $thong_ke_tu['tong'] > 0 ? (int) round($thong_ke_tu['thuoc'] * 100 / $thong_ke_tu['tong']) : 0;
    }
} catch (Throwable $error) {
    error_log('Lỗi Từ vựng của tôi: ' . $error->getMessage());
    $thong_bao = $error instanceof RuntimeException ? $error->getMessage() : 'Có lỗi hệ thống khi xử lý yêu cầu.';
    $loai_thong_bao = 'error';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !headers_sent()) {
        $_SESSION['C_Tuvungcuatoi_flash'] = ['message' => $thong_bao, 'type' => $loai_thong_bao];
        header('Location: C_Tuvungcuatoi.php');
        exit;
    }
}
