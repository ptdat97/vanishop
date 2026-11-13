<?php

declare(strict_types=1);

namespace Modules\Shared\Http;

use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Http\Request;

/**
 * Sắp xếp danh sách Admin theo cột (DataTable `sortable`): query `sort=<khoá>&direction=asc|desc`. Chỉ nhận khoá trong
 * danh sách cho phép (khoá công khai → cột SQL) — khoá lạ bị bỏ qua (dùng thứ tự mặc định của màn hình), không lỗi 422.
 */
final readonly class ListSort
{
    private function __construct(public string $key, public string $direction, private string $column) {}

    /**
     * @param  array<string, string>  $columns  khoá công khai → cột SQL
     */
    public static function fromRequest(Request $request, array $columns): ?self
    {
        $key = $request->query('sort');
        if (! is_string($key) || ! array_key_exists($key, $columns)) {
            return null;
        }
        $direction = $request->query('direction') === 'asc' ? 'asc' : 'desc';

        return new self($key, $direction, $columns[$key]);
    }

    /**
     * Sắp theo cột đã chọn, rồi theo `id` cùng chiều cho thứ tự ổn định giữa các trang.
     *
     * @template TBuilder of Builder
     *
     * @param  TBuilder  $query
     * @return TBuilder
     */
    public function apply(Builder $query, string $idColumn = 'id'): Builder
    {
        return $query->orderBy($this->column, $this->direction)->orderBy($idColumn, $this->direction);
    }

    /**
     * Cho prop `sort` của trang (DataTable `:sort`).
     *
     * @return array{key: string, direction: string}
     */
    public function toArray(): array
    {
        return ['key' => $this->key, 'direction' => $this->direction];
    }
}
