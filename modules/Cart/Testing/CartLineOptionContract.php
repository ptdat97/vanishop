<?php

declare(strict_types=1);

namespace Modules\Cart\Testing;

use Closure;
use Modules\Cart\Contracts\CartLineOption;
use Modules\Cart\Contracts\InvalidCartLineOption;

/**
 * Contract test cho CartLineOption. Kiểm tra: không gửi gì → không có tuỳ chọn; giá trị hợp lệ → mảng scalar/null (lưu JSON
 * được) và chuẩn hoá lại không đổi; giá trị sai → `InvalidCartLineOption` (không ném lỗi khác).
 */
final class CartLineOptionContract
{
    /**
     * @param  Closure(): CartLineOption  $option
     * @param  array<string, mixed>  $valid
     * @param  array<string, mixed>|null  $invalid  null = tuỳ chọn không có giá trị sai
     */
    public static function define(string $label, Closure $option, array $valid, ?array $invalid = null, int $variantId = 1): void
    {
        describe("CartLineOption contract: {$label}", function () use ($option, $valid, $invalid, $variantId): void {
            it('không gửi gì → không có tuỳ chọn', fn () => expect($option()->normalize($variantId, []))->toBe([]));

            it('giá trị hợp lệ → scalar/null, JSON được, chuẩn hoá lại không đổi', function () use ($option, $valid, $variantId): void {
                $normalized = $option()->normalize($variantId, $valid);
                expect($normalized)->not->toBe([]);
                foreach ($normalized as $key => $value) {
                    expect($key)->toBeString()->and($value === null || is_scalar($value))->toBeTrue();
                }
                expect(json_encode($normalized))->toBeString()
                    ->and($option()->normalize($variantId, $normalized))->toBe($normalized);
            });

            if ($invalid !== null) {
                it('giá trị sai → InvalidCartLineOption', fn () => expect(fn () => $option()->normalize($variantId, $invalid))->toThrow(InvalidCartLineOption::class));
            }
        });
    }
}
