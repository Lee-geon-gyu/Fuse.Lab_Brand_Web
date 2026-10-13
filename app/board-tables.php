<?php
/**
 * 표 게시판 (boards.type = 'table'). 관리자가 열 이름과 행(칸 값)을 직접 편집한다.
 * 게시판당 board_tables 1행에 JSON 으로 저장하고, 화면에서는 표 전체를 한 번에 저장한다.
 */

const BOARD_TABLE_MAX_COLUMNS = 30;
const BOARD_TABLE_MAX_ROWS = 1000;
const BOARD_TABLE_COLUMN_MAX = 50;
const BOARD_TABLE_CELL_MAX = 1000;

/** @return array{columns: list<string>, rows: list<list<string>>} 저장값, 없으면 빈 표 */
function board_table(string $key): array
{
    $stmt = db()->prepare('SELECT col_names, row_data FROM board_tables WHERE board_key = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    if (!$row) {
        return ['columns' => [], 'rows' => []];
    }
    return [
        'columns' => json_decode($row['col_names'], true) ?: [],
        'rows' => json_decode($row['row_data'], true) ?: [],
    ];
}

/**
 * 입력 검증 후 정리된 표. 칸 수는 열 수에 맞추고, 모든 칸이 빈 행은 버린다.
 * @param mixed $input JSON 디코드한 {columns: string[], rows: string[][]}
 * @return array{columns: list<string>, rows: list<list<string>>}
 * @throws RuntimeException 사용자에게 보여줄 메시지
 */
function board_table_clean(mixed $input): array
{
    if (!is_array($input) || !is_array($input['columns'] ?? null) || !is_array($input['rows'] ?? null)) {
        throw new RuntimeException('표 데이터가 올바르지 않습니다.');
    }
    $columns = [];
    foreach ($input['columns'] as $name) {
        $name = trim(is_string($name) ? $name : '');
        if ($name === '' || mb_strlen($name) > BOARD_TABLE_COLUMN_MAX) {
            throw new RuntimeException('열 이름은 1~' . BOARD_TABLE_COLUMN_MAX . '자로 입력해 주세요.');
        }
        $columns[] = $name;
    }
    if (count($columns) > BOARD_TABLE_MAX_COLUMNS) {
        throw new RuntimeException('열은 ' . BOARD_TABLE_MAX_COLUMNS . '개까지 만들 수 있습니다.');
    }

    $rows = [];
    foreach ($input['rows'] as $cells) {
        if (!is_array($cells)) {
            throw new RuntimeException('표 데이터가 올바르지 않습니다.');
        }
        $cells = array_values($cells);
        $row = [];
        foreach ($columns as $index => $_) {
            $value = $cells[$index] ?? '';
            $value = trim(str_replace("\r\n", "\n", is_string($value) ? $value : ''));
            if (mb_strlen($value) > BOARD_TABLE_CELL_MAX) {
                throw new RuntimeException('칸 내용은 ' . BOARD_TABLE_CELL_MAX . '자 이내로 입력해 주세요.');
            }
            $row[] = $value;
        }
        if (implode('', $row) !== '') {
            $rows[] = $row;
        }
    }
    if (count($rows) > BOARD_TABLE_MAX_ROWS) {
        throw new RuntimeException('행은 ' . BOARD_TABLE_MAX_ROWS . '개까지 저장할 수 있습니다.');
    }
    return ['columns' => $columns, 'rows' => $rows];
}

/** 표 전체 저장 @throws RuntimeException 표 게시판이 아니거나 입력 오류 */
function board_table_save(string $key, mixed $input): void
{
    if (board_type($key) !== 'table') {
        throw new RuntimeException('표 게시판을 찾을 수 없습니다.');
    }
    $table = board_table_clean($input);
    db()->prepare(
        'INSERT INTO board_tables (board_key, col_names, row_data) VALUES (?, ?, ?)
         ON DUPLICATE KEY UPDATE col_names = VALUES(col_names), row_data = VALUES(row_data)'
    )->execute([
        $key,
        json_encode($table['columns'], JSON_UNESCAPED_UNICODE),
        json_encode($table['rows'], JSON_UNESCAPED_UNICODE),
    ]);
}
