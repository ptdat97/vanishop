<?php

declare(strict_types=1);

namespace Modules\Fulfillment\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Fulfillment\Application\CodStatement;
use Modules\Ordering\Contracts\OrderReader;
use Modules\Shared\Support\SpreadsheetRows;
use RuntimeException;

/**
 * Nhập bảng kê COD của hãng vận chuyển (stabilization A9): tải file → xem trước (chưa ghi gì) → áp dụng.
 */
final class CodStatementController
{
    public function preview(Request $request, CodStatement $statement, OrderReader $orders): Response
    {
        Gate::authorize('payments.confirm');
        $request->validate(['file' => ['required', 'file', 'max:5120', 'extensions:xlsx,csv,txt']]);
        try {
            $rows = SpreadsheetRows::fromUpload($request->file('file'));
        } catch (RuntimeException $exception) {
            throw ValidationException::withMessages(['file' => $exception->getMessage()]);
        }
        $preview = $statement->preview($rows, $request->file('file')->getClientOriginalName());
        $numbers = $orders->numbers(array_values(array_filter(array_column($preview['lines'], 'order_id'))));
        $preview['lines'] = array_map(fn (array $line): array => [
            ...$line,
            'order_number' => $line['order_id'] === null ? null : ($numbers[$line['order_id']] ?? null),
            'order_url' => $line['order_id'] === null ? null : route('admin.orders.orders.show', ['order' => $line['order_id']]),
        ], $preview['lines']);

        return Inertia::render('Fulfillment::CodStatements/Preview', [
            'preview' => $preview,
            'statusLabels' => __('fulfillment::labels.cod_statement_status'),
            'urls' => [
                'back' => route('admin.fulfillment.shipments.index'),
                'apply' => $preview['token'] === null ? null : route('admin.fulfillment.cod-statements.apply', $preview['token']),
            ],
        ]);
    }

    public function apply(string $token, CodStatement $statement): RedirectResponse
    {
        Gate::authorize('payments.confirm');
        $result = $statement->apply($token);
        $back = redirect()->route('admin.payment.payments.index');

        return $result === null
            ? $back->withErrors(['file' => __('fulfillment::messages.cod_statement_expired')])
            : $back->with('success', __('fulfillment::messages.cod_statement_imported', ['matched' => $result['matched'], 'mismatched' => $result['mismatched'], 'fees' => $result['fees']]));
    }
}
