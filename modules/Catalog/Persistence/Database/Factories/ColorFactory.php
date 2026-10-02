<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Persistence\Models\Color;

/**
 * @extends Factory<Color>
 */
final class ColorFactory extends Factory
{
    protected $model = Color::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->lexify('???')),
            'color_family' => 'white',
            'hex' => '#FFFFF0',
            'position' => 0,
        ];
    }

    public function configure(): self
    {
        return $this->afterCreating(function (Color $color): void {
            if ($color->translations()->doesntExist()) {
                $color->syncTranslations(['vi' => ['name' => 'Màu '.$color->code]]);
            }
        });
    }
}
