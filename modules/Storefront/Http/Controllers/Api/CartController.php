<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Cart\Contracts\Carts;
use Modules\Cart\Contracts\Data\CartKey;
use Modules\Cart\Contracts\Data\CartView;
use Modules\Storefront\Application\CartPresenter;
use Modules\Storefront\Http\Requests\LineOptions;

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

    public function addLine(Request $request, string $cart): JsonResponse
    {
        $data = $request->validate([
            'variant_id' => ['required', 'integer', 'min:1'],
            'quantity' => ['required', 'integer', 'min:1', 'max:1000'],
            'buy_now' => ['sometimes', 'boolean'],
        ]);
        $key = $this->key($request, $cart);
        $options = LineOptions::from($request->input('options'));

        // Mua ngay: thêm rồi chỉ chọn riêng dòng này để thanh toán; các dòng khác ở lại giỏ.
        return $this->respond($request->boolean('buy_now')
            ? $this->carts->buyNow($key, (int) $data['variant_id'], (int) $data['quantity'], $options)
            : $this->carts->addLine($key, (int) $data['variant_id'], (int) $data['quantity'], $options));
    }

    /**
     * Chọn dòng để thanh toán: `mode` = `only` (mặc định — đúng các dòng này), `select` (chọn thêm), `deselect` (bỏ chọn).
     */
    public function selection(Request $request, string $cart): JsonResponse
    {
        $data = $request->validate([
            'mode' => ['sometimes', 'string', 'in:only,select,deselect'],
            'line_ids' => ['present', 'array', 'max:100'],
            'line_ids.*' => ['integer', 'min:1'],
        ]);
        $key = $this->key($request, $cart);
        $lineIds = array_values(array_map('intval', $data['line_ids']));

        return $this->respond(match ($data['mode'] ?? 'only') {
            'select' => $this->carts->select($key, $lineIds),
            'deselect' => $this->carts->deselect($key, $lineIds),
            default => $this->carts->selectOnly($key, $lineIds),
        });
    }

    public function updateLine(Request $request, string $cart, int $line): JsonResponse
    {
        $data = $request->validate(['quantity' => ['required', 'integer', 'min:0', 'max:1000']]);

        return $this->respond($this->carts->updateLine($this->key($request, $cart), $line, (int) $data['quantity']));
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
