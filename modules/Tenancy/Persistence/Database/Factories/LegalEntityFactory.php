<?php

declare(strict_types=1);

namespace Modules\Tenancy\Persistence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Tenancy\Persistence\Models\LegalEntity;

/**
 * @extends Factory<LegalEntity>
 */
final class LegalEntityFactory extends Factory
{
    protected $model = LegalEntity::class;

    public function definition(): array
    {
        return [
            'code' => strtoupper(fake()->unique()->bothify('LE###??')),
            'name' => 'Công ty '.fake()->company(),
            'tax_code' => fake()->unique()->numerify('01########'),
            'status' => 'active',
        ];
    }
}
