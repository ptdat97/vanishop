<?php

declare(strict_types=1);

namespace Plugin\HelloWorld\Infrastructure;

use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Storefront\Contracts\StorefrontBlock;

/**
 * Implementation tham chiếu của StorefrontBlock (R26): dải lời chào trên trang chủ.
 */
final class HelloBannerBlock implements StorefrontBlock
{
    public function type(): string
    {
        return 'hello_banner';
    }

    public function label(): string
    {
        return 'Lời chào (Hello World)';
    }

    public function fields(): array
    {
        return [FieldDefinition::string('message', 'Lời chào', required: true, max: 120)];
    }

    public function resolve(array $config, string $locale): array
    {
        return ['message' => (string) ($config['message'] ?? '')];
    }

    public function view(): string
    {
        return 'vani-hello-world::blocks.banner';
    }
}
