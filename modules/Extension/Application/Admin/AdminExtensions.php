<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Admin;

use Closure;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Contracts\AdminScreen;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Throwable;

/**
 * Registry phần mở rộng màn hình Admin của Core do plugin khai báo (ADR-030 §4.A). Plugin đăng ký qua
 * PluginServiceProvider::adminFormSection/adminColumn/adminAction/adminTab/adminFilter; chỉ plugin đang bật được dùng.
 * Đọc (giá trị form, cột, tab, lựa chọn lọc) lỗi → bỏ phần của plugin đó + log; lưu form lỗi → rollback.
 */
final class AdminExtensions implements AdminScreen
{
    /** @var array<string, array<string, array<string, mixed>>> loại => "resource|plugin|key" => định nghĩa */
    private array $items = ['section' => [], 'column' => [], 'action' => [], 'tab' => [], 'filter' => []];

    /** @var array<string, true> */
    private array $resources = [];

    /**
     * @param  Closure(): PluginActivation  $activation
     */
    public function __construct(private readonly Closure $activation) {}

    public function declareResource(string $resource): void
    {
        if (preg_match('/^[a-z][a-z0-9_]{0,63}$/', $resource) !== 1) {
            throw new \InvalidArgumentException("Tài nguyên Admin [{$resource}] không hợp lệ.");
        }

        $this->resources[$resource] = true;
    }

    public function resources(): array
    {
        return array_keys($this->resources);
    }

    /**
     * @param  list<FieldDefinition>  $fields
     * @param  Closure(int): array<string, mixed>  $load
     * @param  Closure(int, array<string, mixed>): void  $save
     */
    public function section(string $resource, string $plugin, string $key, string $label, array $fields, Closure $load, Closure $save, int $order = 500): void
    {
        $this->put('section', $resource, $plugin, $key, compact('label', 'fields', 'load', 'save', 'order'));
    }

    /**
     * @param  Closure(list<int>): array<int, scalar|null>  $resolve
     */
    public function column(string $resource, string $plugin, string $key, string $label, Closure $resolve, int $order = 500): void
    {
        $this->put('column', $resource, $plugin, $key, compact('label', 'resolve', 'order'));
    }

    /**
     * @param  Closure(int): (string|null)  $handle  trả thông báo (tuỳ chọn)
     * @param  'detail'|'bulk'|'both'  $scope
     */
    public function action(string $resource, string $plugin, string $key, string $label, string $permission, Closure $handle, string $scope = 'detail', bool $confirm = false, int $order = 500): void
    {
        $this->put('action', $resource, $plugin, $key, compact('label', 'permission', 'handle', 'scope', 'confirm', 'order'));
    }

    /**
     * @param  Closure(int): list<array{label: string, value: string}>  $rows
     */
    public function tab(string $resource, string $plugin, string $key, string $label, Closure $rows, int $order = 500): void
    {
        $this->put('tab', $resource, $plugin, $key, compact('label', 'rows', 'order'));
    }

    /**
     * @param  array<string, string>|Closure(): array<string, string>  $options
     * @param  Closure(string): list<int>  $apply  giá trị chọn → id thoả
     */
    public function filter(string $resource, string $plugin, string $key, string $label, array|Closure $options, Closure $apply, int $order = 500): void
    {
        $this->put('filter', $resource, $plugin, $key, compact('label', 'options', 'apply', 'order'));
    }

    public function formSections(string $resource, ?int $id): array
    {
        $sections = [];
        foreach ($this->active('section', $resource) as $item) {
            $values = $id === null ? [] : $this->guard($item, 'load', fn (): array => (array) ($item['load'])($id), []);
            $sections[] = [
                'plugin' => $item['plugin'], 'input' => self::inputKey($item['plugin']), 'key' => $item['key'], 'label' => $item['label'],
                'fields' => array_map(fn (FieldDefinition $field): array => $field->toArray(), $item['fields']),
                'values' => $values,
            ];
        }

        return $sections;
    }

    public function validationRules(string $resource): array
    {
        $rules = [];
        foreach ($this->active('section', $resource) as $item) {
            foreach ($item['fields'] as $field) {
                $rules['extensions.'.self::inputKey($item['plugin']).".{$item['key']}.{$field->key}"] = $field->rules();
            }
        }

        return $rules;
    }

    public function saving(string $resource, array $input, Closure $save, Closure $idOf): mixed
    {
        return DB::transaction(function () use ($resource, $input, $save, $idOf): mixed {
            $result = $save();
            $id = $idOf($result);
            foreach ($this->active('section', $resource) as $item) {
                $values = (array) ($input[self::inputKey($item['plugin'])][$item['key']] ?? []);
                $known = array_map(fn (FieldDefinition $field): string => $field->key, $item['fields']);
                ($item['save'])($id, array_intersect_key($values, array_flip($known)));
            }

            return $result;
        });
    }

    public function columns(string $resource, array $ids): array
    {
        $columns = [];
        $values = array_fill_keys($ids, []);
        foreach ($this->active('column', $resource) as $item) {
            $key = "{$item['plugin']}:{$item['key']}";
            $resolved = $ids === [] ? [] : $this->guard($item, 'resolve', fn (): array => (array) ($item['resolve'])($ids), null);
            if ($resolved === null) {
                continue;
            }
            $columns[] = ['key' => $key, 'label' => $item['label']];
            foreach ($ids as $id) {
                $value = $resolved[$id] ?? null;
                $values[$id][$key] = is_scalar($value) ? $value : null;
            }
        }

        return ['columns' => $columns, 'values' => $values];
    }

    public function filters(string $resource): array
    {
        $filters = [];
        foreach ($this->active('filter', $resource) as $item) {
            $options = $item['options'] instanceof Closure ? $this->guard($item, 'options', fn (): array => (array) ($item['options'])(), null) : $item['options'];
            if ($options !== null) {
                $filters[] = ['key' => "{$item['plugin']}:{$item['key']}", 'label' => $item['label'], 'options' => array_map('strval', $options)];
            }
        }

        return $filters;
    }

    public function filterIds(string $resource, array $values): ?array
    {
        $ids = null;
        foreach ($this->active('filter', $resource) as $item) {
            $value = $values["{$item['plugin']}:{$item['key']}"] ?? null;
            if (! is_string($value) || $value === '') {
                continue;
            }
            $matched = array_map('intval', (array) ($item['apply'])($value));
            $ids = $ids === null ? $matched : array_values(array_intersect($ids, $matched));
        }

        return $ids;
    }

    public function actions(string $resource, string $scope): array
    {
        $actions = [];
        foreach ($this->active('action', $resource) as $item) {
            if (($item['scope'] === $scope || $item['scope'] === 'both') && Gate::allows($item['permission'])) {
                $actions[] = [
                    'key' => "{$item['plugin']}:{$item['key']}", 'label' => $item['label'], 'confirm' => $item['confirm'],
                    'url' => route('admin.extensions.action', ['resource' => $resource, 'plugin' => $item['plugin'], 'key' => $item['key']]),
                ];
            }
        }

        return $actions;
    }

    public function tabs(string $resource, int $id): array
    {
        $tabs = [];
        foreach ($this->active('tab', $resource) as $item) {
            $rows = $this->guard($item, 'rows', fn (): array => (array) ($item['rows'])($id), null);
            if ($rows !== null) {
                $tabs[] = ['key' => "{$item['plugin']}:{$item['key']}", 'label' => $item['label'], 'rows' => array_values(array_map(
                    fn (array $row): array => ['label' => (string) ($row['label'] ?? ''), 'value' => (string) ($row['value'] ?? '')], $rows,
                ))];
            }
        }

        return $tabs;
    }

    /**
     * Khoá input của plugin trong form (`extensions.<khoá>.<phần>.<trường>`): id plugin có dấu chấm, mà dấu chấm là
     * phân cấp trong validate/lỗi của Laravel → dùng dạng slug `vani-hello-world` (như đường dẫn Admin của plugin).
     */
    public static function inputKey(string $plugin): string
    {
        return str_replace('.', '-', $plugin);
    }

    /**
     * Thao tác đang bật theo khoá (cho controller thực thi).
     *
     * @return array<string, mixed>|null
     */
    public function findAction(string $resource, string $plugin, string $key): ?array
    {
        $item = $this->items['action']["{$resource}|{$plugin}|{$key}"] ?? null;

        return $item !== null && $this->isActive($item['plugin']) ? $item : null;
    }

    /**
     * @param  array<string, mixed>  $definition
     */
    private function put(string $type, string $resource, string $plugin, string $key, array $definition): void
    {
        if (! isset($this->resources[$resource])) {
            throw new \InvalidArgumentException("Tài nguyên Admin [{$resource}] chưa hỗ trợ mở rộng.");
        }
        if (preg_match('/^[a-z][a-z0-9_.-]{0,63}$/', $key) !== 1) {
            throw new \InvalidArgumentException("Khoá [{$key}] không hợp lệ.");
        }

        $this->items[$type]["{$resource}|{$plugin}|{$key}"] = ['resource' => $resource, 'plugin' => $plugin, 'key' => $key, ...$definition];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function active(string $type, string $resource): array
    {
        $items = array_values(array_filter($this->items[$type], fn (array $item): bool => $item['resource'] === $resource && $this->isActive($item['plugin'])));
        usort($items, fn (array $a, array $b): int => [$a['order'], $a['plugin'], $a['key']] <=> [$b['order'], $b['plugin'], $b['key']]);

        return $items;
    }

    private function isActive(string $plugin): bool
    {
        return ($this->activation)()->isActive($plugin);
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function guard(array $item, string $operation, Closure $call, mixed $fallback): mixed
    {
        try {
            return $call();
        } catch (Throwable $exception) {
            report($exception);
            Log::warning('Phần mở rộng Admin của plugin lỗi, bỏ qua.', ['plugin' => $item['plugin'], 'resource' => $item['resource'], 'key' => $item['key'], 'operation' => $operation]);

            return $fallback;
        }
    }
}
