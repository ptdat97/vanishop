<?php

declare(strict_types=1);

namespace Modules\Extension\Contracts\Data;

final readonly class Column
{
    public function __construct(
        public string $key,
        public string $label,
        public string $format = 'text',
    ) {
        Formats::guard($format, allowText: true);
    }

    /**
     * @return array{key: string, label: string, format: string}
     */
    public function toArray(): array
    {
        return ['key' => $this->key, 'label' => $this->label, 'format' => $this->format];
    }
}
