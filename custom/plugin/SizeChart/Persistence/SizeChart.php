<?php

declare(strict_types=1);

namespace Plugin\SizeChart\Persistence;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property array<string, string> $names
 * @property string $base_unit
 * @property string|null $display_unit
 * @property list<array<string, mixed>> $blocks
 * @property bool $advisor_enabled
 * @property list<array<string, mixed>> $advisor_rules
 * @property bool $is_default
 * @property bool $active
 * @property int $lock_version
 */
final class SizeChart extends Model
{
    protected $table = 'plg_size_charts';

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'names' => 'array',
            'blocks' => 'array',
            'advisor_rules' => 'array',
            'advisor_enabled' => 'boolean',
            'is_default' => 'boolean',
            'active' => 'boolean',
            'lock_version' => 'integer',
        ];
    }

    public function name(string $locale, string $fallback): string
    {
        return (string) ($this->names[$locale] ?? $this->names[$fallback] ?? array_values($this->names)[0] ?? '');
    }
}
