<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Catalog\Persistence\Models\LinkType;

/**
 * @extends Factory<LinkType>
 */
final class LinkTypeFactory extends Factory
{
    protected $model = LinkType::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('link-????'),
            'is_active' => true,
            'position' => 0,
        ];
    }

    public function inactive(): self
    {
        return $this->state(['is_active' => false]);
    }

    public function configure(): self
    {
        return $this->afterCreating(function (LinkType $type): void {
            if ($type->translations()->doesntExist()) {
                $type->syncTranslations(['vi' => ['name' => 'Liên kết '.$type->code]]);
            }
        });
    }
}
