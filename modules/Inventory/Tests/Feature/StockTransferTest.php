<?php

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Catalog\Persistence\Models\Brand;
use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Identity\Persistence\Models\AuditLog;
use Modules\Inventory\Application\StockTransferService;
use Modules\Inventory\Domain\TransferStatus;
use Modules\Inventory\Persistence\Models\StockTransfer;
use Modules\Inventory\Tests\Feature\InventoryTestHelpers as I;
use Modules\Pricing\Tests\Feature\PricingTestHelpers as P;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;

require_once __DIR__.'/InventoryTestHelpers.php';

beforeEach(function () {
    $this->brand = Brand::factory()->create();
    [$this->s, $this->m] = P::variants(T::product($this->brand->id));
    $this->hn = I::location(['code' => 'WH-HN', 'priority' => 10]);
    $this->hcm = I::location(['code' => 'WH-HCM', 'priority' => 5]);
    app(CurrentContext::class)->set(ContextScope::system('test'));
    $this->service = app(StockTransferService::class);
    $this->level = fn ($location, $variant) => DB::table('stock_levels')->where('location_id', $location->id)->where('variant_id', $variant->id)->first();
    $this->movements = fn () => DB::table('stock_movements')->orderBy('id')->pluck('type')->all();
});

it('pending không đổi tồn; gửi trừ kho đi; nhận cộng kho đến; sổ biến động đúng', function () {
    I::stock($this->hn, $this->s->id, 10);
    $transfer = $this->service->create($this->hn, $this->hcm, [$this->s->id => 4]);

    expect($transfer->status)->toBe(TransferStatus::Pending)
        ->and(($this->level)($this->hn, $this->s)->on_hand)->toBe(10)
        ->and(($this->level)($this->hcm, $this->s))->toBeNull();

    $this->service->ship($transfer->fresh());
    expect($transfer->fresh()->status)->toBe(TransferStatus::Shipped)
        ->and(($this->level)($this->hn, $this->s)->on_hand)->toBe(6);

    $this->service->receive($transfer->fresh());
    expect($transfer->fresh()->status)->toBe(TransferStatus::Received)
        ->and(($this->level)($this->hcm, $this->s)->on_hand)->toBe(4)
        ->and(($this->movements)())->toBe(['transfer_out', 'transfer_in'])
        ->and(StockTransfer::query()->whereKey($transfer->id)->value('received_at'))->not->toBeNull();
});

it('hàng đang đi đường không bán được: ATS kho đi giảm khi gửi, kho đến tăng khi nhận', function () {
    I::stock($this->hn, $this->s->id, 5);
    $transfer = $this->service->create($this->hn, $this->hcm, [$this->s->id => 2]);

    $this->service->ship($transfer->fresh());
    $atsInTransit = (int) ($this->level)($this->hn, $this->s)->on_hand;

    $this->service->receive($transfer->fresh());
    expect($atsInTransit)->toBe(3)
        ->and(($this->level)($this->hcm, $this->s)->on_hand)->toBe(2);
});

it('nhận thiếu: chỉ cộng kho đến số nhận, lưu số nhận trên dòng', function () {
    I::stock($this->hn, $this->s->id, 10);
    $transfer = $this->service->create($this->hn, $this->hcm, [$this->s->id => 4]);
    $this->service->ship($transfer->fresh());

    $this->service->receive($transfer->fresh(), [$this->s->id => 3]);

    expect(($this->level)($this->hcm, $this->s)->on_hand)->toBe(3)
        ->and((int) DB::table('stock_transfer_lines')->where('stock_transfer_id', $transfer->id)->value('received_quantity'))->toBe(3);
});

it('huỷ khi pending: không đổi tồn, không có biến động', function () {
    I::stock($this->hn, $this->s->id, 10);
    $transfer = $this->service->create($this->hn, $this->hcm, [$this->s->id => 4]);

    $this->service->cancel($transfer, 'nhập sai');

    expect($transfer->fresh()->status)->toBe(TransferStatus::Cancelled)
        ->and(($this->level)($this->hn, $this->s)->on_hand)->toBe(10)
        ->and(($this->movements)())->toBe([]);
});

it('huỷ sau khi gửi: nhập lại kho đi', function () {
    I::stock($this->hn, $this->s->id, 10);
    $transfer = $this->service->create($this->hn, $this->hcm, [$this->s->id => 4]);
    $this->service->ship($transfer->fresh());

    $this->service->cancel($transfer->fresh(), 'hết xe');

    expect($transfer->fresh()->status)->toBe(TransferStatus::Cancelled)
        ->and(($this->level)($this->hn, $this->s)->on_hand)->toBe(10)
        ->and(($this->movements)())->toBe(['transfer_out', 'transfer_in']);
});

it('gửi vượt tồn bị từ chối, không đổi tồn và không ghi biến động', function () {
    I::stock($this->hn, $this->s->id, 2);
    $transfer = $this->service->create($this->hn, $this->hcm, [$this->s->id => 5]);

    $error = null;
    try {
        $this->service->ship($transfer->fresh());
    } catch (ValidationException $exception) {
        $error = $exception;
    }

    expect($error)->not->toBeNull()
        ->and($error->errors())->toHaveKey('business')
        ->and($transfer->fresh()->status)->toBe(TransferStatus::Pending)
        ->and(($this->level)($this->hn, $this->s)->on_hand)->toBe(2)
        ->and(($this->movements)())->toBe([]);
});

it('không cho chuyển cùng một location hoặc tới location do hệ thống ngoài quản lý', function () {
    $erp = I::location(['code' => 'WH-ERP', 'stock_authority' => 'erp']);

    expect(fn () => $this->service->create($this->hn, $this->hn, [$this->s->id => 1]))
        ->toThrow(ValidationException::class);
    expect(fn () => $this->service->create($this->hn, $erp, [$this->s->id => 1]))
        ->toThrow(ValidationException::class);

    $erpErrors = null;
    try {
        $this->service->create($this->hn, $erp, [$this->s->id => 1]);
    } catch (ValidationException $exception) {
        $erpErrors = $exception;
    }
    expect($erpErrors->errors())->toHaveKey('business')
        ->and(StockTransfer::query()->count())->toBe(0);
});

it('vòng đời sai thứ tự bị từ chối (nhận khi pending, gửi lại sau khi nhận, huỷ sau khi nhận)', function () {
    I::stock($this->hn, $this->s->id, 10);
    $transfer = $this->service->create($this->hn, $this->hcm, [$this->s->id => 2]);

    expect(fn () => $this->service->receive($transfer->fresh()))->toThrow(ValidationException::class);

    $this->service->ship($transfer->fresh());
    $this->service->receive($transfer->fresh());

    expect(fn () => $this->service->ship($transfer->fresh()))->toThrow(ValidationException::class);
    expect(fn () => $this->service->cancel($transfer->fresh(), 'x'))->toThrow(ValidationException::class);
    expect(($this->level)($this->hcm, $this->s)->on_hand)->toBe(2);
});

it('thao tác nhân viên ghi audit cho mọi bước vòng đời', function () {
    I::stock($this->hn, $this->s->id, 10);
    $transfer = $this->service->create($this->hn, $this->hcm, [$this->s->id => 2]);
    $this->service->ship($transfer->fresh());
    $this->service->receive($transfer->fresh());

    expect(AuditLog::query()->where('action', 'like', 'inventory.transfer.%')->orderBy('id')->pluck('action')->all())
        ->toBe(['inventory.transfer.created', 'inventory.transfer.shipped', 'inventory.transfer.received']);
});
