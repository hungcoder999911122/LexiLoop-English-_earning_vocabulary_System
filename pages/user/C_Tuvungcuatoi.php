<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once($_SERVER['DOCUMENT_ROOT'] . "/Connect.php");
// Guest được xem giao diện rỗng; mọi truy vấn và thao tác cá nhân đều cần user_id.
$isLoggedIn = isset($_SESSION['user_id']);
$user_id = $isLoggedIn ? (int) $_SESSION['user_id'] : null;

$thong_bao = "";
$loai_thong_bao = "";
$danh_sach_tu = [];
$danh_sach_chu_de = [];
$danh_sach_bo_tu = [];
$thong_ke_tu = ['tong' => 0, 'thuoc' => 0, 'chua_thuoc' => 0, 'phan_tram' => 0];

// Thông báo được giữ qua redirect để áp dụng Post/Redirect/Get (PRG).
if (isset($_SESSION['C_Tuvungcuatoi_flash'])) {
    $thong_bao = $_SESSION['C_Tuvungcuatoi_flash']['message'];
    $loai_thong_bao = $_SESSION['C_Tuvungcuatoi_flash']['type'];
    unset($_SESSION['C_Tuvungcuatoi_flash']);
}

// ============================================================================
// QUAN TRỌNG - Token này xác nhận form được gửi từ chính trang LexiLoop.
// Không dùng token này để phân quyền; quyền sở hữu vẫn phải kiểm tra tại SQL.
// ============================================================================
if ($isLoggedIn && empty($_SESSION['C_Tuvungcuatoi_csrf'])) {
    $_SESSION['C_Tuvungcuatoi_csrf'] = bin2hex(random_bytes(32));
}

try {
    if ($isLoggedIn && isset($link) && $link) {
        // Quét danh sách bảng thực tế tránh lỗi phân biệt hoa/thường trên Linux
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

        // =========================================================================
        // [XỬ LÝ THÊM TỪ VỰNG KHI SUBMIT FORM (POST)]
        // =========================================================================
        if ($_SERVER["REQUEST_METHOD"] === "POST") {
            $csrf_token = $_POST['C_Tuvungcuatoi_csrf'] ?? '';

            // QUAN TRỌNG - Chặn request POST được tạo từ website khác (CSRF).
            if (!is_string($csrf_token) || !hash_equals($_SESSION['C_Tuvungcuatoi_csrf'], $csrf_token)) {
                $thong_bao = "Yêu cầu không hợp lệ. Vui lòng tải lại trang và thử lại.";
                $loai_thong_bao = "error";
            } elseif (($_POST['C_Tuvungcuatoi_action'] ?? '') === 'create_set') {
                $set_name = trim($_POST['C_Tuvungcuatoi_newSetName'] ?? '');
                $set_description = trim($_POST['C_Tuvungcuatoi_newSetDescription'] ?? '');
                if ($set_name === '' || mb_strlen($set_name) > 100) {
                    $thong_bao = 'Tên bộ từ bắt buộc và không quá 100 ký tự.';
                    $loai_thong_bao = 'error';
                } elseif (mb_strlen($set_description) > 255) {
                    // maxlength ở HTML chỉ hỗ trợ UX; server vẫn phải tự kiểm tra.
                    $thong_bao = 'Mô tả bộ từ không được vượt quá 255 ký tự.';
                    $loai_thong_bao = 'error';
                } else {
                    // UNIQUE ở schema hiện tại chưa bao gồm (user_id, name), nên
                    // kiểm tra tại server để một tài khoản không có hai bộ từ cùng tên.
                    $sql_find_set = "SELECT id FROM $tbl_sets WHERE user_id = ? AND LOWER(name) = LOWER(?) LIMIT 1";
                    $stmt_find_set = mysqli_prepare($link, $sql_find_set);
                    if (!$stmt_find_set) {
                        $thong_bao = 'Không thể kiểm tra bộ từ cá nhân.';
                        $loai_thong_bao = 'error';
                    } else {
                        mysqli_stmt_bind_param($stmt_find_set, 'is', $user_id, $set_name);
                        mysqli_stmt_execute($stmt_find_set);
                        $set_exists = mysqli_num_rows(mysqli_stmt_get_result($stmt_find_set)) > 0;
                        mysqli_stmt_close($stmt_find_set);

                        if ($set_exists) {
                            $thong_bao = 'Bộ từ cá nhân này đã tồn tại.';
                            $loai_thong_bao = 'error';
                        } else {
                            $sql_create_set = "INSERT INTO $tbl_sets (user_id, name, description) VALUES (?, ?, ?)";
                            if ($stmt_create_set = mysqli_prepare($link, $sql_create_set)) {
                                mysqli_stmt_bind_param($stmt_create_set, 'iss', $user_id, $set_name, $set_description);
                                if (mysqli_stmt_execute($stmt_create_set)) {
                                    $thong_bao = 'Đã tạo bộ từ cá nhân mới.';
                                    $loai_thong_bao = 'success';
                                } else {
                                    $thong_bao = 'Không thể tạo bộ từ cá nhân.';
                                    $loai_thong_bao = 'error';
                                }
                                mysqli_stmt_close($stmt_create_set);
                            } else {
                                $thong_bao = 'Không thể chuẩn bị yêu cầu tạo bộ từ.';
                                $loai_thong_bao = 'error';
                            }
                        }
                    }
                }
            } elseif (($_POST['C_Tuvungcuatoi_action'] ?? '') === 'bulk_status') {
                // QUAN TRỌNG: "Thuộc/Chưa thuộc" chỉ thay đổi tiến độ của
                // người đang đăng nhập, tuyệt đối không sửa dữ liệu từ hệ thống.
                $status = $_POST['C_Tuvungcuatoi_status'] ?? '';
                $selected_ids = array_values(array_unique(array_filter(
                    array_map('intval', (array) ($_POST['C_Tuvungcuatoi_selectedIds'] ?? [])),
                    static fn($id) => $id > 0
                )));

                if (!in_array($status, ['mastered', 'learning'], true) || !$selected_ids) {
                    $thong_bao = "Vui lòng chọn ít nhất một từ vựng.";
                    $loai_thong_bao = "error";
                } else {
                    $next_review_sql = $status === 'mastered'
                        ? 'DATE_ADD(CURDATE(), INTERVAL 30 DAY)'
                        : 'CURDATE()';
                    $sql_status = "INSERT INTO $tbl_progress (user_id, vocabulary_id, status, next_review_date)
                        VALUES (?, ?, ?, $next_review_sql)
                        ON DUPLICATE KEY UPDATE status = VALUES(status), next_review_date = VALUES(next_review_date)";
                    $stmt_status = mysqli_prepare($link, $sql_status);

                    if (!$stmt_status) {
                        $thong_bao = "Không thể cập nhật trạng thái thuộc.";
                        $loai_thong_bao = "error";
                    } else {
                        $updated = 0;
                        foreach ($selected_ids as $vocabulary_id) {
                            mysqli_stmt_bind_param($stmt_status, 'iis', $user_id, $vocabulary_id, $status);
                            if (mysqli_stmt_execute($stmt_status)) {
                                $updated++;
                            }
                        }
                        mysqli_stmt_close($stmt_status);
                        $thong_bao = $updated . ' từ đã được đánh dấu ' . ($status === 'mastered' ? 'thuộc.' : 'chưa thuộc.');
                        $loai_thong_bao = 'success';
                    }
                }
            } elseif (($_POST['C_Tuvungcuatoi_action'] ?? '') === 'bulk_delete') {
                // Chỉ xóa từ tự tạo. Từ hệ thống luôn được giữ nguyên.
                $selected_ids = array_values(array_unique(array_filter(
                    array_map('intval', (array) ($_POST['C_Tuvungcuatoi_selectedIds'] ?? [])),
                    static fn($id) => $id > 0
                )));
                if (!$selected_ids) {
                    $thong_bao = "Vui lòng chọn ít nhất một từ để xóa.";
                    $loai_thong_bao = 'error';
                } else {
                    $deleted = 0;
                    $stmt_delete = mysqli_prepare($link, "DELETE FROM $tbl_vocab WHERE id = ? AND created_by = ?");
                    foreach ($selected_ids as $delete_id) {
                        mysqli_stmt_bind_param($stmt_delete, 'ii', $delete_id, $user_id);
                        if (mysqli_stmt_execute($stmt_delete)) {
                            $deleted += mysqli_stmt_affected_rows($stmt_delete);
                        }
                    }
                    mysqli_stmt_close($stmt_delete);
                    $thong_bao = $deleted > 0
                        ? "Đã xóa $deleted từ vựng cá nhân."
                        : 'Không có từ cá nhân nào được xóa.';
                    $loai_thong_bao = $deleted > 0 ? 'success' : 'error';
                }
            } elseif (($_POST['C_Tuvungcuatoi_action'] ?? '') === 'delete') {
                // QUAN TRỌNG - Xóa phải xảy ra ở server. row.remove() trong JS
                // chỉ xóa tạm thời trên màn hình, tải lại trang dữ liệu vẫn còn.
                $delete_id = (int) ($_POST['C_Tuvungcuatoi_deleteId'] ?? 0);

                $sql_delete = "DELETE FROM $tbl_vocab WHERE id = ? AND created_by = ?";
                if ($delete_id <= 0 || !($stmt_delete = mysqli_prepare($link, $sql_delete))) {
                    $thong_bao = "Không thể chuẩn bị yêu cầu xóa từ vựng.";
                    $loai_thong_bao = "error";
                } else {
                    mysqli_stmt_bind_param($stmt_delete, "ii", $delete_id, $user_id);
                    $delete_ok = mysqli_stmt_execute($stmt_delete);
                    $deleted_rows = mysqli_stmt_affected_rows($stmt_delete);
                    mysqli_stmt_close($stmt_delete);

                    // Điều kiện created_by = ? bảo đảm người dùng không thể xóa
                    // từ hệ thống hoặc từ do tài khoản khác tạo, kể cả khi sửa HTML.
                    if ($delete_ok && $deleted_rows === 1) {
                        $thong_bao = "Đã xóa từ vựng thành công.";
                        $loai_thong_bao = "success";
                    } elseif ($delete_ok) {
                        $thong_bao = "Không tìm thấy từ vựng hoặc anh không có quyền xóa.";
                        $loai_thong_bao = "error";
                    } else {
                        $thong_bao = "Có lỗi khi xóa từ vựng.";
                        $loai_thong_bao = "error";
                    }
                }
            } else {
                $tu_vung = trim(
                    $_POST["C_Tuvungcuatoi_txtTuVung"] ?? ""
                );

                // =====================================================
                // ID của từ đang sửa
                // Nếu = 0 nghĩa là đang thêm từ mới
                // Nếu > 0 nghĩa là đang sửa từ đã tồn tại
                // =====================================================
                $edit_id = intval(
                    $_POST["C_Tuvungcuatoi_editId"] ?? 0
                );

                $phien_am = trim(
                    $_POST["C_Tuvungcuatoi_txtPhienAm"] ?? ""
                );

                $nghia = trim(
                    $_POST["C_Tuvungcuatoi_txtNghia"] ?? ""
                );

                $tu_loai = trim(
                    $_POST["C_Tuvungcuatoi_selTuLoai"] ?? ""
                );

                // Bộ từ cá nhân thay thế Topic hệ thống cho dữ liệu người dùng tự tạo.
                $set_id = (int) ($_POST["C_Tuvungcuatoi_selBoTuForm"] ?? 0);

                $cau_vi_du = trim(
                    $_POST["C_Tuvungcuatoi_txtViDu"] ?? ""
                );

                // Danh sách từ loại được hệ thống cho phép
                $tu_loai_hop_le = [
                    "noun",
                    "verb",
                    "adjective",
                    "adverb",
                    "pronoun",
                    "preposition",
                    "conjunction",
                    "phrase",
                    "other"
                ];

                $form_hop_le = true;

                // =============================================
                // 1. KIỂM TRA TỪ VỰNG
                // =============================================
                if ($tu_vung === "") {

                    $thong_bao = "Vui lòng nhập từ vựng.";
                    $loai_thong_bao = "error";
                    $form_hop_le = false;
                } elseif (mb_strlen($tu_vung) > 100) {

                    $thong_bao = "Từ vựng không được vượt quá 100 ký tự.";
                    $loai_thong_bao = "error";
                    $form_hop_le = false;
                }


                // =============================================
                // 2. KIỂM TRA NGHĨA
                // =============================================
                elseif ($nghia === "") {

                    $thong_bao = "Vui lòng nhập nghĩa tiếng Việt.";
                    $loai_thong_bao = "error";
                    $form_hop_le = false;
                }


                // =============================================
                // 3. KIỂM TRA PHIÊN ÂM
                // Phiên âm không bắt buộc nhưng tối đa 100 ký tự
                // =============================================
                elseif ($phien_am !== "" && mb_strlen($phien_am) > 100) {

                    $thong_bao = "Phiên âm không được vượt quá 100 ký tự.";
                    $loai_thong_bao = "error";
                    $form_hop_le = false;
                }


                // =============================================
                // 4. KIỂM TRA TỪ LOẠI
                // =============================================
                elseif (!in_array($tu_loai, $tu_loai_hop_le, true)) {

                    $thong_bao = "Từ loại không hợp lệ.";
                    $loai_thong_bao = "error";
                    $form_hop_le = false;
                }


                // =============================================
                // 5. KIỂM TRA BỘ TỪ CÁ NHÂN
                // =============================================
                elseif ($set_id <= 0) {

                    $thong_bao = "Vui lòng chọn bộ từ cá nhân.";
                    $loai_thong_bao = "error";
                    $form_hop_le = false;
                }

                // =============================================
                // QUAN TRỌNG: Bộ từ phải thuộc đúng user_id, không chỉ tồn tại.
                // =============================================
                if ($form_hop_le) {

                    $set_hop_le = false;

                    $sql_set_check = "
                    SELECT id
                    FROM $tbl_sets
                    WHERE id = ? AND user_id = ?
                    LIMIT 1
                ";

                    if ($stmt_set = mysqli_prepare($link, $sql_set_check)) {

                        mysqli_stmt_bind_param(
                            $stmt_set,
                            "ii",
                            $set_id,
                            $user_id
                        );

                        mysqli_stmt_execute($stmt_set);
                        mysqli_stmt_store_result($stmt_set);

                        if (mysqli_stmt_num_rows($stmt_set) > 0) {
                            $set_hop_le = true;
                        }

                        mysqli_stmt_close($stmt_set);
                    } else {

                        $thong_bao = "Không thể kiểm tra bộ từ.";
                        $loai_thong_bao = "error";
                        $form_hop_le = false;
                    }


                    if (!$set_hop_le && $form_hop_le) {

                        $thong_bao = "Bộ từ không tồn tại hoặc không thuộc tài khoản của bạn.";
                        $loai_thong_bao = "error";
                        $form_hop_le = false;
                    }
                }

                // =============================================
                // KIỂM TRA TỪ VỰNG BỊ TRÙNG
                // Cùng user + cùng từ + cùng topic
                // =============================================

                // =====================================================
                // Khi thêm:
                //     edit_id = 0
                //
                // Khi sửa:
                //     loại chính bản ghi đang sửa ra bằng id <> ?
                // =====================================================
                if ($form_hop_le) {

                    $is_duplicate = false;

                    // QUAN TRỌNG: Khi sửa, xác nhận bản ghi thuộc người gửi POST
                    // trước khi kiểm tra trùng và UPDATE. Không tin editId từ trình duyệt.
                    if ($edit_id > 0) {
                        $sql_owner_check = "SELECT id FROM $tbl_vocab WHERE id = ? AND created_by = ? LIMIT 1";
                        if ($stmt_owner = mysqli_prepare($link, $sql_owner_check)) {
                            mysqli_stmt_bind_param($stmt_owner, "ii", $edit_id, $user_id);
                            mysqli_stmt_execute($stmt_owner);
                            mysqli_stmt_store_result($stmt_owner);
                            $is_owner = mysqli_stmt_num_rows($stmt_owner) === 1;
                            mysqli_stmt_close($stmt_owner);

                            if (!$is_owner) {
                                $thong_bao = "Không tìm thấy từ vựng hoặc bạn không có quyền sửa.";
                                $loai_thong_bao = "error";
                                $form_hop_le = false;
                            }
                        } else {
                            $thong_bao = "Không thể kiểm tra quyền chỉnh sửa.";
                            $loai_thong_bao = "error";
                            $form_hop_le = false;
                        }
                    }

                    if (!$form_hop_le) {
                        // Không tiếp tục truy vấn khi quyền sở hữu đã không hợp lệ.
                    } else {

                        $sql_check = "
                    SELECT id
                    FROM $tbl_vocab
                    WHERE LOWER(word) = LOWER(?)
                    AND created_by = ?
                    AND id <> ?
                    LIMIT 1
                ";

                        if ($stmt_chk = mysqli_prepare($link, $sql_check)) {

                            mysqli_stmt_bind_param(
                                $stmt_chk,
                                "sii",
                                $tu_vung,
                                $user_id,
                                $edit_id
                            );

                            mysqli_stmt_execute($stmt_chk);
                            mysqli_stmt_store_result($stmt_chk);

                            if (mysqli_stmt_num_rows($stmt_chk) > 0) {
                                $is_duplicate = true;
                            }

                            mysqli_stmt_close($stmt_chk);
                        } else {

                            $thong_bao = "Không thể kiểm tra từ vựng trùng.";
                            $loai_thong_bao = "error";
                            $form_hop_le = false;
                        }


                        if ($is_duplicate) {

                            $thong_bao = "Từ vựng \"$tu_vung\" đã có trong danh sách cá nhân của bạn.";
                            $loai_thong_bao = "error";
                            $form_hop_le = false;
                        }
                    }
                }

                // =====================================================
                // THÊM HOẶC CẬP NHẬT TỪ VỰNG
                // =====================================================
                if ($form_hop_le) {

                    // =================================================
                    // TRƯỜNG HỢP 1: SỬA TỪ VỰNG
                    // edit_id > 0
                    // =================================================
                    if ($edit_id > 0) {

                        $sql_update = "
            UPDATE $tbl_vocab
            SET
                word = ?,
                pronunciation = ?,
                part_of_speech = ?,
                meaning = ?,
                example_sentence = ?,
                topic_id = NULL
            WHERE id = ?
            AND created_by = ?
        ";

                        if ($stmt_update = mysqli_prepare($link, $sql_update)) {

                            mysqli_stmt_bind_param(
                                $stmt_update,
                                "sssssii",
                                $tu_vung,
                                $phien_am,
                                $tu_loai,
                                $nghia,
                                $cau_vi_du,
                                $edit_id,
                                $user_id
                            );

                            if (mysqli_stmt_execute($stmt_update)) {

                                // Khi sửa, chuyển từ vào đúng một bộ từ thuộc tài khoản.
                                $sql_remove_old_set = "DELETE vsi FROM $tbl_set_items vsi
                                INNER JOIN $tbl_sets vs ON vs.id = vsi.vocabulary_set_id
                                WHERE vsi.vocabulary_id = ? AND vs.user_id = ?";
                                if ($stmt_remove_set = mysqli_prepare($link, $sql_remove_old_set)) {
                                    mysqli_stmt_bind_param($stmt_remove_set, 'ii', $edit_id, $user_id);
                                    mysqli_stmt_execute($stmt_remove_set);
                                    mysqli_stmt_close($stmt_remove_set);
                                }
                                $sql_add_set = "INSERT INTO $tbl_set_items (vocabulary_set_id, vocabulary_id) VALUES (?, ?)";
                                if ($stmt_add_set = mysqli_prepare($link, $sql_add_set)) {
                                    mysqli_stmt_bind_param($stmt_add_set, 'ii', $set_id, $edit_id);
                                    mysqli_stmt_execute($stmt_add_set);
                                    mysqli_stmt_close($stmt_add_set);
                                }

                                // Sau khi đã kiểm tra owner ở trên, 0 dòng ở đây nghĩa là
                                // người dùng lưu lại đúng dữ liệu cũ, không phải lỗi quyền.
                                if (mysqli_stmt_affected_rows($stmt_update) > 0) {
                                    $thong_bao = "Cập nhật từ vựng \"$tu_vung\" thành công!";
                                    $loai_thong_bao = "success";
                                } else {
                                    $thong_bao = "Dữ liệu chưa thay đổi.";
                                    $loai_thong_bao = "success";
                                }
                            } else {

                                $thong_bao = "Có lỗi khi cập nhật từ vựng.";
                                $loai_thong_bao = "error";
                            }

                            mysqli_stmt_close($stmt_update);
                        } else {

                            $thong_bao = "Không thể chuẩn bị câu lệnh cập nhật.";
                            $loai_thong_bao = "error";
                        }
                    }

                    // =================================================
                    // TRƯỜNG HỢP 2: THÊM TỪ VỰNG
                    // edit_id = 0
                    // =================================================
                    else {

                        $sql_insert = "
            INSERT INTO $tbl_vocab
            (
                topic_id,
                word,
                pronunciation,
                part_of_speech,
                meaning,
                example_sentence,
                created_by
            )
            /* topic_id là NULL vì từ cá nhân thuộc vocabulary_set_items, không thuộc Topic hệ thống. */
            VALUES (NULL, ?, ?, ?, ?, ?, ?)
        ";

                        if ($stmt_ins = mysqli_prepare($link, $sql_insert)) {

                            mysqli_stmt_bind_param(
                                $stmt_ins,
                                "sssssi",
                                $tu_vung,
                                $phien_am,
                                $tu_loai,
                                $nghia,
                                $cau_vi_du,
                                $user_id
                            );

                            if (mysqli_stmt_execute($stmt_ins)) {

                                $new_vocab_id = mysqli_insert_id($link);

                                // =========================================
                                // Tạo tiến độ học ban đầu
                                // =========================================
                                if (
                                    isset($db_tables['user_vocab_progress']) &&
                                    $new_vocab_id > 0
                                ) {

                                    $sql_progress = "
                        INSERT INTO $tbl_progress
                        (
                            user_id,
                            vocabulary_id,
                            status,
                            next_review_date
                        )
                        VALUES (?, ?, 'new', CURDATE())
                    ";

                                    if ($stmt_prog = mysqli_prepare($link, $sql_progress)) {

                                        mysqli_stmt_bind_param(
                                            $stmt_prog,
                                            "ii",
                                            $user_id,
                                            $new_vocab_id
                                        );

                                        mysqli_stmt_execute($stmt_prog);
                                        mysqli_stmt_close($stmt_prog);
                                    }
                                }

                                // Liên kết từ mới với bộ từ cá nhân đã chọn.
                                $sql_set_item = "INSERT INTO $tbl_set_items (vocabulary_set_id, vocabulary_id) VALUES (?, ?)";
                                if ($stmt_set_item = mysqli_prepare($link, $sql_set_item)) {
                                    mysqli_stmt_bind_param($stmt_set_item, 'ii', $set_id, $new_vocab_id);
                                    mysqli_stmt_execute($stmt_set_item);
                                    mysqli_stmt_close($stmt_set_item);
                                }

                                $thong_bao =
                                    "Thêm từ vựng \"" .
                                    $tu_vung .
                                    "\" thành công!";

                                $loai_thong_bao = "success";
                            } else {

                                $thong_bao =
                                    "Có lỗi xảy ra khi lưu từ vựng vào cơ sở dữ liệu.";

                                $loai_thong_bao = "error";
                            }

                            mysqli_stmt_close($stmt_ins);
                        } else {

                            $thong_bao =
                                "Không thể chuẩn bị câu lệnh thêm từ vựng.";

                            $loai_thong_bao = "error";
                        }
                    }
                }
            }
        }

        // QUAN TRỌNG: Sau mọi POST phải chuyển về GET. Nếu không, refresh sẽ
        // gửi lại request cũ và trình duyệt hiện cảnh báo "resubmit form".
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $_SESSION['C_Tuvungcuatoi_flash'] = [
                'message' => $thong_bao,
                'type' => $loai_thong_bao
            ];
            header('Location: C_Tuvungcuatoi.php');
            exit;
        }

        // =========================================================================
        // [TRUY VẤN DANH SÁCH TỪ VỰNG CỦA TÔI]
        // =========================================================================
        if (isset($db_tables['vocabulary'])) {
            $sql_list = "
                SELECT 
                    v.id,
                    v.word AS tu_vung,
                    v.pronunciation AS phien_am,
                    v.part_of_speech AS tu_loai,
                    v.meaning AS nghia,
                    v.example_sentence AS cau_vi_du,
                    v.topic_id,
                    v.created_by,
                    personal_sets.set_ids,
                    personal_sets.set_names,
                    COALESCE(personal_sets.set_names, 'Chưa phân loại') AS chu_de,
                    COALESCE(p.status, 'new') AS db_status,
                    p.next_review_date
                FROM $tbl_vocab v
                LEFT JOIN $tbl_topics t ON v.topic_id = t.topicID
                LEFT JOIN $tbl_progress p
                    ON v.id = p.vocabulary_id
                    AND p.user_id = ?

                INNER JOIN (
                    SELECT
                        vsi.vocabulary_id,
                        GROUP_CONCAT(vsi.vocabulary_set_id) AS set_ids,
                        GROUP_CONCAT(vs.name SEPARATOR ' • ') AS set_names
                    FROM $tbl_set_items vsi
                    INNER JOIN $tbl_sets vs ON vs.id = vsi.vocabulary_set_id
                    WHERE vs.user_id = ?
                    GROUP BY vsi.vocabulary_id
                ) personal_sets ON personal_sets.vocabulary_id = v.id

                ORDER BY v.id DESC
            ";

            if ($stmt_l = @mysqli_prepare($link, $sql_list)) {
                mysqli_stmt_bind_param($stmt_l, "ii", $user_id, $user_id);
                mysqli_stmt_execute($stmt_l);
                $res_l = mysqli_stmt_get_result($stmt_l);
                $today = date('Y-m-d');
                while ($row = mysqli_fetch_assoc($res_l)) {
                    $muc_do = "moi";
                    if ($row['db_status'] === 'mastered') {
                        $muc_do = "tot";
                    } elseif ($row['db_status'] === 'learning' || (!empty($row['next_review_date']) && $row['next_review_date'] <= $today)) {
                        $muc_do = "can_on_tap";
                    }

                    //
                    $danh_sach_tu[] = [
                        "id"           => (int)$row['id'],
                        "tu_vung"      => $row['tu_vung'],
                        "phien_am"     => $row['phien_am'],
                        "tu_loai"      => $row['tu_loai'],
                        "nghia"        => $row['nghia'],
                        "cau_vi_du"    => $row['cau_vi_du'],
                        "topic_id"     => (int)$row['topic_id'],
                        "set_ids"      => $row['set_ids'] ?? '',
                        "set_names"    => $row['set_names'] ?? '',
                        "chu_de"       => $row['chu_de'],
                        "muc_do_nho"   => $muc_do,
                        // Chỉ từ do chính người dùng tạo mới được sửa/xóa.
                        "is_owner"     => (int)$row['created_by'] === $user_id
                    ];
                }
                mysqli_stmt_close($stmt_l);
            }
        }

        // Bộ từ thuộc riêng người dùng. Schema này đã có sẵn trong db_LexiLoop.sql:
        // vocabulary_sets (chủ sở hữu) và vocabulary_set_items (các từ thuộc bộ).
        if (isset($db_tables['vocabulary_sets'])) {
            $sql_sets = "SELECT id, name FROM $tbl_sets WHERE user_id = ? ORDER BY updated_at DESC, name ASC";
            if ($stmt_sets = mysqli_prepare($link, $sql_sets)) {
                mysqli_stmt_bind_param($stmt_sets, 'i', $user_id);
                mysqli_stmt_execute($stmt_sets);
                $result_sets = mysqli_stmt_get_result($stmt_sets);
                while ($set_row = mysqli_fetch_assoc($result_sets)) {
                    $danh_sach_bo_tu[] = ['id' => (int) $set_row['id'], 'name' => $set_row['name']];
                }
                mysqli_stmt_close($stmt_sets);
            }
        }

        foreach ($danh_sach_tu as $tu) {
            $thong_ke_tu['tong']++;
            if ($tu['muc_do_nho'] === 'tot') {
                $thong_ke_tu['thuoc']++;
            }
        }
        $thong_ke_tu['chua_thuoc'] = $thong_ke_tu['tong'] - $thong_ke_tu['thuoc'];
        $thong_ke_tu['phan_tram'] = $thong_ke_tu['tong'] > 0
            ? (int) round(($thong_ke_tu['thuoc'] / $thong_ke_tu['tong']) * 100)
            : 0;

        // =========================================================================
        // [TRUY VẤN DANH SÁCH BÔ TỪ VỰNG CỦA TÔI
        // =========================================================================
        if (isset($db_tables['topics'])) {
            $sql_topics = "
                SELECT topicID, topicName
                FROM $tbl_topics
                ORDER BY topicName ASC
            ";
            $result_topics = mysqli_query($link, $sql_topics);

            if ($result_topics) {
                while ($row_topic = mysqli_fetch_assoc($result_topics)) {

                    $danh_sach_chu_de[] = [
                        "id"   => (int)$row_topic["topicID"],
                        "name" => $row_topic["topicName"]
                    ];
                }
            }
        }
    }
} catch (\Throwable $e) {
    error_log("Lỗi Từ vựng của tôi: " . $e->getMessage());
    $thong_bao = 'Có lỗi hệ thống khi xử lý yêu cầu.';
    $loai_thong_bao = 'error';
}

// Fallback PRG: nếu exception xảy ra trước redirect bên trong try, refresh vẫn
// không được phép gửi lại POST cũ.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !headers_sent()) {
    $_SESSION['C_Tuvungcuatoi_flash'] = ['message' => $thong_bao, 'type' => $loai_thong_bao];
    header('Location: C_Tuvungcuatoi.php');
    exit;
}
?>

<!DOCTYPE html>
<html lang="vi">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Từ vựng của tôi - LexiLoop</title>
    <link rel="stylesheet" href="../../CSS/Style.css">
    <link rel="stylesheet" href="../../CSS/C_Tuvungcuatoi.css">
    <link rel="stylesheet" href="../../CSS/guest-preview.css">
    <link rel="stylesheet" href="../../CSS/topheader.css">
</head>

<body class="C_Tuvungcuatoi_body">

    <!-- =========================================
         SIDEBAR
         ========================================= -->
    <!-- Sidebar dùng chung cho mọi trang người dùng -->
    <?php
    if ($isLoggedIn) {
        include '../../includes/sidebar_user.php';
    } else {
        include '../../includes/sidebar_guest.php';
    }
    ?>

    <!-- =========================================
         KHU VỰC NỘI DUNG CHÍNH
         ========================================= -->
    <div class="page-content">

        <!-- HEADER -->
        <?php
        $headerTitle = 'Từ vựng của tôi';
        $topHeaderPageActions = '';

        include '../../includes/topheader.php';
        ?>

        <?php if (!$isLoggedIn): ?>
            <?php
            $guestInviteTitle = 'Xây dựng kho từ vựng cá nhân';
            $guestInviteMessage = 'Trang đang ở chế độ xem trước và không tải dữ liệu cá nhân. Đăng nhập để thêm từ, phân loại theo bộ và theo dõi mức độ ghi nhớ.';
            include '../../includes/guest_invite.php';
            ?>
        <?php endif; ?>
        <!-- MAIN -->
        <main class="C_Tuvungcuatoi_main">

            <!-- Thông báo phản hồi PHP -->
            <?php if (!empty($thong_bao)): ?>
                <div class="C_Tuvungcuatoi_alert C_Tuvungcuatoi_alert_<?php echo $loai_thong_bao; ?>" id="C_Tuvungcuatoi_alert">
                    <?php echo htmlspecialchars($thong_bao); ?>
                </div>
            <?php endif; ?>

            <!-- ====================================================
                 BỐ CỤC 2 BÊN
                 ==================================================== -->
            <div class="C_Tuvungcuatoi_layout2Col">

                <?php if ($isLoggedIn): ?>
                    <section class="C_Tuvungcuatoi_stats" aria-label="Tổng quan từ vựng">
                        <article><span>Tổng từ</span><strong><?php echo $thong_ke_tu['tong']; ?></strong></article>
                        <article><span>Đã thuộc</span><strong><?php echo $thong_ke_tu['thuoc']; ?></strong></article>
                        <article><span>Đang học</span><strong><?php echo $thong_ke_tu['chua_thuoc']; ?></strong></article>
                        <article><span>Tỷ lệ %</span><strong><?php echo $thong_ke_tu['phan_tram']; ?>%</strong></article>
                    </section>
                <?php endif; ?>

                <!-- TÌM KIẾM & BẢNG TỪ VỰNG -->
                <div class="C_Tuvungcuatoi_colLeft">
                    <!-- Thanh lọc & tìm kiếm -->
                    <section class="C_Tuvungcuatoi_filterBar">
                        <div class="C_Tuvungcuatoi_searchWrapper">
                            <span class="C_Tuvungcuatoi_searchIcon" aria-hidden="true">
                                <!-- Dán SVG icon tìm kiếm của anh vào đây. -->
                            </span>
                            <input
                                type="text"
                                id="C_Tuvungcuatoi_txtTimKiem"
                                class="C_Tuvungcuatoi_inputSearch"
                                placeholder="Tìm kiếm từ vựng, nghĩa...">
                        </div>

                        <select id="C_Tuvungcuatoi_selChuDe" class="C_Tuvungcuatoi_selectFilter">
                            <option value="">Bộ từ: Tất cả</option>
                            <?php foreach ($danh_sach_bo_tu as $bo_tu): ?>
                                <option value="<?php echo $bo_tu['id']; ?>"><?php echo htmlspecialchars($bo_tu['name']); ?></option>
                            <?php endforeach; ?>
                        </select>

                        <?php if ($isLoggedIn): ?>
                            <button type="button" id="C_Tuvungcuatoi_btnThemTu" class="C_Tuvungcuatoi_btnAdd">+ Thêm từ vựng</button>
                        <?php endif; ?>
                    </section>

                    <!-- Bảng danh sách từ vựng -->
                    <form id="C_Tuvungcuatoi_bulkForm" method="POST" action="C_Tuvungcuatoi.php">
                        <input type="hidden" name="C_Tuvungcuatoi_csrf" value="<?php echo htmlspecialchars($_SESSION['C_Tuvungcuatoi_csrf'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <section class="C_Tuvungcuatoi_bulkBar" id="C_Tuvungcuatoi_bulkBar" hidden>
                            <span><strong id="C_Tuvungcuatoi_selectedCount">0</strong> từ đã chọn</span>
                            <div>
                                <button type="button" data-bulk-action="bulk_status" data-status="mastered">Thuộc</button>
                                <button type="button" data-bulk-action="bulk_status" data-status="learning">Chưa thuộc</button>
                                <button type="button" data-bulk-action="bulk_delete" class="is-danger">Xóa từ </button>
                            </div>
                            <input type="hidden" name="C_Tuvungcuatoi_action" id="C_Tuvungcuatoi_bulkAction" value="">
                            <input type="hidden" name="C_Tuvungcuatoi_status" id="C_Tuvungcuatoi_bulkStatus" value="">
                        </section>
                        <section class="C_Tuvungcuatoi_tableCard">
                            <div class="C_Tuvungcuatoi_tableResponsive">
                                <table class="C_Tuvungcuatoi_table" id="C_Tuvungcuatoi_table">
                                    <thead>
                                        <tr>
                                            <th class="C_Tuvungcuatoi_th C_Tuvungcuatoi_checkCell"><input type="checkbox" id="C_Tuvungcuatoi_checkAll" aria-label="Chọn tất cả"></th>
                                            <th class="C_Tuvungcuatoi_th">Từ vựng</th>
                                            <th class="C_Tuvungcuatoi_th">Nghĩa</th>
                                            <th class="C_Tuvungcuatoi_th">Loại từ</th>
                                            <th class="C_Tuvungcuatoi_th">Bộ từ</th>
                                            <th class="C_Tuvungcuatoi_th">Ví dụ</th>
                                            <th class="C_Tuvungcuatoi_th">Thuộc</th>
                                            <th class="C_Tuvungcuatoi_th text-center">
                                                Thao tác
                                            </th>
                                        </tr>
                                    </thead>

                                    <tbody>

                                        <?php foreach ($danh_sach_tu as $item): ?>

                                            <tr
                                                data-id="<?php echo $item['id']; ?>"
                                                data-status="<?php echo $item['muc_do_nho']; ?>"
                                                data-topic-id="<?php echo $item['topic_id']; ?>"
                                                data-word="<?php echo htmlspecialchars($item['tu_vung'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-pronunciation="<?php echo htmlspecialchars($item['phien_am'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-part-of-speech="<?php echo htmlspecialchars($item['tu_loai'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-meaning="<?php echo htmlspecialchars($item['nghia'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-example="<?php echo htmlspecialchars($item['cau_vi_du'], ENT_QUOTES, 'UTF-8'); ?>"
                                                data-set-ids="<?php echo htmlspecialchars($item['set_ids'], ENT_QUOTES, 'UTF-8'); ?>">

                                                <td class="C_Tuvungcuatoi_td C_Tuvungcuatoi_checkCell">
                                                    <input type="checkbox" class="C_Tuvungcuatoi_rowCheck" name="C_Tuvungcuatoi_selectedIds[]" value="<?php echo $item['id']; ?>" aria-label="Chọn <?php echo htmlspecialchars($item['tu_vung'], ENT_QUOTES, 'UTF-8'); ?>">
                                                </td>

                                                <!-- TỪ VỰNG + PHIÊN ÂM -->
                                                <td class="C_Tuvungcuatoi_td C_Tuvungcuatoi_wordCell">

                                                    <div class="C_Tuvungcuatoi_word">
                                                        <?php echo htmlspecialchars($item['tu_vung']); ?>
                                                    </div>

                                                    <?php if (!empty($item['phien_am'])): ?>
                                                        <div class="C_Tuvungcuatoi_pronunciation">
                                                            <?php echo htmlspecialchars($item['phien_am']); ?>
                                                        </div>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- NGHĨA -->
                                                <td class="C_Tuvungcuatoi_td">
                                                    <?php echo htmlspecialchars($item['nghia']); ?>
                                                </td>

                                                <!-- LOẠI TỪ -->
                                                <td class="C_Tuvungcuatoi_td">
                                                    <?php if (!empty($item['tu_loai'])): ?>
                                                        <span class="C_Tuvungcuatoi_posTag">
                                                            <?php echo htmlspecialchars($item['tu_loai']); ?>
                                                        </span>
                                                    <?php else: ?>
                                                        <span class="C_Tuvungcuatoi_emptyText">-</span>
                                                    <?php endif; ?>
                                                </td>

                                                <!-- CHỦ ĐỀ -->
                                                <td class="C_Tuvungcuatoi_td">
                                                    <span class="topic-tag">
                                                        <?php echo htmlspecialchars($item['chu_de']); ?>
                                                    </span>
                                                </td>


                                                <!-- CÂU VÍ DỤ -->
                                                <td class="C_Tuvungcuatoi_td C_Tuvungcuatoi_exampleCell">
                                                    <?php if (!empty($item['cau_vi_du'])): ?>
                                                        <?php echo htmlspecialchars($item['cau_vi_du']); ?>
                                                    <?php else: ?>
                                                        <span class="C_Tuvungcuatoi_emptyText">
                                                            None
                                                        </span>
                                                    <?php endif; ?>
                                                </td>


                                                <!-- CÔNG TẮC THUỘC: submit riêng để không làm mất checkbox đang chọn. -->
                                                <td class="C_Tuvungcuatoi_td">
                                                    <label class="C_Tuvungcuatoi_switch" title="Đánh dấu đã thuộc">
                                                        <input type="checkbox" class="C_Tuvungcuatoi_knownToggle" data-vocabulary-id="<?php echo $item['id']; ?>" <?php echo $item['muc_do_nho'] === 'tot' ? 'checked' : ''; ?>>
                                                        <span aria-hidden="true"></span>
                                                    </label>

                                                </td>


                                                <!-- THAO TÁC -->
                                                <td class="C_Tuvungcuatoi_td text-center">
                                                    <?php if ($item['is_owner']): ?>
                                                        <!--
                                                        QUAN TRỌNG: Chỉ hiện CRUD cho từ cá nhân.
                                                        PHP vẫn kiểm tra created_by ở UPDATE/DELETE để
                                                        không thể vượt quyền bằng cách sửa HTML trên trình duyệt.
                                                    -->
                                                        <button
                                                            type="button"
                                                            class="C_Tuvungcuatoi_btnTableAction btn-edit">
                                                            Sửa
                                                        </button>

                                                    <?php else: ?>
                                                        <span class="C_Tuvungcuatoi_emptyText">Chỉ xem</span>
                                                    <?php endif; ?>

                                                </td>

                                            </tr>

                                        <?php endforeach; ?>

                                    </tbody>
                                </table>
                            </div>
                            <nav class="C_Tuvungcuatoi_pagination" id="C_Tuvungcuatoi_pagination" aria-label="Phân trang từ vựng"></nav>
                            <p class="C_Tuvungcuatoi_noResults" id="C_Tuvungcuatoi_noResults" hidden>Không tìm thấy từ vựng phù hợp.</p>
                        </section>
                    </form>
                    <!-- Form độc lập: tránh lồng form trong bảng chọn hàng loạt. -->
                    <form id="C_Tuvungcuatoi_singleStatusForm" method="POST" action="C_Tuvungcuatoi.php">
                        <input type="hidden" name="C_Tuvungcuatoi_csrf" value="<?php echo htmlspecialchars($_SESSION['C_Tuvungcuatoi_csrf'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                        <input type="hidden" name="C_Tuvungcuatoi_action" value="bulk_status">
                        <input type="hidden" name="C_Tuvungcuatoi_selectedIds[]" id="C_Tuvungcuatoi_singleStatusId" value="">
                        <input type="hidden" name="C_Tuvungcuatoi_status" id="C_Tuvungcuatoi_singleStatusValue" value="">
                    </form>
                </div>

                <!-- =========================================
                    MODAL THÊM / SỬA TỪ VỰNG
                ========================================= -->
                <section
                    class="C_Tuvungcuatoi_formModalContainer"
                    id="C_Tuvungcuatoi_formContainer">

                    <!-- Lớp nền tối -->
                    <div class="C_Tuvungcuatoi_modalOverlay"></div>

                    <!-- Hộp form -->
                    <div class="C_Tuvungcuatoi_formCard">

                        <!-- Nút đóng -->
                        <button
                            type="button"
                            id="C_Tuvungcuatoi_btnDong"
                            class="C_Tuvungcuatoi_btnClose"
                            aria-label="Đóng">
                            &times;
                        </button>

                        <h2
                            class="C_Tuvungcuatoi_formTitle"
                            id="C_Tuvungcuatoi_formTitle">
                            Thêm từ vựng
                        </h2>

                        <form
                            id="C_Tuvungcuatoi_formThemTu"
                            action="C_Tuvungcuatoi.php"
                            method="POST">

                            <!-- QUAN TRỌNG: Server bắt buộc token này trước khi thêm/sửa. -->
                            <input type="hidden" name="C_Tuvungcuatoi_csrf" value="<?php echo htmlspecialchars($_SESSION['C_Tuvungcuatoi_csrf'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">

                            <input
                                type="hidden"
                                id="C_Tuvungcuatoi_editId"
                                name="C_Tuvungcuatoi_editId"
                                value="">

                            <!--
                                Bộ từ phải được chọn trước khi nhập từ. Nút bên cạnh
                                mở form tạo bộ từ ngay trong modal, không chuyển trang.
                            -->
                            <div class="C_Tuvungcuatoi_setPicker">
                                <div class="C_Tuvungcuatoi_setPickerField">
                                    <label for="C_Tuvungcuatoi_selBoTuForm" class="C_Tuvungcuatoi_label">
                                        Thêm vào bộ từ <span class="required">*</span>
                                    </label>
                                    <select
                                        id="C_Tuvungcuatoi_selBoTuForm"
                                        name="C_Tuvungcuatoi_selBoTuForm"
                                        class="C_Tuvungcuatoi_input"
                                        required>
                                        <option value="">-- Chọn bộ từ cá nhân --</option>
                                        <?php foreach ($danh_sach_bo_tu as $bo_tu): ?>
                                            <option value="<?php echo $bo_tu['id']; ?>">
                                                <?php echo htmlspecialchars($bo_tu['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="C_Tuvungcuatoi_error" id="errorChuDe"></small>
                                </div>
                                <button type="button" id="C_Tuvungcuatoi_btnShowCreateSet" class="C_Tuvungcuatoi_btnCreateSet">
                                    + Tạo bộ từ vựng
                                </button>
                            </div>

                            <div class="C_Tuvungcuatoi_formRow">

                                <!-- Từ vựng -->
                                <div class="C_Tuvungcuatoi_formGroup">

                                    <label
                                        for="C_Tuvungcuatoi_txtTuVung"
                                        class="C_Tuvungcuatoi_label">
                                        Từ vựng <span class="required">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        id="C_Tuvungcuatoi_txtTuVung"
                                        name="C_Tuvungcuatoi_txtTuVung"
                                        class="C_Tuvungcuatoi_input"
                                        maxlength="100"
                                        placeholder="VD: Airport"
                                        required>

                                    <small
                                        class="C_Tuvungcuatoi_error"
                                        id="errorTuVung">
                                    </small>
                                </div>


                                <!-- Phiên âm -->
                                <div class="C_Tuvungcuatoi_formGroup">

                                    <label
                                        for="C_Tuvungcuatoi_txtPhienAm"
                                        class="C_Tuvungcuatoi_label">
                                        Phiên âm
                                    </label>

                                    <input
                                        type="text"
                                        id="C_Tuvungcuatoi_txtPhienAm"
                                        name="C_Tuvungcuatoi_txtPhienAm"
                                        class="C_Tuvungcuatoi_input"
                                        maxlength="100"
                                        placeholder="VD: /ˈeərpɔːrt/">

                                    <small
                                        class="C_Tuvungcuatoi_error"
                                        id="errorPhienAm">
                                    </small>
                                </div>

                                <!-- Nghĩa -->
                                <div class="C_Tuvungcuatoi_formGroup">

                                    <label
                                        for="C_Tuvungcuatoi_txtNghia"
                                        class="C_Tuvungcuatoi_label">
                                        Nghĩa tiếng Việt <span class="required">*</span>
                                    </label>

                                    <input
                                        type="text"
                                        id="C_Tuvungcuatoi_txtNghia"
                                        name="C_Tuvungcuatoi_txtNghia"
                                        class="C_Tuvungcuatoi_input"
                                        maxlength="255"
                                        placeholder="VD: Sân bay"
                                        required>

                                    <small
                                        class="C_Tuvungcuatoi_error"
                                        id="errorNghia">
                                    </small>

                                </div>


                                <!-- Từ loại -->
                                <div class="C_Tuvungcuatoi_formGroup">

                                    <label
                                        for="C_Tuvungcuatoi_selTuLoai"
                                        class="C_Tuvungcuatoi_label">
                                        Từ loại <span class="required">*</span>
                                    </label>

                                    <select
                                        id="C_Tuvungcuatoi_selTuLoai"
                                        name="C_Tuvungcuatoi_selTuLoai"
                                        class="C_Tuvungcuatoi_input"
                                        required>

                                        <option value="">
                                            -- Chọn từ loại --
                                        </option>

                                        <option value="noun">Noun - Danh từ</option>
                                        <option value="verb">Verb - Động từ</option>
                                        <option value="adjective">Adjective - Tính từ</option>
                                        <option value="adverb">Adverb - Trạng từ</option>
                                        <option value="pronoun">Pronoun - Đại từ</option>
                                        <option value="preposition">Preposition - Giới từ</option>
                                        <option value="conjunction">Conjunction - Liên từ</option>
                                        <option value="phrase">Phrase - Cụm từ</option>
                                        <option value="other">Other - Khác</option>

                                    </select>

                                    <small
                                        class="C_Tuvungcuatoi_error"
                                        id="errorTuLoai">
                                    </small>

                                </div>
                            </div>

                            <div class="C_Tuvungcuatoi_formGroup">

                                <label
                                    for="C_Tuvungcuatoi_txtViDu"
                                    class="C_Tuvungcuatoi_label">
                                    Câu ví dụ
                                </label>

                                <textarea
                                    id="C_Tuvungcuatoi_txtViDu"
                                    name="C_Tuvungcuatoi_txtViDu"
                                    class="C_Tuvungcuatoi_textarea"
                                    maxlength="500"
                                    rows="3"
                                    placeholder="VD: We arrived at the airport two hours early."></textarea>

                            </div>
                            <div class="C_Tuvungcuatoi_formButtons">

                                <button
                                    type="button"
                                    id="C_Tuvungcuatoi_btnHuy"
                                    class="C_Tuvungcuatoi_btnCancel">
                                    Hủy bỏ
                                </button>

                                <button
                                    type="submit"
                                    id="C_Tuvungcuatoi_btnLuu"
                                    class="C_Tuvungcuatoi_btnSave">
                                    Lưu lại
                                </button>

                            </div>

                        </form>

                        <!-- Form độc lập để tránh nested form và giữ PRG cho thao tác tạo bộ từ. -->
                        <form
                            id="C_Tuvungcuatoi_formTaoBoTu"
                            action="C_Tuvungcuatoi.php"
                            method="POST"
                            hidden>
                            <input type="hidden" name="C_Tuvungcuatoi_csrf" value="<?php echo htmlspecialchars($_SESSION['C_Tuvungcuatoi_csrf'] ?? '', ENT_QUOTES, 'UTF-8'); ?>">
                            <input type="hidden" name="C_Tuvungcuatoi_action" value="create_set">

                            <div class="C_Tuvungcuatoi_formGroup">
                                <label for="C_Tuvungcuatoi_newSetName" class="C_Tuvungcuatoi_label">
                                    Tên bộ từ vựng <span class="required">*</span>
                                </label>
                                <input type="text" id="C_Tuvungcuatoi_newSetName" name="C_Tuvungcuatoi_newSetName" class="C_Tuvungcuatoi_input" maxlength="100" placeholder="VD: IELTS Writing - Chủ đề giáo dục" required>
                            </div>

                            <div class="C_Tuvungcuatoi_formGroup">
                                <label for="C_Tuvungcuatoi_newSetDescription" class="C_Tuvungcuatoi_label">Mô tả (không bắt buộc)</label>
                                <textarea id="C_Tuvungcuatoi_newSetDescription" name="C_Tuvungcuatoi_newSetDescription" class="C_Tuvungcuatoi_textarea" maxlength="255" rows="4" placeholder="Ghi chú ngắn để dễ nhận biết bộ từ này."></textarea>
                            </div>

                            <div class="C_Tuvungcuatoi_formButtons">
                                <button type="button" id="C_Tuvungcuatoi_btnCancelCreateSet" class="C_Tuvungcuatoi_btnCancel">Quay lại thêm từ</button>
                                <button type="submit" class="C_Tuvungcuatoi_btnSave">Tạo bộ từ</button>
                            </div>
                        </form>

                    </div>

                </section>

            </div>

        </main>
    </div>

    <script src="../../JS/jquery-4.0.0.min.js"></script>
    <script src="../../JS/C_Tuvungcuatoi.js"></script>
    <script src="../../JS/auth.js"></script>
</body>

</html>
