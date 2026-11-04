<?php

declare(strict_types=1);

namespace Modules\Returns\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Returns\Application\ReturnReasons;
use Modules\Returns\Persistence\Models\ReturnReason;
use Modules\Shared\Support\StoreLocale;

/**
 * Admin → Đổi trả → Lý do (reference-parity §3.13-1): tên theo ngôn ngữ, thứ tự, bật/tắt, "lỗi do cửa hàng".
 */
final class ReasonController
{
    public function __construct(private readonly ReturnReasons $reasons) {}

    public function index(): Response
    {
        Gate::authorize('returns.view');

        return Inertia::render('Returns::Reasons/Index', [
            'baseUrl' => route('admin.returns.reasons.index'),
            'returnsUrl' => route('admin.returns.returns.index'),
            'reasons' => array_values(array_map(fn (ReturnReason $reason): array => [
                'id' => $reason->id, 'code' => $reason->code, 'names' => $reason->names, 'position' => $reason->position,
                'is_active' => $reason->is_active, 'store_fault' => $reason->store_fault, 'used' => $this->reasons->used($reason),
            ], $this->reasons->all())),
            'canManage' => Gate::allows('returns.manage'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('returns.manage');
        $this->reasons->save(null, $this->validated($request, null));

        return back()->with('success', __('returns::messages.reason_saved'));
    }

    public function update(Request $request, ReturnReason $reason): RedirectResponse
    {
        Gate::authorize('returns.manage');
        $this->reasons->save($reason, $this->validated($request, $reason));

        return back()->with('success', __('returns::messages.reason_saved'));
    }

    public function destroy(ReturnReason $reason): RedirectResponse
    {
        Gate::authorize('returns.manage');
        $this->reasons->delete($reason);

        return back()->with('success', __('returns::messages.reason_deleted'));
    }

    /**
     * @return array{code: string, names: array<string, string|null>, position: int, is_active: bool, store_fault: bool}
     */
    private function validated(Request $request, ?ReturnReason $reason): array
    {
        $rules = [
            'code' => ['required', 'string', 'max:32', 'regex:/^[a-z][a-z0-9_]*$/', Rule::unique('return_reasons', 'code')->ignore($reason?->id)],
            'position' => ['nullable', 'integer', 'min:0', 'max:1000'],
            'is_active' => ['boolean'],
            'store_fault' => ['boolean'],
        ];
        foreach (StoreLocale::supported() as $locale) {
            $rules["names.{$locale}"] = ['nullable', 'string', 'max:80'];
        }
        $data = $request->validate($rules);

        return [
            'code' => $data['code'], 'names' => (array) ($data['names'] ?? []), 'position' => (int) ($data['position'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? true), 'store_fault' => (bool) ($data['store_fault'] ?? false),
        ];
    }
}
