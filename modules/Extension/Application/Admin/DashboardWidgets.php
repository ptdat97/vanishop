<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Admin;

use Illuminate\Support\Facades\Gate;
use Modules\Extension\Contracts\DashboardWidget;
use Modules\Extension\Contracts\Extensions;

/**
 * Widget trang Tổng quan (W6b): lọc theo quyền, render từng ô — ô lỗi bị bỏ (ghi log + circuit breaker của Extensions).
 */
final class DashboardWidgets
{
    public function __construct(private readonly Extensions $extensions) {}

    /**
     * @return list<array{key: string, label: string, width: int, plugin: string|null, data: array<string, mixed>}>
     */
    public function visible(): array
    {
        $widgets = array_filter(
            $this->extensions->implementations(DashboardWidget::TAG, DashboardWidget::class, fn (DashboardWidget $widget): string => $widget->key()),
            fn (DashboardWidget $widget): bool => $widget->permission() === null || Gate::allows($widget->permission()),
        );
        uasort($widgets, fn (DashboardWidget $a, DashboardWidget $b): int => [$a->order(), $a->key()] <=> [$b->order(), $b->key()]);

        $result = [];
        foreach ($widgets as $widget) {
            $data = $this->extensions->call($widget, fn () => $widget->render()->toArray(), null, 'dashboard_widget');
            if ($data === null) {
                continue;
            }

            $result[] = [
                'key' => $widget->key(), 'label' => $widget->label(), 'width' => max(1, min(3, $widget->width())),
                'plugin' => $this->extensions->ownerOf($widget), 'data' => $data,
            ];
        }

        return $result;
    }
}
