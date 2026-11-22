<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Blocks;

/**
 * Bố cục vùng của một trang dùng page builder chung: mỗi vùng giới hạn loại khối và số khối tối đa.
 * Trang một vùng (trang chủ) lưu khối không kèm `zone`; trang nhiều vùng lưu `zone` cho từng khối.
 */
final readonly class BlockLayout
{
    public const MAIN = 'main';

    /**
     * @param  array<string, array{types: list<string>|null, max: int|null}>  $zones  null = không giới hạn
     */
    private function __construct(public array $zones) {}

    public static function home(): self
    {
        return new self([self::MAIN => ['types' => null, 'max' => null]]);
    }

    public static function collection(): self
    {
        return new self([
            'banner' => ['types' => [HeroBlock::TYPE], 'max' => 1],
            'before' => ['types' => null, 'max' => null],
            'after' => ['types' => null, 'max' => null],
        ]);
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
        $types = $this->zones[$zone]['types'] ?? null;

        return isset($this->zones[$zone]) && ($types === null || in_array($type, $types, true));
    }

    public function limit(string $zone): ?int
    {
        return $this->zones[$zone]['max'] ?? null;
    }

    /**
     * @template TBlock of array{type: string, config: array<string, mixed>, zone?: string}
     *
     * @param  list<TBlock>  $blocks
     * @return array<string, list<array{type: string, config: array<string, mixed>}>>
     */
    public function group(array $blocks): array
    {
        $zones = array_fill_keys($this->zoneKeys(), []);
        foreach ($blocks as $block) {
            $zone = $this->zoned() ? (string) ($block['zone'] ?? '') : self::MAIN;
            if (isset($zones[$zone])) {
                $zones[$zone][] = ['type' => $block['type'], 'config' => $block['config']];
            }
        }

        return $zones;
    }

    /** @return list<array{key: string, types: list<string>|null, max: int|null}> */
    public function toArray(): array
    {
        if (! $this->zoned()) {
            return [];
        }

        return array_map(fn (string $key): array => ['key' => $key, ...$this->zones[$key]], $this->zoneKeys());
    }
}
