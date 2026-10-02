<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Cart\Contracts\Carts;
use Modules\Storefront\Application\CartPresenter;
use Modules\Storefront\Application\NativeCart;

/**
 * Giỏ của native storefront qua form POST — dùng được khi JS tắt (ADR-025).
 */
final class CartController
{
    public function __construct(
        private readonly Carts $carts,
        private readonly NativeCart $cart,
        private readonly CartPresenter $presenter,
    ) {}

    public function show(): View
    {
        $view = $this->cart->current();

        return view('theme::pages.cart', ['cart' => $view === null ? null : $this->presenter->present($view)]);
    }

    public function add(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['nullable', 'integer', 'min:1', 'max:1000'],
        ], ['variant_id.required' => __('storefront::messages.choose_variant')]);

        $this->carts->addLine($this->cart->keyOrCreate(), (int) $data['variant_id'], (int) ($data['quantity'] ?? 1));

        return redirect()->route('storefront.cart')->with('status', __('storefront::messages.added_to_cart'));
    }

    public function update(Request $request, int $line): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:1000']]);
        $key = $this->cart->key();
        abort_if($key === null, 404);

        $this->carts->updateLine($key, $line, (int) $data['quantity']);

        return redirect()->route('storefront.cart');
    }

    public function remove(int $line): RedirectResponse
    {
        $key = $this->cart->key();
        abort_if($key === null, 404);

        $this->carts->removeLine($key, $line);

        return redirect()->route('storefront.cart');
    }
}
