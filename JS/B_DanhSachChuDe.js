$(document).ready(function () {

    const $searchInput = $("#topic-search");
    const $filterSelect = $("#topic-filter");
    const $topicCards = $(".topic-card");
    const $pagination = $(".pagination");

    const ITEMS_PER_PAGE = 8;

    let currentPage = 1;
    let filteredCards = $topicCards;

    // ==============================
    // 1. LỌC TOPIC
    // ==============================
    function filterTopics() {

        const keyword = $searchInput.val()
            .trim()
            .toLowerCase();

        const category = $filterSelect.val();

        filteredCards = $topicCards.filter(function () {

            const $card = $(this);

            const title = $card.find(".topic-title")
                .text()
                .trim()
                .toLowerCase();

            const cardCategory = $card.data("category");

            const matchKeyword =
                title.includes(keyword);

            const matchCategory =
                category === "all" ||
                cardCategory === category;

            return matchKeyword && matchCategory;
        });

        currentPage = 1;

        renderTopics();
        renderPagination();
    }


    // ==============================
    // 2. HIỂN THỊ TOPIC
    // ==============================
    function renderTopics() {

        // Ẩn tất cả card trước
        $topicCards.hide();

        const start =
            (currentPage - 1) * ITEMS_PER_PAGE;

        const end =
            start + ITEMS_PER_PAGE;

        filteredCards
            .slice(start, end)
            .show();

        // Không có kết quả
        $(".topic-empty").remove();

        if (filteredCards.length === 0) {

            $(".topic-list").append(`
                <p class="topic-empty">
                    Không tìm thấy chủ đề phù hợp.
                </p>
            `);
        }
    }


    // ==============================
    // 3. PHÂN TRANG
    // ==============================
    function renderPagination() {

        $pagination.empty();

        const totalPages =
            Math.ceil(
                filteredCards.length / ITEMS_PER_PAGE
            );

        // Không cần pagination
        if (totalPages <= 1) {
            return;
        }

        // Trang trước
        const $prev = $(`
            <button
                type="button"
                class="pagination-button"
                aria-label="Trang trước">
                &laquo;
            </button>
        `);

        if (currentPage === 1) {
            $prev.prop("disabled", true);
        }

        $prev.on("click", function () {

            if (currentPage > 1) {
                currentPage--;
                renderTopics();
                renderPagination();
            }
        });

        $pagination.append($prev);


        // Các trang
        for (let page = 1; page <= totalPages; page++) {

            const $button = $(`
                <button
                    type="button"
                    class="pagination-button">
                    ${page}
                </button>
            `);

            if (page === currentPage) {
                $button.addClass("active");
                $button.attr("aria-current", "page");
            }

            $button.on("click", function () {

                currentPage = page;

                renderTopics();
                renderPagination();
            });

            $pagination.append($button);
        }


        // Trang sau
        const $next = $(`
            <button
                type="button"
                class="pagination-button"
                aria-label="Trang sau">
                &raquo;
            </button>
        `);

        if (currentPage === totalPages) {
            $next.prop("disabled", true);
        }

        $next.on("click", function () {

            if (currentPage < totalPages) {
                currentPage++;
                renderTopics();
                renderPagination();
            }
        });

        $pagination.append($next);
    }


    // ==============================
    // 4. SỰ KIỆN TÌM KIẾM
    // ==============================
    $searchInput.on("input", function () {
        filterTopics();
    });


    // ==============================
    // 5. SỰ KIỆN LỌC
    // ==============================
    $filterSelect.on("change", function () {
        filterTopics();
    });


    // ==============================
    // 6. KHỞI TẠO
    // ==============================
    filterTopics();

});