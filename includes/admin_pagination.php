<?php
declare(strict_types=1);

/** Đọc tham số GET dạng chuỗi; bỏ qua giá trị mảng hoặc quá dài. */
function adminQueryText(string $name, string $default = ''): string
{
    $value = $_GET[$name] ?? $default;
    return is_string($value) && strlen($value) <= 500 ? trim($value) : $default;
}

/** Tìm chuỗi con theo nghĩa literal, không coi % và _ là ký tự wildcard. */
function adminSearchPattern(string $keyword): string
{
    return '%' . str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $keyword) . '%';
}

/**
 * COUNT và SELECT phải có cùng điều kiện lọc. SQL do backend cung cấp,
 * dữ liệu GET chỉ truyền qua prepared statement. SELECT cần ORDER BY ổn định.
 */
function adminPaginateView(mysqli $connection, string $countSql, string $selectSql,
    string $parameter = 'page', int $perPage = 10, string $types = '', array $values = []): array
{
    $total = (int) (dbSelectView($connection, $countSql, $types, $values)[0]['total'] ?? 0);
    $pages = max(1, (int) ceil($total / $perPage));
    $requested = filter_var(adminQueryText($parameter, '1'), FILTER_VALIDATE_INT);
    $page = min($pages, max(1, $requested === false ? 1 : $requested));
    $offset = ($page - 1) * $perPage;
    $rows = dbSelectView($connection, $selectSql . ' LIMIT ? OFFSET ?', $types . 'ii', array_merge($values, [$perPage, $offset]));
    return compact('rows', 'total', 'pages', 'page', 'perPage', 'offset', 'parameter');
}

/** Giữ bộ lọc và trang của bảng khác khi điều hướng hoặc gửi form POST. */
function adminPageUrl(array $changes = [], string $anchor = ''): string
{
    $query = array_filter($_GET, static fn($value): bool => is_string($value));
    $query = array_merge($query, $changes);
    $query = array_filter($query, static fn($value): bool => $value !== null);
    return '?' . http_build_query($query) . ($anchor !== '' ? '#' . rawurlencode($anchor) : '');
}

/** Thanh phân trang dùng chung, hiển thị tối đa 5 số trang và đầu/cuối. */
function adminRenderPagination(array $pagination, string $label, array $changes = [], string $anchor = ''): void
{
    $escape = static fn(string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    $page = $pagination['page'];
    $pages = $pagination['pages'];
    $first = $pagination['total'] > 0 ? $pagination['offset'] + 1 : 0;
    $last = min($pagination['offset'] + $pagination['perPage'], $pagination['total']);
    echo '<nav class="admin-pagination" aria-label="' . $escape($label) . '">';
    echo '<span class="admin-pagination-summary">' . number_format($first) . '–' . number_format($last) . ' / ' . number_format($pagination['total']) . ' bản ghi</span>';
    if ($pages > 1) {
        $link = static function (int $target, string $text, bool $current = false) use ($pagination, $changes, $anchor, $escape): void {
            $url = adminPageUrl(array_merge($changes, [$pagination['parameter'] => $target]), $anchor);
            echo '<a href="' . $escape($url) . '"' . ($current ? ' aria-current="page"' : '') . '>' . $escape($text) . '</a>';
        };
        echo '<div class="admin-pagination-links">';
        if ($page > 1) {
            $link($page - 1, '‹ Trước');
        }
        $start = max(1, min($page - 2, $pages - 4));
        $end = min($pages, $start + 4);
        if ($start > 1) {
            $link(1, '1');
            if ($start > 2) echo '<span aria-hidden="true">…</span>';
        }
        for ($number = $start; $number <= $end; $number++) {
            $link($number, (string) $number, $number === $page);
        }
        if ($end < $pages) {
            if ($end < $pages - 1) echo '<span aria-hidden="true">…</span>';
            $link($pages, (string) $pages);
        }
        if ($page < $pages) {
            $link($page + 1, 'Sau ›');
        }
        echo '</div>';
    }
    echo '</nav>';
}
