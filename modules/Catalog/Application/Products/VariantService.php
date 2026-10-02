<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Domain\SkuPattern;
use Modules\Catalog\Domain\VariantStatus;
use Modules\Catalog\Events\ProductUpdated;
use Modules\Catalog\Events\VariantCreated;
use Modules\Catalog\Persistence\Models\Size;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Identity\Contracts\AuditLogger;

/**
 * Variant (màu × size). Không xoá variant đã phát sinh giao dịch — dùng trạng thái inactive.
 */
final class VariantService
{
    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Sinh variant còn thiếu cho mọi màu của style × các size đã chọn. Không đụng variant đã có.
     *
     * @param  list<int>  $sizeIds
     * @return list<Variant> variant mới tạo
     */
    public function generate(Style $style, array $sizeIds): array
    {
        $sizes = Size::query()->whereIn('id', $sizeIds)->orderBy('sort_order')->get();
        if ($sizes->count() !== count(array_unique($sizeIds)) || $sizes->isEmpty()) {
            throw ValidationException::withMessages(['size_ids' => __('catalog::messages.size_not_found')]);
        }

        $style->load('colors.color', 'colors.variants');
        if ($style->colors->isEmpty()) {
            throw ValidationException::withMessages(['size_ids' => __('catalog::messages.variants_need_colors')]);
        }

        return DB::transaction(function () use ($style, $sizes): array {
            $created = [];
            foreach ($style->colors as $styleColor) {
                foreach ($sizes as $size) {
                    if ($styleColor->variants->contains('size_id', $size->id)) {
                        continue;
                    }

                    $sku = SkuPattern::make($style->style_code, $styleColor->color->code, $size->code);
                    if (Variant::query()->withoutGlobalScopes()->where('sku', $sku)->exists()) {
                        throw ValidationException::withMessages(['size_ids' => __('catalog::messages.sku_taken', ['sku' => $sku])]);
                    }

                    $created[] = Variant::query()->create([
                        'style_id' => $style->id,
                        'style_color_id' => $styleColor->id,
                        'size_id' => $size->id,
                        'sku' => $sku,
                        'status' => VariantStatus::Active,
                    ]);
                }
            }

            $this->audit->record('catalog.variants.generated', 'style', $style->id, ['count' => count($created)]);
            foreach ($created as $variant) {
                event(new VariantCreated($variant->id, $style->id, $variant->sku));
            }
            event(new ProductUpdated($style->id, $style->style_code));

            return $created;
        });
    }

    /**
     * @param  array{sku: string, barcode: string|null, status: string, weight_gram: int|null}  $data
     */
    public function update(Style $style, Variant $variant, array $data): Variant
    {
        return DB::transaction(function () use ($style, $variant, $data): Variant {
            $before = $variant->only(['sku', 'barcode', 'status', 'weight_gram']);
            $variant->fill($data)->save();

            $this->audit->record('catalog.variant.updated', 'variant', $variant->id, ['before' => $before, 'after' => $variant->only(['sku', 'barcode', 'status', 'weight_gram'])]);
            event(new ProductUpdated($style->id, $style->style_code));

            return $variant;
        });
    }
}
