<?php

declare(strict_types=1);

namespace Modules\Storefront\View\Components;

use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;
use Modules\Extension\Facades\Hook;
use Modules\Storefront\Contracts\Data\SlotView;
use Throwable;

/**
 * `<x-vani::hook-slot name="vani.storefront.pdp.after_price" :args="[$product]" />` — render các phần tử plugin
 * trả cho slot theo priority (ADR-025). Chỉ nối thêm; phần tử lỗi (listener hoặc lúc render) bị bỏ, ghi log.
 * Phần tử hợp lệ: SlotView, Htmlable (vd. view()), hoặc null (không có gì để hiện). Chuỗi thô bị bỏ để không chèn HTML chưa escape.
 */
final class HookSlot extends Component
{
    /**
     * @param  list<mixed>  $args
     */
    public function __construct(
        public string $name,
        public array $args = [],
    ) {}

    public function render(): Htmlable
    {
        $html = '';
        foreach (Hook::slot($this->name, ...$this->args) as $item) {
            try {
                $html .= match (true) {
                    $item === null => '',
                    $item instanceof SlotView => view($item->view, $item->data)->render(),
                    $item instanceof Htmlable => $item->toHtml(),
                    default => throw new \UnexpectedValueException('Phần tử slot phải là SlotView hoặc Htmlable, nhận '.get_debug_type($item).'.'),
                };
            } catch (Throwable $exception) {
                Log::error('Render phần tử slot storefront lỗi, bỏ qua.', ['hook' => $this->name, 'exception' => $exception]);
            }
        }

        return new HtmlString($html === '' ? '' : '<div class="vani-slot" data-slot="'.e($this->name).'">'.$html.'</div>');
    }
}
