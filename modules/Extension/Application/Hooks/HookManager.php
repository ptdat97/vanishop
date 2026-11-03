<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Hooks;

use Closure;
use Illuminate\Support\Facades\Log;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Domain\Hooks\HookDefinition;
use Modules\Extension\Domain\Hooks\HookNotDeclared;
use Modules\Extension\Domain\Hooks\HookNotPublic;
use Modules\Extension\Domain\Hooks\HookReturnTypeMismatch;
use Modules\Extension\Domain\Hooks\HookType;
use Throwable;
use TorMorten\Eventy\Events;

/**
 * Lớp bọc eventy: kiểm soát tên hook, gắn listener với plugin sở hữu (chỉ chạy khi plugin
 * được bật trong phạm vi hiện tại) và áp dụng quy tắc lỗi theo loại hook.
 *
 * @see docs/04-extension/extension-model.md
 */
final class HookManager
{
    /**
     * Priority mặc định cho mọi loại hook (filter, action, validate, slot). Số nhỏ chạy trước. 100 chừa chỗ hai phía:
     * cần chạy trước listener mặc định dùng < 100, cần chạy sau dùng > 100 (0.3.54; trước đó 10).
     */
    public const DEFAULT_PRIORITY = 100;

    /** Số tham số tối đa truyền cho listener (eventy yêu cầu khai báo trước). */
    private const MAX_ARGUMENTS = 16;

    /** @var array<string, list<string|null>> */
    private array $listenerOwners = [];

    /** @var array<string, array{hook: string, plugin: string, calls: int, total_ms: float, max_ms: float}> */
    private array $timings = [];

    /**
     * @param  Closure(): PluginActivation  $activation  lấy theo request/job hiện tại (PluginActivation là scoped)
     */
    public function __construct(
        private readonly Events $events,
        private readonly HookRegistry $registry,
        private readonly Closure $activation,
        private readonly bool $strict,
        private readonly float $slowMs = 50.0,
        /** Ghi log debug mỗi lần gọi hook (tên, loại, số listener) để dò luồng khi phát triển — `vanishop.hooks.trace`. */
        private readonly bool $trace = false,
    ) {}

    public function filter(string $name, mixed $value, mixed ...$args): mixed
    {
        $this->assertDeclared($name);
        $this->trace($name, 'filter');

        return $this->events->filter($name, $value, ...$args);
    }

    public function action(string $name, mixed ...$args): void
    {
        $this->assertDeclared($name);
        $this->trace($name, 'action');

        $this->events->action($name, ...$args);
    }

    /**
     * Hook kiểu validate: mỗi listener trả về danh sách lỗi; kết quả là tất cả lỗi gộp lại.
     *
     * @return list<mixed>
     */
    public function collect(string $name, mixed ...$args): array
    {
        $this->assertDeclared($name);
        $this->trace($name, 'validate');

        return array_values($this->events->filter($name, [], ...$args));
    }

    /**
     * Slot UI: lỗi của một listener chỉ bỏ qua listener đó, không làm hỏng trang.
     *
     * @return list<mixed>
     */
    public function slot(string $name, mixed ...$args): array
    {
        $this->assertDeclared($name);
        $this->trace($name, 'slot');

        return array_values($this->events->filter($name, [], ...$args));
    }

    public function onFilter(string $name, callable $callback, int $priority = self::DEFAULT_PRIORITY, ?string $pluginId = null): void
    {
        $this->listen($name, $pluginId, $priority, function (mixed $value, mixed ...$args) use ($name, $callback, $pluginId): mixed {
            if (! $this->isActive($pluginId)) {
                return $value;
            }

            try {
                $result = $callback($value, ...$args);
            } catch (Throwable $exception) {
                // Điểm đọc/hiển thị (on_error: skip): bỏ listener lỗi thay vì làm hỏng trang/API.
                if (! $this->skipsErrors($name)) {
                    throw $exception;
                }
                report($exception);
                Log::warning('Filter lỗi, bỏ qua listener.', ['hook' => $name, 'plugin' => $pluginId]);

                return $value;
            }
            if (self::sameType($value, $result)) {
                return $result;
            }

            // Filter phải trả về đúng kiểu đầu vào: strict (local/testing) → lỗi rõ ràng; production → bỏ kết quả sai.
            if ($this->strict) {
                throw new HookReturnTypeMismatch($name, $pluginId, get_debug_type($value), get_debug_type($result));
            }
            Log::warning('Filter trả sai kiểu, bỏ qua kết quả của listener.', ['hook' => $name, 'plugin' => $pluginId, 'expected' => get_debug_type($value), 'actual' => get_debug_type($result)]);

            return $value;
        });
    }

    public function onAction(string $name, callable $callback, int $priority = self::DEFAULT_PRIORITY, ?string $pluginId = null): void
    {
        $this->listen($name, $pluginId, $priority, function (mixed ...$args) use ($name, $callback, $pluginId): void {
            if (! $this->isActive($pluginId)) {
                return;
            }

            try {
                $callback(...$args);
            } catch (Throwable $exception) {
                // Điểm on_error: skip (vd. vani.event.*): listener lỗi không làm hỏng luồng đã commit.
                if (! $this->skipsErrors($name)) {
                    throw $exception;
                }
                report($exception);
                Log::warning('Action lỗi, bỏ qua listener.', ['hook' => $name, 'plugin' => $pluginId]);
            }
        });
    }

    /**
     * Listener cho hook validate: $callback trả về list lỗi.
     */
    public function onValidate(string $name, callable $callback, int $priority = self::DEFAULT_PRIORITY, ?string $pluginId = null): void
    {
        $this->listen($name, $pluginId, $priority, function (array $issues, mixed ...$args) use ($callback, $pluginId): array {
            return $this->isActive($pluginId) ? [...$issues, ...$callback(...$args)] : $issues;
        });
    }

    /**
     * Listener cho slot UI: $callback trả về một phần tử hiển thị (ví dụ mảng mô tả card).
     */
    public function onSlot(string $name, callable $callback, int $priority = self::DEFAULT_PRIORITY, ?string $pluginId = null): void
    {
        $this->listen($name, $pluginId, $priority, function (array $items, mixed ...$args) use ($name, $callback, $pluginId): array {
            if (! $this->isActive($pluginId)) {
                return $items;
            }

            try {
                return [...$items, $callback(...$args)];
            } catch (Throwable $exception) {
                Log::error('Hook slot lỗi, bỏ qua listener.', ['hook' => $name, 'plugin' => $pluginId, 'exception' => $exception]);

                return $items;
            }
        });
    }

    /**
     * Thời gian chạy listener trong request/job hiện tại (hook_duration_ms theo hook × plugin).
     *
     * @return list<array{hook: string, plugin: string, calls: int, total_ms: float, max_ms: float}>
     */
    public function timings(): array
    {
        return array_values($this->timings);
    }

    public function resetTimings(): void
    {
        $this->timings = [];
    }

    /**
     * Có listener cho hook không — điểm tự động dùng để bỏ qua việc chuẩn bị dữ liệu khi không ai nghe.
     */
    public function hasListeners(string $name): bool
    {
        return ($this->listenerOwners[$name] ?? []) !== [];
    }

    /**
     * @return array<string, list<string|null>> hook => danh sách plugin nghe (null = Core)
     */
    public function listenerOwners(): array
    {
        ksort($this->listenerOwners);

        return $this->listenerOwners;
    }

    private function trace(string $name, string $type): void
    {
        if ($this->trace) {
            Log::debug("Hook {$type}: {$name}", ['hook' => $name, 'type' => $type, 'listeners' => array_map(fn (?string $owner): string => $owner ?? 'core', $this->listenerOwners[$name] ?? [])]);
        }
    }

    private function listen(string $name, ?string $pluginId, int $priority, Closure $listener): void
    {
        $definition = $this->assertDeclared($name);

        if ($definition !== null && $pluginId !== null && ! $definition->public) {
            throw new HookNotPublic($name, $pluginId);
        }

        $this->listenerOwners[$name][] = $pluginId;

        $callable = new HookListener($pluginId, $this->timed($name, $pluginId, $listener));

        $type = $definition->type ?? HookType::Filter;
        if ($type === HookType::Action) {
            $this->events->addAction($name, $callable, $priority, self::MAX_ARGUMENTS);
        } else {
            $this->events->addFilter($name, $callable, $priority, self::MAX_ARGUMENTS);
        }
    }

    /**
     * Đo thời gian mỗi lần gọi listener; chậm hơn ngưỡng (mặc định 50 ms — giới hạn cho hook trong transaction)
     * thì ghi cảnh báo kèm plugin.
     */
    private function timed(string $name, ?string $pluginId, Closure $listener): Closure
    {
        return function (mixed ...$args) use ($name, $pluginId, $listener): mixed {
            $started = hrtime(true);
            try {
                return $listener(...$args);
            } finally {
                $ms = (hrtime(true) - $started) / 1_000_000;
                $key = $name.'|'.($pluginId ?? 'core');
                $entry = $this->timings[$key] ?? ['hook' => $name, 'plugin' => $pluginId ?? 'core', 'calls' => 0, 'total_ms' => 0.0, 'max_ms' => 0.0];
                $this->timings[$key] = ['hook' => $name, 'plugin' => $entry['plugin'], 'calls' => $entry['calls'] + 1, 'total_ms' => $entry['total_ms'] + $ms, 'max_ms' => max($entry['max_ms'], $ms)];

                if ($ms > $this->slowMs) {
                    Log::warning('Listener hook chậm.', ['hook' => $name, 'plugin' => $pluginId ?? 'core', 'hook_duration_ms' => round($ms, 1), 'threshold_ms' => $this->slowMs]);
                }
            }
        };
    }

    private static function sameType(mixed $input, mixed $output): bool
    {
        return match (true) {
            $input === null => true,
            is_object($input) => $output instanceof $input,
            default => get_debug_type($input) === get_debug_type($output),
        };
    }

    /**
     * Hook khai báo `on_error: skip` (điểm đọc/hiển thị, vani.event.*): lỗi listener bị bỏ qua thay vì làm hỏng luồng.
     */
    private function skipsErrors(string $name): bool
    {
        $definition = $this->registry->get($name);

        return $definition !== null && $definition->onError === 'skip';
    }

    private function isActive(?string $pluginId): bool
    {
        return $pluginId === null || ($this->activation)()->isActive($pluginId);
    }

    private function assertDeclared(string $name): ?HookDefinition
    {
        $definition = $this->registry->get($name);

        if ($definition === null && $this->strict) {
            throw new HookNotDeclared($name);
        }

        return $definition;
    }
}
