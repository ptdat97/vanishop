<?php

declare(strict_types=1);

use Modules\Storefront\Contracts\Data\LinkTarget;
use Modules\Storefront\Contracts\LinkResolver;

/*
| Helper link có kiểu của Storefront cho plugin và theme (ADR-037/038). Tách khỏi modules/Extension/helpers.php:
| vòng 0 không biết thương mại (R29) nên helper này nằm ở module sở hữu contract. Class trong modules/ KHÔNG dùng
| (inject contract) — arch test chặn.
*/

if (! function_exists('vani_link')) {
    /**
     * URL của link có kiểu theo ngôn ngữ hiện hành; null khi đối tượng đã ẩn/xoá. `vani_link(['type' => 'category', 'value' => '12'])`.
     *
     * @param  LinkTarget|array<string, mixed>  $target
     */
    function vani_link(LinkTarget|array $target): ?string
    {
        $target = is_array($target) ? LinkTarget::fromArray($target) : $target;
        if ($target === null) {
            return null;
        }
        $resolved = app(LinkResolver::class)->resolve($target, app()->getLocale());

        return $resolved->exists ? $resolved->url : null;
    }
}
