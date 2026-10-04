<?php

declare(strict_types=1);

namespace Modules\Inventory\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\VariantDirectory;
use Modules\Inventory\Application\StockQueries;
use Modules\Inventory\Application\StockTransferQueries;
use Modules\Inventory\Application\StockTransferService;
use Modules\Inventory\Http\Requests\StockTransferRequest;
use Modules\Inventory\Persistence\Models\Location;
use Modules\Inventory\Persistence\Models\StockTransfer;

/**
 * Chuyển kho có vòng đời (pending → shipped → received, cancelled).
 */
final class StockTransferController
{
    public function index(StockTransferQueries $queries, StockQueries $stock): Response
    {
        Gate::authorize('inventory.view');

        return Inertia::render('Inventory::Transfers/Index', [
            'baseUrl' => route('admin.inventory.transfers.index'),
            'locations' => array_map(fn (Location $location): array => [
                'id' => $location->id,
                'code' => $location->code,
                'name' => $location->name,
                'external' => ! $location->managesOnHand(),
            ], $stock->locations()),
            'transfers' => $queries->recent(),
            'canManage' => Gate::allows('inventory.transfer'),
        ]);
    }

    public function store(StockTransferRequest $request, VariantDirectory $variants, StockTransferService $transfers): RedirectResponse
    {
        $data = $request->validated();
        $from = Location::query()->find((int) $data['from_location_id']);
        $to = Location::query()->find((int) $data['to_location_id']);
        abort_if($from === null || $to === null, 404);

        $lines = collect($data['lines']);
        $found = $variants->findBySkus($lines->pluck('sku')->map(fn ($sku): string => trim((string) $sku))->all());

        $quantities = [];
        $errors = [];
        foreach ($lines as $index => $line) {
            $sku = trim((string) $line['sku']);
            $variant = $found[$sku] ?? null;
            if ($variant === null) {
                $errors["lines.{$index}.sku"] = __('inventory::messages.transfer_unknown_sku', ['sku' => $sku]);

                continue;
            }
            $quantities[$variant->id] = ($quantities[$variant->id] ?? 0) + (int) $line['quantity'];
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        $transfers->create($from, $to, $quantities, $data['note'] ?? null, $data['reference'] ?? null);

        return back()->with('success', __('inventory::messages.transfer_created'));
    }

    public function ship(StockTransfer $transfer, StockTransferService $transfers): RedirectResponse
    {
        Gate::authorize('inventory.transfer');
        $transfers->ship($transfer);

        return back()->with('success', __('inventory::messages.transfer_shipped'));
    }

    public function receive(StockTransfer $transfer, Request $request, StockTransferService $transfers): RedirectResponse
    {
        Gate::authorize('inventory.transfer');
        $data = $request->validate(['received' => ['nullable', 'array'], 'received.*' => ['integer', 'min:0', 'max:1000000']]);
        $received = array_map(fn ($quantity): int => (int) $quantity, $data['received'] ?? []);
        $transfers->receive($transfer, $received);

        return back()->with('success', __('inventory::messages.transfer_received'));
    }

    public function cancel(StockTransfer $transfer, Request $request, StockTransferService $transfers): RedirectResponse
    {
        Gate::authorize('inventory.transfer');
        $data = $request->validate(['reason' => ['required', 'string', 'max:255']]);
        $transfers->cancel($transfer, $data['reason']);

        return back()->with('success', __('inventory::messages.transfer_cancelled'));
    }
}
