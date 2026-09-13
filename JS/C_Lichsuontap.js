document.addEventListener("DOMContentLoaded", () => {
    // 1. Lấy phần tử select bộ lọc thời gian
    const filterSelect = document.getElementById("C_Lichsuontap_filterSelect");
    const tableBody = document.querySelector("#C_Lichsuontap_table tbody");

if (filterSelect) {
    filterSelect.addEventListener("change", (event) => {
        /*
         * URL hiện tại, ví dụ:
         * http://localhost/pages/user/C_Lichsuontap.php?range=7
         */
        const currentUrl = new URL(window.location.href);

        /*
         * Cập nhật query string theo giá trị user chọn.
         * PHP sẽ nhận được $_GET['range'].
         */
        currentUrl.searchParams.set("range", event.target.value);
        currentUrl.searchParams.delete("page");

        /*
         * Tải lại trang để PHP truy vấn và render dữ liệu mới.
         *
         * Cách này đơn giản hơn AJAX, dễ kiểm tra,
         * hoạt động tốt với nút Back/Forward của trình duyệt.
         */
        window.location.assign(currentUrl.toString());
    });
}

    // 2. Tương tác với các cột biểu đồ
    const bars = document.querySelectorAll(".C_Lichsuontap_bar");
    bars.forEach((bar) => {
        bar.addEventListener("click", () => {
            const count = bar.getAttribute("data-count");
            alert(`Số lượng từ ôn tập trong ngày này là: ${count}`);
        });
    });
});
