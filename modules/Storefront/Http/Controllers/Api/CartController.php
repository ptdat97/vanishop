<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Cart\Contracts\Carts;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Cart\Contracts\Data\CartView;
use Modules\Promotion\Contracts\Data\VoucherRejection;
use Modules\Storefront\Application\CartPresenter;
use Modules\Storefront\Application\CartVoucher;
use Modules\Storefront\Http\Requests\CartLineRequest;
use Modules\Storefront\Http\Requests\CartQuantityRequest;
use Modules\Storefront\Http\Requests\CartSelectionRequest;
use Modules\Storefront\Http\Requests\CartVouchersRequest;

/**
 * Giỏ hàng khách vãng lai: định danh bằng id công khai + token bí mật trong header X-Vani-Cart-Token.
 */
final class CartController
{
    public const TOKEN_HEADER = 'X-Vani-Cart-Token';

    public function __construct(
        private readonly Carts $carts,
        private readonly CartPresenter $presenter,
    ) {}

    public function store(Request $request): JsonResponse
    {
        $created = $this->carts->create((string) config('vanishop.currency'));

        return response()->json([
            'data' => $this->presenter->present($created->view),
            // Chỉ trả một lần; client lưu lại và gửi kèm mọi request sau qua header X-Vani-Cart-Token.
            'meta' => ['token' => $created->key->token],
        ], 201);
    }

    public function show(Request $request, string $cart): JsonResponse
    {
        return $this->respond($this->carts->view($this->key($request, $cart)));
    }

    public function addLine(CartLineRequest $request, string $cart): JsonResponse
    {
        $key = $this->key($request, $cart);

        // Mua ngay: thêm rồi chỉ chọn riêng dòng này để thanh toán; các dòng khác ở lại giỏ.
        return $this->respond($request->boolean('buy_now')
            ? $this->carts->buyNow($key, $request->variantId(), $request->quantity(), $request->lineOptions())
            : $this->carts->addLine($key, $request->variantId(), $request->quantity(), $request->lineOptions()));
    }

    /**
     * Chọn dòng để thanh toán: `mode` = `only` (mặc định — đúng các dòng này), `select` (chọn thêm), `deselect` (bỏ chọn).
     */
    public function selection(CartSelectionRequest $request, string $cart): JsonResponse
    {
        $key = $this->key($request, $cart);

        return $this->respond(match ($request->mode()) {
            'select' => $this->carts->select($key, $request->lineIds()),
            'deselect' => $this->carts->deselect($key, $request->lineIds()),
            default => $this->carts->selectOnly($key, $request->lineIds()),
        });
    }

    /**
     * Đặt mã giảm giá của giỏ (ADR-044): thay toàn bộ danh sách, rỗng = bỏ mã. Chỉ lưu mã đang dùng được; mã bị từ chối
     * trả ở `meta.rejected_vouchers[{code, reason}]` (không phải lỗi). Quote và đặt hàng tính trên mã của giỏ.
     */
    public function vouchers(CartVouchersRequest $request, string $cart, CartVoucher $vouchers): JsonResponse
    {
        $result = $vouchers->applyCodes($this->key($request, $cart), $request->codes());

        return response()->json([
            'data' => $this->presenter->present($result['cart']),
            'meta' => ['rejected_vouchers' => array_map(fn (VoucherRejection $rejection): array => ['code' => $rejection->code, 'reason' => $rejection->reason], $result['rejected'])],
        ]);
    }

    public function updateLine(CartQuantityRequest $request, string $cart, int $line): JsonResponse
    {
        return $this->respond($this->carts->updateLine($this->key($request, $cart), $line, (int) $request->validated('quantity')));
    }

    public function removeLine(Request $request, string $cart, int $line): JsonResponse
    {
        return $this->respond($this->carts->removeLine($this->key($request, $cart), $line));
    }

    private function key(Request $request, string $cart): CartKey
    {
        return new CartKey($cart, (string) $request->headers->get(self::TOKEN_HEADER, ''));
    }

    private function respond(CartView $view): JsonResponse
    {
        return response()->json(['data' => $this->presenter->present($view)]);
    }
}
