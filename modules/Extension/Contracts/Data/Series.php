<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts\Data;

use InvalidArgumentException;

/**
 * Chuỗi giá trị theo nhãn (doanh thu theo ngày…), Admin vẽ dạng cột.
 */
final readonly class Series
{
    /**
     * @param  list<string>  $labels
     * @param  list<int|float>  $values
     */
    public function __construct(
        public array $labels,
        public array $values,
        public string $format = 'number',
        public string $label = '',
    ) {
        Formats::guard($format);

        if (count($labels) !== count($values)) {
            throw new InvalidArgumentException('Series: số nhãn và số giá trị phải bằng nhau.');
        }
    }

    /**
     * @return array{kind: 'series', label: string, labels: list<string>, values: list<int|float>, format: string}
     */
    public function toArray(): array
    {
        return ['kind' => 'series', 'label' => $this->label, 'labels' => $this->labels, 'values' => $this->values, 'format' => $this->format];
    }
}
