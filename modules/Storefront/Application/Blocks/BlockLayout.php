<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Blocks;

use Modules\Storefront\Contracts\Data\BuilderZone;

/**
 * Bố cục vùng của một trang dùng page builder chung, dựng từ {@see BuilderZone}: mỗi vùng giới hạn loại khối và số khối.
 * Bố cục một vùng lưu khối không kèm `zone`; bố cục nhiều vùng lưu `zone` (khoá vùng) cho từng khối.
 */
final readonly class BlockLayout
{
    /**
     * @param  array<string, BuilderZone>  $zones
     */
    private function __construct(public array $zones) {}

    /**
     * @param  list<BuilderZone>  $zones
     */
    public static function of(array $zones): self
    {
        $keyed = [];
        foreach ($zones as $zone) {
            $keyed[$zone->key] ??= $zone;
        }

        return new self($keyed);
    }

    public function zoned(): bool
    {
        return count($this->zones) > 1;
    }

    /** @return list<string> */
    public function zoneKeys(): array
    {
        return array_keys($this->zones);
    }

    public function accepts(string $zone, string $type): bool
    {
        $definition = $this->zones[$zone] ?? null;

        return $definition !== null && ($definition->types === null || in_array($type, $definition->types, true));
    }

    public function limit(string $zone): ?int
    {
        return ($this->zones[$zone] ?? null)?->max;
    }

    /**
     * @param  list<array{type: string, config: array<string, mixed>, zone?: string}>  $blocks
     * @return array<string, list<array{type: string, config: array<string, mixed>}>>
     */
    public function group(array $blocks): array
    {
        $zones = array_fill_keys($this->zoneKeys(), []);
        $only = $this->zoned() ? null : ($this->zoneKeys()[0] ?? null);
        foreach ($blocks as $block) {
            $zone = $only ?? (string) ($block['zone'] ?? '');
            if (isset($zones[$zone])) {
                $zones[$zone][] = ['type' => $block['type'], 'config' => $block['config']];
            }
        }

        return $zones;
    }

    /** @return list<array{key: string, label: string, slot: string|null, types: list<string>|null, max: int|null}> rỗng = một vùng */
    public function toArray(): array
    {
        if (! $this->zoned()) {
            return [];
        }

        return array_values(array_map(fn (BuilderZone $zone): array => [
            'key' => $zone->key, 'label' => $zone->label, 'slot' => $zone->slot, 'types' => $zone->types, 'max' => $zone->max,
        ], $this->zones));
    }
}
