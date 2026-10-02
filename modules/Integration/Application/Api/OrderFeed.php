<?php

declare(strict_types=1);

namespace Modules\Integration\Application\Api;

use Carbon\CarbonImmutable;
use Illuminate\Validation\ValidationException;
use Modules\Integration\Application\CanonicalPayloads;
use Modules\Ordering\Contracts\Data\OrderData;
use Modules\Ordering\Contracts\OrderReader;

/**
 * GET /orders?updated_since=&cursor=: đơn thay đổi theo (updated_at, id).
 * `cursor` là chuỗi mờ do chính API trả về; ưu tiên hơn `updated_since`.
 */
final class OrderFeed
{
    public function __construct(
        private readonly OrderReader $orders,
        private readonly CanonicalPayloads $payloads,
    ) {}

    /**
     * @return array{data: list<array<string, mixed>>, next_cursor: ?string}
     */
    public function page(?string $updatedSince, ?string $cursor, int $limit): array
    {
        [$since, $afterId] = $cursor !== null && $cursor !== ''
            ? $this->decode($cursor)
            : [$updatedSince !== null && $updatedSince !== '' ? CarbonImmutable::parse($updatedSince) : null, null];

        $orders = $this->orders->changedSince($since?->setTimezone((string) config('app.timezone')), $afterId, $limit);
        $last = $orders === [] ? null : $orders[array_key_last($orders)];

        return [
            'data' => array_map(fn (OrderData $order): array => $this->payloads->order($order), $orders),
            'next_cursor' => $last === null || count($orders) < $limit ? null : $this->encode($last),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    public function show(string $number): ?array
    {
        $order = $this->orders->findByNumber($number);

        return $order === null ? null : $this->payloads->order($order);
    }

    private function encode(OrderData $order): string
    {
        return rtrim(strtr(base64_encode(CarbonImmutable::parse((string) $order->updatedAt)->setTimezone((string) config('app.timezone'))->format('Y-m-d H:i:s').'|'.$order->id), '+/', '-_'), '=');
    }

    /**
     * @return array{0: CarbonImmutable, 1: int}
     */
    private function decode(string $cursor): array
    {
        $decoded = base64_decode(strtr($cursor, '-_', '+/'), true);
        $parts = $decoded === false ? [] : explode('|', $decoded);
        if (count($parts) !== 2 || ! ctype_digit($parts[1])) {
            throw ValidationException::withMessages(['cursor' => 'Cursor không hợp lệ.']);
        }

        return [CarbonImmutable::parse($parts[0], (string) config('app.timezone')), (int) $parts[1]];
    }
}
