<?php

declare(strict_types=1);

namespace Modules\Integration\Application;

use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Integration\Persistence\Models\ClientKey;
use Modules\Integration\Persistence\Models\IntegrationClient;
use Modules\Integration\Persistence\Models\WebhookSubscription;

/**
 * Cấp/quản lý Integration Client, key HMAC (tối đa 2 key song song để xoay vòng) và webhook subscription.
 */
final class ClientProvisioning
{
    public const SCOPES = ['events:read', 'orders:read', 'orders:write', 'inventory:write'];

    public const MAX_ACTIVE_KEYS = 2;

    public function __construct(private readonly AuditLogger $audit) {}

    /**
     * Giá trị null/mảng rỗng = giữ nguyên (client đã có) hoặc mặc định (client mới).
     *
     * @param  array{name?: ?string, scopes?: list<string>, brand_ids?: list<int>, ip_allowlist?: list<string>, rate_limit?: int, status?: ?string}  $attributes
     */
    public function upsert(string $code, array $attributes): IntegrationClient
    {
        if (preg_match('/^[a-z0-9][a-z0-9_-]{1,63}$/', $code) !== 1 || $code === 'vanishop') {
            throw ValidationException::withMessages(['code' => 'Mã client chỉ gồm a-z, 0-9, "-", "_" và không được là "vanishop".']);
        }

        $unknown = array_diff($attributes['scopes'] ?? [], self::SCOPES);
        if ($unknown !== []) {
            throw ValidationException::withMessages(['scopes' => 'Scope không hợp lệ: '.implode(', ', $unknown)]);
        }

        $client = IntegrationClient::query()->firstOrNew(['code' => $code]);
        $client->fill(array_filter([
            'name' => $attributes['name'] ?? ($client->exists ? null : $code),
            'scopes' => ($attributes['scopes'] ?? []) !== [] ? array_values(array_unique($attributes['scopes'])) : ($client->exists ? null : []),
            'ip_allowlist' => ($attributes['ip_allowlist'] ?? []) !== [] ? $attributes['ip_allowlist'] : null,
            'rate_limit' => $attributes['rate_limit'] ?? null,
            'status' => $attributes['status'] ?? ($client->exists ? null : 'active'),
        ], fn (mixed $value): bool => $value !== null));
        if (($attributes['brand_ids'] ?? []) !== []) {
            $client->brand_ids = array_values(array_unique($attributes['brand_ids']));
        }

        $changes = $client->getDirty();
        $client->save();
        $this->audit->record($client->wasRecentlyCreated ? 'integration.client.created' : 'integration.client.updated', 'integration_client', $client->id, $changes);

        return $client;
    }

    /**
     * @return array{0: string, 1: string} [key_id, secret]
     */
    public function issueKey(IntegrationClient $client): array
    {
        $active = $client->keys()->get()->filter->isUsable()->count();
        if ($active >= self::MAX_ACTIVE_KEYS) {
            throw ValidationException::withMessages(['key' => 'Client đã có '.self::MAX_ACTIVE_KEYS.' key còn hiệu lực; thu hồi key cũ trước.']);
        }

        $keyId = 'vk_'.Str::lower(Str::random(24));
        $secret = Str::random(48);
        $client->keys()->create(['key_id' => $keyId, 'secret' => $secret]);
        $this->audit->record('integration.client.key_issued', 'integration_client', $client->id, ['key_id' => $keyId]);

        return [$keyId, $secret];
    }

    public function revokeKey(IntegrationClient $client, string $keyId): void
    {
        $key = $client->keys()->where('key_id', $keyId)->first();
        if (! $key instanceof ClientKey) {
            throw ValidationException::withMessages(['key' => "Không có key {$keyId}."]);
        }

        $key->update(['revoked_at' => now()]);
        $this->audit->record('integration.client.key_revoked', 'integration_client', $client->id, ['key_id' => $keyId]);
    }

    /**
     * @param  list<string>  $eventTypes
     * @return array{0: WebhookSubscription, 1: string} [subscription, signing secret]
     */
    public function subscribe(string $clientCode, string $url, array $eventTypes): array
    {
        $client = IntegrationClient::query()->where('code', $clientCode)->first()
            ?? throw ValidationException::withMessages(['client' => "Không có client {$clientCode}."]);

        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (filter_var($url, FILTER_VALIDATE_URL) === false || ! in_array($scheme, app()->isProduction() ? ['https'] : ['https', 'http'], true)) {
            throw ValidationException::withMessages(['url' => 'URL webhook không hợp lệ (production bắt buộc HTTPS).']);
        }

        $secret = Str::random(48);
        $subscription = $client->subscriptions()->create(['url' => $url, 'event_types' => array_values(array_unique($eventTypes)), 'secret' => $secret, 'status' => 'active']);
        $this->audit->record('integration.subscription.created', 'integration_webhook_subscription', $subscription->id, ['client' => $client->code, 'url' => $url, 'event_types' => $eventTypes]);

        return [$subscription, $secret];
    }
}
