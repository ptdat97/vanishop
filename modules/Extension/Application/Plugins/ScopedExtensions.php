<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Plugins;

use Closure;
use Illuminate\Contracts\Container\Container;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Modules\Extension\Contracts\Extensions;
use Modules\Extension\Contracts\Requirement;
use Throwable;

/**
 * Nguồn duy nhất biết một extension point có những implementation nào:
 * implementation mặc định của Core (`tag()`) + implementation do plugin đóng góp (`contribute()`).
 *
 * `tagged()` là điểm chặn duy nhất: implementation của plugin chỉ được trả về khi plugin đang bật.
 */
final class ScopedExtensions implements Extensions
{
    private const BREAKER_THRESHOLD = 5;

    private const BREAKER_COOLDOWN = 300;

    /** @var array<string, array<string, string|null>> tag => abstract => plugin id (null = Core) */
    private array $contributions = [];

    /**
     * Danh sách abstract CÓ HIỆU LỰC của `tagged()` theo tag — chỉ trong một request/job: gắn với instance
     * PluginActivation (scoped) + version của nó; đổi đăng ký thì xoá. Instance vẫn tạo qua container mỗi lần
     * (implementation `bind` đọc cấu hình lúc tạo).
     *
     * @var array<string, list<string>>
     */
    private array $memo = [];

    private ?string $memoOwner = null;

    /**
     * @param  Closure(): PluginActivation  $activation  PluginActivation là scoped theo request/job
     */
    /** @var array<string, array{requirement: Requirement, label: string}> */
    private array $requirements = [];

    /** @var array<string, string> kind => tag */
    private array $kindContracts = [];

    /** @var array<string, list<callable(object): (string|null)>> tag => kiểm tra */
    private array $disableGuards = [];

    /** @var array<string, callable(): (string|null)> mã => kiểm tra của doctor */
    private array $doctorChecks = [];

    public function __construct(
        private readonly Container $container,
        private readonly Closure $activation,
    ) {}

    public function tag(string|array $abstracts, string $tag): void
    {
        $this->memo = [];
        foreach ((array) $abstracts as $abstract) {
            $this->contributions[$tag][(string) $abstract] = null;
        }
    }

    public function contribute(string $tag, string $abstract, string $pluginId): void
    {
        $this->memo = [];
        $this->contributions[$tag][$abstract] = $pluginId;
    }

    public function tagged(string $tag): array
    {
        $activation = ($this->activation)();
        $owner = spl_object_id($activation).':'.$activation->version();
        if ($owner !== $this->memoOwner) {
            $this->memo = [];
            $this->memoOwner = $owner;
        }

        $key = $tag;

        if (! isset($this->memo[$key])) {
            $active = [];
            foreach ($this->contributions[$tag] ?? [] as $abstract => $pluginId) {
                if ($pluginId === null || $activation->isActive($pluginId)) {
                    $active[] = $abstract;
                }
            }
            $this->memo[$key] = $active;
        }

        return array_map(fn (string $abstract): object => $this->container->make($abstract), $this->memo[$key]);
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

    public function call(object $implementation, callable $call, mixed $fallback, string $operation): mixed
    {
        $plugin = $this->ownerOf($implementation);
        $breaker = $plugin === null ? null : "vani:plugin-breaker:{$plugin}";
        if ($breaker !== null && Cache::has("{$breaker}:open")) {
            return $fallback;
        }

        try {
            return $call();
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Extension lỗi trên luồng tuỳ chọn — dùng giá trị dự phòng.', [
                'plugin' => $plugin ?? 'core', 'implementation' => $implementation::class, 'operation' => $operation, 'error' => $exception->getMessage(),
            ]);

            if ($breaker !== null) {
                Cache::add("{$breaker}:failures", 0, now()->addMinute());
                if (Cache::increment("{$breaker}:failures") >= self::BREAKER_THRESHOLD) {
                    Cache::put("{$breaker}:open", true, now()->addSeconds(self::BREAKER_COOLDOWN));
                    Cache::forget("{$breaker}:failures");
                    Log::error('Plugin lỗi liên tục — tạm bỏ qua trên luồng tuỳ chọn.', ['plugin' => $plugin, 'cooldown_seconds' => self::BREAKER_COOLDOWN]);
                }
            }

            return $fallback;
        }
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

    public function requires(string $tag, Requirement $requirement, string $label): void
    {
        $this->requirements[$tag] = ['requirement' => $requirement, 'label' => $label];
    }

    public function requirements(): array
    {
        return $this->requirements;
    }

    public function providers(string $tag): array
    {
        return array_values($this->contributions[$tag] ?? []);
    }

    public function acceptsNewTransactions(object $implementation): bool
    {
        $owner = $this->ownerOf($implementation);

        return $owner === null || ! ($this->activation)()->isDraining($owner);
    }

    public function guardDisable(string $tag, callable $check): void
    {
        $this->disableGuards[$tag][] = $check;
    }

    public function disableBlockers(string $pluginId): array
    {
        $reasons = [];
        foreach ($this->disableGuards as $tag => $checks) {
            foreach ($this->contributions[$tag] ?? [] as $abstract => $owner) {
                if ($owner !== $pluginId) {
                    continue;
                }
                $implementation = $this->container->make($abstract);
                foreach ($checks as $check) {
                    $reason = $check($implementation);
                    if (is_string($reason) && $reason !== '') {
                        $reasons[] = $reason;
                    }
                }
            }
        }

        return $reasons;
    }

    public function kindContract(string $kind, string $tag): void
    {
        $this->kindContracts[$kind] = $tag;
    }

    public function kindContracts(): array
    {
        return $this->kindContracts;
    }

    public function doctorCheck(string $code, callable $check): void
    {
        if (preg_match('/^[a-z][a-z0-9_]{0,63}$/', $code) !== 1) {
            throw new \InvalidArgumentException("Mã kiểm tra doctor [{$code}] không hợp lệ.");
        }

        $this->doctorChecks[$code] = $check;
    }

    public function doctorChecks(): array
    {
        return $this->doctorChecks;
    }
}
