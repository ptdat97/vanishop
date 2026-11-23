<?php

declare(strict_types=1);

namespace Plugin\Bundles\Application;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Promotion\Contracts\Data\ManagedPromotion;
use Modules\Promotion\Contracts\ManagedPromotions;
use Modules\Shared\Support\StoreLocale;
use Plugin\Bundles\BundlesServiceProvider;
use Plugin\Bundles\Infrastructure\BundleAction;
use Plugin\Bundles\Infrastructure\BundleRule;
use Plugin\Bundles\Persistence\Bundle;

/**
 * Lưu combo và đồng bộ khuyến mãi tương ứng của Core (ManagedPromotions) trong cùng một transaction: khuyến mãi không
 * hợp lệ thì combo cũng không được lưu. Mỗi combo là một khuyến mãi cộng dồn, khoá = id combo.
 */
final class Bundles
{
    public const MEDIA_OWNER = 'vani.bundles';

    public function __construct(
        private readonly ManagedPromotions $promotions,
        private readonly MediaDirectory $media,
    ) {}

    /**
     * @param  array{slug: string, name: string, description: ?string, image_media_id: ?int, discount_percent: int, active: bool, starts_at: ?Carbon, ends_at: ?Carbon, position: int, items: list<array{style_id: int, quantity: int}>}  $data
     */
    public function save(array $data, ?Bundle $bundle = null): Bundle
    {
        if ($data['starts_at'] !== null && $data['ends_at'] !== null && $data['ends_at']->lessThanOrEqualTo($data['starts_at'])) {
            throw ValidationException::withMessages(['ends_at' => __('vani-bundles::texts.ends_after_starts')]);
        }
        $locale = StoreLocale::default();

        return DB::transaction(function () use ($data, $bundle, $locale): Bundle {
            $bundle = $bundle === null ? new Bundle : Bundle::query()->lockForUpdate()->findOrFail($bundle->id);
            $translations = (array) ($bundle->translations ?? []);
            $translations[$locale] = ['name' => $data['name'], 'description' => (string) ($data['description'] ?? '')];
            $bundle->fill([
                'slug' => $data['slug'], 'translations' => $translations, 'image_media_id' => $data['image_media_id'],
                'discount_basis_points' => $data['discount_percent'] * 100, 'active' => $data['active'],
                'starts_at' => $data['starts_at'], 'ends_at' => $data['ends_at'], 'position' => $data['position'],
            ])->save();
            $bundle->items()->delete();
            foreach ($data['items'] as $position => $item) {
                $bundle->items()->create(['style_id' => $item['style_id'], 'quantity' => $item['quantity'], 'position' => $position]);
            }
            $bundle->load('items');
            $this->sync($bundle);
            $this->media->syncUsages(self::MEDIA_OWNER, $bundle->id, 'image', $bundle->image_media_id === null ? [] : [$bundle->image_media_id]);

            return $bundle;
        });
    }

    public function delete(Bundle $bundle): void
    {
        DB::transaction(function () use ($bundle): void {
            $bundle = Bundle::query()->lockForUpdate()->findOrFail($bundle->id);
            $this->promotions->remove(BundlesServiceProvider::ID, (string) $bundle->id);
            $this->media->releaseUsages(self::MEDIA_OWNER, $bundle->id);
            $bundle->delete();
        });
    }

    /** Khuyến mãi của combo: lịch và trạng thái theo combo; rule + action mang sẵn danh sách món để giỏ không đọc bảng plugin. */
    public function sync(Bundle $bundle): int
    {
        $config = ['items' => $bundle->requirement()];

        return $this->promotions->sync(BundlesServiceProvider::ID, (string) $bundle->id, new ManagedPromotion(
            name: __('vani-bundles::texts.promotion_name', ['name' => $bundle->text('name', StoreLocale::default())]),
            active: $bundle->active,
            actionType: BundleAction::TYPE,
            actionConfig: [...$config, 'basis_points' => $bundle->discount_basis_points],
            rules: [['type' => BundleRule::TYPE, 'config' => $config]],
            startsAt: $bundle->starts_at?->getTimestamp(),
            endsAt: $bundle->ends_at?->getTimestamp(),
            editRoute: 'admin.plugins.vani-bundles.edit',
        ));
    }
}
