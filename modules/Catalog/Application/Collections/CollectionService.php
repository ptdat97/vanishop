<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Collections;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Events\ProductUpdated;
use Modules\Catalog\Persistence\Models\ProductCollection;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Identity\Contracts\AuditLogger;

/**
 * Bộ sưu tập thủ công: danh sách sản phẩm theo thứ tự nhập.
 */
final class CollectionService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * @param  array{slug: string, status: string, position: int, translations: array<string, array{name: string, description?: string|null}>}  $data
     * @param  list<string>  $styleCodes  thứ tự hiển thị
     */
    public function save(int $brandId, array $data, array $styleCodes, ?ProductCollection $collection = null): ProductCollection
    {
        $codes = array_values(array_unique(array_filter(array_map('trim', $styleCodes))));
        $styles = Style::query()->where('brand_id', $brandId)->whereIn('style_code', $codes)->pluck('id', 'style_code');

        $missing = array_values(array_diff($codes, $styles->keys()->all()));
        if ($missing !== []) {
            throw ValidationException::withMessages(['style_codes' => __('catalog::messages.style_codes_not_found', ['codes' => implode(', ', $missing)])]);
        }

        return DB::transaction(function () use ($brandId, $data, $codes, $styles, $collection): ProductCollection {
            $collection ??= new ProductCollection(['brand_id' => $brandId]);
            $collection->fill(['slug' => $data['slug'], 'status' => $data['status'], 'position' => $data['position']])->save();
            $collection->syncTranslations($data['translations']);

            $before = $collection->styles()->pluck('styles.id')->all();
            $sync = [];
            foreach ($codes as $position => $code) {
                $sync[(int) $styles[$code]] = ['position' => $position];
            }
            $collection->styles()->sync($sync);

            $this->audit->record($collection->wasRecentlyCreated ? 'catalog.collection.created' : 'catalog.collection.updated', 'collection', $collection->id, ['slug' => $collection->slug]);

            // Sản phẩm vào/ra bộ sưu tập cần cập nhật chỉ mục tìm kiếm.
            foreach (array_unique([...$before, ...array_keys($sync)]) as $styleId) {
                $style = Style::query()->find($styleId);
                if ($style !== null) {
                    event(new ProductUpdated($style->id, $style->brand_id, $style->style_code));
                }
            }

            return $collection;
        });
    }

    public function delete(ProductCollection $collection): void
    {
        DB::transaction(function () use ($collection): void {
            $styles = Style::query()->whereIn('id', $collection->styles()->pluck('styles.id'))->get();
            $collection->delete();
            $this->audit->record('catalog.collection.deleted', 'collection', $collection->id, ['slug' => $collection->slug]);

            foreach ($styles as $style) {
                event(new ProductUpdated($style->id, $style->brand_id, $style->style_code));
            }
        });
    }
}
