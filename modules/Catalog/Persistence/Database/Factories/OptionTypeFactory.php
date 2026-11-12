<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Persistence\Models\OptionType;

/**
 * @extends Factory<OptionType>
 */
final class OptionTypeFactory extends Factory
{
    protected $model = OptionType::class;

    public function definition(): array
    {
        return ['code' => strtolower(fake()->unique()->lexify('opt_????')), 'display_type' => 'button', 'is_filterable' => false, 'position' => 10];
    }

    public function configure(): self
    {
        return $this->afterCreating(function (OptionType $type): void {
            if ($type->translations()->doesntExist()) {
                $type->syncTranslations(['vi' => ['name' => ucfirst($type->code)]]);
            }
        });
    }
}
