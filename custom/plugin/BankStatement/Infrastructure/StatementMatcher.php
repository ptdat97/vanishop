<?php

declare(strict_types=1);

namespace Plugin\BankStatement\Infrastructure;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Payment\Contracts\Payments;
use Modules\Payment\Contracts\Reconciliations;
use Plugin\BankStatement\BankStatementServiceProvider;

/**
 * Khớp sao kê với đơn chuyển khoản (EP v3 X5): nội dung chuyển khoản chứa số đơn (cửa hàng hướng dẫn "nội dung = số đơn")
 * + số tiền đúng bằng khoản chờ → xác nhận đã nhận tiền (`Payments::confirmManually`). Thiếu/thừa tiền, không thấy số đơn,
 * đơn không chờ chuyển khoản → dòng lệch trong `Reconciliations`. Idempotent theo mã giao dịch ngân hàng.
 */
final class StatementMatcher
{
    private const REFERENCE_HEADERS = ['ma gd', 'ma giao dich', 'so tham chieu', 'reference'];

    private const AMOUNT_HEADERS = ['so tien', 'so tien ghi co', 'amount', 'credit'];

    private const CONTENT_HEADERS = ['noi dung', 'dien giai', 'mo ta', 'description'];

    public function __construct(private readonly OrderReader $orders, private readonly Payments $payments, private readonly Reconciliations $ledger) {}

    /**
     * @param  list<list<string>>  $rows
     * @return array{confirmed: int, mismatched: int, duplicate: int}
     */
    public function import(array $rows): array
    {
        $header = array_map(fn (string $cell): string => trim(Str::lower(Str::ascii($cell))), $rows[0] ?? []);
        $column = fn (array $names): ?int => ($index = array_search(true, array_map(fn (string $cell): bool => in_array($cell, $names, true), $header), true)) === false ? null : $index;
        [$ref, $amount, $content] = [$column(self::REFERENCE_HEADERS), $column(self::AMOUNT_HEADERS), $column(self::CONTENT_HEADERS)];
        if ($ref === null || $amount === null || $content === null) {
            throw ValidationException::withMessages(['file' => 'Sao kê cần cột "Mã GD", "Số tiền", "Nội dung" ở dòng đầu.']);
        }

        $result = ['confirmed' => 0, 'mismatched' => 0, 'duplicate' => 0];
        foreach (array_slice($rows, 1) as $row) {
            $reference = trim((string) ($row[$ref] ?? ''));
            $paid = (int) preg_replace('/\D+/', '', (string) ($row[$amount] ?? ''));
            if ($reference === '' || $paid <= 0) {
                continue;
            }
            $outcome = $this->match($reference, $paid, (string) ($row[$content] ?? ''));
            $result[$outcome]++;
        }

        return $result;
    }

    /**
     * @return 'confirmed'|'mismatched'|'duplicate'
     */
    private function match(string $reference, int $paid, string $content): string
    {
        $order = null;
        preg_match_all('/[A-Z0-9]+-\d{4,}/i', $content, $tokens);
        foreach ($tokens[0] as $token) {
            $order ??= $this->orders->findByNumber($token);
        }
        $payment = $order === null ? null : $this->payments->latestForOrder($order->id);
        $paymentId = $payment === null ? null : $this->payments->paymentId($payment->publicId);
        $record = fn (bool $matched, ?string $note) => $this->ledger->record(BankStatementServiceProvider::ID, $reference, $matched, $order?->id, $paymentId, $payment?->amount, $paid, $note);

        if ($payment === null || $paymentId === null) {
            return $record(false, 'Không tìm thấy số đơn trong nội dung: '.mb_substr($content, 0, 120)) ? 'mismatched' : 'duplicate';
        }
        if (! in_array($payment->status, ['pending', 'failed', 'expired'], true) || $payment->amount !== $paid) {
            $note = $payment->amount !== $paid ? "Đơn {$order->number}: số tiền không khớp" : "Đơn {$order->number}: không chờ chuyển khoản ({$payment->status})";

            return $record(false, $note) ? 'mismatched' : 'duplicate';
        }
        if (! $record(true, "Đơn {$order->number}")) {
            return 'duplicate';
        }
        $this->payments->confirmManually($paymentId, "Sao kê ngân hàng: {$reference}");

        return 'confirmed';
    }
}
