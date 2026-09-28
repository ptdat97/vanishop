<?php

declare(strict_types=1);

namespace Modules\Brand\Persistence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Tenancy\Persistence\Models\LegalEntity;

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
            'legal_entity_id' => LegalEntity::factory(),
            'code' => strtoupper(Str::substr(Str::slug($name, ''), 0, 6)).fake()->unique()->numberBetween(10, 99),
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'status' => 'active',
        ];
    }
}
