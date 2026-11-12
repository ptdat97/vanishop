<?php

declare(strict_types=1);

namespace Modules\Catalog\Http\Controllers\Admin;

use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Application\Taxonomy\OptionTypeService;
use Modules\Catalog\Domain\ColorFamily;
use Modules\Catalog\Domain\OptionDisplayType;
use Modules\Catalog\Http\Requests\Admin\OptionTypeRequest;
use Modules\Catalog\Http\Requests\Admin\OptionValueRequest;
use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Catalog\Persistence\Models\OptionValue;

/**
 * Admin → Catalog → Tuỳ chọn biến thể (ADR-045): loại tuỳ chọn dùng chung (Màu, Size, Chất liệu…) và giá trị.
 */
final class OptionTypeController
{
    public function index(OptionTypeService $service): Response
    {
        Gate::authorize('catalog.view');
        ['types' => $typeUsage, 'values' => $used] = $service->usage();

        return Inertia::render('Catalog::Taxonomy/OptionTypes', [
            'nav' => CatalogNavigation::all(),
            'types' => OptionType::query()->with(['translations', 'values.translations'])->orderBy('position')->orderBy('code')->get()->map(fn (OptionType $type): array => [
                'id' => $type->id, 'code' => $type->code, 'display_type' => $type->display_type->value, 'is_filterable' => $type->is_filterable,
                'position' => $type->position, 'translations' => $type->translationsByLocale(),
                'products' => $typeUsage[$type->id] ?? 0,
                'values' => $type->values->map(fn (OptionValue $value): array => [
                    'id' => $value->id, 'code' => $value->code, 'group' => $value->group, 'hex' => $value->hex, 'family' => $value->family,
                    'position' => $value->position, 'translations' => $value->translationsByLocale(), 'products' => (int) ($used[$value->id] ?? 0),
                ])->all(),
            ])->all(),
            'displayTypes' => array_column(OptionDisplayType::cases(), 'value'),
            'families' => array_column(ColorFamily::cases(), 'value'),
            'displayLabels' => __('catalog::labels.option_display'),
            'canManage' => Gate::allows('catalog.manage'),
        ]);
    }

    public function store(OptionTypeRequest $request, OptionTypeService $service): RedirectResponse
    {
        $service->saveType($request->toData());

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function update(OptionType $optionType, OptionTypeRequest $request, OptionTypeService $service): RedirectResponse
    {
        $service->saveType($request->toData(), $optionType);

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroy(OptionType $optionType, OptionTypeService $service): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        $service->deleteType($optionType);

        return back()->with('success', __('catalog::messages.deleted'));
    }

    public function storeValue(OptionType $optionType, OptionValueRequest $request, OptionTypeService $service): RedirectResponse
    {
        $service->saveValue($optionType, $request->toData());

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function updateValue(OptionType $optionType, OptionValue $value, OptionValueRequest $request, OptionTypeService $service): RedirectResponse
    {
        abort_unless($value->option_type_id === $optionType->id, 404);
        $service->saveValue($optionType, $request->toData(), $value);

        return back()->with('success', __('catalog::messages.saved'));
    }

    public function destroyValue(OptionType $optionType, OptionValue $value, OptionTypeService $service): RedirectResponse
    {
        Gate::authorize('catalog.manage');
        abort_unless($value->option_type_id === $optionType->id, 404);
        $service->deleteValue($value);

        return back()->with('success', __('catalog::messages.deleted'));
    }
}
