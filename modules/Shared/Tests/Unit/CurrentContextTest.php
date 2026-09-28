<?php

use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Context\MissingContext;

it('ném lỗi khi chưa có phạm vi', function () {
    (new CurrentContext)->brandIds();
})->throws(MissingContext::class);

it('runAs đổi phạm vi tạm thời rồi khôi phục, kể cả khi có lỗi', function () {
    $context = new CurrentContext;
    $context->set(new ContextScope(Actor::guest(), channelId: 1, brandIds: [1]));

    $inside = $context->runAs(ContextScope::system('test'), fn () => $context->brandIds());
    expect($inside)->toBeNull()->and($context->brandIds())->toBe([1]);

    expect(fn () => $context->runAs(ContextScope::system('test'), fn () => throw new RuntimeException('x')))
        ->toThrow(RuntimeException::class);
    expect($context->brandIds())->toBe([1]);
});

it('phạm vi rỗng không cho brand nào, phạm vi null cho mọi brand', function () {
    expect((new ContextScope(Actor::guest(), brandIds: []))->allowsBrand(1))->toBeFalse()
        ->and((new ContextScope(Actor::guest(), brandIds: [2, 3]))->allowsBrand(3))->toBeTrue()
        ->and(ContextScope::system('reason')->allowsBrand(99))->toBeTrue();
});
