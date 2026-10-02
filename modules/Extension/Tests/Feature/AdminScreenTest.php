<?php

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Extension\Application\Admin\AdminExtensions;
use Modules\Extension\Contracts\Data\FieldDefinition;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Identity\Persistence\Models\StaffUser;

beforeEach(function () {
    $this->style = T::product(null, ['name' => 'Đầm lụa', 'style_code' => 'DL01', 'slug' => 'dam-lua']);
    $this->registry = app(AdminExtensions::class);
    $this->actingAs(StaffUser::factory()->withPermissions(['admin.access', 'catalog.view', 'catalog.manage'])->create(), 'staff');
    $this->payload = fn (array $extensions, string $name = 'Đầm lụa mới') => [
        'style_code' => 'DL01', 'slug' => 'dam-lua', 'status' => 'draft', 'lock_version' => $this->style->fresh()->lock_version,
        'translations' => ['vi' => ['name' => $name]], 'category_ids' => [], 'extensions' => $extensions,
    ];
});

it('lưu phần form của plugin cùng transaction: plugin lỗi → không lưu cả thay đổi của Core', function () {
    $saved = [];
    // `vani.cod` đang bật (plugin hệ thống) — dùng làm chủ sở hữu giả của phần mở rộng.
    $this->registry->section('product', 'vani.cod', 'seo', 'SEO', [FieldDefinition::string('keyword', 'Từ khoá', required: true)],
        load: fn (int $id): array => ['keyword' => $saved[$id] ?? null],
        save: function (int $id, array $values) use (&$saved): void {
            if ($values['keyword'] === 'boom') {
                throw new RuntimeException('plugin hỏng');
            }
            $saved[$id] = $values['keyword'];
        },
    );

    $this->put("/admin/catalog/products/{$this->style->id}", ($this->payload)([]))->assertSessionHasErrors('extensions.vani-cod.seo.keyword');
    $this->put("/admin/catalog/products/{$this->style->id}", ($this->payload)(['vani-cod' => ['seo' => ['keyword' => 'đầm', 'la' => 'x']]]))->assertSessionHasNoErrors();
    expect($saved)->toBe([$this->style->id => 'đầm']);

    expect(fn () => $this->withoutExceptionHandling()->put("/admin/catalog/products/{$this->style->id}", ($this->payload)(['vani-cod' => ['seo' => ['keyword' => 'boom']]], 'Tên không được lưu')))
        ->toThrow(RuntimeException::class);
    expect($this->style->fresh()->translate('name'))->toBe('Đầm lụa mới');
});

it('phần mở rộng của plugin chưa bật bị ẩn; cột lỗi bị bỏ, cột khác vẫn có; bộ lọc giao nhau', function () {
    $other = T::product(null, ['name' => 'Áo', 'style_code' => 'AO01']);
    $this->registry->column('product', 'vani.cod', 'ok', 'Cột OK', fn (array $ids): array => array_fill_keys($ids, 'v'));
    $this->registry->column('product', 'vani.cod', 'broken', 'Cột lỗi', fn (array $ids): array => throw new RuntimeException('x'));
    $this->registry->column('product', 'vani.not-enabled', 'hidden', 'Ẩn', fn (array $ids): array => array_fill_keys($ids, 'h'));
    $this->registry->filter('product', 'vani.cod', 'a', 'A', ['y' => 'Có'], fn (string $value): array => [$this->style->id, $other->id]);
    $this->registry->filter('product', 'vani.bank-transfer', 'b', 'B', ['y' => 'Có'], fn (string $value): array => [$other->id]);

    $this->get('/admin/catalog/products')->assertInertia(fn (Assert $page) => $page
        ->where('extensions.columns', [['key' => 'vani.cod:ok', 'label' => 'Cột OK']])
        ->where("extensions.values.{$this->style->id}", ['vani.cod:ok' => 'v']));

    $this->get('/admin/catalog/products?ext[vani.cod:a]=y&ext[vani.bank-transfer:b]=y')->assertInertia(fn (Assert $page) => $page
        ->has('products.data', 1)->where('products.data.0.id', $other->id));
});

it('thao tác: kiểm tra quyền, ghi audit, hàng loạt báo số thành công/lỗi, khoá lạ → 404', function () {
    $this->registry->action('product', 'vani.cod', 'sync', 'Đồng bộ', 'catalog.manage', fn (int $id): ?string => $id === 999 ? throw new RuntimeException('x') : null, scope: 'both');
    $this->registry->action('product', 'vani.cod', 'danger', 'Nguy hiểm', 'staff.manage', fn (int $id): ?string => null);

    $this->post('/admin/extensions/product/actions/vani.cod/sync', ['ids' => [$this->style->id, 999]])->assertSessionHas('success', '"Đồng bộ": 1 thành công, 1 lỗi.');
    expect(AuditLog::query()->where('action', 'plugin.action')->count())->toBe(1);

    $this->post('/admin/extensions/product/actions/vani.cod/danger', ['ids' => [$this->style->id]])->assertForbidden();
    $this->post('/admin/extensions/product/actions/vani.cod/khong-co', ['ids' => [1]])->assertNotFound();
    $this->post('/admin/extensions/product/actions/vani.cod/sync', ['ids' => []])->assertSessionHasErrors('ids');
});
