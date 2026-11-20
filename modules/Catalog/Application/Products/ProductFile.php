<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Catalog\Application\Taxonomy\OptionTypeService;
use Modules\Catalog\Application\Taxonomy\TaxonomyService;
use Modules\Catalog\Contracts\VariantFieldProvider;
use Modules\Catalog\Domain\StyleStatus;
use Modules\Catalog\Events\ProductUpdated;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Contracts\AuditLogger;
use Modules\Shared\Support\StoreLocale;

/**
 * Nhập/xuất sản phẩm bằng file (stabilization A4) — mỗi SKU một dòng, dòng cùng "Mã sản phẩm" là một sản phẩm. File xuất ra là
 * mẫu để nhập lại. Nhập: xem trước (chưa ghi gì, file giữ 30 phút theo token) → áp dụng; ô trống = giữ nguyên. Sản phẩm mới
 * là bản nháp nếu không ghi trạng thái. Thương hiệu, giá trị tuỳ chọn chưa có thì tạo; danh mục, loại tuỳ chọn phải có sẵn.
 * Cột theo SKU của module/plugin khác (giá niêm yết, giá gạch, giá vốn…) lấy từ `VariantFieldProvider` — mỗi trường một cột,
 * chỉ khi người dùng có quyền sửa trường đó. Mọi ghi đi qua ProductService/ProductOptions (audit, event, hook như form).
 */
final class ProductFile
{
    public const MAX_AXES = 3;

    private const CACHE_PREFIX = 'catalog:product-import:';

    /** Cột của Catalog: khoá => [tiêu đề khi xuất, các tiêu đề nhận khi nhập (đã bỏ dấu, chữ thường)]. */
    private const COLUMNS = [
        'code' => ['Mã sản phẩm', ['ma san pham', 'ma', 'style code', 'style_code']], // i18n-ignore: tiêu đề cột file (định dạng file)
        'name' => ['Tên sản phẩm', ['ten san pham', 'ten', 'name']], // i18n-ignore: tiêu đề cột file (định dạng file)
        'status' => ['Trạng thái', ['trang thai', 'status']], // i18n-ignore: tiêu đề cột file (định dạng file)
        'brand' => ['Thương hiệu', ['thuong hieu', 'brand']], // i18n-ignore: tiêu đề cột file (định dạng file)
        'categories' => ['Danh mục (mã, cách nhau bằng |)', ['danh muc (ma, cach nhau bang |)', 'danh muc', 'categories', 'category']], // i18n-ignore: tiêu đề cột file (định dạng file)
        'description' => ['Mô tả', ['mo ta', 'description']], // i18n-ignore: tiêu đề cột file (định dạng file)
        'option1' => ['Tuỳ chọn 1', ['tuy chon 1', 'option 1']], // i18n-ignore: tiêu đề cột file (định dạng file)
        'value1' => ['Giá trị 1', ['gia tri 1', 'value 1']], // i18n-ignore: tiêu đề cột file (định dạng file)
        'option2' => ['Tuỳ chọn 2', ['tuy chon 2', 'option 2']], // i18n-ignore: tiêu đề cột file (định dạng file)
        'value2' => ['Giá trị 2', ['gia tri 2', 'value 2']], // i18n-ignore: tiêu đề cột file (định dạng file)
        'option3' => ['Tuỳ chọn 3', ['tuy chon 3', 'option 3']], // i18n-ignore: tiêu đề cột file (định dạng file)
        'value3' => ['Giá trị 3', ['gia tri 3', 'value 3']], // i18n-ignore: tiêu đề cột file (định dạng file)
        'sku' => ['SKU', ['sku', 'ma sku']],
        'barcode' => ['Barcode', ['barcode', 'ma vach']],
        'weight' => ['Khối lượng (g)', ['khoi luong (g)', 'khoi luong', 'weight', 'weight (g)']], // i18n-ignore: tiêu đề cột file (định dạng file)
    ];

    public function __construct(
        private readonly ProductService $products,
        private readonly ProductOptions $options,
        private readonly OptionTypeService $optionTypes,
        private readonly TaxonomyService $taxonomy,
        private readonly Extensions $extensions,
        private readonly AuditLogger $audit,
    ) {}

    /**
     * File xuất: tiêu đề + mỗi SKU một dòng (sản phẩm chưa có SKU: một dòng không SKU).
     *
     * @param  list<int>|null  $styleIds  null = mọi sản phẩm
     * @return list<list<string|int|null>>
     */
    public function export(?array $styleIds = null): array
    {
        $fields = $this->fieldColumns();
        $rows = [[...array_map(fn (array $column): string => $column[0], array_values(self::COLUMNS)), ...array_column($fields, 'header')]];
        $statusLabels = (array) __('catalog::labels.product_status');

        Style::query()->withoutGlobalScopes()->with(['translations', 'brand', 'categories', 'options', 'variants.optionValues.translations', 'variants.style.options'])
            ->when($styleIds !== null, fn ($query) => $query->whereKey($styleIds))
            ->orderBy('style_code')
            ->chunk(200, function (Collection $styles) use (&$rows, $fields, $statusLabels): void {
                $types = OptionType::query()->with('translations')->get()->keyBy('id');
                foreach ($styles as $style) {
                    /** @var Style $style */
                    $variants = $style->variants->sortBy(fn (Variant $variant): string => VariantOptions::sortKey($variant))->values();
                    $values = $this->fieldValues($fields, $style->id, $variants->pluck('id')->map(fn ($id): int => (int) $id)->all());
                    $base = [
                        $style->style_code, $style->translate('name'), $statusLabels[$style->status->value] ?? $style->status->value, $style->brand?->name,
                        $style->categories->pluck('slug')->implode(' | '), $style->translate('description'),
                    ];
                    foreach ($variants->isEmpty() ? [null] : $variants as $variant) {
                        $options = [];
                        foreach (range(0, self::MAX_AXES - 1) as $index) {
                            $value = $variant === null ? null : (VariantOptions::ordered($variant)[$index] ?? null);
                            $options[] = $value === null ? null : $types->get($value->option_type_id)?->translate('name') ?? $types->get($value->option_type_id)?->code;
                            $options[] = $value?->label();
                        }
                        $extra = array_map(fn (array $field): mixed => $variant === null ? null : ($values[$variant->id][$field['provider']][$field['field']->key] ?? null), $fields);
                        $rows[] = [...$base, ...$options, $variant?->sku, $variant?->barcode, $variant?->weight_gram, ...$extra];
                    }
                }
            });

        return $rows;
    }

    /**
     * Đọc file, lập kế hoạch (chưa ghi). Có ít nhất một sản phẩm ghi được → token để áp dụng.
     *
     * @param  list<list<string>>  $rows
     * @return array{token: string|null, file: string, products: list<array<string, mixed>>, summary: array{create: int, update: int, error: int}}
     */
    public function preview(array $rows, string $fileName): array
    {
        $plans = $this->plan($rows);
        $summary = ['create' => 0, 'update' => 0, 'error' => 0];
        foreach ($plans as $plan) {
            $summary[$plan['errors'] === [] ? $plan['action'] : 'error']++;
        }
        $token = null;
        if ($summary['create'] + $summary['update'] > 0) {
            $token = Str::random(40);
            Cache::put(self::CACHE_PREFIX.$token, ['file' => $fileName, 'rows' => $rows], now()->addMinutes(30));
        }

        return [
            'token' => $token,
            'file' => $fileName,
            'products' => array_map(fn (array $plan): array => [
                'code' => $plan['code'], 'lines' => $plan['lines'], 'action' => $plan['errors'] === [] ? $plan['action'] : 'error', 'name' => $plan['name'],
                'new_skus' => $plan['new_skus'], 'updated_skus' => $plan['updated_skus'], 'notes' => $plan['notes'], 'errors' => $plan['errors'],
            ], $plans),
            'summary' => $summary,
        ];
    }

    /**
     * Áp dụng file đã xem trước: lập lại kế hoạch trên dữ liệu hiện tại (có thể đã đổi từ lúc xem), ghi từng sản phẩm hợp lệ
     * trong một transaction; sản phẩm lỗi bỏ qua và báo lại.
     *
     * @return array{created: int, updated: int, errors: list<string>}|null null = token hết hạn
     */
    public function apply(string $token): ?array
    {
        $pending = Cache::pull(self::CACHE_PREFIX.$token);
        if (! is_array($pending) || ! isset($pending['rows'])) {
            return null;
        }
        $created = $updated = 0;
        $errors = [];
        foreach ($this->plan($pending['rows']) as $plan) {
            if ($plan['errors'] !== []) {
                $errors[] = "{$plan['code']}: ".implode(' ', $plan['errors']);

                continue;
            }
            $fieldErrors = $this->execute($plan);
            $plan['action'] === 'create' ? $created++ : $updated++;
            foreach ($fieldErrors as $message) {
                $errors[] = "{$plan['code']}: {$message}";
            }
        }
        $this->audit->record('catalog.products.imported', 'product_file', null, ['file' => (string) ($pending['file'] ?? ''), 'created' => $created, 'updated' => $updated, 'errors' => count($errors)]);

        return ['created' => $created, 'updated' => $updated, 'errors' => $errors];
    }

    /**
     * @param  list<list<string>>  $rows
     * @return list<array<string, mixed>>
     */
    private function plan(array $rows): array
    {
        $header = array_map(fn (string $cell): string => trim(Str::lower(Str::ascii($cell))), $rows[0] ?? []);
        $columns = [];
        foreach (self::COLUMNS as $key => [, $names]) {
            $index = $this->column($header, $names);
            if ($index !== null) {
                $columns[$key] = $index;
            }
        }
        $fields = [];
        foreach ($this->fieldColumns() as $field) {
            $index = $this->column($header, [trim(Str::lower(Str::ascii($field['header'])))]);
            if ($index !== null) {
                $fields[] = [...$field, 'index' => $index];
            }
        }
        if (! isset($columns['code'])) {
            return [['code' => '—', 'lines' => [1], 'action' => 'error', 'name' => null, 'new_skus' => 0, 'updated_skus' => 0, 'notes' => [], 'errors' => [__('catalog::messages.import_header')]]];
        }

        $groups = [];
        foreach (array_slice($rows, 1) as $offset => $row) {
            $cell = fn (string $key): string => isset($columns[$key]) ? trim((string) ($row[$columns[$key]] ?? '')) : '';
            $code = Str::upper($cell('code'));
            if ($code === '') {
                continue;
            }
            $groups[$code] ??= ['code' => $code, 'rows' => []];
            $groups[$code]['rows'][] = ['line' => $offset + 2, 'cell' => $cell, 'fields' => array_map(fn (array $field): array => [...$field, 'raw' => trim((string) ($row[$field['index']] ?? ''))], $fields)];
        }

        $skusInFile = [];
        $plans = [];
        foreach ($groups as $group) {
            $plans[] = $this->planProduct($group, $skusInFile);
        }

        return $plans;
    }

    /**
     * @param  array{code: string, rows: list<array{line: int, cell: Closure(string): string, fields: list<array<string, mixed>>}>}  $group
     * @param  array<string, string>  $skusInFile  SKU => mã sản phẩm (phát hiện SKU trùng giữa các sản phẩm trong file)
     * @return array<string, mixed>
     */
    private function planProduct(array $group, array &$skusInFile): array
    {
        $code = $group['code'];
        $first = $group['rows'][0]['cell'];
        $errors = $notes = [];
        $style = Style::query()->withoutGlobalScopes()->with(['options', 'variants.optionValues'])->where('style_code', $code)->first();
        $plan = [
            'code' => $code, 'lines' => array_column($group['rows'], 'line'), 'action' => $style === null ? 'create' : 'update', 'style_id' => $style?->id,
            'name' => $first('name') ?: null, 'description' => $first('description') ?: null, 'status' => null, 'brand' => null, 'category_ids' => null,
            'axes' => [], 'variants' => [], 'new_skus' => 0, 'updated_skus' => 0,
        ];

        if ($style === null && ! preg_match('/^[A-Z0-9][A-Z0-9._-]{0,63}$/', $code)) {
            $errors[] = __('catalog::messages.import_code_invalid');
        }
        if ($style === null && $plan['name'] === null) {
            $errors[] = __('catalog::messages.import_name_required');
        }
        if (($status = $first('status')) !== '') {
            $plan['status'] = $this->status($status);
            if ($plan['status'] === null) {
                $errors[] = __('catalog::messages.import_status_invalid', ['value' => $status]);
            }
        }
        if (($brand = $first('brand')) !== '') {
            $plan['brand'] = $brand;
            if (! Brand::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($brand)])->exists()) {
                $notes[] = __('catalog::messages.import_brand_new', ['name' => $brand]);
            }
        }
        if (($categories = $first('categories')) !== '') {
            $plan['category_ids'] = [];
            foreach (array_filter(array_map('trim', preg_split('/[|;,]/', $categories) ?: [])) as $slug) {
                $id = Category::query()->where('slug', Str::lower($slug))->value('id');
                $id === null ? $errors[] = __('catalog::messages.import_category_missing', ['slug' => $slug]) : $plan['category_ids'][] = (int) $id;
            }
        }

        // Trục: loại tuỳ chọn theo thứ tự cột của dòng đầu; mọi dòng phải cùng loại.
        $types = [];
        foreach (range(1, self::MAX_AXES) as $index) {
            if (($name = $first("option{$index}")) === '') {
                continue;
            }
            $type = OptionType::query()->whereRaw('LOWER(code) = ?', [Str::lower($name)])->first()
                ?? OptionType::query()->whereHas('translations', fn ($query) => $query->whereRaw('LOWER(name) = ?', [mb_strtolower($name)]))->first();
            $type === null ? $errors[] = __('catalog::messages.import_option_type_missing', ['name' => $name]) : $types[$index] = $type;
        }
        $typeIds = array_map(fn (OptionType $type): int => $type->id, array_values($types));
        $currentTypes = $style?->options->pluck('option_type_id')->map(fn ($id): int => (int) $id)->all() ?? [];
        if ($style !== null && $style->variants->isNotEmpty() && $typeIds !== [] && $currentTypes !== [] && array_diff($currentTypes, $typeIds) + array_diff($typeIds, $currentTypes) !== []) {
            $errors[] = __('catalog::messages.import_axes_locked');
        }
        $plan['axes'] = $typeIds;

        $existingByKey = $style?->variants->keyBy('option_key') ?? collect();
        $existingBySku = $style?->variants->keyBy('sku') ?? collect();
        foreach ($group['rows'] as $row) {
            $cell = $row['cell'];
            $line = $row['line'];
            $values = [];
            foreach ($types as $index => $type) {
                if ($cell("option{$index}") !== '' && Str::lower(Str::ascii($cell("option{$index}"))) !== Str::lower(Str::ascii($first("option{$index}")))) {
                    $errors[] = __('catalog::messages.import_axes_mismatch', ['line' => $line]);
                }
                $label = $cell("value{$index}");
                if ($label === '') {
                    $errors[] = __('catalog::messages.import_value_missing', ['line' => $line, 'type' => $type->translate('name') ?? $type->code]);

                    continue;
                }
                $value = $this->findValue($type, $label);
                $values[] = ['type_id' => $type->id, 'label' => $label, 'id' => $value?->id];
                if ($value === null) {
                    $notes[] = __('catalog::messages.import_value_new', ['type' => $type->translate('name') ?? $type->code, 'value' => $label]);
                }
            }
            if ($types === [] && $style !== null && $currentTypes !== []) {
                $errors[] = __('catalog::messages.import_values_required', ['line' => $line]);
            }

            $sku = Str::upper($cell('sku'));
            $weight = $cell('weight');
            if ($weight !== '' && ! ctype_digit($weight)) {
                $errors[] = __('catalog::messages.import_weight_invalid', ['line' => $line]);
            }
            $ids = array_column($values, 'id');
            $existing = in_array(null, $ids, true) ? null : $existingByKey->get(Variant::keyFor(array_map('intval', $ids)));
            $existing ??= $sku !== '' ? $existingBySku->get($sku) : null;
            if ($sku !== '') {
                $owner = Variant::query()->where('sku', $sku)->value('style_id');
                if (($owner !== null && (int) $owner !== $style?->id) || (isset($skusInFile[$sku]) && $skusInFile[$sku] !== $code)) {
                    $errors[] = __('catalog::messages.import_sku_taken', ['line' => $line, 'sku' => $sku]);
                }
                $skusInFile[$sku] = $code;
            }
            if (($barcode = $cell('barcode')) !== '' && Variant::query()->where('barcode', $barcode)->where('id', '!=', $existing->id ?? 0)->exists()) {
                $errors[] = __('catalog::messages.import_barcode_taken', ['line' => $line, 'barcode' => $barcode]);
            }

            $fieldValues = [];
            foreach ($row['fields'] as $field) {
                if ($field['raw'] === '') {
                    continue;
                }
                $value = $this->fieldValue($field['field'], $field['raw']);
                if ($value === null || Validator::make(['v' => $value], ['v' => array_map(fn ($rule) => $rule === 'required' ? 'nullable' : $rule, $field['field']->rules())])->fails()) {
                    $errors[] = __('catalog::messages.import_field_invalid', ['line' => $line, 'field' => $field['header']]);

                    continue;
                }
                $fieldValues[$field['provider']][$field['field']->key] = $value;
            }

            $plan['variants'][] = ['line' => $line, 'values' => $values, 'variant_id' => $existing?->id, 'sku' => $sku, 'barcode' => $cell('barcode'), 'weight' => $weight, 'fields' => $fieldValues];
            $existing === null ? $plan['new_skus']++ : $plan['updated_skus']++;
        }

        return [...$plan, 'errors' => array_values(array_unique($errors)), 'notes' => array_values(array_unique($notes))];
    }

    /**
     * @param  array<string, mixed>  $plan
     * @return list<string> lỗi của cột theo SKU (giá…) — phần Catalog đã lưu
     */
    private function execute(array $plan): array
    {
        $variantIds = DB::transaction(function () use ($plan): array {
            $brandId = $plan['brand'] === null ? null : $this->brand($plan['brand'])->id;
            $style = $plan['style_id'] === null ? null : Style::query()->withoutGlobalScopes()->findOrFail($plan['style_id']);
            if ($style === null) {
                $locale = StoreLocale::default();
                $style = $this->products->create(new ProductInput(
                    styleCode: $plan['code'], slug: ProductIdentifiers::uniqueSlug((string) $plan['name'], $plan['code']), status: $plan['status'] ?? StyleStatus::Draft,
                    translations: [$locale => ['name' => $plan['name'], 'description' => $plan['description']]],
                    categoryIds: $plan['category_ids'] ?? [], primaryCategoryId: ($plan['category_ids'] ?? [])[0] ?? null, brandId: $brandId,
                ));
            } elseif ($plan['name'] !== null || $plan['description'] !== null || $plan['status'] !== null || $plan['brand'] !== null || $plan['category_ids'] !== null) {
                $current = $this->products->inputFor($style);
                $translations = $current->translations;
                $locale = StoreLocale::default();
                $translations[$locale] = array_replace($translations[$locale] ?? [], array_filter(['name' => $plan['name'], 'description' => $plan['description']], fn ($value): bool => $value !== null));
                $categoryIds = $plan['category_ids'] ?? $current->categoryIds;
                $style = $this->products->update($style, new ProductInput(
                    styleCode: $current->styleCode, slug: $current->slug, status: $plan['status'] ?? $current->status, translations: $translations,
                    publishedFrom: $current->publishedFrom, publishedTo: $current->publishedTo, categoryIds: $categoryIds,
                    primaryCategoryId: in_array($current->primaryCategoryId, $categoryIds, true) ? $current->primaryCategoryId : ($categoryIds[0] ?? null),
                    attributes: $current->attributes, brandId: $plan['brand'] === null ? $current->brandId : $brandId, trackInventory: $current->trackInventory,
                ), $style->lock_version);
            }

            // Giá trị tuỳ chọn mới → tạo; trục = trục hiện có + giá trị trong file (giữ thứ tự loại của file).
            $variants = $plan['variants'];
            if ($plan['axes'] !== []) {
                foreach ($variants as $index => $variant) {
                    foreach ($variant['values'] as $position => $value) {
                        $variants[$index]['values'][$position]['id'] ??= $this->value($value['type_id'], $value['label'])->id;
                    }
                }
                $style->load('optionValues');
                $current = $style->optionValues->pluck('option_value_id')->map(fn ($id): int => (int) $id)->all();
                $typeOf = OptionValue::query()->whereIn('id', $current)->pluck('option_type_id', 'id');
                $this->options->syncAxes($style, array_map(fn (int $typeId): array => [
                    'option_type_id' => $typeId,
                    'value_ids' => array_values(array_unique([
                        ...array_values(array_filter($current, fn (int $id): bool => (int) $typeOf->get($id) === $typeId)),
                        ...array_merge([], ...array_map(fn (array $variant): array => array_column(array_filter($variant['values'], fn (array $value): bool => $value['type_id'] === $typeId), 'id'), $variants)),
                    ])),
                ], $plan['axes']), fromSingle: $style->options()->doesntExist() && $style->variants()->count() === 1);
            }

            $ids = [];
            $updated = false;
            foreach ($variants as $variant) {
                $valueIds = array_map('intval', array_column($variant['values'], 'id'));
                $data = array_filter(['sku' => $variant['sku'], 'barcode' => $variant['barcode'], 'weight_gram' => $variant['weight'] === '' ? null : (int) $variant['weight']], fn ($value): bool => $value !== null && $value !== '');
                $model = $variant['variant_id'] === null ? null : Variant::query()->find($variant['variant_id']);
                if ($model === null) {
                    $model = $this->options->createVariant($style, $valueIds, $data);
                } elseif ($model->fill($data)->isDirty()) {
                    $model->save();
                    $updated = true;
                }
                $ids[$variant['line']] = $model->id;
            }
            if ($updated) {
                event(new ProductUpdated($style->id, $style->style_code));
            }

            return ['style_id' => $style->id, 'ids' => $ids];
        });

        $errors = [];
        foreach ($this->providers() as $code => $provider) {
            $values = [];
            foreach ($plan['variants'] as $variant) {
                if (isset($variant['fields'][$code], $variantIds['ids'][$variant['line']])) {
                    $values[$variantIds['ids'][$variant['line']]] = $variant['fields'][$code];
                }
            }
            if ($values === []) {
                continue;
            }
            $failed = $this->extensions->call($provider, fn (): array => $provider->save($variantIds['style_id'], $values), [0 => __('catalog::messages.variant_field_failed', ['label' => $provider->label()])], 'catalog.product_file.save');
            foreach ((array) $failed as $message) {
                $errors[] = (string) $message;
            }
        }

        return $errors;
    }

    /**
     * Cột theo SKU của nhà cung cấp người dùng được sửa (giá niêm yết, giá gạch, giá vốn…). Kiểu media/video không nhập file.
     *
     * @return list<array{provider: string, field: FieldDefinition, header: string}>
     */
    private function fieldColumns(): array
    {
        $columns = [];
        foreach ($this->providers() as $code => $provider) {
            foreach ($this->extensions->call($provider, fn (): array => $provider->fields(), [], 'catalog.product_file.fields') as $field) {
                if (in_array($field->type, ['media', 'video'], true)) {
                    continue;
                }
                $columns[] = ['provider' => $code, 'field' => $field, 'header' => "{$provider->label()}: {$field->label}"];
            }
        }

        return $columns;
    }

    /**
     * @return array<string, VariantFieldProvider>
     */
    private function providers(): array
    {
        return array_filter(
            $this->extensions->implementations(VariantFieldProvider::TAG, VariantFieldProvider::class),
            fn (VariantFieldProvider $provider): bool => Gate::allows($provider->permission() ?? 'catalog.manage'),
        );
    }

    /**
     * @param  list<array{provider: string, field: FieldDefinition, header: string}>  $fields
     * @param  list<int>  $variantIds
     * @return array<int, array<string, array<string, scalar|null>>> variant id => provider => field => giá trị
     */
    private function fieldValues(array $fields, int $styleId, array $variantIds): array
    {
        $result = [];
        if ($variantIds === []) {
            return $result;
        }
        foreach ($this->providers() as $code => $provider) {
            if (! in_array($code, array_column($fields, 'provider'), true)) {
                continue;
            }
            foreach ($this->extensions->call($provider, fn (): array => $provider->values($styleId, $variantIds), [], 'catalog.product_file.values') as $variantId => $values) {
                $result[(int) $variantId][$code] = $values;
            }
        }

        return $result;
    }

    /** Giá trị ô theo kiểu trường; null = không đọc được. */
    private function fieldValue(FieldDefinition $field, string $raw): string|int|bool|null
    {
        return match ($field->type) {
            'int' => ctype_digit($digits = (string) preg_replace('/[.,\s]|đ|₫|vnd/iu', '', $raw)) ? (int) $digits : null,
            'bool' => match (Str::lower(Str::ascii($raw))) {
                '1', 'co', 'yes', 'true', 'x' => true,
                '0', 'khong', 'no', 'false' => false,
                default => null,
            },
            'select' => array_key_exists($raw, $field->options) ? $raw : (($key = array_search($raw, $field->options, true)) === false ? null : (string) $key),
            default => $raw,
        };
    }

    /** Trạng thái theo mã, nhãn Admin hoặc cách gọi quen của nhân viên ("Ngừng bán", "Mở bán"). */
    private function status(string $value): ?StyleStatus
    {
        $normalized = Str::lower(Str::ascii($value));
        $aliases = ['ngung ban' => StyleStatus::Archived, 'mo ban' => StyleStatus::Active];
        if (isset($aliases[$normalized])) {
            return $aliases[$normalized];
        }
        foreach (StyleStatus::cases() as $status) {
            if ($normalized === $status->value || $normalized === Str::lower(Str::ascii((string) __("catalog::labels.product_status.{$status->value}")))) {
                return $status;
            }
        }

        return null;
    }

    private function findValue(OptionType $type, string $label): ?OptionValue
    {
        return OptionValue::query()->where('option_type_id', $type->id)->whereRaw('LOWER(code) = ?', [Str::lower($label)])->first()
            ?? OptionValue::query()->where('option_type_id', $type->id)->whereHas('translations', fn ($query) => $query->whereRaw('LOWER(name) = ?', [mb_strtolower($label)]))->first();
    }

    private function value(int $typeId, string $label): OptionValue
    {
        $type = OptionType::query()->findOrFail($typeId);
        if (($existing = $this->findValue($type, $label)) !== null) {
            return $existing;
        }
        $base = Str::limit(Str::slug($label) ?: Str::lower($label), 60, '');
        $code = $base;
        for ($suffix = 2; OptionValue::query()->where('option_type_id', $type->id)->where('code', $code)->exists(); $suffix++) {
            $code = "{$base}-{$suffix}";
        }

        return $this->optionTypes->saveValue($type, [
            'code' => $code, 'group' => null, 'hex' => null, 'family' => null,
            'position' => (int) OptionValue::query()->where('option_type_id', $type->id)->max('position') + 1,
            'translations' => [StoreLocale::default() => ['name' => $label]],
        ]);
    }

    private function brand(string $name): Brand
    {
        $existing = Brand::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($existing !== null) {
            return $existing;
        }
        $slug = Str::slug($name) ?: 'brand';
        $base = Str::limit(Str::upper(Str::slug($name, '-')) ?: 'BRAND', 28, '');
        $code = $base;
        for ($suffix = 2; Brand::query()->where('code', $code)->orWhere('slug', $slug)->exists(); $suffix++) {
            $code = "{$base}-{$suffix}";
            $slug = Str::slug($name)."-{$suffix}";
        }

        return $this->taxonomy->saveBrand(['code' => $code, 'slug' => $slug, 'name' => $name, 'description' => null, 'status' => 'active', 'position' => (int) Brand::query()->max('position') + 1]);
    }

    /**
     * @param  list<string>  $header
     * @param  list<string>  $names
     */
    private function column(array $header, array $names): ?int
    {
        foreach ($header as $index => $cell) {
            if (in_array($cell, $names, true)) {
                return $index;
            }
        }

        return null;
    }
}
