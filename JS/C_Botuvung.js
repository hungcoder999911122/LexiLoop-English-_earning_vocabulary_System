$(function () {
    const $modal = $("#C_Botuvung_modal");
    const $form = $("#C_Botuvung_form");
    const $title = $("#C_Botuvung_dialogTitle");
    const $action = $("#C_Botuvung_action");
    const $setId = $("#C_Botuvung_setId");
    const $name = $("#C_Botuvung_name");
    const $description = $("#C_Botuvung_description");
    const $descriptionCount = $("#C_Botuvung_descriptionCount");

    function updateDescriptionCount() {
        $descriptionCount.text($description.val().length);
    }

    function openModal(mode, data = {}) {
        $form[0].reset();
        $action.val(mode);
        $setId.val(data.id || 0);
        $name.val(data.name || "");
        $description.val(data.description || "");
        $title.text(mode === "update" ? "Chỉnh sửa bộ từ" : "Tạo bộ từ mới");
        updateDescriptionCount();
        $modal.removeAttr("hidden");
        $(document.body).addClass("C_Botuvung_modalOpen");
        window.setTimeout(() => $name.trigger("focus"), 0);
    }

    function closeModal() {
        $modal.attr("hidden", true);
        $(document.body).removeClass("C_Botuvung_modalOpen");
    }

    $("[data-open-set-form]").on("click", function () { openModal("create"); });
    $("[data-edit-set]").on("click", function () {
        const $button = $(this);
        openModal("update", {
            id: $button.data("set-id"),
            name: $button.attr("data-set-name"),
            description: $button.attr("data-set-description")
        });
    });
    $("[data-close-set-form]").on("click", closeModal);
    $description.on("input", updateDescriptionCount);

    $(document).on("keydown", function (event) {
        if (event.key === "Escape" && !$modal.is("[hidden]")) closeModal();
    });

    // Xác nhận hỗ trợ UX; PHP vẫn kiểm tra CSRF và quyền sở hữu khi xóa.
    $(".C_Botuvung_deleteForm").on("submit", function (event) {
        const setName = $(this).attr("data-set-name") || "bộ từ này";
        if (!window.confirm(`Xóa “${setName}”? Các từ vựng gốc vẫn được giữ lại.`)) {
            event.preventDefault();
        }
    });

    const $alert = $(".C_Botuvung_alert");
    if ($alert.length) {
        window.setTimeout(function () { $alert.fadeOut(250); }, 4000);
    }
});
