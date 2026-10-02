<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Catalog\Persistence\Models\Brand;

/**
 * @extends Factory<Brand>
 */
final class BrandFactory extends Factory
{
    protected $model = Brand::class;

    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'code' => strtoupper(Str::substr(Str::slug($name, ''), 0, 6)).fake()->unique()->numberBetween(10, 99),
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'name' => $name,
            'status' => Brand::ACTIVE,
            'position' => 0,
        ];
    }
}
