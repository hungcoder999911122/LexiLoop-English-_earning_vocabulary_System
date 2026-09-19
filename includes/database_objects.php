<?php
declare(strict_types=1);

/**
 * Gọi Stored Procedure bằng prepared statement và lấy result set đầu tiên.
 * Sau CALL, MySQL có thể trả thêm result set rỗng; phải giải phóng chúng để
 * kết nối tiếp tục dùng được (tránh lỗi "Commands out of sync").
 *
 * @return array<int, array<string, mixed>>
 */
function dbCallProcedure(mysqli $connection, string $callSql, string $types = '', array $parameters = []): array
{
    $statement = mysqli_prepare($connection, $callSql);
    if (!$statement) {
        throw new RuntimeException('Không thể chuẩn bị Stored Procedure.');
    }

    try {
        if ($types !== '') {
            $bindValues = [$types];
            foreach ($parameters as $index => $value) {
                $parameters[$index] = $value;
                $bindValues[] = &$parameters[$index];
            }
            if (!mysqli_stmt_bind_param($statement, ...$bindValues)) {
                throw new RuntimeException('Không thể gắn tham số Stored Procedure.');
            }
        }

        mysqli_stmt_execute($statement);
        $rows = [];
        $result = mysqli_stmt_get_result($statement);
        if ($result instanceof mysqli_result) {
            $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);
            mysqli_free_result($result);
        }
        mysqli_stmt_close($statement);

        while (mysqli_more_results($connection) && mysqli_next_result($connection)) {
            $extraResult = mysqli_store_result($connection);
            if ($extraResult instanceof mysqli_result) {
                mysqli_free_result($extraResult);
            }
        }
        return $rows;
    } catch (Throwable $error) {
        mysqli_stmt_close($statement);
        while (mysqli_more_results($connection) && mysqli_next_result($connection)) {
            $extraResult = mysqli_store_result($connection);
            if ($extraResult instanceof mysqli_result) {
                mysqli_free_result($extraResult);
            }
        }
        throw $error;
    }
}

/** @return array<int, array<string, mixed>> */
function dbSelectView(mysqli $connection, string $sql, string $types = '', array $parameters = []): array
{
    $statement = mysqli_prepare($connection, $sql);
    if (!$statement) {
        throw new RuntimeException('Không thể chuẩn bị truy vấn View.');
    }

    try {
        if ($types !== '') {
            $bindValues = [$types];
            foreach ($parameters as $index => $value) {
                $parameters[$index] = $value;
                $bindValues[] = &$parameters[$index];
            }
            mysqli_stmt_bind_param($statement, ...$bindValues);
        }
        mysqli_stmt_execute($statement);
        $result = mysqli_stmt_get_result($statement);
        $rows = $result instanceof mysqli_result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];
        if ($result instanceof mysqli_result) {
            mysqli_free_result($result);
        }
        mysqli_stmt_close($statement);
        return $rows;
    } catch (Throwable $error) {
        mysqli_stmt_close($statement);
        throw $error;
    }
}
