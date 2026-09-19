$(document).ready(function () {

    const $searchInput = $("#vocab-search");
    const $filterSelect = $("#vocab-filter-select");
    const $rows = $("#vocab-data-body tr");

    const ITEMS_PER_PAGE = 8;

    let currentPage = 1;
    let filteredRows = $rows;


    // ==============================
    // 1. TÌM KIẾM + LỌC
    // ==============================
    function filterVocabs() {

        const keyword = $searchInput.val()
            .trim()
            .toLowerCase();

        const filter = $filterSelect.val();

        filteredRows = $rows.filter(function () {

            const $row = $(this);

            const word = $row
                .find(".word-name")
                .text()
                .trim()
                .toLowerCase();

            const meaning = $row
                .find(".col-meaning")
                .text()
                .trim()
                .toLowerCase();

            // Tìm kiếm
            const matchKeyword =
                word.includes(keyword) ||
                meaning.includes(keyword);

            /*
             * Giai đoạn HTML tĩnh:
             * chưa có dữ liệu trạng thái học.
             *
             * Sau này PHP có thể thêm:
             *
             * data-status="new"
             * data-status="review"
             */

            let matchFilter = true;

            if (filter !== "all") {

                const status =
                    $row.data("status");

                matchFilter =
                    status === filter;
            }

            return matchKeyword && matchFilter;
        });

        currentPage = 1;

        renderVocabs();
        renderPagination();
    }


    // ==============================
    // 2. HIỂN THỊ DANH SÁCH
    // ==============================
    function renderVocabs() {

        $rows.hide();

        const start =
            (currentPage - 1) * ITEMS_PER_PAGE;

        const end =
            start + ITEMS_PER_PAGE;

        filteredRows
            .slice(start, end)
            .show();

        $(".vocab-empty").remove();

        if (filteredRows.length === 0) {

            $("#vocab-data-body").append(`
                <tr class="vocab-empty">
                    <td colspan="4">
                        Không tìm thấy từ vựng phù hợp.
                    </td>
                </tr>
            `);
        }
    }


    // ==============================
    // 3. PHÂN TRANG
    // ==============================
    function renderPagination() {

        let $pagination = $(".vocab-pagination");

        if ($pagination.length === 0) {

            $pagination = $(`
                <nav
                    class="pagination vocab-pagination"
                    aria-label="Phân trang từ vựng">
                </nav>
            `);

            $(".vocab-card").after($pagination);
        }

        $pagination.empty();

        const totalPages =
            Math.ceil(
                filteredRows.length /
                ITEMS_PER_PAGE
            );

        if (totalPages <= 1) {
            return;
        }


        // Trang trước
        const $prev = $(`
            <button
                type="button"
                class="pagination-button">
                &laquo;
            </button>
        `);

        if (currentPage === 1) {
            $prev.prop("disabled", true);
        }

        $prev.on("click", function () {

            if (currentPage > 1) {

                currentPage--;

                renderVocabs();
                renderPagination();
            }
        });

        $pagination.append($prev);


        // Số trang
        for (
            let page = 1;
            page <= totalPages;
            page++
        ) {

            const $button = $(`
                <button
                    type="button"
                    class="pagination-button">
                    ${page}
                </button>
            `);

            if (page === currentPage) {

                $button.addClass("active");

                $button.attr(
                    "aria-current",
                    "page"
                );
            }

            $button.on("click", function () {

                currentPage = page;

                renderVocabs();
                renderPagination();
            });

            $pagination.append($button);
        }


        // Trang sau
        const $next = $(`
            <button
                type="button"
                class="pagination-button">
                &raquo;
            </button>
        `);

        if (currentPage === totalPages) {
            $next.prop("disabled", true);
        }

        $next.on("click", function () {

            if (currentPage < totalPages) {

                currentPage++;

                renderVocabs();
                renderPagination();
            }
        });

        $pagination.append($next);
    }


    // ==============================
    // 4. SỰ KIỆN TÌM KIẾM
    // ==============================
    $searchInput.on("input", function () {
        filterVocabs();
    });


    // ==============================
    // 5. SỰ KIỆN LỌC
    // ==============================
    $filterSelect.on("change", function () {
        filterVocabs();
    });


    // ==============================
    // 6. KHỞI TẠO
    // ==============================
    filterVocabs();

});