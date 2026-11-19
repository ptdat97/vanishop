<?php

declare(strict_types=1);

namespace Plugin\CodRiskGuard\Infrastructure;

use Modules\Ordering\Contracts\Data\OrderData;
use Modules\Ordering\Contracts\Data\RiskSignal;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Ordering\Contracts\OrderRiskSignal;
use Modules\Tenancy\Contracts\Settings;
use Plugin\CodRiskGuard\CodRiskGuardServiceProvider;

/**
 * Quy tắc rủi ro của shop (cấu hình ở Admin → Cài đặt → vani.cod-risk-guard):
 *   - SĐT trong danh sách đen → `high` (và chặn xác nhận nếu bật);
 *   - SĐT đã có ≥ N đơn hoàn về → `high`; có đơn hoàn về nhưng chưa tới N → `medium`.
 * Chỉ chấm đơn thu tiền khi giao đang chờ (`cod_pending`).
 */
final class RiskRules implements OrderRiskSignal
{
    public function __construct(private readonly OrderReader $orders, private readonly Settings $settings) {}

    public function assess(array $orderIds): array
    {
        $result = [];
        foreach ($orderIds as $orderId) {
            $order = $this->orders->find($orderId);
            if ($order === null || $order->paymentStatus !== 'cod_pending') {
                continue;
            }
            $signals = [];
            if ($this->blacklisted($order)) {
                $signals[] = new RiskSignal('high', __('vani-cod-risk-guard::texts.risk_rules.blacklisted_phone_number'), __('vani-cod-risk-guard::texts.risk_rules.phone_number_store_s_blacklist'));
            }
            $returned = $this->orders->deliveryHistory($order->recipient['phone'], $order->id)['returned_to_sender'];
            $threshold = max(1, (int) $this->settings->get(CodRiskGuardServiceProvider::ID, 'returned_threshold', 2));
            if ($returned >= $threshold) {
                $signals[] = new RiskSignal('high', __('vani-cod-risk-guard::texts.risk_rules.returned_sender_returned_times', ['returned' => $returned]), __('vani-cod-risk-guard::texts.risk_rules.phone_number_already_has_returned', ['returned' => $returned, 'threshold' => $threshold]));
            } elseif ($returned > 0) {
                $signals[] = new RiskSignal('medium', __('vani-cod-risk-guard::texts.risk_rules.returned_sender_returned_times', ['returned' => $returned]), null);
            }
            if ($signals !== []) {
                $result[$orderId] = $signals;
            }
        }

        return $result;
    }

    public function blacklisted(OrderData $order): bool
    {
        $phone = self::normalize($order->recipient['phone']);
        if ($phone === '') {
            return false;
        }
        $list = preg_split('/[\r\n,;]+/', (string) $this->settings->get(CodRiskGuardServiceProvider::ID, 'blacklist', '')) ?: [];

        return in_array($phone, array_map(self::normalize(...), $list), true);
    }

    /**
     * "+84 912 345 678", "0912.345.678" → "0912345678".
     */
    public static function normalize(string $phone): string
    {
        $digits = (string) preg_replace('/\D+/', '', $phone);

        return str_starts_with($digits, '84') && strlen($digits) > 9 ? '0'.substr($digits, 2) : $digits;
    }
}
