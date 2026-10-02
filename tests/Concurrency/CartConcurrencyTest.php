<?php

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Modules\Cart\Contracts\CartRejected;
use Modules\Cart\Contracts\Carts;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/*
| Nhiều tab/thiết bị cùng sửa một giỏ: khoá dòng carts xếp hàng các thay đổi → không mất cập nhật,
| không lỗi trùng unique (cart_id, variant_id).
*/

require_once __DIR__.'/../../modules/Inventory/Tests/Feature/InventoryTestHelpers.php';

uses(DatabaseTruncation::class)->group('concurrency');

beforeEach(function () {
    if (DB::getDriverName() !== 'mysql') {
        $this->markTestSkipped('Concurrency test cần MySQL (SQLite in-memory không chia sẻ giữa tiến trình).');
    }

    $this->brand = Brand::factory()->create();
    [$this->variant] = P::variants(T::product($this->brand->id), ['S']);
    P::priceList(['code' => 'base'], [$this->variant->id => [500_000]]);
    I::stock(I::location(), $this->variant->id, 100);
});

/**
 * Tác vụ tạo ngoài test (closure không gắn scope class test của Pest → tiến trình con unserialize được).
 *
 * @return list<Closure>
 */
function cartAddTasks(int $count, string $publicId, string $token, int $variantId): array
{
    $tasks = [];
    foreach (range(1, $count) as $ignored) {
        $tasks[] = static function () use ($publicId, $token, $variantId): string {
            app(CurrentContext::class)->set(new ContextScope(Actor::guest(), 'vi'));

            try {
                app(Carts::class)->addLine(new CartKey($publicId, $token), $variantId, 1);

                return 'ok';
            } catch (CartRejected $exception) {
                return $exception->errorCode();
            }
        };
    }

    return $tasks;
}

it('8 tiến trình cùng thêm một variant vào một giỏ → cộng dồn đủ 8, một dòng', function () {
    app(CurrentContext::class)->set(new ContextScope(Actor::guest(), 'vi'));
    $key = app(Carts::class)->create('VND')->key;

    [$publicId, $token] = [$key->publicId, $key->token];
    $tasks = cartAddTasks(8, $publicId, $token, $this->variant->id);

    $results = Concurrency::driver('process')->run($tasks);

    expect(array_count_values($results))->toBe(['ok' => 8])
        ->and(DB::table('cart_lines')->count())->toBe(1)
        ->and((int) DB::table('cart_lines')->value('quantity'))->toBe(8);
});
