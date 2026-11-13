<?php

declare(strict_types=1);

namespace Modules\Catalog\Application\Products;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Contracts\VariantFieldProvider;
use Modules\Catalog\Contracts\VariantUsage;
use Modules\Catalog\Domain\VariantStatus;
use Modules\Catalog\Events\ProductUpdated;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Extension\Contracts\Extensions;
use Modules\Identity\Contracts\AuditLogger;

/**
 * Ma trận biến thể màu × size của Admin (docs/03-domains/variant-matrix.md §3): đọc mọi SKU kèm cột do module/plugin góp
 * (`VariantFieldProvider`) và lưu cả lưới một lần — thứ tự màu, tạo/sửa SKU, tắt ô (chưa phát sinh → xoá, đã phát sinh →
 * ngừng bán, theo `VariantUsage`), rồi giá trị cột góp vào.
 */
final class VariantMatrix
{
    public const SKU_PATTERN = '/^[A-Z0-9][A-Z0-9._-]*$/';

    public const BARCODE_PATTERN = '/^[0-9A-Za-z-]+$/';

    public function __construct(
        private readonly Extensions $extensions,
        private readonly AuditLogger $audit,
        private readonly ProductOptions $options,
    ) {}

    /**
     * Cột góp vào và giá trị hiện tại cho màn hình.
     *
     * @param  Closure(string): bool  $can  quyền của người đang xem
     * @return array{columns: list<array{code: string, label: string, editable: bool, fields: list<array<string, mixed>>}>, values: array<int, array<string, array<string, scalar|null>>>}
     */
    public function columns(?Style $style, Closure $can): array
    {
        // Sản phẩm mới (chưa lưu): chỉ có cột, chưa có giá trị.
        $variantIds = $style === null ? [] : $style->variants()->pluck('id')->map(fn ($id): int => (int) $id)->all();
        $columns = [];
        $values = [];
        foreach ($this->providers() as $code => $provider) {
            $fields = $this->fields($provider);
            if ($fields === []) {
                continue;
            }
            $columns[] = [
                'code' => $code, 'label' => $provider->label(), 'editable' => $can($provider->permission() ?? 'catalog.manage'),
                'fields' => array_map(fn (FieldDefinition $field): array => [
                    'key' => $field->key, 'label' => $field->label, 'type' => $field->type, 'required' => $field->required,
                    'options' => $field->options, 'help' => $field->help,
                ], $fields),
            ];
            $provided = $style === null || $variantIds === [] ? [] : (array) $this->extensions->call($provider, fn (): array => $provider->values($style->id, $variantIds), [], 'catalog.variant_fields.values');
            foreach ($provided as $variantId => $row) {
                $values[(int) $variantId][$code] = (array) $row;
            }
        }

        return ['columns' => $columns, 'values' => $values];
    }

    /**
     * Lưu cả lưới (ADR-045). `$input`: axes (1–3: option_type_id, value_ids theo thứ tự), cells (values: id giá trị theo thứ
     * tự trục, enabled, status, sku, barcode, weight_gram, fields: {"<provider>": {"<field>": giá trị}}).
     *
     * @param  array<string, mixed>  $input
     * @param  Closure(string): bool  $can
     * @return array{created: int, updated: int, deleted: int, deactivated: int, field_errors: array<int, string>}
     *
     * @throws ValidationException
     */
    public function save(Style $style, array $input, Closure $can): array
    {
        $style->load('variants.optionValues');
        $existing = $style->variants->keyBy(fn (Variant $variant): string => self::canonical($variant->optionValues->mapWithKeys(fn ($value): array => [$value->option_type_id => $value->id])->all()));
        $providers = array_filter($this->providers(), fn (VariantFieldProvider $provider): bool => $can($provider->permission() ?? 'catalog.manage'));
        [$axes, $cells] = $this->validate($input, $existing->all(), $providers);
        $typeIds = array_column($axes, 'option_type_id');

        $result = DB::transaction(function () use ($style, $axes, $cells, $existing, $typeIds): array {
            // Tắt ô trước khi đổi trục: đổi tập loại tuỳ chọn chỉ được khi không còn SKU nào.
            $toRemove = [];
            foreach ($cells as $cell) {
                $variant = $existing->get(self::canonical(array_combine($typeIds, $cell['values'])));
                if (! $cell['enabled'] && $variant !== null) {
                    $toRemove[] = $variant->id;
                }
            }
            [$deleted, $deactivated] = $this->remove($toRemove);
            $this->options->syncAxes($style, $axes);

            $created = $updated = 0;
            $variantIdsByCell = [];
            foreach ($cells as $index => $cell) {
                if (! $cell['enabled']) {
                    continue;
                }
                $variant = $existing->get(self::canonical(array_combine($typeIds, $cell['values'])));
                $data = ['sku' => $cell['sku'], 'barcode' => $cell['barcode'], 'status' => $cell['status'], 'weight_gram' => $cell['weight_gram']];
                if ($variant === null || in_array($variant->id, $toRemove, true)) {
                    $variant = $this->options->createVariant($style, $cell['values'], $data);
                    $created++;
                } elseif ($variant->fill($data)->isDirty()) {
                    $variant->save();
                    $updated++;
                }
                $variantIdsByCell[$index] = $variant->id;
            }

            $this->audit->record('catalog.variants.matrix_saved', 'style', $style->id, compact('created', 'updated', 'deleted', 'deactivated'));
            event(new ProductUpdated($style->id, $style->style_code));

            return ['created' => $created, 'updated' => $updated, 'deleted' => $deleted, 'deactivated' => $deactivated, 'variant_ids' => $variantIdsByCell];
        });

        // Cột góp vào lưu sau phần Catalog: lỗi của một nhà cung cấp báo theo SKU, không làm mất phần đã lưu.
        $fieldErrors = [];
        foreach ($providers as $code => $provider) {
            $values = [];
            foreach ($cells as $index => $cell) {
                $variantId = $result['variant_ids'][$index] ?? null;
                $own = (array) ($cell['fields'][$code] ?? []);
                if ($variantId !== null && $own !== []) {
                    $values[$variantId] = $own;
                }
            }
            if ($values !== []) {
                $errors = $this->extensions->call($provider, fn (): array => $provider->save($style->id, $values), array_fill_keys(array_keys($values), __('catalog::messages.variant_field_failed', ['label' => $provider->label()])), 'catalog.variant_fields.save');
                foreach ((array) $errors as $variantId => $message) {
                    $fieldErrors[(int) $variantId] = trim(($fieldErrors[(int) $variantId] ?? '').' '.$message);
                }
            }
        }

        return [...array_diff_key($result, ['variant_ids' => true]), 'field_errors' => $fieldErrors];
    }

    /**
     * Khoá tổ hợp không phụ thuộc thứ tự trục: "loại:giá trị|…" sắp theo id loại.
     *
     * @param  array<int, int>  $valueByType
     */
    private static function canonical(array $valueByType): string
    {
        ksort($valueByType);

        return implode('|', array_map(fn (int $type, int $value): string => "{$type}:{$value}", array_keys($valueByType), $valueByType));
    }

    /**
     * @return array<string, VariantFieldProvider>
     */
    public function providers(): array
    {
        return $this->extensions->implementations(VariantFieldProvider::TAG, VariantFieldProvider::class);
    }

    /**
     * @return list<FieldDefinition>
     */
    private function fields(VariantFieldProvider $provider): array
    {
        return $this->extensions->call($provider, fn (): array => $provider->fields(), [], 'catalog.variant_fields.fields');
    }

    /**
     * @param  array<string, mixed>  $input
     * @param  array<string, Variant>  $existing  khoá tổ hợp chuẩn => SKU
     * @param  array<string, VariantFieldProvider>  $providers  nhà cung cấp người lưu được sửa
     * @return array{0: list<array{option_type_id: int, value_ids: list<int>}>, 1: list<array{values: list<int>, enabled: bool, status: string, sku: string, barcode: string|null, weight_gram: int|null, fields: array<string, array<string, scalar|null>>}>}
     */
    private function validate(array $input, array $existing, array $providers): array
    {
        $rules = [
            'axes' => ['required', 'array', 'min:1', 'max:'.ProductOptions::MAX_AXES],
            'axes.*.option_type_id' => ['required', 'integer', Rule::exists('option_types', 'id')],
            'axes.*.value_ids' => ['required', 'array', 'min:1', 'max:100'],
            'axes.*.value_ids.*' => ['integer', 'distinct'],
            'cells' => ['present', 'array', 'max:1000'],
            'cells.*.values' => ['required', 'array'],
            'cells.*.values.*' => ['integer'],
            'cells.*.enabled' => ['required', 'boolean'],
            'cells.*.status' => ['required_if:cells.*.enabled,true', 'nullable', Rule::enum(VariantStatus::class)],
            'cells.*.sku' => ['required_if:cells.*.enabled,true', 'nullable', 'string', 'max:64', 'regex:'.self::SKU_PATTERN],
            'cells.*.barcode' => ['nullable', 'string', 'max:32', 'regex:'.self::BARCODE_PATTERN],
            'cells.*.weight_gram' => ['nullable', 'integer', 'min:0', 'max:100000'],
            'cells.*.fields' => ['nullable', 'array'],
        ];
        $labels = [];
        foreach ($providers as $code => $provider) {
            foreach ($this->fields($provider) as $field) {
                $rules["cells.*.fields.{$code}.{$field->key}"] = array_map(fn ($rule) => $rule === 'required' ? 'nullable' : $rule, $field->rules());
                $labels["cells.*.fields.{$code}.{$field->key}"] = $field->label;
            }
        }
        // SKU nhập tay: bỏ khoảng trắng, viết hoa trước khi kiểm tra dạng.
        $input['cells'] = array_map(fn ($cell) => is_array($cell) && is_string($cell['sku'] ?? null) ? [...$cell, 'sku' => strtoupper(trim($cell['sku']))] : $cell, (array) ($input['cells'] ?? []));
        $data = Validator::make($input, $rules, [], $labels)->validate();

        $axes = array_map(fn (array $axis): array => ['option_type_id' => (int) $axis['option_type_id'], 'value_ids' => array_values(array_map('intval', $axis['value_ids']))], array_values($data['axes']));
        $typeIds = array_column($axes, 'option_type_id');
        $cells = [];
        $seen = [];
        $errors = [];
        foreach ($data['cells'] as $index => $cell) {
            $values = array_values(array_map('intval', $cell['values']));
            $valid = count($values) === count($axes);
            foreach ($values as $position => $valueId) {
                $valid = $valid && in_array($valueId, $axes[$position]['value_ids'] ?? [], true);
            }
            if (! $valid) {
                $errors["cells.{$index}.values"] = __('catalog::messages.option_value_invalid');

                continue;
            }
            $key = self::canonical(array_combine($typeIds, $values));
            if (isset($seen[$key])) {
                $errors["cells.{$index}.values"] = __('catalog::messages.variant_cell_duplicate');
            }
            $seen[$key] = true;
            $cells[$index] = [
                'values' => $values, 'enabled' => (bool) $cell['enabled'],
                'status' => (string) ($cell['status'] ?? VariantStatus::Active->value), 'sku' => strtoupper(trim((string) ($cell['sku'] ?? ''))),
                'barcode' => ($barcode = trim((string) ($cell['barcode'] ?? ''))) === '' ? null : $barcode,
                'weight_gram' => isset($cell['weight_gram']) ? (int) $cell['weight_gram'] : null,
                // Chỉ giữ trường của nhà cung cấp người lưu được sửa: {"<provider>": {"<field>": giá trị}}.
                'fields' => array_intersect_key(array_filter((array) ($cell['fields'] ?? []), 'is_array'), $providers),
            ];
        }

        // SKU không trùng trong lưới và với SKU của sản phẩm khác.
        $skus = [];
        foreach ($cells as $index => $cell) {
            if (! $cell['enabled']) {
                continue;
            }
            if (isset($skus[$cell['sku']])) {
                $errors["cells.{$index}.sku"] = __('catalog::messages.sku_taken', ['sku' => $cell['sku']]);
            }
            $skus[$cell['sku']] = $existing[self::canonical(array_combine($typeIds, $cell['values']))]->id ?? 0;
        }
        $taken = $skus === [] ? collect() : Variant::query()->withoutGlobalScopes()->whereIn('sku', array_keys($skus))->pluck('id', 'sku');
        foreach ($cells as $index => $cell) {
            if ($cell['enabled'] && $taken->has($cell['sku']) && (int) $taken[$cell['sku']] !== $skus[$cell['sku']]) {
                $errors["cells.{$index}.sku"] = __('catalog::messages.sku_taken', ['sku' => $cell['sku']]);
            }
        }
        if ($errors !== []) {
            throw ValidationException::withMessages($errors);
        }

        return [$axes, $cells];
    }

    /**
     * Ô bị tắt: SKU đã phát sinh ở bất kỳ nhà cung cấp `VariantUsage` nào → ngừng bán; còn lại → nhà cung cấp dọn dữ liệu
     * phụ thuộc rồi xoá hẳn.
     *
     * @param  list<int>  $variantIds
     * @return array{0: int, 1: int} [đã xoá, đã ngừng bán]
     */
    private function remove(array $variantIds): array
    {
        if ($variantIds === []) {
            return [0, 0];
        }
        $usages = $this->extensions->implementations(VariantUsage::TAG, VariantUsage::class);
        $used = [];
        foreach ($usages as $usage) {
            array_push($used, ...array_map('intval', (array) $this->extensions->call($usage, fn (): array => $usage->used($variantIds), $variantIds, 'catalog.variant_usage.used')));
        }
        $keep = array_values(array_intersect($variantIds, $used));
        $delete = array_values(array_diff($variantIds, $used));

        Variant::query()->whereIn('id', $keep)->update(['status' => VariantStatus::Inactive]);
        if ($delete !== []) {
            foreach ($usages as $usage) {
                $usage->release($delete);
            }
            Variant::query()->whereIn('id', $delete)->delete();
        }

        return [count($delete), count($keep)];
    }
}
