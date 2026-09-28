<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Hooks;

use Closure;
use Illuminate\Support\Facades\Log;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Domain\Hooks\HookDefinition;
use Modules\Extension\Domain\Hooks\HookNotDeclared;
use Modules\Extension\Domain\Hooks\HookNotPublic;
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
    /** Số tham số tối đa truyền cho listener (eventy yêu cầu khai báo trước). */
    private const MAX_ARGUMENTS = 16;

    /** @var array<string, list<string|null>> */
    private array $listenerOwners = [];

    /**
     * @param  Closure(): PluginActivation  $activation  lấy theo request/job hiện tại (PluginActivation là scoped)
     */
    public function __construct(
        private readonly Events $events,
        private readonly HookRegistry $registry,
        private readonly Closure $activation,
        private readonly bool $strict,
    ) {}

    public function filter(string $name, mixed $value, mixed ...$args): mixed
    {
        $this->assertDeclared($name);

        return $this->events->filter($name, $value, ...$args);
    }

    public function action(string $name, mixed ...$args): void
    {
        $this->assertDeclared($name);

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

        return array_values($this->events->filter($name, [], ...$args));
    }

    public function onFilter(string $name, callable $callback, int $priority = 10, ?string $pluginId = null): void
    {
        $this->listen($name, $pluginId, $priority, function (mixed $value, mixed ...$args) use ($callback, $pluginId): mixed {
            return $this->isActive($pluginId) ? $callback($value, ...$args) : $value;
        });
    }

    public function onAction(string $name, callable $callback, int $priority = 10, ?string $pluginId = null): void
    {
        $this->listen($name, $pluginId, $priority, function (mixed ...$args) use ($callback, $pluginId): void {
            if ($this->isActive($pluginId)) {
                $callback(...$args);
            }
        });
    }

    /**
     * Listener cho hook validate: $callback trả về list lỗi.
     */
    public function onValidate(string $name, callable $callback, int $priority = 10, ?string $pluginId = null): void
    {
        $this->listen($name, $pluginId, $priority, function (array $issues, mixed ...$args) use ($callback, $pluginId): array {
            return $this->isActive($pluginId) ? [...$issues, ...$callback(...$args)] : $issues;
        });
    }

    /**
     * Listener cho slot UI: $callback trả về một phần tử hiển thị (ví dụ mảng mô tả card).
     */
    public function onSlot(string $name, callable $callback, int $priority = 10, ?string $pluginId = null): void
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
     * @return array<string, list<string|null>> hook => danh sách plugin nghe (null = Core)
     */
    public function listenerOwners(): array
    {
        ksort($this->listenerOwners);

        return $this->listenerOwners;
    }

    private function listen(string $name, ?string $pluginId, int $priority, Closure $listener): void
    {
        $definition = $this->assertDeclared($name);

        if ($definition !== null && $pluginId !== null && ! $definition->public) {
            throw new HookNotPublic($name, $pluginId);
        }

        $this->listenerOwners[$name][] = $pluginId;

        $callable = new HookListener($pluginId, $listener);

        $type = $definition->type ?? HookType::Filter;
        if ($type === HookType::Action) {
            $this->events->addAction($name, $callable, $priority, self::MAX_ARGUMENTS);
        } else {
            $this->events->addFilter($name, $callable, $priority, self::MAX_ARGUMENTS);
        }
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
