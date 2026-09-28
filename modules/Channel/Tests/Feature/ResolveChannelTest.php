<?php

use Illuminate\Support\Facades\Route;
use Modules\Brand\Persistence\Models\Brand;
use Modules\Channel\Persistence\Models\Channel;
use Modules\Shared\Context\CurrentContext;

beforeEach(function () {
    Route::middleware(['web', 'vani.channel'])->group(function () {
        Route::get('/__probe', fn (CurrentContext $context) => response()->json([
            'channel' => $context->channelId(),
            'brands' => $context->brandIds(),
            'locale' => app()->getLocale(),
        ]));
        Route::get('/lumiere/__probe', fn (CurrentContext $context) => response()->json(['channel' => $context->channelId()]));
    });
});

it('xác định kênh và brand theo domain', function () {
    $brand = Brand::factory()->create();
    $channel = Channel::factory()->forBrand($brand, 'lumiere.test')->create();

    $this->get('http://lumiere.test/__probe')
        ->assertOk()
        ->assertJson(['channel' => $channel->id, 'brands' => [$brand->id], 'locale' => 'vi']);
});

it('ưu tiên path prefix dài nhất trên cùng host', function () {
    $group = Channel::factory()->forBrand(Brand::factory()->create(), 'vani.test')->create();
    $lumiere = Channel::factory()->forBrand(Brand::factory()->create(), 'vani.test', '/lumiere')->create();

    $this->get('http://vani.test/lumiere/__probe')->assertJson(['channel' => $lumiere->id]);
    $this->get('http://vani.test/__probe')->assertJson(['channel' => $group->id]);
});

it('kênh đa brand đưa mọi brand vào phạm vi', function () {
    [$a, $b] = Brand::factory()->count(2)->create();
    $channel = Channel::factory()->forBrand($a, 'house.test')->create();
    $channel->brands()->attach($b->id);

    $this->get('http://house.test/__probe')->assertJson(['brands' => [$a->id, $b->id]]);
});

it('trả 404 khi domain không thuộc kênh nào hoặc kênh đã tắt', function () {
    Channel::factory()->forBrand(Brand::factory()->create(), 'off.test')->create(['status' => 'inactive']);

    $this->get('http://unknown.test/__probe')->assertNotFound();
    $this->get('http://off.test/__probe')->assertNotFound();
});
