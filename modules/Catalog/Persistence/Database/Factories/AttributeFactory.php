<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Persistence\Models\Attribute;

/**
 * @extends Factory<Attribute>
 */
final class AttributeFactory extends Factory
{
    protected $model = Attribute::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('attr_?????'),
            'kind' => 'spec',
            'input_type' => 'text',
            'is_filterable' => false,
            'position' => 0,
        ];
    }

    public function configure(): self
    {
        return $this->afterCreating(function (Attribute $attribute): void {
            if ($attribute->translations()->doesntExist()) {
                $attribute->syncTranslations(['vi' => ['name' => ucfirst($attribute->code)]]);
            }
        });
    }

    public function internal(): self
    {
        return $this->state(['kind' => 'internal']);
    }
}
