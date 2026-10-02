<?php

declare(strict_types=1);

namespace Plugin\HelloWorld\Infrastructure;

use Modules\Cart\Contracts\CartLineOption;
use Modules\Cart\Contracts\InvalidCartLineOption;

/**
 * Implementation tham chiếu của CartLineOption (R26): lời chúc gói quà trên dòng giỏ (miễn phí), chụp sang dòng đơn.
 */
final class GiftMessageOption implements CartLineOption
{
    public const MAX = 60;

    public function normalize(int $variantId, array $values): array
    {
        $message = trim((string) ($values['message'] ?? ''));
        if ($message === '') {
            return [];
        }
        if (mb_strlen($message) > self::MAX) {
            throw new InvalidCartLineOption('Lời chúc tối đa '.self::MAX.' ký tự.');
        }

        return ['message' => $message];
    }
}
