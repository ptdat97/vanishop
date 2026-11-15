<?php

declare(strict_types=1);

namespace Modules\Ordering\Contracts\Data;

use InvalidArgumentException;

/**
 * Một tín hiệu rủi ro của đơn (EP v3 X2, `OrderRiskSignal`): mức `low` | `medium` | `high`, nhãn ngắn hiện cho nhân viên
 * ("SĐT trong danh sách đen"), lý do chi tiết (tuỳ chọn). `source` do Core điền (id plugin).
 */
final readonly class RiskSignal
{
    public const LEVELS = ['low', 'medium', 'high'];

    public function __construct(public string $level, public string $label, public ?string $reason = null, public string $source = '')
    {
        if (! in_array($level, self::LEVELS, true)) {
            throw new InvalidArgumentException("Mức rủi ro [{$level}] không hợp lệ.");
        }
    }

    public function withSource(string $source): self
    {
        return new self($this->level, $this->label, $this->reason, $source);
    }

    /**
     * @return array{level: string, label: string, reason: string|null, source: string}
     */
    public function toArray(): array
    {
        return ['level' => $this->level, 'label' => $this->label, 'reason' => $this->reason, 'source' => $this->source];
    }
}
