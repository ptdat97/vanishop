<?php

use Modules\Catalog\Tests\Feature\CatalogTestHelpers as T;
use Modules\Customer\Contracts\CustomerDirectory;
use Modules\Customer\Contracts\CustomerSegments;
use Modules\Customer\Domain\CustomerStatus;
use Modules\Customer\Persistence\Models\CustomerGroup;
use Modules\Customer\Tests\Feature\CustomerTestHelpers as H;

require_once __DIR__.'/../../../Catalog/Tests/Feature/CatalogTestHelpers.php';
require_once __DIR__.'/CustomerTestHelpers.php';

beforeEach(function () {
    $this->directory = app(CustomerDirectory::class);
});

it('tìm khách đang hoạt động theo SĐT (chuẩn hoá), email hoặc tên; khách đã gộp không có trong kết quả', function () {
    $lan = H::account('0912345678', 'lan@example.com');
    $lan->update(['full_name' => 'Nguyễn Thị Lan']);
    $merged = H::account('0987654321', 'lan.cu@example.com');
    $merged->update(['full_name' => 'Lan cũ', 'status' => CustomerStatus::Merged]);

    expect(collect($this->directory->search('+84 912 345 678'))->pluck('id')->all())->toBe([$lan->id])
        ->and(collect($this->directory->search('Lan'))->pluck('id')->all())->toBe([$lan->id])
        ->and($this->directory->search('  '))->toBe([])
        ->and($this->directory->summaries([$lan->id, 999_999])[$lan->id]->fullName)->toBe('Nguyễn Thị Lan');
});

it('liệt kê id khách theo nhóm và tag theo lô tăng dần', function () {
    $vip = T::seed(fn () => CustomerGroup::query()->create(['code' => 'vip', 'name' => 'VIP']));
    [$a, $b, $c] = [H::account('0912000001'), H::account('0912000002'), H::account('0912000003')];
    $segments = app(CustomerSegments::class);
    $segments->assignGroup($a->id, $vip->id, 'test');
    $segments->assignGroup($c->id, $vip->id, 'test');
    $segments->tag($c->id, 'sinh-nhat', 'test');
    $segments->tag($b->id, 'sinh-nhat', 'test');

    expect($this->directory->customerIds($vip->id, null))->toBe([$a->id, $c->id])
        ->and($this->directory->customerIds($vip->id, null, afterId: $a->id, limit: 1))->toBe([$c->id])
        ->and($this->directory->customerIds(null, 'sinh-nhat'))->toBe([$b->id, $c->id])
        ->and($this->directory->customerIds($vip->id, 'sinh-nhat'))->toBe([$c->id]);
});
