<?php

declare(strict_types=1);

namespace Modules\Shared\Application;

use Illuminate\Support\Facades\DB;
use LogicException;

/**
 * Số chứng từ tăng liên tục theo (scope, period). Phải gọi TRONG transaction của chứng từ:
 * dòng sequence bị khoá tới khi commit, rollback thì số được trả lại (không thủng số).
 */
final class NumberSequences
{
    public function next(string $scope, string $period): int
    {
        if (DB::transactionLevel() === 0) {
            throw new LogicException('NumberSequences::next phải chạy trong transaction.');
        }

        DB::table('number_sequences')->insertOrIgnore(['scope' => $scope, 'period' => $period, 'last_value' => 0, 'created_at' => now(), 'updated_at' => now()]);
        $row = DB::table('number_sequences')->where('scope', $scope)->where('period', $period)->lockForUpdate()->first();
        $next = (int) $row->last_value + 1;
        DB::table('number_sequences')->where('id', $row->id)->update(['last_value' => $next, 'updated_at' => now()]);

        return $next;
    }
}
