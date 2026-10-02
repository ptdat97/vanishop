<?php

declare(strict_types=1);

namespace Modules\Storefront\Contracts\Data;

/**
 * Phần tử plugin trả cho slot storefront (ADR-025): view của plugin + dữ liệu. Theme render tại vị trí slot,
 * chỉ nối thêm. Plugin khai báo namespace view của mình (vd. `$this->loadViewsFrom(..., 'vani-reviews')`).
 *
 *   $this->onSlot('vani.storefront.pdp.after_title', fn (array $product) => new SlotView('vani-reviews::stars', ['slug' => $product['slug']]));
 */
final readonly class SlotView
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function __construct(
        public string $view,
        public array $data = [],
    ) {}
}
