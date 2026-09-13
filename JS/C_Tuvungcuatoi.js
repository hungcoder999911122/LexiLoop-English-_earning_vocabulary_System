document.addEventListener("DOMContentLoaded", () => {
    const getById = (id) => document.getElementById(id);

    const addButton = getById("C_Tuvungcuatoi_btnThemTu");
    const modal = getById("C_Tuvungcuatoi_formContainer");
    const form = getById("C_Tuvungcuatoi_formThemTu");
    const createSetForm = getById("C_Tuvungcuatoi_formTaoBoTu");
    const title = getById("C_Tuvungcuatoi_formTitle");
    const editId = getById("C_Tuvungcuatoi_editId");
    const wordInput = getById("C_Tuvungcuatoi_txtTuVung");
    const pronunciationInput = getById("C_Tuvungcuatoi_txtPhienAm");
    const meaningInput = getById("C_Tuvungcuatoi_txtNghia");
    const partOfSpeechSelect = getById("C_Tuvungcuatoi_selTuLoai");
    const topicSelect = getById("C_Tuvungcuatoi_selBoTuForm");
    const exampleInput = getById("C_Tuvungcuatoi_txtViDu");

    // QUAN TRỌNG: Chỉ dùng một cơ chế điều khiển modal (DOM thuần).
    // Trước đây jQuery và DOM thuần cùng gắn sự kiện làm luồng mở/đóng bị chồng chéo.
    function openModal() {
        // Mỗi lần mở modal từ trang chính, luôn quay về form thêm/sửa từ.
        form.hidden = false;
        if (createSetForm) createSetForm.hidden = true;
        modal.classList.add("is-open");
        document.body.classList.add("modal-open");
    }

    function closeModal() {
        modal.classList.remove("is-open");
        document.body.classList.remove("modal-open");
    }

    function resetVocabularyForm() {
        form.reset();
        editId.value = "";
        title.textContent = "Thêm từ vựng";
    }

    addButton?.addEventListener("click", () => {
        resetVocabularyForm();
        openModal();
        wordInput.focus();
    });

    getById("C_Tuvungcuatoi_btnHuy")?.addEventListener("click", () => {
        resetVocabularyForm();
        closeModal();
    });

    // Hai biểu mẫu là hai <form> ngang hàng, tuyệt đối không lồng form.
    // Lồng form khiến trình duyệt gửi sai POST và thường dẫn tới cảnh báo resubmit.
    getById("C_Tuvungcuatoi_btnShowCreateSet")?.addEventListener("click", () => {
        if (!createSetForm) return;
        form.hidden = true;
        createSetForm.hidden = false;
        createSetForm.reset();
        title.textContent = "Tạo bộ từ vựng mới";
        getById("C_Tuvungcuatoi_newSetName")?.focus();
    });

    getById("C_Tuvungcuatoi_btnCancelCreateSet")?.addEventListener("click", () => {
        if (!createSetForm) return;
        createSetForm.reset();
        createSetForm.hidden = true;
        form.hidden = false;
        title.textContent = "Thêm từ vựng";
        topicSelect.focus();
    });

    getById("C_Tuvungcuatoi_btnDong")?.addEventListener("click", closeModal);
    modal?.querySelector(".C_Tuvungcuatoi_modalOverlay")?.addEventListener("click", closeModal);

    document.querySelectorAll("#C_Tuvungcuatoi_table tbody tr").forEach((row) => {
        row.querySelector(".btn-edit")?.addEventListener("click", () => {
            // QUAN TRỌNG: Không đọc row.cells[n]. Cấu trúc bảng có thể thay đổi
            // (đã từng thêm cột 'Từ loại'), khiến nghĩa/chủ đề bị lấy nhầm.
            // data-* là dữ liệu nguồn dành riêng cho thao tác sửa.
            wordInput.value = row.dataset.word || "";
            pronunciationInput.value = row.dataset.pronunciation || "";
            meaningInput.value = row.dataset.meaning || "";
            partOfSpeechSelect.value = row.dataset.partOfSpeech || "";
            topicSelect.value = (row.dataset.setIds || "").split(",")[0] || "";
            exampleInput.value = row.dataset.example || "";
            editId.value = row.dataset.id || "";

            title.textContent = `Chỉnh sửa từ: "${row.dataset.word || ""}"`;
            openModal();
            wordInput.focus();
        });
    });

    // Xác nhận là UX; việc xóa thật và kiểm tra quyền nằm ở PHP.
    document.querySelectorAll(".C_Tuvungcuatoi_deleteForm").forEach((deleteForm) => {
        deleteForm.addEventListener("submit", (event) => {
            const row = deleteForm.closest("tr");
            const word = row?.dataset.word || "từ vựng này";

            if (!window.confirm(`Bạn có chắc muốn xóa "${word}"?`)) {
                event.preventDefault();
            }
        });
    });

    const searchInput = getById("C_Tuvungcuatoi_txtTimKiem");
    const topicFilter = getById("C_Tuvungcuatoi_selChuDe");
    const vocabularyRows = [...document.querySelectorAll("#C_Tuvungcuatoi_table tbody tr")];
    const pagination = getById("C_Tuvungcuatoi_pagination");
    const noResults = getById("C_Tuvungcuatoi_noResults");
    const rowsPerPage = 10;
    let currentPage = 1;

    function renderPagination(filteredRows) {
        if (!pagination) return;

        const totalPages = Math.ceil(filteredRows.length / rowsPerPage);
        pagination.replaceChildren();
        pagination.hidden = totalPages <= 1;

        for (let pageNumber = 1; pageNumber <= totalPages; pageNumber += 1) {
            const button = document.createElement("button");
            button.type = "button";
            button.textContent = pageNumber;
            button.classList.toggle("is-active", pageNumber === currentPage);
            button.setAttribute("aria-label", `Trang ${pageNumber}`);
            if (pageNumber === currentPage) button.setAttribute("aria-current", "page");
            button.addEventListener("click", () => {
                currentPage = pageNumber;
                filterTable(false);
            });
            pagination.append(button);
        }
    }

    function filterTable(resetPage = true) {
        const query = searchInput.value.toLowerCase().trim();
        const selectedSetId = topicFilter.value;
        const filteredRows = vocabularyRows.filter((row) => {
            const word = (row.dataset.word || "").toLowerCase();
            const meaning = (row.dataset.meaning || "").toLowerCase();
            return (word.includes(query) || meaning.includes(query))
                && (!selectedSetId || (row.dataset.setIds || "").split(",").includes(selectedSetId));
        });

        if (resetPage) currentPage = 1;
        const totalPages = Math.max(1, Math.ceil(filteredRows.length / rowsPerPage));
        currentPage = Math.min(currentPage, totalPages);
        const firstRowIndex = (currentPage - 1) * rowsPerPage;
        const visibleRows = new Set(filteredRows.slice(firstRowIndex, firstRowIndex + rowsPerPage));

        vocabularyRows.forEach((row) => { row.hidden = !visibleRows.has(row); });
        if (noResults) noResults.hidden = filteredRows.length > 0;
        renderPagination(filteredRows);
        refreshBulkBar();
    }

    searchInput?.addEventListener("input", filterTable);
    topicFilter?.addEventListener("change", filterTable);

    // Chọn nhiều từ chỉ phục vụ thao tác hàng loạt; PHP mới kiểm tra quyền xóa.
    const checkAll = getById("C_Tuvungcuatoi_checkAll");
    const bulkBar = getById("C_Tuvungcuatoi_bulkBar");
    const selectedCount = getById("C_Tuvungcuatoi_selectedCount");
    const bulkStatus = getById("C_Tuvungcuatoi_bulkStatus");
    const bulkAction = getById("C_Tuvungcuatoi_bulkAction");
    const bulkForm = getById("C_Tuvungcuatoi_bulkForm");
    const rowChecks = [...document.querySelectorAll(".C_Tuvungcuatoi_rowCheck")];

    function refreshBulkBar() {
        const checked = rowChecks.filter((checkbox) => checkbox.checked);
        const visibleChecks = rowChecks.filter((checkbox) => !checkbox.closest("tr").hidden);
        const checkedVisible = visibleChecks.filter((checkbox) => checkbox.checked);
        selectedCount.textContent = checked.length;
        bulkBar.hidden = checked.length === 0;
        if (checkAll) {
            checkAll.checked = visibleChecks.length > 0 && checkedVisible.length === visibleChecks.length;
            checkAll.indeterminate = checkedVisible.length > 0 && checkedVisible.length < visibleChecks.length;
        }
    }

    checkAll?.addEventListener("change", () => {
        // "Chọn tất cả" chỉ tác động 10 dòng đang thấy ở trang hiện tại.
        rowChecks.forEach((checkbox) => {
            if (!checkbox.closest("tr").hidden) checkbox.checked = checkAll.checked;
        });
        refreshBulkBar();
    });
    rowChecks.forEach((checkbox) => checkbox.addEventListener("change", refreshBulkBar));
    document.querySelectorAll(".C_Tuvungcuatoi_bulkBar button[data-bulk-action]").forEach((button) => {
        button.addEventListener("click", () => {
            bulkAction.value = button.dataset.bulkAction;
            bulkStatus.value = button.dataset.status || "";

            if (button.dataset.bulkAction === "bulk_delete" && !window.confirm("Bạn có chắc muốn xóa các từ cá nhân đã chọn?")) {
                return;
            }

            // QUAN TRỌNG: button type=button, rồi JS gán action rõ ràng trước
            // khi submit. Nhờ vậy ba nút không thể gửi nhầm cùng hành động xóa.
            bulkForm.requestSubmit();
        });
    });

    // Form riêng không nằm trong bulkForm: tránh nested form và đảm bảo công tắc
    // gửi đúng vocabulary_id, sau đó PHP redirect về GET để trạng thái được giữ lại.
    const singleStatusForm = getById("C_Tuvungcuatoi_singleStatusForm");
    const singleStatusId = getById("C_Tuvungcuatoi_singleStatusId");
    const singleStatusValue = getById("C_Tuvungcuatoi_singleStatusValue");
    document.querySelectorAll(".C_Tuvungcuatoi_knownToggle").forEach((toggle) => {
        toggle.addEventListener("change", () => {
            singleStatusId.value = toggle.dataset.vocabularyId;
            singleStatusValue.value = toggle.checked ? "mastered" : "learning";
            singleStatusForm.requestSubmit();
        });
    });

    // Khởi tạo trang đầu sau khi toàn bộ điều khiển chọn hàng đã sẵn sàng.
    filterTable();

    const alertBox = getById("C_Tuvungcuatoi_alert");
    if (alertBox) {
        window.setTimeout(() => {
            alertBox.style.transition = "opacity 0.4s ease";
            alertBox.style.opacity = "0";
            window.setTimeout(() => alertBox.remove(), 400);
        }, 4000);
    }
});
