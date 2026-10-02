<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Log;
use Modules\Extension\Contracts\Extensions;
use Modules\Storefront\Contracts\StorefrontEnricher;

/**
 * Gắn dữ liệu của plugin (StorefrontEnricher) vào tài nguyên storefront dưới `extensions.<plugin-id>`.
 */
final class Enrichment
{
    public function __construct(private readonly Extensions $extensions) {}

    /**
     * @param  list<array<string, mixed>>  $items
     * @return list<array<string, mixed>>
     */
    public function apply(string $resource, array $items): array
    {
        if ($items === []) {
            return $items;
        }

        $ids = array_flip(array_map(fn (array $item): string => (string) $item['id'], $items));

        foreach ($this->extensions->tagged(StorefrontEnricher::TAG) as $enricher) {
            if (! $enricher instanceof StorefrontEnricher || $enricher->resource() !== $resource) {
                continue;
            }

            $owner = $this->extensions->ownerOf($enricher) ?? 'core';
            $result = $this->extensions->call($enricher, fn (): array => $enricher->enrich($items, App::getLocale()), [], "storefront.enrich.{$resource}");

            foreach ($result as $id => $data) {
                if (! isset($ids[(string) $id]) || ! is_array($data)) {
                    Log::warning('StorefrontEnricher trả dữ liệu cho phần tử không có trong danh sách, bỏ qua.', ['plugin' => $owner, 'resource' => $resource, 'id' => $id]);

                    continue;
                }
                $items[$ids[(string) $id]]['extensions'][$owner] = $data;
            }
        }

        return $items;
    }

    /**
     * @param  array<string, mixed>  $item
     * @return array<string, mixed>
     */
    public function applyOne(string $resource, array $item): array
    {
        return $this->apply($resource, [$item])[0];
    }
}
