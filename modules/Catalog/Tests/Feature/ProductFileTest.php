<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;
use Modules\Catalog\Application\Products\ProductFile;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Persistence\Models\Category;
use Modules\Catalog\Persistence\Models\OptionValue;
use Modules\Catalog\Persistence\Models\Style;
use Modules\Catalog\Persistence\Models\Variant;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;

require_once __DIR__.'/CatalogTestHelpers.php';
require_once __DIR__.'/../../../Pricing/Tests/Feature/PricingTestHelpers.php';

/*
| Stabilization A4: nhập/xuất sản phẩm bằng file — mỗi SKU một dòng; xem trước chưa ghi gì; ô trống = giữ nguyên; cột giá theo
| VariantFieldProvider của Pricing.
*/

beforeEach(function () {
    $this->actingAs(T::staff(['admin.access', 'catalog.view', 'catalog.manage', 'pricing.view', 'pricing.manage']), 'staff');
    [$this->brand, $this->dresses] = T::seed(fn () => [Brand::factory()->create(['name' => 'Lumière']), Category::factory()->create(['slug' => 'dam'])]);
    $this->dress = T::product($this->brand->id, ['style_code' => 'DAM01', 'name' => 'Đầm lụa', 'category_ids' => [$this->dresses->id], 'primary_category_id' => $this->dresses->id]);
    $this->variants = P::variants($this->dress, ['S', 'M']);
    P::priceList(['code' => 'base'], [$this->variants[0]->id => [500_000], $this->variants[1]->id => [500_000]]);
    $this->csv = function (array $rows): UploadedFile {
        $lines = array_map(fn (array $row): string => implode(',', array_map(fn ($cell): string => '"'.str_replace('"', '""', (string) $cell).'"', $row)), $rows);

        return UploadedFile::fake()->createWithContent('san-pham.csv', implode("\n", $lines));
    };
    $this->exported = fn (): array => app(ProductFile::class)->export();
});

it('xuất: tiêu đề cố định + cột giá của Pricing; mỗi SKU một dòng với trục/giá trị, danh mục theo mã, giá niêm yết', function () {
    $rows = ($this->exported)();

    expect(array_slice($rows[0], 0, 6))->toBe(['Mã sản phẩm', 'Tên sản phẩm', 'Trạng thái', 'Thương hiệu', 'Danh mục (mã, cách nhau bằng |)', 'Mô tả'])
        ->and($rows[0])->toContain('SKU')->toContain('Giá: Giá bán (niêm yết)')
        ->and($rows)->toHaveCount(3)
        ->and(array_slice($rows[1], 0, 5))->toBe(['DAM01', 'Đầm lụa', 'Đang bán', 'Lumière', 'dam'])
        ->and($rows[1][array_search('SKU', $rows[0], true)])->toBe($this->variants[0]->sku)
        ->and($rows[1][array_search('Tuỳ chọn 1', $rows[0], true)])->toBe('Màu')
        ->and($rows[2][array_search('Tuỳ chọn 2', $rows[0], true)])->toBe('Size')
        ->and($rows[1][array_search('Giá: Giá bán (niêm yết)', $rows[0], true)])->toBe(500_000);

    $this->get('/admin/catalog/products/export')->assertOk()->assertDownload();
});

it('xuất file: số truy vấn không tăng theo số sản phẩm (không N+1 — catalog 50.000 SKU)', function () {
    $count = function (): int {
        $queries = 0;
        DB::listen(function () use (&$queries): void {
            $queries++;
        });
        ($this->exported)();

        return $queries;
    };
    $one = $count();
    foreach (['AO01', 'AO02', 'AO03'] as $code) {
        P::variants(T::product($this->brand->id, ['style_code' => $code, 'category_ids' => [$this->dresses->id]]), ['S', 'M']);
    }
    DB::flushQueryLog();

    expect(count(($this->exported)()))->toBe(9)->and($count())->toBeLessThanOrEqual($one + 2);
});

it('file lớn (quá 200 sản phẩm ghi được): chạy nền theo lô 100 sản phẩm, trang danh sách hỏi tiến độ; dòng lỗi bỏ qua và báo lại', function () {
    $rows = [['Mã sản phẩm', 'Tên sản phẩm']];
    foreach (range(1, 205) as $i) {
        $rows[] = [sprintf('IMP%03d', $i), "Áo nhập {$i}"];
    }
    $rows[] = ['IMP999', '']; // sản phẩm mới thiếu tên → lỗi
    $preview = $this->post('/admin/catalog/products/import', ['file' => ($this->csv)($rows)])->assertOk()->viewData('page')['props'];
    expect($preview['preview']['summary'])->toBe(['create' => 205, 'update' => 0, 'error' => 1])
        ->and($preview['preview']['products'][0]['code'])->toBe('IMP999'); // sản phẩm lỗi lên đầu

    $response = $this->post('/admin/catalog/products/import/'.basename((string) $preview['urls']['apply']))->assertSessionHas('success');
    parse_str((string) parse_url((string) $response->headers->get('Location'), PHP_URL_QUERY), $query);
    $this->get('/admin/catalog/products?import_run='.$query['import_run'])->assertInertia(fn (Assert $page) => $page->where('importRunUrl', route('admin.catalog.products.import.runs.show', $query['import_run'])));

    $run = $this->getJson(route('admin.catalog.products.import.runs.show', $query['import_run']))->assertOk()->json('data');
    expect($run['finished'])->toBeTrue()
        ->and($run['counts'])->toBe(['created' => 205, 'updated' => 0, 'failed_rows' => 0])
        ->and($run['errors'])->toHaveCount(1)
        ->and(T::seed(fn () => Style::query()->where('style_code', 'like', 'IMP%')->count()))->toBe(205);

    $this->actingAs(T::staff(['admin.access', 'catalog.view']), 'staff');
    $this->getJson(route('admin.catalog.products.import.runs.show', $query['import_run']))->assertForbidden();
});

it('nhập lại file đã xuất có sửa: xem trước chưa ghi gì; áp dụng cập nhật tên, trạng thái, khối lượng, giá; ô trống giữ nguyên', function () {
    $rows = ($this->exported)();
    $header = $rows[0];
    $col = fn (string $name): int => array_search($name, $header, true);
    $rows[1][$col('Tên sản phẩm')] = 'Đầm lụa mới';
    $rows[1][$col('Trạng thái')] = 'Ngừng bán';
    $rows[1][$col('Khối lượng (g)')] = '320';
    $rows[1][$col('Giá: Giá bán (niêm yết)')] = '450.000';
    $rows[2][$col('Giá: Giá bán (niêm yết)')] = '';
    $rows[1][$col('Thương hiệu')] = '';

    $this->post('/admin/catalog/products/import', ['file' => ($this->csv)($rows)])->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Catalog::Products/Import')
        ->where('preview.summary', ['create' => 0, 'update' => 1, 'error' => 0])
        ->where('preview.products.0.updated_skus', 2)
        ->where('urls.apply', fn (?string $url): bool => $url !== null));
    expect($this->dress->fresh()->translate('name'))->toBe('Đầm lụa');

    $token = basename((string) $this->post('/admin/catalog/products/import', ['file' => ($this->csv)($rows)])->viewData('page')['props']['urls']['apply']);
    $this->post("/admin/catalog/products/import/{$token}")->assertRedirect('/admin/catalog/products')->assertSessionHas('success', 'Đã tạo 0 và cập nhật 1 sản phẩm.');

    $dress = $this->dress->fresh();
    expect($dress->translate('name'))->toBe('Đầm lụa mới')
        ->and($dress->status->value)->toBe('archived')
        ->and($dress->brand_id)->toBe($this->brand->id)
        ->and($this->variants[0]->fresh()->weight_gram)->toBe(320)
        ->and(DB::table('prices')->where('variant_id', $this->variants[0]->id)->value('amount'))->toEqual(450_000)
        ->and(DB::table('prices')->where('variant_id', $this->variants[1]->id)->value('amount'))->toEqual(500_000);
    // Token dùng một lần.
    $this->post("/admin/catalog/products/import/{$token}")->assertSessionHasErrors(['file' => 'File xem trước đã hết hạn (30 phút). Vui lòng tải file lên lại.']);
});

it('tạo sản phẩm mới có trục Màu × Size: giá trị chưa có thì tạo, thương hiệu chưa có thì tạo, SKU tự sinh khi để trống, mặc định nháp', function () {
    $header = ($this->exported)()[0];
    $row = fn (array $cells): array => array_map(fn (string $column): string => (string) ($cells[$column] ?? ''), $header);
    $rows = [$header,
        $row(['Mã sản phẩm' => 'ao01', 'Tên sản phẩm' => 'Áo thun', 'Thương hiệu' => 'Urban X', 'Danh mục (mã, cách nhau bằng |)' => 'dam', 'Tuỳ chọn 1' => 'Màu', 'Giá trị 1' => 'Xanh rêu', 'Tuỳ chọn 2' => 'Size', 'Giá trị 2' => 'S', 'Giá: Giá bán (niêm yết)' => '199000']),
        $row(['Mã sản phẩm' => 'AO01', 'Tuỳ chọn 1' => 'Màu', 'Giá trị 1' => 'Xanh rêu', 'Tuỳ chọn 2' => 'Size', 'Giá trị 2' => 'M', 'SKU' => 'AO01-XR-M', 'Khối lượng (g)' => '250']),
    ];

    $preview = $this->post('/admin/catalog/products/import', ['file' => ($this->csv)($rows)])->viewData('page')['props'];
    expect($preview['preview']['summary'])->toBe(['create' => 1, 'update' => 0, 'error' => 0])
        ->and($preview['preview']['products'][0]['notes'])->toContain('Sẽ tạo thương hiệu "Urban X".')->toContain('Sẽ tạo giá trị "Xanh rêu" cho "Màu".');
    $this->post($preview['urls']['apply'])->assertSessionHas('success', 'Đã tạo 1 và cập nhật 0 sản phẩm.');

    $shirt = Style::query()->where('style_code', 'AO01')->sole();
    $variants = Variant::query()->where('style_id', $shirt->id)->orderBy('id')->get();
    expect($shirt)->status->value->toBe('draft')->primary_category_id->toBe($this->dresses->id)
        ->and($shirt->brand?->name)->toBe('Urban X')
        ->and($variants)->toHaveCount(2)
        ->and($variants[1])->sku->toBe('AO01-XR-M')->weight_gram->toBe(250)
        ->and($variants[0]->sku)->toStartWith('AO01-')
        ->and(OptionValue::query()->whereHas('translations', fn ($query) => $query->where('name', 'Xanh rêu'))->count())->toBe(1)
        ->and(DB::table('prices')->where('variant_id', $variants[0]->id)->value('amount'))->toEqual(199_000);
});

it('lỗi theo sản phẩm: danh mục, trạng thái, SKU của sản phẩm khác, sản phẩm mới thiếu tên — sản phẩm lỗi bỏ qua, sản phẩm đúng vẫn ghi', function () {
    $header = ($this->exported)()[0];
    $row = fn (array $cells): array => array_map(fn (string $column): string => (string) ($cells[$column] ?? ''), $header);
    $rows = [$header,
        $row(['Mã sản phẩm' => 'X1', 'Tên sản phẩm' => 'A', 'Danh mục (mã, cách nhau bằng |)' => 'khong-co']),
        $row(['Mã sản phẩm' => 'X2', 'Tên sản phẩm' => 'B', 'Trạng thái' => 'Bán chạy']),
        $row(['Mã sản phẩm' => 'X3', 'Tên sản phẩm' => 'C', 'SKU' => $this->variants[0]->sku]),
        $row(['Mã sản phẩm' => 'X4']),
        $row(['Mã sản phẩm' => 'X5', 'Tên sản phẩm' => 'Túi', 'SKU' => 'TUI-X5']),
    ];

    $props = $this->post('/admin/catalog/products/import', ['file' => ($this->csv)($rows)])->viewData('page')['props'];
    $errors = collect($props['preview']['products'])->keyBy('code')->map(fn (array $product): array => $product['errors']);
    expect($props['preview']['summary'])->toBe(['create' => 1, 'update' => 0, 'error' => 4])
        ->and($errors['X1'])->toBe(['Không có danh mục mã "khong-co".'])
        ->and($errors['X2'][0])->toStartWith('Trạng thái "Bán chạy" không hợp lệ')
        ->and($errors['X3'])->toBe(['Dòng 4: SKU '.$this->variants[0]->sku.' đã thuộc sản phẩm khác.'])
        ->and($errors['X4'])->toBe(['Sản phẩm mới cần tên sản phẩm.']);

    $this->post($props['urls']['apply']);
    expect(Style::query()->whereIn('style_code', ['X1', 'X2', 'X3', 'X4'])->count())->toBe(0)
        ->and(Variant::query()->where('sku', 'TUI-X5')->exists())->toBeTrue();
});

it('file không có cột Mã sản phẩm → không có gì để áp dụng; chỉ xem catalog thì xuất được, không nhập được', function () {
    $this->post('/admin/catalog/products/import', ['file' => ($this->csv)([['Tên', 'Giá'], ['A', '1']])])
        ->assertInertia(fn (Assert $page) => $page->where('urls.apply', null)->where('preview.products.0.errors.0', fn (string $message): bool => str_starts_with($message, 'File thiếu cột "Mã sản phẩm"')));

    $this->actingAs(T::staff(['admin.access', 'catalog.view']), 'staff');
    $this->get('/admin/catalog/products/export')->assertOk();
    $this->post('/admin/catalog/products/import', ['file' => ($this->csv)([['Mã sản phẩm'], ['A']])])->assertForbidden();
    $this->post('/admin/catalog/products/import/'.str_repeat('a', 40))->assertForbidden();
});
