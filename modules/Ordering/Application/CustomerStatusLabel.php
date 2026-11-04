<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use Modules\Ordering\Domain\CustomerStatus;

/**
 * Trạng thái hiển thị cho khách (`CustomerStatus`) với nhãn theo ngôn ngữ của request (`ordering::labels.customer_status`);
 * thiếu bản dịch → nhãn mặc định của Domain.
 */
final class CustomerStatusLabel
{
    /**
     * @return array{code: string, label: string}
     */
    public static function of(string $orderStatus, string $paymentStatus, string $fulfillmentStatus, string $returnStatus): array
    {
        $status = CustomerStatus::of($orderStatus, $paymentStatus, $fulfillmentStatus, $returnStatus);
        $key = "ordering::labels.customer_status.{$status['code']}";
        $translated = __($key);

        return ['code' => $status['code'], 'label' => is_string($translated) && $translated !== $key ? $translated : $status['label']];
    }
}
