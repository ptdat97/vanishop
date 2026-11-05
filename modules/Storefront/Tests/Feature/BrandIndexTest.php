<?php

use Modules\Catalog\Persistence\Models\Brand;
use Modules\Storefront\Application\BrandIndex;

it('nhóm theo chữ cái đầu: bỏ dấu (Ấ → A), Đ nhóm riêng ngay sau D, ký tự khác vào # ở cuối; trong nhóm theo tên', function () {
    $groups = BrandIndex::group(array_map(fn (string $name): array => ['name' => $name], ['Đông Á', 'urbanx', 'Ấn tượng', 'Dior', 'an nhiên', '7Days', 'Ba Lan']));

    expect(array_keys($groups))->toBe(['A', 'B', 'D', 'Đ', 'U', '#'])
        ->and(array_column($groups['A'], 'name'))->toBe(['an nhiên', 'Ấn tượng'])
        ->and(array_column($groups['Đ'], 'name'))->toBe(['Đông Á']);
});

it('trang /thuong-hieu có thanh chữ cái và nhóm', function () {
    Brand::factory()->create(['slug' => 'dong-a', 'name' => 'Đông Á']);
    Brand::factory()->create(['slug' => 'lumiere', 'name' => 'Lumière']);

    $this->get('/thuong-hieu')->assertOk()
        ->assertSee('data-brand-letters', false)
        ->assertSee('data-brand-letter="Đ"', false)
        ->assertSee('data-brand-letter="L"', false);
});
