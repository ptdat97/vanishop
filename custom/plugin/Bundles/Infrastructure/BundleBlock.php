<?php

declare(strict_types=1);

namespace Plugin\Bundles\Infrastructure;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Shared\Support\StoreLocale;
use Modules\Storefront\Contracts\StorefrontBlock;
use Plugin\Bundles\Persistence\Bundle;

/** Block "Combo sản phẩm" cho Builder Engine (trang chủ, bộ sưu tập, slot): hiện một combo đã chọn. */
final class BundleBlock implements StorefrontBlock
{
    public const TYPE = 'bundle';

    private const MAX_CHOICES = 200;

    public function __construct(private readonly BundlePresenter $presenter) {}

    public function type(): string
    {
        return self::TYPE;
    }

    public function label(): string
    {
        return __('vani-bundles::texts.block_label');
    }

    public function fields(): array
    {
        $choices = Bundle::query()->orderBy('position')->orderByDesc('id')->limit(self::MAX_CHOICES)->get()
            ->mapWithKeys(fn (Bundle $bundle): array => [(string) $bundle->id => $bundle->text('name', StoreLocale::default())])->all();

        return [
            FieldDefinition::string('title', __('vani-bundles::texts.block_title'), max: 120),
            FieldDefinition::select('bundle', __('vani-bundles::texts.block_bundle'), $choices, required: true),
        ];
    }

    public function resolve(array $config, string $locale): array
    {
        return [
            'title' => (string) ($config['title'] ?? ''),
            'bundle' => is_numeric($config['bundle'] ?? null) ? $this->presenter->find((int) $config['bundle'], $locale) : null,
            'scriptSrc' => vani_asset('vani.bundles', 'bundles.js'),
        ];
    }

    public function view(): string
    {
        return 'vani-bundles::blocks.bundle';
    }
}
