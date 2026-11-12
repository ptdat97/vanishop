<?php

declare(strict_types=1);

namespace Modules\Extension\Testing;

use Illuminate\Routing\Route;
use Illuminate\Testing\TestResponse;
use PHPUnit\Framework\Assert;
use Tests\Support\JsonSchemaSubset;

/**
 * Hợp đồng Storefront API của plugin (ADR-044, plugin-system §10b, stabilization S0.4): plugin có `storefrontRoutes()`
 * mô tả các route `/api/storefront/v1/x/<slug>/…` trong `openapi.json` ở thư mục plugin (OpenAPI 3.1, schema theo tập
 * từ khoá của `Tests\Support\JsonSchemaSubset`, `$ref` tương đối với file đó). Lỗi luôn theo `error.v1` của Core.
 *
 * Trong test của plugin:
 *   StorefrontApiContract::assertRoutesDocumented($pluginDir);          // route ↔ openapi.json
 *   StorefrontApiContract::assertResponse($response, $pluginDir, '/items', 'GET');   // response thật ↔ schema
 */
final class StorefrontApiContract
{
    public const ERROR_SCHEMA = 'docs/api/schemas/integration/error.v1.json';

    /**
     * @return array<string, mixed>
     */
    public static function openApi(string $pluginDir): array
    {
        $file = rtrim($pluginDir, '/').'/openapi.json';
        Assert::assertFileExists($file, 'Plugin có storefrontRoutes() phải mô tả API trong openapi.json (plugin-system §10b)');

        return json_decode((string) file_get_contents($file), true, flags: JSON_THROW_ON_ERROR);
    }

    /**
     * Mọi route `api.storefront.x.<slug>.*` đang đăng ký có trong openapi.json (đúng method) và ngược lại.
     */
    public static function assertRoutesDocumented(string $pluginDir): void
    {
        $slug = self::slug($pluginDir);
        $prefix = "api/storefront/v1/x/{$slug}";
        $routes = collect(app('router')->getRoutes()->getRoutes())
            ->filter(fn (Route $route): bool => str_starts_with((string) $route->getName(), "api.storefront.x.{$slug}."))
            ->flatMap(fn (Route $route) => collect($route->methods())->reject(fn (string $method): bool => $method === 'HEAD')
                ->map(fn (string $method): string => $method.' '.(substr($route->uri(), strlen($prefix)) ?: '/')))
            ->sort()->values()->all();
        $documented = collect(self::openApi($pluginDir)['paths'])->flatMap(fn (array $operations, string $path) => collect(array_keys($operations))
            ->map(fn (string $method): string => strtoupper($method).' '.$path))->sort()->values()->all();

        Assert::assertNotEmpty($routes, "Không thấy route api.storefront.x.{$slug}.* — plugin đã được bật và đăng ký chưa?");
        Assert::assertSame($routes, $documented, "Route Storefront API của plugin {$slug} lệch openapi.json");
    }

    /**
     * Response thật khớp hợp đồng: 2xx theo schema mà openapi.json khai cho path + method + status; 4xx/5xx theo
     * `error.v1` của Core (và status đó phải được khai trong openapi.json).
     */
    public static function assertResponse(TestResponse $response, string $pluginDir, string $path, string $method): TestResponse
    {
        $spec = self::openApi($pluginDir);
        $status = $response->status();
        $declared = $spec['paths'][$path][strtolower($method)]['responses'][(string) $status] ?? null;
        Assert::assertNotNull($declared, "openapi.json thiếu {$method} {$path} → {$status}");

        $ref = $declared['content']['application/json']['schema']['$ref'] ?? null;
        $schema = $status >= 400 ? base_path(self::ERROR_SCHEMA) : ($ref === null ? null : rtrim($pluginDir, '/').'/'.$ref);
        if ($schema === null) {
            Assert::assertSame('', (string) $response->getContent(), "{$method} {$path} {$status}: openapi.json khai không có nội dung");

            return $response;
        }
        $errors = JsonSchemaSubset::validate($response->json(), $schema);
        Assert::assertSame([], $errors, "{$method} {$path} {$status}: ".implode('; ', $errors));

        return $response;
    }

    private static function slug(string $pluginDir): string
    {
        $manifest = json_decode((string) file_get_contents(rtrim($pluginDir, '/').'/vanishop.json'), true, flags: JSON_THROW_ON_ERROR);

        return str_replace('.', '-', (string) $manifest['id']);
    }
}
