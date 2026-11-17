<?php

declare(strict_types=1);

namespace Modules\Ordering\Testing;

use Closure;
use Illuminate\Support\Facades\View;
use Modules\Ordering\Contracts\PackingSlipTemplate;

/**
 * Contract test cho PackingSlipTemplate. Kiểm tra: mã, nhãn; view Blade tồn tại (plugin đã đăng ký namespace view).
 */
final class PackingSlipTemplateContract
{
    /**
     * @param  Closure(): PackingSlipTemplate  $template
     */
    public static function define(string $label, Closure $template): void
    {
        describe("PackingSlipTemplate contract: {$label}", function () use ($template): void {
            it('mã, nhãn; view tồn tại', function () use ($template): void {
                expect($template()->code())->toMatch('/^[a-z][a-z0-9_.-]*$/')
                    ->and($template()->label())->not->toBe('')
                    ->and(View::exists($template()->view()))->toBeTrue();
            });
        });
    }
}
