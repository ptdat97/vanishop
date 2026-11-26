<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;

/**
 * Phân trang theo số trang cho danh sách Admin lớn bằng "deferred join" (db-refactor L54): tìm id của trang bằng chính truy
 * vấn đó nhưng chỉ chọn khoá chính (MySQL duyệt index phủ, không đọc dòng cho các dòng bị bỏ qua bởi OFFSET), rồi mới nạp
 * đủ các dòng của trang (kèm eager load). Thay `->paginate()`: cùng kết quả, cùng thứ tự, trang sâu nhanh hơn nhiều.
 */
final class DeferredPage
{
    /**
     * @template TModel of Model
     *
     * @param  Builder<TModel>  $query  đã có điều kiện + thứ tự (thứ tự phải duy nhất, vd. kết thúc bằng id)
     * @param  int|null  $total  tổng số dòng đã biết (vd. từ số đếm có sẵn) — null thì đếm
     * @return LengthAwarePaginator<int, TModel>
     */
    public static function paginate(Builder $query, int $perPage, ?int $total = null, string $pageName = 'page'): LengthAwarePaginator
    {
        $page = max(1, Paginator::resolveCurrentPage($pageName));
        $key = $query->getModel()->getQualifiedKeyName();
        $total ??= (clone $query)->toBase()->getCountForPagination();

        $ids = (clone $query)->toBase()->select($key)->forPage($page, $perPage)->pluck($query->getModel()->getKeyName())->all();
        $position = array_flip(array_map('strval', $ids));
        $items = $ids === [] ? $query->getModel()->newCollection()
            : (clone $query)->reorder()->whereIn($key, $ids)->get()->sortBy(fn (Model $model): int => $position[(string) $model->getKey()] ?? PHP_INT_MAX)->values();

        return (new LengthAwarePaginator($items, $total, $perPage, $page, ['path' => Paginator::resolveCurrentPath(), 'pageName' => $pageName]))->withQueryString();
    }
}
