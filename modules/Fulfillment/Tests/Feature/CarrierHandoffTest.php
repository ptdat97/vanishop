<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Shared\Support\SimpleXlsx;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| Trao đổi file với hãng vận chuyển (0.3.97): xuất .xlsx vận đơn chưa rời kho; nhập lại mã vận đơn từ file hãng xuất (.xlsx)
| hoặc CSV — cột số đơn tìm theo dữ liệu, cột mã/hãng theo tiêu đề.
*/

beforeEach(function () {
    ['s' => $this->s] = C::store(stock: 30);
    T::seed(fn () => DB::table('variants')->where('id', $this->s->id)->update(['weight_gram' => 450]));
    // COD tự xác nhận (mặc định) → Fulfillment tự tạo vận đơn chờ mã.
    $this->place = function (int $quantity = 1, string $phone = '0912 345 678'): Order {
        $api = '/api/storefront/v1';
        $created = $this->postJson("{$api}/carts")->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $cart = $created->json('data.id');
        $this->postJson("{$api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => $quantity], $headers)->assertOk();
        $total = $this->postJson("{$api}/checkout/{$cart}/quote", C::orderPayload(['payment_method' => 'cod']), $headers)->json('data.total.amount');
        $this->postJson("{$api}/checkout/{$cart}/orders", C::orderPayload(['payment_method' => 'cod', 'expected_total' => $total, 'contact' => ['phone' => $phone]]), [...$headers, 'Idempotency-Key' => 'k-'.$cart])->assertCreated();

        return Order::query()->withoutGlobalScopes()->latest('id')->first();
    };
    $this->actingAs(T::staff(['admin.access', 'orders.view', 'fulfillment.view', 'fulfillment.manage']), 'staff');
    $this->upload = fn (string $name, string $path) => new UploadedFile($path, $name, null, null, true);
    $this->tmp = fn (string $extension) => tempnam(sys_get_temp_dir(), 'vani-test-').'.'.$extension;
});

it('xuất file cho hãng: .xlsx, một dòng mỗi vận đơn chưa rời kho, SĐT giữ số 0 đầu, khối lượng, giá trị hàng, COD', function () {
    $a = ($this->place)(2);
    $b = ($this->place)(1, '0987 000 111');
    $response = $this->get('/admin/fulfillment/shipments/export?'.http_build_query(['ids' => [$b->id, $a->id]]))->assertOk()
        ->assertHeader('Content-Type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    expect($response->headers->get('Content-Disposition'))->toContain('don-giao-hang-');

    $rows = SimpleXlsx::read($response->getFile()->getPathname());
    expect($rows[0][0])->toBe('Mã đơn')->and($rows[0][10])->toBe('Tiền thu hộ (COD)')
        ->and(array_column(array_slice($rows, 1), 0))->toBe([$b->number, $a->number])
        ->and($rows[2][2])->toBe('0912345678')
        ->and($rows[2][5])->toBe('Thành phố Hồ Chí Minh')
        ->and($rows[2][6])->toContain('x2')
        ->and($rows[2][7])->toBe('2')
        ->and($rows[2][8])->toBe('900')
        ->and($rows[2][9])->toBe('600000')
        ->and((int) $rows[2][10])->toBe((int) $a->total_amount);
});

it('nhập mã vận đơn từ file Excel hãng xuất (mã đơn của shop ở cột bất kỳ, chuỗi dùng chung) — ghi mã + hãng; nhập lại không trùng; dòng lỗi được báo', function () {
    $a = ($this->place)();
    $b = ($this->place)();
    $path = ($this->tmp)('xlsx');
    carrierExport($path, [
        ['Mã đơn hàng', 'Mã đơn khách hàng', 'Đơn vị vận chuyển', 'Trạng thái'],
        ['GHN1001', $a->number, 'GHN', 'Chờ lấy hàng'],
        ['GHN1002', strtolower($b->number), '', 'Chờ lấy hàng'],
        ['GHN1003', 'LUM2611-999999', 'GHN', 'Chờ lấy hàng'],
    ]);

    $this->post('/admin/fulfillment/shipments/import-tracking', ['file' => ($this->upload)('ghn.xlsx', $path), 'carrier' => 'GHN Express'])->assertRedirect()
        ->assertSessionHas('success', 'Đã ghi 2 mã vận đơn; 0 dòng đã có mã; 1 dòng lỗi.')
        ->assertSessionHasErrors(['import.0' => 'Dòng 4: không có đơn LUM2611-999999.']);
    expect(Shipment::query()->where('order_id', $a->id)->sole()->only(['tracking_number', 'service_code']))->toBe(['tracking_number' => 'GHN1001', 'service_code' => 'GHN'])
        ->and(Shipment::query()->where('order_id', $b->id)->sole()->service_code)->toBe('GHN Express')
        ->and(Shipment::query()->where('order_id', $b->id)->sole()->status->value)->toBe('created');

    $this->post('/admin/fulfillment/shipments/import-tracking', ['file' => ($this->upload)('ghn.xlsx', $path)])->assertRedirect()
        ->assertSessionHas('success', 'Đã ghi 0 mã vận đơn; 2 dòng đã có mã; 1 dòng lỗi.');
});

it('nhập mã từ CSV phân cách ";" (Excel VN) và từ chính file xuất đã điền; file không có cột số đơn bị từ chối', function () {
    $a = ($this->place)();
    $csv = ($this->tmp)('csv');
    file_put_contents($csv, "\xEF\xBB\xBF"."Mã đơn;Mã vận đơn;Hãng\n{$a->number};JT0009;J&T\n");
    $this->post('/admin/fulfillment/shipments/import-tracking', ['file' => ($this->upload)('jt.csv', $csv)])->assertRedirect()
        ->assertSessionHas('success', 'Đã ghi 1 mã vận đơn; 0 dòng đã có mã; 0 dòng lỗi.');
    expect(Shipment::query()->where('order_id', $a->id)->sole()->tracking_number)->toBe('JT0009');

    // File xuất của chính hệ thống, nhân viên điền "Mã vận đơn" rồi tải lên lại.
    $b = ($this->place)();
    $export = SimpleXlsx::read($this->get('/admin/fulfillment/shipments/export?ids[]='.$b->id)->getFile()->getPathname());
    $export[1][11] = 'Viettel Post';
    $export[1][12] = 'VTP777';
    $filled = ($this->tmp)('xlsx');
    SimpleXlsx::write($filled, $export);
    $this->post('/admin/fulfillment/shipments/import-tracking', ['file' => ($this->upload)('filled.xlsx', $filled)])->assertRedirect();
    expect(Shipment::query()->where('order_id', $b->id)->sole()->only(['tracking_number', 'service_code']))->toBe(['tracking_number' => 'VTP777', 'service_code' => 'Viettel Post']);

    $bad = ($this->tmp)('csv');
    file_put_contents($bad, "Tracking,Note\nX1,abc\n");
    $this->post('/admin/fulfillment/shipments/import-tracking', ['file' => ($this->upload)('bad.csv', $bad)])->assertRedirect()
        ->assertSessionHasErrors('import.0');

    $this->actingAs(T::staff(['admin.access', 'orders.view', 'fulfillment.view']), 'staff');
    $this->post('/admin/fulfillment/shipments/import-tracking', ['file' => ($this->upload)('jt.csv', $csv)])->assertForbidden();
});

/** File .xlsx kiểu Excel/hãng xuất: chuỗi trong sharedStrings.xml (ô `t="s"`), không phải ô chữ nội tuyến. */
function carrierExport(string $path, array $rows): void
{
    $strings = [];
    $sheet = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
    foreach ($rows as $r => $row) {
        $sheet .= '<row r="'.($r + 1).'">';
        foreach ($row as $c => $value) {
            if ($value === '') {
                continue;
            }
            $index = array_search($value, $strings, true);
            if ($index === false) {
                $strings[] = $value;
                $index = count($strings) - 1;
            }
            $sheet .= '<c r="'.chr(65 + $c).($r + 1).'" t="s"><v>'.$index.'</v></c>';
        }
        $sheet .= '</row>';
    }
    $zip = new ZipArchive;
    $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
    $zip->addFromString('xl/workbook.xml', '<?xml version="1.0"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Data" sheetId="1" r:id="rId7"/></sheets></workbook>');
    $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId7" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="/xl/worksheets/data.xml"/></Relationships>');
    $zip->addFromString('xl/worksheets/data.xml', $sheet.'</sheetData></worksheet>');
    $zip->addFromString('xl/sharedStrings.xml', '<?xml version="1.0"?><sst xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'.implode('', array_map(fn (string $text): string => '<si><t>'.htmlspecialchars($text, ENT_XML1).'</t></si>', $strings)).'</sst>');
    $zip->close();
}
