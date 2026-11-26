<?php

declare(strict_types=1);

namespace Modules\Storefront\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Modules\Cart\Contracts\Carts;
use Modules\Customer\Contracts\CustomerSessions;
use Modules\Ordering\Contracts\CustomerOrders;
use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Shared\Context\ActorType;
use Modules\Shared\Context\CurrentContext;
use Modules\Storefront\Application\CartPresenter;
use Modules\Storefront\Application\MemberPrices;
use Modules\Storefront\Application\OrderPresenter;
use Modules\Storefront\Application\PriceVisibility;
use Modules\Storefront\Application\ProductViews;
use Modules\Storefront\Http\Requests\CancelOrderRequest;

/**
 * Đơn và giỏ của khách đã đăng nhập (route có middleware `vani.customer`).
 */
final class AccountOrderController
{
    public function __construct(
        private readonly CustomerOrders $orders,
        private readonly OrderPresenter $presenter,
        private readonly CurrentContext $context,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate(['page' => ['nullable', 'integer', 'min:1'], 'per_page' => ['nullable', 'integer', 'min:1', 'max:50']]);
        $perPage = (int) ($data['per_page'] ?? 10);
        $page = (int) ($data['page'] ?? 1);
        $result = $this->orders->ofCustomer($this->customerId(), $page, $perPage);

        return response()->json([
            'data' => array_map(fn (OrderDetail $order): array => $this->presenter->present($order), $result['data']),
            'meta' => ['page' => $page, 'per_page' => $perPage, 'total' => $result['total']],
        ]);
    }

    public function show(string $order): JsonResponse
    {
        $detail = $this->orders->showForCustomer($this->customerId(), $order);
        abort_if($detail === null, 404, __('ordering::messages.not_found'));

        return response()->json(['data' => $this->presenter->present($detail)]);
    }

    public function cancel(CancelOrderRequest $request, string $order): JsonResponse
    {
        $data = $request->validated();

        return response()->json(['data' => $this->presenter->present(
            $this->orders->cancelForCustomer($this->customerId(), $order, $data['reason'] ?? '', $data['reason_code'] ?? null),
        )]);
    }

    /**
     * Giỏ đang mở của khách trên kênh (tạo nếu chưa có); dùng id này với API giỏ, không cần token giỏ.
     */
    public function cart(Request $request, Carts $carts, CartPresenter $cartPresenter): JsonResponse
    {
        return response()->json(['data' => $cartPresenter->present($carts->forCustomer($this->customerId(), (string) config('vanishop.currency')))]);
    }

    private function customerId(): int
    {
        $actor = $this->context->actor();
        abort_unless($actor->type === ActorType::Customer && $actor->id !== null, 401);

        return $actor->id;
    }

    /**
     * Giá thành viên của khách (Phase 8): `?variant_ids[]=…`, chỉ variant có giá theo nhóm thấp hơn giá chung.
     */
    /**
     * Giá riêng của khách đăng nhập (JS của theme gọi bằng phiên cùng nguồn — ADR-044; thay `/_vani/gia`):
     * `?variant_ids[]=` → giá thành viên theo variant (chỉ khi nhóm khách có giá thấp hơn); `?product_ids[]=` → khoảng giá chung
     * theo sản phẩm khi cửa hàng ẩn giá với khách chưa đăng nhập (trang cache CDN luôn là bản ẩn giá). Không cache.
     */
    public function prices(Request $request, MemberPrices $prices, PriceVisibility $visibility, ProductViews $products): JsonResponse
    {
        $data = $request->validate([
            'variant_ids' => ['nullable', 'array', 'max:200'], 'variant_ids.*' => ['integer', 'min:1'],
            'product_ids' => ['nullable', 'array', 'max:100'], 'product_ids.*' => ['integer', 'min:1'],
        ]);
        $customer = $request->attributes->get(CustomerSessions::REQUEST_ATTRIBUTE);
        $productIds = array_values(array_map('intval', $data['product_ids'] ?? []));

        $memberPrices = $prices->for((int) $customer->id, array_values(array_map('intval', $data['variant_ids'] ?? [])));

        // Map theo id luôn là object JSON, kể cả khi rỗng (`{}`, không phải `[]`).
        return response()->json(['data' => [
            ...$memberPrices,
            'prices' => (object) $memberPrices['prices'],
            'products' => (object) ($visibility->membersOnly() && $productIds !== [] ? $products->priceRanges($productIds, App::getLocale()) : []),
        ]])->header('Cache-Control', 'private, no-store');
    }
}
