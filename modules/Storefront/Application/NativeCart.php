<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Illuminate\Contracts\Session\Session;
use Modules\Cart\Contracts\CartRejected;
use Modules\Cart\Contracts\Carts;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Cart\Contracts\Data\CartView;

/**
 * Giỏ của native storefront: khoá giỏ (id công khai + token) giữ trong phiên web thay cho header X-Vani-Cart-Token
 * của Storefront API. Cùng contract `Carts` nên hành vi giống hệt API.
 */
final class NativeCart
{
    private const SESSION_KEY = 'vani.cart';

    public function __construct(
        private readonly Carts $carts,
        private readonly Session $session,
    ) {}

    public function key(): ?CartKey
    {
        $stored = $this->session->get(self::SESSION_KEY);

        return is_array($stored) && isset($stored['id'], $stored['token']) ? new CartKey((string) $stored['id'], (string) $stored['token']) : null;
    }

    /**
     * Giỏ hiện tại (null nếu chưa có hoặc giỏ cũ không dùng được nữa — đã đặt hàng, hết hạn).
     */
    public function current(): ?CartView
    {
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

        $created = $this->carts->create((string) config('vanishop.currency', 'VND'));
        $this->session->put(self::SESSION_KEY, ['id' => $created->key->publicId, 'token' => $created->key->token]);

        return $created->key;
    }

    public function forget(): void
    {
        $this->session->forget(self::SESSION_KEY);
    }
}
