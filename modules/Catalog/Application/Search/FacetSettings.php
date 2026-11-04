<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Search;

use Illuminate\Validation\ValidationException;
use Modules\Catalog\Domain\AttributeKind;
use Modules\Catalog\Persistence\Models\Attribute;
use Modules\Tenancy\Contracts\Settings;

/**
 * Bộ lọc của trang danh sách do Admin cấu hình (reference-parity §3.5-1, Admin → Catalog → Bộ lọc): bật/tắt, thứ tự,
 * kiểu hiển thị. Lưu ở `core`/`catalog.facets` = list<{key, enabled, display}>. Facet có sẵn: thương hiệu, nhóm màu,
 * từng thuộc tính spec đánh dấu "dùng để lọc". Facet mới (thuộc tính vừa bật lọc) tự thêm cuối danh sách, đang bật.
 * Lọc theo giá/size chưa có ở SearchProvider nên chưa là facet.
 */
final class FacetSettings
{
    public const SETTING = 'catalog.facets';

    public const DISPLAYS = ['list', 'swatch'];

    public function __construct(private readonly Settings $settings) {}

    /**
     * @return array<string, array{key: string, type: 'brand'|'color'|'attribute', code: string|null, label: string, displays: list<string>}>
     */
    public function available(string $locale): array
    {
        $facets = [
            'brand' => ['key' => 'brand', 'type' => 'brand', 'code' => null, 'label' => __('catalog::labels.facet.brand', [], $locale), 'displays' => ['list']],
            'color' => ['key' => 'color', 'type' => 'color', 'code' => null, 'label' => __('catalog::labels.facet.color', [], $locale), 'displays' => self::DISPLAYS],
        ];
        $attributes = Attribute::query()->with('translations')->where('kind', AttributeKind::Spec)->where('is_filterable', true)->orderBy('position')->orderBy('code')->get();
        foreach ($attributes as $attribute) {
            $facets["attr:{$attribute->code}"] = ['key' => "attr:{$attribute->code}", 'type' => 'attribute', 'code' => $attribute->code, 'label' => (string) ($attribute->translate('name', $locale) ?? $attribute->code), 'displays' => ['list']];
        }

        return $facets;
    }

    /**
     * Cấu hình đã lưu ghép với facet hiện có: theo thứ tự đã lưu, bỏ facet không còn, thêm facet mới ở cuối.
     *
     * @return list<array{key: string, type: string, code: string|null, label: string, displays: list<string>, enabled: bool, display: string}>
     */
    public function configured(string $locale): array
    {
        $available = $this->available($locale);
        $stored = $this->settings->get('core', self::SETTING);
        $result = [];
        foreach (is_array($stored) ? $stored : [] as $entry) {
            $key = is_array($entry) ? (string) ($entry['key'] ?? '') : '';
            if (! isset($available[$key]) || isset($result[$key])) {
                continue;
            }
            $display = (string) ($entry['display'] ?? 'list');
            $result[$key] = [...$available[$key], 'enabled' => (bool) ($entry['enabled'] ?? true), 'display' => in_array($display, $available[$key]['displays'], true) ? $display : 'list'];
        }
        foreach ($available as $key => $facet) {
            $result[$key] ??= [...$facet, 'enabled' => true, 'display' => 'list'];
        }

        return array_values($result);
    }

    /**
     * @param  list<array{key?: mixed, enabled?: mixed, display?: mixed}>  $facets
     *
     * @throws ValidationException
     */
    public function save(array $facets, string $locale): void
    {
        $available = $this->available($locale);
        $clean = [];
        foreach (array_values($facets) as $index => $facet) {
            $key = (string) ($facet['key'] ?? '');
            $display = (string) ($facet['display'] ?? 'list');
            if (! isset($available[$key])) {
                throw ValidationException::withMessages(["facets.{$index}.key" => __('catalog::messages.facet_unknown')]);
            }
            if (! in_array($display, $available[$key]['displays'], true)) {
                throw ValidationException::withMessages(["facets.{$index}.display" => __('catalog::messages.facet_display_invalid')]);
            }
            $clean[$key] = ['key' => $key, 'enabled' => filter_var($facet['enabled'] ?? true, FILTER_VALIDATE_BOOL), 'display' => $display];
        }

        $this->settings->set('core', self::SETTING, array_values($clean));
    }
}
