<?php

declare(strict_types=1);

namespace Plugin\PromotionRules\Domain\Rules;

use Modules\Catalog\Contracts\CollectionDirectory;
use Modules\Promotion\Contracts\Data\Eligibility;
use Modules\Promotion\Contracts\Data\PromotionContext;
use Modules\Promotion\Contracts\PromotionRule;

/**
 * Rule: chỉ áp dụng cho các sản phẩm thuộc một trong các bộ sưu tập chỉ định.
 * Cấu hình: {"slugs": ["he-2026", "giam-gia"]}
 */
final class InCollectionsRule implements PromotionRule
{
    public const TYPE = 'in_collections';

    public function __construct(private readonly CollectionDirectory $collections) {}

    public function type(): string
    {
        return self::TYPE;
    }

    public function label(): string
    {
        return 'Thuộc bộ sưu tập';
    }

    public function validateConfig(array $config): array
    {
        $slugs = $config['slugs'] ?? null;
        if (! is_array($slugs) || $slugs === []) {
            return ['Cần khai báo danh sách slug "slugs" không rỗng.'];
        }

        foreach ($slugs as $slug) {
            if (! is_string($slug) || trim($slug) === '') {
                return ['Mỗi slug trong "slugs" phải là chuỗi không rỗng.'];
            }
        }

        return [];
    }

    public function evaluate(PromotionContext $context, array $config, Eligibility $candidates): Eligibility
    {
        $allowedSlugs = array_fill_keys((array) ($config['slugs'] ?? []), true);
        if ($allowedSlugs === []) {
            return Eligibility::none();
        }

        $styleIds = [];
        foreach ($candidates->keys as $key) {
            $line = $context->line($key);
            if ($line !== null) {
                $styleIds[] = $line->styleId;
            }
        }

        $styleCollections = $this->collections->slugsForStyles($styleIds);

        $matchingKeys = [];
        foreach ($candidates->keys as $key) {
            $line = $context->line($key);
            if ($line === null) {
                continue;
            }

            $lineSlugs = $styleCollections[$line->styleId] ?? [];
            foreach ($lineSlugs as $slug) {
                if (isset($allowedSlugs[$slug])) {
                    $matchingKeys[] = $key;
                    break;
                }
            }
        }

        return new Eligibility($matchingKeys);
    }
}
