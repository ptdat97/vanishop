<?php

declare(strict_types=1);

namespace Modules\Integration\Tests\Feature;

use Illuminate\Testing\TestResponse;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Integration\Application\ClientProvisioning;
use Modules\Integration\Contracts\Data\IntegrationEvent;
use Modules\Integration\Contracts\IntegrationEvents;
use Modules\Integration\Domain\HmacSignature;
use Modules\Integration\Persistence\Models\IntegrationClient;
use Modules\Integration\Persistence\Models\WebhookSubscription;
use Tests\TestCase;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

final class IntegrationTestHelpers
{
    /**
     * @param  list<string>  $scopes
     * @return array{client: IntegrationClient, key: string, secret: string}
     */
    public static function client(string $code = 'erp-main', array $scopes = ClientProvisioning::SCOPES, array $ips = []): array
    {
        return T::seed(function () use ($code, $scopes, $ips): array {
            $provisioning = app(ClientProvisioning::class);
            $client = $provisioning->upsert($code, ['scopes' => $scopes, 'ip_allowlist' => $ips]);
            [$key, $secret] = $provisioning->issueKey($client);

            return ['client' => $client, 'key' => $key, 'secret' => $secret];
        });
    }

    /**
     * @param  list<string>  $events
     * @return array{0: WebhookSubscription, 1: string}
     */
    public static function subscription(string $clientCode, array $events = ['*'], string $url = 'https://partner.example/hooks'): array
    {
        return T::seed(fn (): array => app(ClientProvisioning::class)->subscribe($clientCode, $url, $events));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function publish(string $type, string $aggregateId, array $data = []): string
    {
        return T::seed(fn (): string => app(IntegrationEvents::class)->publish(new IntegrationEvent($type, 'order', $aggregateId, $data)));
    }

    /**
     * Gọi Integration API có ký HMAC.
     *
     * @param  array{key: string, secret: string}  $credentials
     * @param  array<string, mixed>|null  $body
     * @param  array<string, string>  $headers
     */
    public static function call(TestCase $test, array $credentials, string $method, string $uri, ?array $body = null, array $headers = [], ?int $timestamp = null): TestResponse
    {
        $content = $body === null ? '' : (string) json_encode($body);
        $signature = HmacSignature::header($credentials['secret'], HmacSignature::requestPayload($method, $uri, $content), $timestamp ?? now()->getTimestamp());

        return $test->call($method, $uri, [], [], [], self::server([
            'X-Vani-Key-Id' => $credentials['key'],
            'X-Vani-Signature' => $signature,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
            ...$headers,
        ]), $content);
    }

    /**
     * @param  array<string, string>  $headers
     * @return array<string, string>
     */
    private static function server(array $headers): array
    {
        $server = [];
        foreach ($headers as $name => $value) {
            $key = strtoupper(str_replace('-', '_', $name));
            $server[in_array($key, ['CONTENT_TYPE', 'CONTENT_LENGTH'], true) ? $key : 'HTTP_'.$key] = $value;
        }

        return $server;
    }
}
