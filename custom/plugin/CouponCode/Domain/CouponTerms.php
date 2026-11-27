<?php

declare(strict_types=1);

namespace Plugin\CouponCode\Domain;

use Plugin\CouponCode\Persistence\Coupon;

/**
 * Điều kiện của một mã → khuyến mãi của Core (action + rule của `vani.promotion-rules`). Thứ tự rule có chủ ý: phạm vi
 * sản phẩm → bỏ hàng đang giảm giá → đơn tối thiểu (tính trên hàng được giảm) → nhóm khách.
 */
final class CouponTerms
{
    public const SCOPES = ['all', 'products', 'categories', 'collections', 'brands'];

    public const MAX_PERCENT = 90;

    /** Phạm vi → rule của `vani.promotion-rules` và khoá cấu hình. */
    private const SCOPE_RULES = [
        'products' => ['in_products', 'product_ids'],
        'categories' => ['in_categories', 'category_ids'],
        'collections' => ['in_collections', 'slugs'],
        'brands' => ['in_brands', 'slugs'],
    ];

    public static function actionType(Coupon $coupon): string
    {
        return $coupon->discount_type === 'percent' ? 'percent_off' : 'amount_off';
    }

    /**
     * @return array<string, int>
     */
    public static function actionConfig(Coupon $coupon): array
    {
        if ($coupon->discount_type !== 'percent') {
            return ['amount' => $coupon->discount_value];
        }

        return ['basis_points' => $coupon->discount_value * 100] + ($coupon->max_discount === null ? [] : ['max_amount' => $coupon->max_discount]);
    }

    /**
     * @return list<array{type: string, config: array<string, mixed>}>
     */
    public static function rules(Coupon $coupon): array
    {
        $rules = [];
        if (isset(self::SCOPE_RULES[$coupon->scope_type])) {
            [$type, $key] = self::SCOPE_RULES[$coupon->scope_type];
            $values = self::bySlug($coupon) ? array_map('strval', $coupon->scope_slugs) : array_map('intval', $coupon->scope_values);
            $rules[] = ['type' => $type, 'config' => [$key => $values]];
        }
        if ($coupon->exclude_on_sale) {
            $rules[] = ['type' => 'exclude_on_sale', 'config' => []];
        }
        if ($coupon->min_subtotal !== null && $coupon->min_subtotal > 0) {
            $rules[] = ['type' => 'min_order_subtotal', 'config' => ['min_subtotal' => $coupon->min_subtotal]];
        }
        if ($coupon->customer_groups !== []) {
            $rules[] = ['type' => 'in_customer_groups', 'config' => ['groups' => array_map('strval', $coupon->customer_groups)]];
        }

        return $rules;
    }

    /**
     * Mã có áp cho sản phẩm này không (chỉ xét phạm vi; giá sale, đơn tối thiểu, nhóm khách xét ở giỏ).
     *
     * @param  list<int>  $categoryIds  danh mục của sản phẩm kèm danh mục cha
     * @param  list<string>  $collectionSlugs
     */
    public static function coversProduct(Coupon $coupon, int $productId, ?string $brandSlug, array $categoryIds, array $collectionSlugs): bool
    {
        $ids = array_map('intval', $coupon->scope_values);
        $slugs = array_map('strval', $coupon->scope_slugs);

        return match ($coupon->scope_type) {
            'products' => in_array($productId, $ids, true),
            'categories' => array_intersect($categoryIds, $ids) !== [],
            'collections' => array_intersect($collectionSlugs, $slugs) !== [],
            'brands' => $brandSlug !== null && in_array($brandSlug, $slugs, true),
            default => true,
        };
    }

    /** Bộ sưu tập/thương hiệu: rule của `vani.promotion-rules` nhận slug; sản phẩm/danh mục nhận id. */
    public static function bySlug(Coupon $coupon): bool
    {
        return in_array($coupon->scope_type, ['collections', 'brands'], true);
    }
}
