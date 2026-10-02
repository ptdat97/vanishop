<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts\Data;

/**
 * Một con số (doanh thu hôm nay, số đơn chờ xử lý…). `format`: number | money (đơn vị nhỏ nhất, VND) | percent.
 */
final readonly class Metric
{
    public function __construct(
        public string $label,
        public int|float $value,
        public string $format = 'number',
        public ?string $hint = null,
    ) {
        Formats::guard($format);
    }

    /**
     * @return array{kind: 'metric', label: string, value: int|float, format: string, hint: string|null}
     */
    public function toArray(): array
    {
        return ['kind' => 'metric', 'label' => $this->label, 'value' => $this->value, 'format' => $this->format, 'hint' => $this->hint];
    }
}
