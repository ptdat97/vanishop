<?php

declare(strict_types=1);

use Modules\Customer\Contracts\Customers;
use Modules\Customer\Contracts\Data\CustomerData;
use Modules\Shared\Context\ActorType;
use Modules\Shared\Context\CurrentContext;

/*
| Helper ngắn của Customer cho plugin và theme (ADR-037, danh sách đóng). Tách khỏi modules/Extension/helpers.php:
| vòng 0 không biết thương mại (R29) nên helper này nằm ở module sở hữu contract. Class trong modules/ KHÔNG dùng
| (inject contract) — arch test chặn.
*/

if (! function_exists('vani_customer')) {
    /**
     * Khách đang đăng nhập (DTO), null với khách vãng lai và ngoài storefront.
     */
    function vani_customer(): ?CustomerData
    {
        $context = app(CurrentContext::class);
        $actor = $context->has() ? $context->actor() : null;

        return $actor?->type === ActorType::Customer && $actor->id !== null ? app(Customers::class)->find($actor->id) : null;
    }
}
