<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Context\MissingContext;
use Modules\Shared\Persistence\Concerns\BelongsToBrand;
use Modules\Shared\Persistence\Concerns\BrandAccessDenied;

final class BrandScopedFixture extends Model
{
    use BelongsToBrand;

    protected $table = 'brand_scoped_fixtures';

    protected $fillable = ['brand_id', 'name'];
}

beforeEach(function () {
    Schema::create('brand_scoped_fixtures', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('brand_id');
        $table->string('name');
        $table->timestamps();
    });

    $this->context = app(CurrentContext::class);
    $this->context->runAs(ContextScope::system('seed'), function () {
        BrandScopedFixture::query()->create(['brand_id' => 1, 'name' => 'A']);
        BrandScopedFixture::query()->create(['brand_id' => 2, 'name' => 'B']);
    });
});

it('chỉ đọc được dữ liệu của brand trong phạm vi', function () {
    $this->context->set(new ContextScope(Actor::staff(1), brandIds: [1]));

    expect(BrandScopedFixture::query()->pluck('name')->all())->toBe(['A'])
        ->and(BrandScopedFixture::query()->find(2))->toBeNull();
});

it('không ghi được dữ liệu vào brand ngoài phạm vi', function () {
    $this->context->set(new ContextScope(Actor::staff(1), brandIds: [1]));

    BrandScopedFixture::query()->create(['brand_id' => 2, 'name' => 'Hack']);
})->throws(BrandAccessDenied::class);

it('phạm vi cấp Owner thấy mọi brand', function () {
    $this->context->set(new ContextScope(Actor::staff(1), brandIds: null));

    expect(BrandScopedFixture::query()->count())->toBe(2);
});

it('thiếu context thì không truy vấn được, không mặc định là tất cả', function () {
    $this->context->clear();

    BrandScopedFixture::query()->get();
})->throws(MissingContext::class);
