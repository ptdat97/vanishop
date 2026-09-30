<?php

declare(strict_types=1);

namespace Modules\Integration\Http\Controllers\Api;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Modules\Integration\Application\Api\EventFeed;
use Modules\Integration\Application\Api\IntegrationCommands;
use Modules\Integration\Application\Api\IntegrationRequestRejected;
use Modules\Integration\Application\Api\OrderFeed;
use Modules\Integration\Http\Middleware\AuthenticateIntegrationClient;
use Modules\Integration\Persistence\Models\IntegrationClient;

/**
 * /api/integration/v1 — xác thực HMAC theo client, scope theo route.
 *
 * @see docs/11-integration/integration-platform.md §4
 */
final class IntegrationApiController
{
    public function events(Request $request, EventFeed $feed): JsonResponse
    {
        $data = $request->validate([
            'after' => ['nullable', 'integer', 'min:0'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:500'],
            'type' => ['nullable', 'string', 'max:64'],
        ]);
        $page = $feed->page($this->client($request), (int) ($data['after'] ?? 0), (int) ($data['limit'] ?? 100), $data['type'] ?? null);

        return response()->json(['data' => $page['data'], 'meta' => ['next_cursor' => $page['next_cursor'], 'has_more' => $page['has_more']]]);
    }

    public function orders(Request $request, OrderFeed $feed): JsonResponse
    {
        $data = $request->validate([
            'updated_since' => ['nullable', 'date'],
            'cursor' => ['nullable', 'string', 'max:200'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);
        $page = $feed->page($data['updated_since'] ?? null, $data['cursor'] ?? null, (int) ($data['limit'] ?? 50));

        return response()->json(['data' => $page['data'], 'meta' => ['next_cursor' => $page['next_cursor']]]);
    }

    public function order(string $number, OrderFeed $feed): JsonResponse
    {
        $order = $feed->show($number) ?? throw IntegrationRequestRejected::orderNotFound($number);

        return response()->json(['data' => $order]);
    }

    public function acknowledge(string $number, Request $request, IntegrationCommands $commands): JsonResponse
    {
        $key = (string) $request->header('Idempotency-Key', '');
        abort_if(preg_match('/^[A-Za-z0-9_.:-]{8,128}$/', $key) !== 1, 400, 'Idempotency-Key bắt buộc (8–128 ký tự).');
        $data = $request->validate(['external_id' => ['required', 'string', 'max:128']]);

        $result = $commands->acknowledgeOrderOnce($this->client($request), $number, $data['external_id'], $key);

        return response()->json($result['body'], $result['status'], $result['replayed'] ? ['Idempotent-Replayed' => 'true'] : []);
    }

    public function inventoryLevels(Request $request, IntegrationCommands $commands): JsonResponse
    {
        $data = $request->validate([
            'levels' => ['required', 'array', 'min:1', 'max:'.IntegrationCommands::MAX_LEVELS],
            'levels.*.location_code' => ['required', 'string', 'max:64'],
            'levels.*.sku' => ['required', 'string', 'max:64'],
            'levels.*.on_hand' => ['required', 'integer', 'min:0'],
            'levels.*.version' => ['required', 'integer', 'min:1'],
        ]);

        $levels = array_map(fn (array $level): array => [
            'location_code' => (string) $level['location_code'], 'sku' => (string) $level['sku'],
            'on_hand' => (int) $level['on_hand'], 'version' => (int) $level['version'],
        ], $data['levels']);

        return response()->json(['data' => ['results' => $commands->syncInventoryLevels($this->client($request), $levels)]]);
    }

    private function client(Request $request): IntegrationClient
    {
        $client = $request->attributes->get(AuthenticateIntegrationClient::ATTRIBUTE);
        abort_unless($client instanceof IntegrationClient, 401);

        return $client;
    }
}
