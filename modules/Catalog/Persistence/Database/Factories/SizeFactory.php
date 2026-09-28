<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Size;

/**
 * @extends Factory<Size>
 */
final class SizeFactory extends Factory
{
    protected $model = Size::class;

    public function definition(): array
    {
        return [
            'brand_id' => Brand::factory(),
            'size_system' => 'alpha',
            'code' => strtoupper(fake()->unique()->lexify('S??')),
            'sort_order' => fake()->numberBetween(1, 100),
        ];
    }
}
