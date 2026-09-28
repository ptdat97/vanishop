<?php

declare(strict_types=1);

namespace Modules\Catalog\Persistence\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Style;

/**
 * Tạo style tối giản (không qua ProductService). Cần CurrentContext cho phép brand.
 *
 * @extends Factory<Style>
 */
final class StyleFactory extends Factory
{
    protected $model = Style::class;

    public function definition(): array
    {
        $code = strtoupper(fake()->unique()->bothify('ST##??##'));

        return [
            'brand_id' => Brand::factory(),
            'style_code' => $code,
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
