<?php

declare(strict_types=1);

namespace Modules\Payment\Application;

use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Payment\Contracts\Reconciliations;

/**
 * Bảng đối soát chung (EP v3 X5) — xem Reconciliations. Nhân viên xử lý dòng lệch ở trang Thanh toán (`resolve`).
 */
final class ReconciliationLedger implements Reconciliations
{
    public function __construct(private readonly AuditLogger $audit) {}

    public function record(string $source, string $reference, bool $matched, ?int $orderId = null, ?int $paymentId = null, ?int $expected = null, ?int $actual = null, ?string $note = null): bool
    {
        try {
            DB::table('reconciliation_entries')->insert([
                'source' => mb_substr($source, 0, 64), 'reference' => mb_substr($reference, 0, 128), 'status' => $matched ? 'matched' : 'mismatch',
                'order_id' => $orderId, 'payment_id' => $paymentId, 'expected' => $expected, 'actual' => $actual,
                'note' => $note === null ? null : mb_substr($note, 0, 255), 'created_at' => now(),
            ]);

            return true;
        } catch (UniqueConstraintViolationException) {
            return false;
        }
    }

    public function recorded(string $source, array $references): array
    {
        return array_map('strval', array_keys($this->statuses($source, $references)));
    }

    public function statuses(string $source, array $references): array
    {
        $found = [];
        foreach (array_chunk(array_values(array_unique($references)), 500) as $chunk) {
            foreach (DB::table('reconciliation_entries')->where('source', $source)->whereIn('reference', $chunk)->pluck('status', 'reference') as $reference => $status) {
                $found[(string) $reference] = (string) $status;
            }
        }

        return $found;
    }

    public function matchOpen(string $source, string $reference, int $orderId, int $expected, int $actual, string $note): bool
    {
        return DB::table('reconciliation_entries')->where('source', $source)->where('reference', $reference)->where('status', 'mismatch')
            ->update(['status' => 'matched', 'order_id' => $orderId, 'expected' => $expected, 'actual' => $actual, 'note' => mb_substr($note, 0, 255)]) === 1;
    }

    public function openCount(): int
    {
        return DB::table('reconciliation_entries')->where('status', 'mismatch')->count();
    }

    /**
     * @return list<array{id: int, source: string, reference: string, order_id: int|null, expected: int|null, actual: int|null, note: string|null, created_at: string}>
     */
    public function open(int $limit = 50): array
    {
        return DB::table('reconciliation_entries')->where('status', 'mismatch')->orderByDesc('id')->limit($limit)
            ->get(['id', 'source', 'reference', 'order_id', 'expected', 'actual', 'note', 'created_at'])
            ->map(fn (object $row): array => [
                'id' => (int) $row->id, 'source' => (string) $row->source, 'reference' => (string) $row->reference, 'order_id' => $row->order_id === null ? null : (int) $row->order_id,
                'expected' => $row->expected === null ? null : (int) $row->expected, 'actual' => $row->actual === null ? null : (int) $row->actual,
                'note' => $row->note, 'created_at' => (string) $row->created_at,
            ])->all();
    }

    public function resolve(int $entryId, string $resolution): void
    {
        $updated = DB::table('reconciliation_entries')->where('id', $entryId)->where('status', 'mismatch')
            ->update(['status' => 'resolved', 'resolution' => mb_substr($resolution, 0, 255), 'resolved_at' => now()]);
        if ($updated > 0) {
            $this->audit->record('payment.reconciliation_resolved', 'reconciliation_entry', $entryId, ['resolution' => $resolution]);
        }
    }
}
