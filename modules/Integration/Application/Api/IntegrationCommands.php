<?php

declare(strict_types=1);

namespace Modules\Integration\Application\Api;

use Illuminate\Support\Facades\DB;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Integration\Contracts\ExternalReferences;
use Modules\Integration\Persistence\Models\IntegrationClient;
use Modules\Inventory\Contracts\InventorySync;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Shared\Application\IdempotencyStore;
use Throwable;

/**
 * Lệnh ghi của Integration API. Mọi thay đổi đi qua Service contract của context sở hữu (validation,
 * state machine, ledger, audit vẫn áp dụng) — Integration không ghi bảng của module khác.
 */
final class IntegrationCommands
{
    public const MAX_LEVELS = 500;

    public function __construct(
        private readonly OrderReader $orders,
        private readonly ExternalReferences $references,
        private readonly InventorySync $inventory,
        private readonly VariantDirectory $variants,
        private readonly AuditLogger $audit,
        private readonly IdempotencyStore $idempotency,
    ) {}

    /**
     * `acknowledgeOrder` với Idempotency-Key (ADR-014): cùng key + cùng body → trả lại phản hồi cũ.
     *
     * @return array{status: int, body: array<string, mixed>, replayed: bool}
     */
    public function acknowledgeOrderOnce(IntegrationClient $client, string $number, string $externalId, string $key): array
    {
        $scope = "integration:{$client->code}:acknowledgements";
        $replay = $this->idempotency->claim($scope, $key, hash('sha256', $number.'|'.$externalId));
        if ($replay !== null) {
            return ['status' => $replay->status, 'body' => $replay->body, 'replayed' => true];
        }

        try {
            $body = DB::transaction(function () use ($client, $number, $externalId, $scope, $key): array {
                $body = ['data' => $this->acknowledgeOrder($client, $number, $externalId)];
                $this->idempotency->complete($scope, $key, 201, $body);

                return $body;
            });
        } catch (Throwable $exception) {
            $this->idempotency->release($scope, $key);
            throw $exception;
        }

        return ['status' => 201, 'body' => $body, 'replayed' => false];
    }

    /**
     * Đối tác xác nhận đã nhận đơn và trả số chứng từ phía họ.
     *
     * @return array{order_number: string, system: string, external_id: string}
     *
     * @throws IntegrationRequestRejected
     */
    public function acknowledgeOrder(IntegrationClient $client, string $number, string $externalId): array
    {
        $order = $this->orders->findByNumber($number) ?? throw IntegrationRequestRejected::orderNotFound($number);

        $existing = $this->references->externalId($client->code, 'order', $order->number);
        if ($existing !== null && $existing !== $externalId) {
            throw IntegrationRequestRejected::referenceConflict($order->number, $existing);
        }

        $this->references->link($client->code, 'order', $order->number, $externalId);
        $this->audit->record('integration.order.acknowledged', 'order', $order->id, ['system' => $client->code, 'external_id' => $externalId]);

        return ['order_number' => $order->number, 'system' => $client->code, 'external_id' => $externalId];
    }

    /**
     * Tồn vật lý (số tuyệt đối + version) từ authority. Kiểm quyền authority cho MỌI location trước khi ghi
     * (một location không thuộc client → từ chối cả request, `403 not_data_owner`).
     *
     * @param  list<array{location_code: string, sku: string, on_hand: int, version: int}>  $levels
     * @return list<array{location_code: string, sku: string, status: string}>
     */
    public function syncInventoryLevels(IntegrationClient $client, array $levels): array
    {
        $this->inventory->assertAuthority($client->code, array_values(array_unique(array_column($levels, 'location_code'))));
        $variants = $this->variants->findBySkus(array_column($levels, 'sku'));

        $results = [];
        foreach ($levels as $level) {
            $variant = $variants[$level['sku']] ?? null;
            $status = $variant === null
                ? 'sku_not_found'
                : $this->inventory->syncOnHand($client->code, $level['location_code'], $variant->id, $level['on_hand'], $level['version'], "sync:{$client->code}")->value;

            $results[] = ['location_code' => $level['location_code'], 'sku' => $level['sku'], 'status' => $status === 'stale' ? 'stale_update' : $status];
        }

        return $results;
    }
}
