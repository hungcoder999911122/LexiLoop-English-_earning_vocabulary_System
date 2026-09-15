<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once($_SERVER['DOCUMENT_ROOT'] . '/includes/personal_vocabulary_controller.php');
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
