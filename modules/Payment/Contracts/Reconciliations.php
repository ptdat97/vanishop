<?php

declare(strict_types=1);

namespace Modules\Payment\Contracts;

use Modules\Shared\Contracts\PluginApi;

/**
 * Service contract (EP v3 X5): bảng đối soát chung — plugin đối soát (sao kê ngân hàng, bảng kê COD của hãng…) ghi từng
 * dòng đã khớp hoặc lệch thay vì tự dựng bảng/trang riêng. Dòng lệch vào hàng đợi "Đối soát lệch" (trang Thanh toán, ô Việc
 * cần làm) để nhân viên xử lý. Idempotent theo (`source`, `reference`).
 */
#[PluginApi(PluginApi::EXPERIMENTAL)]
interface Reconciliations
{
    /**
     * @param  string  $source  id plugin
     * @param  string  $reference  tham chiếu duy nhất trong nguồn (mã giao dịch ngân hàng, mã vận đơn…)
     * @return bool false khi dòng đã ghi trước đó
     */
    public function record(string $source, string $reference, bool $matched, ?int $orderId = null, ?int $paymentId = null, ?int $expected = null, ?int $actual = null, ?string $note = null): bool;

    /**
     * Các tham chiếu trong `$references` đã có dòng của nguồn `$source` (mọi trạng thái) — để xem trước file đối soát.
     *
     * @param  list<string>  $references
     * @return list<string>
     */
    public function recorded(string $source, array $references): array;

    /**
     * @param  list<string>  $references
     * @return array<string, string> trạng thái theo tham chiếu
     */
    public function statuses(string $source, array $references): array;

    /** Chuyển một dòng lệch còn mở sang khớp. Dòng đã xử lý thủ công hoặc đã khớp trả false. */
    public function matchOpen(string $source, string $reference, int $orderId, int $expected, int $actual, string $note): bool;
}
