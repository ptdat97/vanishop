<?php

declare(strict_types=1);

namespace Modules\Extension\Application\Hooks\Points;

use Illuminate\View\View;
use Modules\Extension\Application\Hooks\HookManager;

/**
 * View composer: điểm mở rộng tự động cho MỌI view của theme storefront — filter `vani.storefront.view.<view>` trên
 * dữ liệu view (`theme::pages.product` → `vani.storefront.view.pages.product`). Chỉ khi có listener; chỉ dữ liệu
 * hiển thị — giá/tồn/khuyến mãi khi đặt hàng vẫn tính lại ở Core.
 */
final class ViewHooks
{
    public function __construct(private readonly HookManager $hooks) {}

    public function compose(View $view): void
    {
        $name = $view->name();
        if (! str_starts_with($name, 'theme::')) {
            return;
        }

        $hook = 'vani.storefront.view.'.str_replace('/', '.', substr($name, strlen('theme::')));
        if (! $this->hooks->hasListeners($hook)) {
            return;
        }

        $data = array_diff_key($view->getData(), array_flip(['__env', 'app', 'errors']));
        $view->with($this->hooks->filter($hook, $data, $name));
    }
}
