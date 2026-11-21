<?php

declare(strict_types=1);

namespace Modules\Extension\Console;

use Illuminate\Console\Command;
use Modules\Extension\Application\Hooks\HookManager;
use Modules\Extension\Application\Hooks\HookRegistry;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Contracts\Data\FlowStep;
use Modules\Extension\Contracts\Flows;

/**
 * Các bước của luồng nghiệp vụ theo thứ tự chạy thật, kèm plugin chen vào và ranh giới transaction. Báo lỗi (exit 1)
 * khi bước hook nằm trong transaction mà hooks.php không khai báo `transaction: true`.
 */
final class FlowsCommand extends Command
{
    protected $signature = 'vani:flows {flow? : Mã luồng, vd. checkout.place_order}';

    protected $description = 'Liệt kê các bước của luồng nghiệp vụ (tính tổng, đặt hàng…): thứ tự, plugin chen vào, trong/ngoài transaction';

    public function handle(Flows $flows, HookManager $hooks, HookRegistry $registry, PluginActivation $activation): int
    {
        $all = $flows->all();
        $selected = $this->argument('flow');
        if ($selected !== null && ! isset($all[$selected])) {
            $this->error("Không có luồng [{$selected}]. Có: ".implode(', ', array_keys($all)));

            return self::FAILURE;
        }

        $problems = [];
        foreach ($selected === null ? $all : [$selected => $all[$selected]] as $code => $flow) {
            $this->line('');
            $this->info("{$code} — ".__($flow['label']));
            $rows = [];
            foreach (($flow['steps'])() as $index => $step) {
                $rows[] = [$index + 1, $this->stepName($step), $this->source($step, $hooks, $activation), $step->transaction ? 'có' : '-'];
                if ($step->hook !== null && $step->transaction && ! ($registry->get($step->hook)->transaction ?? false)) {
                    $problems[] = "{$code}: hook {$step->hook} chạy trong transaction nhưng hooks.php chưa khai báo 'transaction' => true";
                }
            }
            $this->table(['#', 'Bước', 'Core / plugin (priority)', 'Trong transaction'], $rows);
        }

        foreach ($problems as $problem) {
            $this->error($problem);
        }

        return $problems === [] ? self::SUCCESS : self::FAILURE;
    }

    private function stepName(FlowStep $step): string
    {
        return match (true) {
            $step->hook !== null => "hook {$step->hook}",
            $step->owner !== null || $step->priority !== null => $step->name,
            default => __($step->name),
        };
    }

    private function source(FlowStep $step, HookManager $hooks, PluginActivation $activation): string
    {
        if ($step->hook === null) {
            return ($step->owner ?? 'core').($step->priority === null ? '' : " ({$step->priority})");
        }

        $listeners = array_map(
            fn (array $listener): string => ($listener['plugin'] ?? 'core')." ({$listener['priority']})".($listener['plugin'] !== null && ! $activation->isActive($listener['plugin']) ? ' — đang tắt' : ''),
            $hooks->listeners($step->hook),
        );

        return $listeners === [] ? '(chưa có listener)' : implode(', ', $listeners);
    }
}
