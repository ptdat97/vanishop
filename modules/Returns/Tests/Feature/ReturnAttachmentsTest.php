<?php

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Checkout\Tests\Feature\CheckoutTestHelpers as C;
use Modules\Fulfillment\Application\FulfillmentService;
use Modules\Fulfillment\Domain\ShipmentStatus;
use Modules\Fulfillment\Persistence\Models\Shipment;
use Modules\Ordering\Persistence\Models\Order;
use Modules\Returns\Contracts\ReturnRejected;
use Modules\Returns\Contracts\Returns;
use Modules\Returns\Persistence\Models\ReturnAttachment;
use Modules\Returns\Persistence\Models\ReturnRequest;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/../../../Checkout/Tests/Feature/CheckoutTestHelpers.php';

beforeEach(function () {
    Storage::fake('local');
    ['s' => $this->s] = C::store();
    $this->api = '/api/storefront/v1';
    $created = $this->postJson("{$this->api}/carts")->assertCreated();
    $cartHeaders = ['X-Vani-Cart-Token' => $created->json('meta.token')];
    $cart = $created->json('data.id');
    $this->postJson("{$this->api}/carts/{$cart}/lines", ['variant_id' => $this->s->id, 'quantity' => 2], $cartHeaders)->assertOk();
    $placed = $this->postJson("{$this->api}/checkout/{$cart}/orders", C::orderPayload(['expected_total' => 600_000]), [...$cartHeaders, 'Idempotency-Key' => "k-{$cart}"])->assertCreated();
    app(CurrentContext::class)->runAs(ContextScope::system('test'), function () {
        $shipment = Shipment::query()->latest('id')->first();
        $service = app(FulfillmentService::class);
        $service->book($shipment->id, 'T'.$shipment->id);
        foreach ([ShipmentStatus::PickedUp, ShipmentStatus::Delivered] as $status) {
            $service->updateStatus($shipment->id, $status, "test:{$status->value}", 'staff');
        }
    });
    $this->order = Order::query()->withoutGlobalScopes()->latest('id')->first();
    $this->headers = ['X-Vani-Order-Token' => $placed->json('data.access_token')];
    $this->request = fn (array $photos) => $this->post("{$this->api}/orders/{$this->order->public_id}/returns", [
        'lines' => [['order_line_id' => $this->order->lines()->value('id'), 'quantity' => 1]], 'reason_code' => 'defective', 'photos' => $photos,
    ], [...$this->headers, 'Accept' => 'application/json']);
});

it('khách gửi ảnh cùng yêu cầu: lưu disk riêng tư, link có chữ ký mới xem được; quá 5 ảnh hoặc sai loại thì từ chối trước khi tạo yêu cầu', function () {
    ($this->request)(array_map(fn (int $i) => UploadedFile::fake()->image("anh{$i}.jpg"), range(1, 6)))->assertUnprocessable();
    ($this->request)([UploadedFile::fake()->create('virus.php', 10, 'application/x-php')])->assertUnprocessable();
    expect(ReturnRequest::query()->count())->toBe(0);

    $response = ($this->request)([UploadedFile::fake()->image('loi.jpg'), UploadedFile::fake()->image('loi2.png')])->assertCreated();
    $attachments = $response->json('data.returns.0.attachments');
    expect($attachments)->toHaveCount(2);
    Storage::disk('local')->assertExists(ReturnAttachment::query()->first()->path);

    $this->get($attachments[0]['url'])->assertOk()->assertHeader('Content-Type', 'image/jpeg');
    $this->get(route('returns.attachment', ['attachment' => $attachments[0]['id']]))->assertForbidden();
});

it('không thêm ảnh khi yêu cầu đã xử lý; tổng ảnh không vượt 5', function () {
    ($this->request)([UploadedFile::fake()->image('a.jpg')])->assertCreated();
    $return = ReturnRequest::query()->sole();
    $returns = app(Returns::class);

    expect(fn () => $returns->attachPhotos($this->order->id, $return->public_id, array_map(fn (int $i) => UploadedFile::fake()->image("{$i}.jpg"), range(1, 5)), 'customer'))
        ->toThrow(ReturnRejected::class, '5');

    $return->update(['status' => 'cancelled']);
    expect(fn () => $returns->attachPhotos($this->order->id, $return->public_id, [UploadedFile::fake()->image('b.jpg')], 'customer'))
        ->toThrow(ReturnRejected::class);
});
