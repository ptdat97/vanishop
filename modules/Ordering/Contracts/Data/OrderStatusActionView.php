<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

use Modules\Extension\Contracts\Data\FieldDefinition;

/**
 * Nút của một OrderStatusAction trên thẻ "Trạng thái đơn" (0.3.98).
 */
final readonly class OrderStatusActionView
{
    /**
     * @param  string  $permission  quyền nhân viên cần có (vd. `payments.confirm`, `fulfillment.manage`)
     * @param  list<FieldDefinition>  $fields  ô nhập trong hộp xác nhận (kiểu string/text/int)
     * @param  string|null  $confirm  câu hỏi xác nhận khi không có ô nhập
     * @param  'primary'|'secondary'|'danger'  $tone
     */
    public function __construct(
        public string $label,
        public string $permission,
        public array $fields = [],
        public ?string $confirm = null,
        public string $tone = 'secondary',
        public bool $primary = false,
    ) {}
}
