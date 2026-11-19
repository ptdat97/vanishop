<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Application;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Payment\Contracts\Payments;
use Modules\Payment\Contracts\Reconciliations;

/**
 * Đối soát bảng kê COD của hãng vận chuyển (stabilization A9): mỗi dòng (mã vận đơn, tiền thu hộ, phí) → tìm vận đơn, so số
 * tiền với COD của vận đơn. Khớp → ghi "đã khớp" vào sổ đối soát (và ghi nhận đã thu nếu khoản COD chưa được ghi — vd. shop tự
 * đánh dấu giao); lệch tiền, không có vận đơn, vận đơn chưa giao → hàng đợi "Đối soát lệch". Xem trước rồi mới ghi; nhập lại
 * cùng dòng được nhận ra (sổ đối soát idempotent theo nguồn + mã vận đơn).
 */
final class CodStatement
{
    public const SOURCE = 'cod_statement';

    /** Dòng chỉ hiện ở xem trước, không ghi vào sổ. */
    private const SKIPPED = ['already', 'duplicate', 'invalid', 'header_missing'];

    private const CACHE_PREFIX = 'payment:cod-statement:';

    private const TRACKING_HEADERS = ['ma van don', 'van don', 'ma vd', 'tracking', 'tracking number', 'ma don hang hang van chuyen', 'ma don hvc', 'label', 'ma label'];

    private const AMOUNT_HEADERS = ['tien thu ho', 'thu ho', 'cod', 'tien cod', 'so tien thu ho', 'so tien cod', 'amount', 'cod amount'];

    private const FEE_HEADERS = ['phi', 'phi van chuyen', 'phi ship', 'cuoc', 'cuoc phi', 'phi giao hang', 'tong phi', 'fee'];

    public function __construct(
        private readonly Payments $payments,
        private readonly Reconciliations $ledger,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * @param  list<list<string>>  $rows
     * @return array{token: string|null, file: string, lines: list<array{line: int, tracking: string, amount: int|null, fee: int|null, expected: int|null, order_id: int|null, status: string}>, summary: array<string, int>, total: int}
     */
    public function preview(array $rows, string $fileName): array
    {
        $lines = $this->classify($rows);
        $summary = array_count_values(array_column($lines, 'status'));
        $token = null;
        if (array_diff(array_keys($summary), self::SKIPPED) !== []) {
            $token = Str::random(40);
            Cache::put(self::CACHE_PREFIX.$token, ['file' => $fileName, 'rows' => $rows], now()->addMinutes(30));
        }

        return [
            'token' => $token, 'file' => $fileName, 'lines' => $lines, 'summary' => $summary,
            'total' => array_sum(array_map(fn (array $line): int => (int) $line['amount'], array_filter($lines, fn (array $line): bool => $line['status'] === 'matched'))),
        ];
    }

    /**
     * Ghi file đã xem trước (phân loại lại trên dữ liệu hiện tại).
     *
     * @return array{matched: int, mismatched: int, skipped: int}|null null = token hết hạn
     */
    public function apply(string $token): ?array
    {
        $pending = Cache::pull(self::CACHE_PREFIX.$token);
        if (! is_array($pending) || ! isset($pending['rows'])) {
            return null;
        }
        $result = ['matched' => 0, 'mismatched' => 0, 'skipped' => 0];
        foreach ($this->classify($pending['rows']) as $line) {
            if (in_array($line['status'], self::SKIPPED, true)) {
                $result['skipped']++;

                continue;
            }
            $matched = $line['status'] === 'matched';
            DB::transaction(function () use ($line, $matched): void {
                if ($matched) {
                    $this->payments->collectCod((int) $line['order_id'], (int) $line['amount'], "cod-statement:{$line['tracking']}", (int) $line['expected']);
                }
                $this->ledger->record(self::SOURCE, $line['tracking'], $matched, $line['order_id'], null, $line['expected'], $line['amount'], $this->note($line));
            });
            $matched ? $result['matched']++ : $result['mismatched']++;
        }
        $this->audit->record('payment.cod_statement.imported', 'reconciliation', null, ['file' => (string) ($pending['file'] ?? ''), ...$result]);

        return $result;
    }

    /**
     * @param  list<list<string>>  $rows
     * @return list<array{line: int, tracking: string, amount: int|null, fee: int|null, expected: int|null, order_id: int|null, status: string}>
     */
    private function classify(array $rows): array
    {
        $header = array_map(fn (string $cell): string => trim(Str::lower(Str::ascii($cell))), $rows[0] ?? []);
        $tracking = $this->column($header, self::TRACKING_HEADERS);
        $amount = $this->column($header, self::AMOUNT_HEADERS);
        $fee = $this->column($header, self::FEE_HEADERS);
        if ($tracking === null || $amount === null) {
            return [['line' => 1, 'tracking' => '', 'amount' => null, 'fee' => null, 'expected' => null, 'order_id' => null, 'status' => 'header_missing']];
        }

        $parsed = [];
        foreach (array_slice($rows, 1) as $index => $row) {
            $code = mb_strtoupper(trim((string) ($row[$tracking] ?? '')));
            if ($code !== '') {
                $parsed[] = ['line' => $index + 2, 'tracking' => $code, 'amount' => self::money((string) ($row[$amount] ?? '')), 'fee' => $fee === null ? null : self::money((string) ($row[$fee] ?? ''))];
            }
        }
        $shipments = $this->shipments(array_column($parsed, 'tracking'));
        $done = array_flip($this->ledger->recorded(self::SOURCE, array_column($parsed, 'tracking')));
        $seen = [];
        $lines = [];
        foreach ($parsed as $row) {
            $shipment = $shipments[$row['tracking']] ?? null;
            $status = match (true) {
                isset($seen[$row['tracking']]) => 'duplicate',
                isset($done[$row['tracking']]) => 'already',
                $row['amount'] === null => 'invalid',
                $shipment === null => 'not_found',
                $shipment['status'] !== 'delivered' => 'not_delivered',
                $row['amount'] !== $shipment['cod_amount'] => 'amount_mismatch',
                default => 'matched',
            };
            $seen[$row['tracking']] = true;
            $lines[] = [...$row, 'expected' => $shipment['cod_amount'] ?? null, 'order_id' => $shipment['order_id'] ?? null, 'status' => $status];
        }

        return $lines;
    }

    /**
     * Vận đơn theo mã (khoá viết hoa), bỏ vận đơn đã huỷ khi trùng mã với vận đơn còn hiệu lực.
     *
     * @param  list<string>  $trackingNumbers
     * @return array<string, array{order_id: int, status: string, cod_amount: int}>
     */
    private function shipments(array $trackingNumbers): array
    {
        $result = [];
        foreach (array_chunk(array_values(array_unique($trackingNumbers)), 500) as $chunk) {
            foreach (Shipment::query()->whereIn('tracking_number', $chunk)->orderBy('id')->get(['order_id', 'status', 'cod_amount', 'tracking_number']) as $shipment) {
                $key = mb_strtoupper((string) $shipment->tracking_number);
                if (! isset($result[$key]) || $shipment->status !== ShipmentStatus::Cancelled) {
                    $result[$key] = ['order_id' => (int) $shipment->order_id, 'status' => $shipment->status->value, 'cod_amount' => (int) $shipment->cod_amount];
                }
            }
        }

        return $result;
    }

    /**
     * @param  array{amount: int|null, fee: int|null, expected: int|null, status: string}  $line
     */
    private function note(array $line): string
    {
        $fee = $line['fee'] === null ? '' : __('fulfillment::texts.cod_statement.carrier_fee_fee', ['fee' => $line['fee']]);

        return match ($line['status']) {
            'matched' => __('fulfillment::texts.cod_statement.matched_cod_statement').$fee,
            'amount_mismatch' => __('fulfillment::texts.cod_statement.carrier_recorded_cod_amount_shipment', ['amount' => $line['amount'], 'expected' => $line['expected']]).$fee,
            'not_delivered' => __('fulfillment::texts.cod_statement.statement_but_shipment_not_delivered').$fee,
            'not_found' => __('fulfillment::texts.cod_statement.shipment_does_not_exist_system').$fee,
            default => $line['status'],
        };
    }

    /**
     * "199.000", "199,000 đ", "199000", "0" → số nguyên VND; chữ/số âm → null.
     */
    private static function money(string $cell): ?int
    {
        $value = preg_replace('/\s|đ|₫|vnd/iu', '', $cell) ?? '';
        if (preg_match('/^\d{1,3}([.,]\d{3})+$/', $value) === 1) {
            $value = str_replace(['.', ','], '', $value);
        }

        return ctype_digit($value) ? (int) $value : null;
    }

    /**
     * @param  list<string>  $header
     * @param  list<string>  $names
     */
    private function column(array $header, array $names): ?int
    {
        foreach ($header as $index => $cell) {
            if (in_array($cell, $names, true)) {
                return $index;
            }
        }

        return null;
    }
}
