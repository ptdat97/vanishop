<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Web;

use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Modules\Cart\Contracts\Carts;
use Modules\Cart\Contracts\Data\CartLineView;
use Modules\Shared\Support\LocalizedUrls;
use Modules\Storefront\Application\CartPresenter;
use Modules\Storefront\Application\CartVoucher;
use Modules\Storefront\Application\NativeCart;
use Modules\Storefront\Http\Requests\LineOptions;

/**
 * Giỏ của native storefront. Form POST — dùng được khi JS tắt (ADR-025); cùng route trả JSON khi request gửi
 * `Accept: application/json` (giỏ mini offcanvas, `window.vani.cart` trong app.js của theme) — xem {@see self::payload()}.
 */
final class CartController
{
    /** Flash một lần: thay đổi giỏ gần nhất (đọc ở layout hoặc /_vani/phien). */
    public const CHANGE_FLASH = 'vani.cart_change';

    /** Header JS gửi để nhận thêm HTML thân trang giỏ (`page_html`). */
    public const VIEW_HEADER = 'X-Vani-Cart-View';

    public function __construct(
        private readonly Carts $carts,
        private readonly NativeCart $cart,
        private readonly CartPresenter $presenter,
        private readonly CartVoucher $voucher,
    ) {}

    public function show(Request $request): View|JsonResponse
    {
        if ($request->expectsJson()) {
            return $this->payload();
        }
        $view = $this->cart->current();
        $key = $view === null ? null : $this->cart->key();

        return view('theme::pages.cart', [
            'cart' => $view === null ? null : $this->presenter->present($view),
            'voucher' => $key === null ? null : $this->voucher->preview($key),
        ]);
    }

    public function add(Request $request): RedirectResponse|JsonResponse
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
        $change = $line === null ? null : $this->presenter->change('add', $line, $quantity);

        // Mua ngay (§3.8): chỉ dòng vừa thêm được chọn, đi thẳng tới checkout; các dòng khác ở lại giỏ.
        if ($request->expectsJson()) {
            return $this->payload($change, __('storefront::messages.added_to_cart'), $buyNow ? LocalizedUrls::route('storefront.checkout') : null);
        }
        $redirect = $buyNow
            ? redirect()->to(LocalizedUrls::route('storefront.checkout'))
            : redirect()->to(LocalizedUrls::route('storefront.cart'))->with('status', __('storefront::messages.added_to_cart'));

        return $redirect->with(self::CHANGE_FLASH, $change);
    }

    /**
     * Chọn dòng để thanh toán: `lines[]` là các dòng đã đánh dấu (form gửi cả khi JS tắt).
     */
    public function select(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['lines' => ['nullable', 'array', 'max:100'], 'lines.*' => ['integer', 'min:1'], 'checkout' => ['nullable', 'boolean']]);
        $key = $this->cart->key();
        abort_if($key === null, 404);

        $this->carts->selectOnly($key, array_values(array_map('intval', $data['lines'] ?? [])));
        // `checkout=1` (0.3.95): nút "Thanh toán" khi giỏ chưa sẵn sàng — chọn các dòng mua được rồi sang checkout ngay.
        $next = $request->boolean('checkout') && $this->cart->current()?->isCheckoutReady() ? LocalizedUrls::route('storefront.checkout') : null;

        if ($request->expectsJson()) {
            return $this->payload(null, null, $next);
        }

        return redirect()->to($next ?? LocalizedUrls::route('storefront.cart'));
    }

    public function update(Request $request, int $line): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:1000']]);
        $key = $this->cart->key();
        abort_if($key === null, 404);

        $before = $this->line($line);
        $this->carts->updateLine($key, $line, (int) $data['quantity']);
        $delta = (int) $data['quantity'] - ($before->quantity ?? 0);
        $change = $before === null || $delta === 0 ? null : $this->presenter->change($delta > 0 ? 'add' : 'remove', $before, abs($delta));

        return $request->expectsJson()
            ? $this->payload($change, __('storefront::messages.cart_updated'))
            : redirect()->to(LocalizedUrls::route('storefront.cart'))->with(self::CHANGE_FLASH, $change);
    }

    public function remove(Request $request, int $line): RedirectResponse|JsonResponse
    {
        $key = $this->cart->key();
        abort_if($key === null, 404);

        $before = $this->line($line);
        $this->carts->removeLine($key, $line);
        $change = $before === null ? null : $this->presenter->change('remove', $before, $before->quantity);

        return $request->expectsJson()
            ? $this->payload($change, __('storefront::messages.cart_line_removed'))
            : redirect()->to(LocalizedUrls::route('storefront.cart'))->with(self::CHANGE_FLASH, $change);
    }

    /**
     * Áp mã giảm giá cho giỏ (giữ trong phiên, checkout điền sẵn); `code` rỗng = bỏ mã.
     */
    public function voucher(Request $request): RedirectResponse|JsonResponse
    {
        $data = $request->validate(['code' => ['nullable', 'string', 'max:64']]);
        $code = trim((string) ($data['code'] ?? ''));
        $key = $this->cart->current() === null ? null : $this->cart->key();

        if ($code === '' || $key === null) {
            $this->voucher->forget();
            $message = __('storefront::messages.voucher_removed');
        } else {
            $rejection = $this->voucher->apply($key, $code);
            if ($rejection !== null) {
                throw ValidationException::withMessages(['code' => $rejection]);
            }
            $message = __('storefront::messages.voucher_applied', ['code' => $code]);
        }

        return $request->expectsJson()
            ? $this->payload(null, $message)
            : redirect()->to(LocalizedUrls::route('storefront.cart'))->with('status', $message);
    }

    /**
     * Trạng thái giỏ cho JS: `cart` (như Storefront API), `count` (số món, cho nhãn ở header), `voucher` (mã đang giữ + mức
     * giảm), `html` (giỏ mini render bằng partial `theme::partials.mini-cart` — theme con override được), `change` (cho sự
     * kiện `vani:cart-updated`), `message`, `redirect` (Mua ngay → checkout). Trang giỏ gửi `X-Vani-Cart-View: page` (0.3.94)
     * → thêm `page_html` (partial `theme::partials.cart-page`).
     */
    private function payload(?array $change = null, ?string $message = null, ?string $redirect = null): JsonResponse
    {
        $view = $this->cart->current();
        $key = $view === null ? null : $this->cart->key();
        $cart = $view === null ? null : $this->presenter->present($view);
        $voucher = $key === null || $view->lines === [] ? null : $this->voucher->preview($key);

        return response()->json([
            'cart' => $cart,
            'count' => $view->itemCount ?? 0,
            'voucher' => $voucher,
            'html' => view('theme::partials.mini-cart', ['cart' => $cart, 'voucher' => $voucher])->render(),
            'page_html' => request()->header(self::VIEW_HEADER) === 'page' ? view('theme::partials.cart-page', ['cart' => $cart, 'voucher' => $voucher])->render() : null,
            'change' => $change,
            'message' => $message,
            'redirect' => $redirect,
        ])->header('Cache-Control', 'private, no-store');
    }

    private function line(int $lineId): ?CartLineView
    {
        return collect($this->cart->current()->lines ?? [])->firstWhere('id', $lineId);
    }
}
