<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Hooks\Points;

use Illuminate\Support\Str;
use Modules\Extension\Application\Hooks\HookManager;

/**
 * Điểm tự động cho MỌI domain event của Core: action `vani.event.<module>.<event>` — vd. `Modules\Ordering\Events\OrderPlaced`
 * → `vani.event.ordering.order_placed`, tham số là chính đối tượng event. Event Core phát sau commit nên listener chạy
 * ngoài transaction; việc chậm/gọi mạng nên đẩy vào queue. Chỉ khi có listener; lỗi listener bị bỏ qua (ADR-036).
 */
final class EventHooks
{
    public const PATTERN = 'Modules\\*\\Events\\*';

    public function __construct(private readonly HookManager $hooks) {}

    /**
     * @param  array<int, mixed>  $payload
     */
    public function handle(string $event, array $payload): void
    {
        $hook = self::hookName($event);
        if ($hook === null || ! $this->hooks->hasListeners($hook) || ! is_object($payload[0] ?? null)) {
            return;
        }

        $this->hooks->action($hook, $payload[0]);
    }

    public static function hookName(string $event): ?string
    {
        if (preg_match('/^Modules\\\\([A-Za-z]+)\\\\Events\\\\([A-Za-z]+)$/', $event, $matches) !== 1) {
            return null;
        }

        return 'vani.event.'.Str::snake($matches[1]).'.'.Str::snake($matches[2]);
    }
}
