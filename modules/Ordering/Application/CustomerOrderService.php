<?php

declare(strict_types=1);

namespace Modules\Ordering\Application;

use Modules\Ordering\Contracts\CustomerOrders;
use Modules\Ordering\Contracts\Data\OrderDetail;
use Modules\Ordering\Contracts\OrderActionRejected;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Shared\Support\Phones;

final class CustomerOrderService implements CustomerOrders
{
    public function __construct(
        private readonly OrderQueries $queries,
        private readonly OrderCommands $commands,
    ) {}

    public function track(string $number, string $phone): ?OrderDetail
    {
        $e164 = Phones::parse($phone)?->e164;
        if ($e164 === null) {
            return null;
        }

        $order = Order::query()->with(['lines', 'adjustments'])->where('number', strtoupper(trim($number)))->where('customer_phone', $e164)->first();

        return $order === null ? null : $this->queries->detail($order, masked: true);
    }

    public function show(string $publicId, string $accessToken): ?OrderDetail
    {
        $order = $this->authorized($publicId, $accessToken);

        return $order === null ? null : $this->queries->detail($order);
    }

    public function cancel(string $publicId, string $accessToken, string $reason): OrderDetail
    {
        $order = $this->authorized($publicId, $accessToken) ?? throw OrderActionRejected::notFound();
        $this->commands->cancel($order->id, "customer:{$reason}", 'customer');

        return $this->queries->detail($order->fresh(['lines', 'adjustments']));
    }

    public function ofCustomer(int $customerId, int $page = 1, int $perPage = 10): array
    {
        $paginator = Order::query()->with(['lines', 'adjustments'])->where('customer_id', $customerId)
            ->orderByDesc('placed_at')->orderByDesc('id')->paginate($perPage, page: max(1, $page));

        return [
            'data' => array_map(fn (Order $order): OrderDetail => $this->queries->detail($order), $paginator->items()),
            'total' => $paginator->total(),
        ];
    }

    public function showForCustomer(int $customerId, string $publicId): ?OrderDetail
    {
        $order = $this->owned($customerId, $publicId);

        return $order === null ? null : $this->queries->detail($order);
    }

    public function cancelForCustomer(int $customerId, string $publicId, string $reason): OrderDetail
    {
        $order = $this->owned($customerId, $publicId) ?? throw OrderActionRejected::notFound();
        $this->commands->cancel($order->id, "customer:{$reason}", 'customer');

        return $this->queries->detail($order->fresh(['lines', 'adjustments']));
    }

    private function owned(int $customerId, string $publicId): ?Order
    {
        return Order::query()->with(['lines', 'adjustments'])->where('public_id', $publicId)->where('customer_id', $customerId)->first();
    }

    private function authorized(string $publicId, string $accessToken): ?Order
    {
        $order = Order::query()->with(['lines', 'adjustments'])->where('public_id', $publicId)->first();

        return $order !== null && $order->access_token_hash !== null && hash_equals($order->access_token_hash, hash('sha256', $accessToken)) ? $order : null;
    }
}
