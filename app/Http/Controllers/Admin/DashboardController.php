<?php

declare(strict_types=1);

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Extension\Application\Admin\DashboardWidgets;
use Modules\Extension\Facades\Hook;

/**
 * Trang tổng quan Admin (khung ứng dụng). Plugin đóng góp widget có kiểu (DashboardWidget, W6b, vd. `vani.reports`);
 * slot vani.admin.dashboard.cards (card chữ) vẫn giữ.
 */
final class DashboardController
{
    public function __invoke(DashboardWidgets $widgets): Response
    {
        Gate::authorize('admin.access');

        return Inertia::render('Dashboard', [
            'widgets' => $widgets->visible(),
            'cards' => Hook::slot('vani.admin.dashboard.cards'),
            'tasks' => $this->tasks(),
        ]);
    }

    /**
     * "Việc cần làm" (0.3.101): ô hàng đợi do các module góp qua slot vani.admin.dashboard.tasks, theo `order`.
     *
     * @return list<array{key: string, label: string, count: int, url: string, tone: string, hint: string|null, order: int}>
     */
    private function tasks(): array
    {
        $tasks = [];
        foreach (array_filter(Hook::slot('vani.admin.dashboard.tasks'), 'is_array') as $contributed) {
            foreach ($contributed as $task) {
                $tasks[] = [
                    'key' => (string) $task['key'], 'label' => (string) $task['label'], 'count' => (int) $task['count'], 'url' => (string) $task['url'],
                    'tone' => (string) ($task['tone'] ?? 'warning'), 'hint' => $task['hint'] ?? null, 'order' => (int) ($task['order'] ?? 100),
                ];
            }
        }
        usort($tasks, fn (array $a, array $b): int => [$a['order'], $a['key']] <=> [$b['order'], $b['key']]);

        return $tasks;
    }
}
