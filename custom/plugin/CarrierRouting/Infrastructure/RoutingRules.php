<?php

declare(strict_types=1);

namespace Plugin\CarrierRouting\Infrastructure;

use Modules\Fulfillment\Contracts\CarrierAssignment;
use Modules\Fulfillment\Contracts\Data\CarrierAssignmentContext;
use Modules\Fulfillment\Contracts\Data\CarrierChoice;
use Modules\Tenancy\Contracts\Settings;
use Plugin\CarrierRouting\CarrierRoutingServiceProvider;

/**
 * Bảng quy tắc (Admin → Cấu hình → vani.carrier-routing), mỗi dòng `điều kiện = hãng[:dịch vụ]`, dòng đầu tiên khớp thắng:
 *   `tinh:79,01 = ahamove`        — tỉnh/thành nhận hàng (mã danh mục địa giới);
 *   `cod>=3000000 = ghtk`         — số tiền thu hộ từ mức này;
 *   `* = ghn:standard`            — mọi đơn còn lại.
 * Dòng sai cú pháp bị bỏ qua. Hãng không bật → Core bỏ qua lựa chọn, dùng quy tắc mặc định.
 */
final class RoutingRules implements CarrierAssignment
{
    public function __construct(private readonly Settings $settings) {}

    public function assign(CarrierAssignmentContext $context): ?CarrierChoice
    {
        foreach (preg_split('/\R/', (string) $this->settings->get(CarrierRoutingServiceProvider::ID, 'rules', '')) ?: [] as $line) {
            if (preg_match('/^\s*(.+?)\s*=\s*([a-z0-9_-]+)(?::([A-Za-z0-9_-]+))?\s*$/', $line, $match) !== 1 || ! $this->matches(trim($match[1]), $context)) {
                continue;
            }

            return new CarrierChoice($match[2], ($match[3] ?? '') === '' ? null : $match[3], 'Quy tắc chọn hãng: '.trim($line));
        }

        return null;
    }

    private function matches(string $condition, CarrierAssignmentContext $context): bool
    {
        if ($condition === '*') {
            return true;
        }
        if (preg_match('/^tinh:\s*([0-9,\s]+)$/i', $condition, $match) === 1) {
            return in_array((string) ($context->address['province_code'] ?? ''), array_map('trim', explode(',', $match[1])), true);
        }
        if (preg_match('/^cod\s*>=\s*(\d+)$/i', $condition, $match) === 1) {
            return $context->codAmount >= (int) $match[1];
        }

        return false;
    }
}
