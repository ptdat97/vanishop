<?php

declare(strict_types=1);

namespace Modules\Cart\Application;

use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Cart\Contracts\CartRejected;
use Modules\Cart\Contracts\Carts;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Cart\Contracts\Data\CartLineDraft;
use Modules\Cart\Contracts\Data\CartView;
use Modules\Cart\Contracts\Data\NewCart;
use Modules\Cart\Domain\CartLimits;
use Modules\Cart\Domain\CartStatus;
use Modules\Cart\Events\CartUpdated;
use Modules\Cart\Persistence\Models\Cart;
use Modules\Cart\Persistence\Models\CartLine;
use Modules\Catalog\Contracts\CatalogReader;
use Modules\Catalog\Contracts\Data\SellableVariant;
use Modules\Extension\Facades\Hook;
use Modules\Inventory\Contracts\AvailabilityReader;
use Modules\Pricing\Contracts\Data\PricingContext;
use Modules\Pricing\Contracts\Data\ResolvedPrice;
use Modules\Pricing\Contracts\PriceResolver;
use Modules\Shared\Context\ActorType;
use Modules\Shared\Context\CurrentContext;

/**
 * Mọi thay đổi giỏ khoá dòng `carts` (FOR UPDATE) trước → hai tab cùng sửa một giỏ được xếp hàng,
 * không mất cập nhật. Giỏ không giữ hàng (docs/03-domains/cart-checkout.md §1).
 */
final class CartService implements Carts
{
    public function __construct(
        private readonly CurrentContext $context,
        private readonly CartViewBuilder $views,
        private readonly CatalogReader $catalog,
        private readonly PriceResolver $prices,
        private readonly AvailabilityReader $availability,
        private readonly CartLimits $limits,
    ) {}

    public function create(string $currencyCode): NewCart
    {
        $token = Str::random(48);
        $cart = Cart::query()->create([
            'public_id' => (string) Str::ulid(),
            'token_hash' => hash('sha256', $token),
            'channel_id' => $this->channelId(),
            'currency_code' => $currencyCode,
            'status' => CartStatus::Active,
            'last_activity_at' => now(),
        ]);

        return new NewCart(new CartKey($cart->public_id, $token), $this->build($cart));
    }

    public function view(CartKey $key): CartView
    {
        return $this->build($this->find($key));
    }

    public function addLine(CartKey $key, int $variantId, int $quantity): CartView
    {
        return $this->mutate($key, function (Cart $cart) use ($variantId, $quantity): void {
            $line = CartLine::query()->where('cart_id', $cart->id)->where('variant_id', $variantId)->first();
            if ($line === null && ! $this->limits->canAddLine(CartLine::query()->where('cart_id', $cart->id)->count())) {
                throw CartRejected::tooManyLines($this->limits->maxLines);
            }

            $this->assertQuantity($quantity);
            [$variant, $price] = $this->guard($cart, $variantId, ($line->quantity ?? 0) + $quantity);

            $line ??= new CartLine(['cart_id' => $cart->id, 'variant_id' => $variantId]);
            $line->fill([
                'brand_id' => $variant->brandId,
                'quantity' => ($line->quantity ?? 0) + $quantity,
                // Thêm lại = khách đã thấy giá hiện tại → làm mới giá chụp.
                'unit_price_snapshot' => $price->amount->amount,
            ])->save();
        });
    }

    public function updateLine(CartKey $key, int $lineId, int $quantity): CartView
    {
        if ($quantity === 0) {
            return $this->removeLine($key, $lineId);
        }

        return $this->mutate($key, function (Cart $cart) use ($lineId, $quantity): void {
            $line = $this->line($cart, $lineId);
            $this->assertQuantity($quantity);
            if ($quantity > $line->quantity) {
                $this->guard($cart, $line->variant_id, $quantity);
            }
            $line->update(['quantity' => $quantity]);
        });
    }

    public function removeLine(CartKey $key, int $lineId): CartView
    {
        return $this->mutate($key, fn (Cart $cart) => $this->line($cart, $lineId)->delete());
    }

    public function merge(CartKey $source, CartKey $target): CartView
    {
        if ($source->publicId === $target->publicId) {
            return $this->view($target);
        }

        $this->find($source);
        $this->find($target);

        return DB::transaction(function () use ($source, $target): CartView {
            // Khoá theo thứ tự public_id để hai lần gộp ngược chiều không deadlock.
            $locked = Cart::query()->whereIn('public_id', [$source->publicId, $target->publicId])->orderBy('public_id')->lockForUpdate()->get()->keyBy('public_id');

            return $this->mergeLocked($locked[$source->publicId], $locked[$target->publicId]);
        });
    }

    public function forCustomer(int $customerId, string $currencyCode): CartView
    {
        $cart = $this->openCartOf($customerId);
        if ($cart === null) {
            $cart = Cart::query()->create([
                'public_id' => (string) Str::ulid(),
                'token_hash' => hash('sha256', Str::random(48)), // không ai giữ token: chỉ truy cập qua phiên khách
                'channel_id' => $this->channelId(),
                'customer_id' => $customerId,
                'currency_code' => $currencyCode,
                'status' => CartStatus::Active,
                'last_activity_at' => now(),
            ]);
        }

        return $this->build($cart);
    }

    public function attachToCustomer(CartKey $guestCart, int $customerId): CartView
    {
        $guest = $this->find($guestCart);
        if ($guest->customer_id !== null && $guest->customer_id !== $customerId) {
            throw CartRejected::notFound();
        }

        return DB::transaction(function () use ($guest, $customerId): CartView {
            $existing = $this->openCartOf($customerId);
            if ($existing === null || $existing->id === $guest->id) {
                $locked = Cart::query()->whereKey($guest->id)->lockForUpdate()->firstOrFail();
                $this->assertOpen($locked);
                $locked->update(['customer_id' => $customerId, 'token_hash' => hash('sha256', Str::random(48))]);
                $this->touch($locked);

                return $this->build($locked);
            }

            $locked = Cart::query()->whereIn('id', [$guest->id, $existing->id])->orderBy('public_id')->lockForUpdate()->get()->keyBy('id');

            return $this->mergeLocked($locked[$guest->id], $locked[$existing->id]);
        }, attempts: 3);
    }

    public function lockForCheckout(CartKey $key): CartView
    {
        $this->find($key);
        $cart = Cart::query()->where('public_id', $key->publicId)->lockForUpdate()->firstOrFail();
        $this->assertOpen($cart);

        return $this->build($cart);
    }

    public function markConverted(CartKey $key, string $orderPublicId): void
    {
        $cart = $this->find($key);
        $cart->update([
            'status' => CartStatus::Converted,
            'meta' => [...($cart->meta ?? []), 'order_id' => $orderPublicId],
            'lock_version' => $cart->lock_version + 1,
            'last_activity_at' => now(),
        ]);
        event(new CartUpdated($cart->public_id, $cart->channel_id, $cart->customer_id));
    }

    /**
     * @param  \Closure(Cart): mixed  $change
     */
    private function mutate(CartKey $key, \Closure $change): CartView
    {
        $this->find($key);

        return DB::transaction(function () use ($key, $change): CartView {
            $cart = Cart::query()->where('public_id', $key->publicId)->lockForUpdate()->firstOrFail();
            $this->assertOpen($cart);
            $change($cart);
            $this->touch($cart);

            return $this->build($cart);
        }, attempts: 3);
    }

    /**
     * Kiểm tra variant bán được trên kênh, có giá, đủ hàng cho số lượng sau thay đổi, và quy tắc plugin.
     *
     * @return array{SellableVariant, ResolvedPrice}
     */
    private function guard(Cart $cart, int $variantId, int $quantity): array
    {
        $this->assertQuantity($quantity);

        $variant = $this->catalog->sellableVariants([$variantId], $this->locale(), $this->now())[$variantId] ?? null;
        $price = $this->prices->forVariants([$variantId], new PricingContext($cart->channel_id, $this->now(), null))[$variantId] ?? null;
        if ($variant === null || $price === null) {
            throw CartRejected::variantUnavailable($variantId);
        }
        if (($this->availability->forChannel([$variantId], $cart->channel_id)[$variantId] ?? 0) < $quantity) {
            throw CartRejected::insufficientStock($variantId);
        }

        $errors = Hook::collect('vani.cart.validate_line', new CartLineDraft($cart->public_id, $cart->channel_id, $cart->customer_id, $variantId, $variant->brandId, $quantity, $price->amount->amount));
        if ($errors !== []) {
            throw CartRejected::byRule($variantId, array_map('strval', $errors));
        }

        return [$variant, $price];
    }

    private function assertQuantity(int $quantity): void
    {
        $error = $this->limits->checkQuantity($quantity);
        if ($error !== null) {
            throw CartRejected::quantity($error, $this->limits->maxLineQuantity);
        }
    }

    private function find(CartKey $key): Cart
    {
        $cart = Cart::query()->where('public_id', $key->publicId)->where('channel_id', $this->channelId())->first();

        // Token sai và giỏ không tồn tại trả cùng một lỗi → không dò được public_id hợp lệ.
        // Giỏ của khách hàng: chính khách đó (phiên đăng nhập) truy cập được mà không cần token.
        $ownedByActor = $cart !== null && $cart->customer_id !== null && $cart->customer_id === $this->customerId();
        if ($cart === null || (! $ownedByActor && ! hash_equals($cart->token_hash, hash('sha256', $key->token)))) {
            throw CartRejected::notFound();
        }

        return $cart;
    }

    private function mergeLocked(Cart $from, Cart $into): CartView
    {
        $this->assertOpen($from);
        $this->assertOpen($into);

        $incoming = CartLine::query()->where('cart_id', $from->id)->get();
        $existing = CartLine::query()->where('cart_id', $into->id)->get()->keyBy('variant_id');
        $variantIds = $incoming->pluck('variant_id')->all();
        $sellable = $this->catalog->sellableVariants($variantIds, $this->locale(), $this->now());
        $prices = $this->prices->forVariants($variantIds, new PricingContext($into->channel_id, $this->now(), null));
        $stock = $this->availability->forChannel($variantIds, $into->channel_id);
        $lineCount = $existing->count();

        foreach ($incoming as $line) {
            $current = $existing->get($line->variant_id);
            if (! isset($sellable[$line->variant_id], $prices[$line->variant_id]) || ($current === null && ! $this->limits->canAddLine($lineCount))) {
                continue;
            }

            $quantity = $this->limits->mergedQuantity($current->quantity ?? 0, $line->quantity, $stock[$line->variant_id] ?? 0);
            if ($quantity < 1) {
                continue;
            }

            CartLine::query()->updateOrCreate(
                ['cart_id' => $into->id, 'variant_id' => $line->variant_id],
                ['brand_id' => $line->brand_id, 'quantity' => $quantity, 'unit_price_snapshot' => $current->unit_price_snapshot ?? $line->unit_price_snapshot],
            );
            $lineCount += $current === null ? 1 : 0;
        }

        $from->update(['status' => CartStatus::Merged, 'lock_version' => $from->lock_version + 1, 'last_activity_at' => now()]);
        $this->touch($into);

        return $this->build($into);
    }

    private function openCartOf(int $customerId): ?Cart
    {
        return Cart::query()->where('customer_id', $customerId)->where('channel_id', $this->channelId())
            ->where('status', CartStatus::Active)->latest('last_activity_at')->first();
    }

    private function line(Cart $cart, int $lineId): CartLine
    {
        return CartLine::query()->where('cart_id', $cart->id)->whereKey($lineId)->first() ?? throw CartRejected::lineNotFound();
    }

    private function assertOpen(Cart $cart): void
    {
        if (! $cart->status->isOpen()) {
            throw CartRejected::closed();
        }
    }

    private function touch(Cart $cart): void
    {
        $cart->update(['lock_version' => $cart->lock_version + 1, 'last_activity_at' => now()]);
        event(new CartUpdated($cart->public_id, $cart->channel_id, $cart->customer_id));
    }

    private function build(Cart $cart): CartView
    {
        return $this->views->build($cart, $this->locale(), $this->now());
    }

    private function channelId(): int
    {
        return $this->context->channelId() ?? throw CartRejected::notFound();
    }

    private function customerId(): ?int
    {
        if (! $this->context->has()) {
            return null;
        }
        $actor = $this->context->actor();

        return $actor->type === ActorType::Customer ? $actor->id : null;
    }

    private function locale(): string
    {
        return $this->context->scope()->locale ?? App::getLocale();
    }

    private function now(): int
    {
        return now()->getTimestamp();
    }
}
