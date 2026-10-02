<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Identity\Persistence\Models\AuditLog;

require_once __DIR__.'/CatalogTestHelpers.php';

function categoryPayload(array $overrides = []): array
{
    return array_replace_recursive([
        'slug' => 'ao-so-mi',
        'parent_id' => null,
        'status' => 'active',
        'position' => 0,
        'translations' => ['vi' => ['name' => 'Áo sơ mi'], 'en' => ['name' => 'Shirts']],
    ], $overrides);
}

beforeEach(function () {
    $this->brand = Brand::factory()->create(['slug' => 'lumiere']);
    $this->staff = T::staff();
    $this->base = '/admin/catalog/categories';
});

it('tạo danh mục gốc và danh mục con với path/depth đúng', function () {
    $this->actingAs($this->staff, 'staff')->post($this->base, categoryPayload())->assertRedirect();
    $root = T::seed(fn () => Category::query()->where('slug', 'ao-so-mi')->sole());

    $this->post($this->base, categoryPayload(['slug' => 'so-mi-lua', 'parent_id' => $root->id]))->assertRedirect();
    $child = T::seed(fn () => Category::query()->where('slug', 'so-mi-lua')->sole());

    expect($root->path)->toBe("/{$root->id}/")
        ->and($child->path)->toBe("/{$root->id}/{$child->id}/")
        ->and($child->depth)->toBe(2)
        ->and(T::seed(fn () => $root->translate('name', 'en')))->toBe('Shirts')
        ->and(AuditLog::query()->where('action', 'catalog.category.created')->count())->toBe(2);
});

it('slug danh mục duy nhất trong cửa hàng', function () {
    $this->actingAs($this->staff, 'staff')->post($this->base, categoryPayload())->assertSessionHasNoErrors();
    $this->post($this->base, categoryPayload())->assertSessionHasErrors('slug');
});

it('validate dữ liệu danh mục', function (array $payload, string $field) {
    $this->actingAs($this->staff, 'staff')->post($this->base, categoryPayload($payload))->assertSessionHasErrors($field);
})->with([
    'thiếu tên tiếng Việt' => [['translations' => ['vi' => ['name' => '']]], 'translations.vi.name'],
    'slug có dấu' => [['slug' => 'áo-sơ-mi'], 'slug'],
    'trạng thái lạ' => [['status' => 'deleted'], 'status'],
]);

it('di chuyển danh mục kéo theo cả cây con', function () {
    [$a, $b] = T::seed(fn () => Category::factory()->count(2)->create());
    $child = T::seed(fn () => Category::factory()->childOf($a)->create());
    $grandchild = T::seed(fn () => Category::factory()->childOf($child)->create());

    $this->actingAs($this->staff, 'staff')
        ->put("{$this->base}/{$child->id}", categoryPayload(['slug' => $child->slug, 'parent_id' => $b->id, 'lock_version' => 0]))
        ->assertSessionHasNoErrors();

    T::seed(function () use ($b, $child, $grandchild) {
        expect($child->refresh()->path)->toBe("/{$b->id}/{$child->id}/")
            ->and($grandchild->refresh()->path)->toBe("/{$b->id}/{$child->id}/{$grandchild->id}/")
            ->and($grandchild->depth)->toBe(3);
    });
});

it('không cho chuyển danh mục vào cây con của chính nó', function () {
    $root = T::seed(fn () => Category::factory()->create());
    $child = T::seed(fn () => Category::factory()->childOf($root)->create());

    $this->actingAs($this->staff, 'staff')
        ->put("{$this->base}/{$root->id}", categoryPayload(['slug' => $root->slug, 'parent_id' => $child->id, 'lock_version' => 0]))
        ->assertSessionHasErrors('parent_id');
});

it('giới hạn độ sâu 5 cấp', function () {
    $node = T::seed(fn () => Category::factory()->create());
    foreach (range(2, 5) as $level) {
        $node = T::seed(fn () => Category::factory()->childOf($node)->create());
    }

    $this->actingAs($this->staff, 'staff')
        ->post($this->base, categoryPayload(['parent_id' => $node->id]))
        ->assertSessionHasErrors('parent_id');
});

it('chặn ghi đè khi dữ liệu đã bị người khác sửa (lock_version)', function () {
    $category = T::seed(fn () => Category::factory()->create());
    $payload = categoryPayload(['slug' => $category->slug, 'lock_version' => 0]);

    $this->actingAs($this->staff, 'staff')->put("{$this->base}/{$category->id}", $payload)->assertSessionHasNoErrors();
    $this->put("{$this->base}/{$category->id}", $payload)->assertSessionHasErrors('lock_version');
});

it('không xoá được danh mục còn con, xoá được danh mục lá', function () {
    $root = T::seed(fn () => Category::factory()->create());
    $leaf = T::seed(fn () => Category::factory()->childOf($root)->create());

    $this->actingAs($this->staff, 'staff')->delete("{$this->base}/{$root->id}")->assertSessionHasErrors('category');
    $this->delete("{$this->base}/{$leaf->id}")->assertRedirect(route('admin.catalog.categories.index'));

    expect(T::seed(fn () => Category::query()->find($leaf->id)))->toBeNull();
});

it('tải ảnh danh mục, khử trùng lặp theo checksum', function () {
    Storage::fake('public');
    $category = T::seed(fn () => Category::factory()->create());
    $image = UploadedFile::fake()->image('banner.jpg', 1600, 2000);

    $this->actingAs($this->staff, 'staff')->post("{$this->base}/{$category->id}/image", ['image' => $image, 'alt' => 'Banner'])->assertSessionHasNoErrors();
    $this->post("{$this->base}/{$category->id}/image", ['image' => $image])->assertSessionHasNoErrors();

    $this->get("{$this->base}/{$category->id}/edit")
        ->assertInertia(fn (Assert $page) => $page->component('Catalog::Categories/Form')->whereNot('category.image_url', null));
    // Một ảnh gốc (khử trùng lặp) + các bản WebP thu nhỏ của chính ảnh đó.
    expect(array_values(array_filter(Storage::disk('public')->allFiles(), fn (string $file): bool => preg_match('/-w\d+\.webp$/', $file) !== 1)))->toHaveCount(1);
});

it('từ chối file không phải ảnh', function () {
    $category = T::seed(fn () => Category::factory()->create());

    $this->actingAs($this->staff, 'staff')
        ->post("{$this->base}/{$category->id}/image", ['image' => UploadedFile::fake()->create('virus.php', 10, 'text/x-php')])
        ->assertSessionHasErrors('image');
});

it('hiển thị cây danh mục theo thứ tự', function () {
    $root = T::seed(fn () => Category::factory()->create(['slug' => 'nu']));
    T::seed(fn () => Category::factory()->childOf($root)->create(['slug' => 'b', 'position' => 2]));
    T::seed(fn () => Category::factory()->childOf($root)->create(['slug' => 'a', 'position' => 1]));

    $this->actingAs($this->staff, 'staff')->get($this->base)
        ->assertInertia(fn (Assert $page) => $page->component('Catalog::Categories/Index')
            ->where('tree.0.slug', 'nu')
            ->where('tree.0.children.0.slug', 'a')
            ->where('tree.0.children.1.slug', 'b'));
});
