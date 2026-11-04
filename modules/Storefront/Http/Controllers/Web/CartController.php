<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Modules\Cart\Contracts\Carts;
use Modules\Cart\Contracts\Data\CartLineView;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Storefront\Application\CartPresenter;
use Modules\Storefront\Application\NativeCart;
use Modules\Storefront\Http\Requests\LineOptions;

/**
 * Giỏ của native storefront qua form POST — dùng được khi JS tắt (ADR-025).
 */
final class CartController
{
    /** Flash một lần: thay đổi giỏ gần nhất (đọc ở layout hoặc /_vani/phien). */
    public const CHANGE_FLASH = 'vani.cart_change';

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
            'buy_now' => ['nullable', 'boolean'],
        ], ['variant_id.required' => __('storefront::messages.choose_variant')]);

        $quantity = (int) ($data['quantity'] ?? 1);
        $buyNow = $request->boolean('buy_now');
        $key = $this->cart->keyOrCreate();
        $options = LineOptions::from($request->input('options'));
        $view = $buyNow
            ? $this->carts->buyNow($key, (int) $data['variant_id'], $quantity, $options)
            : $this->carts->addLine($key, (int) $data['variant_id'], $quantity, $options);
        $line = collect($view->lines)->firstWhere('variantId', (int) $data['variant_id']);

        // Mua ngay (§3.8): chỉ dòng vừa thêm được chọn, đi thẳng tới checkout; các dòng khác ở lại giỏ.
        $redirect = $buyNow
            ? redirect()->to(LocalizedUrls::route('storefront.checkout'))
            : redirect()->to(LocalizedUrls::route('storefront.cart'))->with('status', __('storefront::messages.added_to_cart'));

        return $redirect->with(self::CHANGE_FLASH, $line === null ? null : $this->change('add', $line, $quantity));
    }

    /**
     * Chọn dòng để thanh toán: `lines[]` là các dòng đã đánh dấu (form gửi cả khi JS tắt).
     */
    public function select(Request $request): RedirectResponse
    {
        $data = $request->validate(['lines' => ['nullable', 'array', 'max:100'], 'lines.*' => ['integer', 'min:1']]);
        $key = $this->cart->key();
        abort_if($key === null, 404);

        $this->carts->selectOnly($key, array_values(array_map('intval', $data['lines'] ?? [])));

        return redirect()->to(LocalizedUrls::route('storefront.cart'));
    }

    public function update(Request $request, int $line): RedirectResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:1000']]);
        $key = $this->cart->key();
        abort_if($key === null, 404);

        $before = $this->line($line);
        $this->carts->updateLine($key, $line, (int) $data['quantity']);
        $delta = (int) $data['quantity'] - ($before->quantity ?? 0);

        return redirect()->to(LocalizedUrls::route('storefront.cart'))
            ->with(self::CHANGE_FLASH, $before === null || $delta === 0 ? null : $this->change($delta > 0 ? 'add' : 'remove', $before, abs($delta)));
    }

    public function remove(int $line): RedirectResponse
    {
        $key = $this->cart->key();
        abort_if($key === null, 404);

        $before = $this->line($line);
        $this->carts->removeLine($key, $line);

        return redirect()->to(LocalizedUrls::route('storefront.cart'))
            ->with(self::CHANGE_FLASH, $before === null ? null : $this->change('remove', $before, $before->quantity));
    }

    private function line(int $lineId): ?CartLineView
    {
        return collect($this->cart->current()->lines ?? [])->firstWhere('id', $lineId);
    }

    /**
     * Thay đổi giỏ cho sự kiện trình duyệt `vani:cart-updated` (theme phát ở trang kế tiếp — đo quảng cáo, 0.3.43).
     *
     * @param  'add'|'remove'  $action
     * @return array{action: string, variant_id: int, sku: string|null, name: string|null, quantity: int, unit_amount: int|null, currency: string|null}
     */
    private function change(string $action, CartLineView $line, int $quantity): array
    {
        return [
            'action' => $action, 'variant_id' => $line->variantId, 'sku' => $line->variant?->sku, 'name' => $line->variant?->name,
            'quantity' => $quantity, 'unit_amount' => $line->unitPrice?->amount, 'currency' => $line->unitPrice?->currency->code,
        ];
    }
}
