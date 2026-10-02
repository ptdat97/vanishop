<?php

use Modules\Shared\Context\Actor;
use Modules\Shared\Context\ContextScope;
use Modules\Shared\Context\CurrentContext;
use Modules\Shared\Context\MissingContext;

it('ném lỗi khi chưa có phạm vi', function () {
    (new CurrentContext)->actor();
})->throws(MissingContext::class);

it('runAs đổi phạm vi tạm thời rồi khôi phục, kể cả khi có lỗi', function () {
    $context = new CurrentContext;
    $context->set(new ContextScope(Actor::guest(), 'en'));

    $inside = $context->runAs(ContextScope::system('test'), fn () => $context->locale());
    expect($inside)->toBeNull()->and($context->locale())->toBe('en');

    expect(fn () => $context->runAs(ContextScope::system('test'), fn () => throw new RuntimeException('x')))
        ->toThrow(RuntimeException::class);
    expect($context->locale())->toBe('en');
});
