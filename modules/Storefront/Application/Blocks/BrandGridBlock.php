<?php

declare(strict_types=1);

namespace Modules\Storefront\Application\Blocks;

use Modules\Catalog\Contracts\CatalogReader;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;

final class BrandGridBlock implements StorefrontBlock
{
    public function __construct(private readonly CatalogReader $catalog) {}

    public function type(): string
    {
        return 'brand_grid';
    }

    public function label(): string
    {
        return 'Lưới thương hiệu';
    }

    public function fields(): array
    {
        return [FieldDefinition::string('title', 'Tiêu đề', max: 120)];
    }

    public function resolve(array $config, string $locale): array
    {
        return ['title' => (string) ($config['title'] ?? ''), 'brands' => $this->catalog->brands()];
    }

    public function view(): string
    {
        return 'theme::blocks.brand_grid';
    }
}
