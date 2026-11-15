<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Extension\Application\Plugins\PluginManager;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Payment\Persistence\Models\Payment;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Plugin\BankStatement\BankStatementServiceProvider;

require_once __DIR__.'/../../../../../modules/Checkout/Tests/Feature/CheckoutTestHelpers.php';

/*
| vani.bank-statement — tham chiếu EP v3 X5: công cụ tải file trên trang Thanh toán, Payments (xác nhận chuyển khoản),
| Reconciliations (dòng lệch chờ xử lý, ô Việc cần làm).
*/

beforeEach(function () {
    config(['vani.bank-transfer.account' => ['bank' => 'VCB', 'account_number' => '0123456789', 'account_name' => 'VANI']]);
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        app(PluginManager::class)->install(BankStatementServiceProvider::ID);
        app(PluginManager::class)->enable(BankStatementServiceProvider::ID);
    });
    app()->register(BankStatementServiceProvider::class);
    ['s' => $this->s] = C::store(stock: 20);
    $this->place = function (): Order {
        $created = $this->postJson('/api/storefront/v1/carts')->assertCreated();
        $headers = ['X-Vani-Cart-Token' => $created->json('meta.token')];
        $this->postJson("/api/storefront/v1/carts/{$created->json('data.id')}/lines", ['variant_id' => $this->s->id, 'quantity' => 1], $headers)->assertOk();
        $this->postJson("/api/storefront/v1/checkout/{$created->json('data.id')}/orders", C::orderPayload(['payment_method' => 'manual_bank_transfer', 'expected_total' => 330_000]), [...$headers, 'Idempotency-Key' => 'k-'.$created->json('data.id')])->assertCreated();

        return Order::query()->withoutGlobalScopes()->latest('id')->firstOrFail();
    };
    $this->actingAs(T::staff(['admin.access', 'payments.view', 'payments.confirm']), 'staff');
    $this->tool = route('admin.extensions.tool', ['resource' => 'payment', 'plugin' => BankStatementServiceProvider::ID, 'key' => 'import_statement']);
});

it('khớp số đơn + đúng tiền → xác nhận đã nhận tiền; lệch tiền / không thấy số đơn → "Đối soát lệch"; nhập lại không trùng; xử lý dòng lệch', function () {
    [$a, $b] = [($this->place)(), ($this->place)()];
    $csv = "Mã GD;Số tiền;Nội dung\nFT001;330.000;CK {$a->number} cam on\nFT002;300000;{$b->number}\nFT003;150000;chuyen tien hang\n";

    $this->post($this->tool, ['file' => UploadedFile::fake()->createWithContent('saoke.csv', $csv)])
        ->assertSessionHas('success', 'Đã xác nhận 1 chuyển khoản; 2 giao dịch lệch (xem "Đối soát lệch"); bỏ qua 0 giao dịch đã nhập.');
    $this->post($this->tool, ['file' => UploadedFile::fake()->createWithContent('saoke.csv', $csv)])
        ->assertSessionHas('success', 'Đã xác nhận 0 chuyển khoản; 0 giao dịch lệch (xem "Đối soát lệch"); bỏ qua 3 giao dịch đã nhập.');

    expect(Payment::query()->withoutGlobalScopes()->where('order_id', $a->id)->value('status')->value)->toBe('paid')
        ->and(Payment::query()->withoutGlobalScopes()->where('order_id', $b->id)->value('status')->value)->toBe('pending');
    $page = $this->get('/admin/payment/payments')->viewData('page')['props'];
    expect(array_column($page['reconciliation'], 'reference'))->toBe(['FT003', 'FT002'])
        ->and($page['reconciliation'][1])->toMatchArray(['expected' => 330_000, 'actual' => 300_000, 'note' => "Đơn {$b->number}: số tiền không khớp"]);
    $task = fn () => collect($this->get('/admin')->viewData('page')['props']['tasks'])->firstWhere('key', 'payments.reconciliation_mismatch');
    expect($task()['count'])->toBe(2);

    $this->post('/admin/payment/reconciliation/'.$page['reconciliation'][0]['id'].'/resolve', ['note' => 'Không phải tiền hàng'])->assertSessionHasNoErrors();
    expect($task()['count'])->toBe(1)->and(DB::table('reconciliation_entries')->where('reference', 'FT003')->value('status'))->toBe('resolved');
});

it('sao kê thiếu cột bắt buộc bị từ chối', function () {
    $this->post($this->tool, ['file' => UploadedFile::fake()->createWithContent('saoke.csv', "Ngày;Tiền\n1;2\n")])->assertSessionHasErrors('file');
});
