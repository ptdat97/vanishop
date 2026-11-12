<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Persistence\Models\OptionType;
use Modules\Catalog\Persistence\Models\OptionValue;

/**
 * Giá trị tuỳ chọn. Trạng thái `color()` / `size()` gắn vào loại có sẵn `color` / `size` (ADR-045).
 *
 * @extends Factory<OptionValue>
 */
final class OptionValueFactory extends Factory
{
    protected $model = OptionValue::class;

    public function definition(): array
    {
        return ['option_type_id' => OptionType::factory(), 'code' => strtoupper(fake()->unique()->lexify('V???')), 'group' => null, 'hex' => null, 'family' => null, 'position' => 0];
    }

    public function color(): self
    {
        return $this->state(fn (): array => [
            'option_type_id' => self::builtIn(OptionType::COLOR, 'swatch', 'Màu'),
            'code' => strtoupper(fake()->unique()->lexify('???')), 'hex' => '#FFFFF0', 'family' => 'white',
        ]);
    }

    public function size(): self
    {
        return $this->state(fn (): array => [
            'option_type_id' => self::builtIn(OptionType::SIZE, 'button', 'Size'),
            'code' => strtoupper(fake()->unique()->lexify('S??')), 'group' => 'alpha', 'position' => fake()->numberBetween(1, 100),
        ]);
    }

    /** Loại có sẵn do migration tạo; test xoá sạch bảng (truncate) thì tạo lại. */
    private static function builtIn(string $code, string $displayType, string $name): int
    {
        $type = OptionType::query()->firstOrCreate(['code' => $code], ['display_type' => $displayType, 'is_filterable' => true]);
        if ($type->wasRecentlyCreated) {
            $type->syncTranslations(['vi' => ['name' => $name]]);
        }

        return $type->id;
    }

    public function configure(): self
    {
        return $this->afterCreating(function (OptionValue $value): void {
            if ($value->translations()->doesntExist() && $value->hex !== null) {
                $value->syncTranslations(['vi' => ['name' => 'Màu '.$value->code]]);
            }
        });
    }
}
