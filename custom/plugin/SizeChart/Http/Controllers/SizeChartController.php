<?php

declare(strict_types=1);

namespace Plugin\SizeChart\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Modules\Catalog\Contracts\MediaDirectory;
use Modules\Shared\Support\StoreLocale;
use Plugin\SizeChart\Infrastructure\SizeCharts;
use Plugin\SizeChart\Persistence\SizeChart;

final class SizeChartController
{
    public function __construct(
        private readonly SizeCharts $charts,
        private readonly MediaDirectory $media,
    ) {}

    public function index(): Response
    {
        Gate::authorize('size_chart.view');
        $counts = DB::table('plg_size_chart_assignments')->selectRaw('chart_id, target_type, count(*) as n')->groupBy('chart_id', 'target_type')->get()
            ->groupBy('chart_id');

        return Inertia::render('SizeChart::Index', [
            'charts' => SizeChart::query()->orderByDesc('is_default')->orderBy('id')->get()->map(fn (SizeChart $chart): array => [
                'id' => $chart->id,
                'name' => $chart->name(StoreLocale::default(), StoreLocale::default()),
                'unit' => $chart->base_unit.($chart->display_unit ? ' → '.$chart->display_unit : ''),
                'is_default' => $chart->is_default,
                'active' => $chart->active,
                'assignments' => collect($counts->get($chart->id, []))->mapWithKeys(fn (object $row): array => [$row->target_type => (int) $row->n])->all(),
            ])->all(),
            'can' => ['manage' => Gate::allows('size_chart.manage')],
            'urls' => ['create' => route('admin.plugins.vani-size-chart.create'), 'base' => route('admin.plugins.vani-size-chart.index')],
        ]);
    }

    public function create(): Response
    {
        Gate::authorize('size_chart.manage');

        return $this->form(null);
    }

    public function store(Request $request): RedirectResponse
    {
        Gate::authorize('size_chart.manage');
        $chart = $this->charts->save($this->validated($request));

        return redirect()->route('admin.plugins.vani-size-chart.edit', ['id' => $chart->id])->with('success', 'Đã lưu bảng size.');
    }

    public function edit(int $id): Response
    {
        Gate::authorize('size_chart.view');

        return $this->form(SizeChart::query()->findOrFail($id));
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        Gate::authorize('size_chart.manage');
        $chart = SizeChart::query()->findOrFail($id);
        $this->charts->save($this->validated($request), $chart, (int) $request->input('lock_version'));

        return back()->with('success', 'Đã lưu bảng size.');
    }

    public function destroy(int $id): RedirectResponse
    {
        Gate::authorize('size_chart.manage');
        $this->charts->delete(SizeChart::query()->findOrFail($id));

        return redirect()->route('admin.plugins.vani-size-chart.index')->with('success', 'Đã xoá bảng size và bỏ mọi gán.');
    }

    private function form(?SizeChart $chart): Response
    {
        $mediaIds = $chart === null ? [] : array_values(array_filter(array_map(fn (array $block): ?int => $block['type'] === 'image' ? (int) $block['media_id'] : null, $chart->blocks)));
        $images = $mediaIds === [] ? [] : $this->media->find($mediaIds);

        return Inertia::render('SizeChart::Form', [
            'chart' => $chart === null ? null : [
                'id' => $chart->id,
                'names' => $chart->names,
                'base_unit' => $chart->base_unit,
                'display_unit' => $chart->display_unit,
                'blocks' => array_map(fn (array $block): array => $block['type'] === 'image'
                    ? [...$block, 'url' => ($images[$block['media_id']] ?? null)?->urlFor(400)]
                    : $block, $chart->blocks),
                'advisor_enabled' => $chart->advisor_enabled,
                'advisor_rules' => $chart->advisor_rules,
                'is_default' => $chart->is_default,
                'active' => $chart->active,
                'lock_version' => $chart->lock_version,
            ],
            'units' => array_keys(SizeCharts::UNITS),
            'can' => ['manage' => Gate::allows('size_chart.manage')],
            'urls' => ['base' => route('admin.plugins.vani-size-chart.index')],
        ]);
    }

    /**
     * @return array{names: array<string, string|null>, base_unit: string, display_unit: ?string, blocks: list<array<string, mixed>>, advisor_enabled: bool, advisor_rules: list<array<string, mixed>>, is_default: bool, active: bool}
     */
    private function validated(Request $request): array
    {
        $units = array_keys(SizeCharts::UNITS);
        $data = $request->validate([
            'names' => ['required', 'array'],
            'names.'.StoreLocale::default() => ['required', 'string', 'max:120'],
            'names.*' => ['nullable', 'string', 'max:120'],
            'base_unit' => ['required', Rule::in($units)],
            'display_unit' => ['nullable', Rule::in($units)],
            'blocks' => ['present', 'array', 'max:20'],
            'blocks.*.type' => ['required', Rule::in(['image', 'text', 'table'])],
            'blocks.*.media_id' => ['required_if:blocks.*.type,image', 'nullable', 'integer'],
            'blocks.*.alt' => ['nullable', 'string', 'max:200'],
            'blocks.*.markdown' => ['required_if:blocks.*.type,text', 'nullable', 'string', 'max:5000'],
            'blocks.*.rows' => ['required_if:blocks.*.type,table', 'nullable', 'array', 'max:30'],
            'blocks.*.rows.*' => ['array', 'max:12'],
            'blocks.*.rows.*.*' => ['nullable', 'string', 'max:40'],
            'advisor_enabled' => ['boolean'],
            'advisor_rules' => ['present', 'array', 'max:40'],
            'advisor_rules.*.size' => ['required', 'string', 'max:16'],
            'advisor_rules.*.height_min' => ['required', 'numeric', 'min:0', 'max:300'],
            'advisor_rules.*.height_max' => ['required', 'numeric', 'min:0', 'max:300', 'gte:advisor_rules.*.height_min'],
            'advisor_rules.*.weight_min' => ['required', 'numeric', 'min:0', 'max:300'],
            'advisor_rules.*.weight_max' => ['required', 'numeric', 'min:0', 'max:300', 'gte:advisor_rules.*.weight_min'],
            'advisor_rules.*.note' => ['nullable', 'string', 'max:200'],
            'is_default' => ['boolean'],
            'active' => ['boolean'],
        ]);

        $mediaIds = array_values(array_filter(array_map(fn (array $block): ?int => $block['type'] === 'image' ? (int) $block['media_id'] : null, $data['blocks'])));
        if (count($this->media->find($mediaIds)) !== count(array_unique($mediaIds))) {
            throw ValidationException::withMessages(['blocks' => 'Ảnh không còn trong Thư viện ảnh.']);
        }

        return [
            'names' => array_intersect_key((array) $data['names'], array_flip(StoreLocale::supported())),
            'base_unit' => $data['base_unit'],
            'display_unit' => $data['display_unit'] ?? null,
            'blocks' => $data['blocks'],
            'advisor_enabled' => (bool) ($data['advisor_enabled'] ?? false),
            'advisor_rules' => $data['advisor_rules'],
            'is_default' => (bool) ($data['is_default'] ?? false),
            'active' => (bool) ($data['active'] ?? true),
        ];
    }
}
