<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts\Data;

/**
 * Bảng dữ liệu: cột có định dạng, dòng là mảng `key cột => giá trị vô hướng`.
 */
final readonly class Table
{
    /**
     * @param  list<Column>  $columns
     * @param  list<array<string, scalar|null>>  $rows
     */
    public function __construct(
        public array $columns,
        public array $rows,
        public string $label = '',
    ) {}

    /**
     * @return array{kind: 'table', label: string, columns: list<array{key: string, label: string, format: string}>, rows: list<array<string, scalar|null>>}
     */
    public function toArray(): array
    {
        $keys = array_map(fn (Column $column): string => $column->key, $this->columns);

        return [
            'kind' => 'table',
            'label' => $this->label,
            'columns' => array_map(fn (Column $column): array => $column->toArray(), $this->columns),
            'rows' => array_map(fn (array $row): array => array_map(fn (string $key): string|int|float|bool|null => $row[$key] ?? null, array_combine($keys, $keys)), $this->rows),
        ];
    }
}
