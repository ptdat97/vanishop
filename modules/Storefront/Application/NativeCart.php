<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Illuminate\Contracts\Session\Session;
use Modules\Cart\Contracts\CartRejected;
use Modules\Cart\Contracts\Carts;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Cart\Contracts\Data\CartView;
use Modules\Shared\Context\ActorType;
use Modules\Shared\Context\CurrentContext;

/**
 * Giỏ của native storefront. Khách vãng lai: khoá giỏ (id công khai + token) giữ trong phiên web thay cho header
 * X-Vani-Cart-Token. Khách đã đăng nhập: giỏ của khách (`Carts::forCustomer`, truy cập theo actor, không cần token).
 * Cùng contract `Carts` nên hành vi giống hệt Storefront API.
 */
final class NativeCart
{
    private const SESSION_KEY = 'vani.cart';

    public function __construct(
        private readonly Carts $carts,
        private readonly Session $session,
        private readonly CurrentContext $context,
    ) {}

    public function key(): ?CartKey
    {
        $customerId = $this->customerId();
        if ($customerId !== null) {
            return new CartKey($this->carts->forCustomer($customerId, $this->currency())->id, '');
        }

        $stored = $this->session->get(self::SESSION_KEY);

        return is_array($stored) && isset($stored['id'], $stored['token']) ? new CartKey((string) $stored['id'], (string) $stored['token']) : null;
    }

    /**
     * Giỏ hiện tại (null nếu chưa có hoặc giỏ cũ không dùng được nữa — đã đặt hàng, hết hạn).
     */
    public function current(): ?CartView
    {
        $customerId = $this->customerId();
        if ($customerId !== null) {
            return $this->carts->forCustomer($customerId, $this->currency());
        }

        $key = $this->key();
        if ($key === null) {
            return null;
        }

        try {
            $view = $this->carts->view($key);
        } catch (CartRejected) {
            $this->forget();

            return null;
        }

        if ($view->status !== 'active') {
            $this->forget();

            return null;
        }

        return $view;
    }

    public function keyOrCreate(): CartKey
    {
        if ($this->current() !== null) {
            return $this->key();
        }

        $created = $this->carts->create($this->currency());
        $this->session->put(self::SESSION_KEY, ['id' => $created->key->publicId, 'token' => $created->key->token]);

        return $created->key;
    }

    /**
     * Sau khi đăng nhập: gộp giỏ vãng lai trong phiên vào giỏ của khách.
     */
    public function attachGuestCartTo(int $customerId): void
    {
        $stored = $this->session->get(self::SESSION_KEY);
        if (is_array($stored) && isset($stored['id'], $stored['token'])) {
            try {
                $this->carts->attachToCustomer(new CartKey((string) $stored['id'], (string) $stored['token']), $customerId);
            } catch (CartRejected) {
                // Giỏ cũ đã đóng/hết hạn: bỏ qua.
            }
        }
        $this->forget();
    }

    public function forget(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }

    private function customerId(): ?int
    {
        if (! $this->context->has()) {
            return null;
        }
        $actor = $this->context->actor();

        return $actor->type === ActorType::Customer ? $actor->id : null;
    }

    private function currency(): string
    {
        return (string) config('vanishop.currency', 'VND');
    }
}
