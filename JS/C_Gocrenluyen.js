$(function () {
    // Chặn liên kết vô hiệu bằng jQuery; PHP vẫn quyết định bộ từ có dữ liệu hay không.
    $(".C_Gocrenluyen_modeCard[aria-disabled='true']").on("click", function (event) {
        event.preventDefault();
    });

    // Chọn nội dung hoặc số lượng sẽ gửi GET; PHP xác thực và tính lại dữ liệu.
    $("#C_Gocrenluyen_collection, #C_Gocrenluyen_limit").on("change", function () {
        $("#C_Gocrenluyen_filters").trigger("submit");
    });
});
