<?php

declare(strict_types=1);

namespace Modules\Channel\Persistence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Channel\Persistence\Models\Channel;

/**
 * @extends Factory<Channel>
 */
final class ChannelFactory extends Factory
{
    protected $model = Channel::class;

    public function definition(): array
    {
        return [
            'code' => fake()->unique()->lexify('web-?????'),
            'name' => 'Website '.fake()->word(),
            'type' => 'web',
            'locale' => 'vi',
            'currency_code' => 'VND',
            'status' => 'active',
        ];
    }

    /**
     * Gắn brand và domain cho kênh.
     */
    public function forBrand(Brand $brand, string $host, string $pathPrefix = ''): self
    {
        return $this->afterCreating(function (Channel $channel) use ($brand, $host, $pathPrefix): void {
            $channel->brands()->attach($brand->id);
            $channel->domains()->create(['host' => $host, 'path_prefix' => $pathPrefix, 'is_primary' => true]);
        });
    }
}
