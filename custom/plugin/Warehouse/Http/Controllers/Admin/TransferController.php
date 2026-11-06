<?php

declare(strict_types=1);

namespace Plugin\Warehouse\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Contracts\Data\LocationData;
use Modules\Inventory\Contracts\LocationRegistry;
use Plugin\Warehouse\Application\TransferQueries;
use Plugin\Warehouse\Application\TransferService;
use Plugin\Warehouse\Http\Requests\TransferRequest;
use Plugin\Warehouse\Persistence\Transfer;

/**
 * Chuyển kho có vòng đời (pending → shipped → received, cancelled).
 */
final class TransferController
{
    public function index(TransferQueries $queries, LocationRegistry $locations): Response
    {
        Gate::authorize('inventory.view');

        return Inertia::render('Warehouse::Transfers/Index', [
            'labels' => __('vani-warehouse::labels'),
            'baseUrl' => route('admin.plugins.vani-warehouse.transfers.index'),
            'locations' => array_map(fn (LocationData $location): array => [
                'id' => $location->id,
                'code' => $location->code,
                'name' => $location->name,
                'external' => ! $location->managesOnHand(),
            ], $locations->all()),
            'transfers' => $queries->recent(),
            'canManage' => Gate::allows('warehouse.transfer'),
        ]);
    }

    public function store(TransferRequest $request, VariantDirectory $variants, TransferService $transfers, LocationRegistry $locations): RedirectResponse
    {
        $data = $request->validated();
        $from = $locations->find((int) $data['from_location_id']);
        $to = $locations->find((int) $data['to_location_id']);
        abort_if($from === null || $to === null, 404);

        $lines = collect($data['lines']);
        $found = $variants->findBySkus($lines->pluck('sku')->map(fn ($sku): string => trim((string) $sku))->all());

        $quantities = [];
        $errors = [];
        foreach ($lines as $index => $line) {
            $sku = trim((string) $line['sku']);
            $variant = $found[$sku] ?? null;
            if ($variant === null) {
                $errors["lines.{$index}.sku"] = __('vani-warehouse::messages.transfer_unknown_sku', ['sku' => $sku]);

                continue;
            }
            $quantities[$variant->id] = ($quantities[$variant->id] ?? 0) + (int) $line['quantity'];
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $transfers->create($from, $to, $quantities, $data['note'] ?? null, $data['reference'] ?? null);

        return back()->with('success', __('vani-warehouse::messages.transfer_created'));
    }

    public function ship(Transfer $transfer, TransferService $transfers): RedirectResponse
    {
        Gate::authorize('warehouse.transfer');
        $transfers->ship($transfer);

        return back()->with('success', __('vani-warehouse::messages.transfer_shipped'));
    }

    public function receive(Transfer $transfer, Request $request, TransferService $transfers): RedirectResponse
    {
        Gate::authorize('warehouse.transfer');
        $data = $request->validate(['received' => ['nullable', 'array'], 'received.*' => ['integer', 'min:0', 'max:1000000']]);
        $received = array_map(fn ($quantity): int => (int) $quantity, $data['received'] ?? []);
        $transfers->receive($transfer, $received);

        return back()->with('success', __('vani-warehouse::messages.transfer_received'));
    }

    public function cancel(Transfer $transfer, Request $request, TransferService $transfers): RedirectResponse
    {
        Gate::authorize('warehouse.transfer');
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $transfers->cancel($transfer, $data['reason']);

        return back()->with('success', __('vani-warehouse::messages.transfer_cancelled'));
    }
}
