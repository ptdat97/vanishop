<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Closure;
use DateTimeInterface;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Xoá (hoặc chuyển) dòng cũ của bảng lớn theo lô `id` tăng dần (db-refactor G3): mỗi lô quét theo khoá chính, không `DELETE`
 * một lần cả bảng, không cần index trên cột thời gian. Giả định `id` tăng theo thời gian ghi (bảng log append-only): dừng ở
 * lô đầu tiên mà dòng đầu đã mới hơn mốc.
 */
final class ChunkedPrune
{
    public const CHUNK = 5000;

    /**
     * @param  Closure(Builder): void|null  $scope  điều kiện thêm (vd. chỉ trạng thái đã kết thúc)
     * @param  Closure(list<int>): void|null  $beforeDelete  chạy trước khi xoá mỗi lô (vd. chép sang bảng archive)
     * @return int số dòng đã xoá
     */
    public static function olderThan(string $table, string $timeColumn, DateTimeInterface $before, ?Closure $scope = null, ?Closure $beforeDelete = null, int $chunk = self::CHUNK): int
    {
        $deleted = 0;
        $afterId = 0;
        while (true) {
            $rows = DB::table($table)->where('id', '>', $afterId)->orderBy('id')->limit($chunk)->get(['id', $timeColumn]);
            if ($rows->isEmpty() || (string) $rows->first()->{$timeColumn} >= $before->format('Y-m-d H:i:s')) {
                return $deleted;
            }
            $afterId = (int) $rows->last()->id;
            $candidates = $rows->filter(fn (object $row): bool => $row->{$timeColumn} !== null && (string) $row->{$timeColumn} < $before->format('Y-m-d H:i:s'))
                ->pluck('id')->map(fn ($id): int => (int) $id)->all();
            if ($candidates === []) {
                continue;
            }
            $ids = DB::table($table)->whereIn('id', $candidates)->when($scope !== null, fn (Builder $query) => $scope($query))->pluck('id')->map(fn ($id): int => (int) $id)->all();
            if ($ids === []) {
                continue;
            }
            DB::transaction(function () use ($table, $ids, $beforeDelete, &$deleted): void {
                if ($beforeDelete !== null) {
                    $beforeDelete($ids);
                }
                $deleted += DB::table($table)->whereIn('id', $ids)->delete();
            });
        }
    }
}
