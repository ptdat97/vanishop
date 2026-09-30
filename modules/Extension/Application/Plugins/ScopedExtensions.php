<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Log;
use Modules\Extension\Contracts\Extensions;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

/**
 * Nguồn duy nhất biết một extension point có những implementation nào:
 * implementation mặc định của Core (`tag()`) + implementation do plugin đóng góp (`contribute()`).
 *
 * `tagged()` là điểm chặn duy nhất: implementation của plugin chỉ được trả về khi plugin đang bật
 * trong phạm vi hiện tại, nên bật plugin cho brand này không ảnh hưởng brand khác.
 */
final class ScopedExtensions implements Extensions
{
    /** @var array<string, array<string, string|null>> tag => abstract => plugin id (null = Core) */
    private array $contributions = [];

    /**
     * @param  Closure(): PluginActivation  $activation  PluginActivation là scoped theo request/job
     */
    public function __construct(
        private readonly Container $container,
        private readonly Closure $activation,
    ) {}

    public function tag(string|array $abstracts, string $tag): void
    {
        foreach ((array) $abstracts as $abstract) {
            $this->contributions[$tag][(string) $abstract] = null;
        }
    }

    public function contribute(string $tag, string $abstract, string $pluginId): void
    {
        $this->contributions[$tag][$abstract] = $pluginId;
    }

    public function tagged(string $tag): array
    {
        $activation = ($this->activation)();
        $result = [];

        foreach ($this->contributions[$tag] ?? [] as $abstract => $pluginId) {
            if ($pluginId === null || $activation->isActive($pluginId)) {
                $result[] = $this->container->make($abstract);
            }
        }

        return $result;
    }

    public function implementations(string $tag, string $interface, ?callable $key = null): array
    {
        $result = [];
        foreach ($this->tagged($tag) as $implementation) {
            if ($implementation instanceof $interface) {
                $result[(string) ($key === null ? $implementation->code() : $key($implementation))] ??= $implementation;
            }
        }

        return $result;
    }

    public function forBrand(?int $brandId, string $tag, string $interface, ?callable $key = null): array
    {
        $context = $this->container->make(CurrentContext::class);
        $scope = ContextScope::system("extensions {$tag}");
        if ($brandId !== null) {
            $scope = new ContextScope($scope->actor, brandIds: [$brandId]);
        }

        return $context->runAs($scope, fn (): array => $this->implementations($tag, $interface, $key));
    }

    public function select(string $tag, string $code, ?string $fallbackCode = null): ?object
    {
        $byCode = [];
        foreach ($this->tagged($tag) as $implementation) {
            if (method_exists($implementation, 'code')) {
                $byCode[(string) $implementation->code()] ??= $implementation;
            }
        }

        if (isset($byCode[$code])) {
            return $byCode[$code];
        }
        if ($fallbackCode !== null && isset($byCode[$fallbackCode])) {
            Log::warning('Implementation được cấu hình không có hiệu lực, dùng mặc định.', ['tag' => $tag, 'configured' => $code, 'fallback' => $fallbackCode]);

            return $byCode[$fallbackCode];
        }

        return null;
    }

    public function ownerOf(object $implementation): ?string
    {
        foreach ($this->contributions as $abstracts) {
            foreach ($abstracts as $abstract => $pluginId) {
                if ($pluginId !== null && $implementation instanceof $abstract) {
                    return $pluginId;
                }
            }
        }

        return null;
    }
}
