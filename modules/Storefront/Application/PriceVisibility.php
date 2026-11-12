<?php

declare(strict_types=1);

namespace Modules\Storefront\Application;

use Modules\Shared\Context\ActorType;
use Modules\Shared\Context\CurrentContext;
use Modules\Tenancy\Contracts\Settings;

/**
 * Chỗ duy nhất quyết định có hiện giá không (`core` / `storefront.price_visibility`, reference-parity §3.2). `members`:
 * khách chưa đăng nhập không thấy giá (thẻ/PDP/Storefront API trả `price: null` + `price_hidden: true`, JSON-LD không có
 * `offers`), không thêm giỏ, không checkout. Trang cache được chạy không phiên nên luôn là bản ẩn giá; khách đã đăng
 * nhập nhận giá sau qua `GET /api/storefront/v1/me/prices`.
 */
final class PriceVisibility
{
    public const SETTING = 'storefront.price_visibility';

    public const PUBLIC = 'public';

    public const MEMBERS = 'members';

    public function __construct(
        private readonly Settings $settings,
        private readonly CurrentContext $context,
    ) {}

    public function membersOnly(): bool
    {
        return $this->settings->get('core', self::SETTING, self::PUBLIC) === self::MEMBERS;
    }

    /** Giá bị ẩn với người đang xem (actor của request hiện tại). */
    public function hidden(): bool
    {
        return $this->membersOnly() && ! $this->customerSignedIn();
    }

    private function customerSignedIn(): bool
    {
        return $this->context->has() && $this->context->actor()->type === ActorType::Customer;
    }
}
