<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers\Admin;

use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Inventory\Application\ReconciliationQueries;
use Modules\Inventory\Persistence\Models\InventoryReconciliation;

/**
 * Màn hình "Đối soát tồn kho" — chỉ đọc, dữ liệu do `vani:inventory:verify`
 * (nội bộ) và `vani:warehouse:reconcile` (nguồn ngoài, plugin vani.warehouse) ghi.
 */
final class ReconciliationController
{
    public function index(ReconciliationQueries $queries): Response
    {
        Gate::authorize('inventory.view');

        return Inertia::render('Inventory::Reconciliations/Index', [
            'baseUrl' => route('admin.inventory.reconciliations.index'),
            'runs' => $queries->recent(),
        ]);
    }

    public function show(InventoryReconciliation $reconciliation, ReconciliationQueries $queries): Response
    {
        Gate::authorize('inventory.view');

        return Inertia::render('Inventory::Reconciliations/Show', [
            'labels' => __('inventory::labels'),
            'backUrl' => route('admin.inventory.reconciliations.index'),
            'run' => $queries->run($reconciliation),
            'lines' => $queries->lines($reconciliation->id),
        ]);
    }
}
