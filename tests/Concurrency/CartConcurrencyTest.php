<?php

use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Concurrency;
use Illuminate\Support\Facades\DB;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Cart\Contracts\CartRejected;
use Modules\Cart\Contracts\Carts;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Channel\Persistence\Models\Channel;
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
    $this->channel = Channel::factory()->forBrand($this->brand, 'vani.test', '/lumiere')->create();
    [$this->variant] = P::variants(T::product($this->brand->id), ['S']);
    P::priceList($this->brand->id, ['code' => 'base'], [$this->channel->id], [$this->variant->id => [500_000]]);
    I::stock(I::location($this->brand, [$this->channel->id]), $this->variant->id, 100);
});

/**
 * Tác vụ tạo ngoài test (closure không gắn scope class test của Pest → tiến trình con unserialize được).
 *
 * @return list<Closure>
 */
function cartAddTasks(int $count, string $publicId, string $token, int $channelId, int $brandId, int $variantId): array
{
    $tasks = [];
    foreach (range(1, $count) as $ignored) {
        $tasks[] = static function () use ($publicId, $token, $channelId, $brandId, $variantId): string {
            app(CurrentContext::class)->set(new ContextScope(Actor::guest(), $channelId, [$brandId], 'vi'));

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
    app(CurrentContext::class)->set(new ContextScope(Actor::guest(), $this->channel->id, [$this->brand->id], 'vi'));
    $key = app(Carts::class)->create('VND')->key;

    [$publicId, $token] = [$key->publicId, $key->token];
    [$channelId, $brandId, $variantId] = [$this->channel->id, $this->brand->id, $this->variant->id];

    $tasks = cartAddTasks(8, $publicId, $token, $channelId, $brandId, $variantId);

    $results = Concurrency::driver('process')->run($tasks);

    expect(array_count_values($results))->toBe(['ok' => 8])
        ->and(DB::table('cart_lines')->count())->toBe(1)
        ->and((int) DB::table('cart_lines')->value('quantity'))->toBe(8);
});
