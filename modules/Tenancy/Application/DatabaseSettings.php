<?php

declare(strict_types=1);

namespace Modules\Tenancy\Application;

use Illuminate\Support\Facades\Crypt;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Data\SettingsScope;
use Modules\Tenancy\Contracts\Settings;
use Modules\Tenancy\Persistence\Models\SettingRecord;

/**
 * Nạp mỗi namespace một lần mỗi request/job (scoped) rồi tra trong bộ nhớ.
 */
final class DatabaseSettings implements Settings
{
    /** @var array<string, array<string, mixed>> namespace => "scope_type:scope_id:key" => value */
    private array $loaded = [];

    public function __construct(
        private readonly SettingDefinitions $definitions,
        private readonly CurrentContext $context,
    ) {}

    public function get(string $namespace, string $key, SettingsScope $scope, mixed $default = null): mixed
    {
        $values = $this->load($namespace);
        foreach ($scope->chain() as [$type, $id]) {
            if (array_key_exists("{$type}:{$id}:{$key}", $values)) {
                return $values["{$type}:{$id}:{$key}"];
            }
        }

        return $this->definitions->find($namespace, $key)?->default ?? $default;
    }

    public function current(string $namespace, string $key, mixed $default = null): mixed
    {
        return $this->get($namespace, $key, SettingsScope::fromContext($this->context->has() ? $this->context->scope() : null), $default);
    }

    public function set(string $namespace, string $key, mixed $value, string $scopeType, int $scopeId = 0): void
    {
        $secret = $this->definitions->find($namespace, $key)?->type === 'secret';
        $json = (string) json_encode($value, JSON_UNESCAPED_UNICODE);

        SettingRecord::query()->updateOrCreate(
            ['namespace' => $namespace, 'key' => $key, 'scope_type' => $scopeType, 'scope_id' => $scopeType === SettingsScope::OWNER ? 0 : $scopeId],
            ['value' => $secret ? Crypt::encryptString($json) : $json, 'encrypted' => $secret],
        );
        unset($this->loaded[$namespace]);
    }

    public function forget(string $namespace, string $key, string $scopeType, int $scopeId = 0): void
    {
        SettingRecord::query()->where(['namespace' => $namespace, 'key' => $key, 'scope_type' => $scopeType, 'scope_id' => $scopeType === SettingsScope::OWNER ? 0 : $scopeId])->delete();
        unset($this->loaded[$namespace]);
    }

    public function explicit(string $namespace, string $scopeType, int $scopeId = 0): array
    {
        $prefix = "{$scopeType}:".($scopeType === SettingsScope::OWNER ? 0 : $scopeId).':';
        $result = [];
        foreach ($this->load($namespace) as $compound => $value) {
            if (str_starts_with($compound, $prefix)) {
                $result[substr($compound, strlen($prefix))] = $value;
            }
        }

        return $result;
    }

    public function define(SettingDefinition $definition): void
    {
        $this->definitions->add($definition);
    }

    public function definitions(?string $namespace = null): array
    {
        return $this->definitions->all($namespace);
    }

    /**
     * @return array<string, mixed>
     */
    private function load(string $namespace): array
    {
        if (isset($this->loaded[$namespace])) {
            return $this->loaded[$namespace];
        }

        $values = [];
        foreach (SettingRecord::query()->where('namespace', $namespace)->get() as $row) {
            $json = $row->encrypted ? Crypt::decryptString($row->value) : $row->value;
            $values["{$row->scope_type}:{$row->scope_id}:{$row->key}"] = json_decode($json, true);
        }

        return $this->loaded[$namespace] = $values;
    }
}
