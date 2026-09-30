<?php

use Modules\Notification\Domain\TemplateRenderer;

it('thay biến, biến thiếu thành rỗng, render cả mảng lồng', function () {
    expect(TemplateRenderer::render('Đơn {{ order_number }} của {{customer_name}}{{ missing }}.', ['order_number' => 'LU-1', 'customer_name' => 'Lan']))
        ->toBe('Đơn LU-1 của Lan.')
        ->and(TemplateRenderer::render(null, []))->toBeNull()
        ->and(TemplateRenderer::renderArray(['template_id' => '123', 'params' => ['code' => '{{ order_number }}', 'n' => 5]], ['order_number' => 'LU-1']))
        ->toBe(['template_id' => '123', 'params' => ['code' => 'LU-1', 'n' => 5]])
        ->and(TemplateRenderer::variablesIn('{{ a }} {{b}} {{ a }}'))->toBe(['a', 'b']);
});
