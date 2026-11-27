<?php

use Modules\Extension\Application\Plugins\PluginActivation;
use Modules\Extension\Domain\Plugin\PluginStatus;
use Modules\Extension\Persistence\Models\PluginRecord;

/*
| Danh sách plugin đang bật cache ở store mặc định có hạn 1 ngày (§6b L61): sót một chỗ xoá cache thì không giữ giá trị cũ mãi.
*/

it('bản ghi plugin đổi mà không xoá cache: tối đa một ngày sau tự đọc lại DB', function () {
    $this->useArrayCache(); // travel() vượt TTL: Redis tính TTL theo đồng hồ thật
    expect(app(PluginActivation::class)->isActive('vani.wishlist'))->toBeFalse();
    PluginRecord::query()->create(['id' => 'vani.wishlist', 'version' => '1.5.0', 'status' => PluginStatus::Enabled, 'installed_at' => now()]);

    app(PluginActivation::class)->flush();
    expect(app(PluginActivation::class)->isActive('vani.wishlist'))->toBeFalse();

    $this->travel(25)->hours();
    app(PluginActivation::class)->flush();
    expect(app(PluginActivation::class)->isActive('vani.wishlist'))->toBeTrue();
});
