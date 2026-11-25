<?php

declare(strict_types=1);

namespace Modules\Storefront\View\Components;

use Closure;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;
use Illuminate\View\Component;
use Modules\Extension\Facades\Hook;
use Modules\Storefront\Application\Builder\BuilderEngine;
use Modules\Storefront\Contracts\Data\SlotView;
use Throwable;

/**
 * `<x-vani::hook-slot name="vani.storefront.pdp.after_price" :args="[$product]" />` — render các phần tử plugin
 * trả cho slot theo priority (ADR-025). Chỉ nối thêm; phần tử lỗi (listener hoặc lúc render) bị bỏ, ghi log.
 * Phần tử hợp lệ: SlotView, Htmlable (vd. view()), hoặc null (không có gì để hiện). Chuỗi thô bị bỏ để không chèn HTML chưa escape.
 * Thứ tự: block admin đặt vào slot qua Builder Engine (slot khai báo `builder: true`) trước, phần tử plugin chèn bằng onSlot sau.
 */
final class HookSlot extends Component
{
    /**
     * @param  list<mixed>  $args
     */
    public function __construct(
        public string $name,
        public array $args = [],
        public bool $wrap = true,
    ) {}

    public function render(): Htmlable
    {
        return $this->renderSlot();
    }

    public function resolveView(): Closure
    {
        return fn (array $data = []): Htmlable => $this->renderSlot();
    }

    private function renderSlot(): Htmlable
    {
        // Thành phần hệ thống trước: block admin đặt qua Builder Engine, rồi tới phần tử plugin chèn bằng onSlot.
        $html = '';
        try {
            $html .= app(BuilderEngine::class)->renderSlot($this->name, $this->args)->toHtml();
        } catch (Throwable $exception) {
            Log::error('Render block Builder Engine tại slot lỗi, bỏ qua.', ['hook' => $this->name, 'exception' => $exception]);
        }
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

        if ($html === '') {
            return new HtmlString('');
        }

        // Blade luôn gán attributes (withAttributes) trước khi render component.
        $attributes = $this->attributes->merge(['class' => 'vani-slot', 'data-slot' => $this->name]);

        return new HtmlString($this->wrap ? '<div '.$attributes.'>'.$html.'</div>' : $html);
    }
}
