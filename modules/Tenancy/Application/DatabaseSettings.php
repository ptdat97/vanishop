<?php

declare(strict_types=1);

namespace Modules\Tenancy\Application;

use Illuminate\Support\Facades\Crypt;
use Modules\Tenancy\Contracts\Data\SettingDefinition;
use Modules\Tenancy\Contracts\Settings;
use Modules\Tenancy\Persistence\Models\SettingRecord;

/**
 * Nạp mỗi namespace một lần mỗi request/job (scoped) rồi tra trong bộ nhớ.
 */
final class DatabaseSettings implements Settings
{
    /** @var array<string, array<string, mixed>> namespace => key => value */
    private array $loaded = [];

    public function __construct(private readonly SettingDefinitions $definitions) {}

    public function get(string $namespace, string $key, mixed $default = null): mixed
    {
        $values = $this->load($namespace);

        if (array_key_exists($key, $values)) {
            return $values[$key];
        }

        return $this->definitions->find($namespace, $key)?->default ?? $default;
    }

    public function set(string $namespace, string $key, mixed $value): void
    {
        $secret = $this->definitions->find($namespace, $key)?->type === 'secret';
        $json = (string) json_encode($value, JSON_UNESCAPED_UNICODE);

        SettingRecord::query()->updateOrCreate(
            ['namespace' => $namespace, 'key' => $key],
            ['value' => $secret ? Crypt::encryptString($json) : $json, 'encrypted' => $secret],
        );
        unset($this->loaded[$namespace]);
    }

    public function forget(string $namespace, string $key): void
    {
        SettingRecord::query()->where(['namespace' => $namespace, 'key' => $key])->delete();
        unset($this->loaded[$namespace]);
    }

    public function explicit(string $namespace): array
    {
        return $this->load($namespace);
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
            $values[$row->key] = json_decode($json, true);
        }

        return $this->loaded[$namespace] = $values;
    }
}
