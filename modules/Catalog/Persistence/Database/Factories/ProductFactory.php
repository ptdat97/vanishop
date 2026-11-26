<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Catalog\Persistence\Models\Product;

/**
 * Tạo product tối giản (không qua ProductService).
 *
 * @extends Factory<Product>
 */
final class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->bothify('ST##??##'));

        return [
            'brand_id' => null,
            'product_code' => $code,
            'slug' => Str::slug($code),
            'status' => 'active',
            'search_text' => Str::lower($code),
        ];
    }

    public function draft(): self
    {
        return $this->state(['status' => 'draft']);
    }
}
